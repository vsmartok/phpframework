<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use InvalidArgumentException;
use PHPFramework\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    #[DataProvider('requestMethodInvalidValues')]
    public function testItThrowsAnInvalidArgumentExceptionWhenTheRequestMethodValueIsInvalid(string $methodName): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Request($methodName, '/');
    }

    public static function requestMethodInvalidValues(): array
    {
        return [
            'empty line' => [''],
            'control characters' => ["GET\n"],
            'numbers' => ['GET2'],
            'space' => [' GET'],
            'non-ascii characters' => ['GÉT'],
            'punctuation' => ['GE-T'],
        ];
    }

    #[DataProvider('pathInvalidValues')]
    public function testItThrowsAnInvalidArgumentExceptionWhenThePathIsInvalid(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Request('GET', $path);
    }

    public static function pathInvalidValues(): array
    {
        return [
            'path is empty' => [''],
            'without leading slash' => ['home'],
            'contains query parameters' => ['/blog?page=1'],
            'contains an anchor' => ['/blog#benefits'],
        ];
    }

    #[DataProvider('methodNamesToNormalizeTest')]
    public function testItNormalizesTheRequestMethodNameToUppercase(string $inputData, string $expectedResult): void
    {
        $request = new Request($inputData, '/');
        self::assertSame($expectedResult, $request->getMethod());
    }

    public static function methodNamesToNormalizeTest(): array
    {
        return [
            'all lowercase letters' => ['get', 'GET'],
            'mixed-case lettering' => ['gEt', 'GET'],
        ];
    }

    public function testItAcceptsAnArbitraryAlphabeticMethod(): void
    {
        $request = new Request('PURGE', '/');
        self::assertSame('PURGE', $request->getMethod());
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

    public function testItPreservesPercentEncodedPath(): void
    {
        $request = new Request('GET', '/hello%20world');
        self::assertSame('/hello%20world', $request->getPath());
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

    public function testItSetsAnEmptyArrayForDefaultQueryParameters(): void
    {
        $request = new Request('GET', '/');
        self::assertSame([], $request->getQueryParams());
    }

    public function testItAcceptsAnArrayOfQueryParameters(): void
    {
        $request = new Request('GET', '/', ['page' => '2', 'sort_order' => 'DESC']);
        self::assertSame(['page' => '2', 'sort_order' => 'DESC'], $request->getQueryParams());
    }

    public function testGetQueryParamReturnsDefaultValueOfNullIfTheParameterIsMissing(): void
    {
        $request = new Request('GET', '/home');
        self::assertNull($request->getQueryParam('page'));
    }

    public function testGetQueryParamReturnsTheSpecifiedDefaultValueIfTheParameterIsMissing(): void
    {
        $request = new Request('GET', '/home');
        self::assertSame('1', $request->getQueryParam('page', '1'));
    }

    #[DataProvider('valuesForGetQueryParamMethod')]
    public function testGetQueryParamReturnsValueFromTheRequestParametersArray(mixed $actualValue, mixed $defaultValue): void
    {
        $request = new Request('GET', '/', ['page' => $actualValue]);
        self::assertSame($actualValue, $request->getQueryParam('page', $defaultValue));
    }

    public static function valuesForGetQueryParamMethod(): array
    {
        return [
            'param value = 2' => ['2', '1'],
            'param value = null' => [null, '1'],
            'param value =' => ['', 'all'],
            'param value = (array)' => [['one' => '2', 'two' => '3'], 'empty'],
        ];
    }
}
