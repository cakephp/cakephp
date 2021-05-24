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
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\ConnectionManager;
use RuntimeException;

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
 * with the schema + data fixture system. Only tables that have
 * fixture data 'loaded' will be truncated.
 */
class TruncationStrategy implements StateResetStrategyInterface
{
    /**
     * A map of connections to the tables they contain.
     * Caching schema descriptions helps improve performance and
     * is required for SQLServer to reset sequences.
     *
     * @var array
     */
    protected $tables = [];

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
        $fixtures = FixtureLoader::getInstance();
        if (!$fixtures) {
            throw new RuntimeException('Cannot truncate tables without a FixtureLoader');
        }

        $connections = ConnectionManager::configured();
        foreach ($connections as $connection) {
            if (strpos($connection, 'test') !== 0) {
                continue;
            }
            $db = ConnectionManager::get($connection);
            if (!($db instanceof Connection)) {
                continue;
            }
            $lastInserted = $fixtures->lastInserted();
            if (empty($lastInserted)) {
                continue;
            }
            $schema = $db->getSchemaCollection();
            $tables = $schema->listTables();
            $tables = array_intersect($tables, $lastInserted);

            $db->disableConstraints(function (Connection $db) use ($tables): void {
                foreach ($tables as $table) {
                    $tableSchema = $this->getTableSchema($db, $table);
                    if ($tableSchema instanceof SqlGeneratorInterface) {
                        $sql = $tableSchema->truncateSql($db);
                        foreach ($sql as $stmt) {
                            $db->execute($stmt)->closeCursor();
                        }
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

    /**
     * Get the schema description for a table.
     *
     * Lazily populates with fixtures as they are used to reduce
     * the number of reflection queries we use.
     *
     * @param \Cake\Database\Connection $db The connection to use.
     * @param string $table The table to reflect.
     * @return \Cake\Database\Schema\TableSchemaInterface
     */
    protected function getTableSchema(Connection $db, string $table): TableSchemaInterface
    {
        $name = $db->configName();
        if (isset($this->tables[$name][$table])) {
            return $this->tables[$name][$table];
        }
        $schema = $db->getSchemaCollection();
        $tableSchema = $schema->describe($table);
        $this->tables[$name][$table] = $tableSchema;

        return $tableSchema;
    }
}
