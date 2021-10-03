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
use Cake\Database\Type\ExpressionTypeCasterTrait;
use Cake\Database\TypeMap;
use Cake\Database\TypeMapTrait;
use Cake\Database\ValueBinder;
use Closure;
use InvalidArgumentException;
use LogicException;

class CaseStatementExpression implements CaseExpressionInterface
{
    use CaseExpressionTrait;
    use ExpressionTypeCasterTrait;
    use TypeMapTrait;

    /**
     * Whether this is a simple case expression.
     *
     * @var bool
     */
    protected $_isSimpleVariant = false;

    /**
     * The case value.
     *
     * @var \Cake\Database\ExpressionInterface|object|scalar|null
     */
    protected $_value = null;

    /**
     * The case value type.
     *
     * @var string|null
     */
    protected $_valueType = null;

    /**
     * The `WHEN ... THEN ...` expressions.
     *
     * @var \Cake\Database\Expression\WhenThenExpressionInterface[]
     */
    protected $_when = [];

    /**
     * Buffer that holds values and types for use with `then()`.
     *
     * @var array|null
     */
    protected $_whenBuffer = null;

    /**
     * The else part result value.
     *
     * @var mixed|null
     */
    protected $_else = null;

    /**
     * The else part result type.
     *
     * @var string|null
     */
    protected $_elseType = null;

    /**
     * The return type.
     *
     * @var string|null
     */
    protected $_returnType = null;

    /**
     * Constructor.
     *
     * @param \Cake\Database\TypeMap|null $typeMap The type map to use when using an array of conditions for the `WHEN`
     *  value.
     */
    public function __construct(?TypeMap $typeMap = null)
    {
        if ($typeMap !== null) {
            $this->setTypeMap($typeMap);
        }
    }

    /**
     * @inheritDoc
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * @inheritDoc
     */
    public function value($value, ?string $valueType = null)
    {
        if (
            $value !== null &&
            !is_scalar($value) &&
            !(is_object($value) && !($value instanceof Closure))
        ) {
            throw new InvalidArgumentException(sprintf(
                'The `$value` argument must be either `null`, a scalar value, an object, ' .
                'or an instance of `\%s`, `%s` given.',
                ExpressionInterface::class,
                getTypeName($value)
            ));
        }

        $this->_value = $value;

        if (
            $value !== null &&
            $valueType === null
        ) {
            $valueType = $this->_inferType($value);
        }
        $this->_valueType = $valueType;

        $this->_isSimpleVariant = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getValueType(): ?string
    {
        return $this->_valueType;
    }

    /**
     * @inheritDoc
     */
    public function getWhen(): array
    {
        return $this->_when;
    }

    /**
     * @inheritDoc
     */
    public function when($when, $type = null)
    {
        if ($this->_whenBuffer !== null) {
            throw new LogicException(
                'Cannot add new `WHEN` value while an open `when()` buffer is present, ' .
                'it must first be closed using `then()`.'
            );
        }

        if ($when instanceof Closure) {
            $when = $when(new WhenThenExpression($this->getTypeMap()));
            if (!($when instanceof WhenThenExpressionInterface)) {
                throw new LogicException(sprintf(
                    '`when()` callables must return an instance of `\%s`, `%s` given.',
                    WhenThenExpressionInterface::class,
                    getTypeName($when)
                ));
            }
        }

        if ($when instanceof WhenThenExpressionInterface) {
            $this->_when[] = $when;
        } else {
            $this->_whenBuffer = ['when' => $when, 'type' => $type];
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function then($result, ?string $type = null)
    {
        if ($this->_whenBuffer === null) {
            throw new LogicException(
                'There is no `when()` buffer present, ' .
                'you must first open one before calling `then()`.'
            );
        }

        $whenThen = (new WhenThenExpression($this->getTypeMap()))
            ->when($this->_whenBuffer['when'], $this->_whenBuffer['type'])
            ->then($result, $type);

        $this->_whenBuffer = null;

        $this->when($whenThen);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getElse()
    {
        return $this->_else;
    }

    /**
     * @inheritDoc
     */
    public function else($result, ?string $type = null)
    {
        if ($this->_whenBuffer !== null) {
            throw new LogicException(
                'Cannot set `ELSE` value when an open `when()` buffer is present, ' .
                'it must first be closed using `then()`.'
            );
        }

        if (
            $result !== null &&
            !is_scalar($result) &&
            !(is_object($result) && !($result instanceof Closure))
        ) {
            throw new InvalidArgumentException(sprintf(
                'The `$result` argument must be either `null`, a scalar value, an object, ' .
                'or an instance of `\%s`, `%s` given.',
                ExpressionInterface::class,
                getTypeName($result)
            ));
        }

        if ($type === null) {
            $type = $this->_inferType($result);
        }

        $this->_whenBuffer = null;

        $this->_else = $result;
        $this->_elseType = $type;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getElseType(): ?string
    {
        return $this->_elseType;
    }

    /**
     * @inheritDoc
     */
    public function getReturnType(): string
    {
        if ($this->_returnType !== null) {
            return $this->_returnType;
        }

        $types = [];
        foreach ($this->_when as $when) {
            $type = $when->getThenType();
            if ($type !== null) {
                $types[] = $type;
            }
        }

        if ($this->_elseType !== null) {
            $types[] = $this->_elseType;
        }

        $types = array_unique($types);
        if (count($types) === 1) {
            return $types[0];
        }

        return 'string';
    }

    /**
     * @inheritDoc
     */
    public function setReturnType(string $type)
    {
        $this->_returnType = $type;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        if ($this->_whenBuffer !== null) {
            throw new LogicException(
                sprintf(
                    'Cannot compile incomplete `\%s` expression, there is an open `when()` buffer present ' .
                    'that must be closed using `then()`.',
                    CaseExpressionInterface::class
                )
            );
        }

        if (empty($this->_when)) {
            throw new LogicException(
                sprintf(
                    'Cannot compile incomplete `\%s` expression, there are no `WHEN ... THEN ...` statements.',
                    CaseExpressionInterface::class
                )
            );
        }

        $value = '';
        if ($this->_isSimpleVariant) {
            $value = $this->_compileNullableValue($binder, $this->_value, $this->_valueType) . ' ';
        }

        $whenThenExpressions = [];
        foreach ($this->_when as $whenThen) {
            $whenThenExpressions[] = $whenThen->sql($binder);
        }
        $whenThen = implode(' ', $whenThenExpressions);

        $else = $this->_compileNullableValue($binder, $this->_else, $this->_elseType);

        return "CASE {$value}{$whenThen} ELSE $else END";
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback)
    {
        if ($this->_whenBuffer !== null) {
            throw new LogicException(
                sprintf(
                    'Cannot traverse incomplete `\%s` expression, there is an open `when()` buffer present ' .
                    'that must be closed using `then()`.',
                    CaseExpressionInterface::class
                )
            );
        }

        if ($this->_value instanceof ExpressionInterface) {
            $callback($this->_value);
            $this->_value->traverse($callback);
        }

        foreach ($this->_when as $when) {
            $callback($when);
            $when->traverse($callback);
        }

        if ($this->_else instanceof ExpressionInterface) {
            $callback($this->_else);
            $this->_else->traverse($callback);
        }

        return $this;
    }

    /**
     * Clones the inner expression objects.
     *
     * @return void
     */
    public function __clone()
    {
        if ($this->_whenBuffer !== null) {
            throw new LogicException(
                sprintf(
                    'Cannot clone incomplete `\%s` expression, there is an open `when()` buffer present ' .
                    'that must be closed using `then()`.',
                    CaseExpressionInterface::class
                )
            );
        }

        if ($this->_value instanceof ExpressionInterface) {
            $this->_value = clone $this->_value;
        }

        foreach ($this->_when as $key => $when) {
            $this->_when[$key] = clone $this->_when[$key];
        }

        if ($this->_else instanceof ExpressionInterface) {
            $this->_else = clone $this->_else;
        }
    }
}
