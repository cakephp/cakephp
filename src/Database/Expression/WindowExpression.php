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
use function Cake\Core\deprecationWarning;

/**
 * This represents a SQL window expression used by aggregate and window functions.
 */
class WindowExpression implements ExpressionInterface, WindowInterface
{
    /**
     * @var \Cake\Database\Expression\IdentifierExpression
     */
    protected IdentifierExpression $name;

    /**
     * @var array<\Cake\Database\ExpressionInterface>
     */
    protected array $partitions = [];

    /**
     * @var \Cake\Database\Expression\OrderByExpression|null
     */
    protected ?OrderByExpression $order = null;

    /**
     * @var array|null
     */
    protected ?array $frame = null;

    /**
     * @var string|null
     */
    protected ?string $exclusion = null;

    /**
     * @param string $name Window name
     */
    public function __construct(string $name = '')
    {
        $this->name = new IdentifierExpression($name);
    }

    /**
     * Return whether is only a named window expression.
     *
     * These window expressions only specify a named window and do not
     * specify their own partitions, frame or order.
     *
     * @return bool
     */
    public function isNamedOnly(): bool
    {
        return $this->name->getIdentifier() && (!$this->partitions && !$this->frame && !$this->order);
    }

    /**
     * Sets the window name.
     *
     * @param string $name Window name
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = new IdentifierExpression($name);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function partition(ExpressionInterface|Closure|array|string $partitions)
    {
        if (!$partitions) {
            return $this;
        }

        if ($partitions instanceof Closure) {
            $partitions = $partitions(new QueryExpression([], [], ''));
        }

        if (!is_array($partitions)) {
            $partitions = [$partitions];
        }

        foreach ($partitions as &$partition) {
            if (is_string($partition)) {
                $partition = new IdentifierExpression($partition);
            }
        }

        $this->partitions = array_merge($this->partitions, $partitions);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function order(ExpressionInterface|Closure|array|string $fields)
    {
        deprecationWarning(
            '5.0.0',
            'WindowExpression::order() is deprecated. Use WindowExpression::orderBy() instead.',
        );

        return $this->orderBy($fields);
    }

    /**
     * @inheritDoc
     */
    public function orderBy(ExpressionInterface|Closure|array|string $fields)
    {
        if (!$fields) {
            return $this;
        }

        $this->order ??= new OrderByExpression();

        if ($fields instanceof Closure) {
            $fields = $fields(new QueryExpression([], [], ''));
        }

        $this->order->add($fields);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function range(ExpressionInterface|string|int|null $start, ExpressionInterface|string|int|null $end = 0)
    {
        return $this->frame(self::RANGE, $start, self::PRECEDING, $end, self::FOLLOWING);
    }

    /**
     * @inheritDoc
     */
    public function rows(?int $start, ?int $end = 0)
    {
        return $this->frame(self::ROWS, $start, self::PRECEDING, $end, self::FOLLOWING);
    }

    /**
     * @inheritDoc
     */
    public function groups(?int $start, ?int $end = 0)
    {
        return $this->frame(self::GROUPS, $start, self::PRECEDING, $end, self::FOLLOWING);
    }

    /**
     * @inheritDoc
     */
    public function frame(
        string $type,
        ExpressionInterface|string|int|null $startOffset,
        string $startDirection,
        ExpressionInterface|string|int|null $endOffset,
        string $endDirection,
    ) {
        $this->frame = [
            'type' => $type,
            'start' => [
                'offset' => $startOffset,
                'direction' => $startDirection,
            ],
            'end' => [
                'offset' => $endOffset,
                'direction' => $endDirection,
            ],
        ];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeCurrent()
    {
        $this->exclusion = 'CURRENT ROW';

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeGroup()
    {
        $this->exclusion = 'GROUP';

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeTies()
    {
        $this->exclusion = 'TIES';

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $clauses = [];
        if ($this->name->getIdentifier()) {
            $clauses[] = $this->name->sql($binder);
        }

        if ($this->partitions) {
            $expressions = [];
            foreach ($this->partitions as $partition) {
                $expressions[] = $partition->sql($binder);
            }

            $clauses[] = 'PARTITION BY ' . implode(', ', $expressions);
        }

        if ($this->order) {
            $clauses[] = $this->order->sql($binder);
        }

        if ($this->frame) {
            $start = $this->buildOffsetSql(
                $binder,
                $this->frame['start']['offset'],
                $this->frame['start']['direction'],
            );
            $end = $this->buildOffsetSql(
                $binder,
                $this->frame['end']['offset'],
                $this->frame['end']['direction'],
            );

            $frameSql = sprintf('%s BETWEEN %s AND %s', $this->frame['type'], $start, $end);

            if ($this->exclusion !== null) {
                $frameSql .= ' EXCLUDE ' . $this->exclusion;
            }

            $clauses[] = $frameSql;
        }

        return implode(' ', $clauses);
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback)
    {
        $callback($this->name);
        foreach ($this->partitions as $partition) {
            $callback($partition);
            $partition->traverse($callback);
        }

        if ($this->order) {
            $callback($this->order);
            $this->order->traverse($callback);
        }

        if ($this->frame !== null) {
            $offset = $this->frame['start']['offset'];
            if ($offset instanceof ExpressionInterface) {
                $callback($offset);
                $offset->traverse($callback);
            }
            $offset = $this->frame['end']['offset'] ?? null;
            if ($offset instanceof ExpressionInterface) {
                $callback($offset);
                $offset->traverse($callback);
            }
        }

        return $this;
    }

    /**
     * Builds frame offset sql.
     *
     * @param \Cake\Database\ValueBinder $binder Value binder
     * @param \Cake\Database\ExpressionInterface|string|int|null $offset Frame offset
     * @param string $direction Frame offset direction
     * @return string
     */
    protected function buildOffsetSql(
        ValueBinder $binder,
        ExpressionInterface|string|int|null $offset,
        string $direction,
    ): string {
        if ($offset === 0) {
            return 'CURRENT ROW';
        }

        if ($offset instanceof ExpressionInterface) {
            $offset = $offset->sql($binder);
        }

        return sprintf(
            '%s %s',
            $offset ?? 'UNBOUNDED',
            $direction,
        );
    }

    /**
     * Clone this object and its subtree of expressions.
     *
     * @return void
     */
    public function __clone()
    {
        $this->name = clone $this->name;
        foreach ($this->partitions as $i => $partition) {
            $this->partitions[$i] = clone $partition;
        }
        if ($this->order !== null) {
            $this->order = clone $this->order;
        }
    }
}
