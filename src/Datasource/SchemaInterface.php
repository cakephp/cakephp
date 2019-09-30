<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
    public function name();

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
     * - `null` Whether or not the column can hold nulls.
     * - `fixed` Whether or not the column is a fixed length column.
     *   This is only present/valid with string columns.
     * - `unsigned` Whether or not the column is an unsigned column.
     *   This is only present/valid for integer, decimal, float columns.
     *
     * In addition to the above keys, the following keys are
     * implemented in some database dialects, but not all:
     *
     * - `comment` The comment for the column.
     *
     * @param string $name The name of the column
     * @param array|string $attrs The attributes for the column.
     * @return $this
     */
    public function addColumn($name, $attrs);

    /**
     * Get column data in the table.
     *
     * @param string $name The column name.
     * @return array|null Column data or null.
     */
    public function getColumn($name);

    /**
     * Returns true if a column exists in the schema.
     *
     * @param string $name Column name.
     * @return bool
     */
    public function hasColumn($name);

    /**
     * Remove a column from the table schema.
     *
     * If the column is not defined in the table, no error will be raised.
     *
     * @param string $name The name of the column
     * @return $this
     */
    public function removeColumn($name);

    /**
     * Get the column names in the table.
     *
     * @return string[]
     */
    public function columns();

    /**
     * Returns column type or null if a column does not exist.
     *
     * @param string $name The column to get the type of.
     * @return string|null
     */
    public function getColumnType($name);

    /**
     * Sets the type of a column.
     *
     * @param string $name The column to set the type of.
     * @param string $type The type to set the column to.
     * @return $this
     */
    public function setColumnType($name, $type);

    /**
     * Returns the base type name for the provided column.
     * This represent the database type a more complex class is
     * based upon.
     *
     * @param string $column The column name to get the base type from
     * @return string|null The base type name
     */
    public function baseColumnType($column);

    /**
     * Check whether or not a field is nullable
     *
     * Missing columns are nullable.
     *
     * @param string $name The column to get the type of.
     * @return bool Whether or not the field is nullable.
     */
    public function isNullable($name);

    /**
     * Returns an array where the keys are the column names in the schema
     * and the values the database type they have.
     *
     * @return array
     */
    public function typeMap();

    /**
     * Get a hash of columns and their default values.
     *
     * @return array
     */
    public function defaultValues();

    /**
     * Sets the options for a table.
     *
     * Table options allow you to set platform specific table level options.
     * For example the engine type in MySQL.
     *
     * @param array $options The options to set, or null to read options.
     * @return $this
     */
    public function setOptions($options);

    /**
     * Gets the options for a table.
     *
     * Table options allow you to set platform specific table level options.
     * For example the engine type in MySQL.
     *
     * @return array An array of options.
     */
    public function getOptions();
}
