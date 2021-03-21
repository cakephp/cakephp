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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Fixture;

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use RuntimeException;

/**
 * Fixture state strategy that wraps tests in transactions
 * that are rolled back at the end of the transaction.
 *
 * This strategy aims to gives good performance at the cost
 * of not being able to query data in fixtures from another
 * process.
 *
 * @TODO create a strategy interface
 */
class TransactionStrategy
{
    /**
     * Constructor.
     *
     * @param bool $enableLogging Whether or not to enable query logging.
     * @return void
     */
    public function __construct(bool $enableLogging = false)
    {
        $this->aliasConnections($enableLogging);
    }

    /**
     * Alias non test connections to the test ones
     * so that models reach the test database connections instead.
     *
     * @param bool $enableLogging Whether or not to enable query logging.
     * @return void
     */
    protected function aliasConnections(bool $enableLogging): void
    {
        $connections = ConnectionManager::configured();
        ConnectionManager::alias('test', 'default');
        $map = [];
        foreach ($connections as $connection) {
            if ($connection === 'test' || $connection === 'default') {
                continue;
            }
            if (isset($map[$connection])) {
                continue;
            }
            if (strpos($connection, 'test_') === 0) {
                $map[$connection] = substr($connection, 5);
            } else {
                $map['test_' . $connection] = $connection;
            }
        }
        foreach ($map as $testConnection => $normal) {
            ConnectionManager::alias($testConnection, $normal);
            $connection = ConnectionManager::get($normal);
            if ($connection instanceof Connection) {
                if (!$connection->enableSavePoints(true)) {
                    throw new RuntimeException(
                        "Could not enable save points for the `{$normal}` connection. " .
                            'Your database needs to support savepoints in order to use the ' .
                            'TransactionStrategy for fixtures.'
                    );
                }
                if ($enableLogging) {
                    $connection->enableQueryLogging(true);
                }
            }
        }
    }

    /**
     * Before each test start a transaction.
     *
     * @return void
     */
    public function beforeTest(): void
    {
        $connections = ConnectionManager::configured();
        foreach ($connections as $connection) {
            if (strpos($connection, 'test') === false) {
                continue;
            }
            $db = ConnectionManager::get($connection);
            if ($db instanceof Connection) {
                $db->begin();
                $db->createSavePoint('__fixtures__');
            }
        }
    }

    /**
     * After each test rollback the transaction.
     *
     * As long as the application code has balanced BEGIN/ROLLBACK
     * operations we should end up at a transaction depth of 0
     * and we will rollback the root transaction started in beforeTest()
     *
     * @return void
     */
    public function afterTest(): void
    {
        $connections = ConnectionManager::configured();
        foreach ($connections as $connection) {
            if (strpos($connection, 'test') === false) {
                continue;
            }
            $db = ConnectionManager::get($connection);
            if ($db instanceof Connection) {
                $db->rollback(true);
            }
        }
    }
}
