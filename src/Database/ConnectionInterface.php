<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Datasource\ConnectionInterface as DatasourceConnectionInterface;

/**
 * This interface defines the methods you can depend on in
 * a database connection.
 */
interface ConnectionInterface extends DatasourceConnectionInterface
{
    /**
     * Sets the driver instance. If a string is passed it will be treated
     * as a class name and will be instantiated.
     *
     * If no params are passed it will return the current driver instance.
     *
     * @param \Cake\Database\Driver|string|null $driver The driver instance to use.
     * @param array $config Either config for a new driver or `null`.
     * @throws \Cake\Database\Exception\MissingDriverException When a driver class is missing.
     * @throws \Cake\Database\Exception\MissingExtensionException When a driver's PHP extension is missing.
     * @return \Cake\Database\Driver
     */
    public function driver($driver = null, $config = []);

    /**
     * Prepares a SQL statement to be executed.
     *
     * @param string|\Cake\Database\Query $sql The SQL to convert into a prepared statement.
     * @return \Cake\Database\StatementInterface
     */
    public function prepare($sql);

    /**
     * Executes a query using $params for interpolating values and $types as a hint for each
     * those params.
     *
     * @param string $query SQL to be executed and interpolated with $params
     * @param array $params List or associative array of params to be interpolated in `$query` as values
     * @param array $types List or associative array of types to be used for casting values in query
     * @return \Cake\Database\StatementInterface executed statement
     */
    public function execute($query, array $params = [], array $types = []);

    /**
     * Compiles a Query object into a SQL string according to the dialect for this
     * connection's driver
     *
     * @param \Cake\Database\Query $query The query to be compiled
     * @param \Cake\Database\ValueBinder $generator The placeholder generator to use
     * @return string
     */
    public function compileQuery(Query $query, ValueBinder $generator);

    /**
     * Executes the provided query after compiling it for the specific driver
     * dialect and returns the executed Statement object.
     *
     * @param \Cake\Database\Query $query The query to be executed
     * @return \Cake\Database\StatementInterface executed statement
     */
    public function run(Query $query);

    /**
     * Executes a SQL statement and returns the Statement object as result.
     *
     * @param string $sql The SQL query to execute.
     * @return \Cake\Database\StatementInterface
     */
    public function query($sql);

    /**
     * Create a new Query instance for this connection.
     *
     * @return \Cake\Database\Query
     */
    public function newQuery();

    /**
     * Gets or sets a Schema\Collection object for this connection.
     *
     * @param \Cake\Database\Schema\Collection|null $collection The schema collection object
     * @return \Cake\Database\Schema\Collection
     */
    public function schemaCollection(SchemaCollection $collection = null);

    /**
     * Returns whether this connection is using savepoints for nested transactions
     * If a boolean is passed as argument it will enable/disable the usage of savepoints
     * only if driver the allows it.
     *
     * If you are trying to enable this feature, make sure you check the return value of this
     * function to verify it was enabled successfully.
     *
     * @param bool|null $enable Whether or not save points should be used.
     * @return bool `true` if enabled, `false` otherwise
     */
    public function useSavePoints($enable = null);

    /**
     * Checks if a transaction is running.
     *
     * @return bool `true` if a transaction is running else `false`.
     */
    public function inTransaction();

    /**
     * Quotes value to be used safely in database query.
     *
     * @param mixed $value The value to quote.
     * @param string $type Type to be used for determining kind of quoting to perform
     * @return string Quoted value
     */
    public function quote($value, $type = null);

    /**
     * Checks if the driver supports quoting.
     *
     * @return bool
     */
    public function supportsQuoting();

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words.
     *
     * @param string $identifier The identifier to quote.
     * @return string
     */
    public function quoteIdentifier($identifier);

    /**
     * Enables or disables metadata caching for this connection
     *
     * Changing this setting will not modify existing schema collections objects.
     *
     * @param bool|string $cache Either boolean `false` to disable metadata caching,
     *   `true` to use `_cake_model_`, or the name of the cache config to use.
     * @return void
     */
    public function cacheMetadata($cache);
}
