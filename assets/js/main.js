// ============================================================
// main.js — Global JavaScript for Zazagram
// ============================================================

// ── User dropdown ──────────────────────────────────────────
function toggleUserMenu() {
    const d = document.getElementById('user-dropdown');
    if (d) d.classList.toggle('open');
}

// Close dropdown when clicking outside
document.addEventListener('click', (e) => {
    const wrap = document.querySelector('.nav-avatar-wrap');
    if (wrap && !wrap.contains(e.target)) {
        const d = document.getElementById('user-dropdown');
        if (d) d.classList.remove('open');
    }
    // Close post menus
    if (!e.target.closest('.post-menu')) {
        document.querySelectorAll('.post-dropdown').forEach(el => el.style.display = 'none');
    }
});

// ── Flash messages auto-dismiss ───────────────────────────
document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity 0.5s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    }, 4000);
});
