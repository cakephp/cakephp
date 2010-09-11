<?php
/**
 * Helpers collection is used as a registry for loaded helpers and handles loading
 * and constructing helper class objects.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'ObjectCollection');

class HelperCollection extends ObjectCollection {

/**
 * View object to use when making helpers.
 *
 * @var View
 */
	protected $_View;

/**
 * Constructor
 *
 * @return void
 */
	public function __construct(View $view) {
		$this->_View = $view;
	}

/**
 * Loads/constructs a helper.  Will return the instance in the registry if it already exists.
 * 
 * @param string $helper Helper name to load
 * @param array $settings Settings for the helper.
 * @param boolean $enable Whether or not this helper should be enabled by default
 * @return Helper A helper object, Either the existing loaded helper or a new one.
 * @throws MissingHelperFileException, MissingHelperClassException when the helper could not be found
 */
	public function load($helper, $settings = array(), $enable = true) {
		list($plugin, $name) = pluginSplit($helper, true);

		if (isset($this->_loaded[$name])) {
			return $this->_loaded[$name];
		}
		$helperClass = $name . 'Helper';
		if (!class_exists($helperClass)) {
			if (!App::import('Helper', $helper)) {
				throw new MissingHelperFileException(array(
					'class' => $helperClass,
					'file' => Inflector::underscore($name) . '.php'
				));
			}
			if (!class_exists($helperClass)) {
				throw new MissingHelperClassException(array(
					'class' => $helperClass,
					'file' => Inflector::underscore($name) . '.php'
				));
			}
		}
		$this->_loaded[$name] = new $helperClass($this->_View, $settings);

		$vars = array('request', 'theme', 'plugin');
		foreach ($vars as $var) {
			$this->_loaded[$name]->{$var} = $this->_View->{$var};
		}

		if ($enable === true) {
			$this->_enabled[] = $name;
		}
		return $this->_loaded[$name];
	}

}