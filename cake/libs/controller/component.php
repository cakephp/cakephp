<?php
/**
 *
 * PHP 5
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
 * @subpackage    cake.cake.libs.controller
 * @since         CakePHP(tm) v 1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Controller', 'ComponentCollection', false);

/**
 * Base class for an individual Component.  Components provide resuable bits of
 * controller logic that can be composed into a controller.  Components also 
 * provide request life-cycle callbacks for injecting logic at specific points.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.controller
 * @link          http://book.cakephp.org/view/993/Components
 * @see Controller::$components
 */
class Component extends Object {

/**
 * Component collection class used to lazy load components.
 *
 * @var ComponentCollection
 */
	protected $_Collection;

/**
 * Settings for this Component
 *
 * @var array
 */
	public $settings = array();

/**
 * Other Components this component uses.
 *
 * @var array
 */
	public $components = array();

/**
 * A component lookup table used to lazy load component objects.
 *
 * @var array
 */
	protected $_componentMap = array();

/**
 * Constructor
 *
 * @param ComponentCollection $collection A ComponentCollection this component can use to lazy load its components
 * @param array $settings Array of configuration settings.
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$this->_Collection = $collection;
		$this->settings = $settings;
		$this->_set($settings);
		if (!empty($this->components)) {
			$this->_componentMap = ComponentCollection::normalizeObjectArray($this->components);
		}
	}

/**
 * Magic method for lazy loading $components.
 *
 * @param sting $name Name of component to get.
 * @return mixed A Component object or null.
 */
	public function __get($name) {
		if (isset($this->_componentMap[$name]) && !isset($this->{$name})) {
			$this->{$name} = $this->_Collection->load(
				$this->_componentMap[$name]['class'], $this->_componentMap[$name]['settings'], false
			);
		}
		if (isset($this->{$name})) {
			return $this->{$name};
		}
	}

/**
 * Called before the Controller::beforeFilter().
 *
 * @param object $controller Controller with components to initialize
 * @return void
 * @access public
 * @link http://book.cakephp.org/view/998/MVC-Class-Access-Within-Components
 */
	public function initialize(&$controller) { }

/**
 * Called after the Controller::beforeFilter() and before the controller action
 *
 * @param object $controller Controller with components to startup
 * @return void
 * @access public
 * @link http://book.cakephp.org/view/998/MVC-Class-Access-Within-Components
 */
	public function startup(&$controller) { }

/**
 * Called after the Controller::beforeRender(), after the view class is loaded, and before the
 * Controller::render()
 *
 * @param object $controller Controller with components to beforeRender
 * @return void
 * @access public
 */
	public function beforeRender(&$controller) { }

/**
 * Called after Controller::render() and before the output is printed to the browser.
 *
 * @param object $controller Controller with components to shutdown
 * @return void
 * @access public
 */
	function shutdown(&$controller) { }

/**
 * Called before Controller::redirect().
 *
 * @param object $controller Controller with components to beforeRedirect
 * @return void
 */
	public function beforeRedirect(&$controller, $url, $status = null, $exit = true) {
		$response = array();

		foreach ($this->_primary as $name) {
			$component =& $this->_loaded[$name];

			if ($component->enabled === true && method_exists($component, 'beforeRedirect')) {
				$resp = $component->beforeRedirect($controller, $url, $status, $exit);
				if ($resp === false) {
					return false;
				}
				$response[] = $resp;
			}
		}
		return $response;
	}


}
