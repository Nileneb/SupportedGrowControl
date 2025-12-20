<?php

namespace App\Http\Controllers\Api;

use App\Events\VideoFrameReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class VideoStreamController extends Controller
{
    /**
     * Empfängt Video-Frames per WebSocket-ähnlichem POST (Platzhalter für WS-Handler)
     *
     * POST /api/growdash/agent/video-frame
     *
     * Body:
     * {
     *   "event": "video_frame",
     *   "device": "<device_public_id>",
     *   "frame": "<base64-jpeg>",
     *   "timestamp": 1700000000.123
     * }
     */
    public function receiveFrame(Request $request)
    {
        $validated = $request->validate([
            'event' => 'required|in:video_frame',
            'device' => 'required|string',
            'frame' => 'required|string',
            'timestamp' => 'required|numeric',
        ]);

        // Authentifizierung: Device-Token prüfen (wie bei anderen Agent-Events)
        // Annahme: Middleware device.auth prüft X-Device-ID + X-Device-Token
        $devicePublicId = $validated['device'];
        $frame = $validated['frame'];
        $timestamp = (float) $validated['timestamp'];

        // Frame broadcasten
        broadcast(new VideoFrameReceived($devicePublicId, $frame, $timestamp))->toOthers();

        // Letzte N Frames in Redis als Ringpuffer speichern (z.B. 20 Frames)
        $redisKey = 'video:frames:' . $devicePublicId;
        $maxFrames = 20;
        $frameData = json_encode([
            'frame' => $frame,
            'timestamp' => $timestamp,
        ]);
        // Push to Redis list (right), trim to max N
        Redis::connection('cache')->rpush($redisKey, $frameData);
        Redis::connection('cache')->ltrim($redisKey, -$maxFrames, -1);
        // Optional: Set expiry (z.B. 2 Minuten)
        Redis::connection('cache')->expire($redisKey, 120);

        // Zusätzlich: Letzten Frame für schnellen Zugriff speichern
        Cache::put('video:last_frame:' . $devicePublicId, [
            'frame' => $frame,
            'timestamp' => $timestamp,
        ], 60);

        return response()->json(['success' => true]);
    }
}
