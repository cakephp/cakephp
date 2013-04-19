<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database\Schema;

/**
 * Represents a single table in a database schema.
 *
 * Can either be populated using the reflection API's
 * or by incrementally building an instance using
 * methods.
 *
 * Once created Table instances can be added to
 * Schema\Collection objects.
 */
class Table {

/**
 * The name of the table
 *
 * @var string
 */
	protected $_table;

/**
 * Columns in the table.
 *
 * @var array
 */
	protected $_columns = [];

/**
 * Indexes + Keys in the table.
 *
 * @var array
 */
	protected $_indexes = [];

/**
 * The valid keys that can be used in a column
 * definition.
 *
 * @var array
 */
	protected $_columnKeys = [
		'type' => null,
		'length' => null,
		'null' => null,
		'default' => null,
		'fixed' => null,
		'comment' => null,
		'collate' => null,
		'charset' => null,
	];

	public function __construct($table) {
		$this->_table = $table;
	}

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
 * - `default` The default value of the column.
 * - `null` Whether or not the column can hold nulls.
 * - `fixed` Whether or not the column is a fixed length column.
 *
 * In addition to the above keys, the following keys are
 * implemented in some database dialects, but not all:
 *
 * - `comment` The comment for the column.
 * - `charset` The charset for the column.
 * - `collate` The collation for the column.
 *
 * @param string $name The name of the column
 * @param array $attrs The attributes for the column.
 * @return Table $this
 */
	public function addColumn($name, $attrs) {
		if (is_string($attrs)) {
			$attrs = ['type' => $attrs];
		}
		$attrs = array_intersect_key($attrs, $this->_columnKeys);
		$this->_columns[$name] = $attrs + $this->_columnKeys;
		return $this;
	}

/**
 * Get the column names in the table.
 *
 * @return array
 */
	public function columns() {
		return array_keys($this->_columns);
	}

/**
 * Get column data in the table.
 *
 * @param string $name The column name.
 * @return array|null Column data or null.
 */
	public function column($name) {
		if (!isset($this->_columns[$name])) {
			return null;
		}
		return $this->_columns[$name];
	}

/**
 * Add an index or key.
 *
 * @param string $name The name of the index.
 * @param array $attrs The attributes for the index.
 * @return Table $this
 */
	public function addIndex($name, $attrs) {
		$this->_indexes[$name] = $attrs;
		return $this;
	}

/**
 * Get the names of all the indexes in the table.
 *
 * @return array
 */
	public function indexes() {
		return array_keys($this->_indexes);
	}

}
