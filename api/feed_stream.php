<?php
// api/feed_stream.php — Server-Sent Events for real-time feed updates
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me           = get_current_user_data();
$last_post_id = (int)($_GET['last_post_id'] ?? 0);

// Release session lock so other requests aren't blocked
session_write_close();

set_time_limit(0);
ignore_user_abort(false);

// SSE headers — explicitly disable any compression/buffering
@ini_set('zlib.output_compression', 0);
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-store');
header('X-Accel-Buffering: no');
header('Content-Encoding: none');
header('Connection: keep-alive');

while (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);

// Snapshot of user profile pictures to detect avatar changes
$user_pic_snapshot = [];
foreach (db_read('users.json') as $u) {
    $user_pic_snapshot[$u['id']] = $u['profile_picture'];
}

// Snapshot of all post IDs to detect deletions
$posts = db_read('posts.json');
$post_id_snapshot = array_column($posts, 'id');

$start = time();

while (!connection_aborted() && (time() - $start) < 28) {
    $events = [];
    $posts  = db_read('posts.json');
    $users  = db_read('users.json');

    // Deleted posts
    $current_ids = array_column($posts, 'id');
    foreach (array_diff($post_id_snapshot, $current_ids) as $deleted_id) {
        $events[] = ['type' => 'delete_post', 'post_id' => (int)$deleted_id];
    }
    $post_id_snapshot = $current_ids;

    // New posts
    $new_posts = array_values(array_filter($posts, fn($p) => $p['id'] > $last_post_id));
    if (!empty($new_posts)) {
        usort($new_posts, fn($a, $b) => $a['id'] - $b['id']); // oldest first
        foreach ($new_posts as $p) {
            $author = db_find_one($users, 'id', $p['user_id']);
            if (!$author) continue;
            $last_post_id = max($last_post_id, $p['id']);
            $events[] = [
                'type'   => 'post',
                'post'   => $p,
                'author' => [
                    'id'              => $author['id'],
                    'username'        => $author['username'],
                    'profile_picture' => $author['profile_picture'],
                    'updated_at'      => $author['updated_at'],
                ],
                'my_id'  => $me['id'],
            ];
        }
    }

    // Avatar changes
    foreach ($users as $u) {
        $old_pic = $user_pic_snapshot[$u['id']] ?? null;
        if ($old_pic !== null && $old_pic !== $u['profile_picture']) {
            $events[] = [
                'type'       => 'avatar',
                'user_id'    => $u['id'],
                'filename'   => $u['profile_picture'],
                'updated_at' => $u['updated_at'],
            ];
        }
        $user_pic_snapshot[$u['id']] = $u['profile_picture'];
    }

    if (!empty($events)) {
        echo 'data: ' . json_encode(['events' => $events]) . "\n\n";
    }

    // Always flush a heartbeat comment — this keeps the connection alive,
    // ensures Apache never buffers silently, and lets connection_aborted() work.
    echo ": heartbeat\n\n";
    flush();

    usleep(500000); // poll every 500 ms — matches messages_stream.php
}

// Tell client to reconnect with the updated cursor
echo 'data: ' . json_encode(['reconnect' => true, 'last_post_id' => $last_post_id]) . "\n\n";
flush();
