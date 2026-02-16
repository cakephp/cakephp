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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace AttributeResolver;

use AttributeResolver\AttributeCache;
use AttributeResolver\AttributeCollection;
use AttributeResolver\Parser;
use AttributeResolver\Scanner;
use Cake\Core\StaticConfigTrait;
use InvalidArgumentException;

/**
 * Attribute Resolver
 *
 * Main entry point for attribute resolution.
 * Scans source files for PHP attributes and provides a fluent collection interface
 * for filtering and querying discovered attributes.
 *
 * @mixin \AttributeResolver\AttributeCollection
 */
class AttributeResolver
{
    use StaticConfigTrait {
        drop as private traitDrop;
    }

    /**
     * An array mapping URL schemes to fully qualified class names.
     *
     * @var array<string, string>
     * @phpstan-var array<string, class-string>
     */
    protected static array $dsnClassMap = [];

    /**
     * In-memory cache of resolved collections per config name
     *
     * @var array<string, \AttributeResolver\AttributeCollection>
     */
    protected static array $collections = [];

    /**
     * Get attribute collection for configured paths
     *
     * @param string $name Configuration name
     * @return \AttributeResolver\AttributeCollection
     * @throws \InvalidArgumentException When configuration does not exist
     */
    public static function collection(string $name = 'default'): AttributeCollection
    {
        if (isset(static::$collections[$name])) {
            return static::$collections[$name];
        }

        $config = static::getConfig($name);
        if ($config === null) {
            throw new InvalidArgumentException(
                sprintf('The `%s` attribute resolver configuration does not exist.', $name),
            );
        }

        $cache = static::getCache($config);

        $collection = $cache?->read($name);
        if ($collection === null) {
            $parser = new Parser($config['excludeAttributes'] ?? []);
            $scanner = new Scanner(
                $parser,
                $config['paths'] ?? [],
                $config['excludePaths'] ?? [],
                $config['basePath'] ?? null,
            );

            $collection = new AttributeCollection(iterator_to_array($scanner->scanAll(), false));
            $cache?->write($name, $collection);
        }

        static::$collections[$name] = $collection;

        return $collection;
    }

    /**
     * Clear cached attributes for a configuration
     *
     * @param string $name Configuration name
     * @return bool Success status
     */
    public static function clear(string $name = 'default'): bool
    {
        $config = static::getConfig($name);
        if ($config === null) {
            throw new InvalidArgumentException(sprintf(
                'Attribute resolver configuration "%s" does not exist.',
                $name,
            ));
        }
        $cache = static::getCache($config);

        unset(static::$collections[$name]);

        return $cache?->delete($name) ?? true;
    }

    /**
     * Get cache instance for a configuration
     *
     * @param array|null $config Configuration array
     * @return \AttributeResolver\AttributeCache|null Returns null when cache is disabled
     */
    protected static function getCache(?array $config): ?AttributeCache
    {
        $cacheConfig = $config['cache'] ?? '_cake_attributes_';
        if ($cacheConfig === false) {
            return null;
        }

        return new AttributeCache(
            $cacheConfig,
            $config['validateFiles'] ?? false,
        );
    }

    /**
     * Build cache for a configuration
     *
     * @param string $name Configuration name
     * @return \AttributeResolver\AttributeCollection The warmed collection
     */
    public static function warm(string $name = 'default'): AttributeCollection
    {
        static::clear($name);

        return static::collection($name);
    }

    /**
     * Drop a configuration and clear its in-memory cache
     *
     * @param string $config Configuration name to remove
     * @return bool Success
     */
    public static function drop(string $config): bool
    {
        unset(static::$collections[$config]);

        return self::traitDrop($config);
    }

    /**
     * Forward method calls to the default collection
     *
     * Enables convenient access to collection methods on the default config:
     * `$routes = AttributeResolver::withAttribute(Route::class);`
     *
     * @param string $method Method name
     * @param array $arguments Method arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return static::collection()->$method(...$arguments);
    }
}
