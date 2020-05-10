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
use InvalidArgumentException;

/**
 * This represents a SQL window expression used by aggregate and window functions.
 */
class WindowExpression implements ExpressionInterface, WindowInterface
{
    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var \Cake\Database\ExpressionInterface[]
     */
    protected $partitions = [];

    /**
     * @var \Cake\Database\Expression\OrderByExpression|null
     */
    protected $order;

    /**
     * @var array|null
     */
    protected $frame;

    /**
     * @var string|null
     */
    protected $exclusion;

    /**
     * @param string|null $name Window name
     */
    public function __construct(?string $name = null)
    {
        $this->name = $name;
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
        return $this->name && (!$this->partitions && !$this->frame && !$this->order);
    }

    /**
     * Sets the window name.
     *
     * @param string $name Window name
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function partition($partitions)
    {
        if (!$partitions) {
            return $this;
        }

        if (!is_array($partitions)) {
            $partitions = [$partitions];
        }

        foreach ($partitions as &$partition) {
            if (is_string($partition)) {
                $partition = new IdentifierExpression($partition);
            }
        }

        /** @psalm-suppress InvalidPropertyAssignmentValue */
        $this->partitions = array_merge($this->partitions, $partitions);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function order($fields)
    {
        if (!$fields) {
            return $this;
        }

        if ($this->order === null) {
            $this->order = new OrderByExpression();
        }

        $this->order->add($fields);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function range(?int $start, ?int $end = 0)
    {
        if (func_num_args() === 1) {
            return $this->frame(self::RANGE, $start, self::PRECEDING);
        }

        return $this->frame(self::RANGE, $start, self::PRECEDING, $end, self::FOLLOWING);
    }

    /**
     * @inheritDoc
     */
    public function rows(?int $start, ?int $end = 0)
    {
        if (func_num_args() === 1) {
            return $this->frame(self::ROWS, $start, self::PRECEDING);
        }

        return $this->frame(self::ROWS, $start, self::PRECEDING, $end, self::FOLLOWING);
    }

    /**
     * @inheritDoc
     */
    public function groups(?int $start, ?int $end = 0)
    {
        if (func_num_args() === 1) {
            return $this->frame(self::GROUPS, $start, self::PRECEDING);
        }

        return $this->frame(self::GROUPS, $start, self::PRECEDING, $end, self::FOLLOWING);
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
        if ($startOffset < 0 || $endOffset < 0) {
            throw new InvalidArgumentException('Frame offsets must be non-negative.');
        }

        $this->frame = [
            'type' => $type,
            'start' => [
                'offset' => $startOffset,
                'direction' => $startDirection,
            ],
        ];

        if (func_num_args() > 3) {
            $this->frame['end'] = [
                'offset' => $endOffset,
                'direction' => $endDirection,
            ];
        }

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
    public function sql(ValueBinder $generator): string
    {
        $clauses = [];
        if ($this->name) {
            $clauses[] = $this->name;
        }

        if ($this->partitions) {
            $expressions = [];
            foreach ($this->partitions as $partition) {
                $expressions[] = $partition->sql($generator);
            }

            $clauses[] = 'PARTITION BY ' . implode(', ', $expressions);
        }

        if ($this->order) {
            $clauses[] = $this->order->sql($generator);
        }

        if ($this->frame) {
            $offset = $this->buildOffsetSql(
                $generator,
                $this->frame['start']['offset'],
                $this->frame['start']['direction']
            );

            $frameSql = sprintf(
                '%s %s%s',
                $this->frame['type'],
                isset($this->frame['end']) ? 'BETWEEN ' : '',
                $offset
            );

            if (isset($this->frame['end'])) {
                $offset = $this->buildOffsetSql(
                    $generator,
                    $this->frame['end']['offset'],
                    $this->frame['end']['direction']
                );

                $frameSql .= ' AND ' . $offset;
            }

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
    public function traverse(Closure $visitor)
    {
        foreach ($this->partitions as $partition) {
            $visitor($partition);
            $partition->traverse($visitor);
        }

        if ($this->order) {
            $visitor($this->order);
            $this->order->traverse($visitor);
        }

        return $this;
    }

    /**
     * Builds frame offset sql.
     *
     * @param \Cake\Database\ValueBinder $generator Value binder
     * @param int|null $offset Frame offset
     * @param string $direction Frame offset direction
     * @return string
     */
    protected function buildOffsetSql(ValueBinder $generator, ?int $offset, string $direction): string
    {
        if ($offset === 0) {
            return 'CURRENT ROW';
        }

        $sql = sprintf(
            '%s %s',
            $offset ?? 'UNBOUNDED',
            $direction
        );

        return $sql;
    }

    /**
     * Clone this object and its subtree of expressions.
     *
     * @return void
     */
    public function __clone()
    {
        foreach ($this->partitions as $i => $partition) {
            $this->partitions[$i] = clone $partition;
        }
        if ($this->order !== null) {
            $this->order = clone $this->order;
        }
    }
}
