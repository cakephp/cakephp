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

use Cake\Core\Configure;
use Cake\Error\ErrorLogger;
use Cake\Error\ErrorTrap;
use Cake\Error\PhpError;
use Cake\Error\Renderer\ConsoleErrorRenderer;
use Cake\Error\Renderer\HtmlErrorRenderer;
use Cake\Error\Renderer\TextErrorRenderer;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use stdClass;

class ErrorTrapTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Log::drop('test_error');
    }

    public function testConfigRendererInvalid()
    {
        $trap = new ErrorTrap(['errorRenderer' => stdClass::class]);
        $this->expectException(InvalidArgumentException::class);
        $trap->renderer();
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

    public function testLoggerConfigInvalid()
    {
        $trap = new ErrorTrap(['logger' => stdClass::class]);
        $this->expectException(InvalidArgumentException::class);
        $trap->logger();
    }

    public function testLoggerConfig()
    {
        $trap = new ErrorTrap(['logger' => ErrorLogger::class]);
        $this->assertInstanceOf(ErrorLogger::class, $trap->logger());
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

    public function testRegisterAndLogging()
    {
        Log::setConfig('test_error', [
            'className' => 'Array',
        ]);
        $trap = new ErrorTrap([
            'errorRenderer' => TextErrorRenderer::class,
        ]);
        $trap->register();

        ob_start();
        trigger_error('Oh no it was bad', E_USER_NOTICE);
        ob_get_clean();
        restore_error_handler();

        $logs = Log::engine('test_error')->read();
        $this->assertStringContainsString('Oh no it was bad', $logs[0]);
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

    public function testEventTriggered()
    {
        $trap = new ErrorTrap(['errorRenderer' => TextErrorRenderer::class]);
        $trap->register();
        $trap->getEventManager()->on('Error.handled', function ($event, PhpError $error) {
            $this->assertEquals(E_USER_NOTICE, $error->getCode());
            $this->assertStringContainsString('Oh no it was bad', $error->getMessage());
        });

        ob_start();
        trigger_error('Oh no it was bad', E_USER_NOTICE);
        $out = ob_get_clean();
        restore_error_handler();
        $this->assertNotEmpty($out);
    }
}
