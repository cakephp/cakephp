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
class TruncationStrategy implements ResetStrategyInterface
{
    /**
     * A map of connections to the tables they contain.
     * Caching schema descriptions helps improve performance and
     * is required for SQLServer to reset sequences.
     *
     * @var array
     */
    protected static $tables = [];

    /**
     * @var \Cake\TestSuite\Fixture\FixtureLoader
     */
    protected $loader;

    /**
     * Constructor.
     *
     * @param \Cake\TestSuite\Fixture\FixtureLoader $loader The fixture loader being used.
     * @return void
     */
    public function __construct(FixtureLoader $loader)
    {
        $this->loader = $loader;
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
            if (!($db instanceof Connection)) {
                continue;
            }
            $inserted = $this->loader->getInserted();
            if (empty($inserted)) {
                continue;
            }
            $schema = $db->getSchemaCollection();
            $tables = $schema->listTables();
            $tables = array_intersect($tables, $inserted);

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
     * No op. Implemented because of interface.
     *
     * @return void
     */
    public function teardownTest(): void
    {
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
        if (isset(static::$tables[$name][$table])) {
            return static::$tables[$name][$table];
        }
        $schema = $db->getSchemaCollection();
        $tableSchema = $schema->describe($table);
        static::$tables[$name][$table] = $tableSchema;

        return $tableSchema;
    }
}
