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
 * @subpackage		cake.cake.libs.view.helpers
 * @since			CakePHP v 1.1.7.3328
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
 * @subpackage	cake.cake.libs.view.helpers
 *
 */
class SessionHelper extends AppHelper {
	var $__Session;
	var $__active = true;

/**
 * Enter description here...
 *
 */
	function __construct($base = null) {
		if (!defined('AUTO_SESSION') || AUTO_SESSION == true) {
			$this->__Session =& new CakeSession($base);
		} else {
			$this->__active = false;
		}
		parent::__construct();
	}

	function read($name = null) {
		if ($this->__active === true) {
			return $this->__Session->readSessionVar($name);
		}
		return false;
	}

	function check($name) {
		if ($this->__active === true) {
			return $this->__Session->checkSessionVar($name);
		}
		return false;
	}

	function error() {
		if ($this->__active === true) {
			return $this->__Session->getLastError();
		}
		return false;
	}

	function flash($key = 'flash') {
		if ($this->__active === true) {
			if ($this->__Session->checkSessionVar('Message.' . $key)) {
				e($this->__Session->readSessionVar('Message.' . $key));
				$this->__Session->delSessionVar('Message.' . $key);
			} else {
				return false;
			}
		}
		return false;
	}

	function valid() {
		if ($this->__active === true) {
		return $this->__Session->isValid();
		}
	}
}

?>