<?php
// ============================================================
// includes/auth.php — Authentication helpers
// ============================================================

require_once __DIR__ . '/db.php';

/**
 * Check if a user is currently logged in.
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Require the user to be logged in. Redirect if not.
 * Also destroys the session if the account no longer exists (e.g. deleted by admin).
 */
function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/pages/login.php');
        exit;
    }
    // If the account was deleted while the user was logged in, clear the session
    $user = get_current_user_data();
    if (!$user) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/pages/login.php?deleted=1');
        exit;
    }
    // If the account is banned, check if a timed ban has expired and auto-unban
    if ($user['is_banned']) {
        $ban_until = $user['ban_until'] ?? null;
        if ($ban_until && strtotime($ban_until) !== false && time() >= strtotime($ban_until)) {
            // Ban expired — lift it automatically
            try {
                db()->prepare('UPDATE users SET is_banned=0, ban_reason=NULL, ban_until=NULL, updated_at=NOW() WHERE id=?')
                   ->execute([$user['id']]);
            } catch (Throwable $e) {}
        } else {
            // Still banned — redirect to ban screen
            header('Location: ' . BASE_URL . '/pages/banned.php');
            exit;
        }
    }
}

/**
 * Require the user to be an admin or superadmin.
 */
function require_admin(): void {
    require_login();
    $user = get_current_user_data();
    if (!$user || !in_array($user['role'], ['admin', 'superadmin'])) {
        header('Location: ' . BASE_URL . '/pages/feed.php');
        exit;
    }
}

/**
 * Require the user to be a superadmin.
 */
function require_superadmin(): void {
    require_login();
    $user = get_current_user_data();
    if (!$user || $user['role'] !== 'superadmin') {
        header('Location: ' . BASE_URL . '/pages/feed.php');
        exit;
    }
}

/**
 * Check if current user is superadmin.
 */
function is_superadmin(): bool {
    $user = get_current_user_data();
    return $user && $user['role'] === 'superadmin';
}

/**
 * Get the currently logged-in user's data.
 */
function get_current_user_data(): ?array {
    if (!is_logged_in()) return null;
    try {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $row['is_banned'] = (bool)$row['is_banned'];
        return $row;
    } catch (Throwable $e) {
        // Fallback to JSON if DB unavailable
        $users = db_read('users.json');
        return db_find_one($users, 'id', $_SESSION['user_id']);
    }
}

/**
 * Get a user by ID.
 */
function get_user_by_id(int $id): ?array {
    $users = db_read('users.json');
    return db_find_one($users, 'id', $id);
}

/**
 * Get a user by username.
 */
function get_user_by_username(string $username): ?array {
    $users = db_read('users.json');
    return db_find_one($users, 'username', $username);
}

/**
 * Count unread notifications for a user.
 */
function unread_notification_count(int $user_id): int {
    $notifs = db_read('notifications.json');
    return count(array_filter($notifs, fn($n) => $n['user_id'] == $user_id && !$n['is_read']));
}

/**
 * Count unread messages for a user.
 */
function unread_message_count(int $user_id): int {
    $messages = db_read('messages.json');
    return count(array_filter($messages, fn($m) => $m['receiver_id'] == $user_id && !$m['is_read']));
}

/**
 * Get a safe public profile (no password).
 */
function safe_user(array $user): array {
    unset($user['password']);
    return $user;
}
