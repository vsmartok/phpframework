<?php

declare(strict_types=1);

namespace PHPFramework\Http;

final class MiddlewareChainBuilder
{
    public function build(RequestHandlerInterface $last, MiddlewareInterface ...$middlewares): RequestHandlerInterface
    {
        $reversedMiddlewares = array_reverse($middlewares);

        foreach ($reversedMiddlewares as $middleware) {
            $last = new MiddlewareHandler($middleware, $last);
        }

        return $last;
    }
}
