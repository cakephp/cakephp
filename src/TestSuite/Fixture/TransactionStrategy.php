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
 */
class TransactionStrategy implements StateResetStrategyInterface
{
    /**
     * Constructor.
     *
     * @param \Cake\TestSuite\Fixture\FixtureLoader $fixtures The fixture loader being used.
     * @return void
     */
    public function __construct(FixtureLoader $loader)
    {
        $this->checkConnections();
    }

    /**
     * Ensure that all `Connection` connections support savepoints.
     *
     * @return void
     */
    protected function checkConnections(): void
    {
        $connections = ConnectionManager::configured();
        foreach ($connections as $name) {
            $connection = ConnectionManager::get($name);
            if ($connection instanceof Connection) {
                $connection->enableSavePoints();
                if (!$connection->isSavePointsEnabled()) {
                    throw new RuntimeException(
                        "Could not enable save points for the `{$name}` connection. " .
                            'Your database needs to support savepoints in order to use the ' .
                            'TransactionStrategy for fixtures.'
                    );
                }
            }
        }
    }

    /**
     * Before each test start a transaction.
     *
     * @return void
     */
    public function setupTest(): void
    {
        $connections = ConnectionManager::configured();
        foreach ($connections as $connection) {
            if (strpos($connection, 'test') !== 0) {
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
    public function teardownTest(): void
    {
        $connections = ConnectionManager::configured();
        foreach ($connections as $connection) {
            if (strpos($connection, 'test') !== 0) {
                continue;
            }
            $db = ConnectionManager::get($connection);
            if ($db instanceof Connection) {
                $db->rollback(true);
            }
        }
    }
}
