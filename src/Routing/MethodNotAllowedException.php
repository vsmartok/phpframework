<?php

declare(strict_types=1);

namespace PHPFramework\Routing;

use RuntimeException;
use Throwable;

final class MethodNotAllowedException extends RuntimeException
{
    private readonly array $allowedMethods;

    /**
     * @param list<string> $allowedMethods
     */
    public function __construct(array $allowedMethods, string $message = "", int $code = 0, Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->allowedMethods = $allowedMethods;
    }

    /**
     * @return list<string>
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}