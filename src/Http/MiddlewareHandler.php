<?php

declare(strict_types=1);

namespace PHPFramework\Http;

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
