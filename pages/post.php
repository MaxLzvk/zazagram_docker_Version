<?php
// ============================================================
// pages/post.php — Single post detail view (Twitter-style)
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me      = get_current_user_data();
$post_id = (int)($_GET['id'] ?? 0);

if (!$post_id) {
    header('Location: ' . BASE_URL . '/pages/feed.php'); exit;
}

$all_posts = db_read('posts.json');
$post      = db_find_one($all_posts, 'id', $post_id);

if (!$post) {
    http_response_code(404);
    $page_title = 'Post not found';
    include __DIR__ . '/../includes/header.php';
    echo '<div class="container" style="padding-top:3rem"><div class="alert alert-error">Post not found.</div></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$all_users    = db_read('users.json');
$all_likes    = db_read('likes.json');
$all_comments = db_read('comments.json');

$author        = db_find_one($all_users, 'id', $post['user_id']);
$post_likes    = db_find_all($all_likes,    'post_id', $post['id']);
$post_comments = db_find_all($all_comments, 'post_id', $post['id']);
$liked         = (bool) db_find_one($post_likes, 'user_id', $me['id']);
$like_count    = count($post_likes);
$is_own_post   = ($me['id'] === $post['user_id']);

function time_ago_post(string $ts): string {
    $diff = time() - strtotime($ts);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff/60).'m ago';
    if ($diff < 86400)  return floor($diff/3600).'h ago';
    if ($diff < 604800) return floor($diff/86400).'d ago';
    return date('M j, Y \a\t g:i A', strtotime($ts));
}

$page_title = $author ? '@'.$author['username'].'\'s post' : 'Post';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:680px;padding-top:2rem;padding-bottom:4rem">

    <!-- Back button -->
    <a href="javascript:history.back()" style="display:inline-flex;align-items:center;gap:0.4rem;color:var(--text-muted);font-size:0.9rem;text-decoration:none;margin-bottom:1.5rem;transition:color .15s"
       onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--text-muted)'">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        Back
    </a>

    <!-- Post card -->
    <div class="card post-detail-card" id="post-<?= $post['id'] ?>" style="border-radius:18px;overflow:hidden;padding:0">

        <!-- Author header -->
        <div style="display:flex;align-items:center;gap:0.85rem;padding:1.25rem 1.5rem;border-bottom:1px solid var(--border)">
            <?php if ($author): ?>
            <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($author['username']) ?>">
                <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($author['profile_picture'] ?? 'default_avatar.png') ?>?v=<?= strtotime($author['updated_at'] ?? '') ?>"
                     alt=""
                     data-user-id="<?= $author['id'] ?>"
                     style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid var(--border-hi)"
                     onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/default_avatar.png'">
            </a>
            <div style="flex:1">
                <div style="display:flex;align-items:center;gap:0.4rem">
                    <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($author['username']) ?>" style="font-weight:700;color:var(--text);text-decoration:none">
                        <?= htmlspecialchars($author['username']) ?>
                    </a>
                    <?php if (($author['role'] ?? '') === 'admin'): ?>
                        <span style="background:rgba(232,98,10,.15);border:1px solid rgba(232,98,10,.4);color:#f97316;border-radius:999px;font-size:0.62rem;font-weight:900;letter-spacing:0.1em;padding:0.1rem 0.45rem;text-transform:uppercase">Admin</span>
                    <?php elseif (($author['role'] ?? '') === 'superadmin'): ?>
                        <span style="background:rgba(200,127,10,.1);border:1px solid #b8720a;color:#e8920a;border-radius:999px;font-size:0.62rem;font-weight:900;letter-spacing:0.1em;padding:0.1rem 0.45rem;text-transform:uppercase">Superadmin</span>
                    <?php endif; ?>
                </div>
                <div style="font-size:0.8rem;color:var(--text-muted)"><?= time_ago_post($post['created_at']) ?></div>
            </div>
            <?php endif; ?>

            <!-- Delete menu (owner or admin) -->
            <?php if ($is_own_post || $me['role'] === 'admin' || $me['role'] === 'superadmin'): ?>
            <div class="post-menu" style="position:relative">
                <button class="post-menu-btn" onclick="togglePostMenu(<?= $post['id'] ?>)"
                        style="background:none;border:none;color:var(--text-muted);cursor:pointer;padding:0.4rem;border-radius:6px">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
                </button>
                <div class="post-dropdown" id="pdrop-<?= $post['id'] ?>" style="display:none;position:absolute;right:0;top:100%;background:#1a1a28;border:1px solid var(--border-hi);border-radius:10px;min-width:130px;overflow:hidden;z-index:50;box-shadow:var(--shadow-md)">
                    <button onclick="deletePostDetail(<?= $post['id'] ?>)"
                            style="width:100%;padding:0.7rem 1rem;background:none;border:none;color:#ef4444;font-size:0.88rem;cursor:pointer;text-align:left;display:flex;align-items:center;gap:0.5rem">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        Delete Post
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Post image -->
        <?php if ($post['image']): ?>
        <div style="background:#000;max-height:600px;overflow:hidden;display:flex;align-items:center;justify-content:center">
            <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($post['image']) ?>"
                 alt="post"
                 class="filter-<?= htmlspecialchars($post['filter'] ?? 'none') ?>"
                 style="width:100%;max-height:600px;object-fit:contain;display:block">
        </div>
        <?php endif; ?>

        <!-- Caption -->
        <?php if ($post['caption']): ?>
        <div style="padding:1.25rem 1.5rem <?= $post['image'] ? '' : 'padding-top:0' ?>">
            <p style="font-size:1.05rem;line-height:1.65;color:var(--text);white-space:pre-wrap"><?= nl2br(htmlspecialchars($post['caption'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Stats row -->
        <div style="padding:0 1.5rem;margin-bottom:0.6rem;display:flex;gap:1.2rem;font-size:0.85rem;color:var(--text-muted)">
            <?php if ($like_count > 0): ?>
            <span><strong style="color:var(--text)"><?= $like_count ?></strong> like<?= $like_count !== 1 ? 's' : '' ?></span>
            <?php endif; ?>
            <?php if (count($post_comments) > 0): ?>
            <span><strong style="color:var(--text)"><?= count($post_comments) ?></strong> comment<?= count($post_comments) !== 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </div>

        <!-- Action buttons -->
        <div style="padding:0 1rem 0.4rem;border-top:1px solid var(--border);border-bottom:1px solid var(--border);display:flex;gap:0.25rem">
            <button id="likebtn-<?= $post['id'] ?>"
                    onclick="toggleLikeDetail(<?= $post['id'] ?>)"
                    style="display:flex;align-items:center;gap:0.45rem;background:none;border:none;color:<?= $liked ? '#ef4444' : 'var(--text-muted)' ?>;cursor:pointer;padding:0.75rem 1rem;border-radius:8px;font-size:0.9rem;font-weight:600;transition:all .15s;flex:1;justify-content:center"
                    onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background='none'">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="<?= $liked ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.8" id="like-icon-<?= $post['id'] ?>">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                </svg>
                <span id="like-label-<?= $post['id'] ?>"><?= $liked ? 'Liked' : 'Like' ?></span>
            </button>

            <button onclick="document.getElementById('detail-comment-input').focus()"
                    style="display:flex;align-items:center;gap:0.45rem;background:none;border:none;color:var(--text-muted);cursor:pointer;padding:0.75rem 1rem;border-radius:8px;font-size:0.9rem;font-weight:600;transition:all .15s;flex:1;justify-content:center"
                    onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background='none'">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z"/>
                </svg>
                Comment
            </button>

            <button onclick="sharePostDetail(<?= $post['id'] ?>)"
                    style="display:flex;align-items:center;gap:0.45rem;background:none;border:none;color:var(--text-muted);cursor:pointer;padding:0.75rem 1rem;border-radius:8px;font-size:0.9rem;font-weight:600;transition:all .15s;flex:1;justify-content:center"
                    onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background='none'">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"/>
                </svg>
                Share
            </button>
        </div>

        <!-- Comments section -->
        <div style="padding:1rem 1.5rem">

            <!-- Comment form -->
            <form onsubmit="submitDetailComment(event, <?= $post['id'] ?>)" style="display:flex;gap:0.6rem;align-items:center;margin-bottom:1.25rem">
                <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($me['profile_picture']) ?>?v=<?= strtotime($me['updated_at']) ?>"
                     alt=""
                     data-user-id="<?= $me['id'] ?>"
                     style="width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0"
                     onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/default_avatar.png'">
                <input id="detail-comment-input" type="text" placeholder="Add a comment…" required
                       style="flex:1;background:var(--bg-input);border:1px solid var(--border);border-radius:999px;padding:0.55rem 1rem;color:var(--text);font-size:0.9rem">
                <button type="submit" class="btn btn-sm btn-primary">Post</button>
            </form>

            <!-- Comments list -->
            <div id="detail-comments-list">
                <?php foreach ($post_comments as $c):
                    $ca = db_find_one($all_users, 'id', $c['user_id']);
                    if (!$ca) continue;
                ?>
                <div class="detail-comment" id="dc-<?= $c['id'] ?>" style="display:flex;gap:0.65rem;margin-bottom:1rem;align-items:flex-start">
                    <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($ca['username']) ?>" style="flex-shrink:0">
                        <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($ca['profile_picture'] ?? 'default_avatar.png') ?>"
                             alt=""
                             style="width:32px;height:32px;border-radius:50%;object-fit:cover"
                             onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/default_avatar.png'">
                    </a>
                    <div style="flex:1">
                        <div style="background:var(--bg-raised);border-radius:12px;padding:0.6rem 0.9rem;display:inline-block;max-width:100%">
                            <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($ca['username']) ?>" style="font-weight:700;font-size:0.85rem;color:var(--text);text-decoration:none"><?= htmlspecialchars($ca['username']) ?></a>
                            <p style="font-size:0.88rem;color:var(--text-sub);margin-top:0.15rem"><?= nl2br(htmlspecialchars($c['content'])) ?></p>
                        </div>
                        <div style="font-size:0.75rem;color:var(--text-dim);margin-top:0.25rem;padding-left:0.5rem"><?= time_ago_post($c['created_at']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($post_comments)): ?>
                <p style="text-align:center;color:var(--text-muted);font-size:0.9rem;padding:1rem 0">No comments yet. Be the first!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const POST_ID   = <?= $post['id'] ?>;
const BASE_URL  = '<?= BASE_URL ?>';
const MY_ID     = <?= $me['id'] ?>;
let currentLiked = <?= $liked ? 'true' : 'false' ?>;
let currentLikes = <?= $like_count ?>;

function toggleLikeDetail(pid) {
    fetch(BASE_URL + '/api/toggle_like.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({post_id: pid})
    }).then(r => r.json()).then(d => {
        if (d.success) {
            currentLiked = d.liked;
            currentLikes = d.like_count;
            const btn  = document.getElementById('likebtn-' + pid);
            const icon = document.getElementById('like-icon-' + pid);
            const lbl  = document.getElementById('like-label-' + pid);
            btn.style.color  = currentLiked ? '#ef4444' : 'var(--text-muted)';
            icon.setAttribute('fill', currentLiked ? 'currentColor' : 'none');
            lbl.textContent  = currentLiked ? 'Liked' : 'Like';
            // update stats row
            updateStatsRow();
        }
    });
}

function updateStatsRow() {
    // re-render the likes count in stats row
    const statsRow = document.querySelector('.post-detail-card [style*="1.2rem"]');
    // simpler: just update textContent of first strong
    const strongs = document.querySelectorAll('.post-detail-card > div > span > strong');
    if (strongs[0]) strongs[0].textContent = currentLikes;
}

function submitDetailComment(e, pid) {
    e.preventDefault();
    const input = document.getElementById('detail-comment-input');
    const text  = input.value.trim();
    if (!text) return;
    fetch(BASE_URL + '/api/add_comment.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({post_id: pid, content: text})
    }).then(r => r.json()).then(d => {
        if (d.success) {
            input.value = '';
            const list = document.getElementById('detail-comments-list');
            // Remove "no comments" message if present
            const emptyMsg = list.querySelector('p');
            if (emptyMsg) emptyMsg.remove();
            // Append new comment
            const div = document.createElement('div');
            div.className = 'detail-comment';
            div.id = 'dc-' + d.comment.id;
            div.style.cssText = 'display:flex;gap:0.65rem;margin-bottom:1rem;align-items:flex-start';
            div.innerHTML = `
                <a href="${BASE_URL}/pages/profile.php?username=${encodeURIComponent(d.comment.username)}" style="flex-shrink:0">
                    <img src="${BASE_URL}/uploads/${d.comment.avatar}"
                         style="width:32px;height:32px;border-radius:50%;object-fit:cover"
                         onerror="this.onerror=null;this.src='${BASE_URL}/assets/images/default_avatar.png'">
                </a>
                <div style="flex:1">
                    <div style="background:var(--bg-raised);border-radius:12px;padding:0.6rem 0.9rem;display:inline-block;max-width:100%">
                        <a href="${BASE_URL}/pages/profile.php?username=${encodeURIComponent(d.comment.username)}" style="font-weight:700;font-size:0.85rem;color:var(--text);text-decoration:none">${d.comment.username}</a>
                        <p style="font-size:0.88rem;color:var(--text-sub);margin-top:0.15rem">${d.comment.content.replace(/\n/g,'<br>')}</p>
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-dim);margin-top:0.25rem;padding-left:0.5rem">just now</div>
                </div>`;
            list.appendChild(div);
        }
    });
}

function deletePostDetail(pid) {
    if (!confirm('Delete this post?')) return;
    fetch(BASE_URL + '/api/delete_post.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({post_id: pid})
    }).then(r => r.json()).then(d => {
        if (d.success) window.location.href = BASE_URL + '/pages/feed.php';
        else alert(d.error || 'Error deleting post.');
    });
}

function sharePostDetail(pid) {
    const url = window.location.href;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            const btn = event.currentTarget;
            const orig = btn.innerHTML;
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Copied!';
            btn.style.color = 'var(--green)';
            setTimeout(() => { btn.innerHTML = orig; btn.style.color = ''; }, 2000);
        });
    } else {
        prompt('Copy this link:', url);
    }
}

function togglePostMenu(pid) {
    const d = document.getElementById('pdrop-' + pid);
    if (d) d.style.display = d.style.display === 'block' ? 'none' : 'block';
}
document.addEventListener('click', (e) => {
    if (!e.target.closest('.post-menu')) {
        document.querySelectorAll('.post-dropdown').forEach(el => el.style.display = 'none');
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
