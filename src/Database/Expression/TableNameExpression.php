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
use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;
use Cake\Database\Query;

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
 * Holds the alias to the table name
 *
 * @var string
 */
    protected $_alias;

/**
 * Sets the table name this expression represents
 *
 * @param string $name
 * @return void
 */
    public function setName($name) {
        $this->_name = $name;
    }

/**
 * Sets the prefix for the table name of this expression
 *
 * @param string $prefix
 * @return void
 */
    public function setPrefix($prefix) {
        $this->_prefix = $prefix;
    }
    
/**
 * Sets the alias to the table name in this expression
 *
 * @param string $alias
 * @return void
 */
    public function setAlias($alias) {
        if (is_numeric($alias)) {
            $alias = null;
        }
        $this->_alias = $alias;
    }

/**
 * Constructor
 *
 * @param string $name Table name
 * @param string $prefix Prefix to prepend
 */
    public function __construct($name, $prefix, $alias = null) {
        $this->setName($name);
        $this->setPrefix($prefix);
        $this->setAlias($alias);
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
            $sql = $this->_prefix . $this->_name;
        } elseif ($this->_name instanceof Query) {
            $sql = '(' . $this->_name->sql($generator) . ')';
        }

        if ($this->_alias !== null) {
            $sql .= ' AS ' . $this->_alias;
        }

        return $sql;
    }

/**
 * No-op. There is nothing to traverse
 *
 * @param callable $visitor
 * @return void
 */
    public function traverse(callable $visitor) {

    }
}
