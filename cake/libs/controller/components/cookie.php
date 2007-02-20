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
 * @since			CakePHP(tm) v 1.2.0.4213
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Load Security class
 */
if(!class_exists('Security')){
	uses('Security');
}
/**
 * Cookie Component.
 *
 * Cookie handling for the controller.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller.components
 *
 */
class CookieComponent extends Object {
/**
 * The name of the cookie.
 *
 * Overridden with the controller var $cookieName;
 *
 * @var string
 * @access protected
 */
	var $_name = 'CakeCookie';
/**
 * Expire time of the cookie
 *
 * Overridden with the controller var $cookieTime;
 *
 * Number of seconds before you want it to expire.
 * If not set, the cookie will expire at the end of the session (when the browser closes).
 *
 * @var string
 * @access protected
 */
	var $_expire = 0;
/**
 * Cookie path.
 *
 * Overridden with the controller var $cookiePath;
 *
 * The path on the server in which the cookie will be available on.
 * If  var $cookiePath is set to '/foo/', the cookie will only be available
 * within the /foo/ directory and all sub-directories such as /foo/bar/ of domain.
 * The default value is the entire domain.
 *
 * @var string
 * @access protected
 */
	var $_path = '/';
/**
 * Domain path.
 *
 * The domain that the cookie is available.
 *
 * Overridden with the controller var $cookieDomain
 *
 * To make the cookie available on all subdomains of example.com.
 * Set var $cookieDomain = '.example.com'; in your controller
 *
 * @var string
 * @access protected
 */
	var $_domain = '';
/**
 * Secure HTTPS only cookie.
 *
 * Overridden with the controller var $cookieSecure
 *
 * Indicates that the cookie should only be transmitted over a secure HTTPS connection.
 * When set to true, the cookie will only be set if a secure connection exists.
 *
 * @var boolean
 * @access protected
 */
	var $_secure = false;
/**
 * Encryption key.
 *
 * Overridden with the controller var $cookieKey
 *
 * @var string
 * @access protected
 */
	var $_key = 'khu@j1!A*$tNx$mD*^8zD5';
/**
 * Values stored in the cookie.
 *
 * Accessed in the controller using $this->Cookie->read('Name.key');
 *
 * @see CookieComponent::read();
 * @var string
 * @access private
 */
	var $__values = array();
/**
 * Type of encryption to use.
 *
 * Currently only one method is available
 * Defaults to Security::cipher();
 *
 * @var string
 * @access private
 * @todo add additional encryption methods
 */
	var $__type = 'cipher';
/**
 * Used to reset cookie time if $expire is passed to CookieComponent::write()
 *
 * @var string
 * @access private
 */
	var $__reset = null;
/**
 * Sets the default values for the cookie
 *
 * @param object $name
 * @param integer $expire
 * @access private
 */
	function startup($controller = null) {
		if(is_object($controller)){
			if(!isset($controller->cookieName)) {
				trigger_error(__('For added security you should add the var cookieName to your Controller or AppController', true), E_USER_NOTICE);
			} else {
				$this->_name = $controller->cookieName;
			}
			if(isset($controller->cookieTime)){
				$this->__expire($controller->cookieTime);
			}
			if(!isset($controller->cookieKey)){
				trigger_error(__('For added security you should add the var cookieKey to your Controller or AppController', true), E_USER_NOTICE);
			} else {
				$this->_key = $controller->cookieKey;
			}
			if(isset($controller->cookiePath)) {
				$this->_path = $controller->cookiePath;
			}
			if(!isset($controller->cookieDomain)) {
				trigger_error(__('Add var cookieDomain = .yourdomain.com; to your Controller or AppController to allow access on all subdomains', true), E_USER_NOTICE);
			} else {
				$this->_domain = $controller->cookieDomain;
			}
			if(isset($controller->cookieSecure)) {
				$this->_secure = $controller->cookieSecure;
			}
		}

		if(isset($_COOKIE[$this->_name])) {
			$this->__values = $this->__decrypt($_COOKIE[$this->_name]);
		}
	}
/**
 * Write a value to the $_COOKIE[$config];
 *
 * Optional [Name.], reguired key, optional $value, optional $encrypt, optional $expires
 * $this->Cookie->write('[Name.]key, $value);
 *
 * By default all values are encrypted.
 * You must pass $encrypt false to store values in clear test
 *
 * You must use this method before any output is sent to the browser.
 * Failure to do so will result in header already sent errors.
 *
 * @param mixed $key
 * @param mixed $value
 * @param boolean $encrypt
 * @param string $expires
 * @access public
 */
	function write($key, $value = null, $encrypt = true, $expires = null) {
		if(is_null($encrypt)){
			$encrypt = true;
		}

		$this->__encrypted = $encrypt;
		$this->__expire($expires);

		if(!is_array($key) && $value !== null) {
			$name = $this->__cookieVarNames($key);

			if(count($name) > 1){
				$this->__values[$name[0]][$name[1]] = $value;
				$this->__write("[".$name[0]."][".$name[1]."]", $value);
			} else {
				$this->__values[$name[0]] = $value;
				$this->__write("[".$name[0]."]", $value);
			}
		} else {
			foreach($key as $names => $value){
				$name = $this->__cookieVarNames($names);

				if(count($name) > 1){
					$this->__values[$name[0]][$name[1]] = $value;
					$this->__write("[".$name[0]."][".$name[1]."]", $value);
				} else {
					$this->__values[$name[0]] = $value;
					$this->__write("[".$name[0]."]", $value);
				}
			}
		}
	}
/**
 * Read the value of the $_COOKIE[$var];
 *
 * Optional [Name.], reguired key
 * $this->Cookie->read(Name.key);
 *
 * @param mixed $key
 * @return string or null
 * @access public
 */
	function read($key = null) {
		if(is_null($key)){
			return $this->__values;
		}
		$name = $this->__cookieVarNames($key);

		if(count($name) > 1){
			if(isset($this->__values[$name[0]])) {
				$value = $this->__values[$name[0]][$name[1]];
				return $value;
			}
			return null;
		} else {
			if(isset($this->__values[$name[0]])) {
				$value = $this->__values[$name[0]];
				return $value;
			}
			return null;
		}
	}
/**
 * Delete a cookie value
 *
 * Optional [Name.], reguired key
 * $this->Cookie->read('Name.key);
 *
 * You must use this method before any output is sent to the browser.
 * Failure to do so will result in header already sent errors.
 *
 * @param string $name
 * @access public
 */
	function del($key) {
		$name = $this->__cookieVarNames($key);

		if(count($name) > 1){
			if(isset($this->__values[$name[0]])) {
				unset($this->__values[$name[0]][$name[1]]);
				$this->__delete("[".$name[0]."][".$name[1]."]");
			}
		} else {
			if(isset($this->__values[$name[0]])) {
				if(is_array($this->__values[$name[0]])) {
					foreach ($this->__values[$name[0]] as $key => $value) {
						$this->__delete("[".$name[0]."][".$key."]");
					}
				} else {
					$this->__delete("[".$name[0]."]");
				}
				unset($this->__values[$name[0]]);
			}
		}
	}
/**
 * Destroy current cookie
 *
 * You must use this method before any output is sent to the browser.
 * Failure to do so will result in header already sent errors.
 *
 * @access public
 */
	function destroy() {
		if(isset($_COOKIE[$this->_name])) {
			$this->__values = $this->__decrypt($_COOKIE[$this->_name]);
		}

		foreach ($this->__values as $name => $value) {
			if(is_array($value)) {
				foreach ($value as $key => $val) {
					unset($this->__values[$name][$key]);
					$this->__delete("[$name][$key]");
				}
			} else {
				unset($this->__values[$name]);
				$this->__delete("[$name]");
			}
		}
		$this->__delete("[$name]");
	}
/**
 * Will allow overriding default encryption method.
 *
 * @param string $type
 * @access public
 * @todo NOT IMPLEMENTED
 */
	function type($type = 'cipher') {
		$this->__type = 'cipher';
	}
/**
 * Set the expire time for a session variable.
 *
 * Creates a new expire time for a session variable.
 * $expire can be either integer Unix timestamp or a date string.
 *
 * Used by write()
 * CookieComponent::write(string, string, boolean, 8400);
 * CookieComponent::write(string, string, boolean, '5 Days');
 *
 * @param mixed $expires
 * @return integer
 * @access private
 */
	function __expire($expires = null){
		$now = time();
		if(is_null($expires)){
			return $this->_expire;
		}
		$this->__reset = $this->_expire;
		if (is_integer($expires) || is_numeric($expires)) {
			return $this->_expire = $now + intval($expires);
		} else {
			return $this->_expire = strtotime($expires, $now);
		}
	}
/**
 * Set cookie
 *
 * @param string $name
 * @param string $value
 * @access private
 */
	function __write($name, $value) {
		setcookie($this->_name."$name", $this->__encrypt($value), $this->_expire, $this->_path, $this->_domain, $this->_secure);

		if(!is_null($this->__reset)){
			$this->_expire = $this->__reset;
			$this->__reset = null;
		}
		$this->__encrypted = true;
	}
/**
 * Sets a cookie expire time to remove cookie value
 *
 * @param string $name
 * @access private
 */
	function __delete($name) {
		setcookie($this->_name.$name, '', time() - 42000, $this->_path, $this->_domain, $this->_secure);
	}
/**
  * Encrypts $value using var $type method in Security class
  *
  * @param string $value
  * @return encrypted string
  * @access private
  */
	 function __encrypt($value) {
	 	if(is_array($value)){
	 		$value = $this->__implode($value);
	 	}

	 	if($this->__encrypted === true) {
	 		$type = $this->__type;
	 		$value = "Q2FrZQ==." .base64_encode(Security::$type($value, $this->_key));
	 	}
	 	return($value);
	}
/**
 * Decrypts $value using var $type method in Security class
 *
 * @param string $values
 * @return decrypted string
 * @access private
 */
	function __decrypt($values) {
		$decrypted = array();
		$type = $this->__type;

		foreach($values as $name => $value) {
			if(is_array($value)){
				foreach ($value as $key => $val) {
					$pos = strpos($val, 'Q2FrZQ==.');
					$decrypted[$name][$key] = $this->__explode($val);

					if ($pos !== false) {
						$val = substr($val, 8);
						$decrypted[$name][$key] = $this->__explode(Security::$type(base64_decode($val), $this->_key));
					}
				}
			} else {
					$pos = strpos($value, 'Q2FrZQ==.');
					$decrypted[$name] = $this->__explode($value);

					if ($pos !== false) {
						$value = substr($value, 8);
						$decrypted[$name] = $this->__explode(Security::$type(base64_decode($value), $this->_key));
					}
			}
		}

		return($decrypted);
	}

/**
 * Creates an array from the $name parameter which allows the dot notation
 * similar to one used by Session and Configure classes
 *
 * @param string $name
 * @return array
 * @access private
 */
	function __cookieVarNames($name) {
		if (is_string($name)) {
			if (strpos($name, ".")) {
				$name = explode(".", $name);
			} else {
				$name = array($name);
			}
		}
		return $name;
	}
/**
 * Implode method to keep keys are multidimensional arrays
 *
 * @param array $array
 * @return string
 * @access private
 */
	function __implode($array) {
		$string = '';
		foreach ($array as $key => $value) {
			$string .= ',' . $key . '|' . $value;
		}
		return substr($string, 1);
	}
/**
 * Explode method to return array from string set in CookieComponent::__implode()
 *
 * @param string $string
 * @return array
 * @access private
 */
	function __explode($string) {
		$array = array();
		foreach (explode(',', $string) as $pair) {
			$key = explode('|', $pair);
			if(!isset($key[1])){
				return $key[0];
			}
			$array[$key[0]] = $key[1];
		}
		return $array;
	}
}
?>