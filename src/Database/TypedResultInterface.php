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
 * @since         3.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

/**
 * Represents an expression that is known to return a specific type
 */
interface TypedResultInterface
{

    /**
     * Sets the type of the value this object will generate.
     * If called without arguments, returns the current known type
     *
     * @param string|null $type The name of the type that is to be returned
     * @return string|$this
     */
    public function returnType($type = null);
}
