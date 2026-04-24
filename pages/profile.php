<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me = get_current_user_data();

// Determine whose profile we're viewing
$username     = $_GET['username'] ?? $me['username'];
$profile_user = get_user_by_username($username);

if (!$profile_user) {
    http_response_code(404);
    $page_title = 'User not found';
    include __DIR__ . '/../includes/header.php';
    echo '<div class="container"><div class="alert alert-error">User not found.</div></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$is_own_profile = ($me['id'] === $profile_user['id']);

// Load posts for this user
$all_posts = db_read('posts.json');
$user_posts = array_reverse(db_find_all($all_posts, 'user_id', $profile_user['id']));

// Count likes and comments per post
$all_likes    = db_read('likes.json');
$all_comments = db_read('comments.json');

// Friendship status
$friends = db_read('friends.json');
$friend_status = 'none'; // none | pending_sent | pending_received | friends
if (!$is_own_profile) {
    foreach ($friends as $f) {
        if ($f['requester_id'] == $me['id'] && $f['receiver_id'] == $profile_user['id']) {
            $friend_status = $f['status'] === 'accepted' ? 'friends' : 'pending_sent';
            break;
        }
        if ($f['receiver_id'] == $me['id'] && $f['requester_id'] == $profile_user['id']) {
            $friend_status = $f['status'] === 'accepted' ? 'friends' : 'pending_received';
            break;
        }
    }
}

// Count friends
$friend_count = count(array_filter($friends, fn($f) =>
    $f['status'] === 'accepted' &&
    ($f['requester_id'] == $profile_user['id'] || $f['receiver_id'] == $profile_user['id'])
));

$page_title = $profile_user['username'];
$extra_js   = ['posts.js'];
include __DIR__ . '/../includes/header.php';
?>

<div class="container profile-page">

    <!-- Profile Header -->
    <div class="profile-header card">
        <div class="profile-avatar-wrap">
            <img
                src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($profile_user['profile_picture']) ?>?v=<?= strtotime($profile_user['updated_at']) ?>"
                alt="<?= htmlspecialchars($profile_user['username']) ?>"
                class="profile-avatar"
                data-user-id="<?= $profile_user['id'] ?>"
                onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/default_avatar.png'"
            >
            <?php if ($is_own_profile): ?>
                <label for="avatar-upload" class="avatar-edit-btn" title="Change photo">Edit</label>
                <input type="file" id="avatar-upload" accept="image/*" style="display:none"
                       onchange="uploadAvatar(this)">
            <?php endif; ?>
        </div>

        <div class="profile-info">
            <div class="profile-username-row">
                <h2><?= htmlspecialchars($profile_user['username']) ?></h2>
                <?php if ($profile_user['role'] === 'admin'): ?>
                    <span class="badge-admin">Admin</span>
                <?php endif; ?>

                <?php if ($is_own_profile): ?>
                    <a href="<?= BASE_URL ?>/pages/settings.php" class="btn btn-outline btn-sm">Edit Profile</a>
                <?php else: ?>
                    <?php if ($friend_status === 'none'): ?>
                        <button class="btn btn-primary btn-sm"
                                onclick="sendFriendRequest(<?= $profile_user['id'] ?>, this)">Add Friend</button>
                    <?php elseif ($friend_status === 'pending_sent'): ?>
                        <button class="btn btn-outline btn-sm" disabled>Request Sent</button>
                    <?php elseif ($friend_status === 'pending_received'): ?>
                        <button class="btn btn-success btn-sm"
                                onclick="respondFriend(<?= $profile_user['id'] ?>, 'accept', this)">Accept Request</button>
                        <button class="btn btn-danger btn-sm"
                                onclick="respondFriend(<?= $profile_user['id'] ?>, 'decline', this)">Decline</button>
                    <?php else: ?>
                        <span class="friends-badge">Friends</span>
                        <a href="<?= BASE_URL ?>/pages/messages.php?user=<?= $profile_user['id'] ?>"
                             class="btn btn-outline btn-sm">Message</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <p class="profile-fullname"><?= htmlspecialchars($profile_user['first_name'] . ' ' . $profile_user['last_name']) ?></p>
            <p class="profile-bio"><?= nl2br(htmlspecialchars($profile_user['bio'])) ?></p>

            <div class="profile-stats">
                <div class="stat"><strong><?= count($user_posts) ?></strong><span>Posts</span></div>
                <div class="stat"><strong><?= $friend_count ?></strong><span>Friends</span></div>
            </div>
        </div>
    </div>

    <!-- Posts Grid -->
    <?php if (empty($user_posts)): ?>
        <div class="empty-state card">
            <p>No posts yet.</p>
            <?php if ($is_own_profile): ?>
                <a href="<?= BASE_URL ?>/pages/create_post.php" class="btn btn-primary">Share your first post</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="posts-grid">
            <?php foreach ($user_posts as $post): ?>
                <?php
                $like_count    = count(db_find_all($all_likes, 'post_id', $post['id']));
                $comment_count = count(db_find_all($all_comments, 'post_id', $post['id']));
                $liked         = (bool) db_find_one(db_find_all($all_likes, 'post_id', $post['id']), 'user_id', $me['id']);
                ?>
                <div class="post-card" id="post-<?= $post['id'] ?>">
                    <?php if ($post['image']): ?>
                        <div class="post-image-wrap">
                            <img
                                src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($post['image']) ?>"
                                alt="post"
                                class="post-image filter-<?= htmlspecialchars($post['filter']) ?>"
                            >
                        </div>
                    <?php endif; ?>
                    <div class="post-body">
                        <p class="post-caption"><?= nl2br(htmlspecialchars($post['caption'])) ?></p>
                        <div class="post-actions">
                            <button class="like-btn <?= $liked ? 'liked' : '' ?>"
                                    onclick="toggleLike(<?= $post['id'] ?>, this)">
                                <span class="like-icon">♥</span>
                                <span class="like-count"><?= $like_count ?></span>
                            </button>
                            <button class="comment-toggle-btn" onclick="toggleComments(<?= $post['id'] ?>)">
                                <span><?= $comment_count ?></span>
                            </button>
                            <span class="post-time"><?= date('M j, Y', strtotime($post['created_at'])) ?></span>
                        </div>
                        <div class="comments-section" id="comments-<?= $post['id'] ?>" style="display:none">
                            <div class="comments-list" id="comments-list-<?= $post['id'] ?>"></div>
                            <form class="comment-form" onsubmit="submitComment(event, <?= $post['id'] ?>)">
                                <input type="text" placeholder="Add a comment…" class="comment-input" required>
                                <button type="submit" class="btn btn-sm btn-primary">Post</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function uploadAvatar(input) {
    if (!input.files[0]) return;
    const fd = new FormData();
    fd.append('avatar', input.files[0]);
    fetch('<?= BASE_URL ?>/api/update_avatar.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const userId = <?= $profile_user['id'] ?>;
                const newSrc = '<?= BASE_URL ?>/uploads/' + d.filename + '?v=' + Date.now();
                document.querySelectorAll('img[data-user-id="' + userId + '"]').forEach(img => {
                    img.src = newSrc;
                });
            } else {
                alert(d.error);
            }
        });
}
function sendFriendRequest(userId, btn) {
    fetch('<?= BASE_URL ?>/api/friend_request.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'send', receiver_id: userId })
    }).then(r => r.json()).then(d => {
        if (d.success) { btn.textContent = 'Request Sent'; btn.disabled = true; }
        else alert(d.error);
    });
}
function respondFriend(userId, action, btn) {
    fetch('<?= BASE_URL ?>/api/friend_request.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: action, requester_id: userId })
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
