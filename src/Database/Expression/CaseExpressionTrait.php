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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Cake\Database\ValueBinder;

/**
 * Trait that holds shared functionality for case related expressions.
 *
 * @internal
 */
trait CaseExpressionTrait
{
    use TypeInferenceTrait;

    /**
     * Compiles a nullable value to SQL.
     *
     * @param \Cake\Database\ValueBinder $binder The value binder to use.
     * @param \Cake\Database\ExpressionInterface|object|scalar|null $value The value to compile.
     * @param string|null $type The value type.
     * @return string
     */
    protected function compileNullableValue(ValueBinder $binder, mixed $value, ?string $type = null): string
    {
        if (
            $type !== null &&
            !($value instanceof ExpressionInterface)
        ) {
            $value = $this->_castToExpression($value, $type);
        }

        if ($value === null) {
            $value = 'NULL';
        } elseif ($value instanceof Query) {
            $value = sprintf('(%s)', $value->sql($binder));
        } elseif ($value instanceof ExpressionInterface) {
            $value = $value->sql($binder);
        } else {
            $placeholder = $binder->placeholder('c');
            $binder->bind($placeholder, $value, $type);
            $value = $placeholder;
        }

        return $value;
    }
}
