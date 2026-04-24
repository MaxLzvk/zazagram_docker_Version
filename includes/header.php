<?php
// ============================================================
// includes/header.php — Top navigation + HTML head
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

$current_user = get_current_user_data();
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
                <a href="<?= BASE_URL ?>/pages/create_post.php" class="nav-btn" title="New Post">+ Post</a>

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
                        <?php if ($current_user['role'] === 'admin'): ?>
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
