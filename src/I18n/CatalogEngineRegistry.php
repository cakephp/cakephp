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
namespace Cake\I18n;

use Cake\Core\App;
use Cake\Error;
use Cake\I18n\CatalogEngine;
use Cake\Utility\ObjectRegistry;

/**
 * Registry of loaded catalog engines
 */
class CatalogEngineRegistry extends ObjectRegistry {

/**
 * Resolve an catalog engine classname.
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

		return App::classname($class, 'I18n/Engine');
	}

/**
 * Throws an exception when an catalog engine is missing.
 *
 * Part of the template method for Cake\Utility\ObjectRegistry::load()
 *
 * @param string $class The classname that is missing.
 * @param string $plugin The plugin the i18n engine is missing in.
 * @throws \Cake\Error\Exception
 */
	protected function _throwMissingClassError($class, $plugin) {
		throw new Error\Exception(sprintf('Could not load class %s', $class));
	}

/**
 * Create the catalog engine instance.
 *
 * Part of the template method for Cake\Utility\ObjectRegistry::load()
 *
 * @param string|CatalogInterface $class The classname or object to make.
 * @param string $alias The alias of the object.
 * @param array $config An array of setting to use for the catalog engine.
 * @return \Cake\I18n\CatalogEngine The constructed catalog engine instance.
 * @throws \Cake\Error\Exception when an object doesn't implement
 *    the correct interface.
 */
	protected function _create($class, $alias, $config) {
		if (is_object($class)) {
			$instance = $class;
		}

		unset($config['className']);
		if (!isset($instance)) {
			$instance = new $class($config);
		}

		if ($instance instanceof CatalogEngine) {
			return $instance;
		}

		throw new Error\Exception(
			'Catalog engines must extend Cake\I18n\CatalogEngine.'
		);
	}

/**
 * Remove a single catalog engine from the registry.
 *
 * @param string $name The catalog engine name.
 * @return void
 */
	public function unload($name) {
		unset($this->_loaded[$name]);
	}

}
