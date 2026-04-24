<?php
// api/delete_post.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me   = get_current_user_data();
$data = json_decode(file_get_contents('php://input'), true);
$post_id = (int)($data['post_id'] ?? 0);

if (!$post_id) json_response(['success' => false, 'error' => 'Invalid post ID.'], 400);

$posts = db_read('posts.json');
$post  = db_find_one($posts, 'id', $post_id);

if (!$post) json_response(['success' => false, 'error' => 'Post not found.'], 404);
if ($post['user_id'] !== $me['id'] && $me['role'] !== 'admin') {
    json_response(['success' => false, 'error' => 'Unauthorized.'], 403);
}

// Delete image file
if ($post['image']) {
    $img = UPLOADS_PATH . '/' . $post['image'];
    if (file_exists($img)) unlink($img);
}

// Remove post
$posts = db_delete($posts, $post_id);
db_write('posts.json', $posts);

// Remove related likes and comments
$likes = db_read('likes.json');
$likes = array_values(array_filter($likes, fn($l) => $l['post_id'] !== $post_id));
db_write('likes.json', $likes);

$comments = db_read('comments.json');
$comments = array_values(array_filter($comments, fn($c) => $c['post_id'] !== $post_id));
db_write('comments.json', $comments);

json_response(['success' => true]);
