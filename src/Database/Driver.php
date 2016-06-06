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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use InvalidArgumentException;
use PDO;

/**
 * Represents a database diver containing all specificities for
 * a database engine including its SQL dialect
 *
 */
abstract class Driver
{

    /**
     * Configuration data.
     *
     * @var array
     */
    protected $_config;

    /**
     * Base configuration that is merged into the user
     * supplied configuration data.
     *
     * @var array
     */
    protected $_baseConfig = [];

    /**
     * Indicates whether or not the driver is doing automatic identifier quoting
     * for all queries
     *
     * @var bool
     */
    protected $_autoQuoting = false;

    /**
     * Constructor
     *
     * @param array $config The configuration for the driver.
     * @throws \InvalidArgumentException
     */
    public function __construct($config = [])
    {
        if (empty($config['username']) && !empty($config['login'])) {
            throw new InvalidArgumentException(
                'Please pass "username" instead of "login" for connecting to the database'
            );
        }
        $config += $this->_baseConfig;
        $this->_config = $config;
        if (!empty($config['quoteIdentifiers'])) {
            $this->autoQuoting(true);
        }
    }

    /**
     * Establishes a connection to the database server
     *
     * @return bool true con success
     */
    abstract public function connect();

    /**
     * Disconnects from database server
     *
     * @return void
     */
    abstract public function disconnect();

    /**
     * Returns correct connection resource or object that is internally used
     * If first argument is passed,
     *
     * @param null|\PDO $connection The connection object
     * @return void
     */
    abstract public function connection($connection = null);

    /**
     * Returns whether php is able to use this driver for connecting to database
     *
     * @return bool true if it is valid to use this driver
     */
    abstract public function enabled();

    /**
     * Prepares a sql statement to be executed
     *
     * @param string|\Cake\Database\Query $query The query to convert into a statement.
     * @return \Cake\Database\StatementInterface
     */
    abstract public function prepare($query);

    /**
     * Starts a transaction
     *
     * @return bool true on success, false otherwise
     */
    abstract public function beginTransaction();

    /**
     * Commits a transaction
     *
     * @return bool true on success, false otherwise
     */
    abstract public function commitTransaction();

    /**
     * Rollsback a transaction
     *
     * @return bool true on success, false otherwise
     */
    abstract public function rollbackTransaction();

    /**
     * Get the SQL for releasing a save point.
     *
     * @param string $name The table name
     * @return string
     */
    abstract public function releaseSavePointSQL($name);

    /**
     * Get the SQL for creating a save point.
     *
     * @param string $name The table name
     * @return string
     */
    abstract public function savePointSQL($name);

    /**
     * Get the SQL for rollingback a save point.
     *
     * @param string $name The table name
     * @return string
     */
    abstract public function rollbackSavePointSQL($name);

    /**
     * Get the SQL for disabling foreign keys
     *
     * @return string
     */
    abstract public function disableForeignKeySQL();

    /**
     * Get the SQL for enabling foreign keys
     *
     * @return string
     */
    abstract public function enableForeignKeySQL();

    /**
     * Returns whether the driver supports adding or dropping constraints
     * to already created tables.
     *
     * @return bool true if driver supports dynamic constraints
     */
    abstract public function supportsDynamicConstraints();

    /**
     * Returns whether this driver supports save points for nested transactions
     *
     * @return bool true if save points are supported, false otherwise
     */
    public function supportsSavePoints()
    {
        return true;
    }

    /**
     * Returns a value in a safe representation to be used in a query string
     *
     * @param mixed $value The value to quote.
     * @param string $type Type to be used for determining kind of quoting to perform
     * @return string
     */
    abstract public function quote($value, $type);

    /**
     * Checks if the driver supports quoting
     *
     * @return bool
     */
    public function supportsQuoting()
    {
        return true;
    }

    /**
     * Returns a callable function that will be used to transform a passed Query object.
     * This function, in turn, will return an instance of a Query object that has been
     * transformed to accommodate any specificities of the SQL dialect in use.
     *
     * @param string $type the type of query to be transformed
     * (select, insert, update, delete)
     * @return callable
     */
    abstract public function queryTranslator($type);

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
    abstract public function schemaDialect();

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words
     *
     * @param string $identifier The identifier expression to quote.
     * @return string
     */
    abstract public function quoteIdentifier($identifier);

    /**
     * Escapes values for use in schema definitions.
     *
     * @param mixed $value The value to escape.
     * @return string String for use in schema definitions.
     */
    public function schemaValue($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        if ($value === false) {
            return 'FALSE';
        }
        if ($value === true) {
            return 'TRUE';
        }
        if (is_float($value)) {
            return str_replace(',', '.', strval($value));
        }
        if ((is_int($value) || $value === '0') || (
            is_numeric($value) && strpos($value, ',') === false &&
            $value[0] !== '0' && strpos($value, 'e') === false)
        ) {
            return $value;
        }
        return $this->_connection->quote($value, PDO::PARAM_STR);
    }

    /**
     * Returns last id generated for a table or sequence in database
     *
     * @param string|null $table table name or sequence to get last insert value from
     * @param string|null $column the name of the column representing the primary key
     * @return string|int
     */
    public function lastInsertId($table = null, $column = null)
    {
        return $this->_connection->lastInsertId($table, $column);
    }

    /**
     * Check whether or not the driver is connected.
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->_connection !== null;
    }

    /**
     * Returns whether or not this driver should automatically quote identifiers
     * in queries
     *
     * If called with a boolean argument, it will toggle the auto quoting setting
     * to the passed value
     *
     * @param bool|null $enable whether to enable auto quoting
     * @return bool
     */
    public function autoQuoting($enable = null)
    {
        if ($enable === null) {
            return $this->_autoQuoting;
        }
        return $this->_autoQuoting = (bool)$enable;
    }

    /**
     * Transforms the passed query to this Driver's dialect and returns an instance
     * of the transformed query and the full compiled SQL string
     *
     * @param \Cake\Database\Query $query The query to compile.
     * @param \Cake\Database\ValueBinder $generator The value binder to use.
     * @return array containing 2 entries. The first entity is the transformed query
     * and the second one the compiled SQL
     */
    public function compileQuery(Query $query, ValueBinder $generator)
    {
        $processor = $this->newCompiler();
        $translator = $this->queryTranslator($query->type());
        $query = $translator($query);
        return [$query, $processor->compile($query, $generator)];
    }

    /**
     * Returns an instance of a QueryCompiler
     *
     * @return \Cake\Database\QueryCompiler
     */
    public function newCompiler()
    {
        return new QueryCompiler;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->_connection = null;
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'connected' => $this->isConnected()
        ];
    }
}
