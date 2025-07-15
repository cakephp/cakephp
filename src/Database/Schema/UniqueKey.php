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

/**
 * UniqueKey class
 *
 * Models a unique key constraint, and provides methods to set driver specific attributes.
 */
class UniqueKey extends Constraint
{
    /**
     * Constructor
     *
     * @param string $name The name of the constraint.
     * @param array<string> $columns The columns to constraint.
     * @param array<string, int>|null $length The length of the columns, if applicable.
     */
    public function __construct(
        protected string $name,
        protected array $columns,
        protected ?array $length = null,
    ) {
        $this->type = self::UNIQUE;
    }

    /**
     * Sets the constraint columns.
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
     * Gets the constraint columns.
     *
     * @return ?array<string>
     */
    public function getColumns(): ?array
    {
        return $this->columns;
    }

    /**
     * Sets the constraint type.
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
     * Gets the constraint type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets the constraint name.
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
     * Gets the constraint name.
     *
     * @return ?string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the constraint length.
     *
     * In MySQL unique constraints can have limit clauses to control the number of
     * characters indexed in text and char columns.
     *
     * @param array<string, int> $length array of length values
     * @return $this
     */
    public function setLength(array $length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Gets the constraint length.
     *
     * Can be an array of column names and lengths under MySQL.
     *
     * @return array<string, int>|null
     */
    public function getLength(): ?array
    {
        return $this->length;
    }

    /**
     * Converts a constraint to an array that is compatible
     * with the constructor.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'columns' => $this->columns,
            'length' => $this->length,
        ];
    }
}
