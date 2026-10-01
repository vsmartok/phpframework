<?php

declare(strict_types=1);

namespace PHPFramework\Http;

interface MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $next): Response;
}