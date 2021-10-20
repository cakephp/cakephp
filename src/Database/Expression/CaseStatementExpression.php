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
     * The names of the clauses that are valid for use with the
     * `clause()` method.
     *
     * @var array<string>
     */
    protected $validClauseNames = [
        'value',
        'when',
        'else',
    ];

    /**
     * Whether this is a simple case expression.
     *
     * @var bool
     */
    protected $isSimpleVariant = false;

    /**
     * The case value.
     *
     * @var \Cake\Database\ExpressionInterface|scalar|object|null
     */
    protected $value = null;

    /**
     * The case value type.
     *
     * @var string|null
     */
    protected $valueType = null;

    /**
     * The `WHEN ... THEN ...` expressions.
     *
     * @var array<\Cake\Database\Expression\WhenThenExpressionInterface>
     */
    protected $when = [];

    /**
     * Buffer that holds values and types for use with `then()`.
     *
     * @var array|null
     */
    protected $whenBuffer = null;

    /**
     * The else part result value.
     *
     * @var \Cake\Database\ExpressionInterface|scalar|object|null
     */
    protected $else = null;

    /**
     * The else part result type.
     *
     * @var string|null
     */
    protected $elseType = null;

    /**
     * The return type.
     *
     * @var string|null
     */
    protected $returnType = null;

    /**
     * Constructor.
     *
     * When a value is set, the syntax generated is
     * `CASE case_value WHEN when_value ... END` (simple case),
     * where the `when_value`'s are compared against the
     * `case_value`.
     *
     * When no value is set, the syntax generated is
     * `CASE WHEN when_conditions ... END` (searched case),
     * where the conditions hold the comparisons.
     *
     * Note that `null` is a valid case value, and thus should
     * only be passed if you actually want to create the simple
     * case expression variant!
     *
     * @param \Cake\Database\ExpressionInterface|scalar|object|null $value The case value.
     * @param string|null $type The case value type. If no type is provided, the type will be tried to be inferred
     *  from the value.
     */
    public function __construct($value = null, ?string $type = null)
    {
        if (func_num_args() > 0) {
            if (
                $value !== null &&
                !is_scalar($value) &&
                !(is_object($value) && !($value instanceof Closure))
            ) {
                throw new InvalidArgumentException(sprintf(
                    'The `$value` argument must be either `null`, a scalar value, an object, ' .
                    'or an instance of `\%s`, `%s` given.',
                    ExpressionInterface::class,
                    get_debug_type($value)
                ));
            }

            $this->value = $value;

            if (
                $value !== null &&
                $type === null &&
                !($value instanceof ExpressionInterface)
            ) {
                $type = $this->inferType($value);
            }
            $this->valueType = $type;

            $this->isSimpleVariant = true;
        }
    }

    /**
     * @inheritDoc
     */
    public function when($when, $type = null)
    {
        if ($this->whenBuffer !== null) {
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
                    get_debug_type($when)
                ));
            }
        }

        if ($when instanceof WhenThenExpressionInterface) {
            $this->when[] = $when;
        } else {
            $this->whenBuffer = ['when' => $when, 'type' => $type];
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function then($result, ?string $type = null)
    {
        if ($this->whenBuffer === null) {
            throw new LogicException(
                'There is no `when()` buffer present, ' .
                'you must first open one before calling `then()`.'
            );
        }

        $whenThen = (new WhenThenExpression($this->getTypeMap()))
            ->when($this->whenBuffer['when'], $this->whenBuffer['type'])
            ->then($result, $type);

        $this->whenBuffer = null;

        $this->when[] = $whenThen;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function else($result, ?string $type = null)
    {
        if ($this->whenBuffer !== null) {
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
                get_debug_type($result)
            ));
        }

        if ($type === null) {
            $type = $this->inferType($result);
        }

        $this->else = $result;
        $this->elseType = $type;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getReturnType(): string
    {
        if ($this->returnType !== null) {
            return $this->returnType;
        }

        $types = [];
        foreach ($this->when as $when) {
            $type = $when->getResultType();
            if ($type !== null) {
                $types[] = $type;
            }
        }

        if ($this->elseType !== null) {
            $types[] = $this->elseType;
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
        $this->returnType = $type;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        if ($this->whenBuffer !== null) {
            throw new LogicException(
                sprintf(
                    'Cannot compile incomplete `\%s` expression, there is an open `when()` buffer present ' .
                    'that must be closed using `then()`.',
                    CaseExpressionInterface::class
                )
            );
        }

        if (empty($this->when)) {
            throw new LogicException(
                sprintf(
                    'Cannot compile incomplete `\%s` expression, there are no `WHEN ... THEN ...` statements.',
                    CaseExpressionInterface::class
                )
            );
        }

        $value = '';
        if ($this->isSimpleVariant) {
            $value = $this->compileNullableValue($binder, $this->value, $this->valueType) . ' ';
        }

        $whenThenExpressions = [];
        foreach ($this->when as $whenThen) {
            $whenThenExpressions[] = $whenThen->sql($binder);
        }
        $whenThen = implode(' ', $whenThenExpressions);

        $else = $this->compileNullableValue($binder, $this->else, $this->elseType);

        return "CASE {$value}{$whenThen} ELSE $else END";
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback)
    {
        if ($this->whenBuffer !== null) {
            throw new LogicException(
                sprintf(
                    'Cannot traverse incomplete `\%s` expression, there is an open `when()` buffer present ' .
                    'that must be closed using `then()`.',
                    CaseExpressionInterface::class
                )
            );
        }

        if ($this->value instanceof ExpressionInterface) {
            $callback($this->value);
            $this->value->traverse($callback);
        }

        foreach ($this->when as $when) {
            $callback($when);
            $when->traverse($callback);
        }

        if ($this->else instanceof ExpressionInterface) {
            $callback($this->else);
            $this->else->traverse($callback);
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
        if ($this->whenBuffer !== null) {
            throw new LogicException(
                sprintf(
                    'Cannot clone incomplete `\%s` expression, there is an open `when()` buffer present ' .
                    'that must be closed using `then()`.',
                    CaseExpressionInterface::class
                )
            );
        }

        if ($this->value instanceof ExpressionInterface) {
            $this->value = clone $this->value;
        }

        foreach ($this->when as $key => $when) {
            $this->when[$key] = clone $this->when[$key];
        }

        if ($this->else instanceof ExpressionInterface) {
            $this->else = clone $this->else;
        }
    }
}
