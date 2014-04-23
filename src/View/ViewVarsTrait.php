<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c), Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Core\App;

/**
 * Provides the set() method for collecting template context.
 *
 * Once collected context data can be passed to another object.
 * This is done in Controller, TemplateTask and View for example.
 *
 */
trait ViewVarsTrait {

/**
 * Variables for the view
 *
 * @var array
 */
	public $viewVars = [];

/**
 * Constructs the view class instance based on object properties.
 *
 * @param string $viewClass Optional namespaced class name of the View class to instantiate.
 * @return View
 */
	public function createView($viewClass = null) {
		if ($viewClass === null) {
			$viewClass = $this->viewClass;
			if ($this->viewClass !== 'View') {
				list($plugin, $viewClass) = pluginSplit($viewClass, true);
				$viewClass = App::classname($viewClass, 'View', 'View');
			}
		}
		$viewOptions = array_intersect_key(get_object_vars($this), array_flip($this->_validViewOptions));
		return new $viewClass($this->request, $this->response, $this->getEventManager(), $viewOptions);
	}

/**
 * Saves a variable for use inside a template.
 *
 * @param string|array $name A string or an array of data.
 * @param string|array $val Value in case $name is a string (which then works as the key).
 *   Unused if $name is an associative array, otherwise serves as the values to $name's keys.
 * @return void
 */
	public function set($name, $val = null) {
		if (is_array($name)) {
			if (is_array($val)) {
				$data = array_combine($name, $val);
			} else {
				$data = $name;
			}
		} else {
			$data = [$name => $val];
		}
		$this->viewVars = $data + $this->viewVars;
	}

}
