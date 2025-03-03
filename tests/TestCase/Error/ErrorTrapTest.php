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
use Cake\Core\Configure;
use Cake\Error\ErrorLogger;
use Cake\Error\ErrorTrap;
use Cake\Error\FatalErrorException;
use Cake\Error\PhpError;
use Cake\Error\Renderer\ConsoleErrorRenderer;
use Cake\Error\Renderer\HtmlErrorRenderer;
use Cake\Error\Renderer\TextErrorRenderer;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ErrorTrapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Log::drop('test_error');
        Router::reload();
    }

    public function testConfigErrorRendererFallback(): void
    {
        $trap = new ErrorTrap(['errorRenderer' => null]);
        $this->assertInstanceOf(ConsoleErrorRenderer::class, $trap->renderer());
    }

    public function testConfigErrorRenderer(): void
    {
        $trap = new ErrorTrap(['errorRenderer' => HtmlErrorRenderer::class]);
        $this->assertInstanceOf(HtmlErrorRenderer::class, $trap->renderer());
    }

    public function testConfigRendererHandleUnsafeOverwrite(): void
    {
        $trap = new ErrorTrap();
        $trap->setConfig('errorRenderer', null);
        $this->assertInstanceOf(ConsoleErrorRenderer::class, $trap->renderer());
    }

    public function testLoggerConfig(): void
    {
        $trap = new ErrorTrap(['logger' => ErrorLogger::class]);
        $this->assertInstanceOf(ErrorLogger::class, $trap->logger());
    }

    public function testLoggerHandleUnsafeOverwrite(): void
    {
        $trap = new ErrorTrap();
        $trap->setConfig('logger', null);
        $this->assertInstanceOf(ErrorLogger::class, $trap->logger());
    }

    public function testRegisterAndRendering(): void
    {
        $trap = new ErrorTrap(['errorRenderer' => TextErrorRenderer::class]);
        $trap->register();
        ob_start();
        trigger_error('Oh no it was bad', E_USER_NOTICE);
        $output = ob_get_clean();
        restore_error_handler();

        $this->assertStringContainsString('Oh no it was bad', $output);
    }

    public function testRegisterAndHandleFatalUserError(): void
    {
        $trap = new ErrorTrap(['errorRenderer' => TextErrorRenderer::class]);
        $trap->register();
        try {
            trigger_error('Oh no it was bad', E_USER_ERROR);
            $this->fail('Should raise a fatal error');
        } catch (FatalErrorException $e) {
            $this->assertEquals('Oh no it was bad', $e->getMessage());
            $this->assertEquals(E_USER_ERROR, $e->getCode());
        } finally {
            restore_error_handler();
        }
    }

    public static function logLevelProvider(): array
    {
        return [
            // PHP error level, expected log level
            [E_USER_WARNING, 'warning'],
            [E_USER_NOTICE, 'notice'],
            // Log level is notice on windows because windows log levels are different.
            [E_USER_DEPRECATED, DS === '\\' ? 'notice' : 'debug'],
        ];
    }

    #[DataProvider('logLevelProvider')]
    public function testHandleErrorLoggingLevel($level, $logLevel): void
    {
        Log::setConfig('test_error', [
            'className' => 'Array',
        ]);
        $trap = new ErrorTrap([
            'errorRenderer' => TextErrorRenderer::class,
        ]);
        $trap->register();

        ob_start();
        trigger_error('Oh no it was bad', $level);
        ob_get_clean();
        restore_error_handler();

        $logs = Log::engine('test_error')->read();
        $this->assertStringContainsString('Oh no it was bad', $logs[0]);
        $this->assertStringContainsString($logLevel, $logs[0]);
    }

    public function testHandleErrorLogTrace(): void
    {
        Log::setConfig('test_error', [
            'className' => 'Array',
        ]);
        $trap = new ErrorTrap([
            'errorRenderer' => TextErrorRenderer::class,
            'trace' => true,
        ]);
        $trap->register();

        ob_start();
        trigger_error('Oh no it was bad', E_USER_WARNING);
        ob_get_clean();
        restore_error_handler();

        $logs = Log::engine('test_error')->read();
        $this->assertStringContainsString('Oh no it was bad', $logs[0]);
        $this->assertStringContainsString('Trace:', $logs[0]);
        $this->assertStringContainsString('ErrorTrapTest->testHandleErrorLogTrace', $logs[0]);
    }

    public function testHandleErrorNoLog(): void
    {
        Log::setConfig('test_error', [
            'className' => 'Array',
        ]);
        $trap = new ErrorTrap([
            'log' => false,
            'errorRenderer' => TextErrorRenderer::class,
        ]);
        $trap->register();

        ob_start();
        trigger_error('Oh no it was bad', E_USER_WARNING);
        ob_get_clean();
        restore_error_handler();

        $logs = Log::engine('test_error')->read();
        $this->assertEmpty($logs);
    }

    public function testConsoleRenderingNoTrace(): void
    {
        $stub = new StubConsoleOutput();
        $trap = new ErrorTrap([
            'errorRenderer' => ConsoleErrorRenderer::class,
            'trace' => false,
            'stderr' => $stub,
        ]);
        $trap->register();

        ob_start();
        trigger_error('Oh no it was bad', E_USER_NOTICE);
        ob_get_clean();
        restore_error_handler();

        $out = $stub->messages();
        $this->assertStringContainsString('Oh no it was bad', $out[0]);
        $this->assertStringNotContainsString('Trace', $out[0]);
    }

    public function testConsoleRenderingWithTrace(): void
    {
        $stub = new StubConsoleOutput();
        $trap = new ErrorTrap([
            'errorRenderer' => ConsoleErrorRenderer::class,
            'trace' => true,
            'stderr' => $stub,
        ]);
        $trap->register();

        ob_start();
        trigger_error('Oh no it was bad', E_USER_NOTICE);
        ob_get_clean();
        restore_error_handler();

        $out = $stub->messages();
        $this->assertStringContainsString('Oh no it was bad', $out[0]);
        $this->assertStringContainsString('Trace', $out[0]);
        $this->assertStringContainsString('ErrorTrapTest->testConsoleRenderingWithTrace', $out[0]);
    }

    public function testRegisterNoOutputDebug(): void
    {
        Log::setConfig('test_error', [
            'className' => 'Array',
        ]);
        Configure::write('debug', false);
        $trap = new ErrorTrap(['errorRenderer' => TextErrorRenderer::class]);
        $trap->register();

        ob_start();
        trigger_error('Oh no it was bad', E_USER_NOTICE);
        $output = ob_get_clean();
        restore_error_handler();
        $this->assertSame('', $output);
    }

    public function testRegisterIgnoredDeprecations(): void
    {
        $trap = new ErrorTrap([
            'errorRenderer' => TextErrorRenderer::class,
            'trace' => false,
        ]);
        $trap->register();

        ob_start();
        Configure::write('Error.ignoredDeprecationPaths', [
            'tests/TestCase/Error/ErrorTrap*',
        ]);
        trigger_error('Should be ignored', E_USER_DEPRECATED);

        Configure::write('Error.ignoredDeprecationPaths', []);
        trigger_error('Not ignored', E_USER_DEPRECATED);

        $output = ob_get_clean();
        restore_error_handler();

        $this->assertStringNotContainsString('Should be ignored', $output);
        $this->assertStringContainsString('Not ignored', $output);
    }

    public function testEventTriggered(): void
    {
        $trap = new ErrorTrap(['errorRenderer' => TextErrorRenderer::class]);
        $trap->register();
        $trap->getEventManager()->on('Error.beforeRender', function ($event, PhpError $error): void {
            $this->assertEquals(E_USER_NOTICE, $error->getCode());
            $this->assertStringContainsString('Oh no it was bad', $error->getMessage());
        });

        ob_start();
        trigger_error('Oh no it was bad', E_USER_NOTICE);
        $out = ob_get_clean();
        restore_error_handler();
        $this->assertNotEmpty($out);
    }

    public function testEventTriggeredAbortRender(): void
    {
        $trap = new ErrorTrap(['errorRenderer' => TextErrorRenderer::class]);
        $trap->register();
        $trap->getEventManager()->on('Error.beforeRender', function ($event, PhpError $error): void {
            $this->assertEquals(E_USER_NOTICE, $error->getCode());
            $this->assertStringContainsString('Oh no it was bad', $error->getMessage());
            $event->stopPropagation();
        });

        ob_start();
        trigger_error('Oh no it was bad', E_USER_NOTICE);
        $out = ob_get_clean();
        restore_error_handler();
        $this->assertSame('', $out);
    }

    public function testEventReturnResponse(): void
    {
        $trap = new ErrorTrap(['errorRenderer' => TextErrorRenderer::class]);
        $trap->register();
        $trap->getEventManager()->on('Error.beforeRender', function ($event, PhpError $error) {
            return "This ain't so bad";
        });

        ob_start();
        trigger_error('Oh no it was bad', E_USER_NOTICE);
        $out = ob_get_clean();
        restore_error_handler();
        $this->assertSame("This ain't so bad", $out);
    }
}
