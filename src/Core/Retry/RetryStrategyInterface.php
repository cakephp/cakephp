<?php
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
 * Used to instruct a CommandRetry object on whether or not a retry
 * for an action should be performed
 */
interface RetryStrategyInterface
{
    /**
     * Returns true if the action can be retried, false otherwise.
     *
     * @param \Exception $exception The exception that caused the action to fail
     * @param int $retryCount The number of times the action has been already called
     * @return bool Whether or not it is OK to retry the action
     */
    public function shouldRetry(Exception $exception, $retryCount);
}
