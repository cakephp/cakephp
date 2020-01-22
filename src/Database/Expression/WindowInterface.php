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

/**
 * This defines the functions used for building window expressions.
 */
interface WindowInterface
{
    /**
     * @var int
     */
    public const PRECEDING = 0;

    /**
     * @var int
     */
    public const FOLLOWING = 1;

    /**
     * @var int
     */
    public const RANGE = 0;

    /**
     * @var int
     */
    public const ROWS = 1;

    /**
     * @var int
     */
    public const GROUPS = 2;

    /**
     * Adds one or more partition expressions to the window.
     *
     * @param (\Cake\Database\ExpressionInterface|string)[]|\Cake\Database\ExpressionInterface|string $partitions Partition expressions
     * @return $this
     */
    public function partition($partitions);

    /**
     * Adds one or more order clauses to the window.
     *
     * @param (\Cake\Database\ExpressionInterface|string)[]|\Cake\Database\ExpressionInterface|string $fields Order expressions
     * @return $this
     */
    public function order($fields);

    /**
     * Adds a simple range frame to the window.
     *
     * `$start`:
     *  - `0` - 'CURRENT ROW'
     *  - `null` - 'UNBOUNDED PRECEDING'
     *  - offset - 'offset PRECEDING'
     *
     * `$end`:
     *  - `0` - 'CURRENT ROW'
     *  - `null` - 'UNBOUNDED FOLLOWING'
     *  - offset - 'offset FOLLOWING'
     *
     * If you need to use 'FOLLOWING' with frame start or
     * 'PRECEDING' with frame end, use `frame()` instead.
     *
     * @param int|null $start Frame start
     * @param int|null $end Frame end
     *  If not passed in, only frame start SQL will be generated.
     * @return $this
     */
    public function range(?int $start, ?int $end = 0);

    /**
     * Adds a simple rows frame to the window.
     *
     * See `range()` for details.
     *
     * @param int|null $start Frame start
     * @param int|null $end Frame end
     *  If not passed in, only frame start SQL will be generated.
     * @return $this
     */
    public function rows(?int $start, ?int $end = 0);

    /**
     * Adds a simple groups frame to the window.
     *
     * See `range()` for details.
     *
     * @param int|null $start Frame start
     * @param int|null $end Frame end
     *  If not passed in, only frame start SQL will be generated.
     * @return $this
     */
    public function groups(?int $start, ?int $end = 0);

    /**
     * @param int $type Frame type
     * @param string|int|null $startOffset Frame start offset
     * @param int $startDirection Frame start direction
     * @param string|int|null $endOffset Frame end offset
     * @param int $endDirection Frame end direction
     * @return $this
     * @throws \InvalidArgumentException When wrong types are used or offsets are negative.
     */
    public function frame(
        int $type,
        $startOffset,
        int $startDirection,
        $endOffset = null,
        int $endDirection = self::FOLLOWING
    );

    /**
     * Adds current row frame exclusion.
     *
     * @return $this
     */
    public function excludeCurrent();

    /**
     * Adds group frame exclusion.
     *
     * @return $this
     */
    public function excludeGroup();

    /**
     * Adds ties frame exclusion.
     *
     * @return $this
     */
    public function excludeTies();

    /**
     * Adds no others frame exclusion.
     *
     * @return $this
     */
    public function excludeNoOthers();
}
