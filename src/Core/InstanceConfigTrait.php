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
namespace Cake\Core;

use Cake\Error\Exception;
use Cake\Utility\Hash;

/**
 * A trait for reading and writing instance config
 *
 * Implementing objects are expected to declare a `$_defaultConfig` property.
 */
trait InstanceConfigTrait {

/**
 * Runtime config
 *
 * @var array
 */
	protected $_config = [];

/**
 * Whether the config property has already been configured with defaults
 *
 * @var bool
 */
	protected $_configInitialized = false;

/**
 * ### Usage
 *
 * Reading the whole config:
 *
 * `$this->config();`
 *
 * Reading a specific value:
 *
 * `$this->config('key');`
 *
 * Reading a nested value:
 *
 * `$this->config('some.nested.key');`
 *
 * Setting a specific value:
 *
 * `$this->config('key', $value);`
 *
 * Setting a nested value:
 *
 * `$this->config('some.nested.key', $value);`
 *
 * Updating multiple config settings at the same time:
 *
 * `$this->config(['one' => 'value', 'another' => 'value']);`
 *
 * @param string|array|null $key The key to get/set, or a complete array of configs.
 * @param mixed|null $value The value to set.
 * @param bool $merge Whether to merge or overwrite existing config defaults to true.
 * @return mixed Config value being read, or the object itself on write operations.
 * @throws \Cake\Error\Exception When trying to set a key that is invalid.
 */
	public function config($key = null, $value = null, $merge = true) {
		if (!$this->_configInitialized) {
			$this->_config = $this->_defaultConfig;
			$this->_configInitialized = true;
		}

		if (is_array($key) || func_num_args() >= 2) {
			$this->_configWrite($key, $value, $merge);
			return $this;
		}

		return $this->_configRead($key);
	}

/**
 * Read a config variable
 *
 * @param string|null $key
 * @return mixed
 */
	protected function _configRead($key) {
		if ($key === null) {
			return $this->_config;
		}

		if (strpos($key, '.') === false) {
			return isset($this->_config[$key]) ? $this->_config[$key] : null;
		}

		$return = $this->_config;

		foreach (explode('.', $key) as $k) {
			if (!is_array($return) || !isset($return[$k])) {
				$return = null;
				break;
			}

			$return = $return[$k];

		}

		return $return;
	}

/**
 * Write a config variable
 *
 * @throws Cake\Error\Exception if attempting to clobber existing config
 * @param string|array $key
 * @param mixed $value
 * @param bool $merge
 * @return void
 */
	protected function _configWrite($key, $value, $merge = null) {
		if (is_string($key) && $value === null) {
			return $this->_configDelete($key);
		}

		if ($merge) {
			if (is_array($key)) {
				$update = $key;
			} else {
				$update = [$key => $value];
			}
			$this->_config = Hash::merge($this->_config, Hash::expand($update));
			return;
		}

		if (is_array($key)) {
			foreach ($key as $k => $val) {
				$this->_configWrite($k, $val);
			}
			return;
		}

		if (strpos($key, '.') === false) {
			$this->_config[$key] = $value;
			return;
		}

		$update =& $this->_config;
		$stack = explode('.', $key);

		foreach ($stack as $k) {
			if (!is_array($update)) {
				throw new Exception(sprintf('Cannot set %s value', $key));
			}

			if (!isset($update[$k])) {
				$update[$k] = [];
			}

			$update =& $update[$k];
		}

		$update = $value;
	}

/**
 * Delete a single config key
 *
 * @throws Cake\Error\Exception if attempting to clobber existing config
 * @param string $key
 * @return void
 */
	protected function _configDelete($key) {
		if (strpos($key, '.') === false) {
			unset($this->_config[$key]);
			return;
		}

		$update =& $this->_config;
		$stack = explode('.', $key);
		$length = count($stack);

		foreach ($stack as $i => $k) {
			if (!is_array($update)) {
				throw new Exception(sprintf('Cannot unset %s value', $key));
			}

			if (!isset($update[$k])) {
				break;
			}

			if ($i === $length - 2) {
				unset($update[$k]);
				break;
			}

			$update =& $update[$k];
		}
	}

}
