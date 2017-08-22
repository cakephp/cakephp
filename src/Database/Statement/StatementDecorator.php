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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Statement;

use Cake\Database\StatementInterface;
use Cake\Database\TypeConverterTrait;
use Countable;
use IteratorAggregate;

/**
 * Represents a database statement. Statements contains queries that can be
 * executed multiple times by binding different values on each call. This class
 * also helps convert values to their valid representation for the corresponding
 * types.
 *
 * This class is but a decorator of an actual statement implementation, such as
 * PDOStatement.
 *
 * @property-read string $queryString
 */
class StatementDecorator implements StatementInterface, Countable, IteratorAggregate
{

    use TypeConverterTrait;

    /**
     * Statement instance implementation, such as PDOStatement
     * or any other custom implementation.
     *
     * @var \Cake\Database\StatementInterface|\PDOStatement
     */
    protected $_statement;

    /**
     * Reference to the driver object associated to this statement.
     *
     * @var \Cake\Database\Driver
     */
    protected $_driver;

    /**
     * Whether or not this statement has already been executed
     *
     * @var bool
     */
    protected $_hasExecuted = false;

    /**
     * Constructor
     *
     * @param \Cake\Database\StatementInterface|\PDOStatement|null $statement Statement implementation such as PDOStatement
     * @param \Cake\Database\Driver|null $driver Driver instance
     */
    public function __construct($statement = null, $driver = null)
    {
        $this->_statement = $statement;
        $this->_driver = $driver;
    }

    /**
     * Magic getter to return $queryString as read-only.
     *
     * @param string $property internal property to get
     * @return mixed
     */
    public function __get($property)
    {
        if ($property === 'queryString') {
            return $this->_statement->queryString;
        }
    }

    /**
     * Assign a value to a positional or named variable in prepared query. If using
     * positional variables you need to start with index one, if using named params then
     * just use the name in any order.
     *
     * It is not allowed to combine positional and named variables in the same statement.
     *
     * ### Examples:
     *
     * ```
     * $statement->bindValue(1, 'a title');
     * $statement->bindValue('active', true, 'boolean');
     * $statement->bindValue(5, new \DateTime(), 'date');
     * ```
     *
     * @param string|int $column name or param position to be bound
     * @param mixed $value The value to bind to variable in query
     * @param string $type name of configured Type class
     * @return void
     */
    public function bindValue($column, $value, $type = 'string')
    {
        $this->_statement->bindValue($column, $value, $type);
    }

    /**
     * Closes a cursor in the database, freeing up any resources and memory
     * allocated to it. In most cases you don't need to call this method, as it is
     * automatically called after fetching all results from the result set.
     *
     * @return void
     */
    public function closeCursor()
    {
        $this->_statement->closeCursor();
    }

    /**
     * Returns the number of columns this statement's results will contain.
     *
     * ### Example:
     *
     * ```
     * $statement = $connection->prepare('SELECT id, title from articles');
     * $statement->execute();
     * echo $statement->columnCount(); // outputs 2
     * ```
     *
     * @return int
     */
    public function columnCount()
    {
        return $this->_statement->columnCount();
    }

    /**
     * Returns the error code for the last error that occurred when executing this statement.
     *
     * @return int|string
     */
    public function errorCode()
    {
        return $this->_statement->errorCode();
    }

    /**
     * Returns the error information for the last error that occurred when executing
     * this statement.
     *
     * @return array
     */
    public function errorInfo()
    {
        return $this->_statement->errorInfo();
    }

    /**
     * Executes the statement by sending the SQL query to the database. It can optionally
     * take an array or arguments to be bound to the query variables. Please note
     * that binding parameters from this method will not perform any custom type conversion
     * as it would normally happen when calling `bindValue`.
     *
     * @param array|null $params list of values to be bound to query
     * @return bool true on success, false otherwise
     */
    public function execute($params = null)
    {
        $this->_hasExecuted = true;

        return $this->_statement->execute($params);
    }

    /**
     * Returns the next row for the result set after executing this statement.
     * Rows can be fetched to contain columns as names or positions. If no
     * rows are left in result set, this method will return false.
     *
     * ### Example:
     *
     * ```
     * $statement = $connection->prepare('SELECT id, title from articles');
     * $statement->execute();
     * print_r($statement->fetch('assoc')); // will show ['id' => 1, 'title' => 'a title']
     * ```
     *
     * @param string $type 'num' for positional columns, assoc for named columns
     * @return array|false Result array containing columns and values or false if no results
     * are left
     */
    public function fetch($type = 'num')
    {
        return $this->_statement->fetch($type);
    }

    /**
     * Returns an array with all rows resulting from executing this statement.
     *
     * ### Example:
     *
     * ```
     * $statement = $connection->prepare('SELECT id, title from articles');
     * $statement->execute();
     * print_r($statement->fetchAll('assoc')); // will show [0 => ['id' => 1, 'title' => 'a title']]
     * ```
     *
     * @param string $type num for fetching columns as positional keys or assoc for column names as keys
     * @return array List of all results from database for this statement
     */
    public function fetchAll($type = 'num')
    {
        return $this->_statement->fetchAll($type);
    }

    /**
     * Returns the number of rows affected by this SQL statement.
     *
     * ### Example:
     *
     * ```
     * $statement = $connection->prepare('SELECT id, title from articles');
     * $statement->execute();
     * print_r($statement->rowCount()); // will show 1
     * ```
     *
     * @return int
     */
    public function rowCount()
    {
        return $this->_statement->rowCount();
    }

    /**
     * Statements are iterable as arrays, this method will return
     * the iterator object for traversing all items in the result.
     *
     * ### Example:
     *
     * ```
     * $statement = $connection->prepare('SELECT id, title from articles');
     * foreach ($statement as $row) {
     *   //do stuff
     * }
     * ```
     *
     * @return \Iterator
     */
    public function getIterator()
    {
        if (!$this->_hasExecuted) {
            $this->execute();
        }

        return $this->_statement;
    }

    /**
     * Statements can be passed as argument for count() to return the number
     * for affected rows from last execution.
     *
     * @return int
     */
    public function count()
    {
        return $this->rowCount();
    }

    /**
     * Binds a set of values to statement object with corresponding type.
     *
     * @param array $params list of values to be bound
     * @param array $types list of types to be used, keys should match those in $params
     * @return void
     */
    public function bind($params, $types)
    {
        if (empty($params)) {
            return;
        }

        $anonymousParams = is_int(key($params)) ? true : false;
        $offset = 1;
        foreach ($params as $index => $value) {
            $type = null;
            if (isset($types[$index])) {
                $type = $types[$index];
            }
            if ($anonymousParams) {
                $index += $offset;
            }
            $this->bindValue($index, $value, $type);
        }
    }

    /**
     * Returns the latest primary inserted using this statement.
     *
     * @param string|null $table table name or sequence to get last insert value from
     * @param string|null $column the name of the column representing the primary key
     * @return string
     */
    public function lastInsertId($table = null, $column = null)
    {
        $row = null;
        if ($column && $this->columnCount()) {
            $row = $this->fetch('assoc');
        }
        if (isset($row[$column])) {
            return $row[$column];
        }

        return $this->_driver->lastInsertId($table, $column);
    }

    /**
     * Returns the statement object that was decorated by this class.
     *
     * @return \Cake\Database\StatementInterface|\PDOStatement
     */
    public function getInnerStatement()
    {
        return $this->_statement;
    }
}
