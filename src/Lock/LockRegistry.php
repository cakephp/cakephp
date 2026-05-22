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
namespace Cake\Lock;

use BadMethodCallException;
use Cake\Core\App;
use Cake\Core\ObjectRegistry;
use Cake\Lock\Exception\LockException;

/**
 * Registry for lock engines.
 *
 * Used by {@link \Cake\Lock\Lock} to manage lock engine instances.
 *
 * @template TEngine of \Cake\Lock\LockEngine
 * @extends \Cake\Core\ObjectRegistry<TEngine>
 */
class LockRegistry extends ObjectRegistry
{
    /**
     * Resolve a lock engine classname.
     *
     * @param string $class Partial classname to resolve.
     * @return class-string<TEngine>|null Either the correct classname or null.
     */
    protected function _resolveClassName(string $class): ?string
    {
        /** @var class-string<TEngine>|null */
        return App::className($class, 'Lock/Engine', 'LockEngine');
    }

    /**
     * Throws an exception when a lock engine is missing.
     *
     * @param string $class The classname that is missing.
     * @param string|null $plugin The plugin the lock engine is missing in.
     * @return void
     * @throws \BadMethodCallException
     */
    protected function _throwMissingClassError(string $class, ?string $plugin): void
    {
        throw new BadMethodCallException(sprintf('Lock engine `%s` is not available.', $class));
    }

    /**
     * Create the lock engine instance.
     *
     * @param TEngine|class-string<TEngine> $class The classname or object to make.
     * @param string $alias The alias of the object.
     * @param array<string, mixed> $config An array of settings for the lock engine.
     * @return TEngine The constructed LockEngine.
     * @throws \Cake\Lock\Exception\LockException When the lock engine cannot be initialized.
     */
    protected function _create(object|string $class, string $alias, array $config): LockEngine
    {
        if (is_object($class)) {
            $instance = $class;
        } else {
            $instance = new $class($config);
        }
        unset($config['className']);

        assert($instance instanceof LockEngine, 'Lock engines must extend `' . LockEngine::class . '`.');

        if (!$instance->init($config)) {
            throw new LockException(
                sprintf(
                    'Lock engine `%s` is not properly configured.',
                    $instance::class,
                ),
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
