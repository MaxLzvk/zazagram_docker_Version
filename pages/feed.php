<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me = get_current_user_data();

// Load all data
$all_posts    = db_read('posts.json');
$all_users    = db_read('users.json');
$all_likes    = db_read('likes.json');
$all_comments = db_read('comments.json');
$friends      = db_read('friends.json');

// Get friend IDs for the current user
$friend_ids = [];
foreach ($friends as $f) {
    if ($f['status'] === 'accepted') {
        if ($f['requester_id'] == $me['id']) $friend_ids[] = $f['receiver_id'];
        if ($f['receiver_id']  == $me['id']) $friend_ids[] = $f['requester_id'];
    }
}
$friend_ids[] = $me['id']; // include own posts
$friend_count = count(array_filter($friend_ids, fn($id) => $id !== $me['id']));

// ── Right sidebar data ───────────────────────────────────
// Trending: top 4 most-liked posts
$like_counts = [];
foreach ($all_likes as $lk) {
    $like_counts[$lk['post_id']] = ($like_counts[$lk['post_id']] ?? 0) + 1;
}
arsort($like_counts);
$trending_posts = [];
foreach (array_slice(array_keys($like_counts), 0, 4) as $pid) {
    foreach ($all_posts as $p) {
        if ($p['id'] == $pid) { $trending_posts[] = ['post' => $p, 'likes' => $like_counts[$pid]]; break; }
    }
}

// Recently active users (last 5 who posted, excluding me)
$active_users = [];
$seen_active  = [];
foreach ($all_posts as $p) {
    if ($p['user_id'] == $me['id']) continue;
    if (in_array($p['user_id'], $seen_active)) continue;
    $u = null;
    foreach ($all_users as $usr) { if ($usr['id'] == $p['user_id']) { $u = $usr; break; } }
    if ($u && !$u['is_banned']) {
        $active_users[] = ['user' => $u, 'last_post' => $p['created_at']];
        $seen_active[]  = $p['user_id'];
        if (count($active_users) >= 5) break;
    }
}

// Platform stats
$total_posts  = count($all_posts);
$total_likes  = count($all_likes);
$total_users  = count(array_filter($all_users, fn($u) => !$u['is_banned']));
$total_comments = count($all_comments);

// Show all posts
$feed_posts = $all_posts;
// Sort newest first
usort($feed_posts, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

// Helper
function get_user_safe(array $all_users, int $id): ?array {
    foreach ($all_users as $u) {
        if ($u['id'] === $id) { unset($u['password']); return $u; }
    }
    return null;
}

$page_title = 'Feed';
$extra_js   = ['posts.js'];

function time_ago(string $timestamp): string {
    $diff = time() - strtotime($timestamp);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($timestamp));
}

include __DIR__ . '/../includes/header.php';
?>

<div class="feed-layout">

    <!-- Left sidebar: suggestions / friends -->
    <aside class="feed-sidebar left-sidebar">
        <div class="card sidebar-me">
            <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($me['profile_picture']) ?>?v=<?= strtotime($me['updated_at']) ?>"
                 class="sidebar-avatar"
                 data-user-id="<?= $me['id'] ?>"
                 onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/default_avatar.png'">
            <div>
                <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($me['username']) ?>">
                    <strong><?= htmlspecialchars($me['username']) ?></strong>
                </a>
                <p><?= htmlspecialchars($me['first_name'] . ' ' . $me['last_name']) ?></p>
            </div>
        </div>

        <div class="card">
            <h3>People you may know</h3>
            <?php
            $suggestions = array_filter($all_users, function($u) use ($me, $friend_ids) {
                return $u['id'] !== $me['id'] && !in_array($u['id'], $friend_ids) && !$u['is_banned'];
            });
            $suggestions = array_slice(array_values($suggestions), 0, 5);
            ?>
            <?php if (empty($suggestions)): ?>
                <p class="muted">You know everyone!</p>
            <?php else: ?>
                <?php foreach ($suggestions as $sug): ?>
                    <div class="suggestion-item">
                        <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($sug['profile_picture']) ?>?v=<?= strtotime($sug['updated_at']) ?>"
                             class="suggestion-avatar"
                             data-user-id="<?= $sug['id'] ?>"
                             onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/default_avatar.png'">
                        <div>
                            <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($sug['username']) ?>">
                                <?= htmlspecialchars($sug['username']) ?>
                            </a>
                            <p><?= htmlspecialchars($sug['first_name']) ?></p>
                        </div>
                        <button class="btn btn-sm btn-outline"
                                onclick="sendFriendRequest(<?= $sug['id'] ?>, this)">Add</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card stats-card">
            <h3>Quick stats</h3>
            <div class="stat-row">
                <span>Friends</span>
                <strong><?= $friend_count ?></strong>
            </div>
            <div class="stat-row">
                <span>Your posts</span>
                <strong><?= count(array_filter($all_posts, fn($p) => $p['user_id'] === $me['id'])) ?></strong>
            </div>
            <div class="stat-row">
                <span>Feed items</span>
                <strong><?= count($feed_posts) ?></strong>
            </div>
        </div>
    </aside>

    <!-- Main feed -->
    <div class="feed-main">

        <!-- Quick post box -->
        <div class="card quick-post">
            <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($me['profile_picture']) ?>?v=<?= strtotime($me['updated_at']) ?>"
                 class="nav-avatar"
                 data-user-id="<?= $me['id'] ?>"
                 onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/default_avatar.png'">
            <a href="<?= BASE_URL ?>/pages/create_post.php" class="quick-post-input">
                What's on your mind, <?= htmlspecialchars($me['first_name']) ?>?
            </a>
            <a href="<?= BASE_URL ?>/pages/create_post.php" class="btn btn-primary btn-sm">New Post</a>
        </div>

        <!-- Posts -->
        <div id="feed-posts">
        <?php if (empty($feed_posts)): ?>
            <div class="card empty-state" id="feed-empty">
                <p>Your feed is empty. <a href="<?= BASE_URL ?>/pages/create_post.php">Create your first post</a> or add some friends!</p>
            </div>
        <?php else: ?>
            <?php foreach ($feed_posts as $post):
                $author = get_user_safe($all_users, $post['user_id']);
                if (!$author) continue;
                $post_likes    = db_find_all($all_likes, 'post_id', $post['id']);
                $post_comments = db_find_all($all_comments, 'post_id', $post['id']);
                $liked = (bool) db_find_one($post_likes, 'user_id', $me['id']);
                $is_own_post = ($post['user_id'] == $me['id']);
            ?>
            <div class="post-card card" id="post-<?= $post['id'] ?>">
                <!-- Post Header -->
                <div class="post-header">
                    <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($author['username']) ?>">
                        <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($author['profile_picture']) ?>?v=<?= strtotime($author['updated_at']) ?>"
                             class="post-author-avatar"
                             data-user-id="<?= $author['id'] ?>"
                             onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/default_avatar.png'">
                    </a>
                    <div class="post-author-info">
                        <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($author['username']) ?>">
                            <strong><?= htmlspecialchars($author['username']) ?></strong>
                        </a>
                        <span class="post-time"><?= time_ago($post['created_at']) ?></span>
                    </div>
                    <?php if ($is_own_post || $me['role'] === 'admin'): ?>
                        <div class="post-menu">
                            <button class="post-menu-btn" onclick="togglePostMenu(<?= $post['id'] ?>)">⋯</button>
                            <div class="post-dropdown" id="pdrop-<?= $post['id'] ?>">
                                <button onclick="deletePost(<?= $post['id'] ?>)">Delete</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Post Image -->
                <?php if ($post['image']): ?>
                    <div class="post-image-wrap">
                        <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($post['image']) ?>"
                             alt="post"
                             class="post-image filter-<?= htmlspecialchars($post['filter']) ?>">
                    </div>
                <?php endif; ?>

                <!-- Post Body -->
                <div class="post-body">
                    <?php if ($post['caption']): ?>
                        <p class="post-caption">
                            <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($author['username']) ?>">
                                <strong><?= htmlspecialchars($author['username']) ?></strong>
                            </a>
                            <?= nl2br(htmlspecialchars($post['caption'])) ?>
                        </p>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="post-actions">
                        <button class="like-btn <?= $liked ? 'liked' : '' ?>"
                                onclick="toggleLike(<?= $post['id'] ?>, this)">
                            <span class="like-icon">♥</span>
                            <span class="like-count"><?= count($post_likes) ?></span>
                        </button>
                        <button class="comment-toggle-btn"
                                onclick="toggleComments(<?= $post['id'] ?>)">
                            <span><?= count($post_comments) ?></span>
                        </button>
                    </div>

                    <!-- Comments -->
                    <div class="comments-section" id="comments-<?= $post['id'] ?>" style="display:none">
                        <div class="comments-list" id="comments-list-<?= $post['id'] ?>">
                            <?php foreach (array_slice($post_comments, -3) as $c):
                                $c_author = get_user_safe($all_users, $c['user_id']);
                            ?>
                                <?php if ($c_author): ?>
                                    <div class="comment">
                                        <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($c_author['username']) ?>">
                                            <strong><?= htmlspecialchars($c_author['username']) ?></strong>
                                        </a>
                                        <?= nl2br(htmlspecialchars($c['content'])) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <form class="comment-form" onsubmit="submitComment(event, <?= $post['id'] ?>)">
                            <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($me['profile_picture']) ?>?v=<?= strtotime($me['updated_at']) ?>"
                                 class="comment-avatar"
                                 data-user-id="<?= $me['id'] ?>"
                                 onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/default_avatar.png'">
                            <input type="text" placeholder="Add a comment…" class="comment-input" required>
                            <button type="submit" class="btn btn-sm btn-primary">Post</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div><!-- /#feed-posts -->
    </div>

    <!-- Right sidebar -->
    <aside class="feed-sidebar">

        <!-- 🔥 Trending Posts -->
        <div class="card rs-card">
            <h3><span class="rs-icon">🔥</span> Trending</h3>
            <?php if (empty($trending_posts)): ?>
                <p class="muted-sm">No trending posts yet.</p>
            <?php else: ?>
                <?php foreach ($trending_posts as $t):
                    $ta = get_user_safe($all_users, $t['post']['user_id']);
                    if (!$ta) continue;
                ?>
                <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($ta['username']) ?>"
                   class="trending-item">
                    <?php if ($t['post']['image']): ?>
                        <div class="trending-thumb">
                            <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($t['post']['image']) ?>"
                                 alt="" class="filter-<?= htmlspecialchars($t['post']['filter']) ?>">
                        </div>
                    <?php else: ?>
                        <div class="trending-thumb trending-thumb-text">
                            <span><?= mb_substr(htmlspecialchars($t['post']['caption'] ?? '✦'), 0, 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="trending-info">
                        <span class="trending-user">@<?= htmlspecialchars($ta['username']) ?></span>
                        <span class="trending-caption"><?= htmlspecialchars(mb_substr($t['post']['caption'] ?? '', 0, 40)) ?><?= strlen($t['post']['caption'] ?? '') > 40 ? '…' : '' ?></span>
                        <span class="trending-likes">♥ <?= $t['likes'] ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 👥 Recently Active -->
        <div class="card rs-card">
            <h3><span class="rs-icon">👥</span> Recently Active</h3>
            <?php if (empty($active_users)): ?>
                <p class="muted-sm">No activity yet.</p>
            <?php else: ?>
                <?php foreach ($active_users as $au): ?>
                <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($au['user']['username']) ?>"
                   class="active-user-item">
                    <div class="active-avatar-wrap">
                        <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($au['user']['profile_picture']) ?>?v=<?= strtotime($au['user']['updated_at']) ?>"
                             class="active-avatar"
                             onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/default_avatar.png'">
                        <span class="active-dot"></span>
                    </div>
                    <div>
                        <span class="active-name">@<?= htmlspecialchars($au['user']['username']) ?></span>
                        <span class="active-time"><?= time_ago($au['last_post']) ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 📊 Platform Stats -->
        <div class="card rs-card rs-stats">
            <h3><span class="rs-icon">📊</span> Zazagram</h3>
            <div class="rs-stat-grid">
                <div class="rs-stat">
                    <span class="rs-stat-num"><?= $total_users ?></span>
                    <span class="rs-stat-lbl">Members</span>
                </div>
                <div class="rs-stat">
                    <span class="rs-stat-num"><?= $total_posts ?></span>
                    <span class="rs-stat-lbl">Posts</span>
                </div>
                <div class="rs-stat">
                    <span class="rs-stat-num"><?= $total_likes ?></span>
                    <span class="rs-stat-lbl">Likes</span>
                </div>
                <div class="rs-stat">
                    <span class="rs-stat-num"><?= $total_comments ?></span>
                    <span class="rs-stat-lbl">Comments</span>
                </div>
            </div>
        </div>

    </aside>

</div>

<script>
const FEED_LAST_POST_ID = <?= empty($all_posts) ? 0 : max(array_column($all_posts, 'id')) ?>;
const MY_USER_ID        = <?= $me['id'] ?>;
const MY_AVATAR         = '<?= addslashes($me['profile_picture']) ?>';
const MY_AVATAR_VER     = <?= strtotime($me['updated_at']) ?: 0 ?>;
const IS_ADMIN          = <?= $me['role'] === 'admin' ? 'true' : 'false' ?>;
function sendFriendRequest(userId, btn) {
    fetch('<?= BASE_URL ?>/api/friend_request.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'send', receiver_id: userId })
    }).then(r => r.json()).then(d => {
        if (d.success) { btn.textContent = 'Sent'; btn.disabled = true; }
        else alert(d.error);
    });
}
function togglePostMenu(id) {
    const el = document.getElementById('pdrop-' + id);
    el.style.display = el.style.display === 'block' ? 'none' : 'block';
}

// Ferme le dropdown quand on clique ailleurs
document.addEventListener('click', function(e) {
    if (!e.target.closest('.post-menu')) {
        document.querySelectorAll('.post-dropdown').forEach(function(el) {
            el.style.display = 'none';
        });
    }
});
function deletePost(id) {
    if (!confirm('Delete this post?')) return;
    fetch('<?= BASE_URL ?>/api/delete_post.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ post_id: id })
    }).then(r => r.json()).then(d => {
        if (d.success) document.getElementById('post-' + id).remove();
        else alert(d.error);
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
