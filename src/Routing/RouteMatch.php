<?php

declare(strict_types=1);

namespace PHPFramework\Routing;

use Closure;
use InvalidArgumentException;
use PHPFramework\Http\Request;
use PHPFramework\Http\Response;

final readonly class RouteMatch
{
    /**
     * @var Closure(Request): Response
     */
    private Closure $handler;

    /**
     * @var array<string, string>
     */
    private array $parameters;

    /**
     * @param Closure(Request): Response $handler
     * @param array<string, string> $parameters
     */
    public function __construct(Closure $handler, array $parameters = []) {
        foreach ($parameters as $name => $value) {
            if (!is_string($name) || !is_string($value)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Route parameters must be a type of array<string, string>. Got key of type %s with value of type %s.',
                        get_debug_type($name),
                        get_debug_type($value)
                    )
                );
            }
        }

        $this->handler = $handler;
        $this->parameters = $parameters;
    }

    /**
     * @return Closure(Request): Response
     */
    public function getHandler(): Closure
    {
        return $this->handler;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}