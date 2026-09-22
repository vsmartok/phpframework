<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use InvalidArgumentException;
use PHPFramework\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    public function testGetBodyMethodReturnsTheResponseBody(): void
    {
        $response = new Response(body: 'Test body');

        self::assertSame('Test body', $response->getBody());
    }

    public function testGetStatusCodeReturnsTheResponseCode(): void
    {
        $response = new Response(statusCode: 403);

        self::assertSame(403, $response->getStatusCode());
    }

    public function testItSetsDefaultValuesIfNoArgumentsArePassed(): void
    {
        $response = new Response();

        self::assertSame('', $response->getBody());
        self::assertSame(200, $response->getStatusCode());
    }

    public function testItAcceptsInputParametersAndStoresThem(): void
    {
        $response = new Response('Page Not Found', 404);

        self::assertSame('Page Not Found', $response->getBody());
        self::assertSame(404, $response->getStatusCode());
    }

    public function testItThrowsAnInvalidArgumentExceptionIfTheStatusCodeIsLessThanTheValidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Response(statusCode: 99);
    }

    public function testItThrowsAnInvalidArgumentExceptionIfTheStatusCodeExceedsThePermissibleValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Response(statusCode: 600);
    }

    public function testItAcceptsValidResponseStatusCodeBoundaryValues(): void
    {
        $responseOne = new Response(statusCode: 100);
        
        self::assertSame(100, $responseOne->getStatusCode());

        $responseTwo = new Response(statusCode: 599);

        self::assertSame(599, $responseTwo->getStatusCode());
    }

    public function testItOutputsNothingWhenTheObjectIsCreated(): void
    {
        $this->expectOutputString('');

        new Response('Test body', 200);
    }
}