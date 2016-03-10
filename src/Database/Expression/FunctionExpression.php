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
use Cake\Database\TypedResultInterface;
use Cake\Database\TypedResultTrait;
use Cake\Database\ValueBinder;

/**
 * This class represents a function call string in a SQL statement. Calls can be
 * constructed by passing the name of the function and a list of params.
 * For security reasons, all params passed are quoted by default unless
 * explicitly told otherwise.
 *
 * @internal
 */
class FunctionExpression extends QueryExpression implements TypedResultInterface
{

    use TypedResultTrait;

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
     * ### Examples:
     *
     *  `$f = new FunctionExpression('CONCAT', ['CakePHP', ' rules']);`
     *
     * Previous line will generate `CONCAT('CakePHP', ' rules')`
     *
     * `$f = new FunctionExpression('CONCAT', ['name' => 'literal', ' rules']);`
     *
     * Will produce `CONCAT(name, ' rules')`
     *
     * @param string $name the name of the function to be constructed
     * @param array $params list of arguments to be passed to the function
     * If associative the key would be used as argument when value is 'literal'
     * @param array $types associative array of types to be associated with the
     * passed arguments
     * @param string $returnType The return type of this expression
     */
    public function __construct($name, $params = [], $types = [], $returnType = 'string')
    {
        $this->_name = $name;
        $this->_returnType = $returnType;
        parent::__construct($params, $types, ',');
    }

    /**
     * Sets the name of the SQL function to be invoke in this expression,
     * if no value is passed it will return current name
     *
     * @param string|null $name The name of the function
     * @return string|$this
     */
    public function name($name = null)
    {
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
     * @param array $types associative array of types to be associated with the
     * passed arguments
     * @param bool $prepend Whether to prepend or append to the list of arguments
     * @see \Cake\Database\Expression\FunctionExpression::__construct() for more details.
     * @return $this
     */
    public function add($params, $types = [], $prepend = false)
    {
        $put = $prepend ? 'array_unshift' : 'array_push';
        $typeMap = $this->typeMap()->types($types);
        foreach ($params as $k => $p) {
            if ($p === 'literal') {
                $put($this->_conditions, $k);
                continue;
            }

            if ($p === 'identifier') {
                $put($this->_conditions, new IdentifierExpression($k));
                continue;
            }

            if ($p instanceof ExpressionInterface) {
                $put($this->_conditions, $p);
                continue;
            }
            $put($this->_conditions, ['value' => $p, 'type' => $typeMap->type($k)]);
        }

        return $this;
    }

    /**
     * Returns the string representation of this object so that it can be used in a
     * SQL query. Note that values condition values are not included in the string,
     * in their place placeholders are put and can be replaced by the quoted values
     * accordingly.
     *
     * @param \Cake\Database\ValueBinder $generator Placeholder generator object
     * @return string
     */
    public function sql(ValueBinder $generator)
    {
        $parts = [];
        foreach ($this->_conditions as $condition) {
            if ($condition instanceof ExpressionInterface) {
                $condition = sprintf('(%s)', $condition->sql($generator));
            } elseif (is_array($condition)) {
                $p = $generator->placeholder('param');
                $generator->bind($p, $condition['value'], $condition['type']);
                $condition = $p;
            }
            $parts[] = $condition;
        }
        return $this->_name . sprintf('(%s)', implode(
            $this->_conjunction . ' ',
            $parts
        ));
    }

    /**
     * The name of the function is in itself an expression to generate, thus
     * always adding 1 to the amount of expressions stored in this object.
     *
     * @return int
     */
    public function count()
    {
        return 1 + count($this->_conditions);
    }
}
