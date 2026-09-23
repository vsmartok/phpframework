<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPFramework\Http\HttpKernel;
use PHPFramework\Http\Request;
use PHPFramework\Http\Response;
use PHPFramework\Routing\Router;
use PHPFramework\Routing\RouteNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use TypeError;

#[CoversClass(HttpKernel::class)]
#[UsesClass(Router::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
#[UsesClass(RouteNotFoundException::class)]
final class HttpKernelTest extends TestCase
{
    public function testHandleMethodReturnsTheHandlersResponseIfRouteForTheRequestIsRegistered(): void
    {
        $request = new Request('POST', '/home');
        $expectedResponse = new Response('home page - POST');

        $router = new Router();
        $router->add('GET', '/home', fn(Request $request): Response => new Response('home page - GET'));
        $router->add('POST', '/home', function(Request $receivedRequest) use ($expectedResponse, $request): Response {
            self::assertSame($request, $receivedRequest);

            return $expectedResponse;
        });
        $router->add('GET', '/contact', fn(Request $request): Response => new Response('contact page - GET'));

        $httpKernel = new HttpKernel($router);
        $response = $httpKernel->handle($request);

        self::assertSame($expectedResponse, $response);
        self::assertSame('home page - POST', $response->getBody());
    }

    public function testHandleMethodReturnsPageNotFoundResponseIfTheRouteForTheRequestIsNotRegistered(): void
    {
        $router = new Router();
        $router->add('GET', '/home', fn(Request $request): Response => new Response('home page - GET'));
        $router->add('GET', '/contact', fn(Request $request): Response => new Response('contact page - GET'));

        $httpKernel = new HttpKernel($router);
        $response = $httpKernel->handle(new Request('POST', '/home'));

        self::assertSame('Page not found', $response->getBody());
        self::assertSame(404, $response->getStatusCode());
    }

    public function testHandleMethodProcessesSuccessiveRequestsIndependently(): void
    {
        $router = new Router();
        $router->add('GET', '/home', fn(Request $request): Response => new Response('home page - GET'));
        $router->add('GET', '/contact', fn(Request $request): Response => new Response('contact page - GET'));

        $httpKernel = new HttpKernel($router);
        $responseOne = $httpKernel->handle(new Request('GET', '/home'));
        $responseNotFound = $httpKernel->handle(new Request('POST', '/'));
        $responseTwo = $httpKernel->handle(new Request('GET', '/home'));
        $responseThree = $httpKernel->handle(new Request('GET', '/contact'));

        self::assertSame('home page - GET', $responseOne->getBody());
        self::assertSame('home page - GET', $responseTwo->getBody());
        self::assertSame('contact page - GET', $responseThree->getBody());
        self::assertNotSame($responseOne, $responseTwo);
        self::assertSame(404, $responseNotFound->getStatusCode());
    }

    #[DataProvider('exceptionsThatTheHandleMethodDoesNotHandle')]
    public function testHandleMethodLetsExceptionsAndErrorsOtherThanRouteNotFoundExceptionPropagateOutwards(string $exceptionClass): void
    {
        $exception = new $exceptionClass();

        $router = new Router();
        $router->add('GET', '/', function(Request $request) use ($exception): Response {throw $exception;});

        $httpKernel = new HttpKernel($router);

        try {
            $httpKernel->handle(new Request('GET', '/'));
        } catch (Throwable $e) {
            self::assertSame($exception, $e);
            return;
        }

        self::fail('The '. $exceptionClass .' was handled within the handle method');

    }

    public static function exceptionsThatTheHandleMethodDoesNotHandle(): array
    {
        return [
            'RuntimeException' => [RuntimeException::class],
            'TypeError' => [TypeError::class],
        ];
    }
}
