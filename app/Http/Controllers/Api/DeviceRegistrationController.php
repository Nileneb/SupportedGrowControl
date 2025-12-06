<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceRegistrationController extends Controller
{
    /**
     * Register or claim a device directly using an authenticated user (Sanctum).
     * POST /api/growdash/devices/register-from-agent
     * Body: { bootstrap_id, name, board_type?, capabilities?, regenerate_token?, revoke_user_token? }
     */
    public function registerFromAgent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bootstrap_id' => 'required|string|max:64',
            'name' => 'required|string|max:255',
            'board_type' => 'nullable|string|max:50',
            'regenerate_token' => 'sometimes|boolean',
            'revoke_user_token' => 'sometimes|boolean',
        ]);

        $user = $request->user();
        $existing = Device::findByBootstrapId($data['bootstrap_id']);
        $reused = false;
        $plaintextToken = null;

        if ($existing) {
            // Conflict if owned by another user.
            if ($existing->isPaired() && $existing->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device already paired to another user.',
                ], 409);
            }

            // Pair if unclaimed
            if (!$existing->isPaired()) {
                $plaintextToken = $existing->pairWithUser($user->id);
            } else {
                // Optionally regenerate token
                if (!empty($data['regenerate_token'])) {
                    $plaintextToken = Str::random(64);
                    $existing->agent_token = hash('sha256', $plaintextToken);
                    $existing->save();
                }
            }

            // Update meta fields
            $existing->name = $data['name'];
            $existing->board_type = $data['board_type'] ?? $existing->board_type;
            $existing->save();
            $device = $existing;
            $reused = true;
        } else {
            // Fresh device instance
            $device = new Device();
            $device->bootstrap_id = $data['bootstrap_id'];
            $device->name = $data['name'];
            $device->slug = Str::slug($data['name']) . '-' . substr($data['bootstrap_id'], 0, 6);
            $device->board_type = $data['board_type'] ?? null;
            $device->user_id = $user->id;
            $device->paired_at = now();
            $device->public_id = (string) Str::uuid();
            $plaintextToken = Str::random(64);
            $device->agent_token = hash('sha256', $plaintextToken);
            $device->status = 'paired';
            $device->save();
        }

        // Optionally revoke the user token (hardening)
        if (!empty($data['revoke_user_token']) && $request->user()->currentAccessToken()) {
            $token = $request->user()->currentAccessToken();
            if ($token) {
                \Laravel\Sanctum\PersonalAccessToken::where('id', $token->id)->delete();
            }
        }

        return response()->json([
            'success' => true,
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'public_id' => $device->public_id,
                'bootstrap_id' => $device->bootstrap_id,
                'paired_at' => optional($device->paired_at)->toIso8601String(),
                'reused' => $reused,
            ],
            'agent_token' => $plaintextToken, // Plaintext (only time!)
        ], $reused ? 200 : 201);
    }
}
