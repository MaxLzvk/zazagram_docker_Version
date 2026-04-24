<?php
// ============================================================
// config.php — Global configuration for Zazagram
// ============================================================

define('ROOT_PATH',    __DIR__);
define('DATA_PATH',    ROOT_PATH . '/data');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Database settings are read from Docker environment variables in docker-compose.yml.
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'zazagram');
define('DB_USER', getenv('DB_USER') ?: 'zazagram');
define('DB_PASS', getenv('DB_PASS') ?: 'zazagram');

// Dynamically build BASE_URL so it works on any host/port
if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Derive the project subdirectory from this file's location under the web root
    $doc_root   = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
    $script_dir = rtrim(str_replace('\\', '/', __DIR__), '/');
    $sub_path   = str_replace($doc_root, '', $script_dir);
    define('BASE_URL', $scheme . '://' . $host . $sub_path);
}

// Session lifetime (seconds)
define('SESSION_LIFETIME', 86400);

// Max upload size (bytes) — 5MB
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

// Allowed image extensions
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Available post filters
define('POST_FILTERS', ['none', 'warm', 'cool', 'mono', 'vintage', 'fade', 'vivid']);

// Start session globally
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(SESSION_LIFETIME);
    session_start();
}
