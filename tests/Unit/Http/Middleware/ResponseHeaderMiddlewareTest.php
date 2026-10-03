<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use InvalidArgumentException;
use PHPFramework\Http\Middleware\ResponseHeaderMiddleware;
use PHPFramework\Http\Request;
use PHPFramework\Http\RequestHandlerInterface;
use PHPFramework\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

#[CoversClass(ResponseHeaderMiddleware::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
class ResponseHeaderMiddlewareTest extends TestCase
{
    public function testProcessMethodPassesTheSameRequestToTheNextHandlerExactlyOnce(): void
    {
        $request = new Request('POST', '/home');

        $next = $this->createMock(RequestHandlerInterface::class);
        $next
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request))
            ->willReturn(new Response(
                'test body',
                201,
                ['Content-Type' => 'text/html; charset=utf-8'],
            ));

        $middleware = new ResponseHeaderMiddleware('X-App', 'Blog');
        $response = $middleware->process($request, $next);

        self::assertSame('test body', $response->getBody());
        self::assertSame(201, $response->getStatusCode());
        self::assertSame(
            [
                'content-type' => 'text/html; charset=utf-8',
                'x-app' => 'Blog',
            ],
            $response->getHeaders(),
        );
    }

    #[DataProvider('statusCodesForResponsesDataProvider')]
    public function testProcessMethodTheExistingHeaderIsReplacedWithoutRegardToTheCaseOfTheName(int $statusCode): void
    {
        $response = new Response(
            'test body',
            $statusCode,
            [
                'Content-Type' => 'text/html; charset=utf-8',
                'X-Environment' => 'local',
            ],
        );

        $next = $this->createStub(RequestHandlerInterface::class);
        $next
            ->method('handle')
            ->willReturn($response);

        $middleware = new ResponseHeaderMiddleware('X-EnvironmenT', 'remote');

        $receivedResponse = $middleware->process(new Request('GET', '/'), $next);

        self::assertNotSame($receivedResponse, $response);

        self::assertSame('test body', $receivedResponse->getBody());
        self::assertSame($statusCode, $receivedResponse->getStatusCode());
        self::assertSame(
            [
                'content-type' => 'text/html; charset=utf-8',
                'x-environment' => 'remote',
            ],
            $receivedResponse->getHeaders(),
        );

        self::assertSame('test body', $response->getBody());
        self::assertSame($statusCode, $response->getStatusCode());
        self::assertSame(
            [
                'content-type' => 'text/html; charset=utf-8',
                'x-environment' => 'local',
            ],
            $response->getHeaders(),
        );
    }

    public static function statusCodesForResponsesDataProvider(): array
    {
        return [
            '200' => [200],
            '201' => [201],
            '404' => [404],
            '500' => [500],
        ];
    }

    public function testProcessMethodPropagatesTheSameExceptionFromTheNextHandler(): void
    {
        $nextException = new RuntimeException('An error occurred');

        $next = $this->createStub(RequestHandlerInterface::class);
        $next
            ->method('handle')
            ->willThrowException($nextException);

        $middleware = new ResponseHeaderMiddleware('X-App', 'Blog');

        try {
            $middleware->process(new Request('GET', '/'), $next);
        } catch (Throwable $e) {
            self::assertSame($nextException, $e);
            return;
        }

        self::fail('Exception should have been thrown');
    }

    public function testProcessMethodDoesNotCatchExceptionsFromTheWithHeaderMethod(): void
    {
        $next = $this->createStub(RequestHandlerInterface::class);
        $next
            ->method('handle')
            ->willReturn(new Response());

        $middleware = new ResponseHeaderMiddleware('X App', 'Blog');

        $this->expectException(InvalidArgumentException::class);
        $middleware->process(new Request('GET', '/'), $next);
    }
}
