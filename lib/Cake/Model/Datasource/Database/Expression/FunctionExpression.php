<?php

namespace Cake\Model\Datasource\Database\Expression;
use Cake\Model\Datasource\Database\ExpressionInterface;

/**
 * This class represents a function call string in a SQL statement. Calls can be
 * constructed by passing the name of the function and a list of params.
 * For security reasons, all params passed are quoted by default unless
 * explicitly told otherwise.
 */
class FunctionExpression extends QueryExpression {

/**
 * The name of the function to be constructed when generating the SQL string
 *
 * @var string
 */
	protected $_name;

/**
 * Constructor. Takes a name for the function to be invoked and a list of params
 * to be passed into the function. Optionally you can pass a list of types to
 * be used for each bound param.
 *
 * By default, all params that are passed will be quoted. If you wish to use
 * literal arguments, you need to explicitly hint this function.
 *
 * ## Examples:
 *
 *	``$f = new FunctionExpression('CONCAT', ['CakePHP', ' rules']);``
 *
 * Previous line will generate ``CONCAT('CakePHP', ' rules')``
 *
 * ``$f = new FunctionExpression('CONCAT', ['name' => 'literal', ' rules']);``
 *
 * Will produce ``CONCAT(name, ' rules')``
 *
 * @param string $name the name of the function to be constructed
 * @param array $params list of arguments to be passed to the function
 * If associative the key would be used as argument when value is 'literal'
 * @param array types associative array of types to be associated with the
 * passed arguments
 * @return void
 */
	public function __construct($name, $params = [], $types = []) {
		$this->_name = $name;
		parent::__construct($params, $types, ',');
	}

	public function name($name = null) {
		if ($name === null) {
			return $this->_name;
		}
		$this->_name = $name;
		return $this;
	}

/**
 * Adds one or more arguments for the function call.
 *
 * @param array $params list of arguments to be passed to the function
 * If associative the key would be used as argument when value is 'literal'
 * @param array types associative array of types to be associated with the
 * passed arguments
 * @see FunctionExpression::__construct() for more details.
 * @return FunctionExpression
 */
	public function add($params, $types = []) {
		foreach ($params as $k => $p) {
			if (!is_numeric($k) && $p === 'literal') {
				$this->_conditions[] = $k;
				continue;
			}

			if ($p instanceof ExpressionInterface) {
				$this->_conditions[] = $p;
				continue;
			}

			$type = isset($types[$k]) ? $types[$k] : null;
			$this->_conditions[] = $this->_bindValue('param', $p, $type);
		}

		return $this;
	}

/**
 * Returns the string representation of this object so that it can be used in a
 * SQL query. Note that values condition values are not included in the string,
 * in their place placeholders are put and can be replaced by the quoted values
 * accordingly.
 *
 * @return string
 */
	public function sql() {
		return $this->_name . sprintf('(%s)', implode(
			$this->_conjunction. ' ',
			$this->_conditions
		));
	}

}
