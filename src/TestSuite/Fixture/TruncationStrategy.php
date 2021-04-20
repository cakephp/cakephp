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
use Cake\Database\Schema\SqlGeneratorInterface;
use Cake\Datasource\ConnectionManager;

/**
 * Fixture state strategy that truncates tables before a test.
 *
 * This strategy is slower than the TransactionStrategy, but
 * allows tests to reset state when applications require operations
 * that cannot be executed in a transaction. An example
 * of this is DDL operations in MySQL which auto-commit any open
 * transactions.
 *
 * This mode also offers 'backwards compatible' behavior
 * with the schema + data fixture system.
 */
class TruncationStrategy implements StateResetStrategyInterface
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
            if ($connection instanceof Connection && $enableLogging) {
                $connection->enableQueryLogging();
            }
        }
    }

    /**
     * Before each test start a transaction.
     *
     * @param string $test The test class::method that was completed.
     * @return void
     */
    public function beforeTest(string $test): void
    {
        $connections = ConnectionManager::configured();
        foreach ($connections as $connection) {
            if (strpos($connection, 'test') !== 0) {
                continue;
            }
            $db = ConnectionManager::get($connection);
            if (!($db instanceof Connection)) {
                continue;
            }

            $db->disableConstraints(function (Connection $db): void {
                $schema = $db->getSchemaCollection();
                foreach ($schema->listTables() as $table) {
                    $tableSchema = $schema->describe($table);
                    if (!($tableSchema instanceof SqlGeneratorInterface)) {
                        continue;
                    }
                    $sql = $tableSchema->truncateSql($db);
                    foreach ($sql as $stmt) {
                        $db->execute($stmt)->closeCursor();
                    }
                }
            });
        }
    }

    /**
     * No op.
     *
     * Implemented to satisfy the interface.
     *
     * @param string $test The test class::method that was completed.
     * @return void
     */
    public function afterTest(string $test): void
    {
        // Do nothing
    }
}
