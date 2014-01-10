<?php
/**
 * CakePHP(tm) :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log\Engine;

use Cake\Log\LogInterface;

/**
 * Base log engine class.
 *
 */
abstract class BaseLog implements LogInterface {

/**
 * Engine config
 *
 * @var string
 */
	protected $_config = [];

/**
 * __construct method
 *
 * @param array $config Configuration array
 */
	public function __construct(array $config = []) {
		$this->config($config);
	}

/**
 * Sets instance config. When $config is null, returns config array
 *
 * ### Options
 *
 * - `levels` string or array, levels the engine is interested in
 * - `scopes` string or array, scopes the engine is interested in
 *
 * @param array|null $config Either an array of configuration, or null to get
 *    current configuration.
 * @return array Array of configuration options.
 */
	public function config($config = null) {
		if (empty($config)) {
			return $this->_config;
		}
		$config += ['levels' => [], 'scopes' => []];
		$config['scopes'] = (array)$config['scopes'];
		$config['levels'] = (array)$config['levels'];
		if (isset($config['types']) && empty($config['levels'])) {
			$config['levels'] = (array)$config['types'];
		}
		$this->_config = $config;
		return $this->_config;
	}

/**
 * Get the levels this logger is interested in.
 *
 * @return array
 */
	public function levels() {
		return isset($this->_config['levels']) ? $this->_config['levels'] : [];
	}

/**
 * Get the scopes this logger is interested in.
 *
 * @return array
 */
	public function scopes() {
		return isset($this->_config['scopes']) ? $this->_config['scopes'] : [];
	}

}
