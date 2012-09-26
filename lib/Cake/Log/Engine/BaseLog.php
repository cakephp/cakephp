<?php
/**
 * Base Log Engine class
 *
 * PHP 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       Cake.Log.Engine
 * @since         CakePHP(tm) v 2.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakeLogInterface', 'Log');

/**
 * Base log engine class.
 *
 * @package       Cake.Log.Engine
 */
abstract class BaseLog implements CakeLogInterface {

/**
 * Engine config
 *
 * @var string
 */
	protected $_config = array();

/**
 * __construct method
 *
 * @return void
 */
	public function __construct($config = array()) {
		$this->config($config);
	}

/**
 * Sets instance config.  When $config is null, returns config array
 *
 * Config
 *
 * - `types` string or array, levels the engine is interested in
 * - `scopes` string or array, scopes the engine is interested in
 *
 * @param array $config engine configuration
 * @return array
 */
	public function config($config = array()) {
		if (!empty($config)) {
			if (isset($config['types']) && is_string($config['types'])) {
				$config['types'] = array($config['types']);
			}
			$this->_config = $config;
		}
		return $this->_config;
	}

}
