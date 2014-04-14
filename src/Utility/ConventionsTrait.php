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
namespace Cake\Utility;

use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;

/**
 * Provides methods that allow other classes access to conventions based inflections.
 */
trait ConventionsTrait {

/**
 * Creates the proper controller plural name for the specified controller class name
 *
 * @param string $name Controller class name
 * @return string Controller plural name
 */
	protected function _controllerName($name) {
		return Inflector::pluralize(Inflector::camelize($name));
	}

/**
 * Creates a fixture name
 *
 * @param string $name Model class name
 * @return string Singular model key
 */
	protected function _fixtureName($name) {
		return Inflector::underscore(Inflector::singularize($name));
	}

/**
 * Creates the proper model camelized name (plural) for the specified name
 *
 * @param string $name Name
 * @return string Camelized and plural model name
 */
	protected function _modelName($name) {
		return Inflector::pluralize(Inflector::camelize($name));
	}

/**
 * Creates the proper entity name (singular) for the specified name
 *
 * @param string $name Name
 * @return string Camelized and plural model name
 */
	protected function _entityName($name) {
		return Inflector::singularize(Inflector::camelize($name));
	}

/**
 * Creates the proper underscored model key for associations
 *
 * @param string $name Model class name
 * @return string Singular model key
 */
	protected function _modelKey($name) {
		return Inflector::underscore(Inflector::singularize($name)) . '_id';
	}

/**
 * Creates the proper model name from a foreign key
 *
 * @param string $key Foreign key
 * @return string Model name
 */
	protected function _modelNameFromKey($key) {
		$key = str_replace('_id', '', $key);
		return $this->_modelName($key);
	}

/**
 * creates the singular name for use in views.
 *
 * @param string $name
 * @return string name
 */
	protected function _singularName($name) {
		return Inflector::variable(Inflector::singularize($name));
	}

/**
 * Creates the plural name for views
 *
 * @param string $name Name to use
 * @return string Plural name for views
 */
	protected function _pluralName($name) {
		return Inflector::variable(Inflector::pluralize($name));
	}

/**
 * Creates the singular human name used in views
 *
 * @param string $name Controller name
 * @return string Singular human name
 */
	protected function _singularHumanName($name) {
		return Inflector::humanize(Inflector::underscore(Inflector::singularize($name)));
	}

/**
 * Creates a camelized version of $name
 *
 * @param string $name name
 * @return string Camelized name
 */
	protected function _camelize($name) {
		return Inflector::camelize($name);
	}

/**
 * Creates the plural human name used in views
 *
 * @param string $name Controller name
 * @return string Plural human name
 */
	protected function _pluralHumanName($name) {
		return Inflector::humanize(Inflector::underscore($name));
	}

/**
 * Find the correct path for a plugin. Scans $pluginPaths for the plugin you want.
 *
 * @param string $pluginName Name of the plugin you want ie. DebugKit
 * @return string path path to the correct plugin.
 */
	protected function _pluginPath($pluginName) {
		if (Plugin::loaded($pluginName)) {
			return Plugin::path($pluginName);
		}
		return current(App::path('Plugin')) . $pluginName . DS;
	}

}
