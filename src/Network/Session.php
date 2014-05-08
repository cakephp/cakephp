<?php
/**
 * Session class for CakePHP.
 *
 * CakePHP abstracts the handling of sessions.
 * There are several convenient methods to access session information.
 * This class is the implementation of those methods.
 * They are mostly used by the Session Component.
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
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Network;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Error;
use Cake\Utility\Hash;
use SessionHandlerInterface;

/**
 * Session class for CakePHP.
 *
 * CakePHP abstracts the handling of sessions. There are several convenient methods to access session information.
 * This class is the implementation of those methods. They are mostly used by the Session Component.
 *
 */
class Session {

	protected $_engine;

	protected $_started;


	public static function create($sessionConfig = []) {
		if (isset($sessionConfig['defaults'])) {
			$defaults = static::_defaultConfig($sessionConfig['defaults']);
			if ($defaults) {
				$sessionConfig = Hash::merge($defaults, $sessionConfig);
			}
		}

		if (!isset($sessionConfig['ini']['session.cookie_secure']) && env('HTTPS')) {
			$sessionConfig['ini']['session.cookie_secure'] = 1;
		}

		if (isset($sessionConfig['timeout']) && !isset($sessionConfig['cookieTimeout'])) {
			$sessionConfig['cookieTimeout'] = $sessionConfig['timeout'];
		}

		if (!isset($sessionConfig['ini']['session.cookie_lifetime'])) {
			$sessionConfig['ini']['session.cookie_lifetime'] = $sessionConfig['cookieTimeout'] * 60;
		}

		if (!isset($sessionConfig['ini']['session.name'])) {
			$sessionConfig['ini']['session.name'] = $sessionConfig['cookie'];
		}

		if (!empty($sessionConfig['handler'])) {
			$sessionConfig['ini']['session.save_handler'] = 'user';
		}

		if (!isset($sessionConfig['ini']['session.gc_maxlifetime'])) {
			$sessionConfig['ini']['session.gc_maxlifetime'] = $sessionConfig['timeout'] * 60;
		}

		if (!isset($sessionConfig['ini']['session.cookie_httponly'])) {
			$sessionConfig['ini']['session.cookie_httponly'] = 1;
		}

		return new static($sessionConfig);
	}

/**
 * Get one of the prebaked default session configurations.
 *
 * @param string $name
 * @return bool|array
 */
	protected static function _defaultConfig($name) {
		$defaults = array(
			'php' => array(
				'checkAgent' => false,
				'cookie' => 'CAKEPHP',
				'timeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
				)
			),
			'cake' => array(
				'checkAgent' => false,
				'cookie' => 'CAKEPHP',
				'timeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
					'url_rewriter.tags' => '',
					'session.serialize_handler' => 'php',
					'session.use_cookies' => 1,
					'session.save_path' => TMP . 'sessions',
					'session.save_handler' => 'files'
				)
			),
			'cache' => array(
				'checkAgent' => false,
				'cookie' => 'CAKEPHP',
				'timeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
					'url_rewriter.tags' => '',
					'session.use_cookies' => 1,
					'session.save_handler' => 'user',
				),
				'handler' => array(
					'engine' => 'CacheSession',
					'config' => 'default'
				)
			),
			'database' => array(
				'checkAgent' => false,
				'cookie' => 'CAKEPHP',
				'timeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
					'url_rewriter.tags' => '',
					'session.use_cookies' => 1,
					'session.save_handler' => 'user',
					'session.serialize_handler' => 'php',
				),
				'handler' => array(
					'engine' => 'DatabaseSession'
				)
			)
		);

		if (isset($defaults[$name])) {
			return $defaults[$name];
		}
		return false;
	}

	public function __construct($config = []) {
		if (!empty($config['ini']) && is_array($config['ini'])) {
			$this->options($config['ini']);
		}

		if (!empty($config['handler']) && !isset($config['handler']['engine'])) {
			//TODO: REmove this feature
			call_user_func_array('session_set_save_handler', $config['handler']);
		}

		if (!empty($config['handler']['engine'])) {
			$class = $config['handler']['engine'];
			unset($config['handler']['engine']);
			session_set_save_handler($this->engine($class, $config['handler']), false);
		}

		session_register_shutdown();
	}

/**
 * Find the handler class and make sure it implements the correct interface.
 *
 * @return void
 * @throws \Cake\Error\Exception
 */
	public function engine($class = null, $options = []) {
		if ($class === null) {
			return $this->_engine;
		}

		$class = App::className($class, 'Network/Session');
		if (!class_exists($class)) {
			throw new Error\Exception(sprintf('Could not load %s to handle the session.', $class));
		}

		$handler = new $class($options);
		if (!($handler instanceof SessionHandlerInterface)) {
			throw new Error\Exception(
				'Chosen SessionHandler does not implement SessionHandlerInterface, it cannot be used with an engine key.'
			);
		}

		return $this->_engine = $handler;
	}

	public function options(array $options) {
		if (session_status() === \PHP_SESSION_ACTIVE) {
			return;
		}

		foreach ($options as $setting => $value) {
			if (ini_set($setting, $value) === false) {
				throw new Error\Exception(sprintf(
					sprintf('Unable to configure the session, setting %s failed.'),
					$setting
				));
			}
		}
	}

/**
 * Starts the Session.
 *
 * @return bool True if session was started
 */
	public function start() {
		if ($this->_started) {
			return true;
		}

		if (php_sapi_name() === 'cli') {
			$_SESSION = [];
			return $this->_started = true;
		}

		if (session_status() === \PHP_SESSION_ACTIVE) {
			throw new \RuntimeException('Session was already started');
		}

		if (ini_get('session.use_cookies') && headers_sent($file, $line)) {
			throw new \RuntimeException(
				sprintf('Cannot start session, headers already sent in "%s" at line %d', $file, $line)
			);
		}

		if (!session_start()) {
			throw new \RuntimeException('Could not start the session');
		}

		return $this->_started = true;
	}

/**
 * Determine if Session has been started.
 *
 * @return bool True if session has been started.
 */
	public function started() {
		return $this->_started || session_status() === \PHP_SESSION_ACTIVE;
	}

/**
 * Returns true if given variable is set in session.
 *
 * @param string $name Variable name to check for
 * @return bool True if variable is there
 */
	public function check($name = null) {
		if (empty($name)) {
			return false;
		}

		if ($this->_hasSession() && !$this->started()) {
			$this->start();
		}

		return Hash::get($_SESSION, $name) !== null;
	}

/**
 * Returns given session variable, or all of them, if no parameters given.
 *
 * @param string|array $name The name of the session variable (or a path as sent to Set.extract)
 * @return mixed The value of the session variable, null if session not available,
 *   session not started, or provided name not found in the session.
 */
	public function read($name = null) {
		if (empty($name) && $name !== null) {
			return null;
		}

		if ($this->_hasSession() && !$this->started()) {
			$this->start();
		}

		if (!isset($_SESSION)) {
			return null;
		}

		if ($name === null) {
			return isset($_SESSION) ? $_SESSION : [];
		}

		return Hash::get($_SESSION, $name);
	}

/**
 * Writes value to given session variable name.
 *
 * @param string|array $name Name of variable
 * @param string $value Value to write
 * @return bool True if the write was successful, false if the write failed
 */
	public function write($name, $value = null) {
		if (empty($name)) {
			return;
		}

		if (!$this->started()) {
			$this->start();
		}

		$write = $name;
		if (!is_array($name)) {
			$write = array($name => $value);
		}

		$data = $_SESSION ?: [];
		foreach ($write as $key => $val) {
			$data = Hash::insert($data, $key, $val);
		}

		$this->_overwrite($_SESSION, $data);
	}

/**
 * Returns the session id.
 * Calling this method will not auto start the session. You might have to manually
 * assert a started session.
 *
 * Passing an id into it, you can also replace the session id if the session
 * has not already been started.
 * Note that depending on the session handler, not all characters are allowed
 * within the session id. For example, the file session handler only allows
 * characters in the range a-z A-Z 0-9 , (comma) and - (minus).
 *
 * @param string $id Id to replace the current session id
 * @return string Session id
 */
	public function id($id = null) {
		if ($id) {
			session_id($id);
		}

		return session_id();
	}

/**
 * Removes a variable from session.
 *
 * @param string $name Session variable to remove
 * @return bool Success
 */
	public function delete($name) {
		if ($this->check($name)) {
			$this->_overwrite($_SESSION, Hash::remove($_SESSION, $name));
		}
	}

/**
 * Used to write new data to _SESSION, since PHP doesn't like us setting the _SESSION var itself.
 *
 * @param array $old Set of old variables => values
 * @param array $new New set of variable => value
 * @return void
 */
	protected function _overwrite(&$old, $new) {
		if (!empty($old)) {
			foreach ($old as $key => $var) {
				if (!isset($new[$key])) {
					unset($old[$key]);
				}
			}
		}
		foreach ($new as $key => $var) {
			$old[$key] = $var;
		}
	}

/**
 * Helper method to destroy invalid sessions.
 *
 * @return void
 */
	public function destroy() {
		if ($this->_hasSession() && !$this->started()) {
			$this->start();
		}

		session_destroy();

		$_SESSION = [];
		$this->_started = false;
	}

/**
 * Clears the session, the session id, and renews the session.
 *
 * @return void
 */
	public function clear() {
		$_SESSION = [];
		$this->renew();
	}

/**
 * Returns whether a session exists
 * @return bool
 */
	protected function _hasSession() {
		return !ini_get('session.use_cookies') || isset($_COOKIE[session_name()]);
	}

/**
 * Restarts this session.
 *
 * @return void
 */
	public function renew() {
		if (!$this->_hasSession()) {
			return;
		}

		$params = session_get_cookie_params();
		setcookie(
			session_name(), '', time() - 42000,
			$params['path'], $params['domain'],
			$params['secure'], $params['httponly']
		);
		session_regenerate_id(true);
	}

}
