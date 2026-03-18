import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

const reverbHost   = import.meta.env.VITE_REVERB_HOST   || window.location.hostname;
const reverbPort   = import.meta.env.VITE_REVERB_PORT   || 8080;
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || 'http';
const useTLS       = reverbScheme === 'https';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: reverbHost,
    wsPort: useTLS ? (import.meta.env.VITE_REVERB_PORT || 443) : reverbPort,
    wssPort: import.meta.env.VITE_REVERB_PORT || 443,
    forceTLS: useTLS,
    enabledTransports: useTLS ? ['wss'] : ['ws', 'wss'],
    disableStats: true,
});
