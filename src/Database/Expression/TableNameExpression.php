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
 * Holds the Driver instance
 * 
 * @var \Cake\Database\Driver Instance of the driver currently used in the current Connection
 */
    protected $_driver;

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
 * Sets the type of operation this table name will be used in (either from or join)
 *
 * @param string $type
 * @return void
 */
    public function setType($type) {
        if ($type === "from") {
            $this->_type = "from";
        } else {
            $this->_type = "join";
        }
    }

/**
 * Sets the driver that will be used for the autoQuoting feature
 *
 * @param \Cake\Database\Driver $driver Instance of the driver currently used in the current Connection
 * @return void
 */
    public function setDriver($driver) {
        $this->_driver = $driver;
    }

/**
 * Constructor
 * 
 * @param string $name Table name
 * @param string $prefix Prefix to prepend
 */
    public function __construct($name, $prefix, $type, $alias = null) {
        $this->setName($name);
        $this->setPrefix($prefix);
        $this->setAlias($alias);
        $this->setType($type);
    }

/**
 * Converts the expression into a SQL string fragment.
 *
 * @param \Cake\Database\ValueBinder $generator Placeholder generator object
 * @return string
 */
    public function sql(ValueBinder $generator) {
        $sql = "";
        $quote = false;

        if (is_object($this->_driver) && method_exists($this->_driver, 'autoQuoting')) {
            if ($this->_driver->autoQuoting()) {
                $quote = true;
            }
        }

        if (is_string($this->_name)) {
            $sql = $this->_prefix . $this->_name;
        } elseif ($this->_name instanceof ExpressionInterface) {
            $sql = '(' . $this->_prefix . $this->_name->sql($generator) . ')';
        }

        if ($quote) {
            $sql = $this->_driver->quoteIdentifier($sql);
        }

        if ($this->_alias !== null && $this->_type === "from") {
            $alias = $quote ? $this->_driver->quoteIdentifier($this->_alias) : $this->_alias;

            $sql .= ' AS ' . $alias;
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
