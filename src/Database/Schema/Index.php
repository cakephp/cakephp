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
 * @copyright     Copyright (c) Cake Software Foundation, Inc.
 *                (https://github.com/cakephp/migrations/tree/master/LICENSE.txt)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Schema;

use RuntimeException;

/**
 * Index value object
 *
 * Models a database index and its attributes.
 */
class Index
{
    /**
     * @var string
     */
    public const INDEX = TableSchema::INDEX_INDEX;

    /**
     * @var string
     */
    public const FULLTEXT = TableSchema::INDEX_FULLTEXT;

    /**
     * @var ?array<string>
     */
    protected ?array $columns = null;

    /**
     * @var string
     */
    protected string $type = self::INDEX;

    /**
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * @var array|int|null
     */
    protected int|array|null $length = null;

    /**
     * @var ?array<string>
     */
    protected ?array $order = null;

    /**
     * @var ?array<string>
     */
    protected ?array $includedColumns = null;

    /**
     * @var bool
     */
    protected bool $concurrent = false;

    /**
     * @var string|null The where clause for partial indexes.
     */
    protected ?string $where = null;

    /**
     * Sets the index columns.
     *
     * @param array<string>|string $columns Columns
     * @return $this
     */
    public function setColumns(string|array $columns)
    {
        $this->columns = (array)$columns;

        return $this;
    }

    /**
     * Gets the index columns.
     *
     * @return ?array<string>
     */
    public function getColumns(): ?array
    {
        return $this->columns;
    }

    /**
     * Sets the index type.
     *
     * @param string $type Type
     * @return $this
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets the index type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets the index name.
     *
     * @param string $name Name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the index name.
     *
     * @return ?string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the index length.
     *
     * In MySQL indexes can have limit clauses to control the number of
     * characters indexed in text and char columns.
     *
     * @param array<string, int>|int $length length value or array of length value
     * @return $this
     */
    public function setLength(int|array $length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Gets the index length.
     *
     * Can be an array of column names and lengths under MySQL.
     *
     * @return array<string, int>|int|null
     */
    public function getLength(): array|int|null
    {
        return $this->length;
    }

    /**
     * Sets the index columns sort order.
     *
     * @param array<string> $order column name sort order key value pair
     * @return $this
     */
    public function setOrder(array $order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Gets the index columns sort order.
     *
     * @return ?array<string>
     */
    public function getOrder(): ?array
    {
        return $this->order;
    }

    /**
     * Sets the index included columns for a 'covering index'.
     *
     * In postgres and sqlserver, indexes can define additional non-key
     * columns to build 'covering indexes'. This feature allows you to
     * further optimize well-crafted queries that leverage specific
     * indexes by reading all data from the index.
     *
     * @param array<string> $includedColumns Columns
     * @return $this
     */
    public function setInclude(array $includedColumns)
    {
        $this->includedColumns = $includedColumns;

        return $this;
    }

    /**
     * Gets the index included columns.
     *
     * @return ?array<string>
     */
    public function getInclude(): ?array
    {
        return $this->includedColumns;
    }

    /**
     * Set the concurrent mode for an index
     *
     * In postgres, concurrent indexes don't take locks, but cannot be run within transactions.
     *
     * @param bool $value The concurrent mode for an index.
     * @return $this
     */
    public function setConcurrently(bool $value)
    {
        $this->concurrent = $value;

        return $this;
    }

    /**
     * Get the concurrent value for an index.
     *
     * @return bool
     */
    public function getConcurrently(): bool
    {
        return $this->concurrent;
    }

    /**
     * Set the where clause for partial indexes.
     *
     * @param ?string $where The where clause for partial indexes.
     * @return $this
     */
    public function setWhere(?string $where)
    {
        $this->where = $where;

        return $this;
    }

    /**
     * Get the where clause for partial indexes.
     *
     * @return ?string
     */
    public function getWhere(): ?string
    {
        return $this->where;
    }

    /**
     * Utility method that maps an array of index options to this object's methods.
     *
     * @param array<string, mixed> $attributes Attributes to set.
     * @throws \RuntimeException
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        // Valid Options
        $validOptions = ['columns', 'concurrently', 'type', 'name', 'length', 'order', 'include', 'where'];
        foreach ($attributes as $attr => $value) {
            if (!in_array($attr, $validOptions, true)) {
                throw new RuntimeException(sprintf('"%s" is not a valid index option.', $attr));
            }
            $method = 'set' . ucfirst($attr);
            $this->$method($value);
        }

        return $this;
    }
}
