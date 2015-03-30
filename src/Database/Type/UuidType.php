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
use Cake\Database\Type;
use Cake\Utility\Text;
use PDO;

/**
 * Provides behavior for the uuid type
 */
class UuidType extends Type
{

    /**
     * Casts give value to Statement equivalent
     *
     * @param mixed $value value to be converted to PHP equivalent
     * @param Driver $driver object from which database preferences and configuration will be extracted
     * @return mixed
     */
    public function toStatement($value, Driver $driver)
    {
        if ($value === null) {
            return PDO::PARAM_NULL;
        }
        return PDO::PARAM_STR;
    }

    /**
     * Casts given value from a PHP type to one acceptable by database
     *
     * @param mixed $value value to be converted to database equivalent
     * @param Driver $driver object from which database preferences and configuration will be extracted
     * @return mixed
     */
    public function toDatabase($value, Driver $driver)
    {
        if ($value === null || $value === '') {
            return null;
        }
        return strval($value);
    }

    /**
     * Casts given value from a database type to PHP equivalent
     *
     * @param mixed $value value to be converted to PHP equivalent
     * @param Driver $driver object from which database preferences and configuration will be extracted
     * @return mixed
     */
    public function toPHP($value, Driver $driver)
    {
        if ($value === null) {
            return null;
        }
        return strval($value);
    }

    /**
     * Generate a new UUID
     *
     * @return string A new primary key value.
     */
    public function newId()
    {
        return Text::uuid();
    }
}
