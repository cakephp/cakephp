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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Cake\Database\Log\LoggedQuery;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerInterface;

class Statement
{
    use TypeConverterTrait {
        cast as protected;
        matchTypes as protected;
    }

    protected DriverInterface $driver;

    protected PDOStatement $statement;

    protected ?FieldTypeConverter $typeConverter;

    protected ?LoggerInterface $logger;

    /**
     * @param \Cake\Database\DriverInterface $driver Database driver
     * @param \PDOStatement $statement PDO statement
     * @param \Cake\Database\TypeMap|null $typeMap Results type map
     * @param \Psr\Log\LoggerInterface|null $logger Query logger
     */
    public function __construct(
        DriverInterface $driver,
        PDOStatement $statement,
        ?TypeMap $typeMap = null,
        ?LoggerInterface $logger = null
    ) {
        $this->driver = $driver;
        $this->statement = $statement;
        $this->typeConverter = $typeMap !== null ? new FieldTypeConverter($typeMap, $driver) : null;
        $this->logger = $logger;
    }

    /**
     * Binds a set of values to statement object with corresponding type.
     *
     * @param array $params list of values to be bound
     * @param array $types list of types to be used, keys should match those in $params
     * @return void
     */
    public function bind(array $params, array $types): void
    {
        if (empty($params)) {
            return;
        }

        $anonymousParams = is_int(key($params));
        $offset = 1;
        foreach ($params as $index => $value) {
            $type = $types[$index] ?? null;
            if ($anonymousParams) {
                /** @psalm-suppress InvalidOperand */
                $index += $offset;
            }
            /** @psalm-suppress PossiblyInvalidArgument */
            $this->bindValue($index, $value, $type);
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
     * @param string|int|null $type name of configured Type class
     * @return void
     */
    public function bindValue(string|int $column, mixed $value, string|int|null $type = 'string'): void
    {
        if ($type === null) {
            $type = 'string';
        }
        if (!is_int($type)) {
            [$value, $type] = $this->cast($value, $type);
        }
        $this->performBind($column, $value, $type);
    }

    /**
     * Perform bind to statement.
     *
     * @param string|int $column name or param position to be bound
     * @param mixed $value The value to bind to variable in query
     * @param int $type PDO::PARAM_* constant
     * @return void
     */
    protected function performBind(string|int $column, mixed $value, int $type): void
    {
        $this->statement->bindValue($column, $value, $type);
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
    public function execute(?array $params = null): bool
    {
        $loggedQuery = new LoggedQuery();
        $loggedQuery->driver = $this->driver;
        $loggedQuery->query = $this->statement->queryString;

        try {
            $start = microtime(true);
            $result = $this->statement->execute($params);
            $loggedQuery->took = (microtime(true) - $start) * 1000;
            $loggedQuery->numRows = $this->rowCount();
        } catch (PDOException $e) {
            $loggedQuery->error = $e;
        } finally {
        }

        if ($this->logger) {
            $this->logger->debug((string)$loggedQuery, ['query' => $loggedQuery]);
        }

        if ($loggedQuery->error) {
            throw $loggedQuery->error;
        }

        return $result;
    }

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * This behaves the same as `PDOStatement::closeCursor()`.
     *
     * @return void
     */
    public function closeCursor(): void
    {
        $this->statement->closeCursor();
    }

    /**
     * Fetches the next row from a result set using specified mode.
     *
     * This behaves the same as `PDOStatement::fetch()`.
     *
     * @param string|int $mode PDO::FETCH_* constant or fetch mode name.
     *   Valid names are 'assoc', 'num' or 'obj'.
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function fetch(string|int $mode = PDO::FETCH_NUM): mixed
    {
        $mode = $this->convertMode($mode);
        $row = $this->statement->fetch($mode);
        if ($row === false) {
            return false;
        }

        if ($this->typeConverter !== null && $mode === PDO::FETCH_ASSOC) {
            return $this->typeConverter($row);
        }

        return $row;
    }

    /**
     * Fetches the next row from a result set using PDO::FETCH_ASSOC.
     *
     * This behaves the same as `PDOStatement::fetch()`.
     *
     * @return array|false
     */
    public function fetchAssoc(): array|false
    {
        return $this->fetch('assoc');
    }

    /**
     * Fetches the next row from a result set using PDO::FETCH_NUM and returning
     * the column at specified position.
     *
     * @param int $position Column index in result row.
     * @return mixed
     */
    public function fetchColumn(int $position): mixed
    {
        $row = $this->fetch(PDO::FETCH_NUM);
        if ($row && isset($row[$position])) {
            return $row[$position];
        }

        return false;
    }

    /**
     * Fetches the remaining rows from a result set.
     *
     * This behaves the same as `PDOStatement::fetchAll()`.
     *
     * @param string|int $mode PDO::FETCH_* constant or fetch mode name.
     *   Valid names are 'assoc', 'num' or 'obj'.
     * @return array|false
     * @throws \InvalidArgumentException
     */
    public function fetchAll(string|int $mode = PDO::FETCH_NUM): array
    {
        $mode = $this->convertMode($mode);
        $rows = $this->statement->fetchAll($mode);

        if ($this->typeConverter !== null && $mode === PDO::FETCH_ASSOC) {
            return array_map($this->typeConverter, $rows);
        }

        return $rows;
    }

    /**
     * Converts mode name to PDO constant.
     *
     * @param string|int $mode Mode name or PDO constant
     * @return int
     * @throws \InvalidArgumentException
     */
    protected function convertMode(string|int $mode): int
    {
        if (is_int($mode)) {
            // We don't try to validate the PDO constants
            return $mode;
        }

        static $MODES = ['assoc' => PDO::FETCH_ASSOC, 'num' => PDO::FETCH_NUM, 'obj' => PDO::FETCH_OBJ];
        $mode = $MODES[$mode] ?? null;
        if ($mode !== null) {
            return $mode;
        }

        throw new InvalidArgumentException("Invalid fetch mode requested. Expected 'assoc', 'num' or 'obj'.");
    }

    /**
     * Returns the number of rows affected by the last SQL statement.
     *
     * This behaves the same as `PDOStatement::rowCount()`.
     *
     * @return int
     * @link https://www.php.net/manual/en/pdostatement.rowcount.php
     */
    public function rowCount(): int
    {
        if ($this->typeConverter !== null) {
            return 0;
        }

        return $this->statement->rowCount();
    }

    /**
     * Returns the number of columns in the result set.
     *
     * This behaves the same as `PDOStatement::columnCount()`.
     *
     * @return int
     * @link https://php.net/manual/en/pdostatement.columncount.php
     */
    public function columnCount(): int
    {
        return $this->statement->columnCount();
    }

    /**
     * Fetch the SQLSTATE associated with the last operation on the statement handle.
     *
     * This behaves the same as `PDOStatement::errorCode()`.
     *
     * @return string
     * @link https://www.php.net/manual/en/pdostatement.errorcode.php
     */
    public function errorCode(): string
    {
        return $this->statement->errorCode() ?: '';
    }

    /**
     * Fetch extended error information associated with the last operation on the statement handle.
     *
     * This behaves the same as `PDOStatement::errorInfo()`.
     *
     * @return array
     * @link https://www.php.net/manual/en/pdostatement.errorinfo.php
     */
    public function errorInfo(): array
    {
        return $this->statement->errorInfo();
    }
}
