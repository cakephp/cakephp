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
use Cake\Database\Type;
use InvalidArgumentException;
use JsonSerializable;
use PDO;

/**
 * Json type converter.
 *
 * Use to convert json data between PHP and the database types.
 */
class JsonType extends Type
{

    /**
     * Convert a value data into a json string
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return string|null
     */
    public function toDatabase($value, Driver $driver)
    {
        if (is_resource($value)) {
            throw new InvalidArgumentException('Cannot convert a resource value to JSON');
        }

        return json_encode($value);
    }

    /**
     * Convert string values to PHP strings.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return string|null|array
     */
    public function toPHP($value, Driver $driver)
    {
        return json_decode($value, true);
    }

    /**
     * Get the correct PDO binding type for string data.
     *
     * @param mixed $value The value being bound.
     * @param \Cake\Database\Driver $driver The driver.
     * @return int
     */
    public function toStatement($value, Driver $driver)
    {
        return PDO::PARAM_STR;
    }

    /**
     * Marshalls request data into PHP strings.
     *
     * @param mixed $value The value to convert.
     * @return mixed Converted value.
     */
    public function marshal($value)
    {
        return $value;
    }
}
