<?php

declare(strict_types=1);

use PHPFramework\Http\Response;
use PHPFramework\Http\ResponseEmitter;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

header('X-Test: value #1');

$time = time() - 60;

$response = new Response(
    'test body',
    200,
    [
        'content-type' => 'text/plain; charset=utf-8',
        'content-Language' => 'en',
        'Last-Modified' => gmdate('D, d M Y H:i:s', $time).' GMT',
        'X-Test' => 'value #2',
        'Location' => '/',
    ],
);

(new ResponseEmitter())->emit($response);