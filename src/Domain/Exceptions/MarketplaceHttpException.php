<?php

namespace Ziptied\Bedrock\Domain\Exceptions;

use RuntimeException;

class MarketplaceHttpException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode,
        private readonly array $payload = []
    ) {
        parent::__construct($message, $statusCode);
    }

    public function status(): int
    {
        return $this->statusCode;
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
