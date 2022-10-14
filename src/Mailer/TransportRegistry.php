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
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Mailer;

use BadMethodCallException;
use Cake\Core\App;
use Cake\Core\ObjectRegistry;

/**
 * An object registry for mailer transports.
 *
 * @extends \Cake\Core\ObjectRegistry<\Cake\Mailer\AbstractTransport>
 */
class TransportRegistry extends ObjectRegistry
{
    /**
     * Resolve a mailer tranport classname.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class Partial classname to resolve or transport instance.
     * @return class-string<\Cake\Mailer\AbstractTransport>|null Either the correct classname or null.
     */
    protected function _resolveClassName(string $class): ?string
    {
        /** @var class-string<\Cake\Mailer\AbstractTransport>|null */
        return App::className($class, 'Mailer/Transport', 'Transport');
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
        throw new BadMethodCallException(sprintf('Mailer transport `%s` is not available.', $class));
    }

    /**
     * Create the mailer transport instance.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param \Cake\Mailer\AbstractTransport|class-string<\Cake\Mailer\AbstractTransport> $class The classname or object to make.
     * @param string $alias The alias of the object.
     * @param array<string, mixed> $config An array of settings to use for the cache engine.
     * @return \Cake\Mailer\AbstractTransport The constructed transport class.
     */
    protected function _create(object|string $class, string $alias, array $config): AbstractTransport
    {
        if (is_object($class)) {
            return $class;
        }

        return new $class($config);
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
