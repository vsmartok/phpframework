<?php

declare(strict_types=1);

namespace PHPFramework\Http;
interface RequestHandlerInterface
{
    public function handle(Request $request): Response;
}