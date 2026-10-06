<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use InvalidArgumentException;
use PHPFramework\Http\Request;
use PHPFramework\Http\Response;
use PHPFramework\Routing\RouteMatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RouteMatch::class)]
class RouteMatchTest extends TestCase
{
    public function testObjectCreationAndCallsToGettersDoNotTriggerTheHandler(): void
    {
        $handlerIsCalled = false;

        $closure = function (Request $request) use (&$handlerIsCalled): Response {
            $handlerIsCalled = true;

            return new Response();
        };

        $routeMatch = new RouteMatch($closure);

        self::assertSame($closure, $routeMatch->getHandler());
        self::assertSame([], $routeMatch->getParameters());

        self::assertFalse($handlerIsCalled);
    }

    public function testSavesSeveralParameters(): void
    {
        $routeMatch = new RouteMatch(
            fn(Request $request): Response => new Response(),
            ['id' => '42', 'action' => 'edit'],
        );

        self::assertSame(
            ['id' => '42', 'action' => 'edit'],
            $routeMatch->getParameters(),
        );
    }

    #[DataProvider('invalidParameterDataProvider')]
    public function testItRejectsInvalidKeyAndValueTypes(array $parameters): void
    {
        $this->expectException(InvalidArgumentException::class);
        new RouteMatch(
            fn(Request $request): Response => new Response(),
            $parameters,
        );
    }

    public static function invalidParameterDataProvider(): array
    {
        return [
            'value is "null"' => [['id' => null]],
            'value is "number"' => [['id' => 42]],
            'value is "boolean"' => [['id' => true]],
            'value is "array"' => [['id' => ['foo' => 'bar']]],
            'key is "number"' => [[42 => '42']],
        ];
    }
}
