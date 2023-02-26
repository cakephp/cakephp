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
namespace Cake\Database\Statement;

use Cake\Database\Driver;
use Cake\Database\FieldTypeConverter;
use Cake\Database\StatementInterface;
use Cake\Database\TypeFactory;
use Cake\Database\TypeInterface;
use Cake\Database\TypeMap;
use InvalidArgumentException;
use PDO;
use PDOStatement;

class Statement implements StatementInterface
{
    /**
     * @var array<string, int>
     */
    protected const MODE_NAME_MAP = [
        self::FETCH_TYPE_ASSOC => PDO::FETCH_ASSOC,
        self::FETCH_TYPE_NUM => PDO::FETCH_NUM,
        self::FETCH_TYPE_OBJ => PDO::FETCH_OBJ,
    ];

    /**
     * @var \Cake\Database\Driver
     */
    protected Driver $_driver;

    /**
     * @var \PDOStatement
     */
    protected PDOStatement $statement;

    /**
     * @var \Cake\Database\FieldTypeConverter|null
     */
    protected ?FieldTypeConverter $typeConverter;

    /**
     * Cached bound parameters used for logging
     *
     * @var array<mixed>
     */
    protected array $params = [];

    /**
     * @param \PDOStatement $statement PDO statement
     * @param \Cake\Database\Driver $driver Database driver
     * @param \Cake\Database\TypeMap|null $typeMap Results type map
     */
    public function __construct(
        PDOStatement $statement,
        Driver $driver,
        ?TypeMap $typeMap = null,
    ) {
        $this->_driver = $driver;
        $this->statement = $statement;
        $this->typeConverter = $typeMap !== null ? new FieldTypeConverter($typeMap, $driver) : null;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function bindValue(string|int $column, mixed $value, string|int|null $type = 'string'): void
    {
        $type ??= 'string';
        if (!is_int($type)) {
            [$value, $type] = $this->cast($value, $type);
        }

        $this->params[$column] = $value;
        $this->performBind($column, $value, $type);
    }

    /**
     * Converts a give value to a suitable database value based on type and
     * return relevant internal statement type.
     *
     * @param mixed $value The value to cast.
     * @param \Cake\Database\TypeInterface|string|int $type The type name or type instance to use.
     * @return array List containing converted value and internal type.
     * @psalm-return array{0:mixed, 1:int}
     */
    protected function cast(mixed $value, TypeInterface|string|int $type = 'string'): array
    {
        if (is_string($type)) {
            $type = TypeFactory::build($type);
        }
        if ($type instanceof TypeInterface) {
            $value = $type->toDatabase($value, $this->_driver);
            $type = $type->toStatement($value, $this->_driver);
        }

        return [$value, $type];
    }

    /**
     * @inheritDoc
     */
    public function getBoundParams(): array
    {
        return $this->params;
    }

    /**
     * @param string|int $column
     * @param mixed $value
     * @param int $type
     * @return void
     */
    protected function performBind(string|int $column, mixed $value, int $type): void
    {
        $this->statement->bindValue($column, $value, $type);
    }

    /**
     * @inheritDoc
     */
    public function execute(?array $params = null): bool
    {
        return $this->statement->execute($params);
    }

    /**
     * @inheritDoc
     */
    public function fetch(string|int $mode = PDO::FETCH_NUM): mixed
    {
        $mode = $this->convertMode($mode);
        $row = $this->statement->fetch($mode);
        if ($row === false) {
            return false;
        }

        if ($this->typeConverter !== null) {
            return ($this->typeConverter)($row);
        }

        return $row;
    }

    /**
     * @inheritDoc
     */
    public function fetchAssoc(): array
    {
        return $this->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function fetchAll(string|int $mode = PDO::FETCH_NUM): array
    {
        $mode = $this->convertMode($mode);
        $rows = $this->statement->fetchAll($mode);

        if ($this->typeConverter !== null) {
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

        return static::MODE_NAME_MAP[$mode]
            ??
            throw new InvalidArgumentException('Invalid fetch mode requested. Expected \'assoc\', \'num\' or \'obj\'.');
    }

    /**
     * @inheritDoc
     */
    public function closeCursor(): void
    {
        $this->statement->closeCursor();
    }

    /**
     * @inheritDoc
     */
    public function rowCount(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * @inheritDoc
     */
    public function columnCount(): int
    {
        return $this->statement->columnCount();
    }

    /**
     * @inheritDoc
     */
    public function errorCode(): string
    {
        return $this->statement->errorCode() ?: '';
    }

    /**
     * @inheritDoc
     */
    public function errorInfo(): array
    {
        return $this->statement->errorInfo();
    }

    /**
     * @inheritDoc
     */
    public function lastInsertId(?string $table = null, ?string $column = null): string|int
    {
        if ($column && $this->columnCount()) {
            $row = $this->fetch(static::FETCH_TYPE_ASSOC);

            if ($row && isset($row[$column])) {
                return $row[$column];
            }
        }

        return $this->_driver->lastInsertId($table);
    }

    /**
     * Returns prepared query string stored in PDOStatement.
     *
     * @return string
     */
    public function queryString(): string
    {
        return $this->statement->queryString;
    }
}
