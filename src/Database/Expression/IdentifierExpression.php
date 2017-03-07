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
 * Represents a single identifier name in the database
 */
class IdentifierExpression implements ExpressionInterface
{

    /**
     * Holds the identifier string
     *
     * @var string
     */
    protected $_identifier;

    /**
     * Constructor
     *
     * @param string $identifier The identifier this expression represents
     */
    public function __construct($identifier)
    {
        $this->_identifier = $identifier;
    }

    /**
     * Sets the identifier this expression represents
     *
     * @param string $identifier The identifier
     * @return void
     */
    public function setIdentifier($identifier)
    {
        $this->_identifier = $identifier;
    }

    /**
     * Returns the identifier this expression represents
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->_identifier;
    }

    /**
     * Converts the expression to its string representation
     *
     * @param \Cake\Database\ValueBinder $generator Placeholder generator object
     * @return string
     */
    public function sql(ValueBinder $generator)
    {
        return $this->_identifier;
    }

    /**
     * This method is a no-op, this is a leaf type of expression,
     * hence there is nothing to traverse
     *
     * @param callable $callable The callable to traverse with.
     * @return void
     */
    public function traverse(callable $callable)
    {
    }
}
