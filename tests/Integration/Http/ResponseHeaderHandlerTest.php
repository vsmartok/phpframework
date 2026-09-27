<?php

declare(strict_types = 1);

namespace Tests\Integration\Http;

use PHPFramework\Http\HttpKernel;
use PHPFramework\Http\Request;
use PHPFramework\Http\Response;
use PHPFramework\Http\ResponseHeaderHandler;
use PHPFramework\Routing\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResponseHeaderHandler::class)]
#[UsesClass(Router::class)]
#[UsesClass(HttpKernel::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
class ResponseHeaderHandlerTest extends TestCase
{
    #[DataProvider('headerValueForCoupleOfCallsToTheResponseHeaderHandler')]
    public function testCoupleOfResponseHeaderHandlerCalls(array $innerHeader, array $outerHeader, array $expectedHeaders): void {
        $routeHandlerCounter = 0;

        $request = new Request('GET', '/contact');

        $router = new Router();
        $router->add('GET', '/', fn(Request $request) => new Response('home body', 201, ['Content-Type' => 'text/html; charset=UTF-8']));
        $router->add(
            'GET',
            '/contact',
            function(Request $request) use (&$routeHandlerCounter): Response {
                $routeHandlerCounter++;

                return new Response(
                    'contact body',
                    202,
                    ['Content-Type' => 'text/html; charset=UTF-8'],
                );
            }
        );

        $httpKernel = new HttpKernel($router);

        $innerHeaderHandler = new ResponseHeaderHandler($httpKernel, $innerHeader[0], $innerHeader[1]);
        $outerHeaderHandler = new ResponseHeaderHandler($innerHeaderHandler, $outerHeader[0], $outerHeader[1]);

        $response = $outerHeaderHandler->handle($request);

        self::assertSame(1, $routeHandlerCounter);
        self::assertSame(202, $response->getStatusCode());
        self::assertSame($expectedHeaders, $response->getHeaders());
        self::assertSame('contact body', $response->getBody());
    }

    public static function headerValueForCoupleOfCallsToTheResponseHeaderHandler(): array
    {
        return [
            'various header keys' => [
                ['X-App', 'Blog'],
                ['X-Stage', 'outer'],
                ['content-type' => 'text/html; charset=UTF-8', 'x-app' => 'Blog', 'x-stage' => 'outer'],
            ],
            'duplicate header keys' => [
                ['X-Stage', 'inner'],
                ['x-stage', 'outer'],
                ['content-type' => 'text/html; charset=UTF-8', 'x-stage' => 'outer'],
            ],
        ];
    }

    public function testCoupleOfResponseHeaderHandlerCallsWhenRouteNotFound(): void
    {
        $request = new Request('GET', '/unknown');

        $router = new Router();
        $router->add('GET', '/', fn(Request $request) => new Response('home body', 201, ['Content-Type' => 'text/plain; charset=UTF-8']));
        $router->add('GET', '/contact', fn(Request $request) => new Response('contact body', 202, ['Content-Type' => 'text/plain; charset=UTF-8']));

        $httpKernel = new HttpKernel($router);

        $innerHeaderHandler = new ResponseHeaderHandler($httpKernel, 'X-App', 'Blog');
        $outerHeaderHandler = new ResponseHeaderHandler($innerHeaderHandler, 'X-Stage', 'outer');

        $response = $outerHeaderHandler->handle($request);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame(['content-type' => 'text/plain; charset=UTF-8', 'x-app' => 'Blog', 'x-stage' => 'outer'], $response->getHeaders());
        self::assertSame('Page not found', $response->getBody());
    }
}
