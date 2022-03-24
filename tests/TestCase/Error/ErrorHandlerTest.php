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
use Cake\Error\ErrorLoggerInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\MissingControllerException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Exception;
use RuntimeException;
use stdClass;
use TestApp\Error\TestErrorHandler;

/**
 * ErrorHandlerTest class
 */
class ErrorHandlerTest extends TestCase
{
    /**
     * @var \Cake\Log\Engine\ArrayLog
     */
    protected $logger;

    /**
     * error level property
     */
    private static $errorLevel;

    /**
     * setup create a request object to get out of router later.
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

        Router::setRequest($request);
        Configure::write('debug', true);

        Log::reset();
        Log::setConfig('error_test', ['className' => 'Array']);
        $this->logger = Log::engine('error_test');
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Log::reset();
        $this->clearPlugins();
        error_reporting(self::$errorLevel);
    }

    /**
     * setUpBeforeClass
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$errorLevel = error_reporting();
    }

    /**
     * Test an invalid rendering class.
     */
    public function testInvalidRenderer(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The \'TotallyInvalid\' renderer class could not be found');

        $errorHandler = new ErrorHandler(['exceptionRenderer' => 'TotallyInvalid']);
        $errorHandler->getRenderer(new Exception('Something bad'));
    }

    /**
     * test error handling when debug is on, an error should be printed from Debugger.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHandleErrorDebugOn(): void
    {
        Configure::write('debug', true);
        $this->deprecated(function () {
            $errorHandler = new ErrorHandler();
            $errorHandler->register();

            ob_start();
            $wrong = $wrong + 1;
        });
        $result = ob_get_clean();

        $this->assertMatchesRegularExpression('/<div class="cake-error">/', $result);
        if (version_compare(PHP_VERSION, '8.0.0-dev', '<')) {
            $this->assertMatchesRegularExpression('/<b>Notice<\/b>/', $result);
            $this->assertMatchesRegularExpression('/variable:\s+wrong/', $result);
        } else {
            $this->assertMatchesRegularExpression('/<b>Warning<\/b>/', $result);
            $this->assertMatchesRegularExpression('/variable \$wrong/', $result);
        }
        $this->assertStringContainsString(
            'ErrorHandlerTest.php, line ' . (__LINE__ - 13),
            $result,
            'Should contain file and line reference'
        );
    }

    /**
     * test error handling with the _trace_offset context variable
     */
    public function testHandleErrorTraceOffset(): void
    {
        set_error_handler(function ($code, $message, $file, $line, $context = null): void {
            $errorHandler = new ErrorHandler();
            $context['_trace_frame_offset'] = 3;
            $errorHandler->handleError($code, $message, $file, $line, $context);
        });

        ob_start();
        $wrong = $wrong + 1;
        $result = ob_get_clean();

        restore_error_handler();

        $this->assertStringNotContainsString(
            'ErrorHandlerTest.php, line ' . (__LINE__ - 4),
            $result,
            'Should not contain file and line reference'
        );
        $this->assertStringNotContainsString('_trace_frame_offset', $result);
    }

    /**
     * provides errors for mapping tests.
     *
     * @return array
     */
    public static function errorProvider(): array
    {
        return [
            [E_USER_NOTICE, 'Notice'],
            [E_USER_WARNING, 'Warning'],
        ];
    }

    /**
     * test error mappings
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @dataProvider errorProvider
     */
    public function testErrorMapping(int $error, string $expected): void
    {
        $this->deprecated(function () use ($error, $expected) {
            $errorHandler = new ErrorHandler();
            $errorHandler->register();

            ob_start();
            trigger_error('Test error', $error);

            $this->assertStringContainsString('<b>' . $expected . '</b>', ob_get_clean());
        });
    }

    /**
     * test error prepended by @
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorSuppressed(): void
    {
        $errorHandler = new ErrorHandler();
        $this->deprecated(function () use ($errorHandler) {
            $errorHandler->register();

            ob_start();
            // phpcs:disable
            @include 'invalid.file';
            // phpcs:enable
            $this->assertEmpty(ob_get_clean());
        });
    }

    /**
     * Test that errors go into Cake Log when debug = 0.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHandleErrorDebugOff(): void
    {
        Configure::write('debug', false);
        $errorHandler = new ErrorHandler();
        $this->deprecated(function () use ($errorHandler) {
            $errorHandler->register();
            $out = $out + 1;
        });

        $messages = $this->logger->read();
        $this->assertMatchesRegularExpression('/^(notice|debug|warning)/', $messages[0]);

        if (version_compare(PHP_VERSION, '8.0.0-dev', '<')) {
            $this->assertStringContainsString(
                'Notice (8): Undefined variable: out in [' . __FILE__ . ', line ' . (__LINE__ - 8) . ']',
                $messages[0]
            );
        } else {
            $this->assertStringContainsString(
                'Warning (2): Undefined variable $out in [' . __FILE__ . ', line ' . (__LINE__ - 13) . ']',
                $messages[0]
            );
        }
    }

    /**
     * Test that errors going into Cake Log include traces.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHandleErrorLoggingTrace(): void
    {
        Configure::write('debug', false);
        $errorHandler = new ErrorHandler(['trace' => true]);
        $this->deprecated(function () use ($errorHandler) {
            $errorHandler->register();
            $out = $out + 1;
        });

        $messages = $this->logger->read();
        $this->assertMatchesRegularExpression('/^(notice|debug|warning)/', $messages[0]);
        $this->assertMatchesRegularExpression('/Undefined variable\:? \$?out in/', $messages[0]);
        $this->assertStringContainsString('[' . __FILE__ . ', line ' . (__LINE__ - 6) . ']', $messages[0]);
        $this->assertStringContainsString('Trace:', $messages[0]);
        $this->assertStringContainsString(__NAMESPACE__ . '\ErrorHandlerTest::testHandleErrorLoggingTrace()', $messages[0]);
        $this->assertStringContainsString('Request URL:', $messages[0]);
        $this->assertStringContainsString('Referer URL:', $messages[0]);
    }

    /**
     * test handleException generating a page.
     */
    public function testHandleException(): void
    {
        $error = new NotFoundException('Kaboom!');
        $errorHandler = new TestErrorHandler();

        $errorHandler->handleException($error);
        $this->assertStringContainsString('Kaboom!', (string)$errorHandler->response->getBody(), 'message missing.');
    }

    /**
     * test handleException generating log.
     */
    public function testHandleExceptionLog(): void
    {
        $errorHandler = new TestErrorHandler([
            'log' => true,
            'trace' => true,
        ]);

        $error = new NotFoundException('Kaboom!');
        $errorHandler->handleException($error);
        $this->assertStringContainsString('Kaboom!', (string)$errorHandler->response->getBody(), 'message missing.');

        $messages = $this->logger->read();
        $this->assertMatchesRegularExpression('/^error/', $messages[0]);
        $this->assertStringContainsString('[Cake\Http\Exception\NotFoundException] Kaboom!', $messages[0]);
        $this->assertStringContainsString(
            str_replace('/', DS, 'vendor/phpunit/phpunit/src/Framework/TestCase.php'),
            $messages[0]
        );

        $errorHandler = new TestErrorHandler([
            'log' => true,
            'trace' => false,
        ]);
        $errorHandler->handleException($error);

        $messages = $this->logger->read();
        $this->assertMatchesRegularExpression('/^error/', $messages[1]);
        $this->assertStringContainsString('[Cake\Http\Exception\NotFoundException] Kaboom!', $messages[1]);
        $this->assertStringNotContainsString(
            str_replace('/', DS, 'vendor/phpunit/phpunit/src/Framework/TestCase.php'),
            $messages[1]
        );
    }

    /**
     * test logging attributes with/without debug
     */
    public function testHandleExceptionLogAttributes(): void
    {
        $errorHandler = new TestErrorHandler([
            'log' => true,
            'trace' => true,
        ]);

        $error = new MissingControllerException(['class' => 'Derp']);
        $errorHandler->handleException($error);

        Configure::write('debug', false);
        $errorHandler->handleException($error);

        $messages = $this->logger->read();
        $this->assertMatchesRegularExpression('/^error/', $messages[0]);
        $this->assertStringContainsString(
            '[Cake\Http\Exception\MissingControllerException] Controller class Derp could not be found.',
            $messages[0]
        );
        $this->assertStringContainsString('Exception Attributes:', $messages[0]);
        $this->assertStringContainsString('Request URL:', $messages[0]);
        $this->assertStringContainsString('Referer URL:', $messages[0]);

        $this->assertStringContainsString(
            '[Cake\Http\Exception\MissingControllerException] Controller class Derp could not be found.',
            $messages[1]
        );
        $this->assertStringNotContainsString('Exception Attributes:', $messages[1]);
    }

    /**
     * test logging attributes with previous exception
     */
    public function testHandleExceptionLogPrevious(): void
    {
        $errorHandler = new TestErrorHandler([
            'log' => true,
            'trace' => true,
        ]);

        $previous = new RecordNotFoundException('Previous logged');
        $error = new NotFoundException('Kaboom!', null, $previous);
        $errorHandler->handleException($error);

        $messages = $this->logger->read();
        $this->assertStringContainsString('[Cake\Http\Exception\NotFoundException] Kaboom!', $messages[0]);
        $this->assertStringContainsString(
            'Caused by: [Cake\Datasource\Exception\RecordNotFoundException] Previous logged',
            $messages[0]
        );
        $this->assertStringContainsString(
            str_replace('/', DS, 'vendor/phpunit/phpunit/src/Framework/TestCase.php'),
            $messages[0]
        );
    }

    /**
     * test handleException generating log.
     */
    public function testHandleExceptionLogSkipping(): void
    {
        $notFound = new NotFoundException('Kaboom!');
        $forbidden = new ForbiddenException('Fooled you!');
        $errorHandler = new TestErrorHandler([
            'log' => true,
            'skipLog' => ['Cake\Http\Exception\NotFoundException'],
        ]);

        $errorHandler->handleException($notFound);
        $this->assertStringContainsString('Kaboom!', (string)$errorHandler->response->getBody(), 'message missing.');

        $errorHandler->handleException($forbidden);
        $this->assertStringContainsString('Fooled you!', (string)$errorHandler->response->getBody(), 'message missing.');

        $messages = $this->logger->read();
        $this->assertCount(1, $messages);
        $this->assertMatchesRegularExpression('/^error/', $messages[0]);
        $this->assertStringContainsString(
            '[Cake\Http\Exception\ForbiddenException] Fooled you!',
            $messages[0]
        );
    }

    /**
     * tests it is possible to load a plugin exception renderer
     */
    public function testLoadPluginHandler(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $errorHandler = new TestErrorHandler([
            'exceptionRenderer' => 'TestPlugin.TestPluginExceptionRenderer',
        ]);

        $error = new NotFoundException('Kaboom!');
        $errorHandler->handleException($error);

        $result = $errorHandler->response;
        $this->assertSame('Rendered by test plugin', (string)$result);
    }

    /**
     * test handleFatalError generating a page.
     *
     * These tests start two buffers as handleFatalError blows the outer one up.
     */
    public function testHandleFatalErrorPage(): void
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
     */
    public function testHandleFatalErrorLog(): void
    {
        $errorHandler = new TestErrorHandler(['log' => true]);
        $errorHandler->handleFatalError(E_ERROR, 'Something wrong', __FILE__, __LINE__);

        $messages = $this->logger->read();
        $this->assertCount(2, $messages);
        $this->assertStringContainsString(__FILE__ . ', line ' . (__LINE__ - 4), $messages[0]);
        $this->assertStringContainsString('Fatal Error (1)', $messages[0]);
        $this->assertStringContainsString('Something wrong', $messages[0]);
        $this->assertStringContainsString('[Cake\Error\FatalErrorException] Something wrong', $messages[1]);
    }

    /**
     * Data provider for memory limit changing.
     *
     * @return array
     */
    public function memoryLimitProvider(): array
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
     */
    public function testIncreaseMemoryLimit(string $start, int $adjust, string $expected): void
    {
        $initial = ini_get('memory_limit');
        $this->skipIf(strlen($initial) === 0, 'Cannot read memory limit, and cannot test increasing it.');

        // phpunit.xml often has -1 as memory limit
        ini_set('memory_limit', $start);

        $errorHandler = new TestErrorHandler();
        $errorHandler->increaseMemoryLimit($adjust);
        $new = ini_get('memory_limit');
        $this->assertEquals($expected, $new, 'memory limit did not get increased.');

        ini_set('memory_limit', $initial);
    }

    /**
     * Test getting a logger
     */
    public function testGetLogger(): void
    {
        $errorHandler = new TestErrorHandler(['key' => 'value', 'log' => true]);
        $logger = $errorHandler->getLogger();

        $this->assertInstanceOf(ErrorLoggerInterface::class, $logger);
        $this->assertSame('value', $logger->getConfig('key'), 'config should be forwarded.');
        $this->assertSame($logger, $errorHandler->getLogger());
    }

    /**
     * Test getting a logger
     */
    public function testGetLoggerInvalid(): void
    {
        $errorHandler = new TestErrorHandler(['errorLogger' => stdClass::class]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot create logger');
        $errorHandler->getLogger();
    }
}
