<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use \InvalidArgumentException;
use PHPFramework\Http\Request;
use PHPFramework\Http\RequestHandlerInterface;
use PHPFramework\Http\Response;
use PHPFramework\Http\ResponseHeaderHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ResponseHeaderHandler::class)]
#[UsesClass(Response::class)]
#[UsesClass(Request::class)]
class ResponseHeaderHandlerTest extends TestCase
{
    public function testHandleMethodCallsTheHandleMethodOnTheNextObjectExactlyOncePassingTheSameRequestObject(): void
    {
        $httpKernelResponse = new Response('test body', 201, ['Content-Type' => 'text/html; charset=utf-8']);
        $request = new Request('GET', '/');

        $next = $this->createMock(RequestHandlerInterface::class);
        $next->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request))
            ->willReturn($httpKernelResponse);


        $this->expectOutputString('');

        $handler = new ResponseHeaderHandler($next, 'X-App', 'Blog');
        $response = $handler->handle($request);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(
            ['content-type' => 'text/html; charset=utf-8', 'x-app' => 'Blog'],
            $response->getHeaders(),
        );
        self::assertSame('test body', $response->getBody());
        self::assertNotSame($response, $httpKernelResponse);

        self::assertSame(201, $httpKernelResponse->getStatusCode());
        self::assertSame(['content-type' => 'text/html; charset=utf-8'], $httpKernelResponse->getHeaders());
        self::assertSame('test body', $httpKernelResponse->getBody());
    }

    public function testHandleMethodDoesNotCatchExceptionsFromTheWithHeaderMethod(): void
    {
        $httpKernelResponse = new Response('test body', 201, ['Content-Type' => 'text/html; charset=utf-8']);
        $request = new Request('GET', '/');

        $next = $this->createMock(RequestHandlerInterface::class);
        $next->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request))
            ->willReturn($httpKernelResponse);

        $handler = new ResponseHeaderHandler($next, 'X App:', "Blog");

        $this->expectException(InvalidArgumentException::class);

        $handler->handle($request);
    }

    public function testHandleMethodDoesNotCatchExceptionsFromTheNextHandler(): void
    {
        $request = new Request('GET', '/');
        $runtimeException = new RuntimeException();

        $next = $this->createMock(RequestHandlerInterface::class);
        $next->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request))
            ->willThrowException($runtimeException);

        $handler = new ResponseHeaderHandler($next, 'X-App', 'Blog');

        try {
            $handler->handle($request);
        } catch (\Throwable $exception) {
            self::assertSame($exception, $runtimeException);
            return;
        }

        self::fail('RuntimeException should have been thrown.');
    }
}