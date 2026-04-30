<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me       = get_current_user_data();
$messages = db_read('messages.json');
$users    = db_read('users.json');
$friends  = db_read('friends.json');

// Get accepted friend IDs
$friend_ids = [];
foreach ($friends as $f) {
    if ($f['status'] !== 'accepted') continue;
    if ($f['requester_id'] == $me['id']) $friend_ids[] = (int)$f['receiver_id'];
    if ($f['receiver_id']  == $me['id']) $friend_ids[] = (int)$f['requester_id'];
}

// Get all people I've had conversations with
$conversation_ids = [];
foreach ($messages as $m) {
    if ($m['sender_id']   == $me['id']) $conversation_ids[$m['receiver_id']] = true;
    if ($m['receiver_id'] == $me['id']) $conversation_ids[$m['sender_id']]   = true;
}

// Merge: friends always visible; conversation-only people below
$sidebar_ids = $friend_ids;
foreach (array_keys($conversation_ids) as $uid) {
    if (!in_array($uid, $sidebar_ids)) $sidebar_ids[] = (int)$uid;
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

// Helper: render a single message bubble (PHP side)
function render_msg_bubble(array $m, int $my_id, int $active_id, string $base_url): string {
    $is_out = $m['sender_id'] == $my_id;
    $cls    = $is_out ? 'msg-out' : 'msg-in';
    $del    = $is_out ? '<button class="msg-delete-btn" onclick="deleteMessage(' . $m['id'] . ',' . $active_id . ')" title="Delete">×</button>' : '';
    $img    = '';
    if (!empty($m['image'])) {
        $src = htmlspecialchars($base_url . '/uploads/' . $m['image']);
        $ext = strtolower(pathinfo($m['image'], PATHINFO_EXTENSION));
        if (in_array($ext, ['mp4','webm','mov'])) {
            $img = '<video src="' . $src . '" class="msg-media" controls></video>';
        } else {
            $img = '<a href="' . $src . '" target="_blank"><img src="' . $src . '" class="msg-media" loading="lazy"></a>';
        }
    }
    $text = $m['content'] ? '<div class="msg-bubble">' . nl2br(htmlspecialchars($m['content'])) . '</div>' : '';
    return '<div class="msg ' . $cls . '" data-id="' . (int)$m['id'] . '">'
         . $img . $text
         . '<span class="msg-time">' . date('H:i', strtotime($m['created_at'])) . '</span>'
         . $del . '</div>';
}

$page_title = 'Messages';
include __DIR__ . '/../includes/header.php';
?>

<div class="messages-layout">

    <!-- Conversation list -->
    <aside class="conversations-list card">
        <h3>Messages</h3>
        <?php if (empty($sidebar_ids)): ?>
            <p class="muted" style="padding:0.8rem 1rem;font-size:0.82rem">No friends yet.<br>Add friends to start chatting!</p>
        <?php else: ?>
            <?php foreach ($sidebar_ids as $uid):
                $other = db_find_one($users, 'id', $uid);
                if (!$other) continue;
                $is_friend = in_array($uid, $friend_ids);
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
                            <p><?= !empty($last['image']) && !$last['content'] ? '📎 Image' : htmlspecialchars(substr($last['content'] ?? '', 0, 35)) . '…' ?></p>
                        <?php elseif ($is_friend): ?>
                            <p style="color:var(--text-muted);font-style:italic">Friend · Say hi!</p>
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
            <input type="text" id="new-conv-search" placeholder="Search users…" autocomplete="off">
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
                    <?= render_msg_bubble($m, $me['id'], $active_id, BASE_URL) ?>
                <?php endforeach; ?>
            </div>

            <div id="typing-indicator" class="typing-indicator" style="display:none">
                <div class="typing-dots"><span></span><span></span><span></span></div>
                <span class="typing-name" id="typing-name"></span>
            </div>

            <!-- Image paste preview -->
            <div id="img-preview-bar" style="display:none">
                <div class="img-preview-inner">
                    <img id="img-preview-thumb" src="" alt="preview">
                    <button type="button" class="img-preview-cancel" onclick="clearAttachment()" title="Remove">✕</button>
                </div>
            </div>

            <form class="chat-input-form" id="chat-form" onsubmit="sendMessage(event, <?= $active_id ?>)">
                <!-- Hidden file input -->
                <input type="file" id="msg-file-input" accept="image/*,video/mp4,video/webm,video/quicktime"
                       style="display:none" onchange="onFileSelected(this)">
                <!-- + attachment button -->
                <button type="button" class="msg-attach-btn" onclick="document.getElementById('msg-file-input').click()" title="Attach image or video">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                </button>
                <input type="text" id="msg-input" placeholder="Type a message… (Ctrl+V to paste image)" autocomplete="off"
                       oninput="onMsgInput(<?= $active_id ?>)">
                <button type="submit" class="btn btn-primary">Send ➤</button>
            </form>

        <?php else: ?>
            <div class="chat-empty">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1" style="opacity:.25;margin-bottom:.5rem">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
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
