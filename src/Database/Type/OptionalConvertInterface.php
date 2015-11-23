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
namespace Cake\Database\Type;

/**
 * An interface used by Type objects to signal whether the casting
 * is actually required.
 */
interface OptionalConvertInterface
{

    /**
     * Returns whehter the cast to PHP is required to be invoked, since
     * it is not a indentity function.
     *
     * @return bool
     */
    public function requiresToPhpCast();
}
