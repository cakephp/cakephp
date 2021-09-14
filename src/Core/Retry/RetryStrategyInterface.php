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
 * Used to instruct a CommandRetry object on whether a retry
 * for an action should be performed
 */
interface RetryStrategyInterface
{
    /**
     * Returns true if the action can be retried, false otherwise.
     *
     * @param \Exception $exception The exception that caused the action to fail
     * @param int $retryCount The number of times action has been retried
     * @return bool Whether it is OK to retry the action
     */
    public function shouldRetry(Exception $exception, int $retryCount): bool;
}
