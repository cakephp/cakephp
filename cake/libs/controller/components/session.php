<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs.controller.components
 * @since			CakePHP v 0.10.0.1232
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
 * Class constructor
 *
 * @param string $base
 */
	function __construct($base = null) {
		parent::__construct($base);
	}
/**
 * Startup method.  Copies controller data locally for rendering flash messages.
 *
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
 */
	function write($name, $value) {
		$this->writeSessionVar($name, $value);
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
 */
	function read($name = null) {
		return $this->readSessionVar($name);
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
		return $this->delSessionVar($name);
	}
/**
 * Wrapper for SessionComponent::del();
 *
 * In your controller: $this->Session->delete('Controller.sessKey');
 *
 * @param string $name
 * @return boolean, true is session variable is set and can be deleted, false is variable was not set.
 */
	function delete($name) {
		return $this->del($name);
	}
/**
 * Used to check if a session variable is set
 *
 * In your controller: $this->Session->check('Controller.sessKey');
 *
 * @param string $name
 * @return boolean true is session variable is set, false if not
 */
	function check($name) {
		return $this->checkSessionVar($name);
	}
/**
 * Used to determine the last error in a session.
 *
 * In your controller: $this->Session->error();
 *
 * @return string Last session error
 */
	function error() {
		return $this->getLastError();
	}
/**
 * Used to set a session variable that can be used to output messages in the view.
 *
 * In your controller: $this->Session->setFlash('This has been saved');
 *
 * Additional params below can be passed to customize the output, or the Message.[key]
 *
 * @param string $flashMessage Message to be flashed
 * @param string $layout Layout to wrap flash message in
 * @param array $params Parameters to be sent to layout as view variables
 * @param string $key Message key, default is 'flash'
 */
	function setFlash($flashMessage, $layout = 'default', $params = array(), $key = 'flash') {
		if ($layout == 'default') {
			$out = '<div id="' . $key . 'Message" class="message">' . $flashMessage . '</div>';
		} else if($layout == '' || $layout == null) {
			$out = $flashMessage;
		} else {
			$ctrl = null;
			$view = new View($ctrl);
			$view->base			= $this->base;
			$view->webroot		= $this->webroot;
			$view->here			= $this->here;
			$view->params		= $this->params;
			$view->action		= $this->action;
			$view->data			= $this->data;
			$view->plugin		= $this->plugin;
			$view->helpers		= array('Html');
			$view->layout		= $layout;
			$view->pageTitle	= '';
			$view->_viewVars	= $params;
			$out = $view->renderLayout($flashMessage);
		}
		$this->write('Message.' . $key, $out);
	}
/**
 * This method is deprecated.
 * You should use $session->flash('key'); in your views
 *
 * @param string $key Optional message key
 * @return boolean or renders output directly.
 * @deprecated
 */
	function flash($key = 'flash') {
		if ($this->check('Message.' . $key)) {
			e($this->read('Message.' . $key));
			$this->del('Message.' . $key);
		} else {
			return false;
		}
	}
/**
 * Used to renew a session id
 *
 * In your controller: $this->Session->renew();
 */
	function renew() {
		parent::renew();
	}
/**
 * Used to check for a valid session.
 *
 * In your controller: $this->Session->valid();
 *
 * @return boolean true is session is valid, false is session is invalid
 */
	function valid() {
		return $this->isValid();
	}
/**
 * Used to destroy sessions
 *
 * In your controller:. $this->Session->destroy();
 */
	function destroy() {
		$this->destroyInvalid();
	}
}

?>