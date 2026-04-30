<?php
// api/typing.php — broadcast typing indicator via WebSocket
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me   = get_current_user_data();
$data = json_decode(file_get_contents('php://input'), true);
$receiver_id = (int)($data['receiver_id'] ?? 0);

if (!$receiver_id) json_response(['success' => false], 400);

session_write_close();

ws_push([
    'type'         => 'typing',
    'to_user_id'   => $receiver_id,
    'from_user_id' => $me['id'],
    'is_typing'    => true,
]);

json_response(['success' => true]);
