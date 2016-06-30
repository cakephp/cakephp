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
 * @since         3.1.4
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;

/**
 * An expression object that represents a cross schema table name
 *
 * @internal
 */
class CrossSchemaTableExpression implements ExpressionInterface
{

    /**
     * Name of the schema
     *
     * @var ExpressionInterface|string
     */
    protected $_schema;

    /**
     * Name of the table
     *
     * @var ExpressionInterface|string
     */
    protected $_table;

    /**
     * @inheritDoc
     *
     * @param string|ExpressionInterface $schema Name of the schema
     * @param string|ExpressionInterface $table Name of the table
     */
    public function __construct($schema, $table)
    {
        $this->_schema = $schema;
        $this->_table = $table;
    }

    /**
     * Converts the Node into a SQL string fragment.
     *
     * @param \Cake\Database\ValueBinder $generator Placeholder generator object
     * @return string
     */
    public function sql(ValueBinder $generator)
    {
        $schema = $this->_schema;
        if ($schema instanceof ExpressionInterface) {
            $schema = $schema->sql($generator);
        }

        $table = $this->_table;
        if ($table instanceof ExpressionInterface) {
            $table = $table->sql($generator);
        }

        return sprintf('%s.%s', $schema, $table);
    }

    /**
     * Iterates over each part of the expression recursively for every
     * level of the expressions tree and executes the $visitor callable
     * passing as first parameter the instance of the expression currently
     * being iterated.
     *
     * @param callable $visitor The callable to apply to all nodes.
     * @return void
     */
    public function traverse(callable $visitor)
    {
        if ($this->_schema instanceof ExpressionInterface) {
            $this->_schema->traverse($visitor);
        }
        if ($this->_table instanceof ExpressionInterface) {
            $this->_table->traverse($visitor);
        }
    }
}
