<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Device extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'public_id',
        'name',
        'slug',
        'ip_address',
        'serial_port',
        'bootstrap_id',
        'bootstrap_code',
        'agent_token',
        'device_info',
        'status',
        'paired_at',
        'last_seen_at',
        'board_type',
        'capabilities',
        'last_state',
        'shelly_device_id',
        'shelly_auth_token',
        'shelly_config',
        'shelly_last_webhook_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'agent_token',
        'device_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'paired_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'capabilities' => 'array',
            'last_state' => 'array',
            'device_info' => 'array',
            'shelly_config' => 'array',
            'shelly_last_webhook_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate public_id and agent_token only when pairing (not on bootstrap)
        static::creating(function ($device) {
            // Generate bootstrap_code if bootstrap_id is set but code is missing
            if (!empty($device->bootstrap_id) && empty($device->bootstrap_code)) {
                $device->bootstrap_code = static::generateBootstrapCode();
            }
        });
    }

    /**
     * Get the route key name for Laravel route model binding.
     * Use public_id instead of id for all route bindings.
     */
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * Generate a unique 6-digit bootstrap code.
     */
    public static function generateBootstrapCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (static::where('bootstrap_code', $code)->exists());

        return $code;
    }

    /**
     * Pair this device with a user.
     * Returns plaintext token (only time it's available!).
     */
    public function pairWithUser(int $userId): string
    {
        $this->user_id = $userId;
        $this->public_id = $this->public_id ?? (string) Str::uuid();

        // Generate plaintext token and store only SHA256 hash
        $plaintextToken = Str::random(64);
        $this->agent_token = hash('sha256', $plaintextToken);
        $this->paired_at = now();

        $this->save();

        // Return plaintext token (never stored, never retrievable again)
        return $plaintextToken;
    }

    /**
     * Check if device is paired.
     */
    public function isPaired(): bool
    {
        return !is_null($this->user_id) && !is_null($this->paired_at);
    }

    /**
     * Check if device is unclaimed.
     */
    public function isUnclaimed(): bool
    {
        return is_null($this->user_id);
    }

    /**
     * Get the user that owns this device.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Users with shared access via pivot.
     */
    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'users_devices')
            ->withTimestamps()
            ->withPivot('role');
    }



    /**
     * Get all commands for this device.
     */
    public function commands(): HasMany
    {
        return $this->hasMany(Command::class);
    }



    /**
     * Get all configured sensors for this device (manual wizard instances).
     */
    public function deviceSensors(): HasMany
    {
        return $this->hasMany(DeviceSensor::class);
    }

    /**
     * Get all configured actuators for this device (manual wizard instances).
     */
    public function deviceActuators(): HasMany
    {
        return $this->hasMany(DeviceActuator::class);
    }

    /**
     * Get all water level measurements for this device.
     */
    public function waterLevels(): HasMany
    {
        return $this->hasMany(WaterLevel::class);
    }

    /**
     * Get all TDS readings for this device.
     */
    public function tdsReadings(): HasMany
    {
        return $this->hasMany(TdsReading::class);
    }

    /**
     * Get all temperature readings for this device.
     */
    public function temperatureReadings(): HasMany
    {
        return $this->hasMany(TemperatureReading::class);
    }

    /**
     * Get all spray events for this device.
     */
    public function sprayEvents(): HasMany
    {
        return $this->hasMany(SprayEvent::class);
    }

    /**
     * Get all fill events for this device.
     */
    public function fillEvents(): HasMany
    {
        return $this->hasMany(FillEvent::class);
    }

    /**
     * Get all system status snapshots for this device.
     */
    public function systemStatuses(): HasMany
    {
        return $this->hasMany(SystemStatus::class);
    }

    /**
     * Get all Arduino logs for this device.
     */
    public function arduinoLogs(): HasMany
    {
        return $this->hasMany(ArduinoLog::class);
    }

    /**
     * Get the latest system status for this device.
     */
    public function latestStatus(): ?\App\Models\SystemStatus
    {
        return $this->systemStatuses()->latest('measured_at')->first();
    }

    /**
     * Scope: Only devices belonging to the authenticated user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Only unclaimed devices.
     */
    public function scopeUnclaimed($query)
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope: Only paired devices.
     */
    public function scopePaired($query)
    {
        return $query->whereNotNull('user_id')->whereNotNull('paired_at');
    }

    /**
     * Find device by bootstrap_id.
     */
    public static function findByBootstrapId(string $bootstrapId): ?self
    {
        return static::where('bootstrap_id', $bootstrapId)->first();
    }

    /**
     * Find device by bootstrap_code.
     */
    public static function findByBootstrapCode(string $code): ?self
    {
        return static::where('bootstrap_code', $code)->unclaimed()->first();
    }

    /**
     * Find device by public_id (for API access).
     */
    public static function findByPublicId(string $publicId): ?self
    {
        return static::where('public_id', $publicId)->first();
    }

    /**
     * Verify agent token against stored SHA256 hash.
     */
    public function verifyAgentToken(string $plaintextToken): bool
    {
        if ($this->agent_token === null) {
            return false;
        }

        return hash_equals($this->agent_token, hash('sha256', $plaintextToken));
    }

    /**
     * Check if device has Shelly integration configured.
     */
    public function hasShellyIntegration(): bool
    {
        return !is_null($this->shelly_device_id) && !is_null($this->shelly_auth_token);
    }

    /**
     * Verify Shelly webhook token.
     */
    public function verifyShellyToken(string $token): bool
    {
        if ($this->shelly_auth_token === null) {
            return false;
        }

        return hash_equals($this->shelly_auth_token, $token);
    }

    /**
     * Update last webhook timestamp.
     */
    public function recordShellyWebhook(): void
    {
        $this->update(['shelly_last_webhook_at' => now()]);
    }


}
