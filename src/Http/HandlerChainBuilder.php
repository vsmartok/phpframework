<?php

declare(strict_types = 1);

namespace PHPFramework\Http;

use Closure;
use TypeError;

final class HandlerChainBuilder
{
    /**
     * @param RequestHandlerInterface $last
     * @param Closure(RequestHandlerInterface):RequestHandlerInterface  ...$decorators
     * @return RequestHandlerInterface
     */
    public function build(
        RequestHandlerInterface $last,
        Closure ...$decorators
    ): RequestHandlerInterface {
        $reversedDecorators = array_reverse($decorators, preserve_keys: true);
        foreach ($reversedDecorators as $index => $decorator) {
            $last = $decorator($last);

            if (!$last instanceof RequestHandlerInterface) {
                throw new TypeError(
                    sprintf(
                        'Decorator at index %d must return an instance of %s, %s returned.',
                        $index,
                        RequestHandlerInterface::class,
                        get_debug_type($last)
                    )
                );
            }
        }

        return $last;
    }
}
