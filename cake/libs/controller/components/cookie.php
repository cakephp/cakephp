<?php
/**
 * Cookie Component
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
 * @since         CakePHP(tm) v 1.2.0.4213
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Load Security class
 */
App::import('Core', 'Security');

/**
 * Cookie Component.
 *
 * Cookie handling for the controller.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.controller.components
 * @link http://book.cakephp.org/view/1280/Cookies
 *
 */
class CookieComponent extends Object {

/**
 * The name of the cookie.
 *
 * Overridden with the controller beforeFilter();
 * $this->Cookie->name = 'CookieName';
 *
 * @var string
 * @access public
 */
	var $name = 'CakeCookie';

/**
 * The time a cookie will remain valid.
 *
 * Can be either integer Unix timestamp or a date string.
 *
 * Overridden with the controller beforeFilter();
 * $this->Cookie->time = '5 Days';
 *
 * @var mixed
 * @access public
 */
	var $time = null;

/**
 * Cookie path.
 *
 * Overridden with the controller beforeFilter();
 * $this->Cookie->path = '/';
 *
 * The path on the server in which the cookie will be available on.
 * If  var $cookiePath is set to '/foo/', the cookie will only be available
 * within the /foo/ directory and all sub-directories such as /foo/bar/ of domain.
 * The default value is the entire domain.
 *
 * @var string
 * @access public
 */
	var $path = '/';

/**
 * Domain path.
 *
 * The domain that the cookie is available.
 *
 * Overridden with the controller beforeFilter();
 * $this->Cookie->domain = '.example.com';
 *
 * To make the cookie available on all subdomains of example.com.
 * Set $this->Cookie->domain = '.example.com'; in your controller beforeFilter
 *
 * @var string
 * @access public
 */
	var $domain = '';

/**
 * Secure HTTPS only cookie.
 *
 * Overridden with the controller beforeFilter();
 * $this->Cookie->secure = true;
 *
 * Indicates that the cookie should only be transmitted over a secure HTTPS connection.
 * When set to true, the cookie will only be set if a secure connection exists.
 *
 * @var boolean
 * @access public
 */
	var $secure = false;

/**
 * Encryption key.
 *
 * Overridden with the controller beforeFilter();
 * $this->Cookie->key = 'SomeRandomString';
 *
 * @var string
 * @access protected
 */
	var $key = null;

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
 * Expire time of the cookie
 *
 * This is controlled by CookieComponent::time;
 *
 * @var string
 * @access private
 */
	var $__expires = 0;

/**
 * Main execution method.
 *
 * @param object $controller A reference to the instantiating controller object
 * @access public
 */
	function initialize(&$controller, $settings) {
		$this->key = Configure::read('Security.salt');
		$this->_set($settings);
		if (isset($this->time)) {
			$this->__expire($this->time);
		}
	}

/**
 * Start CookieComponent for use in the controller
 *
 * @access public
 */
	function startup() {
		$this->__expire($this->time);

		if (isset($_COOKIE[$this->name])) {
			$this->__values = $this->__decrypt($_COOKIE[$this->name]);
		}
	}

/**
 * Write a value to the $_COOKIE[$key];
 *
 * Optional [Name.], required key, optional $value, optional $encrypt, optional $expires
 * $this->Cookie->write('[Name.]key, $value);
 *
 * By default all values are encrypted.
 * You must pass $encrypt false to store values in clear test
 *
 * You must use this method before any output is sent to the browser.
 * Failure to do so will result in header already sent errors.
 *
 * @param mixed $key Key for the value
 * @param mixed $value Value
 * @param boolean $encrypt Set to true to encrypt value, false otherwise
 * @param string $expires Can be either Unix timestamp, or date string
 * @access public
 */
	function write($key, $value = null, $encrypt = true, $expires = null) {
		if (is_null($encrypt)) {
			$encrypt = true;
		}
		$this->__encrypted = $encrypt;
		$this->__expire($expires);

		if (!is_array($key)) {
			$key = array($key => $value);
		}

		foreach ($key as $name => $value) {
			if (strpos($name, '.') === false) {
				$this->__values[$name] = $value;
				$this->__write("[$name]", $value);

			} else {
				$names = explode('.', $name, 2);
				if (!isset($this->__values[$names[0]])) {
					$this->__values[$names[0]] = array();
				}
				$this->__values[$names[0]] = Set::insert($this->__values[$names[0]], $names[1], $value);
				$this->__write('[' . implode('][', $names) . ']', $value);
			}
		}
		$this->__encrypted = true;
	}

/**
 * Read the value of the $_COOKIE[$key];
 *
 * Optional [Name.], required key
 * $this->Cookie->read(Name.key);
 *
 * @param mixed $key Key of the value to be obtained. If none specified, obtain map key => values
 * @return string or null, value for specified key
 * @access public
 */
	function read($key = null) {
		if (empty($this->__values) && isset($_COOKIE[$this->name])) {
			$this->__values = $this->__decrypt($_COOKIE[$this->name]);
		}

		if (is_null($key)) {
			return $this->__values;
		}

		if (strpos($key, '.') !== false) {
			$names = explode('.', $key, 2);
			$key = $names[0];
		}
		if (!isset($this->__values[$key])) {
			return null;
		}

		if (!empty($names[1])) {
			return Set::extract($this->__values[$key], $names[1]);
		}
		return $this->__values[$key];
	}

/**
 * Delete a cookie value
 *
 * Optional [Name.], required key
 * $this->Cookie->read('Name.key);
 *
 * You must use this method before any output is sent to the browser.
 * Failure to do so will result in header already sent errors.
 *
 * @param string $key Key of the value to be deleted
 * @return void
 * @access public
 */
	function delete($key) {
		if (empty($this->__values)) {
			$this->read();
		}
		if (strpos($key, '.') === false) {
			if (isset($this->__values[$key]) && is_array($this->__values[$key])) {
				foreach ($this->__values[$key] as $idx => $val) {
					$this->__delete("[$key][$idx]");
				}
			}
			$this->__delete("[$key]");
			unset($this->__values[$key]);
			return;
		}
		$names = explode('.', $key, 2);
		$this->__values[$names[0]] = Set::remove($this->__values[$names[0]], $names[1]);
		$this->__delete('[' . implode('][', $names) . ']');
	}

/**
 * Destroy current cookie
 *
 * You must use this method before any output is sent to the browser.
 * Failure to do so will result in header already sent errors.
 *
 * @return void
 * @access public
 */
	function destroy() {
		if (isset($_COOKIE[$this->name])) {
			$this->__values = $this->__decrypt($_COOKIE[$this->name]);
		}

		foreach ($this->__values as $name => $value) {
			if (is_array($value)) {
				foreach ($value as $key => $val) {
					unset($this->__values[$name][$key]);
					$this->__delete("[$name][$key]");
				}
			}
			unset($this->__values[$name]);
			$this->__delete("[$name]");
		}
	}

/**
 * Will allow overriding default encryption method.
 *
 * @param string $type Encryption method
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
 * @param mixed $expires Can be either Unix timestamp, or date string
 * @return int Unix timestamp
 * @access private
 */
	function __expire($expires = null) {
		$now = time();
		if (is_null($expires)) {
			return $this->__expires;
		}
		$this->__reset = $this->__expires;

		if ($expires == 0) {
			return $this->__expires = 0;
		}

		if (is_integer($expires) || is_numeric($expires)) {
			return $this->__expires = $now + intval($expires);
		}
		return $this->__expires = strtotime($expires, $now);
	}

/**
 * Set cookie
 *
 * @param string $name Name for cookie
 * @param string $value Value for cookie
 * @access private
 */
	function __write($name, $value) {
		setcookie($this->name . $name, $this->__encrypt($value), $this->__expires, $this->path, $this->domain, $this->secure);

		if (!is_null($this->__reset)) {
			$this->__expires = $this->__reset;
			$this->__reset = null;
		}
	}

/**
 * Sets a cookie expire time to remove cookie value
 *
 * @param string $name Name of cookie
 * @access private
 */
	function __delete($name) {
		setcookie($this->name . $name, '', time() - 42000, $this->path, $this->domain, $this->secure);
	}

/**
 * Encrypts $value using var $type method in Security class
 *
 * @param string $value Value to encrypt
 * @return string encrypted string
 * @access private
 */
	function __encrypt($value) {
		if (is_array($value)) {
			$value = $this->__implode($value);
		}

		if ($this->__encrypted === true) {
			$type = $this->__type;
			$value = "Q2FrZQ==." .base64_encode(Security::$type($value, $this->key));
		}
		return $value;
	}

/**
 * Decrypts $value using var $type method in Security class
 *
 * @param array $values Values to decrypt
 * @return string decrypted string
 * @access private
 */
	function __decrypt($values) {
		$decrypted = array();
		$type = $this->__type;

		foreach ((array)$values as $name => $value) {
			if (is_array($value)) {
				foreach ($value as $key => $val) {
					$pos = strpos($val, 'Q2FrZQ==.');
					$decrypted[$name][$key] = $this->__explode($val);

					if ($pos !== false) {
						$val = substr($val, 8);
						$decrypted[$name][$key] = $this->__explode(Security::$type(base64_decode($val), $this->key));
					}
				}
			} else {
				$pos = strpos($value, 'Q2FrZQ==.');
				$decrypted[$name] = $this->__explode($value);

				if ($pos !== false) {
					$value = substr($value, 8);
					$decrypted[$name] = $this->__explode(Security::$type(base64_decode($value), $this->key));
				}
			}
		}
		return $decrypted;
	}

/**
 * Implode method to keep keys are multidimensional arrays
 *
 * @param array $array Map of key and values
 * @return string String in the form key1|value1,key2|value2
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
 * @param string $string String in the form key1|value1,key2|value2
 * @return array Map of key and values
 * @access private
 */
	function __explode($string) {
		$array = array();
		foreach (explode(',', $string) as $pair) {
			$key = explode('|', $pair);
			if (!isset($key[1])) {
				return $key[0];
			}
			$array[$key[0]] = $key[1];
		}
		return $array;
	}
}
