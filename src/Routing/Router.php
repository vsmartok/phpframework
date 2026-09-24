<?php

declare(strict_types=1);

namespace PHPFramework\Routing;

use Closure;
use LogicException;
use PHPFramework\Http\Request;
use PHPFramework\Http\Response;
use TypeError;

final class Router
{
    /** 
     * @var array<string,array<string,Closure(Request):Response>> 
     */
    private array $routes = [];

    /**
     * @param Closure(Request):Response $handler
     */
    public function add(string $method, string $path, Closure $handler): void
    {
        $request = new Request($method, $path);

        if (isset($this->routes[$request->getPath()][$request->getMethod()])) {
            throw new LogicException(
                sprintf('Cannot register route: %s "%s" is already registered.', $method, $path)
            );
        }

        $this->routes[$request->getPath()][$request->getMethod()] = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        if (!isset($this->routes[$path])) {
            throw new RouteNotFoundException(
                sprintf('Route not found: [%s] "%s".', $method, $path),
            );
        }

        if (!isset($this->routes[$path][$method])) {
            $allowedMethods = array_keys($this->routes[$path]); 
            throw new MethodNotAllowedException($allowedMethods);
        }

        $handler = $this->routes[$path][$method];

        $response = $handler($request);

        if (!$response instanceof Response) {
            throw new TypeError(
                sprintf(
                    'The router handler for [%s] "%s" must return an instance of %s, %s returned.',
                    $method,
                    $path,
                    Response::class,
                    get_debug_type($response),
                ),
            );
        }

        return $response;
    }
}
