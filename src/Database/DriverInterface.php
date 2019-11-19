<?php
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

use Cake\Database\Query;

/**
 * Interface for database driver.
 *
 * @method $this disableAutoQuoting()
 */
interface DriverInterface
{
    /**
     * Establishes a connection to the database server.
     *
     * @return bool True on success, false on failure.
     */
    public function connect();

    /**
     * Disconnects from database server.
     *
     * @return void
     */
    public function disconnect();

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
    public function enabled();

    /**
     * Prepares a sql statement to be executed.
     *
     * @param string|\Cake\Database\Query $query The query to turn into a prepared statement.
     * @return \Cake\Database\StatementInterface
     */
    public function prepare($query);

    /**
     * Starts a transaction.
     *
     * @return bool True on success, false otherwise.
     */
    public function beginTransaction();

    /**
     * Commits a transaction.
     *
     * @return bool True on success, false otherwise.
     */
    public function commitTransaction();

    /**
     * Rollbacks a transaction.
     *
     * @return bool True on success, false otherwise.
     */
    public function rollbackTransaction();

    /**
     * Get the SQL for releasing a save point.
     *
     * @param string|int $name The table name.
     * @return string
     */
    public function releaseSavePointSQL($name);

    /**
     * Get the SQL for creating a save point.
     *
     * @param string|int $name The table name.
     * @return string
     */
    public function savePointSQL($name);

    /**
     * Get the SQL for rollingback a save point.
     *
     * @param string|int $name The table name.
     * @return string
     */
    public function rollbackSavePointSQL($name);

    /**
     * Get the SQL for disabling foreign keys.
     *
     * @return string
     */
    public function disableForeignKeySQL();

    /**
     * Get the SQL for enabling foreign keys.
     *
     * @return string
     */
    public function enableForeignKeySQL();

    /**
     * Returns whether the driver supports adding or dropping constraints
     * to already created tables.
     *
     * @return bool True if driver supports dynamic constraints.
     */
    public function supportsDynamicConstraints();

    /**
     * Returns whether this driver supports save points for nested transactions.
     *
     * @return bool True if save points are supported, false otherwise.
     */
    public function supportsSavePoints();

    /**
     * Returns a value in a safe representation to be used in a query string
     *
     * @param mixed $value The value to quote.
     * @param int $type Type to be used for determining kind of quoting to perform.
     * @return string
     */
    public function quote($value, $type);

    /**
     * Checks if the driver supports quoting.
     *
     * @return bool
     */
    public function supportsQuoting();

    /**
     * Returns a callable function that will be used to transform a passed Query object.
     * This function, in turn, will return an instance of a Query object that has been
     * transformed to accommodate any specificities of the SQL dialect in use.
     *
     * @param string $type The type of query to be transformed
     * (select, insert, update, delete).
     * @return callable
     */
    public function queryTranslator($type);

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
    public function schemaDialect();

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words.
     *
     * @param string $identifier The identifier expression to quote.
     * @return string
     */
    public function quoteIdentifier($identifier);

    /**
     * Escapes values for use in schema definitions.
     *
     * @param mixed $value The value to escape.
     * @return string String for use in schema definitions.
     */
    public function schemaValue($value);

    /**
     * Returns the schema name that's being used.
     *
     * @return string
     */
    public function schema();

    /**
     * Returns last id generated for a table or sequence in database.
     *
     * @param string|null $table table name or sequence to get last insert value from.
     * @param string|null $column the name of the column representing the primary key.
     * @return string|int
     */
    public function lastInsertId($table = null, $column = null);

    /**
     * Checks whether or not the driver is connected.
     *
     * @return bool
     */
    public function isConnected();

    /**
     * Sets whether or not this driver should automatically quote identifiers
     * in queries.
     *
     * @param bool $enable Whether to enable auto quoting
     * @return $this
     */
    public function enableAutoQuoting($enable = true);

    /**
     * Returns whether or not this driver should automatically quote identifiers
     * in queries.
     *
     * @return bool
     */
    public function isAutoQuotingEnabled();

    /**
     * Transforms the passed query to this Driver's dialect and returns an instance
     * of the transformed query and the full compiled SQL string.
     *
     * @param \Cake\Database\Query $query The query to compile.
     * @param \Cake\Database\ValueBinder $generator The value binder to use.
     * @return array containing 2 entries. The first entity is the transformed query
     * and the second one the compiled SQL.
     */
    public function compileQuery(Query $query, ValueBinder $generator);

    /**
     * Returns an instance of a QueryCompiler.
     *
     * @return \Cake\Database\QueryCompiler
     */
    public function newCompiler();
}
