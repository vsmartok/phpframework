<?php

declare(strict_types=1);

namespace PHPFramework\Http\Middleware;

use PHPFramework\Http\Request;
use PHPFramework\Http\RequestHandlerInterface;
use PHPFramework\Http\Response;

final readonly class MiddlewareHandler implements RequestHandlerInterface
{
    public function __construct(
        private MiddlewareInterface $middleware,
        private RequestHandlerInterface $next,
    ) {
    }

    public function handle(Request $request): Response
    {
        return $this->middleware->process($request, $this->next);
    }
}
