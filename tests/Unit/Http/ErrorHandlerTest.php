<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use InvalidArgumentException;
use LogicException;
use PHPFramework\Http\ErrorHandler;
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

#[CoversClass(ErrorHandler::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
class ErrorHandlerTest extends TestCase
{
    #[DataProvider('responseToTestLoggerNever')]
    public function testHandleMethodPassesTheSameRequestToTheNextHandlerExactlyOnce(Response $response, array $expectedData): void
    {
        $request = new Request('GET', '/');

        $nextMock = $this->createMock(RequestHandlerInterface::class);
        $nextMock->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request))
            ->willReturn($response);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('error');

        $errorHandler = new ErrorHandler($nextMock, $logger);
        $response = $errorHandler->handle($request);

        self::assertSame($expectedData[0], $response->getBody());
        self::assertSame($expectedData[1], $response->getStatusCode());
        self::assertSame($expectedData[2], $response->getHeaders());
    }

    public static function responseToTestLoggerNever(): array
    {
        return [
            'response 201' => [
                new Response('response 201', 201, ['Content-Type' => 'text/html; charset=utf-8']),
                ['response 201', 201, ['content-type' => 'text/html; charset=utf-8']],
            ],
            'response 400' => [
                new Response('response 400', 400, ['Content-Type' => 'text/plain; charset=utf-8']),
                ['response 400', 400, ['content-type' => 'text/plain; charset=utf-8']],
            ],
            'response 404' => [
                new Response('response 404', 404, ['Content-Type' => 'text/plain; charset=utf-8']),
                ['response 404', 404, ['content-type' => 'text/plain; charset=utf-8']],
            ],
            'response 500' => [
                new Response('response 500', 500, ['Content-Type' => 'text/plain; charset=utf-8']),
                ['response 500', 500, ['content-type' => 'text/plain; charset=utf-8']],
            ],
        ];
    }

    public function testHandleMethodUponSuccessfulExecutionItReturnsTheSameResponseObjectWithoutModifyingIt(): void
    {
        $response = new Response('test body', 404, ['Content-Type' => 'text/plain; charset=utf-8']);

        $nextMock = $this->createMock(RequestHandlerInterface::class);
        $nextMock
            ->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $errorHandler = new ErrorHandler($nextMock);
        $receivedResponse = $errorHandler->handle(new Request('GET', '/'));

        self::assertSame($receivedResponse, $response);
    }

    #[DataProvider('nextHandlerExceptions')]
    public function testHandleMethodReturnsAnInternalServerErrorResponseIfTheNextHandlerThrowsThrowable(Throwable $exception): void
    {
        $next = $this->createMock(RequestHandlerInterface::class);
        $next
            ->expects($this->once())
            ->method('handle')
            ->willThrowException($exception);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->identicalTo('Unhandled exception during request handling.'),
                $this->identicalTo([
                    'exception' => $exception,
                    'method' => 'POST',
                    'path' => '/articles',
                ]),
            );

        $errorHandler = new ErrorHandler($next, $logger);
        $response = $errorHandler->handle(new Request('POST', '/articles'));

        self::assertSame(500, $response->getStatusCode());
        self::assertSame(['content-type' => 'text/plain; charset=UTF-8'], $response->getHeaders());
        self::assertSame('Internal server error', $response->getBody());
    }

    public static function nextHandlerExceptions(): array
    {
        return [
            'throw ' . RuntimeException::class => [new RuntimeException('An exception was thrown')],
            'throw ' . TypeError::class => [new TypeError('An exception was thrown')],
            'throw ' . InvalidArgumentException::class => [new InvalidArgumentException('An exception was thrown')],
            'throw ' . LogicException::class => [new LogicException('An exception was thrown')],
        ];
    }

    public function testHandleMethodOutputsNothing(): void
    {
        $nextMock = $this->createMock(RequestHandlerInterface::class);
        $nextMock
            ->expects($this->once())
            ->method('handle')
            ->willReturn(new Response('test body', 404, ['Content-Type' => 'text/plain; charset=utf-8']));

        $this->expectOutputString('');
        $errorHandler = new ErrorHandler($nextMock);
        $errorHandler->handle(new Request('GET', '/'));
    }

    public function testItIndependentProcessingOfTheRequestViaTheHandleMethodWhenReusingSingleErrorHandlerObject(): void
    {
        $requestWithErrorHandler = new Request('GET', '/');
        $requestWithoutErrorHandler = new Request('GET', '/contact');

        $expectedResponse = new Response('test body', 403, ['Content-Type' => 'text/plain; charset=utf-8']);

        $nextMock = $this->createMock(RequestHandlerInterface::class);
        $nextMock
            ->expects($this->exactly(2))
            ->method('handle')
            ->willReturnCallback(
                function (
                    Request $requests
                ) use (
                    $requestWithErrorHandler,
                    $requestWithoutErrorHandler,
                    $expectedResponse
                ): Response {
                    if ($requests === $requestWithErrorHandler) {
                        throw new RuntimeException('An exception was thrown');
                    }

                    if ($requests === $requestWithoutErrorHandler) {
                        return $expectedResponse;
                    }

                    throw new InvalidArgumentException('Unexpected request provided to mock');
                }
            );

        $errorHandler = new ErrorHandler($nextMock);
        $responseOne = $errorHandler->handle($requestWithErrorHandler);
        $responseTwo = $errorHandler->handle($requestWithoutErrorHandler);

        self::assertSame(500, $responseOne->getStatusCode());
        self::assertSame(['content-type' => 'text/plain; charset=UTF-8'], $responseOne->getHeaders());
        self::assertSame('Internal server error', $responseOne->getBody());
        self::assertSame($responseTwo, $expectedResponse);
    }

    public function testHandleMethodPropagatesTheLoggerException(): void
    {
        $loggerException = new RuntimeException('An exception was thrown');
        $request = new Request('GET', '/contact');

        $nextMock = $this->createMock(RequestHandlerInterface::class);
        $nextMock
            ->expects($this->once())
            ->method('handle')
            ->willThrowException(new LogicException());

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->once())
            ->method('error')
            ->willThrowException($loggerException);

        $errorHandler = new ErrorHandler($nextMock, $loggerMock);

        try {
            $errorHandler->handle($request);
        } catch (Throwable $exception) {
            self::assertSame($exception, $loggerException);
            return;
        }

        self::fail('The exception was not propagated outwards');
    }
}
