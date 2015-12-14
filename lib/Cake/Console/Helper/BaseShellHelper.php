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
 * @since         2.8
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

abstract class BaseShellHelper {

/**
 * Default config for this helper.
 *
 * @var array
 */
	protected $_defaultConfig = array();

/**
 * ConsoleOutput instance.
 *
 * @var ConsoleOutput
 */
	protected $_consoleOutput;

/**
 * Runtime config
 *
 * @var array
 */
	protected $_config = array();

/**
 * Whether the config property has already been configured with defaults
 *
 * @var bool
 */
	protected $_configInitialized = false;

/**
 * Constructor.
 *
 * @param ConsoleOutput $consoleOutput The ConsoleOutput instance to use.
 * @param array $config The settings for this helper.
 */
	public function __construct(ConsoleOutput $consoleOutput, array $config = array()) {
		$this->_consoleOutput = $consoleOutput;
		$this->config($config);
	}

/**
 * Initialize config & store config values
 *
 * @param null $config Config values to set
 * @return array|void
 */
	public function config($config = null) {
		if ($config === null) {
			return $this->_config;
		}
		if (!$this->_configInitialized) {
			$this->_config = array_merge($this->_defaultConfig, $config);
			$this->_configInitialized = true;
		} else {
			$this->_config = array_merge($this->_config, $config);
		}
	}

/**
 * This method should output content using `$this->_consoleOutput`.
 *
 * @param array $args The arguments for the helper.
 * @return void
 */
	abstract public function output($args);
}