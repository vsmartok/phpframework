<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPFramework\Http\MiddlewareChainBuilder;
use PHPFramework\Http\MiddlewareHandler;
use PHPFramework\Http\MiddlewareInterface;
use PHPFramework\Http\RequestHandlerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MiddlewareChainBuilder::class)]
#[UsesClass(MiddlewareHandler::class)]
class MiddlewareChainBuilderTest extends TestCase
{
    public function testBuildMethodReturnsTheOriginalHandlerIfTheMiddlewareListIsEmpty(): void
    {
        $last = $this->createStub(RequestHandlerInterface::class);

        $handler = (new MiddlewareChainBuilder())->build($last);

        self::assertSame($last, $handler);
    }

    public function testBuildMethodDoesNotExecuteTheMiddlewareAndTheFinalHandler(): void
    {
        $last = $this->createMock(RequestHandlerInterface::class);
        $last
            ->expects($this->never())
            ->method('handle');

        $middlewareOne = $this->createMock(MiddlewareInterface::class);
        $middlewareOne
            ->expects($this->never())
            ->method('process');

        $middlewareTwo = $this->createMock(MiddlewareInterface::class);
        $middlewareTwo
            ->expects($this->never())
            ->method('process');

        (new MiddlewareChainBuilder())->build($last, $middlewareOne, $middlewareTwo);
    }


}
