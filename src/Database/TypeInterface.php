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
 * @since         3.2.13
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

/**
 * Encapsulates all conversion functions for values coming from database into PHP and
 * going from PHP into database.
 */
interface TypeInterface
{

    /**
     * Casts given value from a PHP type to one acceptable by database
     *
     * @param mixed $value value to be converted to database equivalent
     * @param \Cake\Database\Driver $driver object from which database preferences and configuration will be extracted
     * @return mixed
     */
    public function toDatabase($value, Driver $driver);

    /**
     * Casts given value from a database type to PHP equivalent
     *
     * @param mixed $value value to be converted to PHP equivalent
     * @param \Cake\Database\Driver $driver object from which database preferences and configuration will be extracted
     * @return mixed
     */
    public function toPHP($value, Driver $driver);

    /**
     * Casts given value to Statement equivalent
     *
     * @param mixed $value value to be converted to PDO statement
     * @param \Cake\Database\Driver $driver object from which database preferences and configuration will be extracted
     * @return mixed
     */
    public function toStatement($value, Driver $driver);

    /**
     * Marshalls flat data into PHP objects.
     *
     * Most useful for converting request data into PHP objects
     * that make sense for the rest of the ORM/Database layers.
     *
     * @param mixed $value The value to convert.
     * @return mixed Converted value.
     */
    public function marshal($value);

    /**
     * Returns the base type name that this class is inheriting.
     * This is useful when extending base type for adding extra functionality
     * but still want the rest of the framework to use the same assumptions it would
     * do about the base type it inherits from.
     *
     * @return string
     */
    public function getBaseType();
}
