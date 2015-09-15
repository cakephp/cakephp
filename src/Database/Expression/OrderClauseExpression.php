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
 * An expression object for complex ORDER BY clauses
 *
 * @internal
 */
class OrderClauseExpression implements ExpressionInterface, FieldInterface
{
    use FieldTrait;

    /**
     * The direction of sorting.
     *
     * @var string
     */
    protected $_direction;

    /**
     * Constructor
     *
     * @param \Cake\Database\ExpressionInterface|string $field The field to order on.
     * @param string $direction The direction to sort on.
     */
    public function __construct($field, $direction)
    {
        $this->_field = $field;
        $this->_direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
    }

    /**
     * {@inheritDoc}
     */
    public function sql(ValueBinder $generator)
    {
        $field = $this->_field;
        if ($field instanceof ExpressionInterface) {
            $field = $field->sql($generator);
        }
        return sprintf("%s %s", $field, $this->_direction);
    }

    /**
     * {@inheritDoc}
     */
    public function traverse(callable $visitor)
    {
        if ($this->_field instanceof ExpressionInterface) {
            $visitor($this->_field);
            $this->_field->traverse($visitor);
        }
    }
}
