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
 * @subpackage		cake.cake.libs.view.helpers
 * @since			CakePHP(tm) v 1.1.7.3328
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Session Helper.
 *
 * Session reading from the view.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.view.helpers
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
		if (!defined('AUTO_SESSION') || AUTO_SESSION === true) {
			parent::__construct($base, false);
		} else {
			$this->__active = false;
		}
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
 */
	function read($name = null) {
		if ($this->__active === true) {
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
 */
	function check($name) {
		if ($this->__active === true) {
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
 */
	function error() {
		if ($this->__active === true) {
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
 */
	function flash($key = 'flash') {
		if ($this->__active === true) {
			if (parent::check('Message.' . $key)) {
				e(parent::read('Message.' . $key));
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
 */
	function valid() {
		if ($this->__active === true) {
		return parent::valid();
		}
	}
}
?>