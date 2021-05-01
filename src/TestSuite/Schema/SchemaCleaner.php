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
     * @var bool
     */
    protected $isDroppingEnabled = true;

    /**
     * SchemaCleaner constructor.
     *
     * @param \Cake\Console\ConsoleIo|null $io Outputs if provided.
     * @param bool $enableDropping Enable dropping (useful for testing purposes)
     */
    public function __construct(?ConsoleIo $io = null, ?bool $enableDropping = true)
    {
        $this->io = $io;
        $this->isDroppingEnabled = $enableDropping;
    }

    /**
     * Drop all tables of the provided connection.
     *
     * @param string $connectionName Name of the connection.
     * @param array|null $tables Tables to truncate (all if not set).
     * @return void
     * @throws \Exception if the dropping failed.
     */
    public function dropTables(string $connectionName, $tables = null): void
    {
        if ($this->isDroppingEnabled === true) {
            $this->handle($connectionName, 'dropTableSql', 'dropping', $tables);
        }
    }

    /**
     * Truncate all tables of the provided connection.
     *
     * @param string $connectionName Name of the connection.
     * @param array|null $tables Tables to truncate (all if not set).
     * @return void
     * @throws \Exception if the truncation failed.
     */
    public function truncateTables(string $connectionName, $tables = null): void
    {
        $this->handle($connectionName, 'truncateTableSql', 'truncating', $tables);
    }

    /**
     * @param string $connectionName Name of the connection.
     * @param string $dialectMethod Method applied to the SQL dialect.
     * @param string $action Action displayed in the info message
     * @param string[]|null $tables Tables to truncate (all if null)
     * @return void
     * @throws \Exception
     */
    protected function handle(string $connectionName, string $dialectMethod, string $action, $tables): void
    {
        $schema = $this->getSchema($connectionName);
        $dialect = $this->getDialect($connectionName);
        $allTables = $schema->listTables();
        if (is_null($tables)) {
            $tables = $allTables;
        } else {
            $tables = array_intersect($allTables, $tables);
        }

        $this->displayInfoMessage($connectionName, $tables, $action);

        if (empty($tables)) {
            return;
        }

        $stmts = [];
        foreach ($tables as $table) {
            $table = $schema->describe($table);
            if ($table instanceof TableSchema) {
                $stmts = array_merge($stmts, $dialect->{$dialectMethod}($table));
            }
        }

        $this->executeStatements(ConnectionManager::get($connectionName), $stmts);
    }

    /**
     * Display message in info.
     *
     * @param string $connectionName Name of the connection.
     * @param array $tables Table handled.
     * @param string $action Action performed.
     * @return void
     */
    protected function displayInfoMessage(string $connectionName, array $tables, string $action): void
    {
        $msg = [ucwords($action)];
        $msg[] = count($tables) . ' tables';
        $msg[] = 'for connection ' . $connectionName;
        $this->info(implode(' ', $msg));
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
