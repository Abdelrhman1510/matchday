import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Only initialise Echo/Pusher when a Reverb key is actually configured.
// Without this guard, Pusher throws "You must pass your app key" when
// VITE_REVERB_APP_KEY is not set (e.g. on Railway without a Reverb server).
if (import.meta.env.VITE_REVERB_APP_KEY) {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
