<?php
declare(strict_types=1);

/**
 * CakePHP(tm) <https://book.cakephp.org/5/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Log;

use BadMethodCallException;
use Cake\Core\Exception\CakeException;
use Cake\Log\Engine\BaseLog;
use Cake\Log\Engine\FileLog;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Stringable;
use TestApp\Log\Engine\ReentrantLog;
use TestApp\Log\Engine\TestAppLog;
use TestPlugin\Log\Engine\TestPluginLog;

/**
 * LogTest class
 */
class LogTest extends TestCase
{
    /**
     * @var string|null
     */
    protected ?string $errorLogBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        Log::reset();
        $this->clearPlugins();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Log::reset();

        if ($this->errorLogBackup !== null) {
            ini_set('error_log', $this->errorLogBackup);
            $this->errorLogBackup = null;
        }
    }

    /**
     * Sends error_log() output to a file for the duration of the test, so a suppressed
     * reentrant message does not end up in the test runner's output.
     */
    protected function captureErrorLog(): string
    {
        $file = TMP . 'error_log_capture.log';
        if (file_exists($file)) {
            unlink($file);
        }

        $previous = ini_get('error_log');
        $this->errorLogBackup = $previous === false ? '' : $previous;
        ini_set('error_log', $file);

        return $file;
    }

    /**
     * test importing loggers from app/libs and plugins.
     */
    public function testImportingLoggers(): void
    {
        static::setAppNamespace();
        $this->loadPlugins(['TestPlugin']);

        Log::setConfig('libtest', [
            'engine' => 'TestApp',
        ]);
        Log::setConfig('plugintest', [
            'engine' => 'TestPlugin.TestPlugin',
        ]);

        $result = Log::engine('libtest');
        $this->assertInstanceOf(TestAppLog::class, $result);
        $this->assertContains('libtest', Log::configured());

        $result = Log::engine('plugintest');
        $this->assertInstanceOf(TestPluginLog::class, $result);
        $this->assertContains('libtest', Log::configured());
        $this->assertContains('plugintest', Log::configured());

        Log::write(LOG_INFO, 'TestPluginLog is not a BaseLog descendant');

        $this->clearPlugins();
    }

    /**
     * test all the errors from failed logger imports
     */
    public function testImportingLoggerFailure(): void
    {
        $this->expectException(CakeException::class);
        Log::setConfig('fail', []);
        Log::engine('fail');
    }

    /**
     * test config() with valid key name
     */
    public function testValidKeyName(): void
    {
        Log::setConfig('valid', ['engine' => 'File']);
        $stream = Log::engine('valid');
        $this->assertInstanceOf(FileLog::class, $stream);
    }

    /**
     * test config() with valid numeric key name
     */
    public function testValidKeyNameNumeric(): void
    {
        Log::setConfig('404', ['engine' => 'File']);
        $stream = Log::engine('404');
        $this->assertInstanceOf(FileLog::class, $stream);

        $configured = Log::configured();
        $this->assertSame(['404'], $configured);
    }

    /**
     * explicit tests for drop()
     */
    public function testDrop(): void
    {
        Log::setConfig('file', [
            'engine' => 'File',
            'path' => LOGS,
        ]);
        $result = Log::configured();
        $this->assertContains('file', $result);

        $this->assertTrue(Log::drop('file'), 'Should be dropped');
        $this->assertFalse(Log::drop('file'), 'Already gone');

        $result = Log::configured();
        $this->assertNotContains('file', $result);
    }

    /**
     * test invalid level
     */
    public function testInvalidLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Log::setConfig('myengine', ['engine' => 'File']);
        Log::write('invalid', 'This will not be logged');
    }

    /**
     * Provider for config() tests.
     *
     * @return array
     */
    public static function configProvider(): array
    {
        return [
            'Array of data using engine key.' => [[
                'engine' => 'File',
                'path' => TMP . 'tests',
            ]],
            'Array of data using classname key.' => [[
                'className' => 'File',
                'path' => TMP . 'tests',
            ]],
            'Direct instance' => [new FileLog(['path' => LOGS])],
        ];
    }

    /**
     * Test the various config call signatures.
     *
     * @param mixed $settings
     */
    #[DataProvider('configProvider')]
    public function testConfigVariants($settings): void
    {
        Log::setConfig('test', $settings);
        $this->assertContains('test', Log::configured());
        $this->assertInstanceOf(FileLog::class, Log::engine('test'));
        Log::drop('test');
    }

    /**
     * Test the various setConfig call signatures.
     *
     * @param mixed $settings
     */
    #[DataProvider('configProvider')]
    public function testSetConfigVariants($settings): void
    {
        Log::setConfig('test', $settings);
        $this->assertContains('test', Log::configured());
        $this->assertInstanceOf(FileLog::class, Log::engine('test'));
        Log::drop('test');
    }

    /**
     * Test that config() can read data back
     */
    public function testConfigRead(): void
    {
        $config = [
            'engine' => 'File',
            'path' => LOGS,
        ];
        Log::setConfig('tests', $config);

        $expected = $config;
        $expected['className'] = $config['engine'];
        unset($expected['engine']);
        $this->assertSame($expected, Log::getConfig('tests'));
    }

    /**
     * Ensure you cannot reconfigure a log adapter.
     */
    public function testConfigErrorOnReconfigure(): void
    {
        $this->expectException(BadMethodCallException::class);
        Log::setConfig('tests', ['engine' => 'File', 'path' => TMP]);
        Log::setConfig('tests', ['engine' => 'Apc']);
    }

    /**
     * testLogFileWriting method
     */
    public function testLogFileWriting(): void
    {
        $this->resetLogConfig();
        if (file_exists(LOGS . 'error.log')) {
            unlink(LOGS . 'error.log');
        }
        $result = Log::write(LOG_WARNING, 'Test warning');
        $this->assertTrue($result);
        $this->assertFileExists(LOGS . 'error.log');
        unlink(LOGS . 'error.log');

        Log::write(LOG_WARNING, 'Test warning 1');
        Log::write(LOG_WARNING, 'Test warning 2');
        $result = file_get_contents(LOGS . 'error.log');
        $this->assertMatchesRegularExpression('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ warning: Test warning 1/', $result);
        $this->assertMatchesRegularExpression('/2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ warning: Test warning 2$/', $result);
        unlink(LOGS . 'error.log');
    }

    /**
     * test selective logging by level/type
     */
    public function testSelectiveLoggingByLevel(): void
    {
        if (file_exists(LOGS . 'spam.log')) {
            unlink(LOGS . 'spam.log');
        }
        if (file_exists(LOGS . 'eggs.log')) {
            unlink(LOGS . 'eggs.log');
        }
        Log::setConfig('spam', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => 'debug',
            'file' => 'spam',
        ]);
        Log::setConfig('eggs', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['eggs', 'debug', 'error', 'warning'],
            'file' => 'eggs',
        ]);

        $testMessage = 'selective logging';
        Log::write('warning', $testMessage);

        $this->assertFileExists(LOGS . 'eggs.log');
        $this->assertFileDoesNotExist(LOGS . 'spam.log');

        Log::write('debug', $testMessage);
        $this->assertFileExists(LOGS . 'spam.log');

        $contents = file_get_contents(LOGS . 'spam.log');
        $this->assertStringContainsString('debug: ' . $testMessage, $contents);
        $contents = file_get_contents(LOGS . 'eggs.log');
        $this->assertStringContainsString('debug: ' . $testMessage, $contents);

        if (file_exists(LOGS . 'spam.log')) {
            unlink(LOGS . 'spam.log');
        }
        if (file_exists(LOGS . 'eggs.log')) {
            unlink(LOGS . 'eggs.log');
        }
    }

    /**
     * test selective logging by level using the `types` attribute
     */
    public function testSelectiveLoggingByLevelUsingTypes(): void
    {
        if (file_exists(LOGS . 'spam.log')) {
            unlink(LOGS . 'spam.log');
        }
        if (file_exists(LOGS . 'eggs.log')) {
            unlink(LOGS . 'eggs.log');
        }
        Log::setConfig('spam', [
            'engine' => 'File',
            'path' => LOGS,
            'types' => 'debug',
            'file' => 'spam',
        ]);
        Log::setConfig('eggs', [
            'engine' => 'File',
            'path' => LOGS,
            'types' => ['eggs', 'debug', 'error', 'warning'],
            'file' => 'eggs',
        ]);

        $testMessage = 'selective logging';
        Log::write('warning', $testMessage);

        $this->assertFileExists(LOGS . 'eggs.log');
        $this->assertFileDoesNotExist(LOGS . 'spam.log');

        Log::write('debug', $testMessage);
        $this->assertFileExists(LOGS . 'spam.log');

        $contents = file_get_contents(LOGS . 'spam.log');
        $this->assertStringContainsString('debug: ' . $testMessage, $contents);
        $contents = file_get_contents(LOGS . 'eggs.log');
        $this->assertStringContainsString('debug: ' . $testMessage, $contents);

        if (file_exists(LOGS . 'spam.log')) {
            unlink(LOGS . 'spam.log');
        }
        if (file_exists(LOGS . 'eggs.log')) {
            unlink(LOGS . 'eggs.log');
        }
    }

    protected function resetLogConfig(): void
    {
        Log::setConfig('debug', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['notice', 'info', 'debug'],
            'file' => 'debug',
        ]);
        Log::setConfig('error', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
            'file' => 'error',
        ]);
    }

    protected function deleteLogs(): void
    {
        if (file_exists(LOGS . 'shops.log')) {
            unlink(LOGS . 'shops.log');
        }
        if (file_exists(LOGS . 'error.log')) {
            unlink(LOGS . 'error.log');
        }
        if (file_exists(LOGS . 'debug.log')) {
            unlink(LOGS . 'debug.log');
        }
        if (file_exists(LOGS . 'bogus.log')) {
            unlink(LOGS . 'bogus.log');
        }
        if (file_exists(LOGS . 'spam.log')) {
            unlink(LOGS . 'spam.log');
        }
        if (file_exists(LOGS . 'eggs.log')) {
            unlink(LOGS . 'eggs.log');
        }
    }

    /**
     * test scoped logging
     */
    public function testScopedLogging(): void
    {
        $this->deleteLogs();
        $this->resetLogConfig();
        Log::setConfig('shops', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['info', 'debug', 'warning'],
            'scopes' => ['transactions', 'orders'],
            'file' => 'shops',
        ]);

        Log::write('debug', 'debug message', 'transactions');
        $this->assertFileDoesNotExist(LOGS . 'error.log');
        $this->assertFileExists(LOGS . 'shops.log');
        $this->assertFileExists(LOGS . 'debug.log');

        $this->deleteLogs();

        Log::write('warning', 'warning message', 'orders');
        $this->assertFileExists(LOGS . 'error.log');
        $this->assertFileExists(LOGS . 'shops.log');
        $this->assertFileDoesNotExist(LOGS . 'debug.log');

        $this->deleteLogs();

        Log::write('error', 'error message', ['scope' => 'orders']);
        $this->assertFileExists(LOGS . 'error.log');
        $this->assertFileDoesNotExist(LOGS . 'debug.log');
        $this->assertFileDoesNotExist(LOGS . 'shops.log');

        $this->deleteLogs();

        Log::drop('shops');
    }

    /**
     * Test scoped logging without the default loggers catching everything
     */
    public function testScopedLoggingStrict(): void
    {
        $this->deleteLogs();

        Log::setConfig('debug', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['notice', 'info', 'debug'],
            'file' => 'debug',
            'scopes' => null,
        ]);
        Log::setConfig('shops', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['info', 'debug', 'warning'],
            'file' => 'shops',
            'scopes' => ['transactions', 'orders'],
        ]);

        Log::write('debug', 'debug message');
        $this->assertFileDoesNotExist(LOGS . 'shops.log');
        $this->assertFileExists(LOGS . 'debug.log');

        $this->deleteLogs();

        Log::write('debug', 'debug message', 'orders');
        $this->assertFileExists(LOGS . 'shops.log');
        $this->assertFileDoesNotExist(LOGS . 'debug.log');

        $this->deleteLogs();

        Log::drop('shops');
    }

    /**
     * test scoped logging with convenience methods
     */
    public function testConvenienceScopedLogging(): void
    {
        if (file_exists(LOGS . 'shops.log')) {
            unlink(LOGS . 'shops.log');
        }
        if (file_exists(LOGS . 'error.log')) {
            unlink(LOGS . 'error.log');
        }
        if (file_exists(LOGS . 'debug.log')) {
            unlink(LOGS . 'debug.log');
        }

        $this->resetLogConfig();
        Log::setConfig('shops', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['info', 'debug', 'notice', 'warning'],
            'scopes' => ['transactions', 'orders'],
            'file' => 'shops',
        ]);

        Log::info('info message', 'transactions');
        $this->assertFileDoesNotExist(LOGS . 'error.log');
        $this->assertFileExists(LOGS . 'shops.log');
        $this->assertFileExists(LOGS . 'debug.log');

        $this->deleteLogs();

        Log::error('error message', 'orders');
        $this->assertFileExists(LOGS . 'error.log');
        $this->assertFileDoesNotExist(LOGS . 'debug.log');
        $this->assertFileDoesNotExist(LOGS . 'shops.log');

        $this->deleteLogs();

        Log::warning('warning message', 'orders');
        $this->assertFileExists(LOGS . 'error.log');
        $this->assertFileExists(LOGS . 'shops.log');
        $this->assertFileDoesNotExist(LOGS . 'debug.log');

        $this->deleteLogs();

        Log::drop('shops');
    }

    /**
     * Test that scopes are exclusive and don't bleed.
     */
    public function testScopedLoggingExclusive(): void
    {
        $this->deleteLogs();

        Log::setConfig('shops', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['debug', 'notice', 'warning'],
            'scopes' => ['transactions', 'orders'],
            'file' => 'shops.log',
        ]);
        Log::setConfig('eggs', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['debug', 'notice', 'warning'],
            'scopes' => ['eggs'],
            'file' => 'eggs.log',
        ]);

        Log::write('debug', 'transactions message', 'transactions');
        $this->assertFileDoesNotExist(LOGS . 'eggs.log');
        $this->assertFileExists(LOGS . 'shops.log');

        $this->deleteLogs();

        Log::write('debug', 'eggs message', ['scope' => ['eggs']]);
        $this->assertFileExists(LOGS . 'eggs.log');
        $this->assertFileDoesNotExist(LOGS . 'shops.log');
    }

    /**
     * testPassingScopeToEngine method
     */
    public function testPassingScopeToEngine(): void
    {
        static::setAppNamespace();

        Log::reset();

        Log::setConfig('scope_test', [
            'engine' => 'TestApp',
            'path' => LOGS,
            'levels' => ['notice', 'info', 'debug'],
            'scopes' => ['foo', 'bar'],
        ]);

        /** @var \TestApp\Log\Engine\TestAppLog $engine */
        $engine = Log::engine('scope_test');
        $this->assertNull($engine->passedScope);

        Log::write('debug', 'test message', 'foo');
        $this->assertEquals(['scope' => ['foo']], $engine->passedScope);

        Log::write('debug', 'test message', ['foo', 'bar']);
        $this->assertEquals(['scope' => ['foo', 'bar']], $engine->passedScope);

        $result = Log::write('debug', 'test message');
        $this->assertFalse($result);
    }

    /**
     * test convenience methods
     */
    public function testConvenienceMethods(): void
    {
        $this->deleteLogs();

        Log::setConfig('debug', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['notice', 'info', 'debug'],
            'file' => 'debug',
        ]);
        Log::setConfig('error', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['emergency', 'alert', 'critical', 'error', 'warning'],
            'file' => 'error',
        ]);

        $testMessage = 'emergency message';
        Log::emergency($testMessage);
        $contents = file_get_contents(LOGS . 'error.log');
        $this->assertMatchesRegularExpression('/(emergency|critical): ' . $testMessage . '/', $contents);
        $this->assertFileDoesNotExist(LOGS . 'debug.log');
        $this->deleteLogs();

        $testMessage = 'alert message';
        Log::alert($testMessage);
        $contents = file_get_contents(LOGS . 'error.log');
        $this->assertMatchesRegularExpression('/(alert|critical): ' . $testMessage . '/', $contents);
        $this->assertFileDoesNotExist(LOGS . 'debug.log');
        $this->deleteLogs();

        $testMessage = 'critical message';
        Log::critical($testMessage);
        $contents = file_get_contents(LOGS . 'error.log');
        $this->assertStringContainsString('critical: ' . $testMessage, $contents);
        $this->assertFileDoesNotExist(LOGS . 'debug.log');
        $this->deleteLogs();

        $testMessage = 'error message';
        Log::error($testMessage);
        $contents = file_get_contents(LOGS . 'error.log');
        $this->assertStringContainsString('error: ' . $testMessage, $contents);
        $this->assertFileDoesNotExist(LOGS . 'debug.log');
        $this->deleteLogs();

        $testMessage = 'warning message';
        Log::warning($testMessage);
        $contents = file_get_contents(LOGS . 'error.log');
        $this->assertStringContainsString('warning: ' . $testMessage, $contents);
        $this->assertFileDoesNotExist(LOGS . 'debug.log');
        $this->deleteLogs();

        $testMessage = 'notice message';
        Log::notice($testMessage);
        $contents = file_get_contents(LOGS . 'debug.log');
        $this->assertMatchesRegularExpression('/(notice|debug): ' . $testMessage . '/', $contents);
        $this->assertFileDoesNotExist(LOGS . 'error.log');
        $this->deleteLogs();

        $testMessage = 'info message';
        Log::info($testMessage);
        $contents = file_get_contents(LOGS . 'debug.log');
        $this->assertMatchesRegularExpression('/(info|debug): ' . $testMessage . '/', $contents);
        $this->assertFileDoesNotExist(LOGS . 'error.log');
        $this->deleteLogs();

        $testMessage = 'debug message';
        Log::debug($testMessage);
        $contents = file_get_contents(LOGS . 'debug.log');
        $this->assertStringContainsString('debug: ' . $testMessage, $contents);
        $this->assertFileDoesNotExist(LOGS . 'error.log');
        $this->deleteLogs();
    }

    /**
     * Test that write() returns false on an unhandled message.
     */
    public function testWriteUnhandled(): void
    {
        Log::drop('error');
        Log::drop('debug');

        $result = Log::write('error', 'Bad stuff', 'impossible');
        $this->assertFalse($result);
    }

    /**
     * Tests using a callable for creating a Log engine
     */
    public function testCreateLoggerWithCallable(): void
    {
        $instance = new FileLog();
        Log::setConfig('default', function ($alias) use ($instance) {
            $this->assertSame('default', $alias);

            return $instance;
        });
        $this->assertSame($instance, Log::engine('default'));
    }

    /**
     * A logger that writes a log message from inside its own log() must not be handed that
     * message while it is still dispatching, or it calls itself until the process dies.
     */
    public function testWriteDoesNotReenterTheSameLogger(): void
    {
        $file = $this->captureErrorLog();
        $logger = new ReentrantLog();
        Log::setConfig('reentrant', $logger);

        Log::write('error', 'the original problem');

        $this->assertSame(['the original problem'], $logger->messages);
        $this->assertStringContainsString(
            'dropped: logger `reentrant` reentered while writing',
            (string)file_get_contents($file),
            'The suppressed message should still be recorded somewhere.',
        );
    }

    /**
     * The nested message still reaches the other configured loggers. Only the one that is already
     * dispatching is skipped, so a per-logger cycle does not silence the rest of the stack.
     */
    public function testWriteStillDispatchesNestedMessageToOtherLoggers(): void
    {
        $this->captureErrorLog();
        $reentrant = new ReentrantLog();
        $bystander = new class extends BaseLog {
            public array $messages = [];

            public function log($level, Stringable|string $message, array $context = []): void
            {
                $this->messages[] = (string)$message;
            }
        };
        Log::setConfig('reentrant', $reentrant);
        Log::setConfig('bystander', $bystander);

        Log::write('error', 'the original problem');

        $this->assertSame(['the original problem'], $reentrant->messages);
        $this->assertSame(
            ['INSERT INTO logs ...', 'the original problem'],
            $bystander->messages,
            'The bystander should have received the nested message as well as the original.',
        );
    }

    /**
     * The marker is cleared once dispatch finishes, so a later write is handled normally rather
     * than being mistaken for a reentrant one forever.
     */
    public function testLoggerIsWritableAgainAfterReentrantWrite(): void
    {
        $this->captureErrorLog();
        $logger = new ReentrantLog();
        Log::setConfig('reentrant', $logger);

        Log::write('error', 'first');
        Log::write('error', 'second');

        $this->assertSame(['first', 'second'], $logger->messages);
    }

    /**
     * A logger that throws must not leave itself marked as dispatching, otherwise one failure
     * would silence it for the rest of the process.
     */
    public function testMarkerIsClearedWhenTheLoggerThrows(): void
    {
        $logger = new class extends BaseLog {
            public bool $explode = true;

            public array $messages = [];

            public function log($level, Stringable|string $message, array $context = []): void
            {
                if ($this->explode) {
                    throw new RuntimeException('logger blew up');
                }

                $this->messages[] = (string)$message;
            }
        };
        Log::setConfig('exploding', $logger);

        try {
            Log::write('error', 'first');
            $this->fail('Expected the logger failure to propagate.');
        } catch (RuntimeException $e) {
            $this->assertSame('logger blew up', $e->getMessage());
        }

        $logger->explode = false;
        Log::write('error', 'second');

        $this->assertSame(['second'], $logger->messages);
    }
}
