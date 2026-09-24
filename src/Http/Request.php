<?php

declare(strict_types=1);

namespace PHPFramework\Http;

use InvalidArgumentException;

final readonly class Request
{
    private string $method;

    private string $path;

    /**
     * @var array<array-key,mixed>
     */
    private array $queryParams;

    /**
     * @param array<array-key,mixed> $queryParams
     */
    public function __construct(
        string $method,
        string $path,
        array $queryParams = [],
    ) {
        if ($method === '' || !preg_match('/^[A-Za-z]+$/D', $method)) {
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
        $this->queryParams = $queryParams;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return array<array-key,mixed>
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        return array_key_exists($name, $this->queryParams) ? $this->queryParams[$name] : $default;
    }
}
