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
use Cake\Error\ExceptionRenderer;
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
use RuntimeException;
use TestApp\Error\LegacyErrorLogger;
use Throwable;

class ExceptionTrapTest extends TestCase
{
    /**
     * @var string
     */
    private $memoryLimit;

    private $triggered = false;

    public function setUp(): void
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

    public function testConfigExceptionRendererFallbackInCli()
    {
        $this->deprecated(function () {
            $output = new StubConsoleOutput();
            $trap = new ExceptionTrap(['exceptionRenderer' => ExceptionRenderer::class, 'stderr' => $output]);
            $error = new InvalidArgumentException('nope');
            // Even though we asked for ExceptionRenderer we should get a
            // ConsoleExceptionRenderer as we're in a CLI context.
            $this->assertInstanceOf(ConsoleExceptionRenderer::class, $trap->renderer($error));
        });
    }

    public function testConfigExceptionRendererFallback()
    {
        $output = new StubConsoleOutput();
        $trap = new ExceptionTrap(['exceptionRenderer' => null, 'stderr' => $output]);
        $error = new InvalidArgumentException('nope');
        $this->assertInstanceOf(ConsoleExceptionRenderer::class, $trap->renderer($error));
    }

    public function testConfigExceptionRenderer()
    {
        $trap = new ExceptionTrap(['exceptionRenderer' => WebExceptionRenderer::class]);
        $error = new InvalidArgumentException('nope');
        $this->assertInstanceOf(WebExceptionRenderer::class, $trap->renderer($error));
    }

    public function testConfigExceptionRendererFactory()
    {
        $trap = new ExceptionTrap(['exceptionRenderer' => function ($err, $req) {
            return new WebExceptionRenderer($err, $req);
        }]);
        $error = new InvalidArgumentException('nope');
        $this->assertInstanceOf(WebExceptionRenderer::class, $trap->renderer($error));
    }

    public function testConfigRendererHandleUnsafeOverwrite()
    {
        $output = new StubConsoleOutput();
        $trap = new ExceptionTrap(['stderr' => $output]);
        $trap->setConfig('exceptionRenderer', null);
        $error = new InvalidArgumentException('nope');
        $this->assertInstanceOf(ConsoleExceptionRenderer::class, $trap->renderer($error));
    }

    public function testLoggerConfig()
    {
        $trap = new ExceptionTrap(['logger' => ErrorLogger::class]);
        $this->assertInstanceOf(ErrorLogger::class, $trap->logger());
    }

    public function testLoggerHandleUnsafeOverwrite()
    {
        $trap = new ExceptionTrap();
        $trap->setConfig('logger', null);
        $this->assertInstanceOf(ErrorLogger::class, $trap->logger());
    }

    public function testHandleExceptionText()
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

    public function testHandleExceptionConsoleRenderingNoStack()
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

    public function testHandleExceptionConsoleRenderingWithStack()
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

    public function testHandleExceptionConsoleRenderingWithPrevious()
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

    public function testHandleExceptionConsoleWithAttributes()
    {
        $output = new StubConsoleOutput();
        $trap = new ExceptionTrap([
            'exceptionRenderer' => ConsoleExceptionRenderer::class,
            'stderr' => $output,
        ]);
        $error = new MissingControllerException(['name' => 'Articles']);

        $trap->handleException($error);
        $out = $output->messages();

        $this->assertStringContainsString('Controller class Articles', $out[0]);
        $this->assertStringContainsString('Exception Attributes', $out[0]);
        $this->assertStringContainsString('Articles', $out[0]);
    }

    /**
     * Test integration with HTML exception rendering
     *
     * Run in a separate process because HTML output writes headers.
     *
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testHandleExceptionHtmlRendering()
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

    public function testLogException()
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

    public function testLogExceptionConfigOff()
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

    public function testLogExceptionDeprecatedLoggerMethods()
    {
        Log::setConfig('test_error', [
            'className' => 'Array',
        ]);
        $trap = new ExceptionTrap([
            'log' => true,
            'logger' => LegacyErrorLogger::class,
            'trace' => true,
        ]);
        $error = new InvalidArgumentException('nope');
        $request = new ServerRequest(['url' => '/articles/view/1']);
        $this->deprecated(function () use ($trap, $error, $request) {
            $trap->logException($error, $request);
        });

        $logs = Log::engine('test_error')->read();
        $this->assertStringContainsString('nope', $logs[0]);
        $this->assertStringContainsString('IncludeTrace', $logs[0]);
        $this->assertStringContainsString('URL=http://localhost/articles/view/1', $logs[0]);
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testSkipLogException(): void
    {
        Log::setConfig('test_error', [
            'className' => 'Array',
        ]);
        $trap = new ExceptionTrap([
            'exceptionRenderer' => WebExceptionRenderer::class,
            'skipLog' => [InvalidArgumentException::class],
        ]);

        $trap->getEventManager()->on('Exception.beforeRender', function () {
            $this->triggered = true;
        });

        ob_start();
        $trap->handleException(new InvalidArgumentException('nope'));
        ob_get_clean();

        $logs = Log::engine('test_error')->read();
        $this->assertEmpty($logs);
        $this->assertTrue($this->triggered, 'Should have triggered event when skipping logging.');
    }

    public function testEventTriggered()
    {
        $trap = new ExceptionTrap(['exceptionRenderer' => TextExceptionRenderer::class]);
        $trap->getEventManager()->on('Exception.beforeRender', function ($event, Throwable $error) {
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
        $trap->getEventManager()->on('Exception.beforeRender', function ($event, Throwable $error, ?ServerRequest $req) {
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
        $trap->getEventManager()->on('Exception.beforeRender', function ($event, Throwable $error, ?ServerRequest $req) {
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

    public function testHandleShutdownNoOp()
    {
        $trap = new ExceptionTrap([
            'exceptionRenderer' => TextExceptionRenderer::class,
        ]);
        ob_start();
        $trap->handleShutdown();
        $out = ob_get_clean();

        $this->assertEmpty($out);
    }

    public function testHandleFatalShutdownNoError()
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

    public function testHandleFatalErrorText()
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
     *
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testHandleFatalErrorHtmlRendering()
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

    /**
     * @dataProvider initialMemoryProvider
     */
    public function testIncreaseMemoryLimit($initial)
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

    public function testSingleton()
    {
        $trap = new ExceptionTrap();
        $trap->register();
        $this->assertSame($trap, ExceptionTrap::instance());

        $trap->unregister();
        $this->assertNull(ExceptionTrap::instance());
    }
}
