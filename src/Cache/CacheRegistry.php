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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache;

use BadMethodCallException;
use Cake\Core\App;
use Cake\Core\ObjectRegistry;
use RuntimeException;

/**
 * An object registry for cache engines.
 *
 * Used by Cake\Cache\Cache to load and manage cache engines.
 *
 * @extends \Cake\Core\ObjectRegistry<\Cake\Cache\CacheEngine>
 */
class CacheRegistry extends ObjectRegistry
{
    /**
     * Resolve a cache engine classname.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class Partial classname to resolve.
     * @return string|null Either the correct classname or null.
     * @psalm-return class-string|null
     */
    protected function _resolveClassName(string $class): ?string
    {
        return App::className($class, 'Cache/Engine', 'Engine');
    }

    /**
     * Throws an exception when a cache engine is missing.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class The classname that is missing.
     * @param string|null $plugin The plugin the cache is missing in.
     * @return void
     * @throws \BadMethodCallException
     */
    protected function _throwMissingClassError(string $class, ?string $plugin): void
    {
        throw new BadMethodCallException(sprintf('Cache engine %s is not available.', $class));
    }

    /**
     * Create the cache engine instance.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string|\Cake\Cache\CacheEngine $class The classname or object to make.
     * @param string $alias The alias of the object.
     * @param array $config An array of settings to use for the cache engine.
     * @return \Cake\Cache\CacheEngine The constructed CacheEngine class.
     * @throws \RuntimeException when an object doesn't implement the correct interface.
     */
    protected function _create($class, string $alias, array $config): CacheEngine
    {
        if (is_object($class)) {
            $instance = $class;
        } else {
            $instance = new $class($config);
        }
        unset($config['className']);

        if (!($instance instanceof CacheEngine)) {
            throw new RuntimeException(
                'Cache engines must use Cake\Cache\CacheEngine as a base class.'
            );
        }

        if (!$instance->init($config)) {
            throw new RuntimeException(
                sprintf('Cache engine %s is not properly configured.', get_class($instance))
            );
        }

        return $instance;
    }

    /**
     * Remove a single adapter from the registry.
     *
     * @param string $name The adapter name.
     * @return $this
     */
    public function unload(string $name)
    {
        unset($this->_loaded[$name]);

        return $this;
    }
}
