<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Error;

use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Error\ErrorHandler;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\Routing\Exception\MissingControllerException;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Psr\Log\LoggerInterface;
use TestApp\Error\TestErrorHandler;

/**
 * ErrorHandlerTest class
 */
class ErrorHandlerTest extends TestCase
{
    protected $_restoreError = false;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $_logger;

    /**
     * error level property
     *
     */
    private static $errorLevel;

    /**
     * setup create a request object to get out of router later.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Router::reload();

        $request = new ServerRequest([
            'base' => '',
            'environment' => [
                'HTTP_REFERER' => '/referer',
            ],
        ]);

        Router::setRequestInfo($request);
        Configure::write('debug', true);

        $this->_logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        Log::reset();
        Log::setConfig('error_test', [
            'engine' => $this->_logger,
        ]);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Log::reset();
        $this->clearPlugins();
        if ($this->_restoreError) {
            restore_error_handler();
            restore_exception_handler();
        }
        error_reporting(self::$errorLevel);
    }

    /**
     * setUpBeforeClass
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$errorLevel = error_reporting();
    }

    /**
     * test error handling when debug is on, an error should be printed from Debugger.
     *
     * @return void
     */
    public function testHandleErrorDebugOn()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register();
        $this->_restoreError = true;

        ob_start();
        $wrong = $wrong + 1;
        $result = ob_get_clean();

        $this->assertRegExp('/<pre class="cake-error">/', $result);
        $this->assertRegExp('/<b>Notice<\/b>/', $result);
        $this->assertRegExp('/variable:\s+wrong/', $result);
    }

    /**
     * provides errors for mapping tests.
     *
     * @return array
     */
    public static function errorProvider()
    {
        return [
            [E_USER_NOTICE, 'Notice'],
            [E_USER_WARNING, 'Warning'],
        ];
    }

    /**
     * test error mappings
     *
     * @dataProvider errorProvider
     * @return void
     */
    public function testErrorMapping($error, $expected)
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register();
        $this->_restoreError = true;

        ob_start();
        trigger_error('Test error', $error);
        $result = ob_get_clean();

        $this->assertStringContainsString('<b>' . $expected . '</b>', $result);
    }

    /**
     * test error prepended by @
     *
     * @return void
     */
    public function testErrorSuppressed()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register();
        $this->_restoreError = true;

        ob_start();
        // phpcs:disable
        @include 'invalid.file';
        // phpcs:enable
        $result = ob_get_clean();
        $this->assertEmpty($result);
    }

    /**
     * Test that errors go into Cake Log when debug = 0.
     *
     * @return void
     */
    public function testHandleErrorDebugOff()
    {
        Configure::write('debug', false);
        $errorHandler = new ErrorHandler();
        $errorHandler->register();
        $this->_restoreError = true;

        $this->_logger->expects($this->once())
            ->method('log')
            ->with(
                $this->matchesRegularExpression('(notice|debug)'),
                'Notice (8): Undefined variable: out in [' . __FILE__ . ', line ' . (__LINE__ + 3) . ']' . "\n\n"
            );

        $out = $out + 1;
    }

    /**
     * Test that errors going into Cake Log include traces.
     *
     * @return void
     */
    public function testHandleErrorLoggingTrace()
    {
        Configure::write('debug', false);
        $errorHandler = new ErrorHandler(['trace' => true]);
        $errorHandler->register();
        $this->_restoreError = true;

        $this->_logger->expects($this->once())
            ->method('log')
            ->with(
                $this->matchesRegularExpression('(notice|debug)'),
                $this->logicalAnd(
                    $this->stringContains('Notice (8): Undefined variable: out in '),
                    $this->stringContains('Trace:'),
                    $this->stringContains(__NAMESPACE__ . '\ErrorHandlerTest::testHandleErrorLoggingTrace()'),
                    $this->stringContains('Request URL:'),
                    $this->stringContains('Referer URL:')
                )
            );

        $out = $out + 1;
    }

    /**
     * test handleException generating a page.
     *
     * @return void
     */
    public function testHandleException()
    {
        $error = new NotFoundException('Kaboom!');
        $errorHandler = new TestErrorHandler();

        $errorHandler->handleException($error);
        $this->assertStringContainsString('Kaboom!', (string)$errorHandler->response->getBody(), 'message missing.');
    }

    /**
     * test handleException generating log.
     *
     * @return void
     */
    public function testHandleExceptionLog()
    {
        $errorHandler = new TestErrorHandler([
            'log' => true,
            'trace' => true,
        ]);

        $error = new NotFoundException('Kaboom!');

        $this->_logger->expects($this->at(0))
            ->method('log')
            ->with('error', $this->logicalAnd(
                $this->stringContains('[Cake\Http\Exception\NotFoundException] Kaboom!'),
                $this->stringContains('ErrorHandlerTest->testHandleExceptionLog')
            ));

        $this->_logger->expects($this->at(1))
            ->method('log')
            ->with('error', $this->logicalAnd(
                $this->stringContains('[Cake\Http\Exception\NotFoundException] Kaboom!'),
                $this->logicalNot($this->stringContains('ErrorHandlerTest->testHandleExceptionLog'))
            ));

        $errorHandler->handleException($error);
        $this->assertStringContainsString('Kaboom!', (string)$errorHandler->response->getBody(), 'message missing.');

        $errorHandler = new TestErrorHandler([
            'log' => true,
            'trace' => false,
        ]);
        $errorHandler->handleException($error);
    }

    /**
     * test logging attributes with/without debug
     *
     * @return void
     */
    public function testHandleExceptionLogAttributes()
    {
        $errorHandler = new TestErrorHandler([
            'log' => true,
            'trace' => true,
        ]);

        $error = new MissingControllerException(['class' => 'Derp']);

        $this->_logger->expects($this->at(0))
            ->method('log')
            ->with('error', $this->logicalAnd(
                $this->stringContains(
                    '[Cake\Routing\Exception\MissingControllerException] ' .
                    'Controller class Derp could not be found.'
                ),
                $this->stringContains('Exception Attributes:'),
                $this->stringContains('Request URL:'),
                $this->stringContains('Referer URL:')
            ));

        $this->_logger->expects($this->at(1))
            ->method('log')
            ->with('error', $this->logicalAnd(
                $this->stringContains(
                    '[Cake\Routing\Exception\MissingControllerException] ' .
                    'Controller class Derp could not be found.'
                ),
                $this->logicalNot($this->stringContains('Exception Attributes:'))
            ));
        $errorHandler->handleException($error);

        Configure::write('debug', false);
        $errorHandler->handleException($error);
    }

    /**
     * test logging attributes with previous exception
     *
     * @return void
     */
    public function testHandleExceptionLogPrevious()
    {
        $errorHandler = new TestErrorHandler([
            'log' => true,
            'trace' => true,
        ]);

        $previous = new RecordNotFoundException('Previous logged');
        $error = new NotFoundException('Kaboom!', null, $previous);

        $this->_logger->expects($this->at(0))
            ->method('log')
            ->with('error', $this->logicalAnd(
                $this->stringContains('[Cake\Http\Exception\NotFoundException] Kaboom!'),
                $this->stringContains('Caused by: [Cake\Datasource\Exception\RecordNotFoundException] Previous logged'),
                $this->stringContains('ErrorHandlerTest->testHandleExceptionLogPrevious')
            ));

        $errorHandler->handleException($error);
    }

    /**
     * test handleException generating log.
     *
     * @return void
     */
    public function testHandleExceptionLogSkipping()
    {
        $notFound = new NotFoundException('Kaboom!');
        $forbidden = new ForbiddenException('Fooled you!');

        $this->_logger->expects($this->once())
            ->method('log')
            ->with(
                'error',
                $this->stringContains('[Cake\Http\Exception\ForbiddenException] Fooled you!')
            );

        $errorHandler = new TestErrorHandler([
            'log' => true,
            'skipLog' => ['Cake\Http\Exception\NotFoundException'],
        ]);

        $errorHandler->handleException($notFound);
        $this->assertStringContainsString('Kaboom!', (string)$errorHandler->response->getBody(), 'message missing.');

        $errorHandler->handleException($forbidden);
        $this->assertStringContainsString('Fooled you!', (string)$errorHandler->response->getBody(), 'message missing.');
    }

    /**
     * tests it is possible to load a plugin exception renderer
     *
     * @return void
     */
    public function testLoadPluginHandler()
    {
        $this->loadPlugins(['TestPlugin']);
        $errorHandler = new TestErrorHandler([
            'exceptionRenderer' => 'TestPlugin.TestPluginExceptionRenderer',
        ]);

        $error = new NotFoundException('Kaboom!');
        $errorHandler->handleException($error);

        $result = $errorHandler->response;
        $this->assertSame('Rendered by test plugin', $result);
    }

    /**
     * test handleFatalError generating a page.
     *
     * These tests start two buffers as handleFatalError blows the outer one up.
     *
     * @return void
     */
    public function testHandleFatalErrorPage()
    {
        $line = __LINE__;
        $errorHandler = new TestErrorHandler();
        Configure::write('debug', true);

        $errorHandler->handleFatalError(E_ERROR, 'Something wrong', __FILE__, $line);
        $result = (string)$errorHandler->response->getBody();
        $this->assertStringContainsString('Something wrong', $result, 'message missing.');
        $this->assertStringContainsString(__FILE__, $result, 'filename missing.');
        $this->assertStringContainsString((string)$line, $result, 'line missing.');

        Configure::write('debug', false);
        $errorHandler->handleFatalError(E_ERROR, 'Something wrong', __FILE__, $line);
        $result = (string)$errorHandler->response->getBody();
        $this->assertStringNotContainsString('Something wrong', $result, 'message must not appear.');
        $this->assertStringNotContainsString(__FILE__, $result, 'filename must not appear.');
        $this->assertStringContainsString('An Internal Error Has Occurred.', $result);
    }

    /**
     * test handleFatalError generating log.
     *
     * @return void
     */
    public function testHandleFatalErrorLog()
    {
        $this->_logger->expects($this->at(0))
            ->method('log')
            ->with('error', $this->logicalAnd(
                $this->stringContains(__FILE__ . ', line ' . (__LINE__ + 9)),
                $this->stringContains('Fatal Error (1)'),
                $this->stringContains('Something wrong')
            ));
        $this->_logger->expects($this->at(1))
            ->method('log')
            ->with('error', $this->stringContains('[Cake\Error\FatalErrorException] Something wrong'));

        $errorHandler = new TestErrorHandler(['log' => true]);
        $errorHandler->handleFatalError(E_ERROR, 'Something wrong', __FILE__, __LINE__);
    }

    /**
     * Data provider for memory limit changing.
     *
     * @return array
     */
    public function memoryLimitProvider()
    {
        return [
            // start, adjust, expected
            ['256M', 4, '262148K'],
            ['262144K', 4, '262148K'],
            ['1G', 128, '1048704K'],
        ];
    }

    /**
     * Test increasing the memory limit.
     *
     * @dataProvider memoryLimitProvider
     * @return void
     */
    public function testIncreaseMemoryLimit($start, $adjust, $expected)
    {
        $initial = ini_get('memory_limit');
        $this->skipIf(strlen($initial) === 0, 'Cannot read memory limit, and cannot test increasing it.');

        // phpunit.xml often has -1 as memory limit
        ini_set('memory_limit', $start);

        $errorHandler = new TestErrorHandler();
        $this->assertNull($errorHandler->increaseMemoryLimit($adjust));
        $new = ini_get('memory_limit');
        $this->assertEquals($expected, $new, 'memory limit did not get increased.');

        ini_set('memory_limit', $initial);
    }
}
