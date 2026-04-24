<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me = get_current_user_data();

if (!isset($_FILES['avatar'])) {
    json_response(['success' => false, 'error' => 'No file uploaded.'], 400);
}

$file = $_FILES['avatar'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    json_response(['success' => false, 'error' => 'Upload error.'], 400);
}

if ($file['size'] > MAX_UPLOAD_SIZE) {
    json_response(['success' => false, 'error' => 'File too large (max 5MB).'], 400);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_EXTENSIONS)) {
    json_response(['success' => false, 'error' => 'Invalid file type.'], 400);
}

// Verify it's actually an image
$info = getimagesize($file['tmp_name']);
if (!$info) {
    json_response(['success' => false, 'error' => 'File is not a valid image.'], 400);
}

$filename = 'avatar_' . $me['id'] . '_' . time() . '.' . $ext;
$dest     = UPLOADS_PATH . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    json_response(['success' => false, 'error' => 'Failed to save file.'], 500);
}

// Delete old avatar if not default
if ($me['profile_picture'] !== 'default_avatar.png') {
    $old = UPLOADS_PATH . '/' . $me['profile_picture'];
    if (file_exists($old)) unlink($old);
}

$users = db_read('users.json');
$users = db_update($users, $me['id'], ['profile_picture' => $filename, 'updated_at' => now()]);
db_write('users.json', $users);

json_response(['success' => true, 'filename' => $filename]);
