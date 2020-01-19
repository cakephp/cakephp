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
     * 'CURRENT ROW' frame start, end or exclusion
     *
     * @var int
     */
    public const CURRENT_ROW = 0;

    /**
     * 'UNBOUNDED PRECEDING' and '(offset) PRECEDING' frame start or end
     *
     * @var int
     */
    public const PRECEDING = 1;

    /**
     * 'UNBOUNDED FOLLOWING' and '(offset) FOLLOWING' frame start or end
     *
     * @var int
     */
    public const FOLLOWING = 2;

    /**
     * 'GROUP' frame exclusion
     *
     * @var int
     */
    public const GROUP = 1;

    /**
     * 'TIES' frame exclusion
     *
     * @var int
     */
    public const TIES = 2;

    /**
     * 'NO OTHERS' frame exclusion
     *
     * @var int
     */
    public const NO_OTHERS = 3;

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
     * Adds a range frame clause to the window. Only one frame clause can be
     * specified per window.
     *
     * `$start` assumes `PRECEDING`, and `$end` assumes `FOLLOWING`. Both can be
     * overriden by passing an array with the order as the key. The SQL standard
     * for ordering must be followed.
     *
     * ```
     * // this is produces 'ROWS BETWEEN 1 PRECEDING AND 2 FOLLOWING'
     * $window->rows(1, 2);
     *
     * // this is the same as 'ROWS 1 FOLLOWING`
     * $window->rows([WindowInterface::FOLLOWING => 1]);
     * ```
     *
     * You can use `null` for 'UNBOUNDED' and `0` for 'CURRENT ROW'.
     *
     * ```
     * // this is produces 'ROWS CURRENT ROW'
     * $window->rows(0);
     *
     * // this is produces 'ROWS UNBOUNDED PRECEDING'
     * $window->rows(null)
     * ```
     *
     * @param array|int|null $start Frame start
     * @param array|int|null $end Frame end
     *  If not passed in, only frame start SQL will be generated.
     * @return $this
     */
    public function range($start, $end = 0);

    /**
     * Adds a rows frame clause to the window. Only one frame clause can be
     * specified per window.
     *
     * See `range()` for details on `$start` and `$end` format.
     *
     * @param array|int|null $start Frame start
     * @param array|int|null $end Frame end
     *  If not passed in, only frame start SQL will be generated.
     * @return $this
     */
    public function rows($start, $end = 0);

    /**
     * Adds a groups frame clause to the window. Only one frame clause can be
     * specified per window.
     *
     * See `range()` for details on `$start` and `$end` format.
     *
     * @param array|int|string|null $start Frame start
     * @param array|int|string|null $end Frame end
     *  If not passed in, only frame start SQL will be generated.
     * @return $this
     */
    public function groups($start, $end = 0);

    /**
     * Adds a frame exclusion to the window.
     *
     * @param int $exclusion Frame exclusion
     * @return $this
     */
    public function exclude(int $exclusion);
}
