<?php
// api/add_comment.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me      = get_current_user_data();
$data    = json_decode(file_get_contents('php://input'), true);
$post_id = (int)($data['post_id'] ?? 0);
$content = trim($data['content'] ?? '');

if (!$post_id || !$content) {
    json_response(['success' => false, 'error' => 'Post ID and content are required.'], 400);
}

if (strlen($content) > 500) {
    json_response(['success' => false, 'error' => 'Comment too long (max 500 chars).'], 400);
}

$posts = db_read('posts.json');
$post  = db_find_one($posts, 'id', $post_id);
if (!$post) json_response(['success' => false, 'error' => 'Post not found.'], 404);

$comments   = db_read('comments.json');
$new_comment = [
    'id'         => db_next_id($comments),
    'post_id'    => $post_id,
    'user_id'    => $me['id'],
    'content'    => $content,
    'created_at' => now(),
];
$comments[] = $new_comment;
db_write('comments.json', $comments);

// Notify post author
if ($post['user_id'] !== $me['id']) {
    notify_user($post['user_id'], $me['id'], 'comment', $post_id, 'post', $me['username'] . ' commented on your post');
    // Push badge refresh to post author
    ws_push(['type' => 'badge_refresh', 'to_user_id' => $post['user_id']]);
}

json_response([
    'success'  => true,
    'comment'  => [
        'id'         => $new_comment['id'],
        'username'   => $me['username'],
        'content'    => $content,
        'created_at' => $new_comment['created_at'],
    ],
]);
