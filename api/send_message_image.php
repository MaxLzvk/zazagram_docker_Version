<?php
// api/send_message_image.php — send an image/file in a DM
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me          = get_current_user_data();
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$content     = trim($_POST['content'] ?? '');

if (!$receiver_id) {
    json_response(['success' => false, 'error' => 'receiver_id required.'], 400);
}
if ($receiver_id === $me['id']) {
    json_response(['success' => false, 'error' => 'Cannot message yourself.'], 400);
}

$users    = db_read('users.json');
$receiver = db_find_one($users, 'id', $receiver_id);
if (!$receiver) json_response(['success' => false, 'error' => 'User not found.'], 404);

// Handle file upload
$image_name = '';
if (!empty($_FILES['image']['name'])) {
    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        json_response(['success' => false, 'error' => 'Upload error.'], 400);
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        json_response(['success' => false, 'error' => 'File too large (max 5MB).'], 400);
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = array_merge(ALLOWED_EXTENSIONS, ['mp4', 'webm', 'mov', 'gif']);
    if (!in_array($ext, $allowed)) {
        json_response(['success' => false, 'error' => 'Invalid file type.'], 400);
    }
    $image_name = 'msg_' . $me['id'] . '_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOADS_PATH . '/' . $image_name)) {
        json_response(['success' => false, 'error' => 'Failed to save file.'], 500);
    }
}

if (!$content && !$image_name) {
    json_response(['success' => false, 'error' => 'Message or image required.'], 400);
}

$messages = db_read('messages.json');
$new_msg  = [
    'id'          => db_next_id($messages),
    'sender_id'   => $me['id'],
    'receiver_id' => $receiver_id,
    'content'     => $content,
    'image'       => $image_name,
    'is_read'     => false,
    'created_at'  => now(),
];
$messages[] = $new_msg;
db_write('messages.json', $messages);

// Notification
notify_user($receiver_id, $me['id'], 'message', $new_msg['id'], 'message', $me['username'] . ' sent you a message');

// Push via WebSocket
ws_push([
    'type'       => 'new_message',
    'to_user_id' => $receiver_id,
    'message'    => $new_msg,
    'from'       => ['id' => $me['id'], 'username' => $me['username'], 'avatar' => $me['profile_picture']],
]);
ws_push(['type' => 'badge_refresh', 'to_user_id' => $receiver_id]);

json_response(['success' => true, 'message' => $new_msg]);
