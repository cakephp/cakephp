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
 * @package       Cake.Model.Datasource
 * @since         CakePHP(tm) v .0.10.0.1222
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Hash', 'Utility');
App::uses('Security', 'Utility');

/**
 * Session class for CakePHP.
 *
 * CakePHP abstracts the handling of sessions. There are several convenient methods to access session information.
 * This class is the implementation of those methods. They are mostly used by the Session Component.
 *
 * @package       Cake.Model.Datasource
 */
class CakeSession {

/**
 * True if the Session is still valid
 *
 * @var bool
 */
	public static $valid = false;

/**
 * Error messages for this session
 *
 * @var array
 */
	public static $error = false;

/**
 * User agent string
 *
 * @var string
 */
	protected static $_userAgent = '';

/**
 * Path to where the session is active.
 *
 * @var string
 */
	public static $path = '/';

/**
 * Error number of last occurred error
 *
 * @var int
 */
	public static $lastError = null;

/**
 * Start time for this session.
 *
 * @var int
 */
	public static $time = false;

/**
 * Cookie lifetime
 *
 * @var int
 */
	public static $cookieLifeTime;

/**
 * Time when this session becomes invalid.
 *
 * @var int
 */
	public static $sessionTime = false;

/**
 * Current Session id
 *
 * @var string
 */
	public static $id = null;

/**
 * Hostname
 *
 * @var string
 */
	public static $host = null;

/**
 * Session timeout multiplier factor
 *
 * @var int
 */
	public static $timeout = null;

/**
 * Number of requests that can occur during a session time without the session being renewed.
 * This feature is only used when config value `Session.autoRegenerate` is set to true.
 *
 * @var int
 * @see CakeSession::_checkValid()
 */
	public static $requestCountdown = 10;

/**
 * Whether or not the init function in this class was already called
 *
 * @var bool
 */
	protected static $_initialized = false;

/**
 * Session cookie name
 *
 * @var string
 */
	protected static $_cookieName = null;

/**
 * Pseudo constructor.
 *
 * @param string|null $base The base path for the Session
 * @return void
 */
	public static function init($base = null) {
		static::$time = time();

		if (env('HTTP_USER_AGENT') && !static::$_userAgent) {
			static::$_userAgent = md5(env('HTTP_USER_AGENT') . Configure::read('Security.salt'));
		}

		static::_setPath($base);
		static::_setHost(env('HTTP_HOST'));

		if (!static::$_initialized) {
			register_shutdown_function('session_write_close');
		}

		static::$_initialized = true;
	}

/**
 * Setup the Path variable
 *
 * @param string|null $base base path
 * @return void
 */
	protected static function _setPath($base = null) {
		if (empty($base)) {
			static::$path = '/';
			return;
		}
		if (strpos($base, 'index.php') !== false) {
			$base = str_replace('index.php', '', $base);
		}
		if (strpos($base, '?') !== false) {
			$base = str_replace('?', '', $base);
		}
		static::$path = $base;
	}

/**
 * Set the host name
 *
 * @param string $host Hostname
 * @return void
 */
	protected static function _setHost($host) {
		static::$host = $host;
		if (strpos(static::$host, ':') !== false) {
			static::$host = substr(static::$host, 0, strpos(static::$host, ':'));
		}
	}

/**
 * Starts the Session.
 *
 * @return bool True if session was started
 */
	public static function start() {
		if (static::started()) {
			return true;
		}

		$id = static::id();
		static::_startSession();
		if (!$id && static::started()) {
			static::_checkValid();
		}

		static::$error = false;
		static::$valid = true;
		return static::started();
	}

/**
 * Determine if Session has been started.
 *
 * @return bool True if session has been started.
 */
	public static function started() {
		if (function_exists('session_status')) {
			return isset($_SESSION) && (session_status() === PHP_SESSION_ACTIVE);
		}
		return isset($_SESSION) && session_id();
	}

/**
 * Returns true if given variable is set in session.
 *
 * @param string $name Variable name to check for
 * @return bool True if variable is there
 */
	public static function check($name) {
		if (empty($name) || !static::_hasSession() || !static::start()) {
			return false;
		}

		return Hash::get($_SESSION, $name) !== null;
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
 * @param string|null $id Id to replace the current session id
 * @return string Session id
 */
	public static function id($id = null) {
		if ($id) {
			static::$id = $id;
			session_id(static::$id);
		}
		if (static::started()) {
			return session_id();
		}
		return static::$id;
	}

/**
 * Removes a variable from session.
 *
 * @param string $name Session variable to remove
 * @return bool Success
 */
	public static function delete($name) {
		if (static::check($name)) {
			static::_overwrite($_SESSION, Hash::remove($_SESSION, $name));
			return !static::check($name);
		}
		return false;
	}

/**
 * Used to write new data to _SESSION, since PHP doesn't like us setting the _SESSION var itself.
 *
 * @param array &$old Set of old variables => values
 * @param array $new New set of variable => value
 * @return void
 */
	protected static function _overwrite(&$old, $new) {
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
 * Return error description for given error number.
 *
 * @param int $errorNumber Error to set
 * @return string Error as string
 */
	protected static function _error($errorNumber) {
		if (!is_array(static::$error) || !array_key_exists($errorNumber, static::$error)) {
			return false;
		}
		return static::$error[$errorNumber];
	}

/**
 * Returns last occurred error as a string, if any.
 *
 * @return mixed Error description as a string, or false.
 */
	public static function error() {
		if (static::$lastError) {
			return static::_error(static::$lastError);
		}
		return false;
	}

/**
 * Returns true if session is valid.
 *
 * @return bool Success
 */
	public static function valid() {
		if (static::start() && static::read('Config')) {
			if (static::_validAgentAndTime() && static::$error === false) {
				static::$valid = true;
			} else {
				static::$valid = false;
				static::_setError(1, 'Session Highjacking Attempted !!!');
			}
		}
		return static::$valid;
	}

/**
 * Tests that the user agent is valid and that the session hasn't 'timed out'.
 * Since timeouts are implemented in CakeSession it checks the current static::$time
 * against the time the session is set to expire. The User agent is only checked
 * if Session.checkAgent == true.
 *
 * @return bool
 */
	protected static function _validAgentAndTime() {
		$config = static::read('Config');
		$validAgent = (
			Configure::read('Session.checkAgent') === false ||
			isset($config['userAgent']) && static::$_userAgent === $config['userAgent']
		);
		return ($validAgent && static::$time <= $config['time']);
	}

/**
 * Get / Set the user agent
 *
 * @param string|null $userAgent Set the user agent
 * @return string Current user agent.
 */
	public static function userAgent($userAgent = null) {
		if ($userAgent) {
			static::$_userAgent = $userAgent;
		}
		if (empty(static::$_userAgent)) {
			CakeSession::init(static::$path);
		}
		return static::$_userAgent;
	}

/**
 * Returns given session variable, or all of them, if no parameters given.
 *
 * @param string|null $name The name of the session variable (or a path as sent to Set.extract)
 * @return mixed The value of the session variable, null if session not available,
 *   session not started, or provided name not found in the session, false on failure.
 */
	public static function read($name = null) {
		if (empty($name) && $name !== null) {
			return null;
		}
		if (!static::_hasSession() || !static::start()) {
			return null;
		}
		if ($name === null) {
			return static::_returnSessionVars();
		}
		$result = Hash::get($_SESSION, $name);

		if (isset($result)) {
			return $result;
		}
		return null;
	}

/**
 * Returns all session variables.
 *
 * @return mixed Full $_SESSION array, or false on error.
 */
	protected static function _returnSessionVars() {
		if (!empty($_SESSION)) {
			return $_SESSION;
		}
		static::_setError(2, 'No Session vars set');
		return false;
	}

/**
 * Writes value to given session variable name.
 *
 * @param string|array $name Name of variable
 * @param string $value Value to write
 * @return bool True if the write was successful, false if the write failed
 */
	public static function write($name, $value = null) {
		if (empty($name) || !static::start()) {
			return false;
		}

		$write = $name;
		if (!is_array($name)) {
			$write = array($name => $value);
		}
		foreach ($write as $key => $val) {
			static::_overwrite($_SESSION, Hash::insert($_SESSION, $key, $val));
			if (Hash::get($_SESSION, $key) !== $val) {
				return false;
			}
		}
		return true;
	}

/**
 * Reads and deletes a variable from session.
 *
 * @param string $name The key to read and remove (or a path as sent to Hash.extract).
 * @return mixed The value of the session variable, null if session not available,
 *   session not started, or provided name not found in the session.
 */
	public static function consume($name) {
		if (empty($name)) {
			return null;
		}
		$value = static::read($name);
		if ($value !== null) {
			static::_overwrite($_SESSION, Hash::remove($_SESSION, $name));
		}
		return $value;
	}

/**
 * Helper method to destroy invalid sessions.
 *
 * @return void
 */
	public static function destroy() {
		if (!static::started()) {
			static::_startSession();
		}

		if (static::started()) {
			if (session_id() && static::_hasSession()) {
				session_write_close();
				session_start();
			}
			session_destroy();
			unset($_COOKIE[static::_cookieName()]);
		}

		$_SESSION = null;
		static::$id = null;
		static::$_cookieName = null;
	}

/**
 * Clears the session.
 *
 * Optionally also clears the session id and renews the session.
 *
 * @param bool $renew If the session should also be renewed. Defaults to true.
 * @return void
 */
	public static function clear($renew = true) {
		if (!$renew) {
			$_SESSION = array();
			return;
		}

		$_SESSION = null;
		static::$id = null;
		static::renew();
	}

/**
 * Helper method to initialize a session, based on CakePHP core settings.
 *
 * Sessions can be configured with a few shortcut names as well as have any number of ini settings declared.
 *
 * @return void
 * @throws CakeSessionException Throws exceptions when ini_set() fails.
 */
	protected static function _configureSession() {
		$sessionConfig = Configure::read('Session');

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
		static::$_cookieName = $sessionConfig['ini']['session.name'];

		if (!empty($sessionConfig['handler'])) {
			$sessionConfig['ini']['session.save_handler'] = 'user';
		} elseif (!empty($sessionConfig['session.save_path']) && Configure::read('debug')) {
			if (!is_dir($sessionConfig['session.save_path'])) {
				mkdir($sessionConfig['session.save_path'], 0775, true);
			}
		}

		if (!isset($sessionConfig['ini']['session.gc_maxlifetime'])) {
			$sessionConfig['ini']['session.gc_maxlifetime'] = $sessionConfig['timeout'] * 60;
		}
		if (!isset($sessionConfig['ini']['session.cookie_httponly'])) {
			$sessionConfig['ini']['session.cookie_httponly'] = 1;
		}

		if (empty($_SESSION)) {
			if (!empty($sessionConfig['ini']) && is_array($sessionConfig['ini'])) {
				foreach ($sessionConfig['ini'] as $setting => $value) {
					if (ini_set($setting, $value) === false) {
						throw new CakeSessionException(__d('cake_dev', 'Unable to configure the session, setting %s failed.', $setting));
					}
				}
			}
		}
		if (!empty($sessionConfig['handler']) && !isset($sessionConfig['handler']['engine'])) {
			call_user_func_array('session_set_save_handler', $sessionConfig['handler']);
		}
		if (!empty($sessionConfig['handler']['engine'])) {
			$handler = static::_getHandler($sessionConfig['handler']['engine']);
			session_set_save_handler(
				array($handler, 'open'),
				array($handler, 'close'),
				array($handler, 'read'),
				array($handler, 'write'),
				array($handler, 'destroy'),
				array($handler, 'gc')
			);
		}
		Configure::write('Session', $sessionConfig);
		static::$sessionTime = static::$time + ($sessionConfig['timeout'] * 60);
	}

/**
 * Get session cookie name.
 *
 * @return string
 */
	protected static function _cookieName() {
		if (static::$_cookieName !== null) {
			return static::$_cookieName;
		}

		static::init();
		static::_configureSession();

		return static::$_cookieName = session_name();
	}

/**
 * Returns whether a session exists
 *
 * @return bool
 */
	protected static function _hasSession() {
		return static::started() || isset($_COOKIE[static::_cookieName()]) || (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
	}

/**
 * Find the handler class and make sure it implements the correct interface.
 *
 * @param string $handler Handler name.
 * @return void
 * @throws CakeSessionException
 */
	protected static function _getHandler($handler) {
		list($plugin, $class) = pluginSplit($handler, true);
		App::uses($class, $plugin . 'Model/Datasource/Session');
		if (!class_exists($class)) {
			throw new CakeSessionException(__d('cake_dev', 'Could not load %s to handle the session.', $class));
		}
		$handler = new $class();
		if ($handler instanceof CakeSessionHandlerInterface) {
			return $handler;
		}
		throw new CakeSessionException(__d('cake_dev', 'Chosen SessionHandler does not implement CakeSessionHandlerInterface it cannot be used with an engine key.'));
	}

/**
 * Get one of the prebaked default session configurations.
 *
 * @param string $name Config name.
 * @return bool|array
 */
	protected static function _defaultConfig($name) {
		$defaults = array(
			'php' => array(
				'cookie' => 'CAKEPHP',
				'timeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
					'session.cookie_path' => static::$path
				)
			),
			'cake' => array(
				'cookie' => 'CAKEPHP',
				'timeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
					'url_rewriter.tags' => '',
					'session.serialize_handler' => 'php',
					'session.use_cookies' => 1,
					'session.cookie_path' => static::$path,
					'session.save_path' => TMP . 'sessions',
					'session.save_handler' => 'files'
				)
			),
			'cache' => array(
				'cookie' => 'CAKEPHP',
				'timeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
					'url_rewriter.tags' => '',
					'session.use_cookies' => 1,
					'session.cookie_path' => static::$path,
					'session.save_handler' => 'user',
				),
				'handler' => array(
					'engine' => 'CacheSession',
					'config' => 'default'
				)
			),
			'database' => array(
				'cookie' => 'CAKEPHP',
				'timeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
					'url_rewriter.tags' => '',
					'session.use_cookies' => 1,
					'session.cookie_path' => static::$path,
					'session.save_handler' => 'user',
					'session.serialize_handler' => 'php',
				),
				'handler' => array(
					'engine' => 'DatabaseSession',
					'model' => 'Session'
				)
			)
		);
		if (isset($defaults[$name])) {
			return $defaults[$name];
		}
		return false;
	}

/**
 * Helper method to start a session
 *
 * @return bool Success
 */
	protected static function _startSession() {
		static::init();
		session_write_close();
		static::_configureSession();

		if (headers_sent()) {
			if (empty($_SESSION)) {
				$_SESSION = array();
			}
		} else {
			// For IE<=8
			session_cache_limiter("must-revalidate");
			session_start();
		}
		return true;
	}

/**
 * Helper method to create a new session.
 *
 * @return void
 */
	protected static function _checkValid() {
		$config = static::read('Config');
		if ($config) {
			$sessionConfig = Configure::read('Session');

			if (static::valid()) {
				static::write('Config.time', static::$sessionTime);
				if (isset($sessionConfig['autoRegenerate']) && $sessionConfig['autoRegenerate'] === true) {
					$check = $config['countdown'];
					$check -= 1;
					static::write('Config.countdown', $check);

					if ($check < 1) {
						static::renew();
						static::write('Config.countdown', static::$requestCountdown);
					}
				}
			} else {
				$_SESSION = array();
				static::destroy();
				static::_setError(1, 'Session Highjacking Attempted !!!');
				static::_startSession();
				static::_writeConfig();
			}
		} else {
			static::_writeConfig();
		}
	}

/**
 * Writes configuration variables to the session
 *
 * @return void
 */
	protected static function _writeConfig() {
		static::write('Config.userAgent', static::$_userAgent);
		static::write('Config.time', static::$sessionTime);
		static::write('Config.countdown', static::$requestCountdown);
	}

/**
 * Restarts this session.
 *
 * @return void
 */
	public static function renew() {
		if (session_id() === '') {
			return;
		}
		if (isset($_COOKIE[static::_cookieName()])) {
			setcookie(Configure::read('Session.cookie'), '', time() - 42000, static::$path);
		}
		if (!headers_sent()) {
			session_write_close();
			session_start();
			session_regenerate_id(true);
		}
	}

/**
 * Helper method to set an internal error message.
 *
 * @param int $errorNumber Number of the error
 * @param string $errorMessage Description of the error
 * @return void
 */
	protected static function _setError($errorNumber, $errorMessage) {
		if (static::$error === false) {
			static::$error = array();
		}
		static::$error[$errorNumber] = $errorMessage;
		static::$lastError = $errorNumber;
	}

}
