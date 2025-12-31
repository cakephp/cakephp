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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use Cake\ORM\Attribute\CollectionOf;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Maps array data to DTO objects using reflection.
 *
 * This mapper enables the `projectAs()` query method to hydrate results
 * directly into DTO objects instead of entities. It uses PHP 8 features:
 *
 * - **Type hints** on constructor parameters to detect nested DTOs
 * - **#[CollectionOf]** attribute to specify DTO type for array collections
 * - **Named arguments** for construction
 *
 * ### Example DTO
 *
 * ```php
 * readonly class UserDto {
 *     public function __construct(
 *         public int $id,
 *         public string $username,
 *         public ?RoleDto $role = null,
 *         #[CollectionOf(CommentDto::class)]
 *         public array $comments = [],
 *     ) {}
 * }
 * ```
 *
 * ### Usage with Query
 *
 * ```php
 * $users = $this->Users->find()
 *     ->contain(['Roles', 'Comments'])
 *     ->projectAs(UserDto::class)
 *     ->all();
 * ```
 */
class DtoMapper
{
    /**
     * Cached reflection info per class.
     *
     * @var array<string, array{params: array<string, array{name: string, nullable: bool, hasDefault: bool, default: mixed, dtoClass: class-string|null, collectionOf: class-string|null}>}>
     */
    protected static array $cache = [];

    /**
     * Map array data to a DTO instance.
     *
     * @template T of object
     * @param array<string, mixed> $data The source data (typically from ORM)
     * @param class-string<T> $dtoClass The target DTO class
     * @return T
     */
    public function map(array $data, string $dtoClass): object
    {
        $info = $this->getClassInfo($dtoClass);

        $args = [];
        foreach ($info['params'] as $name => $paramInfo) {
            // isset() is faster than array_key_exists(), check for null separately
            if (isset($data[$name])) {
                $value = $data[$name];

                // Handle nested DTO (type hint is a class) - only map arrays, pass objects through
                if ($paramInfo['dtoClass'] !== null && is_array($value)) {
                    $value = $this->map($value, $paramInfo['dtoClass']);
                } elseif ($paramInfo['collectionOf'] !== null) {
                    // Handle collection - inline loop avoids closure creation overhead
                    $collectionClass = $paramInfo['collectionOf'];
                    $mapped = [];
                    foreach ($value as $item) {
                        $mapped[] = is_array($item) ? $this->map($item, $collectionClass) : $item;
                    }
                    $value = $mapped;
                }

                $args[$name] = $value;
            } elseif (array_key_exists($name, $data)) {
                // Value is explicitly null in data
                $args[$name] = null;
            } elseif ($paramInfo['hasDefault']) {
                $args[$name] = $paramInfo['default'];
            } elseif ($paramInfo['nullable']) {
                $args[$name] = null;
            }
            // If required and not provided, let PHP throw the error
        }

        return new $dtoClass(...$args);
    }

    /**
     * Get cached class info via reflection.
     *
     * @param class-string $class The class to analyze
     * @return array{params: array<string, array{name: string, nullable: bool, hasDefault: bool, default: mixed, dtoClass: class-string|null, collectionOf: class-string|null}>}
     */
    protected function getClassInfo(string $class): array
    {
        if (isset(static::$cache[$class])) {
            return static::$cache[$class];
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        $params = [];
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                $params[$param->getName()] = $this->analyzeParameter($param);
            }
        }

        static::$cache[$class] = ['params' => $params];

        return static::$cache[$class];
    }

    /**
     * Analyze a constructor parameter for DTO mapping info.
     *
     * @param \ReflectionParameter $param The parameter to analyze
     * @return array{name: string, nullable: bool, hasDefault: bool, default: mixed, dtoClass: class-string|null, collectionOf: class-string|null}
     */
    protected function analyzeParameter(ReflectionParameter $param): array
    {
        $type = $param->getType();

        $info = [
            'name' => $param->getName(),
            'nullable' => $param->allowsNull(),
            'hasDefault' => $param->isDefaultValueAvailable(),
            'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
            'dtoClass' => null,
            'collectionOf' => null,
        ];

        // Check if type is a class (potential nested DTO)
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $typeName = $type->getName();
            // Exclude common non-DTO classes
            if (
                !in_array($typeName, ['DateTime', 'DateTimeImmutable', 'DateTimeInterface', 'stdClass'], true)
                && class_exists($typeName)
            ) {
                $info['dtoClass'] = $typeName;
            }
        }

        // Check for #[CollectionOf(SomeDto::class)] attribute
        foreach ($param->getAttributes(CollectionOf::class) as $attr) {
            /** @var class-string $collectionClass */
            $collectionClass = $attr->getArguments()[0];
            $info['collectionOf'] = $collectionClass;
            $info['dtoClass'] = null; // Collection takes precedence
        }

        return $info;
    }

    /**
     * Clear the reflection cache.
     *
     * Useful for testing or when classes are reloaded.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        static::$cache = [];
    }
}
