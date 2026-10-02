<?php

declare(strict_types=1);

namespace PHPFramework\Http\Middleware;

use PHPFramework\Http\Request;
use PHPFramework\Http\RequestHandlerInterface;
use PHPFramework\Http\Response;

interface MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $next): Response;
}