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

use Cake\Database\ValueBinder;
use Closure;

/**
 * This represents a SQL aggregate function expression in a SQL statement.
 * Calls can be constructed by passing the name of the function and a list of params.
 * For security reasons, all params passed are quoted by default unless
 * explicitly told otherwise.
 */
class AggregateExpression extends FunctionExpression implements WindowInterface
{
    /**
     * @var \Cake\Database\Expression\QueryExpression
     */
    protected $filter;

    /**
     * @var \Cake\Database\Expression\WindowExpression
     */
    protected $window;

    /**
     * Adds conditions to the FILTER clause. The conditions are the same format as
     * `Query::where()`.
     *
     * @param string|array|\Cake\Database\ExpressionInterface|\Closure $conditions The conditions to filter on.
     * @param array $types associative array of type names used to bind values to query
     * @return $this
     * @see \Cake\Database\Query::where()
     */
    public function filter($conditions, array $types = [])
    {
        if ($this->filter === null) {
            $this->filter = new QueryExpression();
        }

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
        if ($this->window === null) {
            $this->window = new WindowExpression();
        }
        if ($name) {
            // Set name manually in case this was chained from FunctionsBuilder wrapper
            $this->window->name($name);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function partition($partitions)
    {
        $this->over();
        $this->window->partition($partitions);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function order($fields)
    {
        $this->over();
        $this->window->order($fields);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function range($start, $end = 0)
    {
        $this->over();
        $this->window->range($start, $end);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function rows(?int $start, ?int $end = 0)
    {
        $this->over();
        $this->window->rows($start, $end);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function groups(?int $start, ?int $end = 0)
    {
        $this->over();
        $this->window->groups($start, $end);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function frame(
        string $type,
        $startOffset,
        string $startDirection,
        $endOffset,
        string $endDirection
    ) {
        $this->over();
        $this->window->frame($type, $startOffset, $startDirection, $endOffset, $endDirection);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeCurrent()
    {
        $this->over();
        $this->window->excludeCurrent();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeGroup()
    {
        $this->over();
        $this->window->excludeGroup();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeTies()
    {
        $this->over();
        $this->window->excludeTies();

        return $this;
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
