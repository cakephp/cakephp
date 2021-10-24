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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Statement;

use Cake\Core\Exception\CakeException;
use Cake\Database\DriverInterface;
use PDO;
use PDOStatement as Statement;

/**
 * Decorator for \PDOStatement class mainly used for converting human readable
 * fetch modes into PDO constants.
 */
class PDOStatement extends StatementDecorator
{
    /**
     * PDOStatement instance
     *
     * @var \PDOStatement
     */
    protected $_statement;

    /**
     * Constructor
     *
     * @param \PDOStatement $statement Original statement to be decorated.
     * @param \Cake\Database\DriverInterface $driver Driver instance.
     */
    public function __construct(Statement $statement, DriverInterface $driver)
    {
        $this->_statement = $statement;
        $this->_driver = $driver;
    }

    /**
     * Magic getter to return PDOStatement::$queryString as read-only.
     *
     * @param string $property internal property to get
     * @return string|null
     */
    public function __get(string $property)
    {
        if ($property === 'queryString' && isset($this->_statement->queryString)) {
            /** @psalm-suppress NoInterfaceProperties */
            return $this->_statement->queryString;
        }

        return null;
    }

    /**
     * Assign a value to a positional or named variable in prepared query. If using
     * positional variables you need to start with index one, if using named params then
     * just use the name in any order.
     *
     * You can pass PDO compatible constants for binding values with a type or optionally
     * any type name registered in the Type class. Any value will be converted to the valid type
     * representation if needed.
     *
     * It is not allowed to combine positional and named variables in the same statement
     *
     * ### Examples:
     *
     * ```
     * $statement->bindValue(1, 'a title');
     * $statement->bindValue(2, 5, PDO::INT);
     * $statement->bindValue('active', true, 'boolean');
     * $statement->bindValue(5, new \DateTime(), 'date');
     * ```
     *
     * @param string|int $column name or param position to be bound
     * @param mixed $value The value to bind to variable in query
     * @param string|int|null $type PDO type or name of configured Type class
     * @return void
     */
    public function bindValue($column, $value, $type = 'string'): void
    {
        if ($type === null) {
            $type = 'string';
        }
        if (!is_int($type)) {
            [$value, $type] = $this->cast($value, $type);
        }
        $this->_statement->bindValue($column, $value, $type);
    }

    /**
     * Returns the next row for the result set after executing this statement.
     * Rows can be fetched to contain columns as names or positions. If no
     * rows are left in result set, this method will return false
     *
     * ### Example:
     *
     * ```
     *  $statement = $connection->prepare('SELECT id, title from articles');
     *  $statement->execute();
     *  print_r($statement->fetch('assoc')); // will show ['id' => 1, 'title' => 'a title']
     * ```
     *
     * @param string|int $type 'num' for positional columns, assoc for named columns
     * @return mixed Result array containing columns and values or false if no results
     * are left
     */
    public function fetch($type = parent::FETCH_TYPE_NUM)
    {
        if ($type === static::FETCH_TYPE_NUM) {
            return $this->_statement->fetch(PDO::FETCH_NUM);
        }
        if ($type === static::FETCH_TYPE_ASSOC) {
            return $this->_statement->fetch(PDO::FETCH_ASSOC);
        }
        if ($type === static::FETCH_TYPE_OBJ) {
            return $this->_statement->fetch(PDO::FETCH_OBJ);
        }

        if (!is_int($type)) {
            throw new CakeException(sprintf(
                'Fetch type for PDOStatement must be an integer, found `%s` instead',
                getTypeName($type)
            ));
        }

        return $this->_statement->fetch($type);
    }

    /**
     * Returns an array with all rows resulting from executing this statement
     *
     * ### Example:
     *
     * ```
     *  $statement = $connection->prepare('SELECT id, title from articles');
     *  $statement->execute();
     *  print_r($statement->fetchAll('assoc')); // will show [0 => ['id' => 1, 'title' => 'a title']]
     * ```
     *
     * @param string|int $type num for fetching columns as positional keys or assoc for column names as keys
     * @return array|false list of all results from database for this statement, false on failure
     * @psalm-assert string $type
     */
    public function fetchAll($type = parent::FETCH_TYPE_NUM)
    {
        if ($type === static::FETCH_TYPE_NUM) {
            return $this->_statement->fetchAll(PDO::FETCH_NUM);
        }
        if ($type === static::FETCH_TYPE_ASSOC) {
            return $this->_statement->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($type === static::FETCH_TYPE_OBJ) {
            return $this->_statement->fetchAll(PDO::FETCH_OBJ);
        }

        if (!is_int($type)) {
            throw new CakeException(sprintf(
                'Fetch type for PDOStatement must be an integer, found `%s` instead',
                getTypeName($type)
            ));
        }

        return $this->_statement->fetchAll($type);
    }
}
