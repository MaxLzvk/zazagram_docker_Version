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
        $target = db_find_one($users, 'id', $user_id);
        $users = db_update($users, $user_id, ['is_banned' => true, 'updated_at' => now()]);
        db_write('users.json', $users);
        log_admin_action($me, 'ban_user', 'user', $user_id, 'Banned @'.($target['username']??''));
        $flash = 'User banned.';
    } elseif ($act === 'unban' && $user_id) {
        $target = db_find_one($users, 'id', $user_id);
        $users = db_update($users, $user_id, ['is_banned' => false, 'updated_at' => now()]);
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
    <h2>Admin Panel <?php if (is_superadmin()): ?><span style="font-size:0.7rem;background:linear-gradient(90deg,#f59e0b,#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-weight:900;letter-spacing:.05em;vertical-align:middle">⭐ SUPER ADMIN</span><?php endif; ?></h2>

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
        <button class="tab-btn active" onclick="showTab('users')">Users</button>
        <button class="tab-btn" onclick="showTab('posts')">Posts</button>
        <button class="tab-btn" onclick="showTab('visitors')">🌐 Visitor IPs</button>
        <?php if (is_superadmin()): ?>
        <button class="tab-btn" onclick="showTab('superadmin')" style="background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(139,92,246,.15));border-color:rgba(245,158,11,.4);color:#f59e0b">⭐ Super Admin</button>
        <?php endif; ?>
    </div>

    <!-- Users tab -->
    <div id="tab-users" class="tab-content card">
        <h3>All Users</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr class="<?= $u['is_banned'] ? 'row-banned' : '' ?>">
                        <td><?= $u['id'] ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($u['username']) ?>">
                                <?= htmlspecialchars($u['username']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="role-badge role-<?= $u['role'] ?>"><?= $u['role'] ?></span>
                            <?php if (is_superadmin() && $u['id'] !== $me['id']): ?>
                            <form method="POST" style="display:inline;margin-left:4px">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="action" value="change_role">
                                <select name="new_role" onchange="this.form.submit()" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.15);border-radius:6px;color:var(--text);font-size:0.75rem;padding:0.15rem 0.3rem;cursor:pointer">
                                    <option value="user"     <?= $u['role']==='user'?'selected':'' ?>>user</option>
                                    <option value="admin"    <?= $u['role']==='admin'?'selected':'' ?>>admin</option>
                                    <option value="superadmin" <?= $u['role']==='superadmin'?'selected':'' ?>>superadmin</option>
                                </select>
                            </form>
                            <?php endif; ?>
                        </td>
                        <td><?= $u['is_banned'] ? 'Banned' : 'Active' ?></td>
                        <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        <td class="action-btns">
                            <?php if ($u['id'] !== $me['id']): ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <?php if ($u['is_banned']): ?>
                                        <button name="action" value="unban" class="btn btn-sm btn-success">Unban</button>
                                    <?php else: ?>
                                        <button name="action" value="ban" class="btn btn-sm btn-warning">Ban</button>
                                    <?php endif; ?>
                                    <button name="action" value="delete_user" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete user and all their data?')">Delete</button>
                                </form>
                                <button class="btn btn-sm" style="background:rgba(139,92,246,.2);border:1px solid rgba(139,92,246,.4);color:var(--purple-hi)"
                                    onclick="togglePwForm(<?= $u['id'] ?>)">🔑 Password</button>
                                <div id="pw-form-<?= $u['id'] ?>" style="display:none;margin-top:0.5rem">
                                    <form method="POST" style="display:flex;gap:0.4rem;align-items:center;flex-wrap:wrap">
                                        <input type="hidden" name="action" value="change_password">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="password" name="new_password" placeholder="New password (min 6)" required
                                            style="background:rgba(255,255,255,.05);border:1px solid rgba(139,92,246,.3);border-radius:7px;padding:0.3rem 0.65rem;color:var(--text);font-size:0.83rem;min-width:160px">
                                        <button type="submit" class="btn btn-sm" style="background:rgba(139,92,246,.25);border:1px solid rgba(139,92,246,.5);color:var(--purple-hi)"
                                            onclick="return confirm('Change password for this user?')">Save</button>
                                        <button type="button" class="btn btn-sm" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:var(--text-muted)"
                                            onclick="togglePwForm(<?= $u['id'] ?>)">Cancel</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="muted">— You —</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Posts tab -->
    <div id="tab-posts" class="tab-content card" style="display:none">
        <h3>All Posts</h3>
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
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($author['username'] ?? '?') ?></td>
                        <td><?= htmlspecialchars(substr($p['caption'], 0, 60)) ?>…</td>
                          <td><?= $p['image'] ? 'Yes' : '—' ?></td>
                        <td><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
                        <td>
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
        <h3>🌐 Visitor IP Logs <small style="font-weight:400;font-size:0.8rem;color:var(--text-muted)">(last 200 visits)</small></h3>
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
                        <span style="color:#ef4444;font-size:0.75rem">🚫 blocked</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Blocked IPs panel -->
        <?php if ($blocked_ips_list): ?>
        <div style="background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:1rem;margin-bottom:1.2rem">
            <h4 style="color:#ef4444;margin-bottom:0.8rem">🚫 Currently Blocked IPs (<?= count($blocked_ips_list) ?>)</h4>
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
                    <th>📍 Location</th>
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
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Block this IP?')">🚫 Block</button>
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
        <h3>⭐ Admin Action Logs <small style="font-weight:400;font-size:0.8rem;color:var(--text-muted)">(last 300 actions)</small></h3>

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
function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = 'block';
    event.target.classList.add('active');
    if (name === 'visitors') loadGeoData();
}
function togglePwForm(uid) {
    const el = document.getElementById('pw-form-' + uid);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
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
                        ${flag ? `<img src="${flag}" style="vertical-align:middle;margin-right:4px;border-radius:2px">` : '🌐'}
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
