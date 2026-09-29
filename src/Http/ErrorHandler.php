<?php

declare(strict_types=1);

namespace PHPFramework\Http;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

readonly class ErrorHandler implements RequestHandlerInterface
{

    public function __construct(
        private RequestHandlerInterface $next,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->next->handle($request);
        } catch (Throwable $e) {
            $response = new Response(
                'Internal server error',
                500,
                ['Content-Type' => 'text/plain; charset=UTF-8'],
            );

            $this->logger->error(
                'Unhandled exception during request handling.',
                [
                    'exception' => $e,
                    'method' => $request->getMethod(),
                    'path' => $request->getPath(),
                ],
            );
        }

        return $response;
    }
}
