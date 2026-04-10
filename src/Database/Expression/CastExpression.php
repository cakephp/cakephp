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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\TypedResultInterface;
use Cake\Database\TypedResultTrait;
use Cake\Database\ValueBinder;
use Closure;

/**
 * Represents a SQL CAST expression in a SQL statement.
 *
 * This class represents a SQL CAST operation that converts an expression
 * to a specific database type. For example: CAST(field AS INTEGER)
 */
class CastExpression implements ExpressionInterface, TypedResultInterface
{
    use TypedResultTrait;

    /**
     * Constructor
     *
     * @param \Cake\Database\ExpressionInterface|string $value The value or expression to cast
     * @param string $type The SQL data type to cast to (e.g., 'INTEGER', 'VARCHAR', 'DATE')
     * @param string $returnType The abstract return type. Defaults to 'string'.
     */
    public function __construct(
        protected ExpressionInterface|string $value,
        protected string $type,
        string $returnType = 'string',
    ) {
        $this->returnType = $returnType;
    }

    /**
     * Sets the value to be cast
     *
     * @param \Cake\Database\ExpressionInterface|string $value The value to cast
     * @return $this
     */
    public function setValue(ExpressionInterface|string $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Gets the value to be cast
     *
     * @return \Cake\Database\ExpressionInterface|string
     */
    public function getValue(): ExpressionInterface|string
    {
        return $this->value;
    }

    /**
     * Sets the target SQL data type
     *
     * @param string $type The SQL data type
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets the target SQL data type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $value = $this->value;
        if ($value instanceof ExpressionInterface) {
            $value = $value->sql($binder);
        }

        return sprintf('CAST(%s AS %s)', $value, $this->type);
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback): static
    {
        if ($this->value instanceof ExpressionInterface) {
            $callback($this->value);
            $this->value->traverse($callback);
        }

        return $this;
    }

    /**
     * Clones the inner expression if it's an ExpressionInterface
     */
    public function __clone()
    {
        if ($this->value instanceof ExpressionInterface) {
            $this->value = clone $this->value;
        }
    }
}
