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
 * @since         3.1.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource\Type;

use Cake\Datasource\Type;
use InvalidArgumentException;

/**
 * Bool type converter.
 *
 * Use to convert bool data between PHP and the database types.
 */
class BoolType extends Type
{

    /**
     * Convert bool data into the database format.
     *
     * @param mixed $value The value to convert.
     * @return bool|null
     */
    public function toDatasource($value)
    {
        if ($value === true || $value === false) {
            return $value;
        }

        if (in_array($value, [1, 0, '1', '0'], true)) {
            return (bool)$value;
        }

        throw new InvalidArgumentException('Cannot convert value to bool');
    }

    /**
     * Convert bool values to PHP booleans
     *
     * @param mixed $value The value to convert.
     * @return bool|null
     */
    public function toPHP($value)
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value) && !is_numeric($value)) {
            return strtolower($value) === 'true' ? true : false;
        }
        return !empty($value);
    }

    /**
     * Marshalls request data into PHP booleans.
     *
     * @param mixed $value The value to convert.
     * @return mixed Converted value.
     */
    public function marshal($value)
    {
        if ($value === null) {
            return null;
        }
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }
        return !empty($value);
    }
}
