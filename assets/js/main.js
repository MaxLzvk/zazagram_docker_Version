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

// ── Global WebSocket manager ──────────────────────────────
(function initGlobalWS() {
    if (!window.ZZG) return; // not logged in

    const ZZG = window.ZZG;
    let ws, reconnectTimer;
    window._zzgWS = { send: () => {} }; // placeholder until connected

    function connect() {
        clearTimeout(reconnectTimer);
        ws = new WebSocket(ZZG.wsUrl);

        ws.onopen = () => {
            ws.send(JSON.stringify({
                type:     'auth',
                user_id:  ZZG.userId,
                username: ZZG.username,
                avatar:   ZZG.avatar,
            }));
            window._zzgWS = ws;
        };

        ws.onmessage = (e) => {
            let msg;
            try { msg = JSON.parse(e.data); } catch { return; }
            handleWSMessage(msg);
        };

        ws.onclose = () => {
            window._zzgWS = { send: () => {} };
            reconnectTimer = setTimeout(connect, 4000);
        };

        ws.onerror = () => ws.close();
    }

    function handleWSMessage(msg) {
        switch (msg.type) {

            // ── Nav badge updates ─────────────────────────
            case 'badge_refresh':
                fetch(ZZG.baseUrl + '/api/get_badges.php')
                    .then(r => r.json())
                    .then(d => {
                        updateBadge('nav-notif-badge', d.notif_count);
                        updateBadge('nav-msg-badge',   d.msg_count);
                    }).catch(() => {});
                break;

            // ── Online list (feed sidebar) ────────────────
            case 'online_list':
                window.dispatchEvent(new CustomEvent('zzg:online_list', { detail: msg }));
                break;

            // ── New post (feed) ───────────────────────────
            case 'new_post':
                window.dispatchEvent(new CustomEvent('zzg:new_post', { detail: msg }));
                break;

            // ── Post deleted ──────────────────────────────
            case 'delete_post': {
                // Remove from DOM on any page that shows posts
                const el = document.getElementById('post-' + msg.post_id);
                if (el) el.remove();
                window.dispatchEvent(new CustomEvent('zzg:delete_post', { detail: msg }));
                break;
            }

            // ── Avatar updated ────────────────────────────
            case 'avatar': {
                const ver = new Date(msg.updated_at).getTime();
                document.querySelectorAll(`img[data-user-id="${msg.user_id}"]`).forEach(img => {
                    img.src = ZZG.baseUrl + '/uploads/' + msg.filename + '?v=' + ver;
                });
                // Update global ZZG.avatar if it's our own avatar
                if (msg.user_id === ZZG.userId) {
                    ZZG.avatar    = msg.filename;
                    ZZG.avatarVer = ver;
                }
                window.dispatchEvent(new CustomEvent('zzg:avatar', { detail: msg }));
                break;
            }

            // ── New message ───────────────────────────────
            case 'new_message':
                updateBadge('nav-msg-badge', null, +1);
                window.dispatchEvent(new CustomEvent('zzg:new_message', { detail: msg }));
                break;

            // ── Message deleted ───────────────────────────
            case 'delete_message':
                window.dispatchEvent(new CustomEvent('zzg:delete_message', { detail: msg }));
                break;

            // ── Typing indicator ──────────────────────────
            case 'typing':
                window.dispatchEvent(new CustomEvent('zzg:typing', { detail: msg }));
                break;

            // ── Force logout (ban/kick) ───────────────────
            case 'force_logout':
                window.location.href = (window.ZZG?.baseUrl || '') + '/pages/banned.php';
                break;
        }
    }

    connect();
})();

// ── Nav badge helper ───────────────────────────────────────
function updateBadge(id, value, delta) {
    const el = document.getElementById(id);
    if (!el) return;
    let n = value !== null && value !== undefined
        ? Number(value)
        : (parseInt(el.textContent || 0) + (delta || 0));
    if (n < 0) n = 0;
    el.textContent = n;
    el.style.display = n > 0 ? '' : 'none';
}

// ── Particle canvas background ────────────────────────────
(function initParticles() {
    const canvas = document.createElement('canvas');
    canvas.id = 'bg-canvas';
    document.body.prepend(canvas);
    const ctx = canvas.getContext('2d');

    let W, H, particles = [];

    const COLORS = [
        'rgba(232,98,10,',
        'rgba(212,160,23,',
        'rgba(255,130,40,',
        'rgba(139,92,246,',
        'rgba(6,182,212,',
    ];

    function resize() {
        W = canvas.width  = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }

    function randomParticle(fromBottom = false) {
        const r = Math.random() * 1.8 + 0.4;
        return {
            x:     Math.random() * W,
            y:     fromBottom ? H + 10 : Math.random() * H,
            r,
            color: COLORS[Math.floor(Math.random() * COLORS.length)],
            alpha: Math.random() * 0.35 + 0.05,
            speed: Math.random() * 0.35 + 0.1,
            drift: (Math.random() - 0.5) * 0.25,
            wobble: Math.random() * Math.PI * 2,
            wobbleSpeed: Math.random() * 0.008 + 0.003,
        };
    }

    function init() {
        resize();
        particles = Array.from({ length: 55 }, () => randomParticle());
    }

    function draw() {
        ctx.clearRect(0, 0, W, H);
        for (const p of particles) {
            p.wobble  += p.wobbleSpeed;
            p.y       -= p.speed;
            p.x       += p.drift + Math.sin(p.wobble) * 0.3;

            if (p.y < -10) {
                Object.assign(p, randomParticle(true));
            }

            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fillStyle = p.color + p.alpha + ')';
            ctx.fill();
        }
        requestAnimationFrame(draw);
    }

    window.addEventListener('resize', resize);
    init();
    draw();
})();

// ── Scroll-reveal via IntersectionObserver ────────────────
(function initReveal() {
    const targets = document.querySelectorAll(
        '.post-card, .card, .feed-sidebar .card, .left-sidebar .card'
    );
    if (!targets.length) return;

    targets.forEach((el, i) => {
        el.classList.add('reveal');
        // stagger first 4 items
        const delayClass = ['reveal-d1','reveal-d2','reveal-d3','reveal-d4'][i % 4];
        el.classList.add(delayClass);
    });

    const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.07, rootMargin: '0px 0px -40px 0px' });

    targets.forEach(el => io.observe(el));
})();

// ── Like button pop animation ─────────────────────────────
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.like-btn');
    if (!btn) return;
    btn.classList.remove('pop');
    void btn.offsetWidth; // reflow to restart
    btn.classList.add('pop');
    btn.addEventListener('animationend', () => btn.classList.remove('pop'), { once: true });
});

