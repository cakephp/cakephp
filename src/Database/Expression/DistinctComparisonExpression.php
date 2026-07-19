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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;

/**
 * A Comparison is a type of query expression that represents `IS DISTINCT FROM`
 * or `IS NOT DISTINCT FROM` operations.
 */
class DistinctComparisonExpression extends ComparisonExpression
{
    /**
     * Whether to wrap the expression in `NOT (...)`
     *
     * @var bool
     */
    protected bool $isNot = false;

    /**
     * Sets whether to wrap the expression in `NOT (...)`
     *
     * @param bool $not Whether to wrap the expression in `NOT (...)`
     * @return $this
     */
    public function setNot(bool $not)
    {
        $this->isNot = $not;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $field = $this->_field;

        if ($field instanceof ExpressionInterface) {
            $field = $field->sql($binder);
        }

        if ($this->_value instanceof IdentifierExpression) {
            $template = '%s %s %s';
            $value = $this->_value->sql($binder);
        } elseif ($this->_value instanceof ExpressionInterface) {
            $template = '%s %s (%s)';
            $value = $this->_value->sql($binder);
        } else {
            [$template, $value] = $this->_stringExpression($binder);
        }

        /** @var string $field */
        $sql = sprintf($template, $field, $this->_operator, $value);

        return $this->isNot ? "NOT ({$sql})" : $sql;
    }
}
