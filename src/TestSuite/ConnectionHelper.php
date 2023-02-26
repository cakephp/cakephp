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
use Cake\Database\DriverFeatureEnum;
use Cake\Database\Log\QueryLogger;
use Cake\Datasource\ConnectionManager;
use Closure;

/**
 * Helper for managing test connections
 *
 * @internal
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

            if (str_starts_with($connection, 'test_')) {
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
     * @param array<int, string>|null $connections Connection names or null for all.
     * @return void
     */
    public function enableQueryLogging(?array $connections = null): void
    {
        $connections ??= ConnectionManager::configured();
        foreach ($connections as $connection) {
            $connection = ConnectionManager::get($connection);
            $message = '--Starting test run ' . date('Y-m-d H:i:s');
            if (
                $connection instanceof Connection &&
                $connection->getDriver()->log($message) === false
            ) {
                $connection->getDriver()->setLogger(new QueryLogger());
                $connection->getDriver()->log($message);
            }
        }
    }

    /**
     * Drops all tables.
     *
     * @param string $connectionName Connection name
     * @param array<string>|null $tables List of tables names or null for all.
     * @return void
     */
    public function dropTables(string $connectionName, ?array $tables = null): void
    {
        $connection = ConnectionManager::get($connectionName);
        assert($connection instanceof Connection);
        $collection = $connection->getSchemaCollection();
        $allTables = $collection->listTablesWithoutViews();

        $tables = $tables !== null ? array_intersect($tables, $allTables) : $allTables;
        /** @var array<\Cake\Database\Schema\TableSchema> $schemas Specify type for psalm */
        $schemas = array_map(fn ($table) => $collection->describe($table), $tables);

        $dialect = $connection->getDriver()->schemaDialect();
        foreach ($schemas as $schema) {
            foreach ($dialect->dropConstraintSql($schema) as $statement) {
                $connection->execute($statement);
            }
        }
        foreach ($schemas as $schema) {
            foreach ($dialect->dropTableSql($schema) as $statement) {
                $connection->execute($statement);
            }
        }
    }

    /**
     * Truncates all tables.
     *
     * @param string $connectionName Connection name
     * @param array<string>|null $tables List of tables names or null for all.
     * @return void
     */
    public function truncateTables(string $connectionName, ?array $tables = null): void
    {
        $connection = ConnectionManager::get($connectionName);
        assert($connection instanceof Connection);
        $collection = $connection->getSchemaCollection();

        $allTables = $collection->listTablesWithoutViews();
        $tables = $tables !== null ? array_intersect($tables, $allTables) : $allTables;
        /** @var array<\Cake\Database\Schema\TableSchema> $schemas Specify type for psalm */
        $schemas = array_map(fn ($table) => $collection->describe($table), $tables);

        $this->runWithoutConstraints($connection, function (Connection $connection) use ($schemas): void {
            $dialect = $connection->getDriver()->schemaDialect();
            foreach ($schemas as $schema) {
                foreach ($dialect->truncateTableSql($schema) as $statement) {
                    $connection->execute($statement);
                }
            }
        });
    }

    /**
     * Runs callback with constraints disabled correctly per-database
     *
     * @param \Cake\Database\Connection $connection Database connection
     * @param \Closure $callback callback
     * @return void
     */
    public function runWithoutConstraints(Connection $connection, Closure $callback): void
    {
        if ($connection->getDriver()->supports(DriverFeatureEnum::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION)) {
            $connection->disableConstraints(fn (Connection $connection) => $callback($connection));
        } else {
            $connection->transactional(function (Connection $connection) use ($callback): void {
                $connection->disableConstraints(fn (Connection $connection) => $callback($connection));
            });
        }
    }
}
