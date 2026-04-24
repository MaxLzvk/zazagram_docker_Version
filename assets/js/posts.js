// ============================================================
// posts.js — Likes, Comments, Post interactions
// ============================================================

const BASE = document.querySelector('meta[name="base-url"]')?.content
    || window.location.origin + '/Zazagram_Website';

// ── Like / Unlike ─────────────────────────────────────────
function toggleLike(postId, btn) {
    btn.disabled = true;
    fetch(BASE + '/api/toggle_like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: postId }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = (data.liked ? '❤️' : '🤍') +
                `<span class="like-count">${data.count}</span>`;
            btn.classList.toggle('liked', data.liked);
        } else {
            alert(data.error || 'Error liking post.');
        }
    })
    .catch(() => alert('Network error.'))
    .finally(() => btn.disabled = false);
}

// ── Toggle Comments Section ───────────────────────────────
function toggleComments(postId) {
    const section = document.getElementById('comments-' + postId);
    if (!section) return;
    const isHidden = section.style.display === 'none';
    section.style.display = isHidden ? 'block' : 'none';
    if (isHidden) loadComments(postId);
}

// ── Load Comments ─────────────────────────────────────────
function loadComments(postId) {
    const list = document.getElementById('comments-list-' + postId);
    if (!list) return;
    fetch(BASE + '/api/get_comments.php?post_id=' + postId)
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        list.innerHTML = '';
        data.comments.forEach(c => appendComment(list, c));
    });
}

function appendComment(container, c) {
    const div = document.createElement('div');
    div.className = 'comment';
    div.innerHTML = `<a href="${BASE}/pages/profile.php?username=${encodeURIComponent(c.username)}">
        <strong>${escHtml(c.username)}</strong></a> ${escHtml(c.content)}`;
    container.appendChild(div);
}

// ── Submit Comment ────────────────────────────────────────
function submitComment(event, postId) {
    event.preventDefault();
    const form  = event.target;
    const input = form.querySelector('.comment-input');
    const content = input.value.trim();
    if (!content) return;

    const btn = form.querySelector('button');
    btn.disabled = true;

    fetch(BASE + '/api/add_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: postId, content }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const list = document.getElementById('comments-list-' + postId);
            if (list) appendComment(list, data.comment);
            input.value = '';
            // Update comment count button
            const toggleBtn = document.querySelector(`#post-${postId} .comment-toggle-btn span`);
            if (toggleBtn) toggleBtn.textContent = parseInt(toggleBtn.textContent || 0) + 1;
        } else {
            alert(data.error || 'Error posting comment.');
        }
    })
    .catch(() => alert('Network error.'))
    .finally(() => btn.disabled = false);
}

// ── Escape HTML helper ────────────────────────────────────
function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
// ── Real-time feed: new posts + avatar updates (SSE) ────
let feedEventSource = null;

function startFeedSSE(lastPostId) {
    if (feedEventSource) { feedEventSource.close(); feedEventSource = null; }
    const url = `${BASE}/api/feed_stream.php?last_post_id=${lastPostId}`;
    feedEventSource = new EventSource(url);

    feedEventSource.onmessage = function(e) {
        let data;
        try { data = JSON.parse(e.data); } catch { return; }

        if (data.reconnect) {
            if (data.last_post_id != null) lastPostId = data.last_post_id;
            feedEventSource.close();
            startFeedSSE(lastPostId);
            return;
        }

        if (!data.events) return;
        data.events.forEach(ev => {
            if (ev.type === 'post') {
                renderNewPost(ev.post, ev.author, ev.my_id);
                lastPostId = Math.max(lastPostId, ev.post.id);
            } else if (ev.type === 'avatar') {
                handleAvatarUpdate(ev.user_id, ev.filename, ev.updated_at);
            } else if (ev.type === 'delete_post') {
                document.getElementById('post-' + ev.post_id)?.remove();
            }
        });
    };

    feedEventSource.onerror = function() {};
}

function renderNewPost(post, author, myId) {
    const container = document.getElementById('feed-posts');
    if (!container) return;
    if (document.getElementById('post-' + post.id)) return;

    // Remove empty-feed placeholder
    const emptyEl = document.getElementById('feed-empty');
    if (emptyEl) emptyEl.remove();

    const profileUrl = `${BASE}/pages/profile.php?username=${encodeURIComponent(author.username)}`;
    const isOwn      = (post.user_id == myId);
    const ver        = author.updated_at ? new Date(author.updated_at).getTime() : 0;

    const imageHtml   = post.image
        ? `<div class="post-image-wrap"><img src="${BASE}/uploads/${escHtml(post.image)}" alt="post" class="post-image filter-${escHtml(post.filter || 'none')}"></div>`
        : '';

    const captionHtml = post.caption
        ? `<p class="post-caption"><a href="${profileUrl}"><strong>${escHtml(author.username)}</strong></a> ${escHtml(post.caption).replace(/\n/g,'<br>')}</p>`
        : '';

    const canDelete = isOwn || (typeof IS_ADMIN !== 'undefined' && IS_ADMIN);
    const menuHtml = canDelete
        ? `<div class="post-menu"><button class="post-menu-btn" onclick="togglePostMenu(${post.id})">&#8943;</button><div class="post-dropdown" id="pdrop-${post.id}"><button onclick="deletePost(${post.id})">Delete</button></div></div>`
        : '';

    const myAvatar = (typeof MY_AVATAR !== 'undefined' && MY_AVATAR)
        ? `${BASE}/uploads/${MY_AVATAR}?v=${typeof MY_AVATAR_VER !== 'undefined' ? MY_AVATAR_VER : 0}`
        : `${BASE}/assets/images/default_avatar.png`;
    const myUid = (typeof MY_USER_ID !== 'undefined') ? MY_USER_ID : 0;

    const div = document.createElement('div');
    div.className = 'post-card card';
    div.id = 'post-' + post.id;
    div.innerHTML = `
        <div class="post-header">
            <a href="${profileUrl}">
                <img src="${BASE}/uploads/${escHtml(author.profile_picture)}?v=${ver}"
                     class="post-author-avatar" data-user-id="${author.id}"
                     onerror="this.onerror=null;this.src='${BASE}/assets/images/default_avatar.png'">
            </a>
            <div class="post-author-info">
                <a href="${profileUrl}"><strong>${escHtml(author.username)}</strong></a>
                <span class="post-time">just now</span>
            </div>
            ${menuHtml}
        </div>
        ${imageHtml}
        <div class="post-body">
            ${captionHtml}
            <div class="post-actions">
                <button class="like-btn" onclick="toggleLike(${post.id}, this)">
                    <span class="like-icon">♥</span>
                    <span class="like-count">0</span>
                </button>
                <button class="comment-toggle-btn" onclick="toggleComments(${post.id})"><span>0</span></button>
            </div>
            <div class="comments-section" id="comments-${post.id}" style="display:none">
                <div class="comments-list" id="comments-list-${post.id}"></div>
                <form class="comment-form" onsubmit="submitComment(event, ${post.id})">
                    <img src="${myAvatar}" class="comment-avatar" data-user-id="${myUid}"
                         onerror="this.onerror=null;this.src='${BASE}/assets/images/default_avatar.png'">
                    <input type="text" placeholder="Add a comment…" class="comment-input" required>
                    <button type="submit" class="btn btn-sm btn-primary">Post</button>
                </form>
            </div>
        </div>`;

    container.insertBefore(div, container.firstChild);
}

function handleAvatarUpdate(userId, filename, updatedAt) {
    const ver    = updatedAt ? new Date(updatedAt).getTime() : Date.now();
    const newSrc = `${BASE}/uploads/${escHtml(filename)}?v=${ver}`;
    document.querySelectorAll(`img[data-user-id="${userId}"]`).forEach(img => {
        img.src = newSrc;
    });
    if (typeof MY_USER_ID !== 'undefined' && userId == MY_USER_ID) {
        window.MY_AVATAR     = filename;
        window.MY_AVATAR_VER = ver;
    }
}

// Auto-start SSE when on the feed page
if (typeof FEED_LAST_POST_ID !== 'undefined' && FEED_LAST_POST_ID !== null) {
    startFeedSSE(FEED_LAST_POST_ID);
}