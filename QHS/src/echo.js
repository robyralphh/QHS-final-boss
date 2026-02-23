import Echo from 'laravel-echo'

// Get the base URL from environment or window location
const baseUrl = import.meta.env.VITE_APP_URL || window.location.origin

try {
  // Use Reverb for real-time WebSocket updates
  window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'laravel-reverb',
    wsHost: import.meta.env.VITE_REVERB_HOST || (new URL(baseUrl)).hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${baseUrl}/broadcasting/auth`,
  })

  console.log('Echo initialized with Reverb broadcaster')
} catch (error) {
  console.warn('Failed to initialize Echo:', error.message)
  
  // Fallback stub if Reverb fails to initialize
  window.Echo = {
    channel: () => ({
      listen: () => {},
      stopListening: () => {},
    }),
    private: () => ({ listen: () => {}, stopListening: () => {} }),
    leaveChannel: () => {},
    socketId: () => null,
  }
}

