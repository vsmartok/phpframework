<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use PHPFramework\Http\MiddlewareHandler;
use PHPFramework\Http\Request;
use PHPFramework\Http\RequestHandlerInterface;
use PHPFramework\Http\Response;
use PHPFramework\Http\ResponseHeaderMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResponseHeaderMiddleware::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
#[UsesClass(MiddlewareHandler::class)]
class ResponseHeaderMiddlewareTest extends TestCase
{
    public function testHandleAddsHeaderThroughMiddleware(): void
    {
        $next = $this->createStub(RequestHandlerInterface::class);
        $next
            ->method('handle')
            ->willReturn(new Response('test body', 404, ['Content-Type' => 'text/html; charset=utf-8']));

        $middleware = new ResponseHeaderMiddleware('X-App', 'Blog');

        $middlewareHandler = new MiddlewareHandler($middleware, $next);
        $response = $middlewareHandler->handle(new Request('GET', '/'));

        self::assertSame('test body', $response->getBody());
        self::assertSame(404, $response->getStatusCode());
        self::assertSame(
            [
                'content-type' => 'text/html; charset=utf-8',
                'x-app' => 'Blog',
            ],
            $response->getHeaders()
        );
    }
}