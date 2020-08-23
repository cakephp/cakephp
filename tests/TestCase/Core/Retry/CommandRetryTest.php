<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core\Retry;

use Cake\Core\Retry\CommandRetry;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * Tests for the CommandRetry class
 */
class CommandRetryTest extends TestCase
{
    /**
     * Simple retry test
     *
     * @return void
     */
    public function testRetry()
    {
        $count = 0;
        $action = function () use (&$count) {
            if ($count < 2) {
                ++$count;
                throw new Exception('this is failing');
            }

            return $count;
        };

        $strategy = new \TestApp\Database\Retry\TestRetryStrategy(true);
        $retry = new CommandRetry($strategy, 2);
        $this->assertSame(2, $retry->run($action));
    }

    /**
     * Test attempts exceeded
     *
     * @return void
     */
    public function testExceedAttempts()
    {
        $count = 0;
        $action = function () use (&$count) {
            if ($count < 2) {
                ++$count;
                throw new Exception('this is failing');
            }

            return $count;
        };

        $strategy = new \TestApp\Database\Retry\TestRetryStrategy(true);
        $retry = new CommandRetry($strategy, 1);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('this is failing');
        $retry->run($action);
    }

    /**
     * Test that the strategy is respected
     *
     * @return void
     */
    public function testRespectStrategy()
    {
        $count = 0;
        $action = function () use (&$count) {
            throw new Exception('this is failing');
        };

        $strategy = new \TestApp\Database\Retry\TestRetryStrategy(false);
        $retry = new CommandRetry($strategy, 2);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('this is failing');
        $retry->run($action);
    }
}
