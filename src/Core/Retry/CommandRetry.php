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

use Closure;
use Exception;

/**
 * Allows any action to be retried in case of an exception.
 *
 * This class can be parametrized with a strategy, which will be followed
 * to determine whether the action should be retried.
 */
class CommandRetry
{
    protected int $numRetries;

    /**
     * Creates the CommandRetry object with the given strategy and retry count
     *
     * @param \Cake\Core\Retry\RetryStrategyInterface $strategy The strategy to follow should the action fail
     * @param int $maxRetries The maximum number of retry attempts allowed
     */
    public function __construct(
        /**
         * The strategy to follow should the executed action fail.
         */
        protected RetryStrategyInterface $strategy,
        protected int $maxRetries = 1
    )
    {
    }

    /**
     * The number of retries to perform in case of failure
     *
     * @param \Closure $action Callback to run for each attempt
     * @return mixed The return value of the passed action callable
     * @throws \Exception Throws exception from last failure
     */
    public function run(Closure $action): mixed
    {
        $this->numRetries = 0;
        while (true) {
            try {
                return $action();
            } catch (Exception $e) {
                if (
                    $this->numRetries < $this->maxRetries &&
                    $this->strategy->shouldRetry($e, $this->numRetries)
                ) {
                    $this->numRetries++;
                    continue;
                }

                throw $e;
            }
        }
    }

    /**
     * Returns the last number of retry attemps.
     */
    public function getRetries(): int
    {
        return $this->numRetries;
    }
}
