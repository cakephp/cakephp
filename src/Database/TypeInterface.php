<?php
declare(strict_types=1);

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
 * @since         3.2.14
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

/**
 * Encapsulates all conversion functions for values coming from a database into PHP and
 * going from PHP into a database.
 */
interface TypeInterface
{
    /**
     * Casts given value from a PHP type to one acceptable by a database.
     *
     * @param mixed $value Value to be converted to a database equivalent.
     * @param \Cake\Database\Driver $driver Object from which database preferences and configuration will be extracted.
     * @return mixed Given PHP type casted to one acceptable by a database.
     */
    public function toDatabase(mixed $value, Driver $driver): mixed;

    /**
     * Casts given value from a database type to a PHP equivalent.
     *
     * @param mixed $value Value to be converted to PHP equivalent
     * @param \Cake\Database\Driver $driver Object from which database preferences and configuration will be extracted
     * @return mixed Given value casted from a database to a PHP equivalent.
     */
    public function toPHP(mixed $value, Driver $driver): mixed;

    /**
     * Get the binding type to use in a PDO statement.
     *
     * @param mixed $value The value being bound.
     * @param \Cake\Database\Driver $driver Object from which database preferences and configuration will be extracted.
     * @return int One of PDO::PARAM_* constants.
     */
    public function toStatement(mixed $value, Driver $driver): int;

    /**
     * Marshals flat data into PHP objects.
     *
     * Most useful for converting request data into PHP objects,
     * that make sense for the rest of the ORM/Database layers.
     *
     * @param mixed $value The value to convert.
     * @return mixed Converted value.
     */
    public function marshal(mixed $value): mixed;

    /**
     * Returns the base type name that this class is inheriting.
     *
     * This is useful when extending base type for adding extra functionality,
     * but still want the rest of the framework to use the same assumptions it would
     * do about the base type it inherits from.
     *
     * @return string|null The base type name that this class is inheriting.
     */
    public function getBaseType(): ?string;

    /**
     * Returns type identifier name for this object.
     *
     * @return string|null The type identifier name for this object.
     */
    public function getName(): ?string;

    /**
     * Generate a new primary key value for a given type.
     *
     * This method can be used by types to create new primary key values
     * when entities are inserted.
     *
     * @return mixed A new primary key value.
     * @see \Cake\Database\Type\UuidType
     */
    public function newId(): mixed;
}
