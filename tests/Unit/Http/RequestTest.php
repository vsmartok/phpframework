<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use InvalidArgumentException;
use PHPFramework\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    public function testItThrowsAnInvalidArgumentExceptionIfTheMethodNameIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Request('', '/');
    }

    public function testItThrowsAnInvalidArgumentExceptionIfTheMethodNameContainsCharactersOtherThanLatinLetters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Request('GET2', '/');
    }

    public function testItNormalizesTheRequestMethodNameToUppercase(): void
    {
        $request = new Request('get', '/');

        self::assertSame('GET', $request->getMethod());
    }

    public function testItAcceptsAnArbitraryAlphabeticMethod(): void
    {
        $request = new Request('PURGE', '/');

        self::assertSame('PURGE', $request->getMethod());
    }

    public function testItThrowsAnInvalidArgumentExceptionIfThePathIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Request('GET', '');
    }

    public function testItThrowsAnInvalidArgumentExceptionIfThePathDoesNotStartWithSlash(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Request('GET', 'home');
    }

    public function testItThrowsAnInvalidArgumentExceptionIfThePathContainsQueryParameters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Request('GET', '/blog?page=1');
    }

    public function testItThrowsAnInvalidArgumentExceptionIfThePathContainsAnAnchor(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Request('GET', '/blog#benefits');
    }

    public function testItPreservesThePathWithoutChangingTheCase(): void
    {
        $request = new Request('GET', '/AdmiN');

        self::assertSame('/AdmiN', $request->getPath());
    }

    public function testItPreservesThePathWithoutDiscardingTheTrailingSlash(): void
    {
        $request = new Request('GET', '/home/');

        self::assertSame('/home/', $request->getPath());
    }

    public function testItPreservesThePathWithoutPercentEncoding(): void
    {
        $request = new Request('GET', '/название-пути');

        self::assertSame('/название-пути', $request->getPath());

        $request = new Request('GET', '/admin\n');

        self::assertSame('/admin\n', $request->getPath());
    }

    public function testGetMethodReturnsTheNameOfTheRequestMethodPassedWhenTheObjectWasCreated(): void
    {
        $request = new Request('GET', '/');

        self::assertSame('GET', $request->getMethod());
    }

    public function testGetPathReturnsThePathPassedWhenTheObjectWasCreated(): void
    {
        $request = new Request('GET', '/home');

        self::assertSame('/home', $request->getPath());
    }
}
