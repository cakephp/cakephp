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
use Iterator;

/**
 * A statement decorator that implements buffered results.
 *
 * This statement decorator will save fetched results in memory, allowing
 * the iterator to be rewound and reused.
 */
class BufferedStatement implements Iterator, StatementInterface
{
    use TypeConverterTrait;

    /**
     * If true, all rows were fetched
     *
     * @var bool
     */
    protected $_allFetched = false;

    /**
     * The decorated statement
     *
     * @var \Cake\Database\StatementInterface
     */
    protected $statement;

    /**
     * The driver for the statement
     *
     * @var \Cake\Database\DriverInterface
     */
    protected $_driver;

    /**
     * The in-memory cache containing results from previous iterators
     *
     * @var array
     */
    protected $buffer = [];

    /**
     * Whether or not this statement has already been executed
     *
     * @var bool
     */
    protected $_hasExecuted = false;

    /**
     * The current iterator index.
     *
     * @var int
     */
    protected $index = 0;

    /**
     * Constructor
     *
     * @param \Cake\Database\StatementInterface $statement Statement implementation such as PDOStatement
     * @param \Cake\Database\Driver $driver Driver instance
     */
    public function __construct(StatementInterface $statement, $driver)
    {
        $this->statement = $statement;
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
            return $this->statement->queryString;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function bindValue($column, $value, $type = 'string')
    {
        $this->statement->bindValue($column, $value, $type);
    }

    /**
     * {@inheritDoc}
     */
    public function closeCursor()
    {
        $this->statement->closeCursor();
    }

    /**
     * {@inheritDoc}
     */
    public function columnCount()
    {
        return $this->statement->columnCount();
    }

    /**
     * {@inheritDoc}
     */
    public function errorCode()
    {
        return $this->statement->errorCode();
    }

    /**
     * {@inheritDoc}
     */
    public function errorInfo()
    {
        return $this->statement->errorInfo();
    }

    /**
     * {@inheritDoc}
     */
    public function execute($params = null)
    {
        $this->_reset();
        $this->_hasExecuted = true;

        return $this->statement->execute($params);
    }

    /**
     * Returns the value of the result at position.
     *
     * @param int $position The numeric position of the column to retrieve in the result
     * @return mixed Returns the specific value of the column designated at $position
     */
    public function fetchColumn($position)
    {
        $result = $this->fetch(static::FETCH_TYPE_NUM);
        if (isset($result[$position])) {
            return $result[$position];
        }

        return false;
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
     * {@inheritDoc}
     */
    public function bind($params, $types)
    {
        $this->statement->bind($params, $types);
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($table = null, $column = null)
    {
        return $this->statement->lastInsertId($table, $column);
    }

    /**
     * {@inheritDoc}
     *
     * @param int|string $type The type to fetch.
     * @return array|false
     */
    public function fetch($type = self::FETCH_TYPE_NUM)
    {
        if ($this->_allFetched) {
            $row = false;
            if (isset($this->buffer[$this->index])) {
                $row = $this->buffer[$this->index];
            }
            $this->index += 1;

            if ($row && $type === static::FETCH_TYPE_NUM) {
                return array_values($row);
            }

            return $row;
        }

        $record = $this->statement->fetch($type);
        if ($record === false) {
            $this->_allFetched = true;
            $this->statement->closeCursor();

            return false;
        }
        $this->buffer[] = $record;

        return $record;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAssoc()
    {
        $result = $this->fetch(static::FETCH_TYPE_ASSOC);

        return $result ?: [];
    }

    /**
     * {@inheritDoc}
     *
     * @param string $type The type to fetch.
     * @return array
     */
    public function fetchAll($type = self::FETCH_TYPE_NUM)
    {
        if ($this->_allFetched) {
            return $this->buffer;
        }
        $results = $this->statement->fetchAll($type);
        if ($results !== false) {
            $this->buffer = array_merge($this->buffer, $results);
        }
        $this->_allFetched = true;
        $this->statement->closeCursor();

        return $this->buffer;
    }

    /**
     * {@inheritDoc}
     */
    public function rowCount()
    {
        if (!$this->_allFetched) {
            $this->fetchAll(static::FETCH_TYPE_ASSOC);
        }

        return count($this->buffer);
    }

    /**
     * Reset all properties
     *
     * @return void
     */
    protected function _reset()
    {
        $this->buffer = [];
        $this->_allFetched = false;
        $this->index = 0;
    }

    /**
     * Returns the current key in the iterator
     *
     * @return mixed
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * Returns the current record in the iterator
     *
     * @return mixed
     */
    public function current()
    {
        return $this->buffer[$this->index];
    }

    /**
     * Rewinds the collection
     *
     * @return void
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * Returns whether or not the iterator has more elements
     *
     * @return bool
     */
    public function valid()
    {
        $old = $this->index;
        $row = $this->fetch(self::FETCH_TYPE_ASSOC);

        // Restore the index as fetch() increments during
        // the cache scenario.
        $this->index = $old;

        return $row !== false;
    }

    /**
     * Advances the iterator pointer to the next element
     *
     * @return void
     */
    public function next()
    {
        $this->index += 1;
    }

    /**
     * Get the wrapped statement
     *
     * @return \Cake\Database\StatementInterface
     */
    public function getInnerStatement()
    {
        return $this->statement;
    }
}
