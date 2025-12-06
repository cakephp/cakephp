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

use InvalidArgumentException;

/**
 * ForeignKey metadata object
 *
 * Models a database foreign key constraint
 */
class ForeignKey extends Constraint
{
    public const CASCADE = 'cascade';
    public const RESTRICT = 'restrict';
    public const SET_NULL = 'setNull';
    public const NO_ACTION = 'noAction';
    public const SET_DEFAULT = 'setDefault';
    public const DEFERRED = 'DEFERRABLE INITIALLY DEFERRED';
    public const IMMEDIATE = 'DEFERRABLE INITIALLY IMMEDIATE';
    public const NOT_DEFERRED = 'NOT DEFERRABLE';

    /**
     * An allow list of valid actions
     *
     * @var array<string>
     */
    protected array $validActions = [
        self::CASCADE,
        self::RESTRICT,
        self::SET_NULL,
        self::NO_ACTION,
        self::SET_DEFAULT,
    ];

    /**
     * The action to take when the referenced row is deleted.
     */
    protected ?string $delete = null;

    /**
     * The action to take when the referenced row is updated.
     */
    protected ?string $update = null;

    /**
     * @var string|null
     */
    protected ?string $deferrable = null;

    /**
     * Constructor
     *
     * @param string $name The name of the index.
     * @param array<string> $columns The columns to index.
     * @param ?string $referencedTable The columns to index.
     * @param array<string> $referencedColumns The columns in $referencedTable that this key references.
     * @param ?string $delete The action to take when the referenced row is deleted.
     * @param ?string $update The action to take when the referenced row is updated.
     */
    public function __construct(
        protected string $name,
        protected array $columns,
        protected ?string $referencedTable = null,
        protected array $referencedColumns = [],
        ?string $delete = null,
        ?string $update = null,
        ?string $deferrable = null,
    ) {
        $this->type = self::FOREIGN;
        $this->delete = $this->normalizeAction($delete ?? self::NO_ACTION);
        $this->update = $this->normalizeAction($update ?? self::NO_ACTION);
        if ($deferrable) {
            $this->deferrable = $this->normalizeDeferrable($deferrable);
        }
    }

    /**
     * Sets the foreign key referenced table.
     *
     * @param string $table The table this KEY is pointing to
     * @return $this
     */
    public function setReferencedTable(string $table)
    {
        $this->referencedTable = $table;

        return $this;
    }

    /**
     * Gets the foreign key referenced table.
     *
     * @return ?string
     */
    public function getReferencedTable(): ?string
    {
        return $this->referencedTable;
    }

    /**
     * Sets the foreign key referenced columns.
     *
     * @param array<string>|string $referencedColumns Referenced columns
     * @return $this
     */
    public function setReferencedColumns(array|string $referencedColumns)
    {
        $referencedColumns = is_string($referencedColumns) ? [$referencedColumns] : $referencedColumns;
        $this->referencedColumns = $referencedColumns;

        return $this;
    }

    /**
     * Gets the foreign key referenced columns.
     *
     * @return array<string>
     */
    public function getReferencedColumns(): array
    {
        return $this->referencedColumns;
    }

    /**
     * Converts the foreign key to an array that is compatible
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
            'referencedTable' => $this->referencedTable,
            'referencedColumns' => $this->referencedColumns,
            'delete' => $this->delete,
            'update' => $this->update,
            'deferrable' => $this->deferrable,
        ];
    }

    /**
     * Sets ON DELETE action for the foreign key.
     *
     * @param string $delete On Delete
     * @return $this
     */
    public function setDelete(string $delete)
    {
        $this->delete = $this->normalizeAction($delete);

        return $this;
    }

    /**
     * Gets ON DELETE action for the foreign key.
     *
     * @return string|null
     */
    public function getDelete(): ?string
    {
        return $this->delete;
    }

    /**
     * Gets ON UPDATE action for the foreign key.
     *
     * @return string|null
     */
    public function getUpdate(): ?string
    {
        return $this->update;
    }

    /**
     * Sets ON UPDATE action for the foreign key.
     *
     * @param string $update On Update
     * @return $this
     */
    public function setUpdate(string $update)
    {
        $this->update = $this->normalizeAction($update);

        return $this;
    }

    /**
     * From passed value checks if it's correct and fixes if needed
     *
     * @param string $action Action
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function normalizeAction(string $action): string
    {
        if (in_array($action, $this->validActions, true)) {
            return $action;
        }
        throw new InvalidArgumentException('Unknown action passed: ' . $action);
    }

    /**
     * Sets deferrable mode for the foreign key.
     *
     * @param string $deferrable Constraint
     * @return $this
     */
    public function setDeferrable(string $deferrable)
    {
        $this->deferrable = $this->normalizeDeferrable($deferrable);

        return $this;
    }

    /**
     * Gets deferrable mode for the foreign key.
     */
    public function getDeferrable(): ?string
    {
        return $this->deferrable;
    }

    /**
     * From passed value checks if it's correct and fixes if needed
     *
     * @param string $deferrable Deferrable
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function normalizeDeferrable(string $deferrable): string
    {
        $mapping = [
            'DEFERRED' => ForeignKey::DEFERRED,
            'IMMEDIATE' => ForeignKey::IMMEDIATE,
            'NOT DEFERRED' => ForeignKey::NOT_DEFERRED,
            ForeignKey::DEFERRED => ForeignKey::DEFERRED,
            ForeignKey::IMMEDIATE => ForeignKey::IMMEDIATE,
            ForeignKey::NOT_DEFERRED => ForeignKey::NOT_DEFERRED,
        ];
        $normalized = strtoupper(str_replace('_', ' ', $deferrable));
        if (array_key_exists($normalized, $mapping)) {
            return $mapping[$normalized];
        }

        throw new InvalidArgumentException('Unknown deferrable passed: ' . $deferrable);
    }
}
