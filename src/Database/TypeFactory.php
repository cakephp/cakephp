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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Cake\Database\Type\BinaryType;
use Cake\Database\Type\BinaryUuidType;
use Cake\Database\Type\BoolType;
use Cake\Database\Type\DateTimeFractionalType;
use Cake\Database\Type\DateTimeTimezoneType;
use Cake\Database\Type\DateTimeType;
use Cake\Database\Type\DateType;
use Cake\Database\Type\DecimalType;
use Cake\Database\Type\FloatType;
use Cake\Database\Type\IntegerType;
use Cake\Database\Type\JsonType;
use Cake\Database\Type\StringType;
use Cake\Database\Type\TimeType;
use Cake\Database\Type\UuidType;

/**
 * Factory for building database type classes.
 */
class TypeFactory
{
    /**
     * List of supported database types. A human-readable
     * identifier is used as key and a complete namespaced class name as value
     * representing the class that will do actual type conversions.
     *
     * @var array<string, class-string<\Cake\Database\TypeInterface>>
     */
    protected static array $types = [
        'biginteger' => IntegerType::class,
        'binary' => BinaryType::class,
        'binaryuuid' => BinaryUuidType::class,
        'varbinary' => BinaryType::class,
        'boolean' => BoolType::class,
        'char' => StringType::class,
        'cidr' => StringType::class,
        'citext' => StringType::class,
        'date' => DateType::class,
        'datetime' => DateTimeType::class,
        'datetimefractional' => DateTimeFractionalType::class,
        'decimal' => DecimalType::class,
        'float' => FloatType::class,
        'geometry' => StringType::class,
        'integer' => IntegerType::class,
        'inet' => StringType::class,
        'json' => JsonType::class,
        'linestring' => StringType::class,
        'macaddr' => StringType::class,
        'nativeuuid' => UuidType::class,
        'point' => StringType::class,
        'polygon' => StringType::class,
        'smallinteger' => IntegerType::class,
        'string' => StringType::class,
        'text' => StringType::class,
        'time' => TimeType::class,
        'timestamp' => DateTimeType::class,
        'timestampfractional' => DateTimeFractionalType::class,
        'timestamptimezone' => DateTimeTimezoneType::class,
        'tinyinteger' => IntegerType::class,
        'uuid' => UuidType::class,
        'year' => IntegerType::class,
    ];

    /**
     * Contains a map of type object instances to be reused if needed.
     *
     * @var array<\Cake\Database\TypeInterface>
     */
    protected static array $builtTypes = [];

    /**
     * Returns a Type object capable of converting a type identified by name.
     *
     * @param string $name type identifier
     * @return \Cake\Database\TypeInterface
     */
    public static function build(string $name): TypeInterface
    {
        if (isset(static::$builtTypes[$name])) {
            return static::$builtTypes[$name];
        }
        if (!isset(static::$types[$name])) {
            return static::$builtTypes[$name] = new static::$types['string']($name);
        }

        return static::$builtTypes[$name] = new static::$types[$name]($name);
    }

    /**
     * Returns an arrays with all the mapped type objects, indexed by name.
     *
     * @return array<\Cake\Database\TypeInterface>
     */
    public static function buildAll(): array
    {
        foreach (static::$types as $name => $type) {
            static::$builtTypes[$name] ??= static::build($name);
        }

        return static::$builtTypes;
    }

    /**
     * Set TypeInterface instance capable of converting a type identified by $name
     *
     * @param string $name The type identifier you want to set.
     * @param \Cake\Database\TypeInterface $instance The type instance you want to set.
     * @return void
     */
    public static function set(string $name, TypeInterface $instance): void
    {
        static::$builtTypes[$name] = $instance;
    }

    /**
     * Registers a new type identifier and maps it to a fully namespaced classname.
     *
     * @param string $type Name of type to map.
     * @param class-string<\Cake\Database\TypeInterface> $className The classname to register.
     * @return void
     */
    public static function map(string $type, string $className): void
    {
        static::$types[$type] = $className;
        unset(static::$builtTypes[$type]);
    }

    /**
     * Set type to classname mapping.
     *
     * @param array<string, class-string<\Cake\Database\TypeInterface>> $map List of types to be mapped.
     * @return void
     */
    public static function setMap(array $map): void
    {
        static::$types = $map;
        static::$builtTypes = [];
    }

    /**
     * Get the type mapping array.
     *
     * @return array<string, class-string<\Cake\Database\TypeInterface>>
     */
    public static function getMap(): array
    {
        return static::$types;
    }

    /**
     * Get mapped class name for a specific type.
     *
     * @param string $type Type name to get mapped class for.
     * @return class-string<\Cake\Database\TypeInterface>|null Configured class name for given $type or null if not found.
     */
    public static function getMapped(string $type): ?string
    {
        return static::$types[$type] ?? null;
    }

    /**
     * Clears out all created instances and mapped types classes, useful for testing
     *
     * @return void
     */
    public static function clear(): void
    {
        static::$types = [];
        static::$builtTypes = [];
    }
}
