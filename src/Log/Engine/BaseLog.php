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
 * @since         2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log\Engine;

use Cake\Core\InstanceConfigTrait;
use Cake\Log\LogInterface;

/**
 * Base log engine class.
 *
 */
abstract class BaseLog implements LogInterface {

	use InstanceConfigTrait;

/**
 * Default config for this class
 *
 * @var array
 */
	protected $_defaultConfig = [
		'levels' => [],
		'scopes' => []
	];

/**
 * __construct method
 *
 * @param array $config Configuration array
 */
	public function __construct(array $config = []) {
		$this->config($config);

		if (!is_array($this->_config['scopes'])) {
			$normalized = true;
			$this->_config['scopes'] = (array)$this->_config['scopes'];
		}

		if (!is_array($this->_config['levels'])) {
			$normalized = true;
			$this->_config['levels'] = (array)$this->_config['levels'];
		}

		if (isset($this->_config['types']) && empty($this->_config['levels'])) {
			$normalized = true;
			$this->_config['levels'] = (array)$this->_config['types'];
		}
	}

/**
 * Get the levels this logger is interested in.
 *
 * @return array
 */
	public function levels() {
		return $this->_config['levels'];
	}

/**
 * Get the scopes this logger is interested in.
 *
 * @return array
 */
	public function scopes() {
		return $this->_config['scopes'];
	}

}
