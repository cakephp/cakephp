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
namespace Cake\Database\Statement;

use PDO;
use PDOStatement as Statement;

/**
 * Decorator for \PDOStatement class mainly used for converting human readable
 * fetch modes into PDO constants.
 */
class PDOStatement extends StatementDecorator
{

    /**
     * Constructor
     *
     * @param \PDOStatement|null $statement Original statement to be decorated.
     * @param \Cake\Database\Driver|null $driver Driver instance.
     */
    public function __construct(Statement $statement = null, $driver = null)
    {
        parent::__construct($statement, $driver);
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
     * @param string|int $type PDO type or name of configured Type class
     * @return void
     */
    public function bindValue($column, $value, $type = 'string')
    {
        if ($type === null) {
            $type = 'string';
        }
        if (!ctype_digit($type)) {
            list($value, $type) = $this->cast($value, $type);
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
     * @param string $type 'num' for positional columns, assoc for named columns
     * @return array|false Result array containing columns and values or false if no results
     * are left
     */
    public function fetch($type = 'num')
    {
        if ($type === 'num') {
            return $this->_statement->fetch(PDO::FETCH_NUM);
        }
        if ($type === 'assoc') {
            return $this->_statement->fetch(PDO::FETCH_ASSOC);
        }
        if ($type === 'obj') {
            return $this->_statement->fetch(PDO::FETCH_OBJ);
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
     * @param string $type num for fetching columns as positional keys or assoc for column names as keys
     * @return array list of all results from database for this statement
     */
    public function fetchAll($type = 'num')
    {
        if ($type === 'num') {
            return $this->_statement->fetchAll(PDO::FETCH_NUM);
        }
        if ($type === 'assoc') {
            return $this->_statement->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($type === 'obj') {
            return $this->_statement->fetchAll(PDO::FETCH_OBJ);
        }

        return $this->_statement->fetchAll($type);
    }
}
