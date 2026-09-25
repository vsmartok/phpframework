<?php

declare(strict_types=1);

namespace Tests\Integration\View;

use InvalidArgumentException;
use PHPFramework\View\PhpRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;
use TypeError;

#[CoversClass(PhpRenderer::class)]
final class PhpRendererTest extends TestCase
{
    public function testRenderMethodThrowsAnInvalidArgumentExceptionIfThePathDoesNotPointToAnExistingFile(): void
    {
        $templatePath = dirname(__DIR__, 2) . '/Fixtures/views/non-existing-template.php';

        $phpRender = new PhpRenderer();

        $this->expectException(InvalidArgumentException::class);
        $phpRender->render($templatePath);
    }

    public function testRenderMethodThrowsAnInvalidArgumentExceptionIfThePathDoesNotPointToAnReadableFile(): void
    {
        $templatePath = tempnam(sys_get_temp_dir(), 'tpl_');

        try {
            
            file_put_contents($templatePath, 'Test content');
            chmod($templatePath, 0200);

            $phpRender = new PhpRenderer();

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage(
                sprintf('Template file "%s" does not exist or is not readable.', $templatePath),
            );

            $phpRender->render($templatePath);
        } finally {
            if (file_exists($templatePath )) {
                chmod($templatePath , 0644);
                unlink($templatePath );
            }
        }
    }

    public function testRenderMethodThrowsAnInvalidArgumentExceptionIfThePathToDirectory(): void
    {
        $templatePath = dirname(__DIR__, 2) . '/Fixtures/views';
        $phpRender = new PhpRenderer();

        $this->expectException(InvalidArgumentException::class);
        $phpRender->render($templatePath);
    }

    public function testRenderMethodReturnsTheTemplateOutput(): void
    {
        $templatePath = dirname(__DIR__, 2) . '/Fixtures/views/template-one.php';
        $phpRender = new PhpRenderer();
        $output = $phpRender->render($templatePath);

        self::assertSame(
            '<p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>',
            $output,
        );
    }

    public function testRenderMethodPassesDataToTheTemplateViaTheDataArray(): void
    {
        $templatePath = dirname(__DIR__, 2) . '/Fixtures/views/template-with-data.php';
        $phpRender = new PhpRenderer();
        $output = $phpRender->render($templatePath, ['firstname' => 'Sergii', 'lastname' => 'Nikolaevich']);

        self::assertSame(
            '<p>Hello, Sergii Nikolaevich</p>',
            $output,
        );
    }

    public function testRenderMethodReturnsAnEmptyStringIfThePatternIsEmpty(): void
    {
        $templatePath = dirname(__DIR__, 2) . '/Fixtures/views/template-empty.php';
        $phpRender = new PhpRenderer();
        $output = $phpRender->render($templatePath);

        self::assertSame('', $output );
        
    }

    public function testRenderMethodRepeatedCallExecutesTheTemplateAgain(): void
    {
        $templatePath = dirname(__DIR__, 2) . '/Fixtures/views/template-two.php';
        $phpRender = new PhpRenderer();
        $outputOne = $phpRender->render($templatePath, ['year' => 1966]);
        $outputTwo = $phpRender->render($templatePath, ['year' => 2000]);

        self::assertSame('<p>Lorem Ipsum has been the industry\'s standard dummy text ever since 1966.</p>', $outputOne);
        self::assertSame('<p>Lorem Ipsum has been the industry\'s standard dummy text ever since 2000.</p>', $outputTwo);
    }

    #[DataProvider('exceptionForRenderMethodTest')]
    public function testRenderMethodPropagatesTheExceptionThrownFromTheTemplate(Throwable $expectedException): void
    {
        $templatePath = dirname(__DIR__, 2) . '/Fixtures/views/template-with-exception.php';
        $phpRender = new PhpRenderer();

        try {
            $phpRender->render($templatePath, ['exception' => $expectedException]);
        } catch (Throwable $e) {
            self::assertSame($e, $expectedException);
            return;
        }

        self::fail('The PhpRender::render() method did not propagate the exception, or the error did not occur in the template.');
    }

    public static function exceptionForRenderMethodTest(): array
    {
        return [
            'throw new InvalidArgumentException' => [new InvalidArgumentException()],
            'throw new TypeError' => [new TypeError()],
        ];
    }

    #[DataProvider('exceptionForRenderMethodTest')]
    public function testRenderMethodPreservesTheExternalBufferWhenAnExceptionIsThrownFromTheTemplate(Throwable $expectedException): void
    {
        $bufferLevel = ob_get_level();

        try {
            $isExceptionThrown = false;

            $templatePath = dirname(__DIR__, 2) . '/Fixtures/views/template-with-exception.php';
            $phpRender = new PhpRenderer();
            
            ob_start();
            echo 'There are many variations of passages of Lorem Ipsum available.';

            try {
                $phpRender->render($templatePath, ['exception' => $expectedException]);
            } catch (Throwable $e) {
                $isExceptionThrown = true;
                self::assertSame($expectedException, $e);
            } finally {
                $output = ob_get_clean();
                self::assertSame($bufferLevel, ob_get_level());
            }
            self::assertTrue($isExceptionThrown);
            self::assertSame('There are many variations of passages of Lorem Ipsum available.', $output);
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
        }
    }

    public function testRenderMethodPreservesTheExternalBufferUponSuccessfulTemplateRendering(): void
    {
        $templatePath = dirname(__DIR__, 2) . '/Fixtures/views/template-two.php';

        $phpRender = new PhpRenderer();

        $bufferLevel = ob_get_level();

        try {
            ob_start();
            echo 'There are many variations of passages of Lorem Ipsum available.';
            
            $renderBufferOutput = $phpRender->render($templatePath, ['year' => '2001']);

            $outerBufferOutput = ob_get_contents();
            
            self::assertSame($bufferLevel + 1, ob_get_level());
            self::assertSame('<p>Lorem Ipsum has been the industry\'s standard dummy text ever since 2001.</p>', $renderBufferOutput);
            self::assertSame('There are many variations of passages of Lorem Ipsum available.', $outerBufferOutput);
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
        }
    }
}
