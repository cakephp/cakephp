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

use Cake\Error\ErrorLogger;
use Cake\Error\ExceptionRenderer;
use Cake\Error\ExceptionTrap;
use Cake\Error\Renderer\TextExceptionRenderer;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use stdClass;
use Throwable;

class ExceptionTrapTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Log::drop('test_error');
    }

    public function testConfigRendererInvalid()
    {
        $trap = new ExceptionTrap(['exceptionRenderer' => stdClass::class]);
        $this->expectException(InvalidArgumentException::class);
        $error = new InvalidArgumentException('nope');
        $trap->renderer($error);
    }

    public function testConfigExceptionRendererFallback()
    {
        $this->markTestIncomplete();
        $trap = new ExceptionTrap(['exceptionRenderer' => null]);
        $error = new InvalidArgumentException('nope');
        $this->assertInstanceOf(ConsoleRenderer::class, $trap->renderer($error));
    }

    public function testConfigExceptionRenderer()
    {
        $trap = new ExceptionTrap(['exceptionRenderer' => ExceptionRenderer::class]);
        $error = new InvalidArgumentException('nope');
        $this->assertInstanceOf(ExceptionRenderer::class, $trap->renderer($error));
    }

    public function testConfigExceptionRendererFactory()
    {
        $trap = new ExceptionTrap(['exceptionRenderer' => function ($err, $req) {
            return new ExceptionRenderer($err, $req);
        }]);
        $error = new InvalidArgumentException('nope');
        $this->assertInstanceOf(ExceptionRenderer::class, $trap->renderer($error));
    }

    public function testConfigRendererHandleUnsafeOverwrite()
    {
        $this->markTestIncomplete();
        $trap = new ExceptionTrap();
        $trap->setConfig('exceptionRenderer', null);
        $error = new InvalidArgumentException('nope');
        $this->assertInstanceOf(ConsoleRenderer::class, $trap->renderer($error));
    }

    public function testLoggerConfigInvalid()
    {
        $trap = new ExceptionTrap(['logger' => stdClass::class]);
        $this->expectException(InvalidArgumentException::class);
        $trap->logger();
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
            'exceptionRenderer' => ExceptionRenderer::class,
        ]);
        $error = new InvalidArgumentException('nope');

        ob_start();
        $trap->handleException($error);
        $out = ob_get_clean();

        $this->assertStringContainsString('<!DOCTYPE', $out);
        $this->assertStringContainsString('<html', $out);
        $this->assertStringContainsString('nope', $out);
        $this->assertStringContainsString('ExceptionTrapTest', $out);
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

    public function testAddCallback()
    {
        $trap = new ExceptionTrap(['exceptionRenderer' => TextExceptionRenderer::class]);
        $trap->addCallback(function (Throwable $error) {
            $this->assertEquals(100, $error->getCode());
            $this->assertStringContainsString('nope', $error->getMessage());
        });
        $error = new InvalidArgumentException('nope', 100);

        ob_start();
        $trap->handleException($error);
        $out = ob_get_clean();

        $this->assertNotEmpty($out);
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
            'exceptionRenderer' => ExceptionRenderer::class,
        ]);

        ob_start();
        $trap->handleFatalError(E_USER_ERROR, 'Something bad', __FILE__, __LINE__);
        $out = ob_get_clean();

        $this->assertStringContainsString('<!DOCTYPE', $out);
        $this->assertStringContainsString('<html', $out);
        $this->assertStringContainsString('Something bad', $out);
        $this->assertStringContainsString(__FILE__, $out);
    }
}
