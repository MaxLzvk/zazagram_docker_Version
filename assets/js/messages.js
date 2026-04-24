// ============================================================
// messages.js — Real-time messages via Server-Sent Events (SSE)
// ============================================================

let lastMessageId = 0;
let eventSource   = null;

// Capture the highest message id already rendered on page load
document.querySelectorAll('.msg[data-id]').forEach(el => {
    const id = parseInt(el.dataset.id || 0);
    if (id > lastMessageId) lastMessageId = id;
});

if (typeof ACTIVE_ID !== 'undefined' && ACTIVE_ID) {
    startSSE(ACTIVE_ID);
}

function startSSE(otherId) {
    if (eventSource) { eventSource.close(); eventSource = null; }

    const url = `${BASE_URL}/api/messages_stream.php?user_id=${otherId}&last_id=${lastMessageId}`;
    eventSource = new EventSource(url);

    eventSource.onmessage = function(e) {
        let data;
        try { data = JSON.parse(e.data); } catch { return; }

        // 28-second stream window ended — reconnect immediately with updated cursor
        if (data.reconnect) {
            if (data.last_id) lastMessageId = Math.max(lastMessageId, data.last_id);
            eventSource.close();
            startSSE(otherId);
            return;
        }

        // Update typing indicator
        if ('typing' in data) setTypingIndicator(data.typing);

        // Handle deletions
        if (data.deletions && data.deletions.length) {
            data.deletions.forEach(id => {
                document.querySelector(`.msg[data-id="${id}"]`)?.remove();
            });
        }

        if (!data.messages || !data.messages.length) return;

        const container = document.getElementById('chat-messages');
        if (!container) return;

        data.messages.forEach(m => {
            if (m.id <= lastMessageId) return; // skip already-shown (e.g. own sent msg)
            lastMessageId = m.id;
            const isOut = m.sender_id == data.my_id;
            const div = document.createElement('div');
            div.className = 'msg ' + (isOut ? 'msg-out' : 'msg-in');
            div.dataset.id = m.id;
            div.innerHTML = `<div class="msg-bubble">${escMsg(m.content)}</div>
                             <span class="msg-time">${formatTime(m.created_at)}</span>
                             ${isOut ? `<button class="msg-delete-btn" onclick="deleteMessage(${m.id}, ${otherId})" title="Delete">\xd7</button>` : ''}`;
            container.appendChild(div);
        });
        container.scrollTop = container.scrollHeight;
    };

    // EventSource will auto-reconnect on errors; no manual handling needed
    eventSource.onerror = function() {};
}

// ── Typing indicator ─────────────────────────────────────
let typingThrottleTimer = null;

function onMsgInput(receiverId) {
    // Throttle: send at most one typing ping every 2 seconds
    if (typingThrottleTimer) return;
    typingThrottleTimer = setTimeout(() => { typingThrottleTimer = null; }, 2000);
    fetch(`${BASE_URL}/api/typing.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ receiver_id: receiverId }),
    }).catch(() => {});
}

function setTypingIndicator(isTyping) {
    const el = document.getElementById('typing-indicator');
    if (!el) return;
    if (isTyping) {
        const name = (typeof ACTIVE_USERNAME !== 'undefined' && ACTIVE_USERNAME) ? ACTIVE_USERNAME : 'Someone';
        document.getElementById('typing-name').textContent = name + ' is typing…';
        el.style.display = 'flex';
    } else {
        el.style.display = 'none';
    }
}

function sendMessage(event, receiverId) {
    event.preventDefault();
    const input   = document.getElementById('msg-input');
    const content = input.value.trim();
    if (!content) return;

    const btn = event.target.querySelector('button');
    btn.disabled = true;

    fetch(`${BASE_URL}/api/send_message.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ receiver_id: receiverId, content }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            const container = document.getElementById('chat-messages');
            if (container) {
                const m = data.message;
                // Append immediately for instant feel; track id so SSE doesn't duplicate it
                if (m.id > lastMessageId) {
                    lastMessageId = m.id;
                    const div = document.createElement('div');
                    div.className = 'msg msg-out';
                    div.dataset.id = m.id;
                    div.innerHTML = `<div class="msg-bubble">${escMsg(m.content)}</div>
                                     <span class="msg-time">${formatTime(m.created_at)}</span>
                                     <button class="msg-delete-btn" onclick="deleteMessage(${m.id}, ${receiverId})" title="Delete">\xd7</button>`;
                    container.appendChild(div);
                    container.scrollTop = container.scrollHeight;
                }
            }
        } else {
            alert(data.error || 'Error sending message.');
        }
    })
    .catch(() => alert('Network error.'))
    .finally(() => { btn.disabled = false; });
}

function deleteMessage(messageId, receiverId) {
    fetch(`${BASE_URL}/api/delete_message.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message_id: messageId }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.querySelector(`.msg[data-id="${messageId}"]`)?.remove();
        } else {
            alert(data.error || 'Could not delete message.');
        }
    })
    .catch(() => alert('Network error.'));
}

function escMsg(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/\n/g,'<br>');
}

function formatTime(iso) {
    const d = new Date(iso);
    return d.getHours().toString().padStart(2,'0') + ':' +
           d.getMinutes().toString().padStart(2,'0');
}
