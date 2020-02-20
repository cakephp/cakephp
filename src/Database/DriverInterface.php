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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Cake\Database\Schema\BaseSchema;
use Cake\Database\Schema\TableSchema;
use Closure;

/**
 * Interface for database driver.
 *
 * @method int|null getMaxAliasLength() Returns the maximum alias length allowed.
 */
interface DriverInterface
{
    /**
     * Establishes a connection to the database server.
     *
     * @throws \Cake\Database\Exception\MissingConnectionException If database connection could not be established.
     * @return bool True on success, false on failure.
     */
    public function connect(): bool;

    /**
     * Disconnects from database server.
     *
     * @return void
     */
    public function disconnect(): void;

    /**
     * Returns correct connection resource or object that is internally used.
     *
     * @return object Connection object used internally.
     */
    public function getConnection();

    /**
     * Set the internal connection object.
     *
     * @param object $connection The connection instance.
     * @return $this
     */
    public function setConnection($connection);

    /**
     * Returns whether php is able to use this driver for connecting to database.
     *
     * @return bool True if it is valid to use this driver.
     */
    public function enabled(): bool;

    /**
     * Prepares a sql statement to be executed.
     *
     * @param string|\Cake\Database\Query $query The query to turn into a prepared statement.
     * @return \Cake\Database\StatementInterface
     */
    public function prepare($query): StatementInterface;

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
     * Get the SQL for releasing a save point.
     *
     * @param string|int $name The table name.
     * @return string
     */
    public function releaseSavePointSQL($name): string;

    /**
     * Get the SQL for creating a save point.
     *
     * @param string|int $name The table name.
     * @return string
     */
    public function savePointSQL($name): string;

    /**
     * Get the SQL for rollingback a save point.
     *
     * @param string|int $name The table name.
     * @return string
     */
    public function rollbackSavePointSQL($name): string;

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
     * Returns whether the driver supports adding or dropping constraints
     * to already created tables.
     *
     * @return bool True if driver supports dynamic constraints.
     */
    public function supportsDynamicConstraints(): bool;

    /**
     * Returns whether this driver supports save points for nested transactions.
     *
     * @return bool True if save points are supported, false otherwise.
     */
    public function supportsSavePoints(): bool;

    /**
     * Returns a value in a safe representation to be used in a query string
     *
     * @param mixed $value The value to quote.
     * @param int $type Type to be used for determining kind of quoting to perform.
     * @return string
     */
    public function quote($value, $type): string;

    /**
     * Checks if the driver supports quoting.
     *
     * @return bool
     */
    public function supportsQuoting(): bool;

    /**
     * Returns a callable function that will be used to transform a passed Query object.
     * This function, in turn, will return an instance of a Query object that has been
     * transformed to accommodate any specificities of the SQL dialect in use.
     *
     * @param string $type The type of query to be transformed
     * (select, insert, update, delete).
     * @return \Closure
     */
    public function queryTranslator(string $type): Closure;

    /**
     * Get the schema dialect.
     *
     * Used by Cake\Database\Schema package to reflect schema and
     * generate schema.
     *
     * If all the tables that use this Driver specify their
     * own schemas, then this may return null.
     *
     * @return \Cake\Database\Schema\BaseSchema
     */
    public function schemaDialect(): BaseSchema;

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words.
     *
     * @param string $identifier The identifier expression to quote.
     * @return string
     */
    public function quoteIdentifier(string $identifier): string;

    /**
     * Escapes values for use in schema definitions.
     *
     * @param mixed $value The value to escape.
     * @return string String for use in schema definitions.
     */
    public function schemaValue($value): string;

    /**
     * Returns the schema name that's being used.
     *
     * @return string
     */
    public function schema(): string;

    /**
     * Returns last id generated for a table or sequence in database.
     *
     * @param string|null $table table name or sequence to get last insert value from.
     * @param string|null $column the name of the column representing the primary key.
     * @return string|int
     */
    public function lastInsertId(?string $table = null, ?string $column = null);

    /**
     * Checks whether or not the driver is connected.
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Sets whether or not this driver should automatically quote identifiers
     * in queries.
     *
     * @param bool $enable Whether to enable auto quoting
     * @return $this
     */
    public function enableAutoQuoting(bool $enable = true);

    /**
     * Disable auto quoting of identifiers in queries.
     *
     * @return $this
     */
    public function disableAutoQuoting();

    /**
     * Returns whether or not this driver should automatically quote identifiers
     * in queries.
     *
     * @return bool
     */
    public function isAutoQuotingEnabled(): bool;

    /**
     * Transforms the passed query to this Driver's dialect and returns an instance
     * of the transformed query and the full compiled SQL string.
     *
     * @param \Cake\Database\Query $query The query to compile.
     * @param \Cake\Database\ValueBinder $generator The value binder to use.
     * @return array containing 2 entries. The first entity is the transformed query
     * and the second one the compiled SQL.
     */
    public function compileQuery(Query $query, ValueBinder $generator): array;

    /**
     * Returns an instance of a QueryCompiler.
     *
     * @return \Cake\Database\QueryCompiler
     */
    public function newCompiler(): QueryCompiler;

    /**
     * Constructs new TableSchema.
     *
     * @param string $table The table name.
     * @param array $columns The list of columns for the schema.
     * @return \Cake\Database\Schema\TableSchema
     */
    public function newTableSchema(string $table, array $columns = []): TableSchema;
}
