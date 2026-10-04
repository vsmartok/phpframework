<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Middleware;

use PHPFramework\Http\Middleware\MiddlewareChainBuilder;
use PHPFramework\Http\Middleware\MiddlewareHandler;
use PHPFramework\Http\Middleware\RequestAttributeMiddleware;
use PHPFramework\Http\Request;
use PHPFramework\Http\RequestHandlerInterface;
use PHPFramework\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestAttributeMiddleware::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
#[UsesClass(MiddlewareChainBuilder::class)]
#[UsesClass(MiddlewareHandler::class)]
class RequestAttributeMiddlewareTest extends TestCase
{
    public function testLaterMiddlewareOverridesEarlierRequestAttribute(): void
    {
        $next = $this->createMock(RequestHandlerInterface::class);
        $next
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function (Request $request): Response {
                self::assertSame('fr', $request->getAttribute('locale'));
                return new Response();
            });

        $middlewares = [
            new RequestAttributeMiddleware('locale', 'en'),
            new RequestAttributeMiddleware('locale', 'fr'),
        ];

        $builder = new MiddlewareChainBuilder();
        $handler = $builder->build($next, ...$middlewares);

        $handler->handle(new Request('GET', '/'));
    }
}