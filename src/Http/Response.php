<?php

declare(strict_types=1);

namespace PHPFramework\Http;

use InvalidArgumentException;

final readonly class Response
{
    public function __construct(
        private string $body = '', 
        private int $statusCode = 200
    ) {
        if ($statusCode < 100 || $statusCode > 599) {
            throw new InvalidArgumentException(
                sprintf('Invalid HTTP status code: %d. Code must be an integer between 100 and 599.', $statusCode),
            );
        }
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}