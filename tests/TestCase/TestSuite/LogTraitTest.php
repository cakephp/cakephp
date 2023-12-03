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
}
