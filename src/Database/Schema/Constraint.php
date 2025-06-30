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
 * Constraint base class object
 *
 * Models a database constraint like a unique key.
 */
class Constraint
{
    /**
     * @var string
     */
    public const PRIMARY = TableSchema::CONSTRAINT_PRIMARY;

    /**
     * @var string
     */
    public const UNIQUE = TableSchema::CONSTRAINT_UNIQUE;

    /**
     * @var string
     */
    public const FOREIGN = TableSchema::CONSTRAINT_FOREIGN;

    /**
     * Constructor
     *
     * @param string $name The name of the index.
     * @param array<string> $columns The columns to index.
     * @param string $type The type of constraint, e.g. 'unique', 'check'.
     */
    public function __construct(
        protected string $name,
        protected array $columns,
        protected string $type = self::UNIQUE,
    ) {
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
}
