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
 * @since         2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log;

use Cake\Core\App;
use Cake\Core\Exception\Exception;
use Cake\Core\ObjectRegistry;
use Cake\Log\LogInterface;

/**
 * Registry of loaded log engines
 */
class LogEngineRegistry extends ObjectRegistry {

/**
 * Resolve a logger classname.
 *
 * Part of the template method for Cake\Core\ObjectRegistry::load()
 *
 * @param string $class Partial classname to resolve.
 * @return string|false Either the correct classname or false.
 */
	protected function _resolveClassName($class) {
		if (is_object($class)) {
			return $class;
		}

		return App::className($class, 'Log/Engine', 'Log');
	}

/**
 * Throws an exception when a logger is missing.
 *
 * Part of the template method for Cake\Core\ObjectRegistry::load()
 *
 * @param string $class The classname that is missing.
 * @param string $plugin The plugin the logger is missing in.
 * @return void
 * @throws \Cake\Core\Exception\Exception
 */
	protected function _throwMissingClassError($class, $plugin) {
		throw new Exception(sprintf('Could not load class %s', $class));
	}

/**
 * Create the logger instance.
 *
 * Part of the template method for Cake\Core\ObjectRegistry::load()
 *
 * @param string|LogInterface $class The classname or object to make.
 * @param string $alias The alias of the object.
 * @param array $settings An array of settings to use for the logger.
 * @return \Cake\Log\LogInterface The constructed logger class.
 * @throws \Cake\Core\Exception\Exception when an object doesn't implement
 *    the correct interface.
 */
	protected function _create($class, $alias, $settings) {
		if (is_object($class)) {
			$instance = $class;
		}

		if (!isset($instance)) {
			$instance = new $class($settings);
		}

		if ($instance instanceof LogInterface) {
			return $instance;
		}

		throw new Exception(
			'Loggers must implement Cake\Log\LogInterface.'
		);
	}

/**
 * Remove a single logger from the registry.
 *
 * @param string $name The logger name.
 * @return void
 */
	public function unload($name) {
		unset($this->_loaded[$name]);
	}

}
