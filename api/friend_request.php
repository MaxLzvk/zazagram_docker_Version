<?php
// api/friend_request.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me   = get_current_user_data();
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

$friends = db_read('friends.json');

switch ($action) {

    case 'send':
        $receiver_id = (int)($data['receiver_id'] ?? 0);
        if (!$receiver_id || $receiver_id === $me['id']) {
            json_response(['success' => false, 'error' => 'Invalid request.'], 400);
        }
        // Check no existing relation
        foreach ($friends as $f) {
            if (($f['requester_id'] == $me['id'] && $f['receiver_id'] == $receiver_id) ||
                ($f['receiver_id'] == $me['id'] && $f['requester_id'] == $receiver_id)) {
                json_response(['success' => false, 'error' => 'Request already exists.'], 400);
            }
        }
        $new_req = [
            'id'           => db_next_id($friends),
            'requester_id' => $me['id'],
            'receiver_id'  => $receiver_id,
            'status'       => 'pending',
            'created_at'   => now(),
            'updated_at'   => now(),
        ];
        $friends[] = $new_req;
        db_write('friends.json', $friends);

        // Notify receiver
        $notifs = db_read('notifications.json');
        $notifs[] = [
            'id'             => db_next_id($notifs),
            'user_id'        => $receiver_id,
            'actor_id'       => $me['id'],
            'type'           => 'friend_request',
            'reference_id'   => $new_req['id'],
            'reference_type' => 'friend',
            'message'        => $me['username'] . ' sent you a friend request',
            'is_read'        => false,
            'created_at'     => now(),
        ];
        db_write('notifications.json', $notifs);
        json_response(['success' => true]);

    case 'accept':
        $requester_id = (int)($data['requester_id'] ?? 0);
        foreach ($friends as &$f) {
            if ($f['requester_id'] == $requester_id && $f['receiver_id'] == $me['id'] && $f['status'] === 'pending') {
                $f['status']     = 'accepted';
                $f['updated_at'] = now();
                db_write('friends.json', $friends);
                json_response(['success' => true]);
            }
        }
        json_response(['success' => false, 'error' => 'Request not found.'], 404);

    case 'decline':
        $requester_id = (int)($data['requester_id'] ?? 0);
        $new_friends = array_values(array_filter($friends, fn($f) =>
            !($f['requester_id'] == $requester_id && $f['receiver_id'] == $me['id'] && $f['status'] === 'pending')
        ));
        db_write('friends.json', $new_friends);
        json_response(['success' => true]);

    case 'remove':
        $other_id = (int)($data['user_id'] ?? 0);
        $new_friends = array_values(array_filter($friends, fn($f) =>
            !(($f['requester_id'] == $me['id'] && $f['receiver_id'] == $other_id) ||
              ($f['receiver_id'] == $me['id'] && $f['requester_id'] == $other_id))
        ));
        db_write('friends.json', $new_friends);
        json_response(['success' => true]);

    default:
        json_response(['success' => false, 'error' => 'Unknown action.'], 400);
}
