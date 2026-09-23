<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use InvalidArgumentException;
use LogicException;
use PHPFramework\Http\Request;
use PHPFramework\Http\Response;
use PHPFramework\Routing\RouteNotFoundException;
use PHPFramework\Routing\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use TypeError;

#[CoversClass(Router::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
#[UsesClass(RouteNotFoundException::class)]
final class RouterTest extends TestCase
{
    #[DataProvider('allowedMethodNamesToNormalize')]
    public function testAddMethodNormalizesTheMethodNameToUppercase(string $methodName, string $expectedResult): void
    {
        $router = new Router();
        $router->add($methodName, '/', fn(Request $request): Response => new Response('result body'));

        $request = new Request($expectedResult, '/');
        $response = $router->dispatch($request);

        self::assertSame('result body', $response->getBody());
    }

    public static function allowedMethodNamesToNormalize(): array
    {
        return [
            'all letters in lowercase' => ['put', 'PUT'],
            'mixed-case lettering' => ['pOsT', 'POST'],
            'lowercase letters with a capital letter at the end' => ['deletE', 'DELETE'],
            'all letters in uppercase' => ['GET', 'GET'],
        ];
    }

    #[DataProvider('allowedPathValues')]
    public function testAddMethodAcceptsValidValuesForThePathParameter(string $path): void
    {
        $request = new Request('GET', $path);

        $router = new Router();
        $router->add('GET', $path, fn (Request $request): Response => new Response('result body'));

        $response = $router->dispatch($request);

        self::assertSame('result body', $response->getBody());
    }

    public static function allowedPathValues(): array
    {
        return [
            'lowercase letters' => ['/articles'],
            'starts with a capital letter' => ['/Articles'],
            'ends with a slash' => ['/articles/'],
            'contains an encoded " " character' => ['/articles/hello%20world'],
            'contains an encoded "#" character' => ['/articles/tag%23php'],
            'contains a hyphen and digits' => ['/articles-text12'],
            'contains hyphens, digits, and underscores' => ['/-articles_some-text-1'],
        ];
    }

    public function testAddMethodRegistersTheHandlerButDoesNotCallIt(): void
    {
        $handlerIsCalled = false;

        $router = new Router();
        $router->add('POST', '/', function (Request $request) use (&$handlerIsCalled): Response {
            $handlerIsCalled = true;

            return new Response();
        });

        self::assertFalse($handlerIsCalled);
    }

    public function testAddMethodThrowsLogicExceptionWhenReRegisteringTheSameMethodAndPathPair(): void
    {
        $router = new Router();
        $router->add('POST', '/home', fn(Request $request): Response => new Response('test body #1'));

        $this->expectException(LogicException::class);

        $router->add('POST', '/home', fn(Request $request): Response => new Response('test body #2'));

    }

    public function testAddMethodRegistersTheSamePathWithDifferentMethods(): void
    {
        $router = new Router();
        $router->add('GET', '/home', fn(Request $request): Response => new Response('test body GET'));
        $router->add('POST', '/home', fn(Request $request): Response => new Response('test body POST'));

        $responseOne = $router->dispatch(new Request('GET', '/home'));
        $responseTwo = $router->dispatch(new Request('POST', '/home'));

        self::assertSame('test body GET', $responseOne->getBody());
        self::assertSame('test body POST', $responseTwo->getBody());
    }

    public function testDispatchMethodInvokesTheHandlerOnceIfTheMethodAndPathMatch(): void
    {
        $handlerExecutionsCounter = 0;

        $router = new Router();
        $router->add('POST', '/path-1', function (Request $request) use (&$handlerExecutionsCounter): Response {
            $handlerExecutionsCounter++;

            return new Response('path-1 body');
        });

        $response = $router->dispatch(new Request('POST', '/path-1'));

        self::assertSame('path-1 body', $response->getBody());
        self::assertSame(1, $handlerExecutionsCounter);
    }

    public function testDispatchMethodPassesTheSameRequestObjectToTheHandlerThatItReceived(): void
    {
        $request = new Request('GET', '/');

        $router = new Router();
        $router->add('GET', '/', function (Request $receivedRequest) use ($request): Response {
            self::assertSame($request, $receivedRequest);

            return new Response();
        });

        $router->dispatch($request);
    }

    public function testDispatchMethodReturnsTheSameResponseObjectAsTheHandler(): void
    {
        $returnedResponse = null;

        $router = new Router();
        $router->add('GET', '/', function (Request $request) use (&$returnedResponse): Response {
            $returnedResponse = new Response();

            return $returnedResponse;
        });

        $response = $router->dispatch(new Request('GET', '/'));

        self::assertSame($returnedResponse, $response);
    }

    public function testDispatchMethodThrowsRouteNotFoundExceptionIfNoMatchIsFoundAmongTheRegisteredRoutes(): void
    {
        $router = new Router();
        $router->add('GET', '/', fn(Request $request): Response => new Response());

        $this->expectException(RouteNotFoundException::class);
        $router->dispatch(new Request('GET', '/home'));
    }

    public function testDispatchMethodThrowsTypeErrorIfTheHandlerReturnsResponseThatIsNotOfTypeResponse(): void
    {
        $router = new Router();
        $router->add('GET', '/', fn(Request $request) => 'some data');

        $this->expectException(TypeError::class);
        $router->dispatch(new Request('GET', '/'));
    }

    #[DataProvider('mismatchedRequestPathPairs')]
    public function testDispatchMethodThrowsRouteNotFoundExceptionIfThePathDoesNotMatchCompletely(string $routePath, string $requestPath): void
    {
        $router = new Router();
        $router->add('GET', $routePath, fn(Request $request): Response => new Response());

        $this->expectException(RouteNotFoundException::class);
        $router->dispatch(new Request('GET', $requestPath));
    }

    public static function mismatchedRequestPathPairs(): array
    {
        return [
            'the paths differ in letter case' => ['/articles', '/Articles'],
            'paths are distinguished by the presence of a trailing slash' => ['/articles', '/articles/'],
        ];
    }

    public function testDispatchMethodThrowRouteNotFoundExceptionIfThePathIsRegisteredButTheMethodForTheRequestIsNot(): void
    {
        $router = new Router();
        $router->add('GET', '/articles', fn(Request $request): Response => new Response());

        $this->expectException(RouteNotFoundException::class);
        $router->dispatch(new Request('POST', '/articles'));
    }

    public function testAddRejectsDuplicateRouteAndPreservesOriginalHandler(): void
    {
        $router = new Router();
        $router->add('GET', '/', fn(Request $request): Response => new Response('GET handler - first'));

        try {
            $router->add('get', '/', fn(Request $request): Response => new Response('GET handler - second'));
            self::fail('The "add" method made it possible to re-register a new handler for an existing request method–path pair.');
        } catch (LogicException) {}

        $response = $router->dispatch(new Request('GET', '/'));

        self::assertSame('GET handler - first', $response->getBody());
    }

    public function testDispatchMethodPropagatesTheExceptionThrownByTheHandler(): void
    {
        $runtimeException = new RuntimeException();

        $router = new Router();
        $router->add('GET', '/', function (Request $request) use ($runtimeException): Response {
            throw $runtimeException;
        });

        try {
            $router->dispatch(new Request('GET', '/'));
            self::fail('The handler did not propagate the pre-prepared exception.');
        } catch (RuntimeException $e) {
            self::assertSame($runtimeException, $e);
        }
    }

    #[DataProvider('invalidIncomingParameterForAddMethod')]
    public function testAddMethodThrowsAnInvalidArgumentExceptionGivenInvalidInputData(string $method, string $path): void
    {
        $router = new Router();
        
        $this->expectException(InvalidArgumentException::class);

        $router->add($method, $path, fn(Request $request): Response => new Response());
    }

    public static function invalidIncomingParameterForAddMethod(): array
    {
        return [
            'method name contains numbers' => ['GET1', '/'],
            'path is empty' => ['GET', ''],
            'empty method' => ['', '/'],
            'path without leading slash' => ['GET', 'home'],
            'non-ascii characters in method name' => ['GÉT', '/'],
        ];
    }
}