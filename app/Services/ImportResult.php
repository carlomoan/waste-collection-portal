<?php

namespace App\Services;

class ImportResult
{
    public function __construct(
        public readonly int $created,
        public readonly int $skipped,
        public readonly array $errors = []
    ) {}

    public function isSuccess(): bool
    {
        return empty($this->errors);
    }

    public function getTotal(): int
    {
        return $this->created + $this->skipped;
    }

    public function getSuccessRate(): float
    {
        if ($this->getTotal() === 0) {
            return 0;
        }

        return ($this->created / $this->getTotal()) * 100;
    }

    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'skipped' => $this->skipped,
            'total' => $this->getTotal(),
            'success_rate' => $this->getSuccessRate(),
            'errors' => $this->errors,
            'success' => $this->isSuccess(),
        ];
    }
}
