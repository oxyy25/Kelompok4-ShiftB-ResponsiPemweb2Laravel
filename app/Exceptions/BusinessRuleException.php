<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Pelanggaran aturan bisnis (jadwal bentrok, transisi status tidak valid, dll).
 * Dirender di bootstrap/app.php menjadi response JSON seragam via ApiResponse.
 */
class BusinessRuleException extends RuntimeException
{
    public function __construct(
        string $message,
        protected int $status = 409,
        protected ?array $errors = null,
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errors(): ?array
    {
        return $this->errors;
    }
}
