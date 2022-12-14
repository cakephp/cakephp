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
    /**
     * @var \Cake\Database\Expression\QueryExpression|null
     */
    protected ?QueryExpression $filter = null;

    /**
     * @var \Cake\Database\Expression\WindowExpression|null
     */
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
    public function filter(ExpressionInterface|Closure|array|string $conditions, array $types = [])
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
    public function over(?string $name = null)
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
    public function partition(ExpressionInterface|Closure|array|string $partitions)
    {
        $this->getWindow()->partition($partitions);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function order(ExpressionInterface|Closure|array|string $fields)
    {
        return $this->orderBy($fields);
    }

    /**
     * @inheritDoc
     */
    public function orderBy(ExpressionInterface|Closure|array|string $fields)
    {
        $this->getWindow()->orderBy($fields);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function range(ExpressionInterface|string|int|null $start, ExpressionInterface|string|int|null $end = 0)
    {
        $this->getWindow()->range($start, $end);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function rows(?int $start, ?int $end = 0)
    {
        $this->getWindow()->rows($start, $end);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function groups(?int $start, ?int $end = 0)
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
    ) {
        $this->getWindow()->frame($type, $startOffset, $startDirection, $endOffset, $endDirection);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeCurrent()
    {
        $this->getWindow()->excludeCurrent();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeGroup()
    {
        $this->getWindow()->excludeGroup();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeTies()
    {
        $this->getWindow()->excludeTies();

        return $this;
    }

    /**
     * Returns or creates WindowExpression for function.
     *
     * @return \Cake\Database\Expression\WindowExpression
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
        if ($this->filter !== null) {
            $sql .= ' FILTER (WHERE ' . $this->filter->sql($binder) . ')';
        }
        if ($this->window !== null) {
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
    public function traverse(Closure $callback)
    {
        parent::traverse($callback);
        if ($this->filter !== null) {
            $callback($this->filter);
            $this->filter->traverse($callback);
        }
        if ($this->window !== null) {
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
        if ($this->window !== null) {
            $count = $count + 1;
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
        if ($this->filter !== null) {
            $this->filter = clone $this->filter;
        }
        if ($this->window !== null) {
            $this->window = clone $this->window;
        }
    }
}
