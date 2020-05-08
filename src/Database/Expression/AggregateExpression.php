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
     * @var \Cake\Database\Expression\WindowExpression
     */
    protected $window;

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
            $this->window->setName($name);
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
    public function range(?int $start, ?int $end = 0)
    {
        $this->over();
        if (func_num_args() === 1) {
            $this->window->frame(self::RANGE, $start, self::PRECEDING);
        } else {
            $this->window->frame(self::RANGE, $start, self::PRECEDING, $end, self::FOLLOWING);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function rows(?int $start, ?int $end = 0)
    {
        $this->over();
        if (func_num_args() === 1) {
            $this->window->frame(self::ROWS, $start, self::PRECEDING);
        } else {
            $this->window->frame(self::ROWS, $start, self::PRECEDING, $end, self::FOLLOWING);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function groups(?int $start, ?int $end = 0)
    {
        $this->over();
        if (func_num_args() === 1) {
            $this->window->frame(self::GROUPS, $start, self::PRECEDING);
        } else {
            $this->window->frame(self::GROUPS, $start, self::PRECEDING, $end, self::FOLLOWING);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function frame(
        string $type,
        ?int $startOffset,
        string $startDirection,
        ?int $endOffset = null,
        string $endDirection = self::FOLLOWING
    ) {
        $this->over();
        if (func_num_args() === 3) {
            $this->window->frame($type, $startOffset, $startDirection);
        } else {
            $this->window->frame($type, $startOffset, $startDirection, $endOffset, $endDirection);
        }

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
    public function sql(ValueBinder $generator): string
    {
        $sql = parent::sql($generator);
        if ($this->window !== null) {
            if ($this->window->isEmpty() && $this->window->getName()) {
                $sql .= ' OVER ' . $this->window->getName();
            } else {
                $sql .= ' OVER (' . $this->window->sql($generator) . ')';
            }
        }

        return $sql;
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $visitor)
    {
        parent::traverse($visitor);
        if ($this->window !== null) {
            $this->window->traverse($visitor);
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
        if ($this->window !== null) {
            $this->window = clone $this->window;
        }
    }
}
