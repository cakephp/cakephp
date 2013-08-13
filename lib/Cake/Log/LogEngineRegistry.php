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
 * @since         CakePHP(tm) v 2.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log;

use Cake\Core\App;
use Cake\Error;
use Cake\Log\LogInterface;
use Cake\Utility\ObjectCollection;

/**
 * Registry of loaded log engines
 *
 * @package       Cake.Log
 */
class LogEngineRegistry extends ObjectCollection {

/**
 * Loads/constructs a Log engine.
 *
 * @param string $name instance identifier
 * @param LogInterface|array $options Setting for the Log Engine, or the log engine
 *   If a log engine is used, the adapter will be enabled.
 * @return BaseLog BaseLog engine instance
 * @throws Cake\Error\Exception when logger class does not implement a write method
 */
	public function load($name, $options = array()) {
		$logger = false;
		if ($options instanceof LogInterface) {
			$enable = true;
			$logger = $options;
			$options = null;
		}
		if (is_array($options)) {
			$enable = isset($options['enabled']) ? $options['enabled'] : true;
			$logger = $this->_getLogger($options);
		}
		if (!$logger instanceof LogInterface) {
			throw new Error\Exception(sprintf(
				__d('cake_dev', 'logger class %s is not an instance of Cake\Log\LogInterface.'), $name
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
 * @param array $options The configuration options to load a logger with.
 * @return false|LogInterface boolean false on any failures, log adapter interface on success
 * @throws Cake\Error\Exception
 */
	protected static function _getLogger($options) {
		$name = isset($options['engine']) ? $options['engine'] : null;
		unset($options['engine']);
		$class = App::classname($name, 'Log/Engine', 'Log');
		if (!$class) {
			throw new Error\Exception(__d('cake_dev', 'Could not load class %s', $name));
		}
		$logger = new $class($options);
		return $logger;
	}

}
