<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$me    = get_current_user_data();
$users = db_read('users.json');
$posts = db_read('posts.json');

// ── Admin action logger ──────────────────────────────────────
function log_admin_action(array $me, string $action, string $target_type = '', int $target_id = 0, string $details = ''): void {
    try {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);
        db()->prepare(
            'INSERT INTO admin_logs (admin_id, admin_username, action, target_type, target_id, details, ip, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        )->execute([$me['id'], $me['username'], $action, $target_type, $target_id ?: null, $details, $ip]);
    } catch (Throwable $e) {}
}

// Handle actions
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act     = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    $post_id = (int)($_POST['post_id'] ?? 0);

    if ($act === 'ban' && $user_id && $user_id !== $me['id']) {
        $target     = db_find_one($users, 'id', $user_id);
        $ban_reason = trim($_POST['ban_reason'] ?? '');
        $ban_dur    = $_POST['ban_duration'] ?? 'permanent'; // e.g. '1h','6h','1d','7d','30d','permanent'
        $ban_until  = null;
        if ($ban_dur !== 'permanent') {
            $map = ['1h'=>3600,'6h'=>21600,'12h'=>43200,'1d'=>86400,'3d'=>259200,'7d'=>604800,'14d'=>1209600,'30d'=>2592000];
            $secs = $map[$ban_dur] ?? null;
            if ($secs) $ban_until = date('Y-m-d H:i:s', time() + $secs);
        }
        $users = db_update($users, $user_id, ['is_banned' => true, 'ban_reason' => $ban_reason, 'ban_until' => $ban_until, 'updated_at' => now()]);
        db_write('users.json', $users);
        $dur_label = $ban_until ? "until $ban_until" : 'permanently';
        log_admin_action($me, 'ban_user', 'user', $user_id, 'Banned @'.($target['username']??'')." $dur_label".($ban_reason?" | Reason: $ban_reason":''));
        // Instantly kick the user via WebSocket
        ws_push(['type' => 'force_logout', 'to_user_id' => $user_id]);
        $flash = 'User banned' . ($ban_until ? " until $ban_until (UTC)." : ' permanently.');
    } elseif ($act === 'unban' && $user_id) {
        $target = db_find_one($users, 'id', $user_id);
        $users = db_update($users, $user_id, ['is_banned' => false, 'ban_reason' => null, 'ban_until' => null, 'updated_at' => now()]);
        db_write('users.json', $users);
        log_admin_action($me, 'unban_user', 'user', $user_id, 'Unbanned @'.($target['username']??''));
        $flash = 'User unbanned.';
    } elseif ($act === 'change_password' && $user_id) {
        $new_pass = trim($_POST['new_password'] ?? '');
        if (strlen($new_pass) < 6) {
            $flash = 'Password must be at least 6 characters.';
        } else {
            $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
            try {
                $target = db_find_one($users, 'id', $user_id);
                $pdo = db();
                $pdo->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?')
                    ->execute([$hashed, $user_id]);
                log_admin_action($me, 'change_password', 'user', $user_id, 'Changed password for @'.($target['username']??''));
                $flash = 'Password updated successfully for user #' . $user_id . '.';
            } catch (Throwable $e) { $flash = 'Error updating password: ' . $e->getMessage(); }
        }
    } elseif ($act === 'change_role' && $user_id && is_superadmin()) {
        $new_role = $_POST['new_role'] ?? '';
        if (in_array($new_role, ['user', 'admin', 'superadmin']) && $user_id !== $me['id']) {
            $target = db_find_one($users, 'id', $user_id);
            try {
                db()->prepare('UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?')
                   ->execute([$new_role, $user_id]);
                log_admin_action($me, 'change_role', 'user', $user_id,
                    'Changed role of @'.($target['username']??'').' from '.($target['role']??'').' to '.$new_role);
                $flash = 'Role updated to ' . $new_role . ' for user #' . $user_id . '.';
                $users = db_read('users.json');
            } catch (Throwable $e) { $flash = 'Error changing role.'; }
        }
    } elseif ($act === 'delete_user' && $user_id && $user_id !== $me['id']) {
        // Remove user's posts, comments, likes, friends, messages, notifications
        $target = db_find_one($users, 'id', $user_id);

        // Delete profile picture if not the default
        if ($target && !empty($target['profile_picture']) && $target['profile_picture'] !== 'default_avatar.png') {
            $pic = UPLOADS_PATH . '/' . $target['profile_picture'];
            if (file_exists($pic)) unlink($pic);
        }

        log_admin_action($me, 'delete_user', 'user', $user_id, 'Deleted @'.($target['username']??''));
        $users = db_delete($users, $user_id);
        db_write('users.json', $users);

        $posts_data    = db_read('posts.json');
        $user_post_ids = array_column(db_find_all($posts_data, 'user_id', $user_id), 'id');

        // Delete image files for the user's posts
        foreach (db_find_all($posts_data, 'user_id', $user_id) as $p) {
            if (!empty($p['image'])) {
                $img = UPLOADS_PATH . '/' . $p['image'];
                if (file_exists($img)) unlink($img);
            }
        }

        $posts_data = array_values(array_filter($posts_data, fn($p) => $p['user_id'] !== $user_id));
        db_write('posts.json', $posts_data);

        $comments = db_read('comments.json');
        $comments = array_values(array_filter($comments, fn($c) =>
            $c['user_id'] !== $user_id && !in_array($c['post_id'], $user_post_ids)));
        db_write('comments.json', $comments);

        $likes = db_read('likes.json');
        $likes = array_values(array_filter($likes, fn($l) =>
            $l['user_id'] !== $user_id && !in_array($l['post_id'], $user_post_ids)));
        db_write('likes.json', $likes);

        $friends = db_read('friends.json');
        $friends = array_values(array_filter($friends, fn($f) =>
            $f['requester_id'] !== $user_id && $f['receiver_id'] !== $user_id));
        db_write('friends.json', $friends);

        $messages = db_read('messages.json');
        $messages = array_values(array_filter($messages, fn($m) =>
            $m['sender_id'] !== $user_id && $m['receiver_id'] !== $user_id));
        db_write('messages.json', $messages);

        $notifs = db_read('notifications.json');
        $notifs = array_values(array_filter($notifs, fn($n) =>
            $n['user_id'] !== $user_id && $n['actor_id'] !== $user_id));
        db_write('notifications.json', $notifs);

        $flash = 'User and all associated data deleted.';
        $users = db_read('users.json');
    } elseif ($act === 'delete_post' && $post_id) {
        $posts_data = db_read('posts.json');
        $post = db_find_one($posts_data, 'id', $post_id);
        if ($post && $post['image']) {
            $img = UPLOADS_PATH . '/' . $post['image'];
            if (file_exists($img)) unlink($img);
        }
        log_admin_action($me, 'delete_post', 'post', $post_id, 'Deleted post #'.$post_id);
        $posts_data = db_delete($posts_data, $post_id);
        db_write('posts.json', $posts_data);

        $likes = db_read('likes.json');
        $likes = array_values(array_filter($likes, fn($l) => $l['post_id'] !== $post_id));
        db_write('likes.json', $likes);

        $comments = db_read('comments.json');
        $comments = array_values(array_filter($comments, fn($c) => $c['post_id'] !== $post_id));
        db_write('comments.json', $comments);

        $flash = 'Post deleted.';
        $posts = db_read('posts.json');
    } elseif ($act === 'block_ip') {
        $ip_to_block = trim($_POST['ip'] ?? '');
        $reason      = trim($_POST['reason'] ?? '');
        if ($ip_to_block) {
            try {
                $pdo = db();
                $pdo->prepare(
                    'INSERT IGNORE INTO blocked_ips (ip, reason, blocked_by, created_at) VALUES (?, ?, ?, NOW())'
                )->execute([$ip_to_block, $reason, $me['id']]);
                log_admin_action($me, 'block_ip', 'ip', 0, "Blocked IP {$ip_to_block}".($reason?" reason: {$reason}":''));
                $flash = "IP {$ip_to_block} has been blocked.";
            } catch (Throwable $e) { $flash = 'Error blocking IP.'; }
        }
    } elseif ($act === 'unblock_ip') {
        $ip_to_unblock = trim($_POST['ip'] ?? '');
        if ($ip_to_unblock) {
            try {
                $pdo = db();
                $pdo->prepare('DELETE FROM blocked_ips WHERE ip = ?')->execute([$ip_to_unblock]);
                log_admin_action($me, 'unblock_ip', 'ip', 0, "Unblocked IP {$ip_to_unblock}");
                $flash = "IP {$ip_to_unblock} has been unblocked.";
            } catch (Throwable $e) { $flash = 'Error unblocking IP.'; }
        }
    }
}

// Stats
$total_users   = count($users);
$banned_users  = count(array_filter($users, fn($u) => $u['is_banned']));
$total_posts   = count($posts);
$total_likes   = count(db_read('likes.json'));
$total_comments= count(db_read('comments.json'));

// Blocked IPs
try {
    $pdo_admin = db();
    $blocked_ips_list = $pdo_admin->query('SELECT * FROM blocked_ips ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
    $blocked_ips_set  = array_column($blocked_ips_list, 'ip');
} catch (Throwable $e) {
    $blocked_ips_list = [];
    $blocked_ips_set  = [];
}

// Admin action logs (superadmin only)
$admin_logs = [];
if (is_superadmin()) {
    try {
        $admin_logs = db()->query(
            'SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT 300'
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $admin_logs = []; }
}

$page_title = 'Admin Panel';
include __DIR__ . '/../includes/header.php';
?>

<div class="container admin-panel">
    <div class="admin-header">
        <div class="admin-header-left">
            <h2><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.15em;height:1.15em;vertical-align:-0.2em;margin-right:0.35em"><path fill-rule="evenodd" d="M11.078 2.25c-.917 0-1.699.663-1.855 1.567L9.05 4.889c-.02.12-.115.26-.297.348a7.493 7.493 0 0 0-.986.57c-.166.115-.334.126-.45.083L6.3 5.508a1.875 1.875 0 0 0-2.282.819l-.922 1.597a1.875 1.875 0 0 0 .432 2.385l.84.692c.095.078.17.229.154.43a7.598 7.598 0 0 0 0 1.139c.015.2-.059.352-.153.43l-.841.692a1.875 1.875 0 0 0-.432 2.385l.922 1.597a1.875 1.875 0 0 0 2.282.818l1.019-.382c.115-.043.283-.031.45.082.312.214.641.405.985.57.182.088.277.228.297.35l.178 1.071c.151.904.933 1.567 1.85 1.567h1.844c.916 0 1.699-.663 1.85-1.567l.178-1.072c.02-.12.114-.26.297-.349.344-.165.673-.356.985-.57.167-.114.335-.125.45-.082l1.02.382a1.875 1.875 0 0 0 2.28-.819l.923-1.597a1.875 1.875 0 0 0-.432-2.385l-.84-.692c-.095-.078-.17-.229-.154-.43a7.614 7.614 0 0 0 0-1.139c-.016-.2.059-.352.153-.43l.84-.692c.708-.582.891-1.59.433-2.385l-.922-1.597a1.875 1.875 0 0 0-2.282-.818l-1.02.382c-.114.043-.282.031-.449-.083a7.49 7.49 0 0 0-.985-.57c-.183-.087-.277-.227-.297-.348l-.179-1.072a1.875 1.875 0 0 0-1.85-1.567h-1.843ZM12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" clip-rule="evenodd"/></svg>Admin Panel</h2>
            <p>Manage users, posts, visitors and platform settings</p>
        </div>
        <?php if (is_superadmin()): ?>
        <div class="admin-superadmin-badge" style="display:inline-flex;align-items:center;padding:0.45rem 1.1rem;background:#0a0a14;border:1px solid #b8720a;border-radius:999px;font-size:0.75rem;font-weight:900;letter-spacing:0.1em;text-transform:uppercase;color:#e8920a;white-space:nowrap;box-shadow:0 0 12px rgba(200,127,10,.3)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1em;height:1em;margin-right:0.35em"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd"/></svg>Superadmin</div>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="admin-stats">
        <div class="stat-card card"><span><?= $total_users ?></span><p>Total Users</p></div>
        <div class="stat-card card"><span><?= $banned_users ?></span><p>Banned</p></div>
        <div class="stat-card card"><span><?= $total_posts ?></span><p>Posts</p></div>
        <div class="stat-card card"><span><?= $total_likes ?></span><p>Likes</p></div>
        <div class="stat-card card"><span><?= $total_comments ?></span><p>Comments</p></div>
    </div>

    <!-- Tabs -->
    <div class="admin-tabs">
        <button class="tab-btn active" onclick="showTab('users',this)">Users</button>
        <button class="tab-btn" onclick="showTab('posts',this)">Posts</button>
        <button class="tab-btn" onclick="showTab('visitors',this)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1em;height:1em;vertical-align:-0.15em;margin-right:0.3em"><path d="M21.721 12.752a9.711 9.711 0 0 0-.945-5.003 12.754 12.754 0 0 1-4.339 2.708 18.991 18.991 0 0 1-.214 4.772 17.165 17.165 0 0 0 5.498-2.477ZM14.634 15.55a17.324 17.324 0 0 0 .332-4.647c-.952.227-1.945.347-2.966.347-1.021 0-2.014-.12-2.966-.347a17.515 17.515 0 0 0 .332 4.647 17.385 17.385 0 0 0 5.268 0ZM9.772 17.119a18.963 18.963 0 0 0 4.456 0A17.182 17.182 0 0 1 12 21.724a17.18 17.18 0 0 1-2.228-4.605ZM7.777 15.23a18.87 18.87 0 0 1-.214-4.774 12.753 12.753 0 0 1-4.34-2.708 9.711 9.711 0 0 0-.944 5.004 17.165 17.165 0 0 0 5.498 2.477ZM21.356 14.752a9.765 9.765 0 0 1-7.478 6.817 18.64 18.64 0 0 0 1.988-4.718 18.627 18.627 0 0 0 5.49-2.098ZM2.644 14.752c1.682.971 3.53 1.688 5.49 2.099a18.64 18.64 0 0 0 1.988 4.718 9.765 9.765 0 0 1-7.478-6.816ZM13.878 2.43a9.755 9.755 0 0 1 6.116 3.986 11.267 11.267 0 0 1-3.746 2.504 18.63 18.63 0 0 0-2.37-6.49ZM12 2.276a17.152 17.152 0 0 1 2.805 7.121c-.897.23-1.837.353-2.805.353-.968 0-1.908-.122-2.805-.353A17.151 17.151 0 0 1 12 2.276ZM10.122 2.43a18.629 18.629 0 0 0-2.37 6.49 11.266 11.266 0 0 1-3.746-2.504 9.754 9.754 0 0 1 6.116-3.985Z"/></svg>Visitor IPs</button>
        <?php if (is_superadmin()): ?>
        <button class="tab-btn" onclick="showTab('superadmin',this)" style="background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(139,92,246,.15));border-color:rgba(245,158,11,.4);color:#f59e0b"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1em;height:1em;vertical-align:-0.15em;margin-right:0.3em"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd"/></svg>Super Admin</button>
        <?php endif; ?>
    </div>

    <!-- Users tab -->
    <div id="tab-users" class="tab-content card">
        <div style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;margin-bottom:1.2rem">
            <h3 style="margin:0">All Users <span style="color:var(--text-muted);font-weight:400;font-size:0.85rem">(<?= count($users) ?>)</span></h3>
            <input id="admin-user-search" type="text" placeholder="Search by username, email…"
                   style="flex:1;min-width:180px;max-width:280px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:0.45rem 0.85rem;color:var(--text);font-size:0.88rem"
                   oninput="filterAdminUsers()">
            <select id="admin-role-filter" onchange="filterAdminUsers()"
                    style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:0.45rem 0.7rem;color:var(--text);font-size:0.88rem">
                <option value="">All Roles</option>
                <option value="user">User</option>
                <option value="admin">Admin</option>
                <option value="superadmin">Superadmin</option>
            </select>
            <select id="admin-status-filter" onchange="filterAdminUsers()"
                    style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:0.45rem 0.7rem;color:var(--text);font-size:0.88rem">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="banned">Banned</option>
            </select>
        </div>
        <div style="overflow-x:auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th style="min-width:210px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr class="<?= $u['is_banned'] ? 'row-banned' : '' ?> admin-user-row"
                        data-username="<?= strtolower(htmlspecialchars($u['username'])) ?>"
                        data-email="<?= strtolower(htmlspecialchars($u['email'])) ?>"
                        data-role="<?= htmlspecialchars($u['role']) ?>"
                        data-status="<?= $u['is_banned'] ? 'banned' : 'active' ?>">
                        <td><?= $u['id'] ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($u['username']) ?>" style="font-weight:600;color:var(--text)">
                                <?= htmlspecialchars($u['username']) ?>
                            </a>
                        </td>
                        <td style="color:var(--text-muted);font-size:0.82rem"><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span class="role-badge role-<?= $u['role'] ?>"><?= $u['role'] ?></span>
                            <?php if (is_superadmin() && $u['id'] !== $me['id']): ?>
                            <form method="POST" style="display:inline;margin-left:6px">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="action" value="change_role">
                                <select name="new_role" onchange="this.form.submit()" class="admin-role-select">
                                    <option value="user"       <?= $u['role']==='user'?'selected':'' ?>>user</option>
                                    <option value="admin"      <?= $u['role']==='admin'?'selected':'' ?>>admin</option>
                                    <option value="superadmin" <?= $u['role']==='superadmin'?'selected':'' ?>>superadmin</option>
                                </select>
                            </form>
                            <?php endif; ?>
                        </td>
                        <td><?= $u['is_banned'] ? '<span style="color:#ef4444">Banned</span>' : '<span style="color:#22c55e">Active</span>' ?></td>
                        <td style="color:var(--text-muted);font-size:0.82rem"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        <td style="white-space:nowrap">
                            <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:nowrap">
                            <form method="POST" style="display:contents">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <?php if ($u['is_banned']): ?>
                                    <button name="action" value="unban" class="btn btn-sm btn-success">Unban</button>
                                <?php else: ?>
                                    <button name="action" value="ban" class="btn btn-sm btn-warning"
                                            onclick="return showBanForm(event, <?= $u['id'] ?>)">Ban</button>
                                <?php endif; ?>
                                <button name="action" value="delete_user" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete user and all their data?')">Delete</button>
                            </form>
                            <?php if ($u['id'] !== $me['id']): ?>
                            <button class="btn btn-sm" style="background:rgba(139,92,246,.18);border:1px solid rgba(139,92,246,.4);color:#c4b5fd"
                                onclick="togglePwForm(<?= $u['id'] ?>)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:0.95em;height:0.95em;vertical-align:-0.1em;margin-right:0.25em"><path fill-rule="evenodd" d="M15.75 1.5a6.75 6.75 0 0 0-6.651 7.906c.067.39-.032.717-.221.906l-6.5 6.499a3 3 0 0 0-.878 2.121v2.818c0 .414.336.75.75.75H6a.75.75 0 0 0 .75-.75v-1.5h1.5A.75.75 0 0 0 9 19.5V18h1.5a.75.75 0 0 0 .53-.22l2.658-2.658c.19-.189.517-.288.906-.22A6.75 6.75 0 1 0 15.75 1.5Zm0 3a.75.75 0 0 0 0 1.5A2.25 2.25 0 0 1 18 8.25a.75.75 0 0 0 1.5 0 3.75 3.75 0 0 0-3.75-3.75Z" clip-rule="evenodd"/></svg>PW</button>
                            </div>
                            <div id="pw-form-<?= $u['id'] ?>" class="admin-pw-form" style="display:none">
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="password" name="new_password" placeholder="New password…" required class="admin-pw-input">
                                    <div style="display:flex;gap:0.4rem;margin-top:0.4rem">
                                        <button type="submit" class="btn btn-sm" style="flex:1;background:linear-gradient(135deg,rgba(139,92,246,.35),rgba(99,102,241,.35));border:1px solid rgba(139,92,246,.6);color:#c4b5fd;font-weight:700;border-radius:9px;transition:all .18s ease"
                                            onclick="return confirm('Change password?')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:0.9em;height:0.9em;vertical-align:-0.1em;margin-right:0.2em"><path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 0 1 .208 1.04l-9 13.5a.75.75 0 0 1-1.154.114l-6-6a.75.75 0 0 1 1.06-1.06l5.353 5.353 8.493-12.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd"/></svg>Save</button>
                                        <button type="button" class="btn btn-sm" style="flex:1;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.35);border-radius:9px;transition:all .18s ease"
                                            onclick="togglePwForm(<?= $u['id'] ?>)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:0.9em;height:0.9em;vertical-align:-0.1em;margin-right:0.2em"><path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>Cancel</button>
                                    </div>
                                </form>
                            </div>
                            <?php else: ?>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Posts tab -->
    <div id="tab-posts" class="tab-content card" style="display:none">
        <div style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;margin-bottom:1.2rem">
            <h3 style="margin:0">All Posts</h3>
            <input id="admin-post-search" type="text" placeholder="Search by author, caption…"
                   style="flex:1;min-width:180px;max-width:320px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:0.45rem 0.85rem;color:var(--text);font-size:0.88rem"
                   oninput="filterAdminPosts()">
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th><th>Author</th><th>Caption</th><th>Image</th><th>Posted</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($posts) as $p):
                    $author = db_find_one($users, 'id', $p['user_id']);
                ?>
                    <tr class="admin-post-row"
                        data-author="<?= strtolower(htmlspecialchars($author['username'] ?? '')) ?>"
                        data-caption="<?= strtolower(htmlspecialchars(substr($p['caption'], 0, 200))) ?>">
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($author['username'] ?? '?') ?></td>
                        <td><?= htmlspecialchars(substr($p['caption'], 0, 60)) ?>…</td>
                          <td><?= $p['image'] ? 'Yes' : '—' ?></td>
                        <td><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
                        <td style="white-space:nowrap;display:flex;gap:0.4rem;align-items:center">
                            <a href="<?= BASE_URL ?>/pages/post.php?id=<?= $p['id'] ?>" target="_blank"
                               class="btn btn-sm" style="background:rgba(99,102,241,.18);border:1px solid rgba(99,102,241,.4);color:#a5b4fc">View</a>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                <button name="action" value="delete_post" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete this post?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Visitors tab -->
    <div id="tab-visitors" class="tab-content card" style="display:none">
        <h3><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1em;height:1em;vertical-align:-0.15em;margin-right:0.3em"><path d="M21.721 12.752a9.711 9.711 0 0 0-.945-5.003 12.754 12.754 0 0 1-4.339 2.708 18.991 18.991 0 0 1-.214 4.772 17.165 17.165 0 0 0 5.498-2.477ZM14.634 15.55a17.324 17.324 0 0 0 .332-4.647c-.952.227-1.945.347-2.966.347-1.021 0-2.014-.12-2.966-.347a17.515 17.515 0 0 0 .332 4.647 17.385 17.385 0 0 0 5.268 0ZM9.772 17.119a18.963 18.963 0 0 0 4.456 0A17.182 17.182 0 0 1 12 21.724a17.18 17.18 0 0 1-2.228-4.605ZM7.777 15.23a18.87 18.87 0 0 1-.214-4.774 12.753 12.753 0 0 1-4.34-2.708 9.711 9.711 0 0 0-.944 5.004 17.165 17.165 0 0 0 5.498 2.477ZM21.356 14.752a9.765 9.765 0 0 1-7.478 6.817 18.64 18.64 0 0 0 1.988-4.718 18.627 18.627 0 0 0 5.49-2.098ZM2.644 14.752c1.682.971 3.53 1.688 5.49 2.099a18.64 18.64 0 0 0 1.988 4.718 9.765 9.765 0 0 1-7.478-6.816ZM13.878 2.43a9.755 9.755 0 0 1 6.116 3.986 11.267 11.267 0 0 1-3.746 2.504 18.63 18.63 0 0 0-2.37-6.49ZM12 2.276a17.152 17.152 0 0 1 2.805 7.121c-.897.23-1.837.353-2.805.353-.968 0-1.908-.122-2.805-.353A17.151 17.151 0 0 1 12 2.276ZM10.122 2.43a18.629 18.629 0 0 0-2.37 6.49 11.266 11.266 0 0 1-3.746-2.504 9.754 9.754 0 0 1 6.116-3.985Z"/></svg>Visitor IP Logs <small style="font-weight:400;font-size:0.8rem;color:var(--text-muted)">(last 200 visits)</small></h3>
        <?php
        try {
            $pdo = db();
            $vis_rows = $pdo->query(
                'SELECT ip, user_id, username, page, user_agent, visited_at
                 FROM visitor_logs
                 ORDER BY visited_at DESC
                 LIMIT 200'
            )->fetchAll(PDO::FETCH_ASSOC);

            // Unique IPs summary
            $ip_counts = [];
            foreach ($vis_rows as $r) {
                $ip_counts[$r['ip']] = ($ip_counts[$r['ip']] ?? 0) + 1;
            }
            arsort($ip_counts);
        } catch (Throwable $e) {
            $vis_rows = [];
            $ip_counts = [];
        }
        ?>

        <!-- IP Summary cards -->
        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1.2rem">
            <?php foreach (array_slice($ip_counts, 0, 20, true) as $ip => $cnt): ?>
                <div style="background:<?= in_array($ip, $blocked_ips_set) ? 'rgba(239,68,68,.15)' : 'rgba(139,92,246,.1)' ?>;border:1px solid <?= in_array($ip, $blocked_ips_set) ? 'rgba(239,68,68,.4)' : 'rgba(139,92,246,.25)' ?>;border-radius:8px;padding:0.4rem 0.8rem;font-size:0.82rem;display:flex;align-items:center;gap:0.5rem">
                    <span style="color:var(--orange-hi);font-weight:700"><?= htmlspecialchars($ip) ?></span>
                    <span style="color:var(--text-muted)"> &times;<?= $cnt ?></span>
                    <?php if (in_array($ip, $blocked_ips_set)): ?>
                        <span style="color:#ef4444;font-size:0.75rem;display:inline-flex;align-items:center;gap:0.2em"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:0.85em;height:0.85em"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>blocked</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Blocked IPs panel -->
        <?php if ($blocked_ips_list): ?>
        <div style="background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:1rem;margin-bottom:1.2rem">
            <h4 style="color:#ef4444;margin-bottom:0.8rem;display:flex;align-items:center;gap:0.4em"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:1em;height:1em"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>Currently Blocked IPs (<?= count($blocked_ips_list) ?>)</h4>
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem">
                <?php foreach ($blocked_ips_list as $b): ?>
                <div style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:0.4rem 0.75rem;display:flex;align-items:center;gap:0.6rem;font-size:0.83rem">
                    <code style="color:#ef4444"><?= htmlspecialchars($b['ip']) ?></code>
                    <?php if ($b['reason']): ?><span style="color:var(--text-muted)"><?= htmlspecialchars($b['reason']) ?></span><?php endif; ?>
                    <form method="POST" style="display:inline;margin:0">
                        <input type="hidden" name="action" value="unblock_ip">
                        <input type="hidden" name="ip" value="<?= htmlspecialchars($b['ip']) ?>">
                        <button type="submit" style="background:none;border:1px solid rgba(239,68,68,.4);color:#ef4444;border-radius:5px;padding:0.15rem 0.45rem;cursor:pointer;font-size:0.75rem" onclick="return confirm('Unblock this IP?')">Unblock</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>User</th>
                    <th><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:0.9em;height:0.9em;vertical-align:-0.1em;margin-right:0.2em"><path fill-rule="evenodd" d="m11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.071-.041a16.975 16.975 0 0 0 1.144-.742 19.58 19.58 0 0 0 2.683-2.282c1.944-2.003 3.5-4.697 3.5-8.057a7.5 7.5 0 1 0-15 0c0 3.36 1.556 6.054 3.5 8.057a19.58 19.58 0 0 0 2.682 2.282 16.975 16.975 0 0 0 1.145.742ZM12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd"/></svg>Location</th>
                    <th>Page</th>
                    <th>Browser / UA</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vis_rows as $r): ?>
                <tr>
                    <td><code class="vis-ip" style="color:var(--orange-hi)" data-ip="<?= htmlspecialchars($r['ip']) ?>"><?= htmlspecialchars($r['ip']) ?></code></td>
                    <td>
                        <?php if ($r['username']): ?>
                            <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($r['username']) ?>">
                                <?= htmlspecialchars($r['username']) ?>
                            </a>
                        <?php else: ?>
                            <span style="color:var(--text-muted)">Guest</span>
                        <?php endif; ?>
                    </td>
                    <td class="geo-cell" data-ip="<?= htmlspecialchars($r['ip']) ?>" style="font-size:0.8rem;white-space:nowrap;color:var(--text-muted)">
                        <span style="opacity:.4">Loading…</span>
                    </td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.82rem;color:var(--text-sub)">
                        <?= htmlspecialchars($r['page']) ?>
                    </td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.75rem;color:var(--text-muted)">
                        <?= htmlspecialchars($r['user_agent'] ?? '') ?>
                    </td>
                    <td style="font-size:0.82rem;white-space:nowrap"><?= htmlspecialchars($r['visited_at']) ?></td>
                    <td>
                        <?php if (in_array($r['ip'], $blocked_ips_set)): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="unblock_ip">
                                <input type="hidden" name="ip" value="<?= htmlspecialchars($r['ip']) ?>">
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Unblock this IP?')">Unblock</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display:inline;display:flex;gap:0.3rem;align-items:center">
                                <input type="hidden" name="action" value="block_ip">
                                <input type="hidden" name="ip" value="<?= htmlspecialchars($r['ip']) ?>">
                                <input type="text" name="reason" placeholder="Reason (optional)" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:6px;padding:0.25rem 0.5rem;color:var(--text);font-size:0.78rem;width:120px">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Block this IP?')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:0.9em;height:0.9em;vertical-align:-0.1em;margin-right:0.2em"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>Block</button>
                            </form>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
                <?php if (!$vis_rows): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--text-muted)">No visits logged yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (is_superadmin()): ?>
    <!-- Super Admin tab -->
    <div id="tab-superadmin" class="tab-content card" style="display:none">
        <h3><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1em;height:1em;vertical-align:-0.15em;margin-right:0.3em;color:#f59e0b"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd"/></svg>Admin Action Logs <small style="font-weight:400;font-size:0.8rem;color:var(--text-muted)">(last 300 actions)</small></h3>

        <?php if (!$admin_logs): ?>
            <p style="color:var(--text-muted);text-align:center;padding:2rem">No admin actions logged yet.</p>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Admin</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>Details</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $action_colors = [
                    'ban_user'        => '#f59e0b',
                    'unban_user'      => '#22c55e',
                    'delete_user'     => '#ef4444',
                    'delete_post'     => '#ef4444',
                    'change_password' => '#8b5cf6',
                    'change_role'     => '#06b6d4',
                    'block_ip'        => '#ef4444',
                    'unblock_ip'      => '#22c55e',
                ];
                foreach ($admin_logs as $log):
                    $color = $action_colors[$log['action']] ?? '#fff';
                ?>
                <tr>
                    <td style="font-size:0.8rem;white-space:nowrap;color:var(--text-muted)"><?= htmlspecialchars($log['created_at']) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($log['admin_username']) ?>" style="color:var(--orange-hi);font-weight:600">
                            @<?= htmlspecialchars($log['admin_username']) ?>
                        </a>
                    </td>
                    <td><span style="background:<?= $color ?>22;border:1px solid <?= $color ?>55;color:<?= $color ?>;border-radius:6px;padding:0.15rem 0.5rem;font-size:0.78rem;font-weight:700;white-space:nowrap"><?= htmlspecialchars($log['action']) ?></span></td>
                    <td style="font-size:0.82rem;color:var(--text-sub)">
                        <?php if ($log['target_type'] && $log['target_id']): ?>
                            <?= htmlspecialchars($log['target_type']) ?> #<?= $log['target_id'] ?>
                        <?php elseif ($log['target_type']): ?>
                            <?= htmlspecialchars($log['target_type']) ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td style="font-size:0.82rem;color:var(--text-muted)"><?= htmlspecialchars($log['details'] ?? '') ?></td>
                    <td><code style="font-size:0.78rem;color:var(--cyan)"><?= htmlspecialchars($log['ip'] ?? '') ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function showTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = 'block';
    if (btn) btn.classList.add('active');
    if (name === 'visitors') loadGeoData();
}
function togglePwForm(uid) {
    const el = document.getElementById('pw-form-' + uid);
    const isOpen = el.style.display === 'block';
    // close all others first
    document.querySelectorAll('.admin-pw-form').forEach(f => f.style.display = 'none');
    if (!isOpen) { el.style.display = 'block'; el.querySelector('input[type=password]').focus(); }
}

// ── Ban with reason modal ─────────────────────────────────
function showBanForm(e, userId) {
    e.preventDefault();
    const overlay = document.getElementById('ban-modal-overlay');
    document.getElementById('ban-modal-uid').value = userId;
    document.getElementById('ban-modal-reason').value = '';
    overlay.style.display = 'flex';
    setTimeout(() => document.getElementById('ban-modal-reason').focus(), 80);
    return false;
}
function closeBanModal() {
    document.getElementById('ban-modal-overlay').style.display = 'none';
}
document.getElementById('ban-modal-form')?.addEventListener('submit', function(e) {
    // form submits normally to POST
});

// ── User search + filter ──────────────────────────────────
function filterAdminUsers() {
    const q      = (document.getElementById('admin-user-search').value || '').toLowerCase();
    const role   = (document.getElementById('admin-role-filter').value || '').toLowerCase();
    const status = (document.getElementById('admin-status-filter').value || '').toLowerCase();
    document.querySelectorAll('.admin-user-row').forEach(row => {
        const matchQ = !q || row.dataset.username.includes(q) || row.dataset.email.includes(q);
        const matchR = !role || row.dataset.role === role;
        const matchS = !status || row.dataset.status === status;
        row.style.display = (matchQ && matchR && matchS) ? '' : 'none';
    });
}

// ── Post search ───────────────────────────────────────────
function filterAdminPosts() {
    const q = (document.getElementById('admin-post-search').value || '').toLowerCase();
    document.querySelectorAll('.admin-post-row').forEach(row => {
        const match = !q || row.dataset.author.includes(q) || row.dataset.caption.includes(q);
        row.style.display = match ? '' : 'none';
    });
}

let geoLoaded = false;
async function loadGeoData() {
    if (geoLoaded) return;
    geoLoaded = true;

    // Collect unique IPs
    const cells = document.querySelectorAll('.geo-cell');
    const uniqueIPs = [...new Set([...cells].map(c => c.dataset.ip))]
        .filter(ip => ip && ip !== '127.0.0.1' && ip !== '::1');

    if (!uniqueIPs.length) {
        cells.forEach(c => c.innerHTML = '<span style="color:var(--text-muted)">local</span>');
        return;
    }

    // ip-api.com allows batch up to 100 IPs
    const geoMap = {};
    for (let i = 0; i < uniqueIPs.length; i += 100) {
        const batch = uniqueIPs.slice(i, i + 100).map(ip => ({ query: ip, fields: 'query,country,countryCode,regionName,city,isp,status' }));
        try {
            const res = await fetch('http://ip-api.com/batch?fields=query,country,countryCode,regionName,city,isp,status', {
                method: 'POST',
                body: JSON.stringify(batch)
            });
            const data = await res.json();
            data.forEach(d => { if (d.status === 'success') geoMap[d.query] = d; });
        } catch(e) {}
    }

    // Fill cells
    cells.forEach(cell => {
        const ip = cell.dataset.ip;
        if (ip === '127.0.0.1' || ip === '::1') {
            cell.innerHTML = '<span style="color:var(--cyan)">🏠 Localhost</span>';
            return;
        }
        const g = geoMap[ip];
        if (g) {
            const flag = g.countryCode ? `https://flagcdn.com/16x12/${g.countryCode.toLowerCase()}.png` : '';
            cell.innerHTML = `
                <div style="display:flex;flex-direction:column;gap:2px">
                    <span style="color:var(--text);font-weight:600">
                        ${flag ? `<img src="${flag}" style="vertical-align:middle;margin-right:4px;border-radius:2px">` : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1em;height:1em;vertical-align:-0.15em;opacity:0.5"><path d="M21.721 12.752a9.711 9.711 0 0 0-.945-5.003 12.754 12.754 0 0 1-4.339 2.708 18.991 18.991 0 0 1-.214 4.772 17.165 17.165 0 0 0 5.498-2.477ZM14.634 15.55a17.324 17.324 0 0 0 .332-4.647c-.952.227-1.945.347-2.966.347-1.021 0-2.014-.12-2.966-.347a17.515 17.515 0 0 0 .332 4.647 17.385 17.385 0 0 0 5.268 0ZM9.772 17.119a18.963 18.963 0 0 0 4.456 0A17.182 17.182 0 0 1 12 21.724a17.18 17.18 0 0 1-2.228-4.605ZM7.777 15.23a18.87 18.87 0 0 1-.214-4.774 12.753 12.753 0 0 1-4.34-2.708 9.711 9.711 0 0 0-.944 5.004 17.165 17.165 0 0 0 5.498 2.477ZM21.356 14.752a9.765 9.765 0 0 1-7.478 6.817 18.64 18.64 0 0 0 1.988-4.718 18.627 18.627 0 0 0 5.49-2.098ZM2.644 14.752c1.682.971 3.53 1.688 5.49 2.099a18.64 18.64 0 0 0 1.988 4.718 9.765 9.765 0 0 1-7.478-6.816ZM13.878 2.43a9.755 9.755 0 0 1 6.116 3.986 11.267 11.267 0 0 1-3.746 2.504 18.63 18.63 0 0 0-2.37-6.49ZM12 2.276a17.152 17.152 0 0 1 2.805 7.121c-.897.23-1.837.353-2.805.353-.968 0-1.908-.122-2.805-.353A17.151 17.151 0 0 1 12 2.276ZM10.122 2.43a18.629 18.629 0 0 0-2.37 6.49 11.266 11.266 0 0 1-3.746-2.504 9.754 9.754 0 0 1 6.116-3.985Z"/></svg>'}
                        ${g.city || '?'}, ${g.country || '?'}
                    </span>
                    <span style="color:var(--text-muted);font-size:0.73rem">${g.isp || ''}</span>
                </div>`;
        } else {
            cell.innerHTML = '<span style="color:var(--text-muted)">—</span>';
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Ban reason modal -->
<div id="ban-modal-overlay" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.75);backdrop-filter:blur(4px);align-items:center;justify-content:center">
    <div style="background:#12121c;border:1px solid rgba(239,68,68,.4);border-radius:16px;padding:2rem;max-width:420px;width:90%;box-shadow:0 0 40px rgba(239,68,68,.15)">
        <h3 style="color:#ef4444;margin-bottom:0.4rem;display:flex;align-items:center;gap:0.5rem">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            Ban User
        </h3>
        <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:1.25rem">The user will be instantly disconnected and shown this reason.</p>
        <form id="ban-modal-form" method="POST">
            <input type="hidden" name="action" value="ban">
            <input type="hidden" name="user_id" id="ban-modal-uid">
            <div style="margin-bottom:1rem">
                <label style="display:block;font-size:0.82rem;font-weight:600;margin-bottom:0.4rem;color:var(--text-sub)">Ban duration</label>
                <select name="ban_duration" id="ban-modal-duration"
                        style="width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:0.6rem 0.9rem;color:var(--text);font-family:inherit;font-size:0.9rem">
                    <option value="permanent">Permanent</option>
                    <option value="1h">1 Hour</option>
                    <option value="6h">6 Hours</option>
                    <option value="12h">12 Hours</option>
                    <option value="1d">1 Day</option>
                    <option value="3d">3 Days</option>
                    <option value="7d">7 Days</option>
                    <option value="14d">14 Days</option>
                    <option value="30d">30 Days</option>
                </select>
            </div>
            <div style="margin-bottom:1rem">
                <label style="display:block;font-size:0.82rem;font-weight:600;margin-bottom:0.4rem;color:var(--text-sub)">Reason for ban <small style="color:var(--text-muted)">(shown to user)</small></label>
                <textarea id="ban-modal-reason" name="ban_reason" rows="3" placeholder="E.g. Violated community guidelines, spam, harassment…"
                    style="width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:0.65rem 0.9rem;color:var(--text);font-family:inherit;font-size:0.9rem;resize:vertical"></textarea>
            </div>
            <div style="display:flex;gap:0.6rem">
                <button type="submit" class="btn btn-danger" style="flex:1">Confirm Ban</button>
                <button type="button" class="btn" style="flex:1;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:var(--text-muted)" onclick="closeBanModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>
