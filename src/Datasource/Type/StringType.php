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
 * String type converter.
 *
 * Use to convert string data between PHP and the database types.
 */
class StringType extends Type
{

    /**
     * Convert string data into the database format.
     *
     * @param mixed $value The value to convert.
     * @return string|null
     */
    public function toDatasource($value)
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return $value->__toString();
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        throw new InvalidArgumentException('Cannot convert value to string');
    }

    /**
     * Convert string values to PHP integers
     *
     * @param mixed $value The value to convert.
     * @return string|null
     */
    public function toPHP($value)
    {
        if ($value === null) {
            return null;
        }
        return (string)$value;
    }

    /**
     * Marshalls request data into PHP strings.
     *
     * @param mixed $value The value to convert.
     * @return mixed Converted value.
     */
    public function marshal($value)
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            return '';
        }
        return (string)$value;
    }
}
