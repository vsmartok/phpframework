<?php

declare(strict_types=1);

namespace PHPFramework\Http;

use InvalidArgumentException;

final class RequestFactory
{
    public function fromServer(array $server): Request
    {
        $method = $server['REQUEST_METHOD'] ?? null;
        if (!is_string($method) || $method === '') {
            throw new InvalidArgumentException(
                'Cannot create request: "REQUEST_METHOD" is missing or empty in the server parameters.',
            );
        }

        $uri = $server['REQUEST_URI'] ?? null;
        if (!is_string($uri) || $uri === '' || !str_starts_with($uri, '/') || str_contains($uri, '#')) {
            throw new InvalidArgumentException(
                'Cannot create request: "REQUEST_URI" is missing, empty, contain "#" or does not start with "/".'
            );
        }

        $path = explode('?', $uri, 2)[0];

        return new Request($method, $path);
    }
}
