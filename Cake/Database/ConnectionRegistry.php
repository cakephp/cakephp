<?php
/**
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Cake\Core\App;
use Cake\Database\Connection;
use Cake\Database\Exception\MissingDriverException;
use Cake\Error;
use Cake\Utility\ObjectRegistry;

/**
 * A registry object for connection instances.
 *
 * @see Cake\Database\ConnectionManager
 */
class ConnectionRegistry extends ObjectRegistry {

/**
 * Resolve a driver classname.
 *
 * Part of the template method for Cake\Utility\ObjectRegistry::load()
 *
 * @param string $class Partial classname to resolve.
 * @return string|false Either the correct classname or false.
 */
	protected function _resolveClassName($class) {
		if (is_object($class)) {
			return $class;
		}
		return App::classname($class, 'Database/Driver');
	}

/**
 * Throws an exception when a driver is missing
 *
 * Part of the template method for Cake\Utility\ObjectRegistry::load()
 *
 * @param string $class The classname that is missing.
 * @param string $plugin The plugin the driver is missing in.
 * @throws Cake\Database\Exception\MissingDriverException
 */
	protected function _throwMissingClassError($class, $plugin) {
		throw new MissingDriverException([
			'class' => $class,
			'plugin' => $plugin,
		]);
	}

/**
 * Create the connection object with the correct driver.
 *
 * Part of the template method for Cake\Utility\ObjectRegistry::load()
 *
 * @param string|Driver $class The classname or object to make.
 * @param string $alias The alias of the object.
 * @param array $settings An array of settings to use for the driver.
 * @return Connection A connection with the correct driver.
 */
	protected function _create($class, $alias, $settings) {
		if (is_object($class)) {
			$instance = $class;
		}

		unset($settings['className']);
		if (!isset($instance)) {
			$instance = new $class($settings);
		}
		$settings['datasource'] = $instance;
		return new Connection($settings);
	}

/**
 * Remove a single adapter from the registry.
 *
 * @param string $name The adapter name.
 * @return void
 */
	public function unload($name) {
		unset($this->_loaded[$name]);
	}

}
