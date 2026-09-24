<?php

declare(strict_types=1);

namespace PHPFramework\Http;

use InvalidArgumentException;

final readonly class Response
{
    private string $body;

    private int $statusCode;

    /**
     * @var array<string,string>
     */
    private array $headers;

    public function __construct(string $body = '', int $statusCode = 200, array $headers = []) {
        if ($statusCode < 100 || $statusCode > 599) {
            throw new InvalidArgumentException(
                sprintf('Invalid HTTP status code: %d. Code must be an integer between 100 and 599.', $statusCode),
            );
        }

        $normalizedHeaders = [];
        foreach ($headers as $key => $value) {
            if (!is_string($key) || !preg_match("/^[a-zA-Z0-9!#$%&'*+\-.^_`|~]+$/D", $key)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid HTTP header name "%s". It contains forbidden characters. Refer to RFC 9110 for valid tchar tokens.', $key),
                );
            }

            $normalizedKey = strtolower($key);
            if (array_key_exists($normalizedKey, $normalizedHeaders)) {
                throw new InvalidArgumentException(
                    sprintf('The HTTP header "%s" is already defined. Header names must be unique.', $key),
                );
            }

            if (!is_string($value) || !preg_match("/^[^\r\n\0]*$/D", $value)) {
                throw new InvalidArgumentException(
                    'Invalid HTTP header value. It must not contain control characters like CR (\r), LF (\n), or NUL (\0).'
                );
            }
            
            $normalizedHeaders[$normalizedKey] = $value;
        }

        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $normalizedHeaders;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string,string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}