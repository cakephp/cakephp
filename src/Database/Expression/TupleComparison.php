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
use Cake\Database\ValueBinder;
use Closure;
use InvalidArgumentException;

/**
 * This expression represents SQL fragments that are used for comparing one tuple
 * to another, one tuple to a set of other tuples or one tuple to an expression
 */
class TupleComparison extends ComparisonExpression
{
    /**
     * The type to be used for casting the value to a database representation
     *
     * @var array<string|null>
     */
    protected array $types;

    /**
     * Constructor
     *
     * @param \Cake\Database\ExpressionInterface|array|string $fields the fields to use to form a tuple
     * @param \Cake\Database\ExpressionInterface|array $values the values to use to form a tuple
     * @param array<string|null> $types the types names to use for casting each of the values, only
     * one type per position in the value array in needed
     * @param string $conjunction the operator used for comparing field and value
     */
    public function __construct(
        ExpressionInterface|array|string $fields,
        ExpressionInterface|array $values,
        array $types = [],
        string $conjunction = '=',
    ) {
        $this->types = $types;
        $this->setField($fields);
        $this->_operator = $conjunction;
        $this->setValue($values);
    }

    /**
     * Returns the type to be used for casting the value to a database representation
     *
     * @return array<string|null>
     */
    public function getType(): array
    {
        return $this->types;
    }

    /**
     * Sets the value
     *
     * @param mixed $value The value to compare
     * @return void
     */
    public function setValue(mixed $value): void
    {
        if ($this->isMulti()) {
            if (is_array($value) && !is_array(current($value))) {
                throw new InvalidArgumentException(
                    'Multi-tuple comparisons require a multi-tuple value, single-tuple given.',
                );
            }
        } elseif (is_array($value) && is_array(current($value))) {
            throw new InvalidArgumentException(
                'Single-tuple comparisons require a single-tuple value, multi-tuple given.',
            );
        }

        $this->_value = $value;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $template = '(%s) %s (%s)';
        $fields = [];
        $originalFields = $this->getField();

        if (!is_array($originalFields)) {
            $originalFields = [$originalFields];
        }

        foreach ($originalFields as $field) {
            $fields[] = $field instanceof ExpressionInterface ? $field->sql($binder) : $field;
        }

        $values = $this->_stringifyValues($binder);

        $field = implode(', ', $fields);

        return sprintf($template, $field, $this->_operator, $values);
    }

    /**
     * Returns a string with the values as placeholders in a string to be used
     * for the SQL version of this expression
     *
     * @param \Cake\Database\ValueBinder $binder The value binder to convert expressions with.
     * @return string
     */
    protected function _stringifyValues(ValueBinder $binder): string
    {
        $values = [];
        $parts = $this->getValue();

        if ($parts instanceof ExpressionInterface) {
            return $parts->sql($binder);
        }

        foreach ($parts as $i => $value) {
            if ($value instanceof ExpressionInterface) {
                $values[] = $value->sql($binder);
                continue;
            }

            $type = $this->types;
            $isMultiOperation = $this->isMulti();
            if (!$type) {
                $type = null;
            }

            if ($isMultiOperation) {
                $bound = [];
                foreach ($value as $k => $val) {
                    $valType = $type && isset($type[$k]) ? $type[$k] : $type;
                    assert($valType === null || is_scalar($valType));
                    $bound[] = $this->_bindValue($val, $binder, $valType);
                }

                $values[] = sprintf('(%s)', implode(',', $bound));
                continue;
            }

            $valType = $type && isset($type[$i]) ? $type[$i] : $type;
            assert($valType === null || is_scalar($valType));
            $values[] = $this->_bindValue($value, $binder, $valType);
        }

        return implode(', ', $values);
    }

    /**
     * @inheritDoc
     */
    protected function _bindValue(mixed $value, ValueBinder $binder, ?string $type = null): string
    {
        $placeholder = $binder->placeholder('tuple');
        $binder->bind($placeholder, $value, $type);

        return $placeholder;
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback)
    {
        $fields = (array)$this->getField();
        foreach ($fields as $field) {
            $this->_traverseValue($field, $callback);
        }

        $value = $this->getValue();
        if ($value instanceof ExpressionInterface) {
            $callback($value);
            $value->traverse($callback);

            return $this;
        }

        foreach ($value as $val) {
            if ($this->isMulti()) {
                foreach ($val as $v) {
                    $this->_traverseValue($v, $callback);
                }
            } else {
                $this->_traverseValue($val, $callback);
            }
        }

        return $this;
    }

    /**
     * Conditionally executes the callback for the passed value if
     * it is an ExpressionInterface
     *
     * @param mixed $value The value to traverse
     * @param \Closure $callback The callback to use when traversing
     * @return void
     */
    protected function _traverseValue(mixed $value, Closure $callback): void
    {
        if ($value instanceof ExpressionInterface) {
            $callback($value);
            $value->traverse($callback);
        }
    }

    /**
     * Determines if each of the values in this expressions is a tuple in
     * itself
     *
     * @return bool
     */
    public function isMulti(): bool
    {
        return in_array(strtolower($this->_operator), ['in', 'not in']);
    }
}
