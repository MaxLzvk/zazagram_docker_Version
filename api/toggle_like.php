<?php
// api/toggle_like.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me   = get_current_user_data();
$data = json_decode(file_get_contents('php://input'), true);
$post_id = (int)($data['post_id'] ?? 0);

if (!$post_id) json_response(['success' => false, 'error' => 'Invalid post ID.'], 400);

$posts = db_read('posts.json');
if (!db_find_one($posts, 'id', $post_id)) {
    json_response(['success' => false, 'error' => 'Post not found.'], 404);
}

$likes      = db_read('likes.json');
$existing   = null;
$existing_i = null;

foreach ($likes as $i => $like) {
    if ($like['post_id'] == $post_id && $like['user_id'] == $me['id']) {
        $existing   = $like;
        $existing_i = $i;
        break;
    }
}

if ($existing) {
    // Unlike
    array_splice($likes, $existing_i, 1);
    $liked = false;
} else {
    // Like
    $likes[] = [
        'id'         => db_next_id($likes),
        'user_id'    => $me['id'],
        'post_id'    => $post_id,
        'created_at' => now(),
    ];
    $liked = true;

    // Notify post author (don't notify self)
    $post = db_find_one($posts, 'id', $post_id);
    if ($post && $post['user_id'] !== $me['id']) {
        $notifs = db_read('notifications.json');
        $notifs[] = [
            'id'             => db_next_id($notifs),
            'user_id'        => $post['user_id'],
            'actor_id'       => $me['id'],
            'type'           => 'like',
            'reference_id'   => $post_id,
            'reference_type' => 'post',
            'message'        => $me['username'] . ' liked your post',
            'is_read'        => false,
            'created_at'     => now(),
        ];
        db_write('notifications.json', $notifs);
    }
}

db_write('likes.json', $likes);

$count = count(db_find_all($likes, 'post_id', $post_id));
json_response(['success' => true, 'liked' => $liked, 'count' => $count]);
