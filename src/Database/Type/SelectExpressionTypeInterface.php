<?php
declare(strict_types=1);

namespace Cake\Database\Type;

use Cake\Database\ExpressionInterface;

interface SelectExpressionTypeInterface
{
    /**
     * Returns an expression interface object for the given field that
     * should be used in `SELECT` statements.
     *
     * @param string|\Cake\Database\Expression\IdentifierExpression $field The name of the field in the select list.
     * @return \Cake\Database\ExpressionInterface
     */
    public function toSelectExpression($field): ExpressionInterface;
}
