<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

class SensorCapability implements Arrayable
{
    public function __construct(
        public string $id,
        public string $display_name,
        public string $category,
        public string $unit,
        public string $value_type,
        public ?array $range = null,
        public ?int $min_interval = null,
        public bool $critical = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            display_name: $data['display_name'],
            category: $data['category'],
            unit: $data['unit'],
            value_type: $data['value_type'],
            range: $data['range'] ?? null,
            min_interval: $data['min_interval'] ?? null,
            critical: $data['critical'] ?? false,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'display_name' => $this->display_name,
            'category' => $this->category,
            'unit' => $this->unit,
            'value_type' => $this->value_type,
            'range' => $this->range,
            'min_interval' => $this->min_interval,
            'critical' => $this->critical,
        ], fn($value) => $value !== null || is_bool($value));
    }

    public function validateValue(mixed $value): bool
    {
        // Type validation
        if ($this->value_type === 'float' && !is_numeric($value)) {
            return false;
        }
        if ($this->value_type === 'int' && !is_int($value) && !ctype_digit((string)$value)) {
            return false;
        }

        // Range validation
        if ($this->range !== null) {
            $numValue = (float)$value;
            [$min, $max] = $this->range;
            if ($numValue < $min || $numValue > $max) {
                return false;
            }
        }

        return true;
    }
}
