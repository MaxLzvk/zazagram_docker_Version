<?php
// index.php — Entry point for Zazagram
require_once __DIR__ . '/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/pages/feed.php');
} else {
    header('Location: ' . BASE_URL . '/pages/login.php');
}
exit;
