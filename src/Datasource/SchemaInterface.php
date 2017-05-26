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
     * Binary column type
     *
     * @var string
     */
    const TYPE_BINARY = 'binary';

    /**
     * Date column type
     *
     * @var string
     */
    const TYPE_DATE = 'date';

    /**
     * Datetime column type
     *
     * @var string
     */
    const TYPE_DATETIME = 'datetime';

    /**
     * Time column type
     *
     * @var string
     */
    const TYPE_TIME = 'time';

    /**
     * Timestamp column type
     *
     * @var string
     */
    const TYPE_TIMESTAMP = 'timestamp';

    /**
     * JSON column type
     *
     * @var string
     */
    const TYPE_JSON = 'json';

    /**
     * String column type
     *
     * @var string
     */
    const TYPE_STRING = 'string';

    /**
     * Text column type
     *
     * @var string
     */
    const TYPE_TEXT = 'text';

    /**
     * Tiny Integer column type
     *
     * @var string
     */
    const TYPE_TINYINTEGER = 'tinyinteger';

    /**
     * Small Integer column type
     *
     * @var string
     */
    const TYPE_SMALLINTEGER = 'smallinteger';

    /**
     * Integer column type
     *
     * @var string
     */
    const TYPE_INTEGER = 'integer';

    /**
     * Big Integer column type
     *
     * @var string
     */
    const TYPE_BIGINTEGER = 'biginteger';

    /**
     * Float column type
     *
     * @var string
     */
    const TYPE_FLOAT = 'float';

    /**
     * Decimal column type
     *
     * @var string
     */
    const TYPE_DECIMAL = 'decimal';

    /**
     * Boolean column type
     *
     * @var string
     */
    const TYPE_BOOLEAN = 'boolean';

    /**
     * UUID column type
     *
     * @var string
     */
    const TYPE_UUID = 'uuid';

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
     * @param array $attrs The attributes for the column.
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
     * @return array
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
     * Add an index.
     *
     * Used to add indexes, and full text indexes in platforms that support
     * them.
     *
     * ### Attributes
     *
     * - `type` The type of index being added.
     * - `columns` The columns in the index.
     *
     * @param string $name The name of the index.
     * @param array $attrs The attributes for the index.
     * @return $this
     * @throws \Cake\Database\Exception
     */
    public function addIndex($name, $attrs);

    /**
     * Read information about an index based on name.
     *
     * @param string $name The name of the index.
     * @return array|null Array of index data, or null
     */
    public function getIndex($name);

    /**
     * Get the names of all the indexes in the table.
     *
     * @return array
     */
    public function indexes();

    /**
     * Get the column(s) used for the primary key.
     *
     * @return array Column name(s) for the primary key. An
     *   empty list will be returned when the table has no primary key.
     */
    public function primaryKey();

    /**
     * Add a constraint.
     *
     * Used to add constraints to a table. For example primary keys, unique
     * keys and foreign keys.
     *
     * ### Attributes
     *
     * - `type` The type of constraint being added.
     * - `columns` The columns in the index.
     * - `references` The table, column a foreign key references.
     * - `update` The behavior on update. Options are 'restrict', 'setNull', 'cascade', 'noAction'.
     * - `delete` The behavior on delete. Options are 'restrict', 'setNull', 'cascade', 'noAction'.
     *
     * The default for 'update' & 'delete' is 'cascade'.
     *
     * @param string $name The name of the constraint.
     * @param array $attrs The attributes for the constraint.
     * @return $this
     * @throws \Cake\Database\Exception
     */
    public function addConstraint($name, $attrs);

    /**
     * Read information about a constraint based on name.
     *
     * @param string $name The name of the constraint.
     * @return array|null Array of constraint data, or null
     */
    public function getConstraint($name);

    /**
     * Remove a constraint.
     *
     * @param string $name Name of the constraint to remove
     * @return $this
     */
    public function dropConstraint($name);

    /**
     * Get the names of all the constraints in the table.
     *
     * @return array
     */
    public function constraints();

    /**
     * Check whether or not a table has an autoIncrement column defined.
     *
     * @return bool
     */
    public function hasAutoincrement();

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

    /**
     * Sets whether the table is temporary in the database.
     *
     * @param bool $temporary Whether or not the table is to be temporary.
     * @return $this
     */
    public function setTemporary($temporary);

    /**
     * Gets whether the table is temporary in the database.
     *
     * @return bool The current temporary setting.
     */
    public function isTemporary();
}
