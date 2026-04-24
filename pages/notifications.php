<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me    = get_current_user_data();
$users = db_read('users.json');

$notifs = db_read('notifications.json');
$my_notifs = array_reverse(db_find_all($notifs, 'user_id', $me['id']));

// Mark all as read
$notifs = array_map(function($n) use ($me) {
    if ($n['user_id'] == $me['id']) $n['is_read'] = true;
    return $n;
}, $notifs);
db_write('notifications.json', $notifs);

$page_title = 'Notifications';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:700px">
    <h2>Notifications</h2>

    <?php if (empty($my_notifs)): ?>
        <div class="card empty-state"><p>No notifications yet.</p></div>
    <?php else: ?>
        <div class="notif-list">
            <?php foreach ($my_notifs as $n):
                $actor = db_find_one($users, 'id', $n['actor_id']);
                if (!$actor) continue;
            ?>
                <div class="notif-item card <?= !$n['is_read'] ? 'notif-unread' : '' ?>">
                    <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($actor['profile_picture']) ?>"
                         class="notif-avatar"
                         onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/default_avatar.png'">
                    <div class="notif-body">
                        <p>
                            <?php
                            $label = match($n['type']) {
                                  'like'           => '[Like]',
                                  'comment'        => '[Comment]',
                                  'friend_request' => '[Friend Request]',
                                  'message'        => '[Message]',
                                  default          => '',
                              };
                              if ($label) echo '<span class="notif-type-label">' . $label . '</span> ';
                            ?>
                            <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($actor['username']) ?>">
                                <strong><?= htmlspecialchars($actor['username']) ?></strong>
                            </a>
                            <?= htmlspecialchars(substr($n['message'], strlen($actor['username']))) ?>
                        </p>
                        <span class="notif-time"><?= date('M j, Y · H:i', strtotime($n['created_at'])) ?></span>

                        <?php if ($n['type'] === 'friend_request'): ?>
                            <div class="notif-actions">
                                <button class="btn btn-sm btn-success"
                                        onclick="respondFriend(<?= $actor['id'] ?>, 'accept', this)">Accept</button>
                                <button class="btn btn-sm btn-danger"
                                        onclick="respondFriend(<?= $actor['id'] ?>, 'decline', this)">Decline</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function respondFriend(userId, action, btn) {
    fetch('<?= BASE_URL ?>/api/friend_request.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action, requester_id: userId })
    }).then(r => r.json()).then(d => {
        if (d.success) btn.closest('.notif-actions').innerHTML = action === 'accept' ? '<span class="text-success">Accepted</span>' : '<span class="text-muted">Declined</span>';
        else alert(d.error);
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
