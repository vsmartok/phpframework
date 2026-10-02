<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Middleware;

use PHPFramework\Http\Middleware\ErrorMiddleware;
use PHPFramework\Http\Middleware\MiddlewareChainBuilder;
use PHPFramework\Http\Middleware\MiddlewareHandler;
use PHPFramework\Http\Middleware\ResponseHeaderMiddleware;
use PHPFramework\Http\Request;
use PHPFramework\Http\RequestHandlerInterface;
use PHPFramework\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ErrorMiddleware::class)]
#[UsesClass(Response::class)]
#[UsesClass(Request::class)]
#[UsesClass(MiddlewareChainBuilder::class)]
#[UsesClass(MiddlewareHandler::class)]
#[UsesClass(ResponseHeaderMiddleware::class)]
class ErrorMiddlewareTest extends TestCase
{
    public function testErrorResponseReceivesOuterMiddlewareHeaders(): void
    {
        $request = new Request('POST', '/path/to');

        $last = $this->createStub(RequestHandlerInterface::class);
        $last
            ->method('handle')
            ->willThrowException(new RuntimeException('An error occurred'));

        $middlewares = [
            new ResponseHeaderMiddleware('X-Environment', 'local'),
            new ResponseHeaderMiddleware('X-App', 'Blog'),
            new ErrorMiddleware(),
        ];

        $builder = new MiddlewareChainBuilder();
        $handler  = $builder->build($last, ...$middlewares);

        $response = $handler->handle($request);

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('Internal server error', $response->getBody());
        self::assertSame(
            [
                'content-type' => 'text/plain; charset=utf-8',
                'x-app' => 'Blog',
                'x-environment' => 'local',
            ],
            $response->getHeaders(),
        );
    }
}
