<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$me    = get_current_user_data();
$users = db_read('users.json');
$posts = db_read('posts.json');

// Handle actions
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act     = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    $post_id = (int)($_POST['post_id'] ?? 0);

    if ($act === 'ban' && $user_id && $user_id !== $me['id']) {
        $users = db_update($users, $user_id, ['is_banned' => true, 'updated_at' => now()]);
        db_write('users.json', $users);
        $flash = 'User banned.';
    } elseif ($act === 'unban' && $user_id) {
        $users = db_update($users, $user_id, ['is_banned' => false, 'updated_at' => now()]);
        db_write('users.json', $users);
        $flash = 'User unbanned.';
    } elseif ($act === 'delete_user' && $user_id && $user_id !== $me['id']) {
        // Remove user's posts, comments, likes, friends, messages, notifications
        $target = db_find_one($users, 'id', $user_id);

        // Delete profile picture if not the default
        if ($target && !empty($target['profile_picture']) && $target['profile_picture'] !== 'default_avatar.png') {
            $pic = UPLOADS_PATH . '/' . $target['profile_picture'];
            if (file_exists($pic)) unlink($pic);
        }

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
                $flash = "IP {$ip_to_block} has been blocked.";
            } catch (Throwable $e) { $flash = 'Error blocking IP.'; }
        }
    } elseif ($act === 'unblock_ip') {
        $ip_to_unblock = trim($_POST['ip'] ?? '');
        if ($ip_to_unblock) {
            try {
                $pdo = db();
                $pdo->prepare('DELETE FROM blocked_ips WHERE ip = ?')->execute([$ip_to_unblock]);
                $flash = "IP {$ip_to_unblock} has been unblocked.";
            } catch (Throwable $e) { $flash = 'Error unblocking IP.'; }
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

$page_title = 'Admin Panel';
include __DIR__ . '/../includes/header.php';
?>

<div class="container admin-panel">
    <h2>Admin Panel</h2>

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
                        <td><span class="role-badge role-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
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
                    <th>Page</th>
                    <th>Browser / UA</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vis_rows as $r): ?>
                <tr>
                    <td><code style="color:var(--orange-hi)"><?= htmlspecialchars($r['ip']) ?></code></td>
                    <td>
                        <?php if ($r['username']): ?>
                            <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($r['username']) ?>">
                                <?= htmlspecialchars($r['username']) ?>
                            </a>
                        <?php else: ?>
                            <span style="color:var(--text-muted)">Guest</span>
                        <?php endif; ?>
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
                    <tr><td colspan="5" style="text-align:center;color:var(--text-muted)">No visits logged yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = 'block';
    event.target.classList.add('active');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
