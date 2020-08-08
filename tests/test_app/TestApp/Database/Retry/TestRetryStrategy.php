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
namespace TestApp\Database\Retry;

use Cake\Core\Retry\RetryStrategyInterface;
use Exception;

class TestRetryStrategy implements RetryStrategyInterface
{
    /**
     * @var bool
     */
    protected $allowRetry;

    public function __construct(bool $allowRetry)
    {
        $this->allowRetry = $allowRetry;
    }

    /**
     * @inheritDoc
     */
    public function shouldRetry(Exception $exception, int $retryCount): bool
    {
        return $this->allowRetry;
    }
}
