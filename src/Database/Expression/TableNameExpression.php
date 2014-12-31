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
 * Holds the parameters needed by the Expression
 * Params can be of the following :
 *
 * - `snippet` Bool that states whether the given $name is a SQL snippet or simply a table name.
 * default to false
 * - `tablesNames` Array of the tables names that are being used in the Query object this Expression
 * belongs to
 * - `quoted` Bool that states whether the current $_value is quoted or not
 *
 * @var array
 */
	protected $_params = [
		'snippet' => false,
		'tablesNames' => [],
		'quoted' => false,
		'quoteStrings' => ['', '']
	];

/**
 * Sets the table name this expression represents
 *
 * @param string $name Name of the table
 * @return void
 */
	public function setValue($name) {
		$this->_value = $name;
	}

/**
 * Gets the table name this expression represents
 *
 * @return string Table name this expression represents
 */
	public function getValue() {
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

	public function isSnippet() {
		return $this->_params['snippet'];
	}

/**
 * Constructor
 *
 * @param string $name Table name
 * @param string $prefix Prefix to prepend
 * @param array $params Various parameters for the expression
 * This tables names are the one that will need to be prefixed
 */
	public function __construct($name, $prefix, $params = []) {
		$this->_params = array_merge($this->_params, $params);
		if ($this->_params['snippet'] === false && strpos($name, '.') !== false) {
			list($name, $field) = explode('.', $name);
		}

		$this->setValue($name);
		$this->setPrefix($prefix);

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
		$this->_params['quoted'] = (bool)$quoted;
	}

/**
 * Converts the expression into a SQL string fragment.
 *
 * @param \Cake\Database\ValueBinder $generator Placeholder generator object
 * @return string
 */
	public function sql(ValueBinder $generator) {
		$sql = "";

		if (is_string($this->_value) && $this->_params['snippet'] === false) {
			return $this->_sqlValue();
		}

		if ($this->_params['snippet'] === true) {
			return $this->_sqlSnippet();
		}

		return $sql;
	}

/**
 * Create the SQL string when self::$_value is not a snippet
 *
 * @return string
 */
	protected function _sqlValue() {
		if ($this->_params['quoted'] === true) {
			$sql = $this->_value[0] . $this->_prefix . substr($this->_value, 1);
		} else {
			$sql = $this->_prefix . $this->_value;
		}

		if ($this->_field !== null) {
			$sql .= '.' . $this->_field;
		}

		return $sql;
	}

/**
 * Create the SQL string when self::$_value is a snippet
 *
 * @return string
 */
	protected function _sqlSnippet() {
		$sql = "";
		if (is_string($this->_value)) {
			$sql = $this->_value;

			if (!empty($this->_params['tablesNames'])) {
				$lookAhead = implode('|', $this->_params['tablesNames']);
				$wordPattern = '[\w-]+';
				$tableNamePattern = '([\w-]+)';
				$replacePattern = $this->_prefix . '$1$2';

				list($startQuote, $endQuote) = $this->_params['quoteStrings'];

				if ($this->_params['quoted'] === true && !empty($startQuote) && !empty($endQuote)) {
					$lookAhead = $startQuote . implode($endQuote . '|' . $startQuote, $this->_params['tablesNames']) . $endQuote;
					$tableNamePattern = '[' . $startQuote . ']' . $tableNamePattern . '[' . $endQuote . ']';
					$replacePattern = $startQuote . $this->_prefix . '$1' . $endQuote . '$2';

					if ($startQuote === $endQuote) {
						$wordPattern = '[\w-' . $startQuote . ']+';
					} else {
						$wordPattern = '[\w-' . $startQuote . $endQuote . ']+';
					}
				}

				$pattern = '/(?=(?:' . $lookAhead . '))' . $tableNamePattern . '(\.' . $wordPattern . ')/';
				$sql = preg_replace($pattern, $replacePattern, $sql);
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
