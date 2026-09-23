<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use PHPFramework\Http\Response;
use PHPFramework\Http\ResponseEmitter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResponseEmitter::class)]
#[UsesClass(Response::class)]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ResponseEmitterTest extends TestCase
{
    public function testItAcceptsResponseObjectAndSendsTheStatusCodeAndResponseBodyFromIt(): void
    {
        $this->expectOutputString('page not found');

        $response = new Response('page not found', 404);
        (new ResponseEmitter)->emit($response);

        self::assertSame(404, http_response_code());
    }
}