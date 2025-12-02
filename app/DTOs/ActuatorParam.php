<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

class ActuatorParam implements Arrayable
{
    public function __construct(
        public string $name,
        public string $type,
        public mixed $min = null,
        public mixed $max = null,
        public ?string $unit = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            min: $data['min'] ?? null,
            max: $data['max'] ?? null,
            unit: $data['unit'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'type' => $this->type,
            'min' => $this->min,
            'max' => $this->max,
            'unit' => $this->unit,
        ], fn($value) => $value !== null);
    }

    public function validateValue(mixed $value): bool
    {
        // Type validation
        if ($this->type === 'int' && !is_int($value) && !ctype_digit((string)$value)) {
            return false;
        }
        if ($this->type === 'float' && !is_numeric($value)) {
            return false;
        }
        if ($this->type === 'string' && !is_string($value)) {
            return false;
        }
        if ($this->type === 'bool' && !is_bool($value)) {
            return false;
        }

        // Range validation for numeric types
        if (($this->type === 'int' || $this->type === 'float') && is_numeric($value)) {
            $numValue = ($this->type === 'int') ? (int)$value : (float)$value;
            if ($this->min !== null && $numValue < $this->min) {
                return false;
            }
            if ($this->max !== null && $numValue > $this->max) {
                return false;
            }
        }

        return true;
    }
}
