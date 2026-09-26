<?php

declare(strict_types=1);

namespace PHPFramework\View;

use InvalidArgumentException;
use Throwable;

final class PhpRenderer implements RendererInterface
{
    /**
     * @param array<string,mixed> $data
     */
    public function render(string $templatePath, array $data = []): string
    {
        if (!is_file($templatePath) || !is_readable($templatePath)) {
            throw new InvalidArgumentException(
                sprintf('Template file "%s" does not exist or is not readable.', $templatePath),
            );
        }

        $isolatedRenderer = static function (string $templatePath, array $data = []): void {
            require $templatePath;
        };

        ob_start();

        try {
            $isolatedRenderer($templatePath, $data);
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return ob_get_clean();
    }
}
