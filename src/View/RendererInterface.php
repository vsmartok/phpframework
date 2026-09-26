<?php

declare(strict_types=1);

namespace PHPFramework\View;

interface RendererInterface
{
    /**
     * @param string $templatePath Absolute path to the template file selected by the application
     * @param array<string, mixed> $data Data to be passed to the template
     * @return string The generated text, without sending the output externally
     */
    public function render(string $templatePath, array $data = []): string;
}
