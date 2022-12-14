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
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Retry;

use Cake\Core\Retry\RetryStrategyInterface;
use Exception;
use PDOException;

/**
 * Implements retry strategy based on db error codes and wait interval.
 *
 * @internal
 */
class ErrorCodeWaitStrategy implements RetryStrategyInterface
{
    /**
     * @var array<int>
     */
    protected array $errorCodes;

    /**
     * @var int
     */
    protected int $retryInterval;

    /**
     * @param array<int> $errorCodes DB-specific error codes that allow retrying
     * @param int $retryInterval Seconds to wait before allowing next retry, 0 for no wait.
     */
    public function __construct(array $errorCodes, int $retryInterval)
    {
        $this->errorCodes = $errorCodes;
        $this->retryInterval = $retryInterval;
    }

    /**
     * @inheritDoc
     */
    public function shouldRetry(Exception $exception, int $retryCount): bool
    {
        if (
            $exception instanceof PDOException &&
            $exception->errorInfo &&
            in_array($exception->errorInfo[1], $this->errorCodes)
        ) {
            if ($this->retryInterval > 0) {
                sleep($this->retryInterval);
            }

            return true;
        }

        return false;
    }
}
