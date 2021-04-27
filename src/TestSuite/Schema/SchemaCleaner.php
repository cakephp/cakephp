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
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;

/**
 * This class will help dropping and truncating all tables of a given connection
 */
class SchemaCleaner
{
    /**
     * @var \Cake\Console\ConsoleIo|null
     */
    protected $io;

    /**
     * SchemaCleaner constructor.
     *
     * @param \Cake\Console\ConsoleIo|null $io Outputs if provided.
     */
    public function __construct(?ConsoleIo $io = null)
    {
        $this->io = $io;
    }

    /**
     * Drop all tables of the provided connection.
     *
     * @param string $connectionName Name of the connection.
     * @return void
     * @throws \Exception if the dropping failed.
     */
    public function dropTables(string $connectionName)
    {
        $this->info('Dropping all tables for connection ' . $connectionName);

        $schema = $this->getSchema($connectionName);
        $dialect = $this->getDialect($connectionName);

        $stmts = [];
        foreach ($schema->listTables() as $table) {
            $table = $schema->describe($table);
            if ($table instanceof TableSchema) {
                $stmts = array_merge($stmts, $dialect->dropTableSql($table));
            }
        }

        $this->executeStatements(ConnectionManager::get($connectionName), $stmts);
    }

    /**
     * Truncate all tables of the provided connection.
     *
     * @param string $connectionName Name of the connection.
     * @return void
     * @throws \Exception if the truncation failed.
     */
    public function truncate(string $connectionName)
    {
        $this->info('Truncating all tables for connection ' . $connectionName);

        $stmts = [];
        $schema = static::getSchema($connectionName);
        $dialect = static::getDialect($connectionName);
        $tables = $schema->listTables();
        foreach ($tables as $table) {
            $table = $schema->describe($table);
            if ($table instanceof TableSchema) {
                $stmts = array_merge($stmts, $dialect->truncateTableSql($table));
            }
        }

        $this->executeStatements(ConnectionManager::get($connectionName), $stmts);
    }

    /**
     * @param  \Cake\Datasource\ConnectionInterface $connection Connection.
     * @param  array               $commands Sql commands to run
     * @return void
     * @throws \Exception
     */
    protected function executeStatements(ConnectionInterface $connection, array $commands): void
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
     * @param string $msg Message to display.
     * @return void
     */
    protected function info(string $msg): void
    {
        if ($this->io instanceof ConsoleIo) {
            $this->io->info($msg);
        }
    }

    /**
     * @param  string $connectionName name of the connection.
     * @return \Cake\Database\Schema\CollectionInterface
     */
    protected function getSchema(string $connectionName): CollectionInterface
    {
        return ConnectionManager::get($connectionName)->getSchemaCollection();
    }

    /**
     * @param  string $connectionName Name of the connection.
     * @return \Cake\Database\Schema\BaseSchema
     */
    protected function getDialect(string $connectionName): BaseSchema
    {
        return ConnectionManager::get($connectionName)->getDriver()->schemaDialect();
    }
}
