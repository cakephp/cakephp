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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\Type\ExpressionTypeCasterTrait;
use Cake\Database\TypedResultInterface;
use Cake\Database\TypedResultTrait;
use Cake\Database\ValueBinder;
use Closure;

/**
 * An expression object that represents a SQL BETWEEN snippet
 */
class BetweenExpression implements ExpressionInterface, FieldInterface, TypedResultInterface
{
    use ExpressionTypeCasterTrait;
    use FieldTrait;
    use TypedResultTrait;

    /**
     * The first value in the expression
     *
     * @var mixed
     */
    protected mixed $from;

    /**
     * The second value in the expression
     *
     * @var mixed
     */
    protected mixed $to;

    /**
     * Constructor
     *
     * @param \Cake\Database\ExpressionInterface|string $field The field name to compare for values in between the range.
     * @param mixed $from The initial value of the range.
     * @param mixed $to The ending value in the comparison range.
     * @param string|null $type The data type name to bind the values with.
     * @param bool $not Whether this is a NOT BETWEEN expression.
     */
    public function __construct(
        ExpressionInterface|string $field,
        mixed $from,
        mixed $to,
        protected ?string $type = null,
        protected bool $not = false,
    ) {
        if ($type !== null) {
            $from = $this->castToExpression($from, $type);
            $to = $this->castToExpression($to, $type);
        }

        $this->field = $field;
        $this->from = $from;
        $this->to = $to;
        $this->returnType = 'boolean';
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $parts = [
            'from' => $this->from,
            'to' => $this->to,
        ];

        $field = $this->field;
        if ($field instanceof ExpressionInterface) {
            $field = $field->sql($binder);
        }

        foreach ($parts as $name => $part) {
            if ($part instanceof ExpressionInterface) {
                $parts[$name] = $part->sql($binder);
                continue;
            }
            $parts[$name] = $this->bindValue($part, $binder, $this->type);
        }
        assert(is_string($field));

        $operator = $this->not ? 'NOT BETWEEN' : 'BETWEEN';

        return sprintf('%s %s %s AND %s', $field, $operator, $parts['from'], $parts['to']);
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback): static
    {
        foreach ([$this->field, $this->from, $this->to] as $part) {
            if ($part instanceof ExpressionInterface) {
                $callback($part);
            }
        }

        return $this;
    }

    /**
     * Registers a value in the placeholder generator and returns the generated placeholder
     *
     * @param mixed $value The value to bind
     * @param \Cake\Database\ValueBinder $binder The value binder to use
     * @param string|null $type The type of $value
     * @return string generated placeholder
     */
    protected function bindValue(mixed $value, ValueBinder $binder, ?string $type): string
    {
        $placeholder = $binder->placeholder('c');
        $binder->bind($placeholder, $value, $type);

        return $placeholder;
    }

    /**
     * Do a deep clone of this expression.
     */
    public function __clone()
    {
        foreach (['field', 'from', 'to'] as $part) {
            if ($this->{$part} instanceof ExpressionInterface) {
                $this->{$part} = clone $this->{$part};
            }
        }
    }
}
