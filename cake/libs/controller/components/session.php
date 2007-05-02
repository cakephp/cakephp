<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.controller.components
 * @since			CakePHP(tm) v 0.10.0.1232
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Session Component.
 *
 * Session handling from the controller.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller.components
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
 * Class constructor
 *
 * @param string $base
 */
	function __construct($base = null) {
		if (!defined('AUTO_SESSION') || AUTO_SESSION === true) {
			parent::__construct($base);
		} else {
			$this->__active = false;
		}
	}
/**
 * Turn sessions on if AUTO_SESSION is set to false in core.php
 *
 * @param string $base
 */
	function activate($base = null) {
		if ($this->__active === true) {
			return;
		}
		parent::__construct($base);
		$this->__active = true;
	}
/**
 * Startup method.  Copies controller data locally for rendering flash messages.
 *
 * @param object $controller
 * @access public
 */
	function startup(&$controller) {
		$this->base = $controller->base;
		$this->webroot = $controller->webroot;
		$this->here = $controller->here;
		$this->params = $controller->params;
		$this->action = $controller->action;
		$this->data = $controller->data;
		$this->plugin = $controller->plugin;
	}
/**
 * Used to write a value to a session key.
 *
 * In your controller: $this->Session->write('Controller.sessKey', 'session value');
 *
 * @param string $name The name of the key your are setting in the session.
 * 							This should be in a Controller.key format for better organizing
 * @param string $value The value you want to store in a session.
 * @access public
 */
	function write($name, $value = null) {
		if ($this->__active === true) {
			if(is_array($name)) {
				foreach($name as $key => $value) {
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
 *
 * @return values from the session vars
 * @access public
 */
	function read($name = null) {
		if ($this->__active === true) {
			return parent::read($name);
		}
		return false;
	}
/**
 * Used to delete a session variable.
 *
 * In your controller: $this->Session->del('Controller.sessKey');
 *
 * @param string $name
 * @return boolean, true is session variable is set and can be deleted, false is variable was not set.
 */
	function del($name) {
		if ($this->__active === true) {
			return parent::del($name);
		}
		return false;
	}
/**
 * Wrapper for SessionComponent::del();
 *
 * In your controller: $this->Session->delete('Controller.sessKey');
 *
 * @param string $name
 * @return boolean, true is session variable is set and can be deleted, false is variable was not set.
 * @access public
 */
	function delete($name) {
		if ($this->__active === true) {
			return $this->del($name);
		}
		return false;
	}
/**
 * Used to check if a session variable is set
 *
 * In your controller: $this->Session->check('Controller.sessKey');
 *
 * @param string $name
 * @return boolean true is session variable is set, false if not
 * @access public
 */
	function check($name) {
		if ($this->__active === true) {
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
			$this->write('Message.' . $key, compact('message', 'layout', 'params'));
		}
	}
/**
 * Used to renew a session id
 *
 * In your controller: $this->Session->renew();
 * @access public
 */
	function renew() {
		if ($this->__active === true) {
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
			return parent::valid();
		}
		return false;
	}
/**
 * Used to destroy sessions
 *
 * In your controller:. $this->Session->destroy();
 * @access public
 */
	function destroy() {
		if ($this->__active === true) {
			parent::destroy();
		}
	}
}
?>