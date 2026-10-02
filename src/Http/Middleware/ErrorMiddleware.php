<?php

declare(strict_types=1);

namespace PHPFramework\Http\Middleware;

use PHPFramework\Http\Request;
use PHPFramework\Http\RequestHandlerInterface;
use PHPFramework\Http\Response;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

final readonly class ErrorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        try {
            $response = $next->handle($request);
        } catch (Throwable $e) {
            $response = new Response(
                'Internal server error',
                500,
                ['Content-Type' => 'text/plain; charset=utf-8'],
            );

            $this->logger->error(
                'Unhandled exception during request handling.',
                [
                    'exception' => $e,
                    'method' => $request->getMethod(),
                    'path' => $request->getPath(),
                ]
            );
        }

        return $response;
    }
}