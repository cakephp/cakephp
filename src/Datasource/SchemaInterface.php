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
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

/**
 * An interface used by TableSchema objects.
 */
interface SchemaInterface
{
    /**
     * Get the name of the table.
     *
     * @return string
     */
    public function name(): string;

    /**
     * Add a column to the table.
     *
     * ### Attributes
     *
     * Columns can have several attributes:
     *
     * - `type` The type of the column. This should be
     *   one of CakePHP's abstract types.
     * - `length` The length of the column.
     * - `precision` The number of decimal places to store
     *   for float and decimal types.
     * - `default` The default value of the column.
     * - `null` Whether the column can hold nulls.
     * - `fixed` Whether the column is a fixed length column.
     *   This is only present/valid with string columns.
     * - `unsigned` Whether the column is an unsigned column.
     *   This is only present/valid for integer, decimal, float columns.
     *
     * In addition to the above keys, the following keys are
     * implemented in some database dialects, but not all:
     *
     * - `comment` The comment for the column.
     *
     * @param string $name The name of the column
     * @param array<string, mixed>|string $attrs The attributes for the column or the type name.
     * @return $this
     */
    public function addColumn(string $name, array|string $attrs);

    /**
     * Get column data in the table.
     *
     * @param string $name The column name.
     * @return array<string, mixed>|null Column data or null.
     */
    public function getColumn(string $name): ?array;

    /**
     * Returns true if a column exists in the schema.
     *
     * @param string $name Column name.
     * @return bool
     */
    public function hasColumn(string $name): bool;

    /**
     * Remove a column from the table schema.
     *
     * If the column is not defined in the table, no error will be raised.
     *
     * @param string $name The name of the column
     * @return $this
     */
    public function removeColumn(string $name);

    /**
     * Get the column names in the table.
     *
     * @return array<string>
     * @psalm-return list<string>
     */
    public function columns(): array;

    /**
     * Returns column type or null if a column does not exist.
     *
     * @param string $name The column to get the type of.
     * @return string|null
     */
    public function getColumnType(string $name): ?string;

    /**
     * Sets the type of column.
     *
     * @param string $name The column to set the type of.
     * @param string $type The type to set the column to.
     * @return $this
     */
    public function setColumnType(string $name, string $type);

    /**
     * Returns the base type name for the provided column.
     * This represents the database type a more complex class is
     * based upon.
     *
     * @param string $column The column name to get the base type from
     * @return string|null The base type name
     */
    public function baseColumnType(string $column): ?string;

    /**
     * Check whether a field is nullable
     *
     * Missing columns are nullable.
     *
     * @param string $name The column to get the type of.
     * @return bool Whether the field is nullable.
     */
    public function isNullable(string $name): bool;

    /**
     * Returns an array where the keys are the column names in the schema
     * and the values the database type they have.
     *
     * @return array<string, string>
     */
    public function typeMap(): array;

    /**
     * Get a hash of columns and their default values.
     *
     * @return array<string, mixed>
     */
    public function defaultValues(): array;

    /**
     * Sets the options for a table.
     *
     * Table options allow you to set platform specific table level options.
     * For example the engine type in MySQL.
     *
     * @param array<string, mixed> $options The options to set, or null to read options.
     * @return $this
     */
    public function setOptions(array $options);

    /**
     * Gets the options for a table.
     *
     * Table options allow you to set platform specific table level options.
     * For example the engine type in MySQL.
     *
     * @return array<string, mixed> An array of options.
     */
    public function getOptions(): array;
}
