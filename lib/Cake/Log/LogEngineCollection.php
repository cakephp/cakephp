<?php
/**
 * Registry of loaded log engines
 *
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
 * @package       Cake.Log
 * @since         CakePHP(tm) v 2.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ObjectCollection', 'Utility');

/**
 * Registry of loaded log engines
 *
 * @package       Cake.Log
 */
class LogEngineCollection extends ObjectCollection {

/**
 * Loads/constructs a Log engine.
 *
 * @param string $name instance identifier
 * @param array $options Setting for the Log Engine
 * @return BaseLog BaseLog engine instance
 * @throws CakeLogException when logger class does not implement a write method
 */
	public function load($name, $options = array()) {
		$enable = isset($options['enabled']) ? $options['enabled'] : true;
		$loggerName = $options['engine'];
		unset($options['engine']);
		$className = $this->_getLogger($loggerName);
		$logger = new $className($options);
		if (!$logger instanceof CakeLogInterface) {
			throw new CakeLogException(sprintf(
				__d('cake_dev', 'logger class %s does not implement a write method.'), $loggerName
			));
		}
		$this->_loaded[$name] = $logger;
		if ($enable) {
			$this->enable($name);
		}
		return $logger;
	}

/**
 * Attempts to import a logger class from the various paths it could be on.
 * Checks that the logger class implements a write method as well.
 *
 * @param string $loggerName the plugin.className of the logger class you want to build.
 * @return mixed boolean false on any failures, string of classname to use if search was successful.
 * @throws CakeLogException
 */
	protected static function _getLogger($loggerName) {
		list($plugin, $loggerName) = pluginSplit($loggerName, true);

		App::uses($loggerName, $plugin . 'Log/Engine');
		if (!class_exists($loggerName)) {
			throw new CakeLogException(__d('cake_dev', 'Could not load class %s', $loggerName));
		}
		return $loggerName;
	}

}
