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
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\TestSuite;

use Cake\Log\Log;
use Cake\TestSuite\LogTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\AssertionFailedError;
use TestApp\Log\Engine\TestAppLog;

/**
 * Tests LogTrait assertions
 */
class LogTestTraitTest extends TestCase
{
    use LogTestTrait;

    /**
     * Test expecting log messages
     */
    public function testExpectLog(): void
    {
        $this->setupLog('debug');
        Log::setConfig([
            'error' => [
                'className' => TestAppLog::class,
                'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
            ],
        ]);
        Log::debug('This usually needs to happen inside your app');
        $this->assertLogMessage('debug', 'This usually needs to happen inside your app');
    }

    /**
     * Test expecting log messages from different engines
     */
    public function testExpectMultipleLog(): void
    {
        $this->setupLog(['debug', 'error']);
        Log::debug('This usually needs to happen inside your app');
        Log::error('Some error message');
        $this->assertLogMessage('debug', 'This usually needs to happen inside your app');
        $this->assertLogMessage('error', 'Some error message');
    }

    /**
     * Test log messages from lower levels don't get mixed up with upper level ones
     */
    public function testExpectMultipleLogsMixedUpWithHigherFails(): void
    {
        $this->setupLog(['debug', 'error']);
        Log::debug('This usually needs to happen inside your app');
        Log::error('Some error message');

        $this->expectException(AssertionFailedError::class);
        $this->assertLogMessage('error', 'This usually needs to happen inside your app');
    }

    /**
     * Test log messages from higher levels don't get mixed up with lower level ones
     */
    public function testExpectMultipleLogsMixedUpWithLowerFails(): void
    {
        $this->setupLog(['debug', 'error']);
        Log::debug('This usually needs to happen inside your app');
        Log::error('Some error message');

        $this->expectException(AssertionFailedError::class);
        $this->assertLogMessage('debug', 'Some error message');
    }

    /**
     * Test expecting log messages contains
     */
    public function testExpectLogContains(): void
    {
        $this->setupLog('debug');
        Log::debug('This usually needs to happen inside your app');
        $this->assertLogMessageContains('debug', 'This usually needs');
    }

    /**
     * Test expecting log message without setup
     */
    public function testExpectLogWithoutSetup(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Could not find the message `debug: ` in logs.');
        $this->assertLogMessage('debug', '');
    }

    /**
     * Test expecting log messages from different engines with custom configs
     */
    public function testExpectMultipleLogWithLevels(): void
    {
        $this->setupLog([
            'debug' => [
                'levels' => ['notice', 'info', 'debug'],
            ],
            'error' => [
                'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
            ],
        ]);
        Log::notice('This is a notice message');
        Log::info('This is a info message');
        Log::debug('This is a debug message');
        Log::warning('This is a warning message');
        Log::error('This is a error message');
        Log::critical('This is a critical message');
        Log::alert('This is a alert message');
        Log::emergency('This is a emergency message');
        $this->assertLogMessage('notice', 'This is a notice message');
        $this->assertLogMessage('info', 'This is a info message');
        $this->assertLogMessage('debug', 'This is a debug message');
        $this->assertLogMessage('warning', 'This is a warning message');
        $this->assertLogMessage('error', 'This is a error message');
        $this->assertLogMessage('critical', 'This is a critical message');
        $this->assertLogMessage('alert', 'This is a alert message');
        $this->assertLogMessage('emergency', 'This is a emergency message');
    }

    /**
     * Test expecting log messages from different engines with custom configs
     */
    public function testExpectMultipleLogAbsent(): void
    {
        $this->setupLog([
            'error' => [
                'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
            ],
        ]);
        Log::notice('This is a notice message');
        Log::info('This is a info message');
        Log::debug('This is a debug message');
        Log::warning('This is a warning message');
        Log::error('This is a error message');
        Log::critical('This is a critical message');
        Log::alert('This is a alert message');
        Log::emergency('This is a emergency message');

        $this->assertLogAbsent('notice', 'No notice messages should be captured');
        $this->assertLogAbsent('info', 'No info messages should be captured');
        $this->assertLogAbsent('debug', 'No debug messages should be captured');
        $this->assertLogMessage('warning', 'This is a warning message');
        $this->assertLogMessage('error', 'This is a error message');
        $this->assertLogMessage('critical', 'This is a critical message');
        $this->assertLogMessage('alert', 'This is a alert message');
        $this->assertLogMessage('emergency', 'This is a emergency message');
    }

    public function testAbsentLogWithSetup(): void
    {
        $this->setupLog([
            'error' => [
                'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
            ],
        ]);
        $this->assertLogAbsent('warning', 'This is a warning message');
        $this->assertLogAbsent('error', 'This is a error message');
        $this->assertLogAbsent('critical', 'This is a critical message');
        $this->assertLogAbsent('alert', 'This is a critical message');
        $this->assertLogAbsent('emergency', 'This is a emergency message');
    }

    public function testAbsentLogWithoutSetup(): void
    {
        Log::setConfig([
            'debug' => [
                'className' => TestAppLog::class,
                'levels' => ['notice', 'info', 'debug'],
            ],
        ]);
        Log::debug('This is a debug message');
        $this->expectNotToPerformAssertions();
        $this->assertLogAbsent('debug', 'Some error message');
    }

    /**
     * Test expecting log messages from different engines with custom configs
     */
    public function testExpectMultipleLogWithLevelsAndScopes(): void
    {
        $this->setupLog([
            'debug' => [
                'levels' => ['notice', 'info', 'debug'],
            ],
            'error' => [
                'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
            ],
            'scoped' => [
                'scopes' => ['app.security'],
                'levels' => ['info'],
            ],
        ]);
        Log::info('This is a info message');
        Log::info('security settings changed', 'app.security');
        Log::warning('This is a warning message');
        Log::error('This is a error message');

        $this->assertLogMessage('info', 'This is a info message');
        $this->assertLogMessage('info', 'security settings changed');
        $this->assertLogMessage('info', 'security settings changed', 'app.security');
        $this->assertLogMessage('warning', 'This is a warning message');
        $this->assertLogMessage('error', 'This is a error message');
    }
}
