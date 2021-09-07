<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;

/**
 * Helper for managing test connections
 */
class ConnectionHelper
{
    /**
     * Adds `test_<connection name>` aliases for all non-test connections.
     *
     * This forces all models to use the test connection instead. For example,
     * if a model is confused to use connection `files` then it will be aliased
     * to `test_files`.
     *
     * The `default` connection is aliased to `test`.
     *
     * @return void
     */
    public function addTestAliases(): void
    {
        ConnectionManager::alias('test', 'default');
        foreach (ConnectionManager::configured() as $connection) {
            if ($connection === 'test' || $connection === 'default') {
                continue;
            }

            if (strpos($connection, 'test_') === 0) {
                $original = substr($connection, 5);
                ConnectionManager::alias($connection, $original);
            } else {
                $test = 'test_' . $connection;
                ConnectionManager::alias($test, $connection);
            }
        }
    }

    /**
     * Enables query logging for all database connections.
     *
     * @param array<int, string> $connections Connection names or empty for all.
     * @return void
     */
    public function enableQueryLogging(array $connections = []): void
    {
        $connections = $connections ? $connections : ConnectionManager::configured();
        foreach ($connections as $connection) {
            $connection = ConnectionManager::get($connection);
            if ($connection instanceof Connection) {
                $connection->enableQueryLogging();
            }
        }
    }
}
