<?php

declare(strict_types=1);

namespace Infomaniak\Ticketing;

/**
 * Outcome of a TicketingApi credential verification.
 */
final class VerificationResult
{
    public function __construct(
        private bool $isConnected,
        private string $errorMessage = '',
        private int $statusCode = 0,
    ) {}

    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    public function errorMessage(): string
    {
        return $this->errorMessage;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
