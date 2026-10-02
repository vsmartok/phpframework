<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use InvalidArgumentException;
use PHPFramework\Http\Middleware\ErrorMiddleware;
use PHPFramework\Http\Request;
use PHPFramework\Http\RequestHandlerInterface;
use PHPFramework\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use TypeError;

#[CoversClass(ErrorMiddleware::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
class ErrorMiddlewareTest extends TestCase
{
    #[DataProvider('responsesDataProvider')]
    public function testProcessMethodPassesTheSameRequestToTheNextHandlerAndReturnsTheHandlersResponse(Response $expectedResponse): void
    {
        $request = new Request('POST', '/path/to');

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('error');

        $next = $this->createMock(RequestHandlerInterface::class);
        $next
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request))
            ->willReturn($expectedResponse);

        $errorMiddleware = new ErrorMiddleware($logger);
        $response = $errorMiddleware->process($request, $next);

        self::assertSame($expectedResponse, $response);
    }

    public static function responsesDataProvider(): array
    {
        return [
            'response: 201' => [
                new Response('test response', 201, ['Content-Type' => 'text/html; charset=utf-8']),
            ],
            'response: 404' => [
                new Response('test response', 404, ['Content-Type' => 'text/html; charset=utf-8']),
            ],
            'response: 500' => [
                new Response('test response', 500, ['Content-Type' => 'text/html; charset=utf-8']),
            ],
        ];
    }

    #[DataProvider('exceptionsAndErrorsDataProvider')]
    public function testProcessMethodHandlesExceptionsAndErrorsThrownByTheNextHandler(Throwable $exception): void
    {
        $request = new Request('POST', '/path/to');

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->identicalTo('Unhandled exception during request handling.'),
                $this->identicalTo([
                    'exception' => $exception,
                    'method' => $request->getMethod(),
                    'path' => $request->getPath(),
                ]),
            );

        $next = $this->createMock(RequestHandlerInterface::class);
        $next
            ->expects($this->once())
            ->method('handle')
            ->willThrowException($exception);

        $errorMiddleware = new ErrorMiddleware($logger);
        $response = $errorMiddleware->process($request, $next);

        self::assertSame('Internal server error', $response->getBody());
        self::assertSame(500, $response->getStatusCode());
        self::assertSame(['content-type' => 'text/plain; charset=utf-8'], $response->getHeaders());
    }

    public static function exceptionsAndErrorsDataProvider(): array
    {
        return [
            RuntimeException::class => [new RuntimeException('test exception')],
            InvalidArgumentException::class => [new InvalidArgumentException('test exception')],
            TypeError::class => [new TypeError('test exception')],
        ];
    }

    public function testProcessMethodPropagatesLoggerExceptions(): void
    {
        $exception = new RuntimeException('test exception');

        $request = new Request('POST', '/path/to');

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->willThrowException($exception);

        $next = $this->createMock(RequestHandlerInterface::class);
        $next
            ->expects($this->once())
            ->method('handle')
            ->willThrowException(new RuntimeException());

        $errorMiddleware = new ErrorMiddleware($logger);

        try {
            $errorMiddleware->process($request, $next);
        } catch (Throwable $e) {
            self::assertSame($exception, $e);
            return;
        }

        self::fail('Exception should have been thrown');
    }

    public function testProcessReturnsSuccessfulResponseAfterPreviousFailure(): void
    {
        $requestWithError = new Request('POST', '/path/to');
        $requestWithoutError = new Request('GET', '/path/to');
        $expectedResponse = new Response('test response', 201, ['Content-Type' => 'text/html; charset=utf-8']);

        $next = $this->createMock(RequestHandlerInterface::class);
        $next
            ->expects($this->exactly(2))
            ->method('handle')
            ->willReturnCallback(function (Request $request) use (
                $requestWithError,
                $requestWithoutError,
                $expectedResponse,
            ): Response {
                if ($requestWithoutError === $request) {
                    return $expectedResponse;
                }

                if ($requestWithError === $request) {
                    throw new RuntimeException('An exception was thrown.');
                }

                self::fail('Exception should have been thrown.');
            });

        $errorMiddleware = new ErrorMiddleware();
        $responseOne = $errorMiddleware->process($requestWithError, $next);
        $responseTwo = $errorMiddleware->process($requestWithoutError, $next);

        self::assertSame('test response', $responseTwo->getBody());
        self::assertSame('Internal server error', $responseOne->getBody());
        self::assertSame(201, $responseTwo->getStatusCode());
        self::assertSame(500, $responseOne->getStatusCode());
        self::assertSame([
            'content-type' => 'text/html; charset=utf-8',
        ], $responseTwo->getHeaders());
        self::assertSame([
            'content-type' => 'text/plain; charset=utf-8',
        ], $responseOne->getHeaders());
    }
}

