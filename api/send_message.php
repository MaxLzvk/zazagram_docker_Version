<?php
// api/send_message.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me   = get_current_user_data();
$data = json_decode(file_get_contents('php://input'), true);

$receiver_id = (int)($data['receiver_id'] ?? 0);
$content     = trim($data['content'] ?? '');

if (!$receiver_id || !$content) {
    json_response(['success' => false, 'error' => 'Receiver and content are required.'], 400);
}

if ($receiver_id === $me['id']) {
    json_response(['success' => false, 'error' => 'Cannot message yourself.'], 400);
}

if (strlen($content) > 1000) {
    json_response(['success' => false, 'error' => 'Message too long (max 1000 chars).'], 400);
}

// Check receiver exists
$users    = db_read('users.json');
$receiver = db_find_one($users, 'id', $receiver_id);
if (!$receiver) json_response(['success' => false, 'error' => 'User not found.'], 404);

$messages = db_read('messages.json');
$new_msg  = [
    'id'          => db_next_id($messages),
    'sender_id'   => $me['id'],
    'receiver_id' => $receiver_id,
    'content'     => $content,
    'is_read'     => false,
    'created_at'  => now(),
];
$messages[] = $new_msg;
db_write('messages.json', $messages);

// Notification
$notifs = db_read('notifications.json');
$notifs[] = [
    'id'             => db_next_id($notifs),
    'user_id'        => $receiver_id,
    'actor_id'       => $me['id'],
    'type'           => 'message',
    'reference_id'   => $new_msg['id'],
    'reference_type' => 'message',
    'message'        => $me['username'] . ' sent you a message',
    'is_read'        => false,
    'created_at'     => now(),
];
db_write('notifications.json', $notifs);

json_response(['success' => true, 'message' => $new_msg]);
