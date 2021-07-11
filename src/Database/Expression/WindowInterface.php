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
     * @var string
     */
    public const PRECEDING = 'PRECEDING';

    /**
     * @var string
     */
    public const FOLLOWING = 'FOLLOWING';

    /**
     * @var string
     */
    public const RANGE = 'RANGE';

    /**
     * @var string
     */
    public const ROWS = 'ROWS';

    /**
     * @var string
     */
    public const GROUPS = 'GROUPS';

    /**
     * Adds one or more partition expressions to the window.
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array<\Cake\Database\ExpressionInterface|string>|string $partitions Partition expressions
     * @return $this
     */
    public function partition($partitions);

    /**
     * Adds one or more order clauses to the window.
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array<\Cake\Database\ExpressionInterface|string>|string $fields Order expressions
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
     * @param \Cake\Database\ExpressionInterface|string|int|null $start Frame start
     * @param \Cake\Database\ExpressionInterface|string|int|null $end Frame end
     *  If not passed in, only frame start SQL will be generated.
     * @return $this
     */
    public function range($start, $end = 0);

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
     * Adds a frame to the window.
     *
     * Use the `range()`, `rows()` or `groups()` helpers if you need simple
     * 'BETWEEN offset PRECEDING and offset FOLLOWING' frames.
     *
     * You can specify any direction for both frame start and frame end.
     *
     * With both `$startOffset` and `$endOffset`:
     *  - `0` - 'CURRENT ROW'
     *  - `null` - 'UNBOUNDED'
     *
     * @param string $type Frame type
     * @param \Cake\Database\ExpressionInterface|string|int|null $startOffset Frame start offset
     * @param string $startDirection Frame start direction
     * @param \Cake\Database\ExpressionInterface|string|int|null $endOffset Frame end offset
     * @param string $endDirection Frame end direction
     * @return $this
     * @throws \InvalidArgumentException WHen offsets are negative.
     * @psalm-param self::RANGE|self::ROWS|self::GROUPS $type
     * @psalm-param self::PRECEDING|self::FOLLOWING $startDirection
     * @psalm-param self::PRECEDING|self::FOLLOWING $endDirection
     */
    public function frame(
        string $type,
        $startOffset,
        string $startDirection,
        $endOffset,
        string $endDirection
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
}
