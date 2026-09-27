<?php

declare(strict_types=1);

namespace PHPFramework\Http;

readonly class ResponseHeaderHandler implements RequestHandlerInterface
{
    public function __construct(
        private RequestHandlerInterface $next,
        private string $name,
        private string $value
    ) {
    }

    public function handle(Request $request): Response
    {
        $response = $this->next->handle($request);

        return $response->withHeader($this->name, $this->value);
    }
}
