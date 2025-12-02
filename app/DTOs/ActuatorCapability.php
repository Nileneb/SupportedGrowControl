<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

class ActuatorCapability implements Arrayable
{
    /** @var ActuatorParam[] */
    public array $params;

    public function __construct(
        public string $id,
        public string $display_name,
        public string $category,
        public string $command_type,
        array $params = [],
        public ?int $min_interval = null,
        public bool $critical = false,
    ) {
        $this->params = array_map(
            fn($param) => $param instanceof ActuatorParam ? $param : ActuatorParam::fromArray($param),
            $params
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            display_name: $data['display_name'],
            category: $data['category'],
            command_type: $data['command_type'],
            params: $data['params'] ?? [],
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
            'command_type' => $this->command_type,
            'params' => array_map(fn($p) => $p->toArray(), $this->params),
            'min_interval' => $this->min_interval,
            'critical' => $this->critical,
        ], fn($value) => $value !== null || is_bool($value) || is_array($value));
    }

    public function validateParams(array $providedParams): array
    {
        $errors = [];

        // Check for missing required params
        foreach ($this->params as $paramDef) {
            if (!array_key_exists($paramDef->name, $providedParams)) {
                $errors[$paramDef->name] = "Missing required parameter: {$paramDef->name}";
            } else {
                // Validate the value
                if (!$paramDef->validateValue($providedParams[$paramDef->name])) {
                    $errors[$paramDef->name] = "Invalid value for parameter: {$paramDef->name}";
                }
            }
        }

        return $errors;
    }
}
