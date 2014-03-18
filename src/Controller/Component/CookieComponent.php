<?php
/**
 * Cookie Component
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Error;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Utility\Hash;
use Cake\Utility\Security;

/**
 * Cookie Component.
 *
 * Cookie handling for the controller.
 *
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
 * If public $cookiePath is set to '/foo/', the cookie will only be available
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
 * Set to true to make HTTP only cookies. Cookies that are HTTP only
 * are not accessible in JavaScript.
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
 * Defaults to Security::encrypt(); or AES encryption.
 *
 * @var string
 */
	protected $_type = 'aes';

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
 * A reference to the Controller's Cake\Network\Response object
 *
 * @var \Cake\Network\Response
 */
	protected $_response = null;

/**
 * The request from the controller.
 *
 * @var \Cake\Network\Request
 */
	protected $_request;

/**
 * Constructor
 *
 * @param ComponentRegistry $collection A ComponentRegistry for this component
 * @param array $settings Array of settings.
 */
	public function __construct(ComponentRegistry $collection, $settings = array()) {
		$this->key = Configure::read('Security.salt');
		parent::__construct($collection, $settings);
		if (isset($this->time)) {
			$this->_expire($this->time);
		}

		$controller = $collection->getController();
		if ($controller && isset($controller->request)) {
			$this->_request = $controller->request;
		} else {
			$this->_request = Request::createFromGlobals();
		}

		if ($controller && isset($controller->response)) {
			$this->_response = $controller->response;
		} else {
			$this->_response = new Response();
		}
	}

/**
 * Start CookieComponent for use in the controller
 *
 * @param Event $event An Event instance
 * @return void
 */
	public function startup(Event $event) {
		$this->_expire($this->time);

		$this->_values[$this->name] = array();
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
 * @param integer|string $expires Can be either the number of seconds until a cookie
 *   expires, or a strtotime compatible time offset.
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/cookie.html#CookieComponent::write
 */
	public function write($key, $value = null, $encrypt = true, $expires = null) {
		if (empty($this->_values[$this->name])) {
			$this->read();
		}

		if ($encrypt === null) {
			$encrypt = true;
		}
		$this->_encrypted = $encrypt;
		$this->_expire($expires);

		if (!is_array($key)) {
			$key = array($key => $value);
		}

		foreach ($key as $name => $value) {
			$names = array($name);
			if (strpos($name, '.') !== false) {
				$names = explode('.', $name, 2);
			}
			$firstName = $names[0];
			$isMultiValue = (is_array($value) || count($names) > 1);

			if (!isset($this->_values[$this->name][$firstName]) && $isMultiValue) {
				$this->_values[$this->name][$firstName] = array();
			}

			if (count($names) > 1) {
				$this->_values[$this->name][$firstName] = Hash::insert(
					$this->_values[$this->name][$firstName],
					$names[1],
					$value
				);
			} else {
				$this->_values[$this->name][$firstName] = $value;
			}
			$this->_write('[' . $firstName . ']', $this->_values[$this->name][$firstName]);
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
		$values = $this->_request->cookie($this->name);
		if (empty($this->_values[$this->name]) && $values) {
			$this->_values[$this->name] = $this->_decrypt($values);
		}
		if (empty($this->_values[$this->name])) {
			$this->_values[$this->name] = array();
		}
		if ($key === null) {
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
 * Returns true if given key is set in the cookie.
 *
 * @param string $key Key to check for
 * @return boolean True if the key exists
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
 * $this->Cookie->delete('Name.key);
 *
 * You must use this method before any output is sent to the browser.
 * Failure to do so will result in header already sent errors.
 *
 * This method will delete both the top level and 2nd level cookies set.
 * For example assuming that $name = App, deleting `User` will delete
 * both `App[User]` and any other cookie values like `App[User][email]`
 * This is done to clean up cookie storage from before 2.4.3, where cookies
 * were stored inconsistently.
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
		if (empty($this->_values[$this->name])) {
			$this->read();
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
 * @throws \Cake\Error\Exception When an unknown type is used.
 */
	public function type($type = 'aes') {
		$availableTypes = [
			'rijndael',
			'aes'
		];
		if (!in_array($type, $availableTypes)) {
			throw new Error\Exception('You must use rijndael, or aes for cookie encryption type');
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
		if ($expires === null) {
			return $this->_expires;
		}
		$this->_reset = $this->_expires;
		if (!$expires) {
			return $this->_expires = 0;
		}
		$now = new \DateTime();

		if (is_int($expires) || is_numeric($expires)) {
			return $this->_expires = $now->format('U') + intval($expires);
		}
		$now->modify($expires);
		return $this->_expires = $now->format('U');
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

		if (!empty($this->_reset)) {
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
 * @return string Encoded values
 */
	protected function _encrypt($value) {
		if (is_array($value)) {
			$value = $this->_implode($value);
		}
		if (!$this->_encrypted) {
			return $value;
		}
		$prefix = "Q2FrZQ==.";
		if ($this->_type === 'rijndael') {
			$cipher = Security::rijndael($value, $this->key, 'encrypt');
		}
		if ($this->_type === 'aes') {
			$cipher = Security::encrypt($value, $this->key);
		}
		return $prefix . base64_encode($cipher);
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
					$decrypted[$name][$key] = $this->_decode($val);
				}
			} else {
				$decrypted[$name] = $this->_decode($value);
			}
		}
		return $decrypted;
	}

/**
 * Decodes and decrypts a single value.
 *
 * @param string $value The value to decode & decrypt.
 * @return string Decoded value.
 */
	protected function _decode($value) {
		$prefix = 'Q2FrZQ==.';
		$pos = strpos($value, $prefix);
		if ($pos === false) {
			return $this->_explode($value);
		}
		$value = base64_decode(substr($value, strlen($prefix)));
		if ($this->_type === 'rijndael') {
			$plain = Security::rijndael($value, $this->key, 'decrypt');
		}
		if ($this->_type === 'aes') {
			$plain = Security::decrypt($value, $this->key);
		}
		return $this->_explode($plain);
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
			return ($ret !== null) ? $ret : $string;
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
