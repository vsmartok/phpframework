<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use PHPFramework\Http\MiddlewareChainBuilder;
use PHPFramework\Http\MiddlewareHandler;
use PHPFramework\Http\Request;
use PHPFramework\Http\RequestHandlerInterface;
use PHPFramework\Http\Response;
use PHPFramework\Http\ResponseHeaderMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MiddlewareChainBuilder::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
#[UsesClass(ResponseHeaderMiddleware::class)]
#[UsesClass(MiddlewareHandler::class)]
class MiddlewareChainBuilderTest extends TestCase
{
    public function testTheAssembledChainWorksAndInTheEventOfDuplicateHeaderNamesTheFirstMiddlewareInTheListTakesPrecedence(): void
    {
        $last = $this->createStub(RequestHandlerInterface::class);
        $last
            ->method('handle')
            ->willReturn(new Response('test body', 201, ['Content-Type' => 'text/html; charset=utf-8']));

        $middlewares = [
            new ResponseHeaderMiddleware('X-Environment', 'local'),
            new ResponseHeaderMiddleware('X-Environment', 'remote'),
            new ResponseHeaderMiddleware('X-App', 'Blog'),
        ];

        $handler = (new MiddlewareChainBuilder())->build($last, ...$middlewares);

        $response = $handler->handle(new Request('GET', '/'));

        self::assertSame('test body', $response->getBody());
        self::assertSame(201, $response->getStatusCode());
        self::assertSame(
            [
                'content-type' => 'text/html; charset=utf-8',
                'x-app' => 'Blog',
                'x-environment' => 'local',
            ],
            $response->getHeaders()
        );
    }

    public function testTwoChainsAssembledBySingleAssemblerAreIndependent(): void
    {
        $lastOne = $this->createStub(RequestHandlerInterface::class);
        $lastOne
            ->method('handle')
            ->willReturn(new Response('test body one', 201, ['Content-Type' => 'text/html; charset=utf-8']));

        $lastTwo = $this->createStub(RequestHandlerInterface::class);
        $lastTwo
            ->method('handle')
            ->willReturn(new Response('test body two', 203, ['Content-Type' => 'text/plain; charset=utf-8']));

        $middlewaresOne = [
            new ResponseHeaderMiddleware('X-Environment', 'local'),
            new ResponseHeaderMiddleware('X-App', 'Blog'),
        ];

        $middlewaresTwo = [
            new ResponseHeaderMiddleware('X-Environment', 'remote'),
            new ResponseHeaderMiddleware('X-App', 'Blog'),
        ];

        $builder = new MiddlewareChainBuilder();

        $handlerOne = $builder->build($lastOne, ...$middlewaresOne);
        $handlerTwo = $builder->build($lastTwo, ...$middlewaresTwo);

        $responseOne = $handlerOne->handle(new Request('GET', '/'));
        $responseTwo = $handlerTwo->handle(new Request('GET', '/'));

        self::assertSame('test body one', $responseOne->getBody());
        self::assertSame('test body two', $responseTwo->getBody());
        self::assertSame(201, $responseOne->getStatusCode());
        self::assertSame(203, $responseTwo->getStatusCode());
        self::assertSame(
            [
                'content-type' => 'text/html; charset=utf-8',
                'x-app' => 'Blog',
                'x-environment' => 'local',
            ],
            $responseOne->getHeaders()
        );
        self::assertSame(
            [
                'content-type' => 'text/plain; charset=utf-8',
                'x-app' => 'Blog',
                'x-environment' => 'remote',
            ],
            $responseTwo->getHeaders()
        );
    }
}