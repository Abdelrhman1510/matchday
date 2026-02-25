<!DOCTYPE html>
<html>
<head>
    <title>MatchDay WebSocket Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #000; color: #0f0; }
        #log { background: #111; padding: 20px; height: 500px; overflow-y: auto; white-space: pre-wrap; border: 2px solid #0f0; }
        .error { color: #f00; }
        .success { color: #0f0; }
        .info { color: #ff0; }
        button { margin: 10px 5px; padding: 10px 20px; font-size: 16px; }
    </style>
</head>
<body>
<h2>ðŸ”Œ MatchDay WebSocket Deep Debug</h2>

<div>
    <button onclick="testConnection()">ðŸ”„ Test Connection</button>
    <button onclick="sendTestMessage()">ðŸ“¨ Send Test Message</button>
    <button onclick="clearLog()">ðŸ—‘ï¸ Clear Log</button>
</div>

<div id="log"></div>

<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.3.0/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
<script>
const API_TOKEN = '7|X9rQnKlr4bI8X8ZbirZ5Wh0AVRJpH8UYEQf0V4oSc710e1de';
let echo;
let chatChannel;

function log(msg, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    const logDiv = document.getElementById('log');
    logDiv.innerHTML += `<span class="${type}">${timestamp} | ${msg}</span>\n`;
    logDiv.scrollTop = logDiv.scrollHeight;
    console.log(`[${timestamp}] ${msg}`);
}

function clearLog() {
    document.getElementById('log').innerHTML = '';
}

function testConnection() {
    log('ðŸ”„ Testing Reverb connection...', 'info');
    
    // Test WebSocket endpoint directly
    const ws = new WebSocket('ws://localhost:8080/app/matchday-key?protocol=7&client=js&version=8.3.0');
    
    ws.onopen = () => {
        log('âœ… Raw WebSocket connected!', 'success');
        ws.close();
        initEcho();
    };
    
    ws.onerror = (error) => {
        log('âŒ Raw WebSocket error: ' + JSON.stringify(error), 'error');
    };
    
    ws.onclose = () => {
        log('ðŸ”Œ Raw WebSocket closed', 'info');
    };
}

function initEcho() {
    if (echo) {
        log('â™»ï¸ Reconnecting Echo...', 'info');
        echo.disconnect();
    }
    
    log('ðŸ”§ Initializing Echo with Reverb...', 'info');
    
    echo = new Echo({
        broadcaster: 'reverb',
        key: 'matchday-key',
        wsHost: 'localhost',
        wsPort: 8080,
        forceTLS: false,
        disableStats: true,
        enabledTransports: ['ws'],
        authEndpoint: 'http://localhost:8000/api/v1/broadcasting/auth',
        auth: {
            headers: {
                'Authorization': 'Bearer ' + API_TOKEN,
                'Accept': 'application/json'
            }
        }
    });
    
    // Connection state monitoring
    echo.connector.pusher.connection.bind('state_change', (states) => {
        log(`ðŸ“¡ Connection: ${states.previous} â†’ ${states.current}`, 'info');
    });
    
    echo.connector.pusher.connection.bind('connected', () => {
        log('âœ… Echo connected to Reverb!', 'success');
        const socketId = echo.socketId();
        log(`ðŸ†” Socket ID: ${socketId}`, 'info');
    });
    
    echo.connector.pusher.connection.bind('disconnected', () => {
        log('âŒ Echo disconnected from Reverb', 'error');
    });
    
    echo.connector.pusher.connection.bind('error', (err) => {
        log('âŒ Connection error: ' + JSON.stringify(err), 'error');
    });
    
    // Subscribe to chat presence channel
    log('ðŸ“» Subscribing to chat.1 presence channel...', 'info');
    
    chatChannel = echo.join('chat.1');
    
    chatChannel.here((users) => {
        log(`ðŸ‘¥ Currently in room: ${users.length} users`, 'success');
        users.forEach(u => log(`  - ${u.name} (ID: ${u.id})`, 'info'));
    });
    
    chatChannel.joining((user) => {
        log(`âž• ${user.name} joined the room`, 'success');
    });
    
    chatChannel.leaving((user) => {
        log(`âž– ${user.name} left the room`, 'info');
    });
    
    chatChannel.listen('.message.sent', (data) => {
        log(`ðŸ’¬ Message from ${data.user.name}: ${data.message.message}`, 'success');
    });
    
    chatChannel.listen('message.sent', (data) => {
        log(`ðŸ’¬ [No dot] Message from ${data.user.name}: ${data.message.message}`, 'success');
    });
    
    chatChannel.error((error) => {
        log(`âŒ Chat channel error: ${JSON.stringify(error)}`, 'error');
    });
    
    chatChannel.subscription.bind('pusher:subscription_succeeded', (data) => {
        log(`âœ… Successfully subscribed! Members: ${data.count}`, 'success');
        log(`Members data: ${JSON.stringify(data.members || {})}`, 'info');
    });
    
    chatChannel.subscription.bind('pusher:subscription_error', (error) => {
        log(`âŒ Subscription failed: ${JSON.stringify(error)}`, 'error');
    });
    
    // Listen for all events on the channel (debug)
    chatChannel.subscription.bind_global((eventName, data) => {
        if (!eventName.startsWith('pusher:')) {
            log(`ðŸ”” Event: ${eventName} - ${JSON.stringify(data)}`, 'info');
        }
    });
}

async function sendTestMessage() {
    log('ðŸ“¨ Sending message via API...', 'info');
    
    try {
        const response = await fetch('http://localhost:8000/api/v1/chat/rooms/1/messages', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + API_TOKEN,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                message: 'Test message at ' + new Date().toLocaleTimeString()
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            log(`âœ… Message sent! ID: ${data.data.id}`, 'success');
            log(`â³ Waiting for WebSocket broadcast...`, 'info');
        } else {
            log(`âŒ API Error: ${data.message || 'Unknown error'}`, 'error');
        }
    } catch (error) {
        log(`âŒ Request failed: ${error.message}`, 'error');
    }
}

// Auto-initialize on load
log('ðŸš€ MatchDay WebSocket Debug Tool', 'success');
log('Click "Test Connection" to start', 'info');
log('â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•', 'info');

// Auto-start
setTimeout(() => testConnection(), 500);
</script>
</body>
</html>
