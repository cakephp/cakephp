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
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;
use Closure;

/**
 * This represents an SQL aggregate function expression in an SQL statement.
 * Calls can be constructed by passing the name of the function and a list of params.
 * For security reasons, all params passed are quoted by default unless
 * explicitly told otherwise.
 */
class AggregateExpression extends FunctionExpression implements WindowInterface
{
    protected ?QueryExpression $filter = null;

    protected ?WindowExpression $window = null;

    /**
     * Adds conditions to the FILTER clause. The conditions are the same format as
     * `Query::where()`.
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array|string $conditions The conditions to filter on.
     * @param array<string, string> $types Associative array of type names used to bind values to query
     * @return $this
     * @see \Cake\Database\Query::where()
     */
    public function filter(ExpressionInterface|Closure|array|string $conditions, array $types = []): static
    {
        $this->filter ??= new QueryExpression();

        if ($conditions instanceof Closure) {
            $conditions = $conditions(new QueryExpression());
        }

        $this->filter->add($conditions, $types);

        return $this;
    }

    /**
     * Adds an empty `OVER()` window expression or a named window epression.
     *
     * @param string|null $name Window name
     * @return $this
     */
    public function over(?string $name = null): static
    {
        $window = $this->getWindow();
        if ($name) {
            // Set name manually in case this was chained from FunctionsBuilder wrapper
            $window->name($name);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function partition(ExpressionInterface|Closure|array|string $partitions): static
    {
        $this->getWindow()->partition($partitions);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function order(ExpressionInterface|Closure|array|string $fields): static
    {
        return $this->orderBy($fields);
    }

    /**
     * @inheritDoc
     */
    public function orderBy(ExpressionInterface|Closure|array|string $fields): static
    {
        $this->getWindow()->orderBy($fields);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function range(ExpressionInterface|string|int|null $start, ExpressionInterface|string|int|null $end = 0): static
    {
        $this->getWindow()->range($start, $end);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function rows(?int $start, ?int $end = 0): static
    {
        $this->getWindow()->rows($start, $end);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function groups(?int $start, ?int $end = 0): static
    {
        $this->getWindow()->groups($start, $end);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function frame(
        string $type,
        ExpressionInterface|string|int|null $startOffset,
        string $startDirection,
        ExpressionInterface|string|int|null $endOffset,
        string $endDirection
    ): static {
        $this->getWindow()->frame($type, $startOffset, $startDirection, $endOffset, $endDirection);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeCurrent(): static
    {
        $this->getWindow()->excludeCurrent();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeGroup(): static
    {
        $this->getWindow()->excludeGroup();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeTies(): static
    {
        $this->getWindow()->excludeTies();

        return $this;
    }

    /**
     * Returns or creates WindowExpression for function.
     */
    protected function getWindow(): WindowExpression
    {
        return $this->window ??= new WindowExpression();
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $sql = parent::sql($binder);
        if ($this->filter instanceof \Cake\Database\Expression\QueryExpression) {
            $sql .= ' FILTER (WHERE ' . $this->filter->sql($binder) . ')';
        }

        if ($this->window instanceof \Cake\Database\Expression\WindowExpression) {
            if ($this->window->isNamedOnly()) {
                $sql .= ' OVER ' . $this->window->sql($binder);
            } else {
                $sql .= ' OVER (' . $this->window->sql($binder) . ')';
            }
        }

        return $sql;
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback): static
    {
        parent::traverse($callback);
        if ($this->filter instanceof \Cake\Database\Expression\QueryExpression) {
            $callback($this->filter);
            $this->filter->traverse($callback);
        }

        if ($this->window instanceof \Cake\Database\Expression\WindowExpression) {
            $callback($this->window);
            $this->window->traverse($callback);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        $count = parent::count();
        if ($this->window instanceof \Cake\Database\Expression\WindowExpression) {
            return $count + 1;
        }

        return $count;
    }

    /**
     * Clone this object and its subtree of expressions.
     *
     * @return void
     */
    public function __clone()
    {
        parent::__clone();
        if ($this->filter instanceof \Cake\Database\Expression\QueryExpression) {
            $this->filter = clone $this->filter;
        }

        if ($this->window instanceof \Cake\Database\Expression\WindowExpression) {
            $this->window = clone $this->window;
        }
    }
}
