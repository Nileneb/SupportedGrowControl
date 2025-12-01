<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'ip_address',
        'serial_port',
    ];

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
}
