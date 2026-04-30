<?php
// ============================================================
// includes/header.php — Top navigation + HTML head
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';

$current_user = get_current_user_data();

// ── Log visitor IP ──
try {
    $vis_ip = $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['HTTP_X_REAL_IP']
        ?? $_SERVER['REMOTE_ADDR']
        ?? 'unknown';
    $vis_ip   = trim(explode(',', $vis_ip)[0]);
    $vis_page = substr(($_SERVER['REQUEST_URI'] ?? '/'), 0, 512);
    $vis_ua   = substr(($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512);
    $vis_uid  = $current_user['id'] ?? null;
    $vis_user = $current_user['username'] ?? null;
    $pdo = db();

    // Check if IP is blocked (skip check for admin pages to avoid lockout)
    $is_admin_page = strpos($vis_page, 'admin.php') !== false;
    $is_admin_user = in_array($current_user['role'] ?? '', ['admin', 'superadmin']);
    if (!$is_admin_page && !$is_admin_user) {
        $block_check = $pdo->prepare('SELECT id FROM blocked_ips WHERE ip = ? LIMIT 1');
        $block_check->execute([$vis_ip]);
        if ($block_check->fetch()) {
            http_response_code(403);
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Access Denied</title>
            <style>body{background:#080810;color:#fff;font-family:Inter,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;flex-direction:column;gap:1rem}
            h1{font-size:3rem;color:#ef4444}p{color:#888;font-size:1.1rem}</style></head>
            <body><h1>🚫 403</h1><p>Your IP address has been blocked from accessing this site.</p></body></html>';
            exit;
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO visitor_logs (ip, user_id, username, page, user_agent, visited_at)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([$vis_ip, $vis_uid, $vis_user, $vis_page, $vis_ua]);
} catch (Throwable $e) { /* silent fail */ }
$notif_count  = $current_user ? unread_notification_count($current_user['id']) : 0;
$msg_count    = $current_user ? unread_message_count($current_user['id']) : 0;

$page_title = $page_title ?? 'Zazagram';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title><?= htmlspecialchars($page_title) ?> — Zazagram</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/filters.css">
</head>
<body class="<?= htmlspecialchars($body_class ?? '') ?>">

<?php if ($current_user): ?>
<nav class="navbar">
    <div class="nav-inner">
        <a href="<?= BASE_URL ?>/pages/feed.php" class="nav-logo">
            <span class="logo-text">Zazagram</span>
        </a>

        <div class="mobile-menu-row mobile-only" style="margin-left: auto;">
            <button type="button" class="icon-btn menu-toggle" title="Menu" onclick="toggleMobileNav()">☰</button>
        </div>

        <div class="desktop-only">
            <div class="nav-search">
                <input type="text" id="global-search" placeholder="Search users…" autocomplete="off">
                <div id="search-results" class="search-dropdown"></div>
            </div>

            <div class="nav-actions">
                <a href="<?= BASE_URL ?>/pages/feed.php" class="nav-btn" title="Feed">Feed</a>
                <a href="<?= BASE_URL ?>/pages/create_post.php" class="nav-btn nav-btn-cta" title="New Post">+ Post</a>

                <a href="<?= BASE_URL ?>/pages/messages.php" class="nav-btn nav-notif" title="Messages">
                    Msgs
                    <?php if ($msg_count > 0): ?>
                        <span class="badge"><?= $msg_count ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?= BASE_URL ?>/pages/notifications.php" class="nav-btn nav-notif" title="Notifications">
                    Notifs
                    <?php if ($notif_count > 0): ?>
                        <span class="badge"><?= $notif_count ?></span>
                    <?php endif; ?>
                </a>

                <div class="nav-avatar-wrap">
                    <img
                        src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($current_user['profile_picture']) ?>?v=<?= strtotime($current_user['updated_at']) ?>"
                        class="nav-avatar"
                        alt="me"
                        data-user-id="<?= $current_user['id'] ?>"
                        onclick="toggleUserMenu()"
                        onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/default_avatar.png'"
                    >
                    <div class="user-dropdown" id="user-dropdown">
                        <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($current_user['username']) ?>">My Profile</a>
                        <a href="<?= BASE_URL ?>/pages/settings.php">Settings</a>
                        <?php if (in_array($current_user['role'], ['admin', 'superadmin'])): ?>
                            <a href="<?= BASE_URL ?>/pages/admin.php">Admin Panel</a>
                        <?php endif; ?>
                        <hr>
                        <a href="<?= BASE_URL ?>/api/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="nav-panel mobile-only" id="mobile-nav-panel">
            <div class="nav-scroll">
                <a href="<?= BASE_URL ?>/pages/feed.php" class="nav-btn" title="Feed">Feed</a>
                <a href="<?= BASE_URL ?>/pages/create_post.php" class="nav-btn" title="New Post">+ Post</a>
                <a href="<?= BASE_URL ?>/pages/messages.php" class="nav-btn nav-notif" title="Messages">Msgs</a>
                <a href="<?= BASE_URL ?>/pages/notifications.php" class="nav-btn nav-notif" title="Notifications">Notifs</a>
                <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($current_user['username']) ?>" class="nav-btn" title="Profile">Profile</a>
            </div>
        </div>
    </div>
</nav>
<script>
function toggleMobileNav() {
    var panel = document.getElementById('mobile-nav-panel');
    panel.classList.toggle('open');
}
document.addEventListener('click', function(event) {
    var panel = document.getElementById('mobile-nav-panel');
    if (!panel || !panel.classList.contains('open')) return;
    if (!event.target.closest('.mobile-menu-row') && !event.target.closest('.nav-panel')) {
        panel.classList.remove('open');
    }
});
</script>
<?php endif; ?>

<main class="main-content">
