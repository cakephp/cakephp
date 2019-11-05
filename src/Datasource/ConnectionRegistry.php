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
namespace Cake\Datasource;

use Cake\Core\App;
use Cake\Core\ObjectRegistry;
use Cake\Datasource\Exception\MissingDatasourceException;

/**
 * A registry object for connection instances.
 *
 * @see \Cake\Datasource\ConnectionManager
 *
 * @extends \Cake\Core\ObjectRegistry<\Cake\Datasource\ConnectionInterface>
 */
class ConnectionRegistry extends ObjectRegistry
{
    /**
     * Resolve a datasource classname.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class Partial classname to resolve.
     * @return string|null Either the correct class name or null.
     */
    protected function _resolveClassName(string $class): ?string
    {
        return App::className($class, 'Datasource');
    }

    /**
     * Throws an exception when a datasource is missing
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class The classname that is missing.
     * @param string|null $plugin The plugin the datasource is missing in.
     * @return void
     * @throws \Cake\Datasource\Exception\MissingDatasourceException
     */
    protected function _throwMissingClassError(string $class, ?string $plugin): void
    {
        throw new MissingDatasourceException([
            'class' => $class,
            'plugin' => $plugin,
        ]);
    }

    /**
     * Create the connection object with the correct settings.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * If a callable is passed as first argument, The returned value of this
     * function will be the result of the callable.
     *
     * @param string|\Cake\Datasource\ConnectionInterface|callable $class The classname or object to make.
     * @param string $alias The alias of the object.
     * @param array $settings An array of settings to use for the datasource.
     * @return \Cake\Datasource\ConnectionInterface A connection with the correct settings.
     */
    protected function _create($class, string $alias, array $settings)
    {
        if (is_callable($class)) {
            return $class($alias);
        }

        if (is_object($class)) {
            return $class;
        }

        unset($settings['className']);

        /** @var \Cake\Datasource\ConnectionInterface */
        return new $class($settings);
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
