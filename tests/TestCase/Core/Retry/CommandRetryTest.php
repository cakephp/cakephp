<?php
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
use Cake\Core\Retry\RetryStrategyInterface;
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
        $exception = new Exception('this is failing');
        $action = function () use (&$count, $exception) {
            $count++;

            if ($count < 4) {
                throw $exception;
            }

            return $count;
        };

        $strategy = $this->getMockBuilder(RetryStrategyInterface::class)->getMock();
        $strategy
            ->expects($this->exactly(3))
            ->method('shouldRetry')
            ->will($this->returnCallback(function ($e, $c) use ($exception, &$count) {
                $this->assertSame($e, $exception);
                $this->assertEquals($c + 1, $count);

                return true;
            }));

        $retry = new CommandRetry($strategy, 5);
        $this->assertEquals(4, $retry->run($action));
    }

    /**
     * Test attempts exceeded
     *
     * @return void
     */
    public function testExceedAttempts()
    {
        $exception = new Exception('this is failing');
        $action = function () use ($exception) {
            throw $exception;
        };

        $strategy = $this->getMockBuilder(RetryStrategyInterface::class)->getMock();
        $strategy
            ->expects($this->exactly(4))
            ->method('shouldRetry')
            ->will($this->returnCallback(function ($e) use ($exception) {
                return true;
            }));

        $retry = new CommandRetry($strategy, 3);
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
        $action = function () {
            throw new Exception('this is failing');
        };

        $strategy = $this->getMockBuilder(RetryStrategyInterface::class)->getMock();
        $strategy
            ->expects($this->once())
            ->method('shouldRetry')
            ->will($this->returnCallback(function () {
                return false;
            }));

        $retry = new CommandRetry($strategy, 3);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('this is failing');
        $retry->run($action);
    }
}
