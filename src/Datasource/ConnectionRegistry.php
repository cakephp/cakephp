<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

use Cake\Core\App;
use Cake\Core\ObjectRegistry;
use Cake\Datasource\Exception\MissingDatasourceException;

/**
 * A registry object for connection instances.
 *
 * @see \Cake\Datasource\ConnectionManager
 */
class ConnectionRegistry extends ObjectRegistry
{

    /**
     * Resolve a driver classname.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class Partial classname to resolve.
     * @return string|false Either the correct classname or false.
     */
    protected function _resolveClassName($class)
    {
        if (is_object($class)) {
            return $class;
        }
        return App::className($class, 'Datasource');
    }

    /**
     * Throws an exception when a driver is missing
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class The classname that is missing.
     * @param string $plugin The plugin the driver is missing in.
     * @return void
     * @throws \Cake\Datasource\Exception\MissingDatasourceException
     */
    protected function _throwMissingClassError($class, $plugin)
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
     * @param string|object $class The classname or object to make.
     * @param string $alias The alias of the object.
     * @param array $settings An array of settings to use for the driver.
     * @return object A connection with the correct settings.
     */
    protected function _create($class, $alias, $settings)
    {
        unset($settings['className']);
        return new $class($settings);
    }

    /**
     * Remove a single adapter from the registry.
     *
     * @param string $name The adapter name.
     * @return void
     */
    public function unload($name)
    {
        unset($this->_loaded[$name]);
    }
}
