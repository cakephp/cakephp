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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Type;

use Cake\Database\Driver;

/**
 * An interface used by Type objects to signal whether the value should
 * be converted to an ExpressionInterface instead of a string when sent
 * to the database.
 */
interface ExpressionTypeInterface
{

    /**
     * Returns an ExpressionInterface object for the given value that can
     * be used in queries.
     *
     * @return \Cake\Database\ExpressionInterface
     */
    public function toExpression($value, Driver $driver);
}
