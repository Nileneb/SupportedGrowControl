<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;

class ShellyDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'name',
        'shelly_device_id',
        'ip_address',
        'auth_token',
        'model',
        'config',
        'last_webhook_at',
        'last_seen_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'last_webhook_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user that owns this Shelly device.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the linked GrowControl device (optional).
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Verify webhook auth token.
     */
    public function verifyToken(string $token): bool
    {
        return hash_equals($this->auth_token, $token);
    }

    /**
     * Record webhook received.
     */
    public function recordWebhook(): void
    {
        $this->update([
            'last_webhook_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Check if device is Gen2/Plus based on model name.
     */
    public function isGen2(): bool
    {
        $id = strtolower($this->shelly_device_id ?? '');
        $model = strtolower($this->model ?? '');

        return str_contains($id, 'plus')
            || str_contains($id, 'pro')
            || str_contains($model, 'gen2')
            || str_contains($model, 'plus')
            || str_contains($model, 'pro');
    }

    /**
     * Send ON command to Shelly device.
     */
    public function turnOn(): array
    {
        return $this->sendCommand('on');
    }

    /**
     * Send OFF command to Shelly device.
     */
    public function turnOff(): array
    {
        return $this->sendCommand('off');
    }

    /**
     * Send toggle command to Shelly device.
     */
    public function toggle(): array
    {
        return $this->sendCommand('toggle');
    }

    /**
     * Send control command to Shelly device.
     */
    protected function sendCommand(string $action): array
    {
        if (!$this->ip_address) {
            return [
                'success' => false,
                'error' => 'No IP address configured',
            ];
        }

        try {
            if ($this->isGen2()) {
                // Shelly Gen2/Plus API
                $url = "http://{$this->ip_address}/rpc/Switch.Set";
                $params = [
                    'id' => 0,
                    'on' => $action === 'on' ? true : ($action === 'off' ? false : null),
                ];
                if ($action === 'toggle') {
                    $params = ['id' => 0, 'toggle' => true];
                }

                $response = Http::timeout(5)->post($url, $params);
            } else {
                // Shelly Gen1 API
                $url = "http://{$this->ip_address}/relay/0";
                $response = Http::timeout(5)->get($url, ['turn' => $action]);
            }

            $body = $response->json();

            // Update last seen
            $this->update(['last_seen_at' => now()]);

            return [
                'success' => true,
                'response' => $body,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get webhook URL for this Shelly device.
     */
    public function getWebhookUrl(): string
    {
        return route('api.shelly.webhook', ['shelly' => $this->id]) . '?token=' . $this->auth_token;
    }
}
