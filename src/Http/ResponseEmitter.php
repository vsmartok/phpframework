<?php

declare(strict_types=1);

namespace PHPFramework\Http;

final class ResponseEmitter
{
    public function emit(Response $response): void
    {
        http_response_code($response->getStatusCode());
        echo $response->getBody();
    }
}