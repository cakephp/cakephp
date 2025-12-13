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
     * @var array<string, string>
     * @phpstan-var array<string, class-string<\Cake\Database\TypeInterface>>
     */
    protected static array $_types = [
        'biginteger' => Type\IntegerType::class,
        'binary' => Type\BinaryType::class,
        'binaryuuid' => Type\BinaryUuidType::class,
        'boolean' => Type\BoolType::class,
        'char' => Type\StringType::class,
        'cidr' => Type\StringType::class,
        'citext' => Type\StringType::class,
        'date' => Type\DateType::class,
        'datetime' => Type\DateTimeType::class,
        'datetimefractional' => Type\DateTimeFractionalType::class,
        'decimal' => Type\DecimalType::class,
        'float' => Type\FloatType::class,
        'geometry' => Type\StringType::class,
        'integer' => Type\IntegerType::class,
        'inet' => Type\StringType::class,
        'json' => Type\JsonType::class,
        'linestring' => Type\StringType::class,
        'macaddr' => Type\StringType::class,
        'nativeuuid' => Type\UuidType::class,
        'point' => Type\StringType::class,
        'polygon' => Type\StringType::class,
        'smallinteger' => Type\IntegerType::class,
        'string' => Type\StringType::class,
        'text' => Type\StringType::class,
        'time' => Type\TimeType::class,
        'timestamp' => Type\DateTimeType::class,
        'timestampfractional' => Type\DateTimeFractionalType::class,
        'timestamptimezone' => Type\DateTimeTimezoneType::class,
        'tinyinteger' => Type\IntegerType::class,
        'uuid' => Type\UuidType::class,
        'year' => Type\IntegerType::class,
    ];

    /**
     * Contains a map of type object instances to be reused if needed.
     *
     * @var array<\Cake\Database\TypeInterface>
     */
    protected static array $_builtTypes = [];

    /**
     * Returns a Type object capable of converting a type identified by name.
     *
     * @param string $name type identifier
     * @return \Cake\Database\TypeInterface
     */
    public static function build(string $name): TypeInterface
    {
        if (isset(static::$_builtTypes[$name])) {
            return static::$_builtTypes[$name];
        }
        if (!isset(static::$_types[$name])) {
            return static::$_builtTypes[$name] = new static::$_types['string']($name);
        }

        return static::$_builtTypes[$name] = new static::$_types[$name]($name);
    }

    /**
     * Returns an arrays with all the mapped type objects, indexed by name.
     *
     * @return array<\Cake\Database\TypeInterface>
     */
    public static function buildAll(): array
    {
        foreach (static::$_types as $name => $type) {
            static::$_builtTypes[$name] ??= static::build($name);
        }

        return static::$_builtTypes;
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
        static::$_builtTypes[$name] = $instance;
    }

    /**
     * Registers a new type identifier and maps it to a fully namespaced classname.
     *
     * @param string $type Name of type to map.
     * @param string $className The classname to register.
     * @return void
     * @phpstan-param class-string<\Cake\Database\TypeInterface> $className
     */
    public static function map(string $type, string $className): void
    {
        static::$_types[$type] = $className;
        unset(static::$_builtTypes[$type]);
    }

    /**
     * Set type to classname mapping.
     *
     * @param array<string, string> $map List of types to be mapped.
     * @return void
     * @phpstan-param array<string, class-string<\Cake\Database\TypeInterface>> $map
     */
    public static function setMap(array $map): void
    {
        static::$_types = $map;
        static::$_builtTypes = [];
    }

    /**
     * Get the type mapping array.
     *
     * Deprecated 5.3.0: Argument $type has been deprecated.
     * Use getMap() without arguments to get the full map, or getMapped($type) to get a specific type mapping.
     *
     * @param string|null $type Type name to get mapped class for or null to get map array.
     * @return array<string, class-string<\Cake\Database\TypeInterface>>|string|null Configured class name for given $type or map array.
     */
    public static function getMap(?string $type = null): array|string|null
    {
        if ($type === null) {
            return static::$_types;
        }

        trigger_error(
            'Calling getMap() with a type argument is deprecated. Use getMapped() instead.',
            E_USER_DEPRECATED,
        );

        return static::$_types[$type] ?? null;
    }

    /**
     * Get mapped class name for a specific type.
     *
     * @param string $type Type name to get mapped class for.
     * @return string|null Configured class name for given $type or null if not found.
     * @phpstan-return class-string<\Cake\Database\TypeInterface>|null
     */
    public static function getMapped(string $type): ?string
    {
        return static::$_types[$type] ?? null;
    }

    /**
     * Clears out all created instances and mapped types classes, useful for testing
     *
     * @return void
     */
    public static function clear(): void
    {
        static::$_types = [];
        static::$_builtTypes = [];
    }
}
