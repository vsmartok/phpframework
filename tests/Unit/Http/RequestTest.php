<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use InvalidArgumentException;
use PHPFramework\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

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

    public function testItSetsAnEmptyArrayAsTheDefaultValueForTheAttributesParameter(): void
    {
        $request = new Request('GET', '/');
        self::assertSame([], $request->getAttributes());
        self::assertNull($request->getAttribute('page'));
    }

    public function testItStoresThePassedAttributeValuesWithoutConversion(): void
    {
        $request = new Request('GET', '/', [], ['locale' => 'en', 'Locale' => 'en_US', 'nAme' => 'Some name!']);

        self::assertSame(
            ['locale' => 'en', 'Locale' => 'en_US', 'nAme' => 'Some name!'],
            $request->getAttributes(),
        );

        self::assertSame('en', $request->getAttribute('locale', 'en_US'));
        self::assertSame('en_US', $request->getAttribute('Locale', 'EN'));
    }

    public function testGetAttributeMethodReturnsDefaultValueForMissingAttribute(): void
    {
        $request = new Request(
            'GET',
            '/',
            [],
            ['locale' => 'en', 'is_logged_in' => false],
        );

        self::assertFalse($request->getAttribute('is_auth', false));
    }

    public function testGetAttributeMethodReturnsNullIfTheAttributeIsPassedWithSuchValue(): void
    {
        $request = new Request('GET', '/', [], ['some_key' => null]);

        self::assertNull($request->getAttribute('some_key', '1'));
    }

    #[DataProvider('withAttributeMethodTestData')]
    public function testWithAttributeMethodAddsOrReplacesSingleAttributeAndReturnsNewRequest(array $newAttribute, array $expectedAttributes): void
    {
        $request = new Request('POST', '/articles', ['locale' => 'en'], ['locale' => 'en', 'is_logged_in' => false]);

        $newRequest = $request->withAttribute($newAttribute['name'], $newAttribute['value']);

        self::assertSame($expectedAttributes, $newRequest->getAttributes());

        self::assertSame('POST', $request->getMethod());
        self::assertSame('/articles', $request->getPath());
        self::assertSame(['locale' => 'en'], $request->getQueryParams());
        self::assertSame(['locale' => 'en', 'is_logged_in' => false], $request->getAttributes());

        self::assertSame($request->getMethod(), $newRequest->getMethod());
        self::assertSame($request->getPath(), $newRequest->getPath());
        self::assertSame($request->getQueryParams(), $newRequest->getQueryParams());

        self::assertNotSame($request, $newRequest);
    }

    public static function withAttributeMethodTestData(): array
    {
        return [
            'example one' => [
                ['name' => 'Locale', 'value' => 'en'],
                ['locale' => 'en', 'is_logged_in' => false, 'Locale' => 'en'],
            ],
            'example two' => [
                ['name' => 'locale', 'value' => 'de'],
                ['locale' => 'de', 'is_logged_in' => false],
            ],
            'example three' => [
                ['name' => 'is_auth', 'value' => null],
                ['locale' => 'en', 'is_logged_in' => false, 'is_auth' => null],
            ],
            'example four' => [
                ['name' => '10', 'value' => 'number value'],
                ['locale' => 'en', 'is_logged_in' => false, '10' => 'number value'],
            ],
        ];
    }

    public function testWithAttributeMethodSavesTheObjectAsAnAttributeValue(): void
    {
        $request = new Request('GET', '/');

        $user = new stdClass();
        $user->name = 'John';
        $user->age = 23;

        $newRequest = $request->withAttribute('user', $user);

        self::assertSame($user, $newRequest->getAttribute('user'));

        $anotherNewRequest = $newRequest->withAttribute('is_logged_in', true);

        self::assertSame($user, $anotherNewRequest->getAttribute('user'));
        self::assertTrue($anotherNewRequest->getAttribute('is_logged_in'));
    }
}
