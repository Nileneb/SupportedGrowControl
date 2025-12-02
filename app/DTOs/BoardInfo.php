<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

class BoardInfo implements Arrayable
{
    public function __construct(
        public string $id,
        public string $vendor,
        public string $model,
        public string $connection,
        public ?string $firmware = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            vendor: $data['vendor'] ?? '',
            model: $data['model'] ?? '',
            connection: $data['connection'] ?? '',
            firmware: $data['firmware'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'vendor' => $this->vendor,
            'model' => $this->model,
            'connection' => $this->connection,
            'firmware' => $this->firmware,
        ], fn($value) => $value !== null);
    }
}
