<?php
// api/messages_stream.php — Server-Sent Events endpoint for real-time messages
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me       = get_current_user_data();
$other_id = (int)($_GET['user_id'] ?? 0);
$last_id  = (int)($_GET['last_id'] ?? 0);

if (!$other_id) { http_response_code(400); exit; }

// Release the PHP session lock immediately so send_message.php and other
// requests from this user are not blocked for the lifetime of the stream.
session_write_close();

// Allow the script to run longer than the default PHP time limit.
set_time_limit(0);
ignore_user_abort(false); // let connection_aborted() work correctly

// SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // disable nginx/proxy buffering
header('Connection: keep-alive');

// Disable all PHP output buffering so each flush() reaches the client immediately
while (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);

$start       = time();
$prev_typing = null; // track last-sent typing state to only emit on changes

// Snapshot of conversation message IDs to detect deletions
$_init_msgs = db_read('messages.json');
$msg_id_snapshot = array_column(array_values(array_filter($_init_msgs, fn($m) =>
    ($m['sender_id'] == $me['id']   && $m['receiver_id'] == $other_id) ||
    ($m['sender_id'] == $other_id   && $m['receiver_id'] == $me['id'])
)), 'id');
unset($_init_msgs);

while (!connection_aborted() && (time() - $start) < 28) {
    $messages = db_read('messages.json');

    // Mark incoming messages as read
    $changed = false;
    foreach ($messages as &$m) {
        if ($m['sender_id'] == $other_id && $m['receiver_id'] == $me['id'] && !$m['is_read']) {
            $m['is_read'] = true;
            $changed = true;
        }
    }
    unset($m);
    if ($changed) db_write('messages.json', $messages);

    // Collect messages newer than last_id for this conversation
    $new_msgs = array_values(array_filter($messages, fn($m) =>
        $m['id'] > $last_id &&
        (($m['sender_id'] == $me['id']   && $m['receiver_id'] == $other_id) ||
         ($m['sender_id'] == $other_id   && $m['receiver_id'] == $me['id']))
    ));

    if (!empty($new_msgs)) {
        foreach ($new_msgs as $msg) {
            if ($msg['id'] > $last_id) $last_id = $msg['id'];
        }
    }

    // Detect deleted messages
    $current_conv_ids = array_column(array_values(array_filter($messages, fn($m) =>
        ($m['sender_id'] == $me['id']   && $m['receiver_id'] == $other_id) ||
        ($m['sender_id'] == $other_id   && $m['receiver_id'] == $me['id'])
    )), 'id');
    $deleted_ids = array_values(array_diff($msg_id_snapshot, $current_conv_ids));
    $msg_id_snapshot = $current_conv_ids;

    // Check if the other user is currently typing to me (timestamp within last 3 seconds)
    $typing_file = sys_get_temp_dir() . '/zzg_typing_' . $other_id . '_' . $me['id'] . '.txt';
    $is_typing   = false;
    if (file_exists($typing_file)) {
        $ts        = (int)file_get_contents($typing_file);
        $is_typing = (time() - $ts) < 3;
    }

    // Emit if there are new messages, deletions, OR if typing state just changed
    if (!empty($new_msgs) || !empty($deleted_ids) || $is_typing !== $prev_typing) {
        echo 'data: ' . json_encode([
            'messages'  => $new_msgs,
            'deletions' => $deleted_ids,
            'my_id'     => $me['id'],
            'typing'    => $is_typing,
        ]) . "\n\n";
        flush();
        $prev_typing = $is_typing;
    }

    usleep(500000); // poll every 500 ms

    // Always flush a heartbeat to keep connection alive and prevent buffering
    echo ": heartbeat\n\n";
    flush();
}

// Stream window expired — tell the client to reconnect with the updated cursor
echo 'data: ' . json_encode(['reconnect' => true, 'last_id' => $last_id]) . "\n\n";
flush();
