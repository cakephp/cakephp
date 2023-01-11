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
 * @since         2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log;

use Cake\Core\App;
use Cake\Core\Exception\CakeException;
use Cake\Core\ObjectRegistry;
use Psr\Log\LoggerInterface;

/**
 * Registry of loaded log engines
 *
 * @extends \Cake\Core\ObjectRegistry<\Psr\Log\LoggerInterface>
 */
class LogEngineRegistry extends ObjectRegistry
{
    /**
     * Resolve a logger classname.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class Partial classname to resolve.
     * @return class-string<\Psr\Log\LoggerInterface>|null Either the correct class name or null.
     */
    protected function _resolveClassName(string $class): ?string
    {
        /** @var class-string<\Psr\Log\LoggerInterface>|null */
        return App::className($class, 'Log/Engine', 'Log');
    }

    /**
     * Throws an exception when a logger is missing.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class The classname that is missing.
     * @param string|null $plugin The plugin the logger is missing in.
     * @return void
     * @throws \Cake\Core\Exception\CakeException
     */
    protected function _throwMissingClassError(string $class, ?string $plugin): void
    {
        throw new CakeException(sprintf('Could not load class `%s`.', $class));
    }

    /**
     * Create the logger instance.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param \Psr\Log\LoggerInterface|callable|class-string<\Psr\Log\LoggerInterface> $class The classname or object to make.
     * @param string $alias The alias of the object.
     * @param array<string, mixed> $config An array of settings to use for the logger.
     * @return \Psr\Log\LoggerInterface The constructed logger class.
     */
    protected function _create(callable|object|string $class, string $alias, array $config): LoggerInterface
    {
        if (is_string($class)) {
            /** @var class-string<\Psr\Log\LoggerInterface> $class */
            return new $class($config);
        }

        if (is_callable($class)) {
            return $class($alias);
        }

        return $class;
    }

    /**
     * Remove a single logger from the registry.
     *
     * @param string $name The logger name.
     * @return $this
     */
    public function unload(string $name)
    {
        unset($this->_loaded[$name]);

        return $this;
    }
}
