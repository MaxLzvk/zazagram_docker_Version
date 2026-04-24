<?php
// api/get_messages.php — poll for new messages
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me          = get_current_user_data();
$other_id    = (int)($_GET['user_id'] ?? 0);
$last_id     = (int)($_GET['last_id'] ?? 0);

if (!$other_id) json_response(['success' => false, 'error' => 'user_id required.'], 400);

$messages = db_read('messages.json');

// Mark incoming as read
$changed = false;
foreach ($messages as &$m) {
    if ($m['sender_id'] == $other_id && $m['receiver_id'] == $me['id'] && !$m['is_read']) {
        $m['is_read'] = true;
        $changed = true;
    }
}
unset($m);
if ($changed) db_write('messages.json', $messages);

// Return new messages since last_id
$new_msgs = array_values(array_filter($messages, fn($m) =>
    $m['id'] > $last_id &&
    (($m['sender_id'] == $me['id']  && $m['receiver_id'] == $other_id) ||
     ($m['sender_id'] == $other_id  && $m['receiver_id'] == $me['id']))
));

json_response(['success' => true, 'messages' => $new_msgs, 'my_id' => $me['id']]);
