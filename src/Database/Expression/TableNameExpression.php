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
 * Holds the table name string or the SQL snippet that needs to be prefixed
 *
 * @var string
 */
	protected $_value;

/**
 * Holds the prefix to be prepended to the tables names in the sql snipped stored in $_value
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
 * Holds the lists of table names for the Query this expression is used in
 *
 * @var array
 */
	protected $_tablesNames = [];

/**
 * Tells whether the current $_value is quoted or not
 *
 * @var bool
 */
	protected $_quoted = false;

/**
 * Tells whether the current $_value is a SQL snippet (as opposed to just a single table name)
 *
 * @var bool
 */
	protected $_snippet = false;

/**
 * Sets the table name this expression represents
 *
 * @param string $name Name of the table
 * @return void
 */
	public function setName($name) {
		$this->_value = $name;
	}

/**
 * Gets the table name this expression represents
 *
 * @return string Table name this expression represents
 */
	public function getName() {
		return $this->_value;
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
 * @param bool $snippet Whether this expression represents a SQL snippet or just a table name (optionnaly with a prefix)
 *
 * @return void
 */
	public function __construct($name, $prefix, $snippet = false, $tablesNames = []) {
		if ($snippet === false) {
			if (strpos($name, '.') !== false) {
				list($name, $field) = explode('.', $name);
			}
		}

		$this->setName($name);
		$this->setPrefix($prefix);
		$this->_snippet = $snippet;
		$this->_tablesNames = $tablesNames;

		if (!empty($field)) {
			$this->_field = $field;
		}
	}

/**
 * Change the $_quoted property that to tell that the $_value property was quoted
 *
 * @param bool $quoted Boolean indicating whether the $_value property was quoted or not
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

		if (is_string($this->_value) && $this->_snippet === false) {
			if ($this->_quoted) {
				$sql = $this->_value[0] . $this->_prefix . substr($this->_value, 1);
			} else {
				$sql = $this->_prefix . $this->_value;
			}

			if ($this->_field !== null) {
				$sql .= '.' . $this->_field;
			}
		} elseif ($this->_snippet === true) {
			if (is_string($this->_value)) {
				$sql = $this->_value;
				if (!empty($this->_tablesNames)) {
					$pattern = '/\b(?=(?:' . implode('|', $this->_tablesNames) . ')\b)([\w-]+)(\.[\w-]+)/';
					$sql = preg_replace($pattern, $this->_prefix . "$1$2", $sql);
				}
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
