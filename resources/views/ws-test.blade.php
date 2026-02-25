<!DOCTYPE html>
<html>
<head><title>MatchDay Real-Time Test</title></head>
<body>
<h2>MatchDay WebSocket Tester</h2>
<div id="log" style="font-family:monospace; white-space:pre; background:#111; color:#0f0; padding:20px; height:500px; overflow-y:auto;"></div>

<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.3.0/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
<script>
const log = (msg) => {
    document.getElementById('log').innerHTML += new Date().toLocaleTimeString() + ' | ' + msg + '\n';
};

// Connect to Reverb with auth for presence channels
const echo = new Echo({
    broadcaster: 'reverb',
    key: 'matchday-key',
    wsHost: 'localhost',
    wsPort: 8080,
    forceTLS: false,
    disableStats: true,
    enabledTransports: ['ws'],
    authEndpoint: '/api/v1/broadcasting/auth',
    auth: {
        headers: {
            'Authorization': 'Bearer 1|NFmT8h5TO64vQ00wz0iY4zBHVxbuFo4TUOp3PfLpfdd525bd'
        }
    }
});

log('Connecting to Reverb...');

// ═══════════════════════════════════════
// 1. PUBLIC CHANNELS (no auth needed)
// ═══════════════════════════════════════

echo.channel('matches')
    .listen('MatchPublished', (e) => log('MATCH PUBLISHED: ' + JSON.stringify(e)))
    .listen('MatchScoreUpdated', (e) => log('SCORE: ' + JSON.stringify(e)))
    .listen('MatchCancelled', (e) => log('CANCELLED: ' + JSON.stringify(e)));

echo.channel('match.1')
    .listen('MatchScoreUpdated', (e) => log('[Match 1] Score: ' + e.home_score + '-' + e.away_score));

echo.channel('branch.1')
    .listen('MatchPublished', (e) => log('[Branch 1] New match available'));

// ═══════════════════════════════════════
// 2. CHAT ROOM (Presence channel with auth)
// ═══════════════════════════════════════

echo.join('chat.1')
    .here((users) => log('Joined chat room! Online: ' + users.length + ' users'))
    .joining((user) => log('+ ' + user.name + ' joined'))
    .leaving((user) => log('- ' + user.name + ' left'))
    .listen('.message.sent', (e) => log('MSG ' + e.user.name + ': ' + e.message.message))
    .listen('.reaction.sent', (e) => log('REACT ' + e.user.name + ': ' + e.emoji))
    .listen('.viewer.count.updated', (e) => log('Viewers: ' + e.count));

// ═══════════════════════════════════════
// 3. NOTIFICATIONS (Private channel with auth)
// ═══════════════════════════════════════

echo.private('notifications.1')
    .listen('.notification.new', (e) => log('NOTIFICATION [' + e.type + '] ' + e.title + ': ' + e.body));

log('Subscribed to: matches, match.1, branch.1, chat.1, notifications.1');
log('Waiting for events...');
</script>
</body>
</html>
