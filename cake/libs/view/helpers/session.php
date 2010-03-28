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
 * @subpackage    cake.cake.libs.view.helpers
 * @since         CakePHP(tm) v 1.1.7.3328
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
if (!class_exists('cakesession')) {
	uses('session');
}

/**
 * Session Helper.
 *
 * Session reading from the view.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 *
 */
class SessionHelper extends CakeSession {
/**
 * List of helpers used by this helper
 *
 * @var array
 */
	var $helpers = null;
/**
 * Used to determine if methods implementation is used, or bypassed
 *
 * @var boolean
 */
	var $__active = true;
/**
 * Class constructor
 *
 * @param string $base
 */
	function __construct($base = null) {
		if (Configure::read('Session.start') === true) {
			parent::__construct($base, false);
			$this->start();
			$this->__active = true;
		} else {
			$this->__active = false;
		}
	}
/**
 * Turn sessions on if 'Session.start' is set to false in core.php
 *
 * @param string $base
 */
	function activate($base = null) {
		$this->__active = true;
	}
/**
 * Used to read a session values set in a controller for a key or return values for all keys.
 *
 * In your view: $session->read('Controller.sessKey');
 * Calling the method without a param will return all session vars
 *
 * @param string $name the name of the session key you want to read
 *
 * @return values from the session vars
 * @access public
 */
	function read($name = null) {
		if ($this->__active === true && $this->__start()) {
			return parent::read($name);
		}
		return false;
	}
/**
 * Used to check is a session key has been set
 *
 * In your view: $session->check('Controller.sessKey');
 *
 * @param string $name
 * @return boolean
 * @access public
 */
	function check($name) {
		if ($this->__active === true && $this->__start()) {
			return parent::check($name);
		}
		return false;
	}
/**
 * Returns last error encountered in a session
 *
 * In your view: $session->error();
 *
 * @return string last error
 * @access public
 */
	function error() {
		if ($this->__active === true && $this->__start()) {
			return parent::error();
		}
		return false;
	}
/**
 * Used to render the message set in Controller::Session::setFlash()
 *
 * In your view: $session->flash('somekey');
 * 					Will default to flash if no param is passed
 *
 * @param string $key The [Message.]key you are rendering in the view.
 * @return string Will echo the value if $key is set, or false if not set.
 * @access public
 */
	function flash($key = 'flash') {
		if ($this->__active === true && $this->__start()) {
			if (parent::check('Message.' . $key)) {
				$flash = parent::read('Message.' . $key);

				if ($flash['layout'] == 'default') {
					if (!empty($flash['params']['class'])) {
						$class = $flash['params']['class'];
					} else {
						$class = 'message';
					}
					$out = '<div id="' . $key . 'Message" class="' . $class . '">' . $flash['message'] . '</div>';
				} elseif ($flash['layout'] == '' || $flash['layout'] == null) {
					$out = $flash['message'];
				} else {
					$view =& ClassRegistry::getObject('view');
					list($tmpVars, $tmpTitle) = array($view->viewVars, $view->pageTitle);
					list($view->viewVars, $view->pageTitle) = array($flash['params'], '');
					$out = $view->renderLayout($flash['message'], $flash['layout']);
					list($view->viewVars, $view->pageTitle) = array($tmpVars, $tmpTitle);
				}
				echo($out);
				parent::del('Message.' . $key);
				return true;
			}
		}
		return false;
	}
/**
 * Used to check is a session is valid in a view
 *
 * @return boolean
 * @access public
 */
	function valid() {
		if ($this->__active === true && $this->__start()) {
			return parent::valid();
		}
	}
/**
 * Override CakeSession::write().
 * This method should not be used in a view
 *
 * @return boolean
 * @access public
 */
	function write() {
		trigger_error(__('You can not write to a Session from the view', true), E_USER_WARNING);
	}
/**
 * Determine if Session has been started
 * and attempt to start it if not
 *
 * @return boolean true if Session is already started, false if
 * Session could not be started
 * @access private
 */
	function __start() {
		if (!$this->started()) {
			return $this->start();
		}
		return true;
	}
}
?>