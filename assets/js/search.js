// ============================================================
// search.js — Global user search + new message search
// ============================================================

const BASE_URL_JS = (function() {
    const meta = document.querySelector('meta[name="base-url"]');
    return meta ? meta.content : window.location.origin + '/Zazagram_Website';
})();

// ── Global navbar search ──────────────────────────────────
const globalSearch  = document.getElementById('global-search');
const searchResults = document.getElementById('search-results');

if (globalSearch && searchResults) {
    let debounceTimer;
    globalSearch.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = globalSearch.value.trim();
        if (!q) { searchResults.innerHTML = ''; return; }
        debounceTimer = setTimeout(() => fetchUsers(q, searchResults, 'profile'), 250);
    });
    document.addEventListener('click', e => {
        if (!globalSearch.contains(e.target)) searchResults.innerHTML = '';
    });
}

// ── New conversation search in messages ──────────────────
const newConvSearch  = document.getElementById('new-conv-search');
const newConvResults = document.getElementById('new-conv-results');

if (newConvSearch && newConvResults) {
    let debounceTimer2;
    newConvSearch.addEventListener('input', () => {
        clearTimeout(debounceTimer2);
        const q = newConvSearch.value.trim();
        if (!q) { newConvResults.innerHTML = ''; return; }
        debounceTimer2 = setTimeout(() => fetchUsers(q, newConvResults, 'message'), 250);
    });
    document.addEventListener('click', e => {
        if (!newConvSearch.contains(e.target)) newConvResults.innerHTML = '';
    });
}

// ── Core search function ──────────────────────────────────
function fetchUsers(query, container, mode) {
    fetch(BASE_URL_JS + '/api/search_users.php?q=' + encodeURIComponent(query))
    .then(r => r.json())
    .then(data => {
        container.innerHTML = '';
        if (!data.users || data.users.length === 0) {
            container.innerHTML = '<div class="search-item muted">No results found</div>';
            return;
        }
        data.users.forEach(u => {
            const item = document.createElement('div');
            item.className = 'search-item';
            item.innerHTML = `
                <img src="${BASE_URL_JS}/uploads/${u.profile_picture}"
                     onerror="this.src='${BASE_URL_JS}/assets/images/default_avatar.png'">
                <div>
                    <strong>${escHtml2(u.username)}</strong>
                    <p style="font-size:.8rem;color:#8e8e8e">${escHtml2(u.first_name)} ${escHtml2(u.last_name)}</p>
                </div>`;
            item.addEventListener('click', () => {
                if (mode === 'profile') {
                    window.location.href = BASE_URL_JS + '/pages/profile.php?username=' + encodeURIComponent(u.username);
                } else {
                    window.location.href = BASE_URL_JS + '/pages/messages.php?user=' + u.id;
                }
                container.innerHTML = '';
            });
            container.appendChild(item);
        });
    })
    .catch(() => {});
}

function escHtml2(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
