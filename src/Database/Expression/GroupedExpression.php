<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ValueBinder;
use Closure;

/**
 * Handles the few special aggregate functions that require special syntax
 * for grouped ordering.
 */
class GroupedExpression extends FunctionExpression
{
    /**
     * @var \Cake\Database\Expression\FunctionExpression
     */
    protected $orderContainer;

    /**
     * @var \Cake\Database\Expression\OrderByExpression|null
     */
    protected $order;

    /**
     * @inheritDoc
     */
    public function __construct(string $name, array $params = [], array $types = [], string $returnType = 'string')
    {
        $this->resetOrderContainer();
        parent::__construct($name, $params, $types, $returnType);
    }

    /**
     * Overwrites or initializes the orderContainer property with a
     * FunctionExpression object.
     *
     * @return $this
     */
    protected function resetOrderContainer()
    {
        $this->orderContainer = (
            new FunctionExpression(
                $this->orderContainer instanceof FunctionExpression ? $this->orderContainer->getName() : 'OVER '
            )
        )->setConjunction(' ', false, false);

        return $this;
    }

    /**
     * Adds one or more order clauses to the group function.
     * Order expressions for GroupedExpression are added to an `OVER()` clause
     * at the end of the function.
     *
     * @param \Closure|(\Cake\Database\ExpressionInterface|string)[]|\Cake\Database\ExpressionInterface|string $fields Order expressions
     * @return $this
     */
    public function order($fields)
    {
        if (!$fields) {
            return $this;
        }

        if ($this->order === null) {
            $this->order = new OrderByExpression();
        }

        if ($fields instanceof Closure) {
            $fields = $fields(new QueryExpression([], [], ''));
        }

        if ($fields instanceof OrderByExpression) {
            $this->order = $fields;
        } else {
            $this->order->add($fields);
        }

        return $this;
    }

    /**
     * Returns the order container function expression.
     *
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function getOrderContainer()
    {
        return $this->orderContainer;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $generator): string
    {
        $this->resetOrderContainer()->orderContainer->add([$this->order]);
        $order = $this->order ? $this->orderContainer->sql($generator) : '';

        return rtrim(parent::sql($generator) . ' ' . $order);
    }
}
