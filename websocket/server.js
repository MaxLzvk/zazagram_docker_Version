const WebSocket = require('ws');

const PORT = 8084;
const wss  = new WebSocket.Server({ port: PORT });

// Map: ws => { id, username, avatar }
const clients = new Map();

function broadcast(data) {
    const msg = JSON.stringify(data);
    for (const [ws] of clients) {
        if (ws.readyState === WebSocket.OPEN) ws.send(msg);
    }
}

function broadcastOnlineList() {
    const users = [...clients.values()].filter(u => u.id);
    // deduplicate by user id (multiple tabs)
    const seen = new Set();
    const unique = users.filter(u => {
        if (seen.has(u.id)) return false;
        seen.add(u.id); return true;
    });
    broadcast({ type: 'online_list', users: unique });
}

wss.on('connection', (ws) => {
    clients.set(ws, {});

    ws.on('message', (raw) => {
        try {
            const msg = JSON.parse(raw);
            if (msg.type === 'auth') {
                clients.set(ws, {
                    id:       msg.user_id,
                    username: msg.username,
                    avatar:   msg.avatar || ''
                });
                broadcastOnlineList();
            }
        } catch (e) {}
    });

    ws.on('close', () => {
        clients.delete(ws);
        broadcastOnlineList();
    });

    ws.on('error', () => {
        clients.delete(ws);
    });

    // Send heartbeat every 30s
    const ping = setInterval(() => {
        if (ws.readyState === WebSocket.OPEN) ws.ping();
        else clearInterval(ping);
    }, 30000);
});

console.log(`WebSocket server running on ws://0.0.0.0:${PORT}`);
