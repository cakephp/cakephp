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
     * `$start` assumes `PRECEDING`, and `$end` assumes `FOLLOWING. Both can be
     * overriden by passing an array with the order as the key. The SQL standard
     * for ordering must be followed.
     *
     * ```
     * // this is the same as '1 FOLLOWING`
     * $window->range(['following' => 1]);
     * ```
     *
     * The SQL keywords `UNBOUNDED` and `CURRENT ROW` can be used directly or
     * easier to read substitutes `null` and `0` instead.
     *
     * ```
     * // this is the same as 'CURRENT ROW'
     * $window->range(0);
     *
     * // this is the same as 'UNBOUNDED PRECEDING'
     * $window->range(null)
     * ```
     *
     * @param array|int|string|null $start Frame start
     * @param array|int|string|null $end Frame end
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
     * @param array|int|string|null $start Frame start
     * @param array|int|string|null $end Frame end
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
     * Known exclusion keywords are:
     *  - CURRENT ROW
     *  - GROUP
     *  - TIES
     *  - NO OTHERS
     *
     * @param string $exclusion Frame exclusion
     * @return $this
     */
    public function exclude(string $exclusion);
}
