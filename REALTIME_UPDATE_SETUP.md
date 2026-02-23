# Real-Time Updates Setup Guide

## What We've Implemented

Your application now has **real-time notifications and borrow history updates** using Laravel Reverb WebSocket server.

### Key Changes Made:

#### 1. **Frontend Updates (React)**

**File: `QHS/src/echo.js`**
- Configured Laravel Echo to connect to Reverb broadcaster
- Listens on `transactions` channel for updates
- Falls back to polling if WebSocket is unavailable

**File: `QHS/src/Components/UserLayout.jsx`**
- Added `refreshNotifications()` function for manual refresh
- Set up Reverb listener in `useEffect` hook
- Immediately refreshes notifications after successful request submission
- Dispatches `transactionUpdated` event for other components

**File: `QHS/src/views/BorrowHistory.jsx`**
- Set up Reverb listener to refresh on transaction updates
- Listens for `transactionUpdated` window events
- Automatically shows new requests immediately

#### 2. **Backend Configuration**

**File: `routes/channels.php`**
- Added public `transactions` channel for authenticated users
- Allows all logged-in users to receive real-time updates

**Backend Broadcasting (already configured)**
- `TransactionUpdated` event broadcasts to `transactions` channel
- Backend broadcasts to all authenticated users when transactions change

#### 3. **Environment Setup**

Your `.env` file already has:
```
BROADCAST_DRIVER=reverb
REVERB_APP_ID=qhs_app
REVERB_APP_KEY=reverb-key-qhs-12345
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY=reverb-key-qhs-12345
VITE_REVERB_HOST=127.0.0.1
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

## How It Works

### Real-Time Flow:
1. User submits a request in the cart
2. Frontend sends POST to `/transactions` endpoint
3. Backend creates transaction and broadcasts `TransactionUpdated` event
4. **All connected users receive the update instantly via WebSocket**
5. Notifications and Borrow History update immediately
6. Cart clears and user sees success message

### Fallback (Polling):
- If WebSocket connection fails, app falls back to polling every 10 seconds
- Ensures data updates even if Reverb is unavailable

## Starting Reverb Server

### Option 1: Command Line (Production-ready)
```bash
php artisan reverb:start
```

### Option 2: Using Supervisor (Recommended for local dev)
```bash
# Start in a separate terminal window
cd C:\Users\Rob\Desktop\QHS
php artisan reverb:start --host=127.0.0.1 --port=8080
```

### Option 3: Using FrankenPHP
- If your app is running on FrankenPHP, Reverb may start automatically
- Check `.env` to verify `REVERB_HOST=127.0.0.1` and `REVERB_PORT=8080`

## Testing Real-Time Updates

1. **Open your app in two browser windows:**
   - Window A: Submit a request
   - Window B: Watch the Borrow History page

2. **Expected behavior:**
   - Request appears in Window B's Borrow History **immediately** (no page refresh)
   - Notification badge updates in real-time
   - No waiting for 10-second polling interval

3. **Check browser console:**
   - Should see: `"Echo initialized with Reverb broadcaster"`
   - No WebSocket connection errors

## Troubleshooting

### If notifications don't appear:

1. **Check Reverb server status:**
   ```bash
   # List running processes
   Get-Process | Where-Object {$_.ProcessName -eq "node"}
   ```

2. **Check browser console:**
   - Look for WebSocket connection errors
   - If error, Reverb server isn't running
   - Solution: Start Reverb with `php artisan reverb:start`

3. **Verify .env file:**
   - Ensure `BROADCAST_DRIVER=reverb`
   - Check `REVERB_HOST` matches your server IP
   - `REVERB_PORT=8080` should match your setup

4. **Clear browser cache:**
   - `Ctrl+Shift+Delete` or `Cmd+Shift+Delete`
   - Clear all cache and reload

### If WebSocket times out:

- This is normal and falls back to polling
- App will still work, just with slight delay
- Ensure firewall isn't blocking port 8080

## Performance Notes

- Real-time updates use WebSocket (very efficient)
- Fallback polling every 10 seconds if WebSocket unavailable
- Each transaction update broadcasts to all users
- Minimal bandwidth usage (only data changes broadcast)

## Next Steps (Optional)

1. **Start Reverb in production:**
   ```bash
   supervisord  # if using Supervisor
   ```

2. **Monitor WebSocket connections:**
   - Check Reverb logs for connection events
   - Monitor memory usage (WebSocket connections are long-lived)

3. **Scale Reverb (if needed):**
   - Use Redis adapter for multiple Reverb instances
   - Configure in `config/reverb.php`

---

**Summary:** Your app now has fully functional real-time notifications and borrow history updates using WebSockets, with polling fallback for reliability.
