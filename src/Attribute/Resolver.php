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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Attribute;

use Cake\Attribute\Resolver\Artifact;
use Cake\Attribute\Resolver\AttributeCollection;
use Cake\Attribute\Resolver\Event\AfterArtifactsClearEvent;
use Cake\Attribute\Resolver\Event\AfterResolveEvent;
use Cake\Attribute\Resolver\Event\AfterScanEvent;
use Cake\Attribute\Resolver\Event\BeforeArtifactsClearEvent;
use Cake\Attribute\Resolver\Event\BeforeResolveEvent;
use Cake\Attribute\Resolver\Event\BeforeScanEvent;
use Cake\Attribute\Resolver\Parser;
use Cake\Attribute\Resolver\Scanner;
use Cake\Core\StaticConfigTrait;
use Cake\Event\EventManager;

/**
 * Attribute Resolver
 *
 * Main entry point for attribute resolution.
 * Scans source files for PHP attributes and provides a fluent collection interface
 * for filtering and querying discovered attributes.
 *
 * @mixin \Cake\Attribute\Resolver\AttributeCollection
 */
class Resolver
{
    use StaticConfigTrait {
        drop as private traitDrop;
    }

    /**
     * In-memory cache of resolved collections per config name
     *
     * @var array<string, \Cake\Attribute\Resolver\AttributeCollection>
     */
    protected static array $collections = [];

    /**
     * Get attribute collection for configured paths
     *
     * @param string $name Configuration name
     * @return \Cake\Attribute\Resolver\AttributeCollection
     */
    public static function collection(string $name = 'default'): AttributeCollection
    {
        // Check in-memory cache first, but allow BeforeResolve to prevent even cached results
        $instance = new self();
        $event = EventManager::instance()->dispatch(new BeforeResolveEvent($instance));
        if ($event->isStopped()) {
            return new AttributeCollection([]);
        }

        if (isset(static::$collections[$name])) {
            EventManager::instance()->dispatch(new AfterResolveEvent($instance, static::$collections[$name]));

            return static::$collections[$name];
        }

        $config = static::getConfig($name);
        $artifactPath = $config['artifact'] ?? null;

        $artifact = null;
        if ($artifactPath !== null) {
            $artifact = new Artifact($artifactPath, $config['validateFiles'] ?? false);
            $cached = $artifact->get();
            if ($cached !== null) {
                $collection = new AttributeCollection($cached);
                static::$collections[$name] = $collection;

                EventManager::instance()->dispatch(new AfterResolveEvent($instance, $collection));

                return $collection;
            }
        }

        $scanEvent = EventManager::instance()->dispatch(new BeforeScanEvent($instance));
        if ($scanEvent->isStopped()) {
            return new AttributeCollection([]);
        }

        $parser = new Parser($config['excludeAttributes'] ?? []);
        $scanner = new Scanner(
            $parser,
            $config['paths'] ?? [],
            $config['excludePaths'] ?? [],
            $config['basePath'] ?? null,
        );
        // Materialize the generator to an array so we can use it multiple times
        $attributes = iterator_to_array($scanner->scanAll(), false);
        $collection = new AttributeCollection($attributes);

        EventManager::instance()->dispatch(new AfterScanEvent($instance, $collection, $scanner->getScannedFiles()));

        if ($artifact !== null) {
            $artifact->set($attributes);
        }

        static::$collections[$name] = $collection;

        EventManager::instance()->dispatch(new AfterResolveEvent($instance, $collection));

        return $collection;
    }

    /**
     * Clear artifacts for a configuration
     *
     * @param string $name Configuration name
     * @return bool Success status
     */
    public static function clear(string $name = 'default'): bool
    {
        $instance = new self();

        $event = EventManager::instance()->dispatch(new BeforeArtifactsClearEvent($instance));
        if ($event->isStopped()) {
            return false;
        }

        $config = static::getConfig($name);
        $artifactPath = $config['artifact'] ?? null;

        $success = false;
        if ($artifactPath !== null) {
            $artifact = new Artifact($artifactPath);
            $success = $artifact->delete();
        }

        unset(static::$collections[$name]);

        EventManager::instance()->dispatch(new AfterArtifactsClearEvent($instance, $success));

        return $success;
    }

    /**
     * Build artifacts for a configuration
     *
     * @param string $name Configuration name
     * @return bool Success status
     */
    public static function warm(string $name = 'default'): bool
    {
        static::clear($name);
        $collection = static::collection($name);

        return $collection->count() >= 0;
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

        /** @phpstan-ignore-next-line staticClassAccess.privateMethod */
        return self::traitDrop($config);
    }

    /**
     * Forward method calls to the default collection
     *
     * Enables convenient access to collection methods on the default config:
     * ```
     * $routes = Resolver::withAttribute(Route::class);
     * ```
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
