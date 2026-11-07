import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Laravel Reverb client. The endpoint comes from meta tags rendered by Blade
 * because Docker builds the bundle without a .env file (see config/broadcasting.php).
 */

const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content;

const key = meta('reverb-key');

window.Pusher = Pusher;

export const echo = key
  ? new Echo({
      broadcaster: 'reverb',
      key,
      wsHost: meta('reverb-host') || window.location.hostname,
      wsPort: Number(meta('reverb-port') || 8080),
      wssPort: Number(meta('reverb-port') || 443),
      forceTLS: (meta('reverb-scheme') || 'http') === 'https',
      enabledTransports: ['ws', 'wss'],
      authEndpoint: '/broadcasting/auth',
    })
  : null;

export default echo;
