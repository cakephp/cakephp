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
 * @since         5.x.x
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Psr\Log\LoggerAwareInterface;

/**
 * Interface for a database driver.
 *
 * Defines the methods that any database driver should implement.
 */
interface DriverInterface extends LoggerAwareInterface
{
    /**
     * Establishes a connection to the database server.
     *
     * @return void
     */
    public function connect(): void;

    /**
     * Disconnects from database server.
     *
     * @return void
     */
    public function disconnect(): void;

    /**
     * Returns whether php is able to use this driver for connecting to database.
     *
     * @return bool True if it is valid to use this driver.
     */
    public function enabled(): bool;

    /**
     * Prepares a sql statement to be executed.
     *
     * @param \Cake\Database\Query|string $query The query to turn into a prepared statement.
     * @return \Cake\Database\StatementInterface
     */
    public function prepare(Query|string $query): StatementInterface;

    /**
     * Starts a transaction.
     *
     * @return bool True on success, false otherwise.
     */
    public function beginTransaction(): bool;

    /**
     * Commits a transaction.
     *
     * @return bool True on success, false otherwise.
     */
    public function commitTransaction(): bool;

    /**
     * Rollbacks a transaction.
     *
     * @return bool True on success, false otherwise.
     */
    public function rollbackTransaction(): bool;

    /**
     * Get the SQL for disabling foreign keys.
     *
     * @return string
     */
    public function disableForeignKeySQL(): string;

    /**
     * Get the SQL for enabling foreign keys.
     *
     * @return string
     */
    public function enableForeignKeySQL(): string;

    /**
     * Returns whether the driver supports the feature.
     *
     * @param \Cake\Database\DriverFeatureEnum $feature Driver feature
     * @return bool
     */
    public function supports(DriverFeatureEnum $feature): bool;

    /**
     * Compiles a Query object into its SQL representation for this specific driver.
     *
     * @param \Cake\Database\Query $query The query to compile.
     * @param \Cake\Database\ValueBinder $binder The value binder to use.
     * @return string The compiled SQL.
     */
    public function compileQuery(Query $query, ValueBinder $binder): string;

    /**
     * Returns an instance of the correct QueryCompiler for this driver.
     *
     * @return \Cake\Database\QueryCompiler
     */
    public function newCompiler(): QueryCompiler;

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words
     *
     * @param string $identifier The identifier to quote.
     * @return string
     */
    public function quoteIdentifier(string $identifier): string;

    /**
     * Quotes a database value.
     *
     * @param string $value The value to quote.
     * @return string
     */
    public function quote(string $value): string;

    /**
     * Returns the name of the driver (e.g. Mysql, Postgres, Sqlite, Sqlserver)
     * This can be used for logging or conditional logic.
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get the configuration name for this driver.
     *
     * @return string
     */
    public function configName(): string;
}
