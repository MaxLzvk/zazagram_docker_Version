<?php
// api/get_comments.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$post_id = (int)($_GET['post_id'] ?? 0);
if (!$post_id) json_response(['success' => false, 'error' => 'Invalid post ID.'], 400);

$comments = db_read('comments.json');
$users    = db_read('users.json');

$post_comments = db_find_all($comments, 'post_id', $post_id);

$result = [];
foreach ($post_comments as $c) {
    $author = db_find_one($users, 'id', $c['user_id']);
    $result[] = [
        'id'         => $c['id'],
        'username'   => $author['username'] ?? 'unknown',
        'content'    => $c['content'],
        'created_at' => $c['created_at'],
    ];
}

json_response(['success' => true, 'comments' => $result]);
