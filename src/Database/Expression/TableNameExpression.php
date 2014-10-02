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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;

/**
 * Represents a table name from the datasource
 *
 * @internal
 */
class TableNameExpression implements ExpressionInterface {

/**
 * Holds the table name string
 *
 * @var string
 */
	protected $_name;

/**
 * Holds the prefix to be prepended to the table name
 *
 * @var string
 */
	protected $_prefix;

/**
 * Holds a possible field name to be associated with the table name
 * For instance in SELECT or ORDER clause (table.field)
 *
 * @var string
 */
	protected $_field = null;

/**
 * Tells whether the current $_name is quoted or not
 *
 * @var bool
 */
	protected $_quoted = false;

/**
 * Sets the table name this expression represents
 *
 * @param string $name Name of the table
 * @return void
 */
	public function setName($name) {
		$this->_name = $name;
	}

/**
 * Gets the table name this expression represents
 *
 * @return string Table name this expression represents
 */
	public function getName() {
		return $this->_name;
	}

/**
 * Sets the table name prefix for this expression
 *
 * @param string $prefix Prefix of the table
 * @return void
 */
	public function setPrefix($prefix) {
		$this->_prefix = $prefix;
	}

/**
 * Sets the field name associated to the table name for this expression
 *
 * @param string $field Field name associated to the table name
 * @return void
 */
	public function setField($field) {
		$this->_field = $field;
	}

/**
 * Gets the table name prefix for this expression
 *
 * @return void
 */
	public function getPrefix() {
		return $this->_prefix;
	}

/**
 * Constructor
 *
 * @param string $name Table name
 * @param string $prefix Prefix to prepend
 */
	public function __construct($name, $prefix, $field = null) {
		$this->setName($name);
		$this->setPrefix($prefix);

		if ($field !== null) {
			$this->setField($field);
		}
	}

/**
 * Change the $_quoted property that to tell that the $_name property was quoted
 *
 * @param bool $quoted Boolean indicating whether the $_name property was quoted or not
 *
 * @return void
 */
	public function setQuoted($quoted = true) {
		if ($quoted === true) {
			$this->_quoted = true;
		} else {
			$this->_quoted = false;
		}
	}

/**
 * Converts the expression into a SQL string fragment.
 *
 * @param \Cake\Database\ValueBinder $generator Placeholder generator object
 * @return string
 */
	public function sql(ValueBinder $generator) {
		$sql = "";

		if (is_string($this->_name)) {
			if ($this->_quoted) {
				$sql = $this->_name[0] . $this->_prefix . substr($this->_name, 1);
			} else {
				$sql = $this->_prefix . $this->_name;
			}

			if ($this->_field !== null) {
				$sql .= '.' . $this->_field;
			}
		}

		return $sql;
	}

/**
 * No-op. There is nothing to traverse
 *
 * @param callable $callable The callable to traverse with.
 * @return void
 */
	public function traverse(callable $callable) {
	}

}
