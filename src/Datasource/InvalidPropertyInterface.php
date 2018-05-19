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
namespace Cake\Datasource;

/**
 * Describes the methods that any class representing a data storage should
 * comply with.
 *
 * @method array getInvalid()
 * @method mixed getInvalidField($field)
 * @method $this setInvalid($field, $value = null, $overwrite = false)
 * @method $this setInvalidField($field, $value = null, $overwrite = false)
 */
interface InvalidPropertyInterface
{
}
