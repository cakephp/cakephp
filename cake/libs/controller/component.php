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
 * @package       cake.libs.controller
 * @since         CakePHP(tm) v 1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Controller', 'ComponentCollection', false);

/**
 * Base class for an individual Component.  Components provide resuable bits of
 * controller logic that can be composed into a controller.  Components also 
 * provide request life-cycle callbacks for injecting logic at specific points.
 *
 * ## Life cycle callbacks
 *
 * Components can provide several callbacks that are fired at various stages of the request
 * cycle.  The available callbacks are:
 *
 * - `initialize()` - Fired before the controller's beforeFilter method.
 * - `startup()` - Fired after the controller's beforeFilter method.
 * - `beforeRender()` - Fired before the view + layout are rendered.
 * - `shutdown()` - Fired after the action is complete and the view has been rendered 
 *    but before Controller::afterFilter(). 
 * - `beforeRedirect()` - Fired before a redirect() is done.
 *
 * @package       cake.libs.controller
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
			$settings = array_merge((array)$this->_componentMap[$name]['settings'], array('enabled' => false));
			$this->{$name} = $this->_Collection->load($this->_componentMap[$name]['class'], $settings);
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
 * @link http://book.cakephp.org/view/998/MVC-Class-Access-Within-Components
 */
	public function initialize($controller) { }

/**
 * Called after the Controller::beforeFilter() and before the controller action
 *
 * @param object $controller Controller with components to startup
 * @return void
 * @link http://book.cakephp.org/view/998/MVC-Class-Access-Within-Components
 */
	public function startup($controller) { }

/**
 * Called after the Controller::beforeRender(), after the view class is loaded, and before the
 * Controller::render()
 *
 * @param object $controller Controller with components to beforeRender
 * @return void
 */
	public function beforeRender($controller) { }

/**
 * Called after Controller::render() and before the output is printed to the browser.
 *
 * @param object $controller Controller with components to shutdown
 * @return void
 */
	function shutdown($controller) { }

/**
 * Called before Controller::redirect().  Allows you to replace the url that will
 * be redirected to with a new url. The return of this method can either be an array or a string.
 *
 * If the return is an array and contains a 'url' key.  You may also supply the following:
 * 
 * - `status` The status code for the redirect
 * - `exit` Whether or not the redirect should exit.
 *
 * If your response is a string or an array that does not contain a 'url' key it will 
 * be used as the new url to redirect to.
 *
 * @param object $controller Controller with components to beforeRedirect
 * @param mixed $url Either the string or url array that is being redirected to.
 * @param int $status The status code of the redirect
 * @param bool $exit Will the script exit.
 * @return mixed Either an array or null.
 */
	public function beforeRedirect($controller, $url, $status = null, $exit = true) {}

}
