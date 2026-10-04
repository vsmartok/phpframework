<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use PHPFramework\Http\Middleware\RequestAttributeMiddleware;
use PHPFramework\Http\Request;
use PHPFramework\Http\RequestHandlerInterface;
use PHPFramework\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

#[CoversClass(RequestAttributeMiddleware::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
class RequestAttributeMiddlewareTest extends TestCase
{
    public function testProcessMethodForwardsTheNewRequestToTheNextHandlerOnce(): void
    {
        $expectedResponse = new Response('test body', 201, ['Content-Type' => 'text/html; charset=utf-8']);

        $request = new Request('POST', '/path', ['page' => '2', 'order_by' => 'price'], ['is_auth' => false]);

        $next = $this->createMock(RequestHandlerInterface::class);
        $next
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function (Request $receivedRequest) use ($request, $expectedResponse): Response {
                self::assertSame('uk', $receivedRequest->getAttribute('locale'));
                self::assertNotSame($request, $receivedRequest);

                self::assertSame($request->getMethod(), $receivedRequest->getMethod());
                self::assertSame($request->getPath(), $receivedRequest->getPath());
                self::assertSame($request->getQueryParams(), $receivedRequest->getQueryParams());
                return $expectedResponse;
            });

        $middleware = new RequestAttributeMiddleware('locale', 'uk');

        $receivedResponse =  $middleware->process($request, $next);

        self::assertSame($expectedResponse, $receivedResponse);
    }

    public function testProcessMethodPropagatesTheExceptionFromTheNextHandler(): void
    {
        $exception = new RuntimeException('An error occurred');

        $next = $this->createStub(RequestHandlerInterface::class);
        $next
            ->method('handle')
            ->willThrowException($exception);

        $middleware = new RequestAttributeMiddleware('locale', 'uk');

        try {
            $middleware->process(new Request('POST', '/path'), $next);
        } catch (Throwable $e) {
            self::assertSame($exception, $e);
            return;
        }

        self::fail('Exception should have been thrown');
    }

    #[DataProvider('withAttributeDataProvider')]
    public function testProcessAddsOrReplacesAttributeInForwardedRequest(string $name, mixed $value, array $expectedAttributes): void
    {
        $request = new Request('POST', '/path', [], ['is_auth' => false, 'app_name' => 'test app']);

        $next = $this->createMock(RequestHandlerInterface::class);
        $next
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function (Request $receivedRequest) use ($name, $value, $expectedAttributes, $request): Response {
                self::assertSame($value, $receivedRequest->getAttribute($name));
                self::assertSame($expectedAttributes, $receivedRequest->getAttributes());
                self::assertSame(['is_auth' => false, 'app_name' => 'test app'], $request->getAttributes());
                return new Response();
            });

        $middleware = new RequestAttributeMiddleware($name, $value);

        $middleware->process($request, $next);
    }

    public static function withAttributeDataProvider(): array
    {
        return [
            'add new attribute value' => [
                'name',
                'John Doe',
                [
                    'is_auth' => false,
                    'app_name' => 'test app',
                    'name' => 'John Doe',
                ],
            ],
            'replace existing attribute value' => [
                'is_auth',
                true,
                [
                    'is_auth' => true,
                    'app_name' => 'test app',
                ],
            ],
            'add new attribute with null value' => [
                'data',
                null,
                [
                    'is_auth' => false,
                    'app_name' => 'test app',
                    'data' => null,
                ],
            ],
            'add new attribute with the different case in the name' => [
                'App_name',
                'John Doe App',
                [
                    'is_auth' => false,
                    'app_name' => 'test app',
                    'App_name' => 'John Doe App',
                ],
            ],
        ];
    }
}