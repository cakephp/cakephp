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
use InvalidArgumentException;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Tests LogTrait assertions
 */
class LogTraitTest extends TestCase
{
    use LogTestTrait;

    /**
     * Test expecting log messages
     */
    public function testExpectLog(): void
    {
        $this->setupLog('debug');
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

        $this->expectException(ExpectationFailedException::class);
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

        $this->expectException(ExpectationFailedException::class);
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('`debug` is not of type ArrayLog. Make sure to call `setupLog(\'debug\')` before expecting a log message.');
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
        Log::emergency('This is a emergency message');
        $this->assertLogMessage('debug', 'This is a notice message', 'notice');
        $this->assertLogMessage('debug', 'This is a info message', 'info');
        $this->assertLogMessage('debug', 'This is a debug message', 'debug');
        $this->assertLogMessage('error', 'This is a warning message', 'warning');
        $this->assertLogMessage('error', 'This is a error message', 'error');
        $this->assertLogMessage('error', 'This is a critical message', 'critical');
        $this->assertLogMessage('error', 'This is a emergency message', 'emergency');
    }
}
