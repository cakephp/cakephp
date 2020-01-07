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
namespace Cake\Database;

/**
 * Represents a database statement. Concrete implementations
 * can either use PDOStatement or a native driver
 *
 * @property-read string $queryString
 */
interface StatementInterface
{
    /**
     * Used to designate that numeric indexes be returned in a result when calling fetch methods
     *
     * @var string
     */
    public const FETCH_TYPE_NUM = 'num';

    /**
     * Used to designate that an associated array be returned in a result when calling fetch methods
     *
     * @var string
     */
    public const FETCH_TYPE_ASSOC = 'assoc';

    /**
     * Used to designate that a stdClass object be returned in a result when calling fetch methods
     *
     * @var string
     */
    public const FETCH_TYPE_OBJ = 'obj';

    /**
     * Assign a value to a positional or named variable in prepared query. If using
     * positional variables you need to start with index one, if using named params then
     * just use the name in any order.
     *
     * It is not allowed to combine positional and named variables in the same statement
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
     * @param string|int|null $type name of configured Type class, or PDO type constant.
     * @return void
     */
    public function bindValue($column, $value, $type = 'string'): void;

    /**
     * Closes a cursor in the database, freeing up any resources and memory
     * allocated to it. In most cases you don't need to call this method, as it is
     * automatically called after fetching all results from the result set.
     *
     * @return void
     */
    public function closeCursor(): void;

    /**
     * Returns the number of columns this statement's results will contain
     *
     * ### Example:
     *
     * ```
     *  $statement = $connection->prepare('SELECT id, title from articles');
     *  $statement->execute();
     *  echo $statement->columnCount(); // outputs 2
     * ```
     *
     * @return int
     */
    public function columnCount(): int;

    /**
     * Returns the error code for the last error that occurred when executing this statement
     *
     * @return int|string
     */
    public function errorCode();

    /**
     * Returns the error information for the last error that occurred when executing
     * this statement
     *
     * @return array
     */
    public function errorInfo(): array;

    /**
     * Executes the statement by sending the SQL query to the database. It can optionally
     * take an array or arguments to be bound to the query variables. Please note
     * that binding parameters from this method will not perform any custom type conversion
     * as it would normally happen when calling `bindValue`
     *
     * @param array|null $params list of values to be bound to query
     * @return bool true on success, false otherwise
     */
    public function execute(?array $params = null): bool;

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
     * @param string|int $type 'num' for positional columns, assoc for named columns, or PDO fetch mode constants.
     * @return mixed Result array containing columns and values or false if no results
     * are left
     */
    public function fetch($type = 'num');

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
     * @return array|false list of all results from database for this statement or false on failure.
     */
    public function fetchAll($type = 'num');

    /**
     * Returns the value of the result at position.
     *
     * @param int $position The numeric position of the column to retrieve in the result
     * @return mixed Returns the specific value of the column designated at $position
     */
    public function fetchColumn(int $position);

    /**
     * Returns the number of rows affected by this SQL statement
     *
     * ### Example:
     *
     * ```
     *  $statement = $connection->prepare('SELECT id, title from articles');
     *  $statement->execute();
     *  print_r($statement->rowCount()); // will show 1
     * ```
     *
     * @return int
     */
    public function rowCount(): int;

    /**
     * Statements can be passed as argument for count()
     * to return the number for affected rows from last execution
     *
     * @return int
     */
    public function count(): int;

    /**
     * Binds a set of values to statement object with corresponding type
     *
     * @param array $params list of values to be bound
     * @param array $types list of types to be used, keys should match those in $params
     * @return void
     */
    public function bind(array $params, array $types): void;

    /**
     * Returns the latest primary inserted using this statement
     *
     * @param string|null $table table name or sequence to get last insert value from
     * @param string|null $column the name of the column representing the primary key
     * @return string|int
     */
    public function lastInsertId(?string $table = null, ?string $column = null);
}
