<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPFramework\Http\HandlerChainBuilder;
use PHPFramework\Http\HttpKernel;
use PHPFramework\Http\Request;
use PHPFramework\Http\RequestHandlerInterface;
use PHPFramework\Http\Response;
use PHPFramework\Http\ResponseHeaderHandler;
use PHPFramework\Routing\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversClass(HandlerChainBuilder::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
#[UsesClass(Router::class)]
#[UsesClass(HttpKernel::class)]
#[UsesClass(ResponseHeaderHandler::class)]
final class HandlerChainBuilderTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        $router = new Router();

        $router->add(
            'GET',
            '/',
            fn(Request $request): Response => new Response(
                'test body',
                201,
                ['Content-Type' => 'text/html; charset=utf-8'],
            ),
        );

        $this->router = $router;
    }

    public function testBuildMethodReturnsTheSameIncomingRequestHandlerInterfaceObjectIfCalledWithoutFactories(): void
    {
        $routeHandlerResponse = new Response(
            'test body',
            201,
            ['Content-Type' => 'text/html; charset=utf-8'],
        );

        $this->router->add(
            'GET',
            '/contact',
            function (Request $request) use ($routeHandlerResponse) {return $routeHandlerResponse;},
        );

        $request = new Request('GET', '/contact');
        $httpKernel = new HttpKernel($this->router);

        $builder = new HandlerChainBuilder();
        $handler = $builder->build($httpKernel);

        $response = $handler->handle($request);

        self::assertSame($response, $routeHandlerResponse);
        self::assertSame($handler, $httpKernel);
    }

    public function testBuildMethodCallsEachFactoryExactlyOnceDuringAssembly(): void
    {
        $httpKernel = new HttpKernel($this->router);

        $firstDecoratorCallCounter = 0;
        $secondDecoratorCallCounter = 0;

        $builder = new HandlerChainBuilder();
        $handler = $builder->build(
            $httpKernel,
            function(RequestHandlerInterface $next) use (&$firstDecoratorCallCounter): RequestHandlerInterface {
                $firstDecoratorCallCounter++;

                return new ResponseHeaderHandler(
                    $next,
                    'X-Environment',
                    'local',
                );
            },
            function(RequestHandlerInterface $next) use (&$secondDecoratorCallCounter): RequestHandlerInterface {
                $secondDecoratorCallCounter++;

                return new ResponseHeaderHandler(
                    $next,
                    'X-App',
                    'Blog',
                );
            },
        );

        self::assertSame(1, $firstDecoratorCallCounter);
        self::assertSame(1, $secondDecoratorCallCounter);

        $handler->handle(new Request('GET', '/'));


    }

    public function testBuildMethodTheHandleMethodOfRequestHandlerInterfaceIsNotCalledDuringTheChainAssembly(): void
    {
        $httpKernelMock = $this->createMock(RequestHandlerInterface::class);
        $httpKernelMock
            ->expects($this->never())
            ->method('handle');

        $responseHeaderHandlerMock = $this->createMock(ResponseHeaderHandler::class);
        $responseHeaderHandlerMock
            ->expects($this->never())
            ->method('handle');

        $builder = new HandlerChainBuilder();
        $builder->build(
            $httpKernelMock,
            fn(RequestHandlerInterface $next): RequestHandlerInterface => $responseHeaderHandlerMock,
        );
    }

    public function testBuildMethodThrowsTypeErrorIfTheFactoryReturnsAnObjectThatDoesNotImplementRequestHandlerInterface(): void
    {
        $httpKernel = new HttpKernel($this->router);

        $builder = new HandlerChainBuilder();

        $this->expectException(TypeError::class);

        $builder->build(
            $httpKernel,
            function(RequestHandlerInterface $next) {
                return 'response value';
            },
            function(RequestHandlerInterface $next): RequestHandlerInterface {
                return new ResponseHeaderHandler(
                    $next,
                    'X-App',
                    'Blog',
                );
            },
        );
    }

    public function testBuildMethodPropagatesFactoryExceptionsOutwards(): void
    {
        $httpKernel = new HttpKernel($this->router);

        $exception = new TypeError();

        $builder = new HandlerChainBuilder();

        try {
            $builder->build(
                $httpKernel,
                function(RequestHandlerInterface $next) use ($exception): RequestHandlerInterface {
                    throw $exception;
                },
            );
        } catch (TypeError $e) {
            self::assertSame($e, $exception);
            return;
        }

        self::fail('TypeError exception should have been thrown');
    }

    public function testBuildMethodDoesNotSaveTheIntermediateChainInTheProperties(): void
    {
        $httpKernel = new HttpKernel($this->router);

        $builder = new HandlerChainBuilder();
        $handlerOne = $builder->build(
            $httpKernel,
            function(RequestHandlerInterface $next): RequestHandlerInterface {
                return new ResponseHeaderHandler(
                    $next,
                    'X-Environment',
                    'local',
                );
            },
        );

        $responseOne = $handlerOne->handle(new Request('GET', '/'));

        self::assertSame('test body', $responseOne->getBody());
        self::assertSame(201, $responseOne->getStatusCode());
        self::assertSame(
            ['content-type' => 'text/html; charset=utf-8', 'x-environment' => 'local'],
            $responseOne->getHeaders()
        );

        $handlerTwo = $builder->build(
            $httpKernel,
            function(RequestHandlerInterface $next): RequestHandlerInterface {
                return new ResponseHeaderHandler(
                    $next,
                    'X-App',
                    'Blog',
                );
            },
        );

        $responseTwo = $handlerTwo->handle(new Request('GET', '/'));

        self::assertSame('test body', $responseTwo->getBody());
        self::assertSame(201, $responseTwo->getStatusCode());
        self::assertSame(
            ['content-type' => 'text/html; charset=utf-8', 'x-app' => 'Blog'],
            $responseTwo->getHeaders()
        );
    }

    public function testBuildMethodEachInvokedDecoratorAddsItsValueToTheResponseIfTheHeaderNamesAreDifferent(): void
    {
        $httpKernel = new HttpKernel($this->router);

        $builder = new HandlerChainBuilder();
        $handler = $builder->build(
            $httpKernel,
            fn (RequestHandlerInterface $next): RequestHandlerInterface => new ResponseHeaderHandler(
                $next,
                'X-Environment',
                'local',
            ),
            fn (RequestHandlerInterface $next): RequestHandlerInterface => new ResponseHeaderHandler(
                $next,
                'X-App',
                'Blog',
            ),
        );

        $response = $handler->handle(new Request('GET', '/'));

        self::assertSame('test body', $response->getBody());
        self::assertSame(201, $response->getStatusCode());
        self::assertSame(
            [
                'content-type' => 'text/html; charset=utf-8',
                'x-app' => 'Blog',
                'x-environment' => 'local',
            ],
            $response->getHeaders(),
        );
    }

    public function testBuildMethodCallingDecoratorsWithIdenticalHeaderNamesWillAddOnlyTheValueOfTheLastOneCalled(): void
    {
        $httpKernel = new HttpKernel($this->router);

        $builder = new HandlerChainBuilder();
        $handler = $builder->build(
            $httpKernel,
            fn (RequestHandlerInterface $next): RequestHandlerInterface => new ResponseHeaderHandler(
                $next,
                'X-Environment',
                'local',
            ),
            fn (RequestHandlerInterface $next): RequestHandlerInterface => new ResponseHeaderHandler(
                $next,
                'X-Environment',
                'remote',
            ),
        );

        $response = $handler->handle(new Request('GET', '/'));

        self::assertSame('test body', $response->getBody());
        self::assertSame(201, $response->getStatusCode());
        self::assertSame(
            [
                'content-type' => 'text/html; charset=utf-8',
                'x-environment' => 'local',
            ],
            $response->getHeaders(),
        );
    }
}
