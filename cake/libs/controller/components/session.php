<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
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
 * @subpackage    cake.cake.libs.controller.components
 * @since         CakePHP(tm) v 0.10.0.1232
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
if (!class_exists('cakesession')) {
	require LIBS . 'session.php';
}
/**
 * Session Component.
 *
 * Session handling from the controller.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.controller.components
 *
 */
class SessionComponent extends CakeSession {
/**
 * Used to determine if methods implementation is used, or bypassed
 *
 * @var boolean
 * @access private
 */
	var $__active = true;
/**
 * Used to determine if request are from an Ajax request
 *
 * @var boolean
 * @access private
 */
	var $__bare = 0;
/**
 * Class constructor
 *
 * @param string $base The base path for the Session
 */
	function __construct($base = null) {
		if (Configure::read('Session.start') === true) {
			parent::__construct($base);
		} else {
			$this->__active = false;
		}
	}
/**
 * Initializes the component, gets a reference to Controller::$param['bare'].
 *
 * @param object $controller A reference to the controller
 * @return void
 * @access public
 */
	function initialize(&$controller) {
		if (isset($controller->params['bare'])) {
			$this->__bare = $controller->params['bare'];
		}
	}
/**
 * Startup method.
 *
 * @param object $controller Instantiating controller
 * @return void
 * @access public
 */
	function startup(&$controller) {
		if ($this->started() === false && $this->__active === true) {
			$this->__start();
		}
	}
/**
 * Starts Session on if 'Session.start' is set to false in core.php
 *
 * @param string $base The base path for the Session
 * @return void
 * @access public
 */
	function activate($base = null) {
		if ($this->__active === true) {
			return;
		}
		parent::__construct($base);
		$this->__active = true;
	}
/**
 * Used to write a value to a session key.
 *
 * In your controller: $this->Session->write('Controller.sessKey', 'session value');
 *
 * @param string $name The name of the key your are setting in the session.
 * 							This should be in a Controller.key format for better organizing
 * @param string $value The value you want to store in a session.
 * @return boolean Success
 * @access public
 */
	function write($name, $value = null) {
		if ($this->__active === true) {
			$this->__start();
			if (is_array($name)) {
				foreach ($name as $key => $value) {
					if (parent::write($key, $value) === false) {
						return false;
					}
				}
				return true;
			}
			if (parent::write($name, $value) === false) {
				return false;
			}
			return true;
		}
		return false;
	}
/**
 * Used to read a session values for a key or return values for all keys.
 *
 * In your controller: $this->Session->read('Controller.sessKey');
 * Calling the method without a param will return all session vars
 *
 * @param string $name the name of the session key you want to read
 * @return mixed value from the session vars
 * @access public
 */
	function read($name = null) {
		if ($this->__active === true) {
			$this->__start();
			return parent::read($name);
		}
		return false;
	}
/**
 * Used to delete a session variable.
 *
 * In your controller: $this->Session->del('Controller.sessKey');
 *
 * @param string $name the name of the session key you want to delete
 * @return boolean true is session variable is set and can be deleted, false is variable was not set.
 * @access public
 */
	function del($name) {
		if ($this->__active === true) {
			$this->__start();
			return parent::del($name);
		}
		return false;
	}
/**
 * Wrapper for SessionComponent::del();
 *
 * In your controller: $this->Session->delete('Controller.sessKey');
 *
 * @param string $name the name of the session key you want to delete
 * @return boolean true is session variable is set and can be deleted, false is variable was not set.
 * @access public
 */
	function delete($name) {
		if ($this->__active === true) {
			$this->__start();
			return $this->del($name);
		}
		return false;
	}
/**
 * Used to check if a session variable is set
 *
 * In your controller: $this->Session->check('Controller.sessKey');
 *
 * @param string $name the name of the session key you want to check
 * @return boolean true is session variable is set, false if not
 * @access public
 */
	function check($name) {
		if ($this->__active === true) {
			$this->__start();
			return parent::check($name);
		}
		return false;
	}
/**
 * Used to determine the last error in a session.
 *
 * In your controller: $this->Session->error();
 *
 * @return string Last session error
 * @access public
 */
	function error() {
		if ($this->__active === true) {
			$this->__start();
			return parent::error();
		}
		return false;
	}
/**
 * Used to set a session variable that can be used to output messages in the view.
 *
 * In your controller: $this->Session->setFlash('This has been saved');
 *
 * Additional params below can be passed to customize the output, or the Message.[key]
 *
 * @param string $message Message to be flashed
 * @param string $layout Layout to wrap flash message in
 * @param array $params Parameters to be sent to layout as view variables
 * @param string $key Message key, default is 'flash'
 * @access public
 */
	function setFlash($message, $layout = 'default', $params = array(), $key = 'flash') {
		if ($this->__active === true) {
			$this->__start();
			$this->write('Message.' . $key, compact('message', 'layout', 'params'));
		}
	}
/**
 * Used to renew a session id
 *
 * In your controller: $this->Session->renew();
 *
 * @return void
 * @access public
 */
	function renew() {
		if ($this->__active === true) {
			$this->__start();
			parent::renew();
		}
	}
/**
 * Used to check for a valid session.
 *
 * In your controller: $this->Session->valid();
 *
 * @return boolean true is session is valid, false is session is invalid
 * @access public
 */
	function valid() {
		if ($this->__active === true) {
			$this->__start();
			return parent::valid();
		}
		return false;
	}
/**
 * Used to destroy sessions
 *
 * In your controller: $this->Session->destroy();
 *
 * @return void
 * @access public
 */
	function destroy() {
		if ($this->__active === true) {
			$this->__start();
			parent::destroy();
		}
	}
/**
 * Returns Session id
 *
 * If $id is passed in a beforeFilter, the Session will be started
 * with the specified id
 *
 * @param $id string
 * @return string
 * @access public
 */
	function id($id = null) {
		return parent::id($id);
	}
/**
 * Starts Session if SessionComponent is used in Controller::beforeFilter(),
 * or is called from
 *
 * @return boolean
 * @access private
 */
	function __start() {
		if ($this->started() === false) {
			if (!$this->id() && parent::start()) {
				parent::_checkValid();
			} else {
				parent::start();
			}
		}
		return $this->started();
	}
}

?>