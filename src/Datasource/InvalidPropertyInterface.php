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
 */
interface InvalidPropertyInterface
{
    /**
     * Sets a field as invalid and not patchable into the entity.
     *
     * This is useful for batch operations when one needs to get the original value for an error message after patching.
     * This value could not be patched into the entity and is simply copied into the _invalid property for debugging purposes
     * or to be able to log it away.
     *
     * @param string|array|null $field The field to get invalid value for, or the value to set.
     * @param mixed|null $value The invalid value to be set for $field.
     * @param bool $overwrite Whether or not to overwrite pre-existing values for $field.
     * @return $this|mixed
     */
    public function invalid($field = null, $value = null, $overwrite = false);
}
