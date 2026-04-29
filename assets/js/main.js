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

