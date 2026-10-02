<?php

declare(strict_types=1);

namespace PHPFramework\Http\Middleware;

use PHPFramework\Http\Request;
use PHPFramework\Http\RequestHandlerInterface;
use PHPFramework\Http\Response;

final readonly class ResponseHeaderMiddleware implements MiddlewareInterface
{
    public function __construct(
        private string $name,
        private string $value,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $response = $next->handle($request);

        return $response->withHeader($this->name, $this->value);
    }
}