<?php
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
 * @since         3.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Type;

/**
 * An interface used by Type objects to signal whether the casting
 * is actually required.
 */
interface OptionalConvertInterface
{

    /**
     * Returns whether the cast to PHP is required to be invoked, since
     * it is not a identity function.
     *
     * @return bool
     */
    public function requiresToPhpCast();
}
