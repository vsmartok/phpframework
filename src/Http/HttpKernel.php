<?php

declare(strict_types=1);

namespace PHPFramework\Http;

use PHPFramework\Routing\MethodNotAllowedException;
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
            $response = new Response('Page not found', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
        } catch (MethodNotAllowedException $e) {
            $response = new Response(
                'Method not allowed', 
                405, 
                [
                    'Allow' => implode(', ', $e->getAllowedMethods()),
                    'Content-Type' => 'text/plain; charset=UTF-8',
                ],
            );
        }

        return $response;
    }
}