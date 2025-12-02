# WebSocket Fix - Verification Guide

## Issues Fixed

### 1. ❌ Missing `config/broadcasting.php`
**Problem**: Laravel couldn't broadcast events without the broadcasting driver configuration.

**Solution**: Created `/home/nileneb/SupportedGrowControl/config/broadcasting.php` with Reverb driver configuration.

**Status**: ✅ Fixed

### 2. ❌ Missing `BROADCAST_CONNECTION` Environment Variable
**Problem**: Laravel didn't know which broadcasting driver to use.

**Solution**: Added `BROADCAST_CONNECTION=reverb` to `.env`

**Status**: ✅ Fixed

### 3. ❌ Missing `BroadcastServiceProvider`
**Problem**: Broadcasting routes (especially `/broadcasting/auth`) were not registered.

**Solution**: 
- Created `/home/nileneb/SupportedGrowControl/app/Providers/BroadcastServiceProvider.php`
- Registered it in `bootstrap/providers.php`

**Status**: ✅ Fixed

## Current Configuration

### Backend (Laravel)
```bash
# Broadcasting is configured
BROADCAST_CONNECTION=reverb

# Reverb server settings (internal Docker)
REVERB_APP_ID=growdash
REVERB_APP_KEY=growdash-reverb-key
REVERB_APP_SECRET=growdash-reverb-secret
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Frontend (Vite)
```bash
# Client connects via HTTPS proxy through Synology
VITE_REVERB_APP_KEY=growdash-reverb-key
VITE_REVERB_HOST=grow.linn.games
VITE_REVERB_PORT=443 (empty = default 443)
VITE_REVERB_PATH=/app
VITE_REVERB_SCHEME=https
```

### Routes Registered
✅ `/broadcasting/auth` - Handles private channel authorization
✅ Private channels: `device.{deviceId}` and `command.{commandId}`

### Events Broadcasting
✅ `CommandStatusUpdated` → broadcasts to `device.{deviceId}` as `.command.status.updated`
✅ `DeviceCapabilitiesUpdated` → broadcasts to `device.{deviceId}` as `.capabilities.updated`
✅ `DeviceTelemetryReceived` → broadcasts to `device.{deviceId}` as `.telemetry.received`

## Verification Steps

### 1. Check Laravel Configuration
```bash
docker compose -f docker-compose.grow.prod.yaml exec php-fpm php artisan config:show broadcasting | head -15
```

**Expected Output**:
```
broadcasting ...............................................................  
default ............................................................. reverb  
connections ⇁ reverb ⇁ driver ....................................... reverb  
connections ⇁ reverb ⇁ key ............................. growdash-reverb-key
```

### 2. Check Broadcasting Route
```bash
docker compose -f docker-compose.grow.prod.yaml exec php-fpm php artisan route:list | grep broadcasting
```

**Expected Output**:
```
GET|POST|HEAD broadcasting/auth Illuminate\Broadcasting › BroadcastController@authenticate
```

### 3. Check Reverb Server Status
```bash
docker compose -f docker-compose.grow.prod.yaml ps reverb
```

**Expected**: Container should be "Up"

### 4. Check Reverb Logs
```bash
docker compose -f docker-compose.grow.prod.yaml logs reverb --tail=50
```

**Expected**: Should show "Starting server on 0.0.0.0:8080"

### 5. Browser Console Test

**Open**: https://grow.linn.games/devices/{your-device-id}

**Expected Console Output**:
```
✓ Laravel Echo initialized
✓ WebSocket connected
Subscribing to private channel: device.{deviceId}
✓ WebSocket listeners initialized
```

**Check for Errors**:
- ❌ "WebSocket channel error:" - If you see this, check authorization
- ❌ "Echo not initialized yet" - Frontend bundle issue, rebuild needed
- ❌ "Connection refused" - Nginx proxy or Reverb server issue

### 6. Test WebSocket Connection

**Method 1: Browser DevTools Network Tab**
1. Open DevTools → Network tab
2. Filter by "WS" (WebSocket)
3. Reload page
4. Look for connection to `wss://grow.linn.games/app/`
5. Status should be "101 Switching Protocols"

**Method 2: Send Test Command**
1. Go to device detail page
2. Open serial console
3. Send a command
4. Watch for real-time updates in the UI

**Method 3: Check Laravel Logs**
```bash
docker compose -f docker-compose.grow.prod.yaml exec php-fpm tail -f storage/logs/laravel.log
```

## Connection Flow

1. **Browser → Nginx** (HTTPS)
   - Client connects to `wss://grow.linn.games/app/`
   - Nginx receives WebSocket upgrade request

2. **Nginx → Reverb** (HTTP)
   - Nginx proxies `/app/` to `reverb:8080`
   - WebSocket upgrade headers forwarded

3. **Reverb → Laravel** (Authentication)
   - Client sends auth request to `/broadcasting/auth`
   - Laravel validates user owns the device
   - Returns signed authorization

4. **Reverb ↔ Browser** (WebSocket Open)
   - Client subscribes to `private-device.{deviceId}`
   - Listens for `.command.status.updated` and `.capabilities.updated`

5. **Laravel → Reverb** (Event Broadcasting)
   - Backend fires event: `CommandStatusUpdated::dispatch($command)`
   - Reverb receives event via internal connection
   - Reverb pushes to subscribed clients

## Troubleshooting

### Error: "WebSocket channel error: {status: 403}"
**Cause**: Authorization failed

**Solutions**:
1. Verify user is logged in
2. Check `routes/channels.php` authorization logic
3. Check device ownership: `$device->user_id === $user->id`
4. Verify CSRF token is present in page

### Error: "WebSocket connection failed"
**Cause**: Reverb server not reachable

**Solutions**:
1. Check Reverb container: `docker compose ps reverb`
2. Check Reverb logs: `docker compose logs reverb`
3. Verify nginx proxy config at `/app/` location block
4. Test direct access to Reverb from container:
   ```bash
   docker compose exec web curl -I http://reverb:8080
   ```

### Error: "Echo is not defined"
**Cause**: Frontend assets not compiled

**Solutions**:
1. Rebuild frontend: `npm run build`
2. Clear browser cache (Ctrl+Shift+R)
3. Check `public/build/manifest.json` exists
4. Verify `resources/js/app.js` imports Echo

### Error: "Connection timeout"
**Cause**: Firewall or proxy issue

**Solutions**:
1. Check Synology firewall rules
2. Verify reverse proxy settings in Synology
3. Check nginx timeout settings (proxy_read_timeout)
4. Test WebSocket connection with online tool

## Files Changed in This Fix

1. **Created**: `config/broadcasting.php`
   - Defines Reverb as default broadcaster
   - Configures connection credentials

2. **Created**: `app/Providers/BroadcastServiceProvider.php`
   - Registers broadcasting routes
   - Loads channel authorization definitions

3. **Modified**: `bootstrap/providers.php`
   - Added `BroadcastServiceProvider` to application providers

4. **Modified**: `.env`
   - Added `BROADCAST_CONNECTION=reverb`

5. **Existing (Verified Correct)**:
   - `resources/js/app.js` - Echo initialization
   - `routes/channels.php` - Channel authorization
   - `app/Events/*.php` - Broadcasting events
   - `docker/common/nginx/default.conf` - WebSocket proxy

## Next Steps

1. **Test WebSocket Connection**: Open device page and check browser console
2. **Verify Real-time Updates**: Send commands and watch for status updates
3. **Monitor Reverb Logs**: Watch for incoming connections and broadcasts
4. **Test Authorization**: Try accessing another user's device (should fail with 403)

## Success Criteria

✅ Browser console shows "✓ WebSocket connected"
✅ No "WebSocket channel error" messages
✅ Command status updates appear in real-time
✅ DevTools shows WS connection with status 101
✅ Reverb logs show successful client connections

## Additional Notes

- **Frontend assets are baked into Docker image during build**
  - Changes to `resources/js/app.js` require rebuild: `docker compose build web php-fpm`
  - `.env` changes require container restart: `docker compose restart`

- **WebSocket path**: `/app/` (not `/apps/` or `/websocket/`)
  - Must match `VITE_REVERB_PATH=/app` in `.env`
  - Nginx proxies this to Reverb server

- **Port handling**:
  - Internal: Reverb runs on `0.0.0.0:8080`
  - External: Client connects via `443` (HTTPS default)
  - `VITE_REVERB_PORT=` (empty) uses default 443

- **TLS/SSL**:
  - External: HTTPS (handled by Synology reverse proxy)
  - Internal: HTTP (Reverb ↔ Nginx within Docker network)
  - `forceTLS: true` in frontend (uses WSS protocol)
