<?php
/**
 * CakePHP(tm) <https://book.cakephp.org/3.0/en/development/testing.html>
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

use Cake\Core\Plugin;
use Cake\Log\Engine\FileLog;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;

/**
 * LogTest class
 */
class LogTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        Log::reset();
    }

    public function tearDown()
    {
        parent::tearDown();
        Log::reset();
    }

    /**
     * test importing loggers from app/libs and plugins.
     *
     * @return void
     */
    public function testImportingLoggers()
    {
        static::setAppNamespace();
        Plugin::load('TestPlugin');

        Log::config('libtest', [
            'engine' => 'TestApp'
        ]);
        Log::config('plugintest', [
            'engine' => 'TestPlugin.TestPlugin'
        ]);

        $result = Log::engine('libtest');
        $this->assertInstanceOf('TestApp\Log\Engine\TestAppLog', $result);
        $this->assertContains('libtest', Log::configured());

        $result = Log::engine('plugintest');
        $this->assertInstanceOf('TestPlugin\Log\Engine\TestPluginLog', $result);
        $this->assertContains('libtest', Log::configured());
        $this->assertContains('plugintest', Log::configured());

        Log::write(LOG_INFO, 'TestPluginLog is not a BaseLog descendant');

        Plugin::unload();
    }

    /**
     * test all the errors from failed logger imports
     *
     * @return void
     */
    public function testImportingLoggerFailure()
    {
        $this->expectException(\RuntimeException::class);
        Log::config('fail', []);
        Log::engine('fail');
    }

    /**
     * test config() with valid key name
     *
     * @return void
     */
    public function testValidKeyName()
    {
        Log::config('valid', ['engine' => 'File']);
        $stream = Log::engine('valid');
        $this->assertInstanceOf('Cake\Log\Engine\FileLog', $stream);
    }

    /**
     * test that loggers have to implement the correct interface.
     *
     * @return void
     */
    public function testNotImplementingInterface()
    {
        $this->expectException(\RuntimeException::class);
        Log::config('fail', ['engine' => '\stdClass']);
        Log::engine('fail');
    }

    /**
     * explicit tests for drop()
     *
     * @return void
     */
    public function testDrop()
    {
        Log::config('file', [
            'engine' => 'File',
            'path' => LOGS
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
     *
     * @return void
     */
    public function testInvalidLevel()
    {
        $this->expectException(\InvalidArgumentException::class);
        Log::config('myengine', ['engine' => 'File']);
        Log::write('invalid', 'This will not be logged');
    }

    /**
     * Provider for config() tests.
     *
     * @return array
     */
    public static function configProvider()
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
     * @dataProvider configProvider
     * @return void
     */
    public function testConfigVariants($settings)
    {
        Log::config('test', $settings);
        $this->assertContains('test', Log::configured());
        $this->assertInstanceOf('Cake\Log\Engine\FileLog', Log::engine('test'));
        Log::drop('test');
    }

    /**
     * Test the various setConfig call signatures.
     *
     * @dataProvider configProvider
     * @return void
     */
    public function testSetConfigVariants($settings)
    {
        Log::setConfig('test', $settings);
        $this->assertContains('test', Log::configured());
        $this->assertInstanceOf('Cake\Log\Engine\FileLog', Log::engine('test'));
        Log::drop('test');
    }

    /**
     * Test that config() throws an exception when adding an
     * adapter with the wrong type.
     *
     * @return void
     */
    public function testConfigInjectErrorOnWrongType()
    {
        $this->expectException(\RuntimeException::class);
        Log::config('test', new \StdClass);
        Log::info('testing');
    }

    /**
     * Test that setConfig() throws an exception when adding an
     * adapter with the wrong type.
     *
     * @return void
     */
    public function testSetConfigInjectErrorOnWrongType()
    {
        $this->expectException(\RuntimeException::class);
        Log::setConfig('test', new \StdClass);
        Log::info('testing');
    }

    /**
     * Test that config() can read data back
     *
     * @return void
     */
    public function testConfigRead()
    {
        $config = [
            'engine' => 'File',
            'path' => LOGS
        ];
        Log::config('tests', $config);

        $expected = $config;
        $expected['className'] = $config['engine'];
        unset($expected['engine']);
        $this->assertSame($expected, Log::config('tests'));
    }

    /**
     * Ensure you cannot reconfigure a log adapter.
     *
     * @return void
     */
    public function testConfigErrorOnReconfigure()
    {
        $this->expectException(\BadMethodCallException::class);
        Log::config('tests', ['engine' => 'File', 'path' => TMP]);
        Log::config('tests', ['engine' => 'Apc']);
    }

    /**
     * testLogFileWriting method
     *
     * @return void
     */
    public function testLogFileWriting()
    {
        $this->_resetLogConfig();
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
        $this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 1/', $result);
        $this->assertRegExp('/2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 2$/', $result);
        unlink(LOGS . 'error.log');
    }

    /**
     * test selective logging by level/type
     *
     * @return void
     */
    public function testSelectiveLoggingByLevel()
    {
        if (file_exists(LOGS . 'spam.log')) {
            unlink(LOGS . 'spam.log');
        }
        if (file_exists(LOGS . 'eggs.log')) {
            unlink(LOGS . 'eggs.log');
        }
        Log::config('spam', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => 'debug',
            'file' => 'spam',
        ]);
        Log::config('eggs', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['eggs', 'debug', 'error', 'warning'],
            'file' => 'eggs',
        ]);

        $testMessage = 'selective logging';
        Log::write('warning', $testMessage);

        $this->assertFileExists(LOGS . 'eggs.log');
        $this->assertFileNotExists(LOGS . 'spam.log');

        Log::write('debug', $testMessage);
        $this->assertFileExists(LOGS . 'spam.log');

        $contents = file_get_contents(LOGS . 'spam.log');
        $this->assertContains('Debug: ' . $testMessage, $contents);
        $contents = file_get_contents(LOGS . 'eggs.log');
        $this->assertContains('Debug: ' . $testMessage, $contents);

        if (file_exists(LOGS . 'spam.log')) {
            unlink(LOGS . 'spam.log');
        }
        if (file_exists(LOGS . 'eggs.log')) {
            unlink(LOGS . 'eggs.log');
        }
    }

    /**
     * test selective logging by level using the `types` attribute
     *
     * @return void
     */
    public function testSelectiveLoggingByLevelUsingTypes()
    {
        if (file_exists(LOGS . 'spam.log')) {
            unlink(LOGS . 'spam.log');
        }
        if (file_exists(LOGS . 'eggs.log')) {
            unlink(LOGS . 'eggs.log');
        }
        Log::config('spam', [
            'engine' => 'File',
            'path' => LOGS,
            'types' => 'debug',
            'file' => 'spam',
        ]);
        Log::config('eggs', [
            'engine' => 'File',
            'path' => LOGS,
            'types' => ['eggs', 'debug', 'error', 'warning'],
            'file' => 'eggs',
        ]);

        $testMessage = 'selective logging';
        Log::write('warning', $testMessage);

        $this->assertFileExists(LOGS . 'eggs.log');
        $this->assertFileNotExists(LOGS . 'spam.log');

        Log::write('debug', $testMessage);
        $this->assertFileExists(LOGS . 'spam.log');

        $contents = file_get_contents(LOGS . 'spam.log');
        $this->assertContains('Debug: ' . $testMessage, $contents);
        $contents = file_get_contents(LOGS . 'eggs.log');
        $this->assertContains('Debug: ' . $testMessage, $contents);

        if (file_exists(LOGS . 'spam.log')) {
            unlink(LOGS . 'spam.log');
        }
        if (file_exists(LOGS . 'eggs.log')) {
            unlink(LOGS . 'eggs.log');
        }
    }

    protected function _resetLogConfig()
    {
        Log::config('debug', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['notice', 'info', 'debug'],
            'file' => 'debug',
        ]);
        Log::config('error', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
            'file' => 'error',
        ]);
    }

    protected function _deleteLogs()
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
     *
     * @return void
     */
    public function testScopedLogging()
    {
        $this->_deleteLogs();
        $this->_resetLogConfig();
        Log::config('shops', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['info', 'debug', 'warning'],
            'scopes' => ['transactions', 'orders'],
            'file' => 'shops',
        ]);

        Log::write('debug', 'debug message', 'transactions');
        $this->assertFileNotExists(LOGS . 'error.log');
        $this->assertFileExists(LOGS . 'shops.log');
        $this->assertFileExists(LOGS . 'debug.log');

        $this->_deleteLogs();

        Log::write('warning', 'warning message', 'orders');
        $this->assertFileExists(LOGS . 'error.log');
        $this->assertFileExists(LOGS . 'shops.log');
        $this->assertFileNotExists(LOGS . 'debug.log');

        $this->_deleteLogs();

        Log::write('error', 'error message', ['scope' => 'orders']);
        $this->assertFileExists(LOGS . 'error.log');
        $this->assertFileNotExists(LOGS . 'debug.log');
        $this->assertFileNotExists(LOGS . 'shops.log');

        $this->_deleteLogs();

        Log::drop('shops');
    }

    /**
     * Test scoped logging without the default loggers catching everything
     *
     * @return void
     */
    public function testScopedLoggingStrict()
    {
        $this->_deleteLogs();

        Log::config('debug', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['notice', 'info', 'debug'],
            'file' => 'debug',
            'scopes' => false
        ]);
        Log::config('shops', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['info', 'debug', 'warning'],
            'file' => 'shops',
            'scopes' => ['transactions', 'orders'],
        ]);

        Log::write('debug', 'debug message');
        $this->assertFileNotExists(LOGS . 'shops.log');
        $this->assertFileExists(LOGS . 'debug.log');

        $this->_deleteLogs();

        Log::write('debug', 'debug message', 'orders');
        $this->assertFileExists(LOGS . 'shops.log');
        $this->assertFileNotExists(LOGS . 'debug.log');

        $this->_deleteLogs();

        Log::drop('shops');
    }

    /**
     * test scoped logging with convenience methods
     */
    public function testConvenienceScopedLogging()
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

        $this->_resetLogConfig();
        Log::config('shops', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['info', 'debug', 'notice', 'warning'],
            'scopes' => ['transactions', 'orders'],
            'file' => 'shops',
        ]);

        Log::info('info message', 'transactions');
        $this->assertFileNotExists(LOGS . 'error.log');
        $this->assertFileExists(LOGS . 'shops.log');
        $this->assertFileExists(LOGS . 'debug.log');

        $this->_deleteLogs();

        Log::error('error message', 'orders');
        $this->assertFileExists(LOGS . 'error.log');
        $this->assertFileNotExists(LOGS . 'debug.log');
        $this->assertFileNotExists(LOGS . 'shops.log');

        $this->_deleteLogs();

        Log::warning('warning message', 'orders');
        $this->assertFileExists(LOGS . 'error.log');
        $this->assertFileExists(LOGS . 'shops.log');
        $this->assertFileNotExists(LOGS . 'debug.log');

        $this->_deleteLogs();

        Log::drop('shops');
    }

    /**
     * Test that scopes are exclusive and don't bleed.
     *
     * @return void
     */
    public function testScopedLoggingExclusive()
    {
        $this->_deleteLogs();

        Log::config('shops', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['debug', 'notice', 'warning'],
            'scopes' => ['transactions', 'orders'],
            'file' => 'shops.log',
        ]);
        Log::config('eggs', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['debug', 'notice', 'warning'],
            'scopes' => ['eggs'],
            'file' => 'eggs.log',
        ]);

        Log::write('debug', 'transactions message', 'transactions');
        $this->assertFileNotExists(LOGS . 'eggs.log');
        $this->assertFileExists(LOGS . 'shops.log');

        $this->_deleteLogs();

        Log::write('debug', 'eggs message', ['scope' => ['eggs']]);
        $this->assertFileExists(LOGS . 'eggs.log');
        $this->assertFileNotExists(LOGS . 'shops.log');
    }

    /**
     * testPassingScopeToEngine method
     */
    public function testPassingScopeToEngine()
    {
        static::setAppNamespace();

        Log::reset();

        Log::config('scope_test', [
            'engine' => 'TestApp',
            'path' => LOGS,
            'levels' => ['notice', 'info', 'debug'],
            'scopes' => ['foo', 'bar'],
        ]);

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
    public function testConvenienceMethods()
    {
        $this->_deleteLogs();

        Log::config('debug', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['notice', 'info', 'debug'],
            'file' => 'debug',
        ]);
        Log::config('error', [
            'engine' => 'File',
            'path' => LOGS,
            'levels' => ['emergency', 'alert', 'critical', 'error', 'warning'],
            'file' => 'error',
        ]);

        $testMessage = 'emergency message';
        Log::emergency($testMessage);
        $contents = file_get_contents(LOGS . 'error.log');
        $this->assertRegExp('/(Emergency|Critical): ' . $testMessage . '/', $contents);
        $this->assertFileNotExists(LOGS . 'debug.log');
        $this->_deleteLogs();

        $testMessage = 'alert message';
        Log::alert($testMessage);
        $contents = file_get_contents(LOGS . 'error.log');
        $this->assertRegExp('/(Alert|Critical): ' . $testMessage . '/', $contents);
        $this->assertFileNotExists(LOGS . 'debug.log');
        $this->_deleteLogs();

        $testMessage = 'critical message';
        Log::critical($testMessage);
        $contents = file_get_contents(LOGS . 'error.log');
        $this->assertContains('Critical: ' . $testMessage, $contents);
        $this->assertFileNotExists(LOGS . 'debug.log');
        $this->_deleteLogs();

        $testMessage = 'error message';
        Log::error($testMessage);
        $contents = file_get_contents(LOGS . 'error.log');
        $this->assertContains('Error: ' . $testMessage, $contents);
        $this->assertFileNotExists(LOGS . 'debug.log');
        $this->_deleteLogs();

        $testMessage = 'warning message';
        Log::warning($testMessage);
        $contents = file_get_contents(LOGS . 'error.log');
        $this->assertContains('Warning: ' . $testMessage, $contents);
        $this->assertFileNotExists(LOGS . 'debug.log');
        $this->_deleteLogs();

        $testMessage = 'notice message';
        Log::notice($testMessage);
        $contents = file_get_contents(LOGS . 'debug.log');
        $this->assertRegExp('/(Notice|Debug): ' . $testMessage . '/', $contents);
        $this->assertFileNotExists(LOGS . 'error.log');
        $this->_deleteLogs();

        $testMessage = 'info message';
        Log::info($testMessage);
        $contents = file_get_contents(LOGS . 'debug.log');
        $this->assertRegExp('/(Info|Debug): ' . $testMessage . '/', $contents);
        $this->assertFileNotExists(LOGS . 'error.log');
        $this->_deleteLogs();

        $testMessage = 'debug message';
        Log::debug($testMessage);
        $contents = file_get_contents(LOGS . 'debug.log');
        $this->assertContains('Debug: ' . $testMessage, $contents);
        $this->assertFileNotExists(LOGS . 'error.log');
        $this->_deleteLogs();
    }

    /**
     * Test that write() returns false on an unhandled message.
     *
     * @return void
     */
    public function testWriteUnhandled()
    {
        Log::drop('error');
        Log::drop('debug');

        $result = Log::write('error', 'Bad stuff', 'impossible');
        $this->assertFalse($result);
    }

    /**
     * Tests using a callable for creating a Log engine
     *
     * @return void
     */
    public function testCreateLoggerWithCallable()
    {
        $instance = new FileLog;
        Log::config('default', function ($alias) use ($instance) {
            $this->assertEquals('default', $alias);

            return $instance;
        });
        $this->assertSame($instance, Log::engine('default'));
    }
}
