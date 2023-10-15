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
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use TestApp\Error\LegacyErrorLogger;

class ErrorTrapTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Log::drop('test_error');
        Router::reload();
    }

    public function testConfigErrorRendererFallback()
    {
        $trap = new ErrorTrap(['errorRenderer' => null]);
        $this->assertInstanceOf(ConsoleErrorRenderer::class, $trap->renderer());
    }

    public function testConfigErrorRenderer()
    {
        $trap = new ErrorTrap(['errorRenderer' => HtmlErrorRenderer::class]);
        $this->assertInstanceOf(HtmlErrorRenderer::class, $trap->renderer());
    }

    public function testConfigRendererHandleUnsafeOverwrite()
    {
        $trap = new ErrorTrap();
        $trap->setConfig('errorRenderer', null);
        $this->assertInstanceOf(ConsoleErrorRenderer::class, $trap->renderer());
    }

    public function testLoggerConfig()
    {
        $trap = new ErrorTrap(['logger' => ErrorLogger::class]);
        $this->assertInstanceOf(ErrorLogger::class, $trap->logger());
    }

    public function testLoggerConfigCompatibility()
    {
        $this->deprecated(function () {
            $trap = new ErrorTrap(['errorLogger' => ErrorLogger::class]);
            $this->assertInstanceOf(ErrorLogger::class, $trap->logger());
        });
    }

    public function testLoggerHandleUnsafeOverwrite()
    {
        $trap = new ErrorTrap();
        $trap->setConfig('logger', null);
        $this->assertInstanceOf(ErrorLogger::class, $trap->logger());
    }

    public function testRegisterAndRendering()
    {
        $trap = new ErrorTrap(['errorRenderer' => TextErrorRenderer::class]);
        $trap->register();
        ob_start();
        trigger_error('Oh no it was bad', E_USER_NOTICE);
        $output = ob_get_clean();
        restore_error_handler();

        $this->assertStringContainsString('Oh no it was bad', $output);
    }

    public function testRegisterAndHandleFatalUserError()
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

    public function logLevelProvider(): array
    {
        return [
            // PHP error level, expected log level
            [E_USER_WARNING, 'warning'],
            [E_USER_NOTICE, 'notice'],
            [E_USER_DEPRECATED, 'notice'],
        ];
    }

    /**
     * @dataProvider logLevelProvider
     */
    public function testHandleErrorLoggingLevel($level, $logLevel)
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

    public function testHandleErrorLogTrace()
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

    public function testHandleErrorNoLog()
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

    public function testHandleErrorLogDeprecatedLoggerMethods()
    {
        $request = new ServerRequest([
            'url' => '/articles/view/1',
        ]);
        Router::setRequest($request);

        Log::setConfig('test_error', [
            'className' => 'Array',
        ]);
        $trap = new ErrorTrap([
            'errorRenderer' => TextErrorRenderer::class,
            'logger' => LegacyErrorLogger::class,
            'log' => true,
            'trace' => true,
        ]);

        $this->deprecated(function () use ($trap) {
            // Calling this method directly as deprecated() interferes with registering
            // an error handler.
            ob_start();
            $trap->handleError(E_USER_WARNING, 'Oh no it was bad', __FILE__, __LINE__);
            ob_get_clean();
        });

        $logs = Log::engine('test_error')->read();
        $this->assertStringContainsString('Oh no it was bad', $logs[0]);
        $this->assertStringContainsString('IncludeTrace', $logs[0]);
        $this->assertStringContainsString('URL=http://localhost/articles/view/1', $logs[0]);
    }

    public function testConsoleRenderingNoTrace()
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

    public function testConsoleRenderingWithTrace()
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

    public function testRegisterNoOutputDebug()
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

    public function testRegisterIgnoredDeprecations()
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

    public function testEventTriggered()
    {
        $trap = new ErrorTrap(['errorRenderer' => TextErrorRenderer::class]);
        $trap->register();
        $trap->getEventManager()->on('Error.beforeRender', function ($event, PhpError $error) {
            $this->assertEquals(E_USER_NOTICE, $error->getCode());
            $this->assertStringContainsString('Oh no it was bad', $error->getMessage());
        });

        ob_start();
        trigger_error('Oh no it was bad', E_USER_NOTICE);
        $out = ob_get_clean();
        restore_error_handler();
        $this->assertNotEmpty($out);
    }

    public function testEventTriggeredAbortRender()
    {
        $trap = new ErrorTrap(['errorRenderer' => TextErrorRenderer::class]);
        $trap->register();
        $trap->getEventManager()->on('Error.beforeRender', function ($event, PhpError $error) {
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
