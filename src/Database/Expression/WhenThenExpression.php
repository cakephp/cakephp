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
use Cake\Database\Type\ExpressionTypeCasterTrait;
use Cake\Database\TypeMap;
use Cake\Database\ValueBinder;
use Closure;
use InvalidArgumentException;
use LogicException;

class WhenThenExpression implements WhenThenExpressionInterface
{
    use CaseExpressionTrait;
    use ExpressionTypeCasterTrait;

    /**
     * The type map to use when using an array of conditions for the
     * `WHEN` value.
     *
     * @var \Cake\Database\TypeMap
     */
    protected $_typeMap;

    /**
     * Then `WHEN` value.
     *
     * @var \Cake\Database\ExpressionInterface|object|scalar|null
     */
    protected $_when = null;

    /**
     * The `WHEN` value type.
     *
     * @var array|string|null
     */
    protected $_whenType = null;

    /**
     * The `THEN` value.
     *
     * @var mixed
     */
    protected $_then = null;

    /**
     * Whether the `THEN` value has been defined, eg whether `then()`
     * has been invoked.
     *
     * @var bool
     */
    protected $_hasThenBeenDefined = false;

    /**
     * The `THEN` result type.
     *
     * @var string|null
     */
    protected $_thenType = null;

    /**
     * Constructor.
     *
     * @param \Cake\Database\TypeMap|null $typeMap The type map to use when using an array of conditions for the `WHEN`
     *  value.
     */
    public function __construct(?TypeMap $typeMap = null)
    {
        if ($typeMap === null) {
            $typeMap = new TypeMap();
        }
        $this->_typeMap = $typeMap;
    }

    /**
     * @inheritDoc
     */
    public function getWhen()
    {
        return $this->_when;
    }

    /**
     * @inheritDoc
     */
    public function when($when, $type = null)
    {
        if (
            !(is_array($when) && !empty($when)) &&
            !is_scalar($when) &&
            !is_object($when)
        ) {
            throw new InvalidArgumentException(sprintf(
                'The `$when` argument must be either a non-empty array, a scalar value, an object, ' .
                'or an instance of `\%s`, `%s` given.',
                ExpressionInterface::class,
                is_array($when) ? '[]' : getTypeName($when)
            ));
        }

        if (
            $type !== null &&
            !is_array($type) &&
            !is_string($type)
        ) {
            throw new InvalidArgumentException(sprintf(
                'The `$type` argument must be either an array, a string, or `null`, `%s` given.',
                getTypeName($type)
            ));
        }

        if (is_array($when)) {
            if (
                $type !== null &&
                !is_array($type)
            ) {
                throw new InvalidArgumentException(sprintf(
                    'When using an array for the `$when` argument, the `$type` argument must be an ' .
                    'array too, `%s` given.',
                    getTypeName($type)
                ));
            }

            // avoid dirtying the type map for possible consecutive `when()` calls
            $typeMap = clone $this->_typeMap;
            if (
                is_array($type) &&
                count($type) > 0
            ) {
                $typeMap = $typeMap->setTypes($type);
            }

            $when = new QueryExpression($when, $typeMap);
        } else {
            if (
                $type !== null &&
                !is_string($type)
            ) {
                throw new InvalidArgumentException(sprintf(
                    'When using a non-array value for the `$when` argument, the `$type` argument must ' .
                    'be a string, `%s` given.',
                    getTypeName($type)
                ));
            }

            if ($type === null) {
                $type = $this->_inferType($when);
            }
        }

        $this->_when = $when;
        $this->_whenType = $type;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getWhenType()
    {
        return $this->_whenType;
    }

    /**
     * @inheritDoc
     */
    public function getThen()
    {
        return $this->_then;
    }

    /**
     * @inheritDoc
     */
    public function then($result, ?string $type = null)
    {
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

        $this->_then = $result;

        if ($type === null) {
            $type = $this->_inferType($result);
        }

        $this->_thenType = $type;

        $this->_hasThenBeenDefined = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getThenType(): ?string
    {
        return $this->_thenType;
    }

    /**
     * Returns the type map.
     *
     * @return \Cake\Database\TypeMap
     */
    protected function getTypeMap(): TypeMap
    {
        return $this->_typeMap;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        if ($this->_when === null) {
            throw new LogicException(
                sprintf(
                    'Cannot compile incomplete `\%s`, the value for `WHEN` is missing.',
                    WhenThenExpressionInterface::class
                )
            );
        }

        if (!$this->_hasThenBeenDefined) {
            throw new LogicException(
                sprintf(
                    'Cannot compile incomplete `\%s`, the value for `THEN` is missing.',
                    WhenThenExpressionInterface::class
                )
            );
        }

        $when = $this->_when;
        if (
            is_string($this->_whenType) &&
            !($when instanceof ExpressionInterface)
        ) {
            $when = $this->_castToExpression($when, $this->_whenType);
        }
        if ($when instanceof Query) {
            $when = sprintf('(%s)', $when->sql($binder));
        } elseif ($when instanceof ExpressionInterface) {
            $when = $when->sql($binder);
        } else {
            $placeholder = $binder->placeholder('c');
            if (is_string($this->_whenType)) {
                $whenType = $this->_whenType;
            } else {
                $whenType = null;
            }
            $binder->bind($placeholder, $when, $whenType);
            $when = $placeholder;
        }

        $then = $this->_compileNullableValue($binder, $this->_then, $this->_thenType);

        return "WHEN $when THEN $then";
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback)
    {
        if ($this->_when instanceof ExpressionInterface) {
            $callback($this->_when);
            $this->_when->traverse($callback);
        }

        if ($this->_then instanceof ExpressionInterface) {
            $callback($this->_then);
            $this->_then->traverse($callback);
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
        if ($this->_when instanceof ExpressionInterface) {
            $this->_when = clone $this->_when;
        }

        if ($this->_then instanceof ExpressionInterface) {
            $this->_then = clone $this->_then;
        }
    }
}
