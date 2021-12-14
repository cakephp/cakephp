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
use Closure;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Statement
{
    use TypeConverterTrait {
        cast as protected;
        matchTypes as protected;
    }

    protected const MODES = ['assoc' => PDO::FETCH_ASSOC, 'num' => PDO::FETCH_NUM, 'obj' => PDO::FETCH_OBJ];

    protected DriverInterface $driver;

    protected PDOStatement $statement;

    protected string $type;

    protected ?LoggerInterface $logger;

    protected ?LoggedQuery $query;

    protected array $modifiers = [];

    protected array $rows = [];

    protected bool $bufferRows = true;

    protected bool $rowsFetched = false;

    protected bool $executed = false;

    /**
     * @param \PDOStatement $statement PDO statement
     * @param string $type Query type: select, insert, update, delete
     * @param \Cake\Database\Log\LoggedQuery $query Query to log after execution
     */
    public function __construct(
        DriverInterface $driver,
        PDOStatement $statement,
        string $type,
        ?LoggerInterface $logger = null
    ) {
        $this->driver = $driver;
        $this->statement = $statement;
        $this->type = $type;
        $this->logger = $logger;
    }

    /**
     * Set whether fetch rows are buffered.
     *
     * Cannot be called after rows are fetched.
     *
     * @param bool $enable Whether or not to enable. Defaults to true.
     * @return $this
     */
    public function enableBuffering(bool $enable = true)
    {
        if (key($this->rows) !== null) {
            throw new RuntimeException('Cannot modify buffering after fetching rows.');
        }
        $this->bufferRows = $enable;

        return $this;
    }

    /**
     * Disables buffering fetched rows.
     *
     * Cannot be called after rows are fetched.
     *
     * @return $this
     */
    public function disableBuffering()
    {
        return $this->enableBuffering(false);
    }

    /**
     * Add result set modifier that's called for each row fetched.
     *
     * @param \Closure $modifier Modifier callback
     * @return $this;
     */
    public function addModifier(Closure $modifier)
    {
        if (key($this->rows) !== null) {
            throw new RuntimeException('Cannot add row modifier after fetching rows.');
        }
        $this->modifiers[] = $modifier;

        return $this;
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
        $this->rows = [];
        $this->rowsFetched = false;
        $this->executed = true;

        $result = $this->statement->execute($params);
        if ($result && $this->logger) {
            //$this->logger->debug();
        }

        return $result;
    }

    /**
     * Closes a cursor in the database, freeing up any resources and memory
     * allocated to it. In most cases you don't need to call this method, as it is
     * automatically called after fetching all results from the result set.
     *
     * @return void
     */
    public function closeCursor(): void
    {
        $this->statement->closeCursor();
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
     * @param int $mode Fetch mode. PDO::FETCH_* constant or name.
     * @return mixed
     */
    public function fetch(string|int $mode = 'num'): mixed
    {
        $mode = static::MODES[$mode] ?? $mode;
        if (is_string($mode)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid named fetch mode `%s` requested. Must be one of %s.',
                $mode,
                array_keys(static::MODES)
            ));
        }

        if ($this->bufferRows && $this->rowsFetched) {
            // Can we validate the fetch mode matches? It might be row-specific.
            $row = next($this->rows);
            if ($row === false) {
                return false;
            }

            foreach ($this->modifiers as $modifier) {
                $row = $modifier($row);
            }

            return $row;
        }

        $row = $this->statement->fetch($mode);
        if ($row === false) {
            $this->rowsFetched = true;

            return false;
        }

        if ($this->bufferRows) {
            $this->rows[] = $row;
            foreach ($this->modifiers as $modifier) {
                $row = $modifier($row);
            }
        }

        return $row;
    }

    /**
     * Returns the next row in a result set as an associative array. Calling this function is the same as calling
     * $statement->fetch('assoc'). If no results are found false is returned.
     *
     * @return array|false
     */
    public function fetchAssoc(): array|false
    {
        return $this->fetch('assoc');
    }

    /**
     * Returns the value of the result at position.
     *
     * @param int $position The numeric position of the column to retrieve in the result
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
     * Returns remaining rows.
     *
     * @param string|int $mode Fetch mode. PDO::FETCH_TYPE_* constant or name.
     * @return array|false
     */
    public function fetchAll(string|int $mode = PDO::FETCH_NUM): array
    {
        $mode = static::MODES[$mode] ?? $mode;
        if (is_string($mode)) {
            throw new InvalidArgumentException('Invalid named fetch mode `%s` requested.', $mode);
        }

        if ($this->bufferRows && $this->rowsFetched) {
            // Should validate the fetch mode matches.
            $rows = $this->rows;
            foreach ($this->modifiers as $modifier) {
                $rows = $modifier($rows);
            }

            return $rows;
        }

        $rows = $this->statement->fetchAll($mode);
        $this->rowsFetched = true;

        $this->rows = array_merge($this->rows, $rows);
        foreach ($this->modifiers as $modifier) {
            $rows = $modifier($rows);
        }

        return $rows;
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
    public function rowCount(): int
    {
        return $this->statement->rowCount();
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
    public function columnCount(): int
    {
        return $this->statement->columnCount();
    }

    /**
     * Returns the error code for the last error that occurred when executing this statement.
     *
     * @return string
     */
    public function errorCode(): string
    {
        return $this->statement->errorCode() ?: '';
    }

    /**
     * Returns the error information for the last error that occurred when executing
     * this statement.
     *
     * @return array
     */
    public function errorInfo(): array
    {
        return $this->statement->errorInfo();
    }
}
