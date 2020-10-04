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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Closure;

/**
 * An interface used by Expression objects.
 */
interface ExpressionInterface
{
    /**
     * Converts the Node into a SQL string fragment.
     *
     * @param \Cake\Database\ValueBinder $binder Parameter binder
     * @return string
     */
    public function sql(ValueBinder $binder): string;

    /**
     * Iterates over each part of the expression recursively for every
     * level of the expressions tree and executes the $callback callable
     * passing as first parameter the instance of the expression currently
     * being iterated.
     *
     * @param \Closure $callback The callable to apply to all nodes.
     * @return $this
     */
    public function traverse(Closure $callback);
}
