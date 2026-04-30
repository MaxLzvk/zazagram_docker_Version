<?php
// api/delete_message.php — delete a sent message (sender only)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me    = get_current_user_data();
$input = json_decode(file_get_contents('php://input'), true);
$message_id = (int)($input['message_id'] ?? 0);

if (!$message_id) json_response(['success' => false, 'error' => 'message_id required.'], 400);

$messages = db_read('messages.json');

$idx = null;
foreach ($messages as $i => $m) {
    if ((int)$m['id'] === $message_id) { $idx = $i; break; }
}

if ($idx === null) json_response(['success' => false, 'error' => 'Message not found.'], 404);
if ((int)$messages[$idx]['sender_id'] !== $me['id']) json_response(['success' => false, 'error' => 'Unauthorized.'], 403);

$deleted_msg = $messages[$idx];
$other_id    = (int)$deleted_msg['receiver_id'];

array_splice($messages, $idx, 1);
db_write('messages.json', $messages);

ws_push(['type' => 'delete_message', 'to_user_id' => $me['id'], 'message_id' => $message_id]);
ws_push(['type' => 'delete_message', 'to_user_id' => $other_id, 'message_id' => $message_id]);

json_response(['success' => true]);
