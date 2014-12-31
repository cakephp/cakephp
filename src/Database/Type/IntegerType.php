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
namespace Cake\Database\Type;

use Cake\Database\Driver;
use PDO;

/**
 * Integer type converter.
 *
 * Use to convert integer data between PHP and the database types.
 */
class IntegerType extends \Cake\Database\Type
{

    /**
     * Convert integer data into the database format.
     *
     * @param string|resource $value The value to convert.
     * @param Driver $driver The driver instance to convert with.
     * @return string|resource
     */
    public function toDatabase($value, Driver $driver)
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int)$value;
    }

    /**
     * Convert integer values to PHP integers
     *
     * @param null|string|resource $value The value to convert.
     * @param Driver $driver The driver instance to convert with.
     * @return resource
     * @throws \Cake\Core\Exception\Exception
     */
    public function toPHP($value, Driver $driver)
    {
        if ($value === null) {
            return null;
        }
        return (int)$value;
    }

    /**
     * Get the correct PDO binding type for integer data.
     *
     * @param mixed $value The value being bound.
     * @param Driver $driver The driver.
     * @return int
     */
    public function toStatement($value, Driver $driver)
    {
        return PDO::PARAM_INT;
    }

    /**
     * Marshalls request data into PHP floats.
     *
     * @param mixed $value The value to convert.
     * @return mixed Converted value.
     */
    public function marshal($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (ctype_digit($value)) {
            return (int)$value;
        }
        return $value;
    }
}
