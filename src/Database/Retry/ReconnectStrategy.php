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
namespace Cake\Database\Retry;

use Cake\Core\Retry\RetryStrategyInterface;
use Cake\Database\Connection;
use Exception;

/**
 * Makes sure the connection to the database is alive before authorizing
 * the retry of an action.
 *
 * @internal
 */
class ReconnectStrategy implements RetryStrategyInterface
{
    /**
     * The list of error strings to match when looking for a disconnection error.
     *
     * This is a static variable to enable opcache to inline the values.
     *
     * @var array<string>
     */
    protected static array $causes = [
        'gone away',
        'Lost connection',
        'Transaction() on null',
        'closed the connection unexpectedly',
        'closed unexpectedly',
        'deadlock avoided',
        'decryption failed or bad record mac',
        'is dead or not enabled',
        'no connection to the server',
        'query_wait_timeout',
        'reset by peer',
        'terminate due to client_idle_limit',
        'while sending',
        'writing data to the connection',
    ];

    /**
     * The connection to check for validity
     *
     * @var \Cake\Database\Connection
     */
    protected Connection $connection;

    /**
     * Creates the ReconnectStrategy object by storing a reference to the
     * passed connection. This reference will be used to automatically
     * reconnect to the server in case of failure.
     *
     * @param \Cake\Database\Connection $connection The connection to check
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritDoc}
     *
     * Checks whether the exception was caused by a lost connection,
     * and returns true if it was able to successfully reconnect.
     */
    public function shouldRetry(Exception $exception, int $retryCount): bool
    {
        $message = $exception->getMessage();

        foreach (static::$causes as $cause) {
            if (str_contains($message, $cause)) {
                return $this->reconnect();
            }
        }

        return false;
    }

    /**
     * Tries to re-establish the connection to the server, if it is safe to do so
     *
     * @return bool Whether the connection was re-established
     */
    protected function reconnect(): bool
    {
        if ($this->connection->inTransaction()) {
            // It is not safe to blindly reconnect in the middle of a transaction
            return false;
        }

        try {
            // Make sure we free any resources associated with the old connection
            $this->connection->getDriver()->disconnect();
        } catch (Exception) {
        }

        try {
            $this->connection->getDriver()->connect();
            $this->connection->getDriver()->log(
                'connection={connection} [RECONNECT]',
                ['connection' => $this->connection->configName()],
            );

            return true;
        } catch (Exception) {
            // If there was an error connecting again, don't report it back,
            // let the retry handler do it.
            return false;
        }
    }
}
