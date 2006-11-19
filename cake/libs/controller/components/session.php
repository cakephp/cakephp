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
 * Short description for file.
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller.components
 *
 */
class SessionComponent extends Object{

/**
 * Enter description here...
 *
 */
	function __construct($base = null) {
		$this->CakeSession = new CakeSession($base);
		parent::__construct();
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
 * Writes a variable to the session.
 *
 * Use like this. $this->Session->write('Controller.sessKey', 'session value');
 *
 * @param unknown_type $name
 * @param unknown_type $value
 * @return unknown
 */
	function write($name, $value = null) {
		if (is_array($name)) {
			foreach ($name as $key => $val) {
				$this->CakeSession->writeSessionVar($key, $val);
			}
			return;
		}
		return $this->CakeSession->writeSessionVar($name, $value);
	}
/**
 * Reads a variable from the session.
 *
 * Use like this. $this->Session->read('Controller.sessKey');
 * Calling the method without a param will return all session vars
 *
 * @param unknown_type $name
 * @return unknown
 */
	function read($name = null) {
		return $this->CakeSession->readSessionVar($name);
	}
/**
 * Removes a variable from the session.
 *
 * Use like this. $this->Session->del('Controller.sessKey');
 *
 * @param unknown_type $name
 * @return unknown
 */
	function del($name) {
		return $this->CakeSession->delSessionVar($name);
	}
/**
 * Identical to del().
 * 
 * @param unknown_type $name
 * @return unknown
 */
	function delete($name) {
		return $this->del($name);
	}
/**
 * Checks whether the variable is set in the session.
 *
 * Use like this. $this->Session->check('Controller.sessKey');
 *
 * @param unknown_type $name
 * @return unknown
 */
	function check($name) {
		return $this->CakeSession->checkSessionVar($name);
	}
/**
 * Enter description here...
 *
 * Use like this. $this->Session->error();
 *
 * @return string Last session error
 */
	function error() {
		return $this->CakeSession->getLastError();
	}
/**
 * Enter description here...
 *
 * Use like this. $this->Session->setFlash('This has been saved');
 *
 * @param string $flashMessage Message to be flashed
 * @param string $layout Layout to wrap flash message in
 * @param array $params Parameters to be sent to layout as view variables
 * @param string $key Message key, default is 'flash'
 * @return string Last session error
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
 * Use like this. $this->Session->flash();
 *
 * @param string $key Optional message key
 * @return null
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
 * Renews session.
 *
 * Use like this. $this->Session->renew();
 * This will renew sessions
 *
 * @return boolean
 */
	function renew() {
		$this->CakeSession->renew();
	}
/**
 * Checks whether the session is valid.
 *
 * Use like this. $this->Session->valid();
 * This will return true if session is valid
 * false if session is invalid
 *
 * @return boolean
 */
	function valid() {
		return $this->CakeSession->isValid();
	}
/**
 * Destroys session.
 *
 * Use like this. $this->Session->destroy();
 * Used to destroy Sessions
 *
 */
	function destroy() {
		$this->CakeSession->destroyInvalid();
	}
}

?>