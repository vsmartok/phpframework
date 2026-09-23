<?php

declare(strict_types=1);

namespace PHPFramework\Http;

use PHPFramework\Routing\RouteNotFoundException;
use PHPFramework\Routing\Router;

final class HttpKernel
{
    public function __construct(
        private readonly Router $router,
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->router->dispatch($request);
        } catch (RouteNotFoundException) {
            $response = new Response('Page not found', 404);
        }

        return $response;
    }
}