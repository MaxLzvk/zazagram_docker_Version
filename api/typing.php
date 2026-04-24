<?php
// api/typing.php — record that the current user is typing to another user
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me   = get_current_user_data();
$data = json_decode(file_get_contents('php://input'), true);
$receiver_id = (int)($data['receiver_id'] ?? 0);

if (!$receiver_id) json_response(['success' => false], 400);

// Close the session lock immediately — we don't need it for a file write
session_write_close();

// Write the current timestamp to a per-pair temp file.
// The SSE stream reads this file to detect typing activity.
$file = sys_get_temp_dir() . '/zzg_typing_' . $me['id'] . '_' . $receiver_id . '.txt';
file_put_contents($file, time());

json_response(['success' => true]);
