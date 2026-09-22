<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use InvalidArgumentException;
use PHPFramework\Http\Request;
use PHPFramework\Http\RequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestFactory::class)]
#[UsesClass(Request::class)]
final class RequestFactoryTest extends TestCase
{
    #[DataProvider('invalidMethodNames')]
    public function testItThrowsAnInvalidArgumentExceptionWhenTheMethodNameIsInvalid(array $server): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new RequestFactory())->fromServer($server);
    }

    public static function invalidMethodNames(): array
    {
        return [
            'empty line' => [
                ['REQUEST_METHOD' => '', 'REQUEST_URI' => '/'],
            ],
            'the key is missing' => [
                ['REQUEST_URI' => '/'],
            ],
            'array type' => [
                ['REQUEST_METHOD' => [], 'REQUEST_URI' => '/'],
            ],
            'null value' => [
                ['REQUEST_METHOD' => null, 'REQUEST_URI' => '/'],
            ], 
        ];
    }

    #[DataProvider('invalidPaths')]
    public function testItThrowsAnInvalidArgumentExceptionWhenThePathIsInvalid(array $server): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new RequestFactory())->fromServer($server);
    }

    public static function invalidPaths(): array
    {
        return [
            'empty line' => [
                ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => ''],
            ],
            'the key is missing' => [
                ['REQUEST_METHOD' => 'GET'],
            ],
            'does not start with a slash' => [
                ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => 'home'],
            ],
            'contain # symbol' => [
                ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/#benefits'], 
            ],
            'array value' => [
                ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => []], 
            ],
            'null value' => [
                ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => null], 
            ],
        ];
    }

    #[DataProvider('correctPaths')]
    public function testItHandlesThePathCorrectly(array $server, string $resultingPath): void
    {
        $request = (new RequestFactory())->fromServer($server);
        self::assertSame($resultingPath, $request->getPath());
    }

    public static function correctPaths(): array
    {
        return [
            'root path without query parameters' => [
                ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'], 
                '/',
            ],
            'root path with query parameters' => [
                ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/?page=1'], 
                '/',
            ],
        
            'path without query parameters' => [
                ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/blog'], 
                '/blog',
            ],
            'path with query parameters' => [
                ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/blog?page=1'], 
                '/blog',
            ],
            'percent-encoding is preserved' => [
                ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/Articles/hello%20world/?page=2'], 
                '/Articles/hello%20world/',
            ],
            'the closing slash is preserved' => [
                ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/home/page/'], 
                '/home/page/',
            ],
            'contain encoded # symbol' => [
                ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/blog?tag=%23php'], 
                '/blog',
            ],
            'several slashes in a row at the beginning' => [
                ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '///admin?page=settings'], 
                '///admin',
            ],
        ];
    }

    public function testItCreatesRequestObjectWithTheMethodFromThePassedArray(): void
    {
        $request = (new RequestFactory())->fromServer([
            'REQUEST_METHOD' => 'POST', 
            'REQUEST_URI' => '/home',
        ]);

        self::assertSame('POST', $request->getMethod());
    }

    public function testItDoesNotCatchTheExceptionThrownByTheRequestObject(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new RequestFactory())->fromServer([
            'REQUEST_METHOD' => 'POST-2', 
            'REQUEST_URI' => '/home',
        ]);
    }

    public function testItIgnoresExtraKeysWhenCreatingTheRequestObject(): void
    {
        $request = (new RequestFactory())->fromServer([
            'REDIRECT_STATUS' => 200,
            'REQUEST_METHOD' => 'PUT', 
            'REQUEST_URI' => '/home',
            'REQUEST_SCHEME' => 'https',
            'SERVER_PORT' => 443,
        ]);

        self::assertSame('PUT', $request->getMethod());
        self::assertSame('/home', $request->getPath());
    }
}