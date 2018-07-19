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
namespace Cake\Database\Schema;

use Cake\Datasource\SchemaInterface;

/**
 * An interface used by database TableSchema objects.
 *
 * Deprecated 3.5.0: Use Cake\Database\TableSchemaAwareInterface instead.
 */
interface TableSchemaInterface extends SchemaInterface
{

    /**
     * Binary column type
     *
     * @var string
     */
    const TYPE_BINARY = 'binary';

    /**
     * Binary UUID column type
     *
     * @var string
     */
    const TYPE_BINARY_UUID = 'binaryuuid';

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
     * Check whether or not a table has an autoIncrement column defined.
     *
     * @return bool
     */
    public function hasAutoincrement();

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

    /**
     * Get the column(s) used for the primary key.
     *
     * @return array Column name(s) for the primary key. An
     *   empty list will be returned when the table has no primary key.
     */
    public function primaryKey();

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
}
