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
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Error;

use Cake\Controller\Exception\MissingActionException;
use Cake\Core\Exception\Exception;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\NotFoundException;
use Cake\Log\Log;
use Cake\TestSuite\Stub\ConsoleOutput;
use Cake\TestSuite\TestCase;

/**
 * ConsoleErrorHandler Test case.
 */
class ConsoleErrorHandlerTest extends TestCase
{
    /**
     * setup, create mocks
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->stderr = new ConsoleOutput();
        $this->Error = $this->getMockBuilder('Cake\Error\ConsoleErrorHandler')
            ->setMethods(['_stop'])
            ->setConstructorArgs([['stderr' => $this->stderr]])
            ->getMock();
        Log::drop('stderr');
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Error);
        parent::tearDown();
    }

    /**
     * test that the console error handler can deal with Exceptions.
     *
     * @return void
     */
    public function testHandleError()
    {
        $content = "<error>Notice Error:</error> This is a notice error\nIn [/some/file, line 275]\n";
        $this->Error->expects($this->never())
            ->method('_stop');

        $this->Error->handleError(E_NOTICE, 'This is a notice error', '/some/file', 275);
        $this->assertEquals($content, $this->stderr->messages()[0]);
    }

    /**
     * test that the console error handler can deal with fatal errors.
     *
     * @return void
     */
    public function testHandleFatalError()
    {
        ob_start();
        $content = "<error>Fatal Error:</error> This is a fatal error\nIn [/some/file, line 275]\n";

        $this->Error->handleError(E_USER_ERROR, 'This is a fatal error', '/some/file', 275);
        $this->assertCount(1, $this->stderr->messages());
        $this->assertEquals($content, $this->stderr->messages()[0]);
        ob_end_clean();
    }

    /**
     * test that the console error handler can deal with CakeExceptions.
     *
     * @return void
     */
    public function testCakeErrors()
    {
        $exception = new MissingActionException('Missing action');
        $message = sprintf("<error>Exception:</error> Missing action\nIn [%s, line %s]\n", $exception->getFile(), $exception->getLine());

        $this->Error->expects($this->once())
            ->method('_stop');

        $this->Error->handleException($exception);

        $this->assertCount(1, $this->stderr->messages());
        $this->assertEquals($message, $this->stderr->messages()[0]);
    }

    /**
     * test a non Cake Exception exception.
     *
     * @return void
     */
    public function testNonCakeExceptions()
    {
        $exception = new \InvalidArgumentException('Too many parameters.');

        $this->Error->handleException($exception);
        $this->assertStringContainsString('Too many parameters', $this->stderr->messages()[0]);
    }

    /**
     * test a Error404 exception.
     *
     * @return void
     */
    public function testError404Exception()
    {
        $exception = new NotFoundException("don't use me in cli.");

        $this->Error->handleException($exception);
        $this->assertStringContainsString("don't use me in cli", $this->stderr->messages()[0]);
    }

    /**
     * test a Error500 exception.
     *
     * @return void
     */
    public function testError500Exception()
    {
        $exception = new InternalErrorException("don't use me in cli.");

        $this->Error->handleException($exception);
        $this->assertStringContainsString("don't use me in cli", $this->stderr->messages()[0]);
    }

    /**
     * test a exception with non-integer code
     *
     * @return void
     */
    public function testNonIntegerExceptionCode()
    {
        $exception = new Exception('Non-integer exception code');

        $class = new \ReflectionClass('Exception');
        $property = $class->getProperty('code');
        $property->setAccessible(true);
        $property->setValue($exception, '42S22');

        $this->Error->expects($this->once())
            ->method('_stop')
            ->with(1);

        $this->Error->handleException($exception);
        $this->assertStringContainsString('Non-integer exception code', $this->stderr->messages()[0]);
    }
}
