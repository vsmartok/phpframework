<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use PHPFramework\Routing\MethodNotAllowedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MethodNotAllowedException::class)]
final class MethodNotAllowedExceptionTest extends TestCase
{
    public function testItStoresThePassedArrayOfAllowedRequestMethodNames(): void
    {
        $e = new MethodNotAllowedException(['GET', 'POST']);
        self::assertSame(['GET', 'POST'], $e->getAllowedMethods());
    }
}