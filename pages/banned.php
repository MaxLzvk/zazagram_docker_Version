<?php
// ============================================================
// pages/banned.php — Ban screen (shown to banned users)
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) session_start();

$user = null;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['is_banned'] = (bool)$row['is_banned'];
            $user = $row;
        }
    } catch (Throwable $e) {}
}

// If not banned / not logged in, redirect appropriately
if (!$user) {
    header('Location: ' . BASE_URL . '/pages/login.php'); exit;
}
if (!$user['is_banned']) {
    header('Location: ' . BASE_URL . '/pages/feed.php'); exit;
}

$ban_reason = $user['ban_reason'] ?? '';
$ban_until  = $user['ban_until'] ?? null;
$username   = htmlspecialchars($user['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspended — Zazagram</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #080810;
            color: #f0f0f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        /* Ambient background */
        body::before {
            content: '';
            position: fixed;
            top: -20%;
            left: -20%;
            width: 60%;
            height: 60%;
            background: radial-gradient(circle, rgba(239,68,68,.18) 0%, transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -20%;
            right: -20%;
            width: 60%;
            height: 60%;
            background: radial-gradient(circle, rgba(139,92,246,.12) 0%, transparent 70%);
            pointer-events: none;
        }
        .ban-card {
            background: #12121c;
            border: 1px solid rgba(239,68,68,.35);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            max-width: 540px;
            width: 100%;
            text-align: center;
            position: relative;
            box-shadow: 0 0 60px rgba(239,68,68,.15), 0 20px 60px rgba(0,0,0,.8);
        }
        .ban-icon {
            width: 72px;
            height: 72px;
            background: rgba(239,68,68,.12);
            border: 2px solid rgba(239,68,68,.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .ban-icon svg { color: #ef4444; }
        .ban-title {
            font-size: 1.7rem;
            font-weight: 900;
            color: #ef4444;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }
        .ban-subtitle {
            font-size: 1rem;
            color: #8892aa;
            margin-bottom: 2rem;
        }
        .ban-user {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 999px;
            padding: 0.35rem 1rem;
            font-size: 0.9rem;
            font-weight: 700;
            color: #f0f0f8;
            margin-bottom: 2rem;
        }
        .ban-reason-box {
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.25);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            text-align: left;
            margin-bottom: 2rem;
        }
        .ban-reason-label {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #ef4444;
            margin-bottom: 0.5rem;
        }
        .ban-reason-text {
            font-size: 0.95rem;
            color: #b8bdd6;
            line-height: 1.6;
        }
        .ban-appeal {
            font-size: 0.82rem;
            color: #565f7a;
            margin-bottom: 2rem;
            line-height: 1.5;
        }
        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(239,68,68,.15);
            border: 1px solid rgba(239,68,68,.45);
            color: #ef4444;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all .18s ease;
        }
        .btn-logout:hover {
            background: rgba(239,68,68,.28);
            border-color: rgba(239,68,68,.7);
            transform: translateY(-1px);
        }
        .zzg-brand {
            position: absolute;
            top: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.8rem;
            font-weight: 900;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: rgba(255,255,255,.2);
        }
    </style>
</head>
<body>
    <div class="ban-card">
        <div class="zzg-brand">Zazagram</div>

        <div class="ban-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
        </div>

        <h1 class="ban-title">Account Suspended</h1>
        <p class="ban-subtitle">Your Zazagram account has been suspended by a moderator.</p>

        <div class="ban-user">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"/>
            </svg>
            @<?= $username ?>
        </div>

        <div class="ban-reason-box">
            <div class="ban-reason-label">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:-0.1em;margin-right:0.3em"><path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"/></svg>
                Reason for suspension
            </div>
            <p class="ban-reason-text">
                <?= $ban_reason ? nl2br(htmlspecialchars($ban_reason)) : 'No specific reason was provided by the moderator.' ?>
            </p>
        </div>

        <p class="ban-appeal">
            If you believe this is a mistake, please contact a platform administrator to appeal this decision.
        </p>

        <?php if ($ban_until): ?>
        <div style="background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.3);border-radius:10px;padding:0.85rem 1.2rem;margin-bottom:1.5rem;font-size:0.88rem;color:#c4b5fd;display:flex;align-items:center;gap:0.5rem">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>This ban expires on <strong><?= htmlspecialchars(date('M j, Y \a\t H:i \U\T\C', strtotime($ban_until))) ?></strong></span>
        </div>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/api/logout.php" class="btn-logout">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
            </svg>
            Log Out
        </a>
    </div>
</body>
</html>
