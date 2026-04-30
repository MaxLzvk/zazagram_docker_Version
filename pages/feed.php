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

// Role badge helper — $author already has role from db_read('users.json') via MySQL
function role_badge(string $role): string {
    $base = 'display:inline-flex;align-items:center;gap:0.25rem;padding:0.15rem 0.55rem;border-radius:999px;font-size:0.6rem;font-weight:900;letter-spacing:0.1em;text-transform:uppercase;vertical-align:middle;margin-left:0.45rem;white-space:nowrap;background:#0a0a14;';
    $star_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.736.749 2.589l-4.204 3.602 1.25 5.275c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.25-5.275-4.204-3.602c-.887-.853-.415-2.496.749-2.589l5.404-.434 2.082-5.005Z" clip-rule="evenodd"/></svg>';
    $shield_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 0 0-1.032 0 11.209 11.209 0 0 1-7.877 3.08.75.75 0 0 0-.722.515A12.74 12.74 0 0 0 2.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 0 0 .374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 0 0-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08Z" clip-rule="evenodd"/></svg>';
    if ($role === 'superadmin') return '<span class="user-badge user-badge-superadmin" style="'.$base.'border:1px solid #b8720a;color:#e8920a;box-shadow:0 0 8px rgba(200,127,10,.3);">'.$star_svg.' Superadmin</span>';
    if ($role === 'admin')      return '<span class="user-badge user-badge-admin" style="'.$base.'border:1px solid rgba(232,98,10,.55);color:#f97316;">'.$shield_svg.' Admin</span>';
    return '';
}

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
                        <div class="post-author-name-row">
                            <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($author['username']) ?>">
                                <strong><?= htmlspecialchars($author['username']) ?></strong>
                            </a><?= role_badge($author['role'] ?? '') ?>
                        </div>
                        <span class="post-time"><?= time_ago($post['created_at']) ?></span>
                    </div>
                    <?php if ($is_own_post || in_array($me['role'], ['admin', 'superadmin'])): ?>
                        <div class="post-menu">
                            <button class="post-menu-btn" onclick="togglePostMenu(<?= $post['id'] ?>)"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg></button>
                            <div class="post-dropdown" id="pdrop-<?= $post['id'] ?>">
                                <button onclick="deletePost(<?= $post['id'] ?>)">Delete</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Post Image -->
                <?php if ($post['image']): ?>
                    <div class="post-image-wrap">
                        <a href="<?= BASE_URL ?>/pages/post.php?id=<?= $post['id'] ?>">
                        <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($post['image']) ?>"
                             alt="post"
                             class="post-image filter-<?= htmlspecialchars($post['filter']) ?>">
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Post Body -->
                <div class="post-body">
                    <?php if ($post['caption']): ?>
                        <p class="post-caption">
                            <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($author['username']) ?>">
                                <strong><?= htmlspecialchars($author['username']) ?></strong>
                            </a><?= role_badge($author['role'] ?? '') ?>
                            <?= nl2br(htmlspecialchars($post['caption'])) ?>
                        </p>
                    <?php endif; ?>

                    <!-- Actions -->
                    <?php
                        $total_likes    = count($post_likes);
                        $total_comments = count($post_comments);
                        $liked          = (bool) db_find_one($post_likes, 'user_id', $me['id']);
                    ?>

                    <!-- Summary row -->
                    <?php if ($total_likes > 0 || $total_comments > 0): ?>
                    <div class="react-summary">
                        <?php if ($total_likes > 0): ?>
                        <div class="react-emojis">
                            <span class="react-bubble"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M7.493 18.5c-.425 0-.82-.236-.975-.632A7.48 7.48 0 0 1 6 15.125c0-1.75.599-3.358 1.602-4.634.151-.192.373-.309.6-.397.473-.183.89-.514 1.212-.924a9.042 9.042 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75A.75.75 0 0 1 15 2a2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H14.23c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23h-.777ZM2.331 10.727a11.969 11.969 0 0 0-.831 4.398 12 12 0 0 0 .52 3.507C2.28 19.482 3.105 20 3.994 20H4.9c.445 0 .72-.498.523-.898a8.963 8.963 0 0 1-.924-3.977c0-1.708.476-3.305 1.302-4.666.245-.403-.028-.959-.5-.959H4.25c-.832 0-1.612.453-1.918 1.227Z"/></svg></span>
                            <span class="react-total"><?= $total_likes ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($total_comments > 0): ?>
                        <button class="react-comment-count" onclick="toggleComments(<?= $post['id'] ?>)">
                            <?= $total_comments ?> comment<?= $total_comments !== 1 ? 's' : '' ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Action buttons row -->
                    <div class="post-actions">

                        <!-- Like -->
                        <button class="action-btn like-btn <?= $liked ? 'reacted' : '' ?>"
                                id="likebtn-<?= $post['id'] ?>"
                                onclick="toggleLikeSimple(<?= $post['id'] ?>)">
                            <span class="action-icon" id="like-icon-<?= $post['id'] ?>"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M7.493 18.5c-.425 0-.82-.236-.975-.632A7.48 7.48 0 0 1 6 15.125c0-1.75.599-3.358 1.602-4.634.151-.192.373-.309.6-.397.473-.183.89-.514 1.212-.924a9.042 9.042 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75A.75.75 0 0 1 15 2a2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H14.23c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23h-.777ZM2.331 10.727a11.969 11.969 0 0 0-.831 4.398 12 12 0 0 0 .52 3.507C2.28 19.482 3.105 20 3.994 20H4.9c.445 0 .72-.498.523-.898a8.963 8.963 0 0 1-.924-3.977c0-1.708.476-3.305 1.302-4.666.245-.403-.028-.959-.5-.959H4.25c-.832 0-1.612.453-1.918 1.227Z"/></svg></span>
                            <span class="action-label" id="like-label-<?= $post['id'] ?>"><?= $liked ? 'Liked' : 'Like' ?></span>
                        </button>

                        <!-- Comment -->
                        <button class="action-btn comment-toggle-btn" onclick="toggleComments(<?= $post['id'] ?>)">
                            <span class="action-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.804 21.644A6.707 6.707 0 0 0 6 21.75a6.721 6.721 0 0 0 3.583-1.029c.774.182 1.584.279 2.417.279 5.322 0 9.75-3.97 9.75-9 0-5.03-4.428-9-9.75-9s-9.75 3.97-9.75 9c0 2.409 1.025 4.587 2.674 6.192.232.226.277.428.254.543a3.73 3.73 0 0 1-.814 1.686.75.75 0 0 0 .44 1.223Z" clip-rule="evenodd"/></svg></span>
                            <span class="action-label">Comment</span>
                        </button>

                        <!-- Share -->
                        <button class="action-btn share-btn" onclick="sharePost(<?= $post['id'] ?>)">
                            <span class="action-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M15.75 4.5a3 3 0 1 1 .825 2.066l-8.421 4.679a3.002 3.002 0 0 1 0 1.51l8.421 4.679a3 3 0 1 1-.729 1.31l-8.421-4.678a3 3 0 1 1 0-4.132l8.421-4.679a3 3 0 0 1-.096-.755Z" clip-rule="evenodd"/></svg></span>
                            <span class="action-label">Share</span>
                        </button>

                        <!-- View full post -->
                        <a class="action-btn" href="<?= BASE_URL ?>/pages/post.php?id=<?= $post['id'] ?>" style="text-decoration:none">
                            <span class="action-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg></span>
                            <span class="action-label">View</span>
                        </a>

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
                                        </a><?= role_badge($c_author['role'] ?? '') ?>
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
            <h3><span class="rs-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#f97316"><path fill-rule="evenodd" d="M12.963 2.286a.75.75 0 0 0-1.071-.136 9.742 9.742 0 0 0-3.539 6.177A7.547 7.547 0 0 1 6.648 6.61a.75.75 0 0 0-1.152-.082A9 9 0 1 0 15.68 4.534a7.46 7.46 0 0 1-2.717-2.248ZM15.75 14.25a3.75 3.75 0 1 1-7.313-1.172c.628.465 1.35.81 2.133 1a5.99 5.99 0 0 1 1.925-3.545 3.75 3.75 0 0 1 3.255 3.717Z" clip-rule="evenodd"/></svg></span> Trending</h3>
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

        <!-- 🟢 Online Now (WebSocket) -->
        <div class="card rs-card" id="online-card">
            <h3><span class="rs-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#22c55e"><circle cx="12" cy="12" r="10"/></svg></span> Online Now <span id="online-count" style="font-size:0.75rem;font-weight:600;color:rgba(255,255,255,.35);margin-left:4px">(0)</span></h3>
            <div id="online-list">
                <p class="muted-sm" id="online-empty">Connecting…</p>
            </div>
        </div>

        <!-- 📊 Platform Stats -->
        <div class="card rs-card rs-stats">
            <h3><span class="rs-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75ZM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 0 1-1.875-1.875V8.625ZM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 0 1 3 19.875v-6.75Z"/></svg></span> Zazagram</h3>
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
const MY_USERNAME       = '<?= addslashes($me['username']) ?>';
const MY_AVATAR         = '<?= addslashes($me['profile_picture']) ?>';
const MY_AVATAR_VER     = <?= strtotime($me['updated_at']) ?: 0 ?>;
const IS_ADMIN          = <?= $me['role'] === 'admin' ? 'true' : 'false' ?>;
const BASE_URL_JS       = '<?= BASE_URL ?>';

// ── WS: Online Users (driven by global WS in main.js) ────
window.addEventListener('zzg:online_list', (e) => renderOnline(e.detail.users));

// ── WS: New post pushed by another user ──────────────────
window.addEventListener('zzg:new_post', (e) => {
    const { post, author } = e.detail;
    if (!post || !author) return;
    if (post.id <= FEED_LAST_POST_ID) return; // already on page
    if (document.getElementById('post-' + post.id)) return;

    const wrap = document.getElementById('feed-posts');
    if (!wrap) return;

    const avatarSrc = BASE_URL_JS + '/uploads/' + (author.profile_picture || 'default_avatar.png');
    const isOwn     = post.user_id == MY_USER_ID;

    const div = document.createElement('div');
    div.className = 'post-card reveal visible';
    div.id = 'post-' + post.id;
    div.innerHTML = `
        <div class="post-header">
            <div class="post-author">
                <a href="${BASE_URL_JS}/pages/profile.php?username=${encodeURIComponent(author.username)}">
                    <img src="${avatarSrc}" class="post-avatar" data-user-id="${author.id}"
                         onerror="this.src='${BASE_URL_JS}/assets/images/default_avatar.png'">
                </a>
                <a href="${BASE_URL_JS}/pages/profile.php?username=${encodeURIComponent(author.username)}" style="text-decoration:none">
                    <strong>${escHtml(author.username)}</strong>
                </a>
            </div>
            <span class="post-time">just now</span>
            ${(isOwn || IS_ADMIN) ? `
            <div class="post-menu">
                <button class="post-menu-btn" onclick="togglePostMenu(${post.id})">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
                </button>
                <div class="post-dropdown" id="pdrop-${post.id}">
                    <button onclick="deletePost(${post.id})">Delete</button>
                </div>
            </div>` : ''}
        </div>
        ${post.image ? `<div class="post-image-wrap"><img src="${BASE_URL_JS}/uploads/${escHtml(post.image)}" class="post-image filter-${escHtml(post.filter || 'none')}" alt="post"></div>` : ''}
        <div class="post-body">
            <p class="post-caption">${escHtml(post.caption || '').replace(/\n/g,'<br>')}</p>
            <div class="post-actions">
                <button class="like-btn" id="likebtn-${post.id}" onclick="toggleLikeSimple(${post.id})">
                    <span class="like-icon" id="like-icon-${post.id}">♡</span>
                    <span id="like-label-${post.id}">Like</span>
                    <span class="like-count" id="like-count-${post.id}">0</span>
                </button>
            </div>
        </div>`;
    wrap.prepend(div);
});

// ── WS: Avatar updated ────────────────────────────────────
window.addEventListener('zzg:avatar', (e) => {
    // main.js already updates all img[data-user-id] elements; nothing extra needed here
});

function renderOnline(rawUsers) {
    const seen  = new Set();
    const users = rawUsers.filter(u => {
        const key = String(u.id);
        if (seen.has(key)) return false;
        seen.add(key); return true;
    });
    const list  = document.getElementById('online-list');
    const count = document.getElementById('online-count');
    if (!list) return;
    count.textContent = '(' + users.length + ')';
    if (users.length === 0) {
        list.innerHTML = '<p class="muted-sm">No one online right now.</p>';
        return;
    }
    list.innerHTML = users.map(u => {
        const isSelf    = u.id == MY_USER_ID;
        const avatarSrc = BASE_URL_JS + '/uploads/' + (u.avatar || 'default_avatar.png');
        return `
        <a href="${BASE_URL_JS}/pages/profile.php?username=${encodeURIComponent(u.username)}"
           class="active-user-item" style="text-decoration:none">
            <div class="active-avatar-wrap">
                <img src="${avatarSrc}" class="active-avatar"
                     onerror="this.src='${BASE_URL_JS}/assets/images/default_avatar.png'">
                <span class="active-dot" style="background:#22c55e;box-shadow:0 0 0 2px #080810,0 0 6px #22c55e"></span>
            </div>
            <div>
                <span class="active-name">@${u.username}${isSelf ? ' <span style="font-size:0.68rem;color:var(--orange)">(you)</span>' : ''}</span>
                <span class="active-time" style="color:#22c55e;font-size:0.72rem">● online</span>
            </div>
        </a>`;
    }).join('');
}

function toggleLikeSimple(postId) {
    const btn   = document.getElementById('likebtn-' + postId);
    const icon  = document.getElementById('like-icon-' + postId);
    const label = document.getElementById('like-label-' + postId);
    const liked = btn.classList.contains('reacted');

    fetch('<?= BASE_URL ?>/api/toggle_like.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ post_id: postId, reaction: 'like', toggle_off: liked })
    }).then(r => r.json()).then(d => {
        if (!d.success) return;
        btn.classList.toggle('reacted', !liked);
        label.textContent = liked ? 'Like' : 'Liked';
        // pop animation
        btn.classList.remove('pop');
        void btn.offsetWidth;
        btn.classList.add('pop');
        btn.addEventListener('animationend', () => btn.classList.remove('pop'), {once: true});
    });
}

function sharePost(postId) {
    const url = window.location.origin + window.location.pathname + '?post=' + postId;
    if (navigator.share) {
        navigator.share({ title: 'Zazagram post', url });
    } else {
        navigator.clipboard.writeText(url).then(() => {
            showToast('Link copied to clipboard!');
        });
    }
}

function showToast(msg) {
    const t = document.createElement('div');
    t.className = 'toast-msg';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.classList.add('toast-show'); }, 10);
    setTimeout(() => { t.classList.remove('toast-show'); setTimeout(() => t.remove(), 400); }, 2800);
}

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
