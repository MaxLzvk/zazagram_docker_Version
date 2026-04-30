// ============================================================
// messages.js — Real-time messages via WebSocket + image support
// ============================================================

let lastMessageId  = 0;
let pendingFile    = null; // File object waiting to be sent

// Capture the highest message id already rendered on page load
document.querySelectorAll('.msg[data-id]').forEach(el => {
    const id = parseInt(el.dataset.id || 0);
    if (id > lastMessageId) lastMessageId = id;
});

// ── Attachment handling ───────────────────────────────────
function onFileSelected(input) {
    const file = input.files[0];
    if (!file) return;
    setPendingFile(file);
    input.value = ''; // reset so same file can be re-selected
}

function setPendingFile(file) {
    pendingFile = file;
    const bar   = document.getElementById('img-preview-bar');
    const thumb = document.getElementById('img-preview-thumb');
    if (!bar || !thumb) return;
    const isVideo = file.type.startsWith('video/');
    if (isVideo) {
        thumb.src = '';
        thumb.style.display = 'none';
        bar.querySelector('.img-preview-inner').innerHTML =
            `<span style="font-size:0.8rem;color:var(--text-muted)">🎬 ${escMsg(file.name)}</span>
             <button type="button" class="img-preview-cancel" onclick="clearAttachment()" title="Remove">✕</button>`;
    } else {
        thumb.style.display = '';
        const reader = new FileReader();
        reader.onload = e => { thumb.src = e.target.result; };
        reader.readAsDataURL(file);
    }
    bar.style.display = 'flex';
    document.getElementById('msg-input')?.focus();
}

function clearAttachment() {
    pendingFile = null;
    const bar = document.getElementById('img-preview-bar');
    if (bar) {
        bar.style.display = 'none';
        // Restore inner HTML in case it was replaced for video
        bar.innerHTML = `<div class="img-preview-inner">
            <img id="img-preview-thumb" src="" alt="preview">
            <button type="button" class="img-preview-cancel" onclick="clearAttachment()" title="Remove">✕</button>
        </div>`;
    }
}

// ── Ctrl+V paste to attach clipboard images ───────────────
document.addEventListener('paste', (e) => {
    if (typeof ACTIVE_ID === 'undefined' || !ACTIVE_ID) return;
    const items = e.clipboardData?.items;
    if (!items) return;
    for (const item of items) {
        if (item.type.startsWith('image/')) {
            e.preventDefault();
            const file = item.getAsFile();
            if (file) setPendingFile(file);
            break;
        }
    }
});

// ── WS events dispatched by main.js ──────────────────────
window.addEventListener('zzg:new_message', (e) => {
    const msg = e.detail.message;
    if (!msg) return;
    if (typeof ACTIVE_ID === 'undefined' || !ACTIVE_ID) return;
    if (msg.sender_id != ACTIVE_ID && msg.receiver_id != ACTIVE_ID) return;
    if (msg.id <= lastMessageId) return;

    lastMessageId = msg.id;
    const container = document.getElementById('chat-messages');
    if (!container) return;

    const isOut = msg.sender_id == (window.ZZG && window.ZZG.userId);
    container.insertAdjacentHTML('beforeend', buildMsgHTML(msg, isOut));
    container.scrollTop = container.scrollHeight;
    setTypingIndicator(false);
    updateBadge('nav-msg-badge', null, -1);
});

window.addEventListener('zzg:delete_message', (e) => {
    document.querySelector(`.msg[data-id="${e.detail.message_id}"]`)?.remove();
});

window.addEventListener('zzg:typing', (e) => {
    if (typeof ACTIVE_ID === 'undefined' || e.detail.from_user_id != ACTIVE_ID) return;
    setTypingIndicator(e.detail.is_typing);
    clearTimeout(window._typingTimeout);
    window._typingTimeout = setTimeout(() => setTypingIndicator(false), 3000);
});

// ── Build message HTML (used for WS-pushed messages) ─────
function buildMsgHTML(m, isOut) {
    const base    = (window.ZZG && window.ZZG.baseUrl) || BASE_URL;
    const cls     = isOut ? 'msg-out' : 'msg-in';
    const del     = isOut ? `<button class="msg-delete-btn" onclick="deleteMessage(${m.id},${ACTIVE_ID})" title="Delete">\xd7</button>` : '';
    const now     = new Date(m.created_at || Date.now());
    const time    = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
    let img = '';
    if (m.image) {
        const src = base + '/uploads/' + m.image;
        const ext = m.image.split('.').pop().toLowerCase();
        if (['mp4','webm','mov'].includes(ext)) {
            img = `<video src="${src}" class="msg-media" controls></video>`;
        } else {
            img = `<a href="${src}" target="_blank"><img src="${src}" class="msg-media" loading="lazy"></a>`;
        }
    }
    const text = m.content ? `<div class="msg-bubble">${escMsg(m.content)}</div>` : '';
    return `<div class="msg ${cls}" data-id="${m.id}">${img}${text}<span class="msg-time">${time}</span>${del}</div>`;
}

// ── Typing indicator ──────────────────────────────────────
let typingThrottleTimer = null;

function onMsgInput(receiverId) {
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

// ── Send message (text + optional attachment) ─────────────
function sendMessage(event, receiverId) {
    event.preventDefault();
    const input   = document.getElementById('msg-input');
    const content = input.value.trim();

    if (!content && !pendingFile) return;

    const btn = event.target.querySelector('button[type="submit"]');
    btn.disabled = true;

    if (pendingFile) {
        // Multipart upload
        const fd = new FormData();
        fd.append('receiver_id', receiverId);
        fd.append('content', content);
        fd.append('image', pendingFile, pendingFile.name);

        fetch(`${BASE_URL}/api/send_message_image.php`, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    clearAttachment();
                    const m = data.message;
                    if (m.id > lastMessageId) {
                        lastMessageId = m.id;
                        const container = document.getElementById('chat-messages');
                        if (container) {
                            container.insertAdjacentHTML('beforeend', buildMsgHTML(m, true));
                            container.scrollTop = container.scrollHeight;
                        }
                    }
                } else {
                    alert(data.error || 'Error sending.');
                }
            })
            .catch(() => alert('Network error.'))
            .finally(() => { btn.disabled = false; });
    } else {
        // JSON text-only
        fetch(`${BASE_URL}/api/send_message.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ receiver_id: receiverId, content }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                const m = data.message;
                if (m.id > lastMessageId) {
                    lastMessageId = m.id;
                    const container = document.getElementById('chat-messages');
                    if (container) {
                        container.insertAdjacentHTML('beforeend', buildMsgHTML(m, true));
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
