<?php
/**
 * Registry of loaded log engines
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Log
 * @since         CakePHP(tm) v 2.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Cake\Log;
use Cake\Utility\ObjectCollection;
use Cake\Core\App;
use Cake\Error;

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
		if (!$logger instanceof LogInterface) {
			throw new Error\LogException(sprintf(
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
 * @throws Cake\Error\LogException
 */
	protected static function _getLogger($loggerName) {
		$loggerClass = App::classname($loggerName, 'Log/Engine');
		if (!$loggerClass) {
			throw new Error\LogException(__d('cake_dev', 'Could not load class %s', $loggerName));
		}
		return $loggerClass;
	}

}
