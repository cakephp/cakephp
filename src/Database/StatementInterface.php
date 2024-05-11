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

use IteratorAggregate;
use PDO;

/**
 * @template-extends \IteratorAggregate<array>
 */
interface StatementInterface extends IteratorAggregate
{
    /**
     * Maps to PDO::FETCH_NUM.
     *
     * @var string
     * @link https://www.php.net/manual/en/pdo.constants.php
     */
    public const FETCH_TYPE_NUM = 'num';

    /**
     * Maps to PDO::FETCH_ASSOC.
     *
     * @var string
     * @link https://www.php.net/manual/en/pdo.constants.php
     */
    public const FETCH_TYPE_ASSOC = 'assoc';

    /**
     * Maps to PDO::FETCH_OBJ.
     *
     * @var string
     * @link https://www.php.net/manual/en/pdo.constants.php
     */
    public const FETCH_TYPE_OBJ = 'obj';

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
     * @param string|int|null $type name of configured Type class
     * @return void
     */
    public function bindValue(string|int $column, mixed $value, string|int|null $type = 'string'): void;

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * This behaves the same as `PDOStatement::closeCursor()`.
     *
     * @return void
     */
    public function closeCursor(): void;

    /**
     * Returns the number of columns in the result set.
     *
     * This behaves the same as `PDOStatement::columnCount()`.
     *
     * @return int
     * @link https://php.net/manual/en/pdostatement.columncount.php
     */
    public function columnCount(): int;

    /**
     * Fetch the SQLSTATE associated with the last operation on the statement handle.
     *
     * This behaves the same as `PDOStatement::errorCode()`.
     *
     * @return string
     * @link https://www.php.net/manual/en/pdostatement.errorcode.php
     */
    public function errorCode(): string;

    /**
     * Fetch extended error information associated with the last operation on the statement handle.
     *
     * This behaves the same as `PDOStatement::errorInfo()`.
     *
     * @return array
     * @link https://www.php.net/manual/en/pdostatement.errorinfo.php
     */
    public function errorInfo(): array;

    /**
     * Executes the statement by sending the SQL query to the database. It can optionally
     * take an array or arguments to be bound to the query variables. Please note
     * that binding parameters from this method will not perform any custom type conversion
     * as it would normally happen when calling `bindValue`.
     *
     * @param array|null $params list of values to be bound to query
     * @return bool true on success, false otherwise
     */
    public function execute(?array $params = null): bool;

    /**
     * Fetches the next row from a result set
     * and converts fields to types based on TypeMap.
     *
     * This behaves the same as `PDOStatement::fetch()`.
     *
     * @param string|int $mode PDO::FETCH_* constant or fetch mode name.
     *   Valid names are 'assoc', 'num' or 'obj'.
     * @return mixed
     * @throws \InvalidArgumentException
     * @link https://www.php.net/manual/en/pdo.constants.php
     */
    public function fetch(string|int $mode = PDO::FETCH_NUM): mixed;

    /**
     * Fetches the remaining rows from a result set
     * and converts fields to types based on TypeMap.
     *
     * This behaves the same as `PDOStatement::fetchAll()`.
     *
     * @param string|int $mode PDO::FETCH_* constant or fetch mode name.
     *   Valid names are 'assoc', 'num' or 'obj'.
     * @return array
     * @throws \InvalidArgumentException
     * @link https://www.php.net/manual/en/pdo.constants.php
     */
    public function fetchAll(string|int $mode = PDO::FETCH_NUM): array;

    /**
     * Fetches the next row from a result set using PDO::FETCH_NUM
     * and converts fields to types based on TypeMap.
     *
     * This behaves the same as `PDOStatement::fetch()` except only
     * a specific column from the row is returned.
     *
     * @param int $position Column index in result row.
     * @return mixed
     */
    public function fetchColumn(int $position): mixed;

    /**
     * Fetches the next row from a result set using PDO::FETCH_ASSOC
     * and converts fields to types based on TypeMap.
     *
     * This behaves the same as `PDOStatement::fetch()` except an
     * empty array is returned instead of false.
     *
     * @return array
     */
    public function fetchAssoc(): array;

    /**
     * Returns the number of rows affected by the last SQL statement.
     *
     * This behaves the same as `PDOStatement::rowCount()`.
     *
     * @return int
     * @link https://www.php.net/manual/en/pdostatement.rowcount.php
     */
    public function rowCount(): int;

    /**
     * Binds a set of values to statement object with corresponding type.
     *
     * @param array $params list of values to be bound
     * @param array $types list of types to be used, keys should match those in $params
     * @return void
     */
    public function bind(array $params, array $types): void;

    /**
     * Returns the latest primary inserted using this statement.
     *
     * @param string|null $table table name or sequence to get last insert value from
     * @param string|null $column the name of the column representing the primary key
     * @return string|int
     */
    public function lastInsertId(?string $table = null, ?string $column = null): string|int;

    /**
     * Returns prepared query string.
     *
     * @return string
     */
    public function queryString(): string;

    /**
     * Get the bound params.
     *
     * @return array
     */
    public function getBoundParams(): array;
}
