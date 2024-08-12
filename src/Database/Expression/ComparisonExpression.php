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

use Cake\Database\Exception\DatabaseException;
use Cake\Database\ExpressionInterface;
use Cake\Database\Type\ExpressionTypeCasterTrait;
use Cake\Database\ValueBinder;
use Closure;

/**
 * A Comparison is a type of query expression that represents an operation
 * involving a field an operator and a value. In its most common form the
 * string representation of a comparison is `field = value`
 */
class ComparisonExpression implements ExpressionInterface, FieldInterface
{
    use ExpressionTypeCasterTrait;
    use FieldTrait;

    /**
     * The value to be used in the right hand side of the operation
     *
     * @var mixed
     */
    protected mixed $_value;

    /**
     * The type to be used for casting the value to a database representation
     *
     * @var string|null
     */
    protected ?string $_type = null;

    /**
     * The operator used for comparing field and value
     *
     * @var string
     */
    protected string $_operator = '=';

    /**
     * Whether the value in this expression is a traversable
     *
     * @var bool
     */
    protected bool $_isMultiple = false;

    /**
     * A cached list of ExpressionInterface objects that were
     * found in the value for this expression.
     *
     * @var array<\Cake\Database\ExpressionInterface>
     */
    protected array $_valueExpressions = [];

    /**
     * Constructor
     *
     * @param \Cake\Database\ExpressionInterface|string $field the field name to compare to a value
     * @param mixed $value The value to be used in comparison
     * @param string|null $type the type name used to cast the value
     * @param string $operator the operator used for comparing field and value
     */
    public function __construct(
        ExpressionInterface|string $field,
        mixed $value,
        ?string $type = null,
        string $operator = '='
    ) {
        $this->_type = $type;
        $this->setField($field);
        $this->setValue($value);
        $this->_operator = $operator;
    }

    /**
     * Sets the value
     *
     * @param mixed $value The value to compare
     * @return void
     */
    public function setValue(mixed $value): void
    {
        $value = $this->_castToExpression($value, $this->_type);

        $isMultiple = $this->_type && str_contains($this->_type, '[]');
        if ($isMultiple) {
            [$value, $this->_valueExpressions] = $this->_collectExpressions($value);
        }

        $this->_isMultiple = $isMultiple;
        $this->_value = $value;
    }

    /**
     * Returns the value used for comparison
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->_value;
    }

    /**
     * Sets the operator to use for the comparison
     *
     * @param string $operator The operator to be used for the comparison.
     * @return void
     */
    public function setOperator(string $operator): void
    {
        $this->_operator = $operator;
    }

    /**
     * Returns the operator used for comparison
     *
     * @return string
     */
    public function getOperator(): string
    {
        return $this->_operator;
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
        assert(is_string($field));

        return sprintf($template, $field, $this->_operator, $value);
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback)
    {
        if ($this->_field instanceof ExpressionInterface) {
            $callback($this->_field);
            $this->_field->traverse($callback);
        }

        if ($this->_value instanceof ExpressionInterface) {
            $callback($this->_value);
            $this->_value->traverse($callback);
        }

        foreach ($this->_valueExpressions as $v) {
            $callback($v);
            $v->traverse($callback);
        }

        return $this;
    }

    /**
     * Create a deep clone.
     *
     * Clones the field and value if they are expression objects.
     *
     * @return void
     */
    public function __clone()
    {
        foreach (['_value', '_field'] as $prop) {
            if ($this->{$prop} instanceof ExpressionInterface) {
                $this->{$prop} = clone $this->{$prop};
            }
        }
    }

    /**
     * Returns a template and a placeholder for the value after registering it
     * with the placeholder $binder
     *
     * @param \Cake\Database\ValueBinder $binder The value binder to use.
     * @return array First position containing the template and the second a placeholder
     */
    protected function _stringExpression(ValueBinder $binder): array
    {
        $template = '%s ';

        if ($this->_field instanceof ExpressionInterface && !$this->_field instanceof IdentifierExpression) {
            $template = '(%s) ';
        }

        if ($this->_isMultiple) {
            $template .= '%s (%s)';
            $type = $this->_type;
            if ($type !== null) {
                $type = str_replace('[]', '', $type);
            }
            $value = $this->_flattenValue($this->_value, $binder, $type);

            // To avoid SQL errors when comparing a field to a list of empty values,
            // better just throw an exception here
            if ($value === '') {
                $field = $this->_field instanceof ExpressionInterface ? $this->_field->sql($binder) : $this->_field;
                /** @var string $field */
                throw new DatabaseException(
                    "Impossible to generate condition with empty list of values for field ({$field})"
                );
            }
        } else {
            $template .= '%s %s';
            $value = $this->_bindValue($this->_value, $binder, $this->_type);
        }

        return [$template, $value];
    }

    /**
     * Registers a value in the placeholder generator and returns the generated placeholder
     *
     * @param mixed $value The value to bind
     * @param \Cake\Database\ValueBinder $binder The value binder to use
     * @param string|null $type The type of $value
     * @return string generated placeholder
     */
    protected function _bindValue(mixed $value, ValueBinder $binder, ?string $type = null): string
    {
        $placeholder = $binder->placeholder('c');
        $binder->bind($placeholder, $value, $type);

        return $placeholder;
    }

    /**
     * Converts a traversable value into a set of placeholders generated by
     * $binder and separated by `,`
     *
     * @param iterable $value the value to flatten
     * @param \Cake\Database\ValueBinder $binder The value binder to use
     * @param string|null $type the type to cast values to
     * @return string
     */
    protected function _flattenValue(iterable $value, ValueBinder $binder, ?string $type = null): string
    {
        $parts = [];
        if (is_array($value)) {
            foreach ($this->_valueExpressions as $k => $v) {
                $parts[$k] = $v->sql($binder);
                unset($value[$k]);
            }
        }

        if ($value) {
            $parts += $binder->generateManyNamed($value, $type);
        }

        return implode(',', $parts);
    }

    /**
     * Returns an array with the original $values in the first position
     * and all ExpressionInterface objects that could be found in the second
     * position.
     *
     * @param \Cake\Database\ExpressionInterface|iterable $values The rows to insert
     * @return array
     */
    protected function _collectExpressions(ExpressionInterface|iterable $values): array
    {
        if ($values instanceof ExpressionInterface) {
            return [$values, []];
        }
        $expressions = [];
        $result = [];
        $isArray = is_array($values);

        if ($isArray) {
            $result = (array)$values;
        }

        foreach ($values as $k => $v) {
            if ($v instanceof ExpressionInterface) {
                $expressions[$k] = $v;
            }

            if ($isArray) {
                $result[$k] = $v;
            }
        }

        return [$result, $expressions];
    }
}
