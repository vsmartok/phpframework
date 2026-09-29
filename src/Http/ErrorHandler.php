<?php

declare(strict_types=1);

namespace PHPFramework\Http;

use Throwable;

readonly class ErrorHandler implements RequestHandlerInterface
{

    public function __construct(
        private RequestHandlerInterface $next,
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->next->handle($request);
        } catch (Throwable) {
            $response = new Response(
                'Internal server error',
                500,
                ['Content-Type' => 'text/plain; charset=UTF-8'],
            );
        }

        return $response;
    }
}
