<?php
/**
 * Cookie Component
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.4213
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Component', 'Controller');
App::uses('Security', 'Utility');
App::uses('Hash', 'Utility');

/**
 * Cookie Component.
 *
 * Cookie handling for the controller.
 *
 * @package       Cake.Controller.Component
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/cookie.html
 *
 */
class CookieComponent extends Component {

/**
 * The name of the cookie.
 *
 * Overridden with the controller beforeFilter();
 * $this->Cookie->name = 'CookieName';
 *
 * @var string
 */
	public $name = 'CakeCookie';

/**
 * The time a cookie will remain valid.
 *
 * Can be either integer Unix timestamp or a date string.
 *
 * Overridden with the controller beforeFilter();
 * $this->Cookie->time = '5 Days';
 *
 * @var mixed
 */
	public $time = null;

/**
 * Cookie path.
 *
 * Overridden with the controller beforeFilter();
 * $this->Cookie->path = '/';
 *
 * The path on the server in which the cookie will be available on.
 * If  public $cookiePath is set to '/foo/', the cookie will only be available
 * within the /foo/ directory and all sub-directories such as /foo/bar/ of domain.
 * The default value is the entire domain.
 *
 * @var string
 */
	public $path = '/';

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
 */
	public $domain = '';

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
 */
	public $secure = false;

/**
 * Encryption key.
 *
 * Overridden with the controller beforeFilter();
 * $this->Cookie->key = 'SomeRandomString';
 *
 * @var string
 */
	public $key = null;

/**
 * HTTP only cookie
 *
 * Set to true to make HTTP only cookies.  Cookies that are HTTP only
 * are not accessible in Javascript.
 *
 * @var boolean
 */
	public $httpOnly = false;

/**
 * Values stored in the cookie.
 *
 * Accessed in the controller using $this->Cookie->read('Name.key');
 *
 * @see CookieComponent::read();
 * @var string
 */
	protected $_values = array();

/**
 * Type of encryption to use.
 *
 * Currently two methods are available: cipher and rijndael
 * Defaults to Security::cipher();
 *
 * @var string
 */
	protected $_type = 'cipher';

/**
 * Used to reset cookie time if $expire is passed to CookieComponent::write()
 *
 * @var string
 */
	protected $_reset = null;

/**
 * Expire time of the cookie
 *
 * This is controlled by CookieComponent::time;
 *
 * @var string
 */
	protected $_expires = 0;

/**
 * A reference to the Controller's CakeResponse object
 *
 * @var CakeResponse
 */
	protected $_response = null;

/**
 * Constructor
 *
 * @param ComponentCollection $collection A ComponentCollection for this component
 * @param array $settings Array of settings.
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$this->key = Configure::read('Security.salt');
		parent::__construct($collection, $settings);
		if (isset($this->time)) {
			$this->_expire($this->time);
		}

		$controller = $collection->getController();
		if ($controller && isset($controller->response)) {
			$this->_response = $controller->response;
		} else {
			$this->_response = new CakeResponse(array('charset' => Configure::read('App.encoding')));
		}
	}

/**
 * Start CookieComponent for use in the controller
 *
 * @param Controller $controller
 * @return void
 */
	public function startup(Controller $controller) {
		$this->_expire($this->time);

		$this->_values[$this->name] = array();
		if (isset($_COOKIE[$this->name])) {
			$this->_values[$this->name] = $this->_decrypt($_COOKIE[$this->name]);
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
 * @param string|array $key Key for the value
 * @param mixed $value Value
 * @param boolean $encrypt Set to true to encrypt value, false otherwise
 * @param integer|string $expires Can be either Unix timestamp, or date string
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/cookie.html#CookieComponent::write
 */
	public function write($key, $value = null, $encrypt = true, $expires = null) {
		if (empty($this->_values[$this->name])) {
			$this->read();
		}

		if (is_null($encrypt)) {
			$encrypt = true;
		}
		$this->_encrypted = $encrypt;
		$this->_expire($expires);

		if (!is_array($key)) {
			$key = array($key => $value);
		}

		foreach ($key as $name => $value) {
			if (strpos($name, '.') === false) {
				$this->_values[$this->name][$name] = $value;
				$this->_write("[$name]", $value);
			} else {
				$names = explode('.', $name, 2);
				if (!isset($this->_values[$this->name][$names[0]])) {
					$this->_values[$this->name][$names[0]] = array();
				}
				$this->_values[$this->name][$names[0]] = Hash::insert($this->_values[$this->name][$names[0]], $names[1], $value);
				$this->_write('[' . implode('][', $names) . ']', $value);
			}
		}
		$this->_encrypted = true;
	}

/**
 * Read the value of the $_COOKIE[$key];
 *
 * Optional [Name.], required key
 * $this->Cookie->read(Name.key);
 *
 * @param string $key Key of the value to be obtained. If none specified, obtain map key => values
 * @return string or null, value for specified key
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/cookie.html#CookieComponent::read
 */
	public function read($key = null) {
		if (empty($this->_values[$this->name]) && isset($_COOKIE[$this->name])) {
			$this->_values[$this->name] = $this->_decrypt($_COOKIE[$this->name]);
		}
		if (empty($this->_values[$this->name])) {
			$this->_values[$this->name] = array();
		}
		if (is_null($key)) {
			return $this->_values[$this->name];
		}

		if (strpos($key, '.') !== false) {
			$names = explode('.', $key, 2);
			$key = $names[0];
		}
		if (!isset($this->_values[$this->name][$key])) {
			return null;
		}

		if (!empty($names[1])) {
			return Hash::get($this->_values[$this->name][$key], $names[1]);
		}
		return $this->_values[$this->name][$key];
	}

/**
 * Returns true if given variable is set in cookie.
 *
 * @param string $var Variable name to check for
 * @return boolean True if variable is there
 */
	public function check($key = null) {
		if (empty($key)) {
			return false;
		}
		return $this->read($key) !== null;
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/cookie.html#CookieComponent::delete
 */
	public function delete($key) {
		if (empty($this->_values[$this->name])) {
			$this->read();
		}
		if (strpos($key, '.') === false) {
			if (isset($this->_values[$this->name][$key]) && is_array($this->_values[$this->name][$key])) {
				foreach ($this->_values[$this->name][$key] as $idx => $val) {
					$this->_delete("[$key][$idx]");
				}
			}
			$this->_delete("[$key]");
			unset($this->_values[$this->name][$key]);
			return;
		}
		$names = explode('.', $key, 2);
		if (isset($this->_values[$this->name][$names[0]])) {
			$this->_values[$this->name][$names[0]] = Hash::remove($this->_values[$this->name][$names[0]], $names[1]);
		}
		$this->_delete('[' . implode('][', $names) . ']');
	}

/**
 * Destroy current cookie
 *
 * You must use this method before any output is sent to the browser.
 * Failure to do so will result in header already sent errors.
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/cookie.html#CookieComponent::destroy
 */
	public function destroy() {
		if (isset($_COOKIE[$this->name])) {
			$this->_values[$this->name] = $this->_decrypt($_COOKIE[$this->name]);
		}

		foreach ($this->_values[$this->name] as $name => $value) {
			if (is_array($value)) {
				foreach ($value as $key => $val) {
					unset($this->_values[$this->name][$name][$key]);
					$this->_delete("[$name][$key]");
				}
			}
			unset($this->_values[$this->name][$name]);
			$this->_delete("[$name]");
		}
	}

/**
 * Will allow overriding default encryption method. Use this method
 * in ex: AppController::beforeFilter() before you have read or
 * written any cookies.
 *
 * @param string $type Encryption method
 * @return void
 */
	public function type($type = 'cipher') {
		$availableTypes = array(
			'cipher',
			'rijndael'
		);
		if (!in_array($type, $availableTypes)) {
			trigger_error(__d('cake_dev', 'You must use cipher or rijndael for cookie encryption type'), E_USER_WARNING);
			$type = 'cipher';
		}
		$this->_type = $type;
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
 * @param integer|string $expires Can be either Unix timestamp, or date string
 * @return integer Unix timestamp
 */
	protected function _expire($expires = null) {
		$now = time();
		if (is_null($expires)) {
			return $this->_expires;
		}
		$this->_reset = $this->_expires;

		if ($expires == 0) {
			return $this->_expires = 0;
		}

		if (is_int($expires) || is_numeric($expires)) {
			return $this->_expires = $now + intval($expires);
		}
		return $this->_expires = strtotime($expires, $now);
	}

/**
 * Set cookie
 *
 * @param string $name Name for cookie
 * @param string $value Value for cookie
 * @return void
 */
	protected function _write($name, $value) {
		$this->_response->cookie(array(
			'name' => $this->name . $name,
			'value' => $this->_encrypt($value),
			'expire' => $this->_expires,
			'path' => $this->path,
			'domain' => $this->domain,
			'secure' => $this->secure,
			'httpOnly' => $this->httpOnly
		));

		if (!is_null($this->_reset)) {
			$this->_expires = $this->_reset;
			$this->_reset = null;
		}
	}

/**
 * Sets a cookie expire time to remove cookie value
 *
 * @param string $name Name of cookie
 * @return void
 */
	protected function _delete($name) {
		$this->_response->cookie(array(
			'name' => $this->name . $name,
			'value' => '',
			'expire' => time() - 42000,
			'path' => $this->path,
			'domain' => $this->domain,
			'secure' => $this->secure,
			'httpOnly' => $this->httpOnly
		));
	}

/**
 * Encrypts $value using public $type method in Security class
 *
 * @param string $value Value to encrypt
 * @return string encrypted string
 * @return string Encoded values
 */
	protected function _encrypt($value) {
		if (is_array($value)) {
			$value = $this->_implode($value);
		}

		if ($this->_encrypted === true) {
			$type = $this->_type;
			$value = "Q2FrZQ==." . base64_encode(Security::$type($value, $this->key, 'encrypt'));
		}
		return $value;
	}

/**
 * Decrypts $value using public $type method in Security class
 *
 * @param array $values Values to decrypt
 * @return string decrypted string
 */
	protected function _decrypt($values) {
		$decrypted = array();
		$type = $this->_type;

		foreach ((array)$values as $name => $value) {
			if (is_array($value)) {
				foreach ($value as $key => $val) {
					$pos = strpos($val, 'Q2FrZQ==.');
					$decrypted[$name][$key] = $this->_explode($val);

					if ($pos !== false) {
						$val = substr($val, 8);
						$decrypted[$name][$key] = $this->_explode(Security::$type(base64_decode($val), $this->key, 'decrypt'));
					}
				}
			} else {
				$pos = strpos($value, 'Q2FrZQ==.');
				$decrypted[$name] = $this->_explode($value);

				if ($pos !== false) {
					$value = substr($value, 8);
					$decrypted[$name] = $this->_explode(Security::$type(base64_decode($value), $this->key, 'decrypt'));
				}
			}
		}
		return $decrypted;
	}

/**
 * Implode method to keep keys are multidimensional arrays
 *
 * @param array $array Map of key and values
 * @return string A json encoded string.
 */
	protected function _implode(array $array) {
		return json_encode($array);
	}

/**
 * Explode method to return array from string set in CookieComponent::_implode()
 * Maintains reading backwards compatibility with 1.x CookieComponent::_implode().
 *
 * @param string $string A string containing JSON encoded data, or a bare string.
 * @return array Map of key and values
 */
	protected function _explode($string) {
		$first = substr($string, 0, 1);
		if ($first === '{' || $first === '[') {
			$ret = json_decode($string, true);
			return ($ret != null) ? $ret : $string;
		}
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

