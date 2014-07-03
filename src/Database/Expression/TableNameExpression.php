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
 * Sets the alias to the table name in this expression
 *
 * @param string $alias
 * @return void
 */
    public function setAlias($alias) {
        $this->_alias = $alias;
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
 * Constructor
 *
 * @param string $name Table name
 * @param string $prefix Prefix to prepend
 */
    public function __construct($name, $alias, $prefix) {
        $this->setName($name);
        $this->setAlias($alias);
        $this->setPrefix($prefix);
    }

/**
 * Converts the expression into a SQL string fragment.
 *
 * @param \Cake\Database\ValueBinder $generator Placeholder generator object
 * @return string
 */
    public function sql(ValueBinder $generator) {
        if (is_string($this->_name)) {
            $sql = $this->_prefix . $this->_name;
        } else {
            $sql = '(' . $this->_name . ')';
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