<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

/**
 * An interface used by Expression objects.
 */
interface ExpressionInterface
{

    /**
     * Converts the Node into a SQL string fragment.
     *
     * @param \Cake\Database\ValueBinder $generator Placeholder generator object
     * @return string
     */
    public function sql(ValueBinder $generator);

    /**
     * Iterates over each part of the expression recursively for every
     * level of the expressions tree and executes the $visitor callable
     * passing as first parameter the instance of the expression currently
     * being iterated.
     *
     * @param callable $visitor The callable to apply to all nodes.
     * @return void
     */
    public function traverse(callable $visitor);
}
