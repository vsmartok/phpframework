<?php

namespace Tests\Unit\Http;

use PHPFramework\Http\MiddlewareHandler;
use PHPFramework\Http\MiddlewareInterface;
use PHPFramework\Http\Request;
use PHPFramework\Http\RequestHandlerInterface;
use PHPFramework\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(MiddlewareHandler::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
class MiddlewareHandlerTest extends TestCase
{
    public function testHandleMethodCallsProcessOnTheMiddlewareExactlyOncePassingTheSameRequestAndTheSameNextHandler(): void
    {
        $request = new Request('POST', '/home');
        $expectedResponse = new Response(
            'test body',
            201,
            ['Content-Type' => 'text/html; charset=utf-8'],
        );

        $next = $this->createStub(RequestHandlerInterface::class);


        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($request), $this->identicalTo($next))
            ->willReturn($expectedResponse);

        $middlewareHandler = new MiddlewareHandler($middleware, $next);
        $response = $middlewareHandler->handle($request);

        self::assertSame($expectedResponse, $response);
    }

    public function testHandleMethodDoesNotCatchExceptions(): void
    {
        $expectedException = new RuntimeException('An error occurred');

        $next = $this->createStub(RequestHandlerInterface::class);
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware
            ->expects($this->once())
            ->method('process')
            ->willThrowException($expectedException);

        $middlewareHandler = new MiddlewareHandler($middleware, $next);

        try {
            $middlewareHandler->handle(new Request('POST', '/home'));
        } catch (RuntimeException $e) {
            self::assertSame($expectedException, $e);
            return;
        }

        self::fail('Exception should have been thrown');
    }

    public function testHandleMethodIfTheMiddlewareReturnsResponseItselfTheNextHandlerIsNotCalled(): void
    {
        $next = $this->createMock(RequestHandlerInterface::class);
        $next
            ->expects($this->never())
            ->method('handle');

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware
            ->expects($this->once())
            ->method('process')
            ->willReturn(new Response());

        $middlewareHandler = new MiddlewareHandler($middleware, $next);
        $middlewareHandler->handle(new Request('POST', '/home'));
    }
}
