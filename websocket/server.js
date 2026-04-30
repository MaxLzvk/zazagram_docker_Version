const WebSocket = require('ws');
const http      = require('http');

const WS_PORT   = 8084;
const PUSH_PORT = 8085; // internal HTTP port for PHP → WS push

const wss = new WebSocket.Server({ port: WS_PORT });

// Map: ws => { id, username, avatar }
const clients = new Map();

// ── Helpers ──────────────────────────────────────────────
function broadcast(data) {
    const msg = JSON.stringify(data);
    for (const [ws] of clients) {
        if (ws.readyState === WebSocket.OPEN) ws.send(msg);
    }
}

function sendToUser(userId, data) {
    const uid = Number(userId);
    const msg = JSON.stringify(data);
    for (const [ws, info] of clients) {
        if (info.id === uid && ws.readyState === WebSocket.OPEN) ws.send(msg);
    }
}

function broadcastOnlineList() {
    const users = [...clients.values()].filter(u => u.id);
    const seen  = new Set();
    const unique = users.filter(u => {
        if (seen.has(u.id)) return false;
        seen.add(u.id); return true;
    });
    broadcast({ type: 'online_list', users: unique });
}

// ── WebSocket server (port 8084) ─────────────────────────
wss.on('connection', (ws) => {
    clients.set(ws, {});

    ws.on('message', (raw) => {
        try {
            const msg = JSON.parse(raw);
            if (msg.type === 'auth') {
                clients.set(ws, {
                    id:       Number(msg.user_id),
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

    const ping = setInterval(() => {
        if (ws.readyState === WebSocket.OPEN) ws.ping();
        else clearInterval(ping);
    }, 30000);
});

// ── Internal HTTP push endpoint (port 8085) ──────────────
// PHP calls POST http://ws:8085/push with a JSON body:
//   { type, broadcast: true }          → send to every connected client
//   { type, to_user_id: N, ... }       → send only to that user's socket(s)
http.createServer((req, res) => {
    if (req.method !== 'POST' || req.url !== '/push') {
        res.writeHead(404); res.end(); return;
    }
    let body = '';
    req.on('data', chunk => body += chunk);
    req.on('end', () => {
        try {
            const payload = JSON.parse(body);
            if (payload.broadcast) {
                broadcast(payload);
            } else if (payload.to_user_id) {
                sendToUser(payload.to_user_id, payload);
            }
        } catch (e) {}
        res.writeHead(200); res.end('ok');
    });
}).listen(PUSH_PORT, '0.0.0.0');

console.log(`WebSocket server  → ws://0.0.0.0:${WS_PORT}`);
console.log(`Push HTTP server  → http://0.0.0.0:${PUSH_PORT}/push`);
