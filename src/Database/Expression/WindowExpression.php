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
 * This represents a SQL window expression used by aggregate and window functions.
 */
class WindowExpression implements ExpressionInterface, WindowInterface
{
    /**
     * @var \Cake\Database\ExpressionInterface[]
     */
    protected $partitions = [];

    /**
     * @var \Cake\Database\Expression\OrderByExpression|null
     */
    protected $order;

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
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function rows(?int $start, ?int $end = 0)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function groups(?int $start, ?int $end = 0)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeCurrent()
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeGroup()
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeTies()
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludeNoOthers()
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $generator): string
    {
        $partitionSql = '';
        if ($this->partitions) {
            $expressions = [];
            foreach ($this->partitions as $partition) {
                $expressions[] = $partition->sql($generator);
            }

            $partitionSql = 'PARTITION BY ' . implode(', ', $expressions);
        }

        return sprintf(
            'OVER (%s%s%s)',
            $partitionSql,
            $partitionSql && $this->order ? ' ' : '',
            $this->order ? $this->order->sql($generator) : ''
        );
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
}
