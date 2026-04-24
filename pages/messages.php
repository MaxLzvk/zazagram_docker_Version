<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me       = get_current_user_data();
$messages = db_read('messages.json');
$users    = db_read('users.json');

// Get all people I've had conversations with
$conversation_ids = [];
foreach ($messages as $m) {
    if ($m['sender_id'] == $me['id'])   $conversation_ids[$m['receiver_id']] = true;
    if ($m['receiver_id'] == $me['id']) $conversation_ids[$m['sender_id']]   = true;
}

// Selected conversation
$active_id   = (int)($_GET['user'] ?? 0);
$active_user = $active_id ? db_find_one($users, 'id', $active_id) : null;

// Mark messages as read
if ($active_user) {
    $changed = false;
    foreach ($messages as &$m) {
        if ($m['sender_id'] == $active_id && $m['receiver_id'] == $me['id'] && !$m['is_read']) {
            $m['is_read'] = true;
            $changed = true;
        }
    }
    unset($m);
    if ($changed) db_write('messages.json', $messages);
}

// Get conversation messages
$conv_messages = [];
if ($active_user) {
    $conv_messages = array_values(array_filter($messages, fn($m) =>
        ($m['sender_id'] == $me['id'] && $m['receiver_id'] == $active_id) ||
        ($m['sender_id'] == $active_id && $m['receiver_id'] == $me['id'])
    ));
}

$page_title = 'Messages';
include __DIR__ . '/../includes/header.php';
?>

<div class="messages-layout">

    <!-- Conversation list -->
    <aside class="conversations-list card">
        <h3>Messages</h3>
        <?php if (empty($conversation_ids)): ?>
            <p class="muted">No conversations yet.</p>
        <?php else: ?>
            <?php foreach (array_keys($conversation_ids) as $uid):
                $other = db_find_one($users, 'id', $uid);
                if (!$other) continue;
                $unread = count(array_filter($messages, fn($m) =>
                    $m['sender_id'] == $uid && $m['receiver_id'] == $me['id'] && !$m['is_read']
                ));
                $last = null;
                foreach (array_reverse($messages) as $m) {
                    if (($m['sender_id'] == $me['id'] && $m['receiver_id'] == $uid) ||
                        ($m['sender_id'] == $uid   && $m['receiver_id'] == $me['id'])) {
                        $last = $m; break;
                    }
                }
            ?>
                <a href="?user=<?= $uid ?>" class="conv-item <?= $active_id == $uid ? 'active' : '' ?>">
                    <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($other['profile_picture']) ?>"
                         class="conv-avatar"
                         onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/default_avatar.png'">
                    <div class="conv-info">
                        <strong><?= htmlspecialchars($other['username']) ?></strong>
                        <?php if ($last): ?>
                            <p><?= htmlspecialchars(substr($last['content'], 0, 35)) ?>…</p>
                        <?php endif; ?>
                    </div>
                    <?php if ($unread > 0): ?>
                        <span class="badge"><?= $unread ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Start new conversation -->
        <div class="new-conv">
            <input type="text" id="new-conv-search" placeholder="New message…" autocomplete="off">
            <div id="new-conv-results" class="search-dropdown"></div>
        </div>
    </aside>

    <!-- Chat window -->
    <div class="chat-window card">
        <?php if ($active_user): ?>
            <div class="chat-header">
                <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($active_user['profile_picture']) ?>"
                     class="chat-avatar"
                     onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/default_avatar.png'">
                <div>
                    <a href="<?= BASE_URL ?>/pages/profile.php?username=<?= urlencode($active_user['username']) ?>">
                        <strong><?= htmlspecialchars($active_user['username']) ?></strong>
                    </a>
                    <p><?= htmlspecialchars($active_user['first_name'] . ' ' . $active_user['last_name']) ?></p>
                </div>
            </div>

            <div class="chat-messages" id="chat-messages">
                <?php foreach ($conv_messages as $m): ?>
                    <div class="msg <?= $m['sender_id'] == $me['id'] ? 'msg-out' : 'msg-in' ?>" data-id="<?= (int)$m['id'] ?>">
                        <div class="msg-bubble"><?= nl2br(htmlspecialchars($m['content'])) ?></div>
                        <span class="msg-time"><?= date('H:i', strtotime($m['created_at'])) ?></span>
                        <?php if ($m['sender_id'] == $me['id']): ?>
                            <button class="msg-delete-btn" onclick="deleteMessage(<?= $m['id'] ?>, <?= $active_id ?>)" title="Delete">×</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="typing-indicator" class="typing-indicator" style="display:none">
                <div class="typing-dots"><span></span><span></span><span></span></div>
                <span class="typing-name" id="typing-name"></span>
            </div>

            <form class="chat-input-form" onsubmit="sendMessage(event, <?= $active_id ?>)">
                <input type="text" id="msg-input" placeholder="Type a message…" required autocomplete="off"
                       oninput="onMsgInput(<?= $active_id ?>)">
                <button type="submit" class="btn btn-primary">Send ➤</button>
            </form>

        <?php else: ?>
            <div class="chat-empty">
                <p>Select a conversation or start a new one</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
const BASE_URL        = '<?= BASE_URL ?>';
const ACTIVE_ID       = <?= $active_id ?: 'null' ?>;
const ACTIVE_USERNAME = <?= $active_user ? json_encode(htmlspecialchars($active_user['username'])) : 'null' ?>;
// Scroll to bottom
const cm = document.getElementById('chat-messages');
if (cm) cm.scrollTop = cm.scrollHeight;
</script>
<script src="<?= BASE_URL ?>/assets/js/messages.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
