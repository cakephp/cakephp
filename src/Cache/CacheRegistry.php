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
use Cake\Core\Exception\CakeException;
use Cake\Core\ObjectRegistry;

/**
 * An object registry for cache engines.
 *
 * Used by {@link \Cake\Cache\Cache} to load and manage cache engines.
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
     * @return class-string<\Cake\Cache\CacheEngine>|null Either the correct classname or null.
     */
    protected function _resolveClassName(string $class): ?string
    {
        /** @var class-string<\Cake\Cache\CacheEngine>|null */
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
        throw new BadMethodCallException(sprintf('Cache engine `%s` is not available.', $class));
    }

    /**
     * Create the cache engine instance.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param \Cake\Cache\CacheEngine|class-string<\Cake\Cache\CacheEngine> $class The classname or object to make.
     * @param string $alias The alias of the object.
     * @param array<string, mixed> $config An array of settings to use for the cache engine.
     * @return \Cake\Cache\CacheEngine The constructed CacheEngine class.
     * @throws \Cake\Core\Exception\CakeException When the cache engine cannot be initialized.
     */
    protected function _create(object|string $class, string $alias, array $config): CacheEngine
    {
        if (is_object($class)) {
            $instance = $class;
        } else {
            $instance = new $class($config);
        }
        unset($config['className']);

        assert($instance instanceof CacheEngine, 'Cache engines must extend `' . CacheEngine::class . '`.');

        if (!$instance->init($config)) {
            throw new CakeException(
                sprintf(
                    'Cache engine `%s` is not properly configured. Check error log for additional information.',
                    $instance::class
                )
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
