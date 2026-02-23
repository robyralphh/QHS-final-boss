import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const PUSHER_KEY = import.meta.env.VITE_PUSHER_APP_KEY || null
if (PUSHER_KEY) {
  window.Echo = new Echo({
    broadcaster: 'pusher',
    key: PUSHER_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || null,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || 'https') === 'https',
    wsHost: import.meta.env.VITE_PUSHER_HOST || undefined,
    wsPort: import.meta.env.VITE_PUSHER_PORT ? Number(import.meta.env.VITE_PUSHER_PORT) : undefined,
    enabledTransports: ['ws', 'wss']
  })
} else {
  console.warn('VITE_PUSHER_APP_KEY is not set — skipping Echo (Pusher) initialization.')
}
