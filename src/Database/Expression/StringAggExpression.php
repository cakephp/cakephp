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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Cake\Database\ValueBinder;
use Closure;
use InvalidArgumentException;

/**
 * Portable string aggregation expression that can render as STRING_AGG or
 * GROUP_CONCAT depending on the driver dialect.
 */
class StringAggExpression extends AggregateExpression
{
    public const SYNTAX_STANDARD = 'standard';
    public const SYNTAX_WITHIN_GROUP = 'within-group';
    public const SYNTAX_GROUP_CONCAT = 'group-concat';

    /**
     * Aggregate-local ORDER BY clause.
     *
     * @var \Cake\Database\Expression\OrderByExpression|null
     */
    protected ?OrderByExpression $aggregateOrderBy = null;

    /**
     * SQL syntax variant to render.
     *
     * @var string
     */
    protected string $syntax = self::SYNTAX_STANDARD;

    /**
     * Constructor.
     *
     * @param array $params Function arguments, value first and separator second.
     * @param array<string, string>|array<string|null> $types Types for function arguments.
     * @param \Cake\Database\ExpressionInterface|array|string|null $orderBy Aggregate-local ordering.
     */
    public function __construct(array $params = [], array $types = [], ExpressionInterface|array|string|null $orderBy = null)
    {
        parent::__construct('STRING_AGG', $params, $types);
        if ($orderBy !== null) {
            $this->setAggregateOrderBy($orderBy);
        }
    }

    /**
     * Sets aggregate-local ordering.
     *
     * @param \Cake\Database\ExpressionInterface|array|string $fields The sort columns.
     * @return $this
     */
    public function setAggregateOrderBy(ExpressionInterface|array|string $fields)
    {
        $this->aggregateOrderBy ??= new OrderByExpression();
        $this->aggregateOrderBy->add($fields);

        return $this;
    }

    /**
     * Sets the SQL syntax variant.
     *
     * @param string $syntax The syntax variant.
     * @return $this
     */
    public function setSyntax(string $syntax)
    {
        $allowed = [
            static::SYNTAX_STANDARD,
            static::SYNTAX_WITHIN_GROUP,
            static::SYNTAX_GROUP_CONCAT,
        ];
        if (!in_array($syntax, $allowed, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported string aggregation syntax `%s`.', $syntax));
        }

        $this->syntax = $syntax;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $parts = array_map(fn($part) => $this->stringifyPart($part, $binder), $this->_conditions);
        [$value, $separator] = $parts + [null, null];

        $sql = match ($this->syntax) {
            static::SYNTAX_GROUP_CONCAT => $this->_name . sprintf(
                '(%s%s SEPARATOR %s)',
                $value,
                $this->aggregateOrderBy ? ' ' . $this->aggregateOrderBy->sql($binder) : '',
                $separator,
            ),
            static::SYNTAX_WITHIN_GROUP => $this->_name . sprintf(
                '(%s, %s)%s',
                $value,
                $separator,
                $this->aggregateOrderBy ? ' WITHIN GROUP (' . $this->aggregateOrderBy->sql($binder) . ')' : '',
            ),
            default => $this->_name . sprintf(
                '(%s, %s%s)',
                $value,
                $separator,
                $this->aggregateOrderBy ? ' ' . $this->aggregateOrderBy->sql($binder) : '',
            ),
        };

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
        if ($this->aggregateOrderBy !== null) {
            $callback($this->aggregateOrderBy);
            $this->aggregateOrderBy->traverse($callback);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        $count = parent::count();
        if ($this->aggregateOrderBy !== null) {
            $count += 1;
        }

        return $count;
    }

    /**
     * Clone this object and its subtree of expressions.
     */
    public function __clone()
    {
        parent::__clone();
        if ($this->aggregateOrderBy !== null) {
            $this->aggregateOrderBy = clone $this->aggregateOrderBy;
        }
    }

    /**
     * Converts a function argument into SQL.
     *
     * @param mixed $part Function argument.
     * @param \Cake\Database\ValueBinder $binder Value binder.
     * @return string
     */
    protected function stringifyPart(mixed $part, ValueBinder $binder): string
    {
        if ($part instanceof Query) {
            return sprintf('(%s)', $part->sql($binder));
        }
        if ($part instanceof ExpressionInterface) {
            return $part->sql($binder);
        }
        if (is_array($part)) {
            $placeholder = $binder->placeholder('param');
            $binder->bind($placeholder, $part['value'], $part['type']);

            return $placeholder;
        }

        return (string)$part;
    }
}
