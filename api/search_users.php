<?php
// api/search_users.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$q = trim($_GET['q'] ?? '');
if (!$q) json_response(['users' => []]);

$users   = db_read('users.json');
$results = [];

foreach ($users as $u) {
    if ($u['is_banned']) continue;
    if (stripos($u['username'], $q) !== false ||
        stripos($u['first_name'], $q) !== false ||
        stripos($u['last_name'], $q) !== false) {
        $results[] = [
            'id'              => $u['id'],
            'username'        => $u['username'],
            'first_name'      => $u['first_name'],
            'last_name'       => $u['last_name'],
            'profile_picture' => $u['profile_picture'],
        ];
    }
    if (count($results) >= 8) break;
}

json_response(['users' => $results]);
