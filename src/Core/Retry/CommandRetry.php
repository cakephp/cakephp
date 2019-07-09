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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core\Retry;

use Exception;

/**
 * Allows any action to be retried in case of an exception.
 *
 * This class can be parametrized with a strategy, which will be followed
 * to determine whether or not the action should be retried.
 */
class CommandRetry
{
    /**
     * The strategy to follow should the executed action fail.
     *
     * @var \Cake\Core\Retry\RetryStrategyInterface
     */
    protected $strategy;

    /**
     * The number of retries to perform in case of failure.
     *
     * @var int
     */
    protected $retries;

    /**
     * Creates the CommandRetry object with the given strategy and retry count
     *
     * @param \Cake\Core\Retry\RetryStrategyInterface $strategy The strategy to follow should the action fail
     * @param int $retries The number of times the action has been already called
     */
    public function __construct(RetryStrategyInterface $strategy, int $retries = 1)
    {
        $this->strategy = $strategy;
        $this->retries = $retries;
    }

    /**
     * The number of retries to perform in case of failure
     *
     * @param callable $action The callable action to execute with a retry strategy
     * @return mixed The return value of the passed action callable
     * @throws \Exception
     */
    public function run(callable $action)
    {
        $retryCount = 0;
        $lastException = null;

        do {
            try {
                return $action();
            } catch (Exception $e) {
                $lastException = $e;
                if (!$this->strategy->shouldRetry($e, $retryCount)) {
                    throw $e;
                }
            }
        } while ($this->retries > $retryCount++);

        /** @psalm-suppress RedundantCondition */
        if ($lastException !== null) {
            throw $lastException;
        }
    }
}
