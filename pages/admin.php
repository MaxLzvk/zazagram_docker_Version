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
    }
}

// Stats
$total_users   = count($users);
$banned_users  = count(array_filter($users, fn($u) => $u['is_banned']));
$total_posts   = count($posts);
$total_likes   = count(db_read('likes.json'));
$total_comments= count(db_read('comments.json'));

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
