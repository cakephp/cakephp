<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Error;

use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\Error\ErrorLogger;
use Cake\Error\ExceptionTrap;
use Cake\Error\Renderer\ConsoleExceptionRenderer;
use Cake\Error\Renderer\TextExceptionRenderer;
use Cake\Error\Renderer\WebExceptionRenderer;
use Cake\Http\Exception\MissingControllerException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use RuntimeException;
use Throwable;

class ExceptionTrapTest extends TestCase
{
    /**
     * @var string
     */
    private $memoryLimit;

    private $triggered = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->memoryLimit = ini_get('memory_limit');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Log::reset();
        ini_set('memory_limit', $this->memoryLimit);
    }

    public function testConfigExceptionRendererFallback(): void
    {
        $output = new StubConsoleOutput();
        $trap = new ExceptionTrap(['exceptionRenderer' => null, 'stderr' => $output]);
        $error = new InvalidArgumentException('nope');
        $this->assertInstanceOf(ConsoleExceptionRenderer::class, $trap->renderer($error));
    }

    public function testConfigExceptionRenderer(): void
    {
        $trap = new ExceptionTrap(['exceptionRenderer' => WebExceptionRenderer::class]);
        $error = new InvalidArgumentException('nope');
        $this->assertInstanceOf(WebExceptionRenderer::class, $trap->renderer($error));
    }

    public function testConfigExceptionRendererFactory(): void
    {
        $trap = new ExceptionTrap(['exceptionRenderer' => function ($err, $req) {
            return new WebExceptionRenderer($err, $req);
        }]);
        $error = new InvalidArgumentException('nope');
        $this->assertInstanceOf(WebExceptionRenderer::class, $trap->renderer($error));
    }

    public function testConfigRendererHandleUnsafeOverwrite(): void
    {
        $output = new StubConsoleOutput();
        $trap = new ExceptionTrap(['stderr' => $output]);
        $trap->setConfig('exceptionRenderer', null);
        $error = new InvalidArgumentException('nope');
        $this->assertInstanceOf(ConsoleExceptionRenderer::class, $trap->renderer($error));
    }

    public function testLoggerConfig(): void
    {
        $trap = new ExceptionTrap(['logger' => ErrorLogger::class]);
        $this->assertInstanceOf(ErrorLogger::class, $trap->logger());
    }

    public function testLoggerHandleUnsafeOverwrite(): void
    {
        $trap = new ExceptionTrap();
        $trap->setConfig('logger', null);
        $this->assertInstanceOf(ErrorLogger::class, $trap->logger());
    }

    public function testHandleExceptionText(): void
    {
        $trap = new ExceptionTrap([
            'exceptionRenderer' => TextExceptionRenderer::class,
        ]);
        $error = new InvalidArgumentException('nope');

        ob_start();
        $trap->handleException($error);
        $out = ob_get_clean();

        $this->assertStringContainsString('nope', $out);
        $this->assertStringContainsString('ExceptionTrapTest', $out);
    }

    public function testHandleExceptionConsoleRenderingNoStack(): void
    {
        $output = new StubConsoleOutput();
        $trap = new ExceptionTrap([
            'exceptionRenderer' => ConsoleExceptionRenderer::class,
            'stderr' => $output,
        ]);
        $error = new InvalidArgumentException('nope');

        $trap->handleException($error);
        $out = $output->messages();

        $this->assertStringContainsString('nope', $out[0]);
        $this->assertStringNotContainsString('Stack', $out[0]);
    }

    public function testHandleExceptionConsoleRenderingWithStack(): void
    {
        $output = new StubConsoleOutput();
        $trap = new ExceptionTrap([
            'exceptionRenderer' => ConsoleExceptionRenderer::class,
            'stderr' => $output,
            'trace' => true,
        ]);
        $error = new InvalidArgumentException('nope');

        $trap->handleException($error);
        $out = $output->messages();

        $this->assertStringContainsString('nope', $out[0]);
        $this->assertStringContainsString('Stack', $out[0]);
        $this->assertStringContainsString('->testHandleExceptionConsoleRenderingWithStack', $out[0]);
    }

    public function testHandleExceptionConsoleRenderingWithPrevious(): void
    {
        $output = new StubConsoleOutput();
        $trap = new ExceptionTrap([
            'exceptionRenderer' => ConsoleExceptionRenderer::class,
            'stderr' => $output,
            'trace' => true,
        ]);
        $previous = new RuntimeException('underlying error');
        $error = new InvalidArgumentException('nope', 0, $previous);

        $trap->handleException($error);
        $out = $output->messages();

        $this->assertStringContainsString('nope', $out[0]);
        $this->assertStringContainsString('Caused by [RuntimeException] underlying error', $out[0]);
        $this->assertEquals(2, substr_count($out[0], 'Stack Trace'));
    }

    public function testHandleExceptionConsoleWithAttributes(): void
    {
        $output = new StubConsoleOutput();
        $trap = new ExceptionTrap([
            'exceptionRenderer' => ConsoleExceptionRenderer::class,
            'stderr' => $output,
        ]);
        $error = new MissingControllerException(['name' => 'Articles']);

        $trap->handleException($error);
        $out = $output->messages();

        $this->assertStringContainsString('Controller class `Articles`', $out[0]);
        $this->assertStringContainsString('Exception Attributes', $out[0]);
        $this->assertStringContainsString('Articles', $out[0]);
    }

    /**
     * Test integration with HTML exception rendering
     *
     * Run in a separate process because HTML output writes headers.
     */
    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testHandleExceptionHtmlRendering(): void
    {
        $trap = new ExceptionTrap([
            'exceptionRenderer' => WebExceptionRenderer::class,
        ]);
        $error = new InvalidArgumentException('nope');

        ob_start();
        $trap->handleException($error);
        $out = ob_get_clean();

        $this->assertStringContainsString('<!DOCTYPE', $out);
        $this->assertStringContainsString('<html', $out);
        $this->assertStringContainsString('nope', $out);
        $this->assertStringContainsString('class="stack-frame-header"', $out);
        $this->assertStringContainsString('Toggle Arguments', $out);
    }

    public function testLogException(): void
    {
        Log::setConfig('test_error', [
            'className' => 'Array',
        ]);
        $trap = new ExceptionTrap();
        $error = new InvalidArgumentException('nope');
        $trap->logException($error);

        $logs = Log::engine('test_error')->read();
        $this->assertStringContainsString('nope', $logs[0]);
    }

    public function testLogExceptionConfigOff(): void
    {
        Log::setConfig('test_error', [
            'className' => 'Array',
        ]);
        $trap = new ExceptionTrap(['log' => false]);
        $error = new InvalidArgumentException('nope');
        $trap->logException($error);

        $logs = Log::engine('test_error')->read();
        $this->assertEmpty($logs);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testSkipLogException(): void
    {
        Log::setConfig('test_error', [
            'className' => 'Array',
        ]);
        $trap = new ExceptionTrap([
            'exceptionRenderer' => WebExceptionRenderer::class,
            'skipLog' => [InvalidArgumentException::class],
        ]);

        $trap->getEventManager()->on('Exception.beforeRender', function (): void {
            $this->triggered = true;
        });

        ob_start();
        $trap->handleException(new InvalidArgumentException('nope'));
        ob_get_clean();

        $logs = Log::engine('test_error')->read();
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('MissingTemplateException - Failed to render', $logs[0]);
        $this->assertTrue($this->triggered, 'Should have triggered event when skipping logging.');
    }

    public function testEventTriggered(): void
    {
        $trap = new ExceptionTrap(['exceptionRenderer' => TextExceptionRenderer::class]);
        $trap->getEventManager()->on('Exception.beforeRender', function ($event, Throwable $error): void {
            $this->assertEquals(100, $error->getCode());
            $this->assertStringContainsString('nope', $error->getMessage());
        });
        $error = new InvalidArgumentException('nope', 100);

        ob_start();
        $trap->handleException($error);
        $out = ob_get_clean();

        $this->assertNotEmpty($out);
    }

    public function testBeforeRenderEventAborted(): void
    {
        $trap = new ExceptionTrap(['exceptionRenderer' => TextExceptionRenderer::class]);
        $trap->getEventManager()->on('Exception.beforeRender', function ($event, Throwable $error, ?ServerRequest $req): void {
            $this->assertEquals(100, $error->getCode());
            $this->assertStringContainsString('nope', $error->getMessage());
            $event->stopPropagation();
        });
        $error = new InvalidArgumentException('nope', 100);

        ob_start();
        $trap->handleException($error);
        $out = ob_get_clean();

        $this->assertSame('', $out);
    }

    public function testBeforeRenderEventExceptionChanged(): void
    {
        $trap = new ExceptionTrap(['exceptionRenderer' => TextExceptionRenderer::class]);
        $trap->getEventManager()->on('Exception.beforeRender', function ($event, Throwable $error, ?ServerRequest $req): void {
            $event->setData('exception', new NotFoundException());
        });
        $error = new InvalidArgumentException('nope', 100);

        ob_start();
        $trap->handleException($error);
        $out = ob_get_clean();

        $this->assertStringContainsString('404 : Not Found', $out);
    }

    public function testBeforeRenderEventReturnResponse(): void
    {
        $trap = new ExceptionTrap(['exceptionRenderer' => TextExceptionRenderer::class]);
        $trap->getEventManager()->on('Exception.beforeRender', function ($event, Throwable $error, ?ServerRequest $req) {
            return 'Here B Erroz';
        });

        ob_start();
        $trap->handleException(new NotFoundException());
        $out = ob_get_clean();

        $this->assertSame('Here B Erroz', $out);
    }

    public function testHandleShutdownNoOp(): void
    {
        $trap = new ExceptionTrap([
            'exceptionRenderer' => TextExceptionRenderer::class,
        ]);
        ob_start();
        $trap->handleShutdown();
        $out = ob_get_clean();

        $this->assertEmpty($out);
    }

    public function testHandleFatalShutdownNoError(): void
    {
        $trap = new ExceptionTrap([
            'exceptionRenderer' => TextExceptionRenderer::class,
        ]);
        error_clear_last();
        ob_start();
        $trap->handleShutdown();
        $out = ob_get_clean();

        $this->assertSame('', $out);
    }

    public function testHandleFatalErrorText(): void
    {
        $trap = new ExceptionTrap([
            'exceptionRenderer' => TextExceptionRenderer::class,
        ]);
        ob_start();
        $trap->handleFatalError(E_USER_ERROR, 'Something bad', __FILE__, __LINE__);
        $out = ob_get_clean();

        $this->assertStringContainsString('500 : Fatal Error', $out);
        $this->assertStringContainsString('Something bad', $out);
        $this->assertStringContainsString(__FILE__, $out);
    }

    /**
     * Test integration with HTML rendering for fatal errors
     *
     * Run in a separate process because HTML output writes headers.
     */
    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testHandleFatalErrorHtmlRendering(): void
    {
        $trap = new ExceptionTrap([
            'exceptionRenderer' => WebExceptionRenderer::class,
        ]);

        ob_start();
        $trap->handleFatalError(E_USER_ERROR, 'Something bad', __FILE__, __LINE__);
        $out = ob_get_clean();

        $this->assertStringContainsString('<!DOCTYPE', $out);
        $this->assertStringContainsString('<html', $out);
        $this->assertStringContainsString('Something bad', $out);
        $this->assertStringContainsString(__FILE__, $out);
    }

    /**
     * Data provider for memory limit increase
     */
    public static function initialMemoryProvider(): array
    {
        return [
            ['256M'],
            ['1G'],
        ];
    }

    #[DataProvider('initialMemoryProvider')]
    public function testIncreaseMemoryLimit($initial): void
    {
        ini_set('memory_limit', $initial);
        $this->assertEquals($initial, ini_get('memory_limit'));

        $trap = new ExceptionTrap([
            'exceptionRenderer' => TextExceptionRenderer::class,
        ]);
        $trap->increaseMemoryLimit(4 * 1024);
        $initialBytes = Text::parseFileSize($initial, false);
        $result = Text::parseFileSize(ini_get('memory_limit'), false);
        $this->assertWithinRange($initialBytes + (4 * 1024 * 1024), $result, 1024);
    }

    public function testSingleton(): void
    {
        $trap = new ExceptionTrap();
        $trap->register();
        $this->assertSame($trap, ExceptionTrap::instance());

        $trap->unregister();
        $this->assertNull(ExceptionTrap::instance());
    }
}
