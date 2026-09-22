<?php

declare(strict_types=1);

namespace PHPFramework\Http;

use InvalidArgumentException;

final readonly class Request
{
    private string $method;

    private string $path;

    public function __construct(
        string $method,
        string $path,
    ) {
        if ($method === '' || !preg_match('/^[A-Za-z]+$/', $method)) {
            throw new InvalidArgumentException(
                sprintf('HTTP method must be a non-empty string containing only letters, "%s" given.', $method),
            );
        }

        if ($path === '' || !str_starts_with($path, '/') || strpbrk($path, '?#') !== false) {
            throw new InvalidArgumentException(
                sprintf('The request path must be a non-empty string starting with "/" and must not contain "?" or "#", "%s" given.', $path),
            );
        }

        $this->method = strtoupper($method);
        $this->path = $path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
