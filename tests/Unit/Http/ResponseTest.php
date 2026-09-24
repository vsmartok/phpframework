<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use InvalidArgumentException;
use PHPFramework\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

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

    public function testGetHeadersMethodReturnsNormalizedArray(): void
    {
        $response = new Response(
            'body content', 
            200, 
            [
                'ContEnt-Type' => 'text/html; charset=utf-8 ',
                'COntent-LanguagE' => 'en',
            ],
        );

        self::assertSame(
            [
                'content-type' => 'text/html; charset=utf-8 ',
                'content-language' => 'en',
            ],
            $response->getHeaders(),
        );
    }

    #[DataProvider('validHeaderNamesInTheHeadersArray')]
    public function testItAcceptsOnlyValidHeaderNamesInTheHeadersArray(string $incomingName, string $expectedName): void
    {
        $response = new Response('', 200, [$incomingName => 'plain/text; charset=utf-8']);

        self::assertSame(
            [$expectedName => 'plain/text; charset=utf-8'],
            $response->getHeaders(),
        );
    }

    public static function validHeaderNamesInTheHeadersArray(): array
    {
        return [
            'format - Content-Type' => ['Content-Type', 'content-type'],
            'format - contEnt-Type' => ['contEnt-Type', 'content-type'],
            'format - CONTENT-TYPE' => ['CONTENT-TYPE', 'content-type'],
            'format - content-type' => ['content-type', 'content-type'],
            'contains "-" character' => ['X-Forwarded-For', 'x-forwarded-for'],
            'contains "_" character' => ['X-App_Version', 'x-app_version'],
            'contains "." character' => ['GraphQL.Query-Type', 'graphql.query-type'],
            'contains "+" character' => ['X-Plugin+Auth', 'x-plugin+auth'],
            'contains "*" character' => ['X-Crypto*Cipher', 'x-crypto*cipher'],
            'contains "!" character' => ['X-Urgent!', 'x-urgent!'],
            'contains "~" character' => ['X-Cache~Status', 'x-cache~status'],
            'contains "^" character' => ['X-Math^Power', 'x-math^power'],
            'contains "%" character' => ['X-Progress%', 'x-progress%'],
            'contains "&" character' => ['X-User&Role', 'x-user&role'],
            'contains "#" character' => ['X-Build#Num', 'x-build#num'],
            'contains "\'" character' => ["X-Author's-Id", "x-author's-id"],
            'contains "$" character' => ['X-Context-$TenantID', 'x-context-$tenantid'],
            'contains "`" character' => ['X-Database-`users`-Fields', 'x-database-`users`-fields'],
        ];
    }

    #[DataProvider('invalidHeaderNames')]
    public function testItThrowsAnInvalidArgumentExceptionIfTheHeaderNameHasInvalidTypeOrContainsInvalidCharacters(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Response('', 200, [$name => 'some value']);
    }

    public static function invalidHeaderNames(): array
    {
        return [
            'contains "\" character' => ['Some\name'],
            'empty line' => [''],
            'contains " " character' => ['Content Type'],
            'contains ":" character' => ['Content-Type:'],
            'contains non-ascii letters' => ['Название'],
            'contains "\n" character at the end' => ["content-type\n"],
        ];
    }

    public function testItThrowsAnInvalidArgumentExceptionIfThereAreDuplicateKeysInTheHeadersArray(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Response('', 200, [
            'Content-Type' => 'some value 1',
            'content-Type' => 'some value 2',
        ]);
    }

    #[DataProvider('invalidHeaderValues')]
    public function testItThrowsAnInvalidArgumentExceptionIfTheHeaderValueContainsForbiddenCharacters(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Response('', 200, ['content-type' => $value]);
    }

    public static function invalidHeaderValues(): array
    {
        return [
            'contains "\r" character at the start' => ["\rsomevalue"],
            'contains "\r" character at the middle' => ["some\rvalue"],
            'contains "\r" character at the end' => ["some value\r"],
            'contains "\n" character at the start' => ["\nsome value"],
            'contains "\n" character at the middle' => ["some \nvalue"],
            'contains "\n" character at the end' => ["some value\n"],
            'contains "\0" character at the start' => ["\0sme value"],
            'contains "\0" character at the middle' => ["s\0me value"],
            'contains "\0" character at the end' => ["sme value\0"],
        ];
    }

    public function testItSetsAnEmptyArrayForHeadersAsTheDefaultValue(): void
    {
        $response = new Response();
        self::assertSame([], $response->getHeaders());
    }

    public function testItPreservesTheCaseOfHeaderValuesWhenNormalizingTheArray(): void
    {
        $response = new Response('', 200, [
            'content-type' => 'Some Value',
            'content-language' => 'EN',
            'www-authenticate' => 'Basic realm="Top Secret"',
        ]);
        self::assertSame(
            [
                'content-type' => 'Some Value',
                'content-language' => 'EN',
                'www-authenticate' => 'Basic realm="Top Secret"',
            ],
            $response->getHeaders(),
        );
    }

    public function testItAnEmptyHeaderValueInTheHeadersArrayIsPermissible(): void
    {
        $response = new Response('', 200, ['content-type' => '']);
        self::assertSame(
            ['content-type' => ''], 
            $response->getHeaders(),
        );
    }

    #[DataProvider('invalidTypesOfHeadersNameOrValues')]
    public function testItThrowsAnInvalidArgumentExceptionIfTheKeyNameOrValueHasAnInvalidType(mixed $name, mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Response('', 200, [$name => $value]);
    }

    public static function invalidTypesOfHeadersNameOrValues(): array
    {
        return [
            'name has type "null"' => [null, 'some value'],
            'name has type "integer"' => [5, 'some value'],
            'name has type "boolean"' => [true, 'some value'],
            'value has type "null"' => ['content-type', null],
            'value has type "integer"' => ['content-type', 5],
            'value has type "array"' => ['content-type', []],
            'value has type "boolean"' => ['content-type', true],
            'value has type "object"' => ['content-type', new stdClass()],
        ];
    }
}
