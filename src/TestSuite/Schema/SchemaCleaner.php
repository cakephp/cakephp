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
namespace Cake\TestSuite\Schema;

use Cake\Console\ConsoleIo;
use Cake\Database\Schema\BaseSchema;
use Cake\Database\Schema\CollectionInterface;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestConnectionManager;

/**
 * This class will help dropping all tables of a given connection
 * and truncate the non phinx migrations tables.
 */
class SchemaCleaner
{
    /**
     * Drop all tables of the provided connection.
     *
     * @param string $connectionName Name of the connection.
     * @param \Cake\Console\ConsoleIo|null $io Console IO to output the processes.
     * @return void
     * @throws \Exception if the dropping failed.
     */
    public static function drop(string $connectionName, ?ConsoleIo $io = null)
    {
        self::info($io, __d('cake', 'Dropping all tables for connection ' . $connectionName));

        $schema = static::getSchema($connectionName);
        $dialect = static::getDialect($connectionName);

        $stmts = [];
        foreach ($schema->listTables() as $table) {
            $table = $schema->describe($table);
            $stmts = array_merge($stmts, $dialect->dropTableSql($table));
        }

        static::executeStatements(ConnectionManager::get($connectionName), $stmts);
    }

    /**
     * Truncate all tables of the provided connection.
     *
     * @param string $connectionName Name of the connection.
     * @param \Cake\Console\ConsoleIo|null $io Console IO to output the processes.
     * @return void
     * @throws \Exception if the truncation failed.
     */
    public static function truncate(string $connectionName, ?ConsoleIo $io = null)
    {
        static::info($io, __d('cake', 'Truncating all tables for connection ' . $connectionName));

        $stmts = [];
        $schema = static::getSchema($connectionName);
        $dialect = static::getDialect($connectionName);
        $tables = $schema->listTables();
        $tables = TestConnectionManager::unsetMigrationTables($tables);
        foreach ($tables as $table) {
            $table = $schema->describe($table);
            $stmts = array_merge($stmts, $dialect->truncateTableSql($table));
        }

        static::executeStatements(ConnectionManager::get($connectionName), $stmts);
    }

    /**
     * @param  \Cake\Datasource\ConnectionInterface $connection Connection.
     * @param  array               $commands Sql commands to run
     * @return void
     * @throws \Exception
     */
    private static function executeStatements(ConnectionInterface $connection, array $commands): void
    {
        $connection->disableConstraints(function ($connection) use ($commands) {
            $connection->transactional(function (ConnectionInterface $connection) use ($commands) {
                foreach ($commands as $sql) {
                    $connection->execute($sql);
                }
            });
        });
    }

    /**
     * @param \Cake\Console\ConsoleIo|null $io Console IO.
     * @param string         $msg Message to display.
     * @return void
     */
    private static function info($io, string $msg): void
    {
        if ($io instanceof ConsoleIo) {
            $io->info($msg);
        }
    }

    /**
     * @param  string $connectionName name of the connection.
     * @return \Cake\Database\Schema\CollectionInterface
     */
    private static function getSchema(string $connectionName): CollectionInterface
    {
        return ConnectionManager::get($connectionName)->getSchemaCollection();
    }

    /**
     * @param  string $connectionName Name of the connection.
     * @return \Cake\Database\Schema\BaseSchema
     */
    private static function getDialect(string $connectionName): BaseSchema
    {
        return ConnectionManager::get($connectionName)->getDriver()->schemaDialect();
    }
}
