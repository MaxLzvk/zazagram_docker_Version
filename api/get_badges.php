<?php
// api/get_badges.php — return unread notification + message counts for the current user
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me = get_current_user_data();
session_write_close();

json_response([
    'notif_count' => unread_notification_count($me['id']),
    'msg_count'   => unread_message_count($me['id']),
]);
