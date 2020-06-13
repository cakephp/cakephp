<?php
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
use RuntimeException;

/**
 * An object registry for mailer transports.
 */
class TransportRegistry extends ObjectRegistry
{
    /**
     * Resolve a mailer tranport classname.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string|\Cake\Mailer\AbstractTransport $class Partial classname to resolve or transport instance.
     * @return string|false Either the correct classname or false.
     */
    protected function _resolveClassName($class)
    {
        if (is_object($class)) {
            return $class;
        }

        $className = App::className($class, 'Mailer/Transport', 'Transport');

        if (!$className) {
            $className = App::className($class, 'Network/Email', 'Transport');
            if ($className) {
                deprecationWarning(
                    'Transports in "Network/Email" are deprecated, use "Mailer/Transport" instead.'
                );
            }
        }

        return $className;
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
    protected function _throwMissingClassError($class, $plugin)
    {
        throw new BadMethodCallException(sprintf('Mailer transport %s is not available.', $class));
    }

    /**
     * Create the mailer transport instance.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string|\Cake\Mailer\AbstractTransport $class The classname or object to make.
     * @param string $alias The alias of the object.
     * @param array $config An array of settings to use for the cache engine.
     * @return \Cake\Mailer\AbstractTransport The constructed transport class.
     * @throws \RuntimeException when an object doesn't implement the correct interface.
     */
    protected function _create($class, $alias, $config)
    {
        $instance = null;

        if (is_object($class)) {
            $instance = $class;
        }

        if (!$instance) {
            $instance = new $class($config);
        }

        if ($instance instanceof AbstractTransport) {
            return $instance;
        }

        throw new RuntimeException(
            'Mailer transports must use Cake\Mailer\AbstractTransport as a base class.'
        );
    }

    /**
     * Remove a single adapter from the registry.
     *
     * @param string $name The adapter name.
     * @return $this
     */
    public function unload($name)
    {
        unset($this->_loaded[$name]);

        return $this;
    }
}
