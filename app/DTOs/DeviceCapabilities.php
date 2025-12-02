<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

class DeviceCapabilities implements Arrayable
{
    public ?BoardInfo $board = null;

    /** @var SensorCapability[] */
    public array $sensors = [];

    /** @var ActuatorCapability[] */
    public array $actuators = [];

    public function __construct(array $data = [])
    {
        if (isset($data['board'])) {
            $this->board = $data['board'] instanceof BoardInfo
                ? $data['board']
                : BoardInfo::fromArray($data['board']);
        }

        if (isset($data['sensors'])) {
            $this->sensors = array_map(
                fn($sensor) => $sensor instanceof SensorCapability ? $sensor : SensorCapability::fromArray($sensor),
                $data['sensors']
            );
        }

        if (isset($data['actuators'])) {
            $this->actuators = array_map(
                fn($actuator) => $actuator instanceof ActuatorCapability ? $actuator : ActuatorCapability::fromArray($actuator),
                $data['actuators']
            );
        }
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return array_filter([
            'board' => $this->board?->toArray(),
            'sensors' => array_map(fn($s) => $s->toArray(), $this->sensors),
            'actuators' => array_map(fn($a) => $a->toArray(), $this->actuators),
        ], fn($value) => $value !== null);
    }

    public function getSensorById(string $sensorId): ?SensorCapability
    {
        foreach ($this->sensors as $sensor) {
            if ($sensor->id === $sensorId) {
                return $sensor;
            }
        }
        return null;
    }

    public function getActuatorById(string $actuatorId): ?ActuatorCapability
    {
        foreach ($this->actuators as $actuator) {
            if ($actuator->id === $actuatorId) {
                return $actuator;
            }
        }
        return null;
    }

    public function getSensorsByCategory(string $category): array
    {
        return array_filter($this->sensors, fn($s) => $s->category === $category);
    }

    public function getActuatorsByCategory(string $category): array
    {
        return array_filter($this->actuators, fn($a) => $a->category === $category);
    }

    public function getCriticalSensors(): array
    {
        return array_filter($this->sensors, fn($s) => $s->critical);
    }

    public function getCriticalActuators(): array
    {
        return array_filter($this->actuators, fn($a) => $a->critical);
    }

    public function getAllCategories(): array
    {
        $categories = [];
        foreach ($this->sensors as $sensor) {
            $categories[$sensor->category] = true;
        }
        foreach ($this->actuators as $actuator) {
            $categories[$actuator->category] = true;
        }
        return array_keys($categories);
    }
}
