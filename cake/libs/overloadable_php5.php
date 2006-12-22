<?php
/* SVN FILE: $Id$ */
/**
 * Overload abstraction interface.  Merges differences between PHP4 and 5.
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
 * @subpackage		cake.cake.libs
 * @since			CakePHP v 1.2
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Overloadable class selector
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */

/**
 * Load the interface class based on the version of PHP.
 *
 */
class Overloadable extends Object {

	function overload() { }

	function __call($method, $params) {
		if(!method_exists($this, '__call__')) {
			trigger_error(sprintf(__('Magic method handler __call__ not defined in %s', true), get_class($this)), E_USER_ERROR);
		}
		return $this->__call__($method, $params);
	}
}

class Overloadable2 extends Object {

	function overload() { }

	function __call($method, $params) {
		if(!method_exists($this, '__call__')) {
			trigger_error(sprintf(__('Magic method handler __call__ not defined in %s', true), get_class($this)), E_USER_ERROR);
		}
		return $this->__call__($method, $params);
	}

	function __get($name) {
		return $this->get__($name);
	}

	function __set($name, $value) {
		return $this->set__($name, $value);
	}
}

?>