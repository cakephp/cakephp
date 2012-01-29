<?php
/**
 * Session class for Cake.
 *
 * Cake abstracts the handling of sessions.
 * There are several convenient methods to access session information.
 * This class is the implementation of those methods.
 * They are mostly used by the Session Component.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model.Datasource
 * @since         CakePHP(tm) v .0.10.0.1222
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Set', 'Utility');
App::uses('Security', 'Utility');

/**
 * Session class for Cake.
 *
 * Cake abstracts the handling of sessions. There are several convenient methods to access session information.
 * This class is the implementation of those methods. They are mostly used by the Session Component.
 *
 * @package       Cake.Model.Datasource
 */
class CakeSession {

/**
 * True if the Session is still valid
 *
 * @var boolean
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
 * @var integer
 */
	public static $lastError = null;

/**
 * 'Security.level' setting, "high", "medium", or "low".
 *
 * @var string
 */
	public static $security = null;

/**
 * Start time for this session.
 *
 * @var integer
 */
	public static $time = false;

/**
 * Cookie lifetime
 *
 * @var integer
 */
	public static $cookieLifeTime;

/**
 * Time when this session becomes invalid.
 *
 * @var integer
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
 * @var integer
 */
	public static $timeout = null;

/**
 * Number of requests that can occur during a session time without the session being renewed.
 * This feature is only used when config value `Session.autoRegenerate` is set to true.
 *
 * @var integer
 * @see CakeSession::_checkValid()
 */
	public static $requestCountdown = 10;

/**
 * Constructor.
 *
 * @param string $base The base path for the Session
 * @param boolean $start Should session be started right now
 * @return void
 */
	public static function init($base = null, $start = true) {
		self::$time = time();

		$checkAgent = Configure::read('Session.checkAgent');
		if (($checkAgent === true || $checkAgent === null) && env('HTTP_USER_AGENT') != null) {
			self::$_userAgent = md5(env('HTTP_USER_AGENT') . Configure::read('Security.salt'));
		}
		self::_setPath($base);
		self::_setHost(env('HTTP_HOST'));
	}

/**
 * Setup the Path variable
 *
 * @param string $base base path
 * @return void
 */
	protected static function _setPath($base = null) {
		if (empty($base)) {
			self::$path = '/';
			return;
		}
		if (strpos($base, 'index.php') !== false) {
			 $base = str_replace('index.php', '', $base);
		}
		if (strpos($base, '?') !== false) {
			 $base = str_replace('?', '', $base);
		}
		self::$path = $base;
	}

/**
 * Set the host name
 *
 * @param string $host Hostname
 * @return void
 */
	protected static function _setHost($host) {
		self::$host = $host;
		if (strpos(self::$host, ':') !== false) {
			self::$host = substr(self::$host, 0, strpos(self::$host, ':'));
		}
	}

/**
 * Starts the Session.
 *
 * @return boolean True if session was started
 */
	public static function start() {
		if (self::started()) {
			return true;
		}
		$id = self::id();
		session_write_close();
		self::_configureSession();
		self::_startSession();

		if (!$id && self::started()) {
			self::_checkValid();
		}

		self::$error = false;
		return self::started();
	}

/**
 * Determine if Session has been started.
 *
 * @return boolean True if session has been started.
 */
	public static function started() {
		return isset($_SESSION) && session_id();
	}

/**
 * Returns true if given variable is set in session.
 *
 * @param string $name Variable name to check for
 * @return boolean True if variable is there
 */
	public static function check($name = null) {
		if (!self::started() && !self::start()) {
			return false;
		}
		if (empty($name)) {
			return false;
		}
		$result = Set::classicExtract($_SESSION, $name);
		return isset($result);
	}

/**
 * Returns the Session id
 *
 * @param string $id
 * @return string Session id
 */
	public static function id($id = null) {
		if ($id) {
			self::$id = $id;
			session_id(self::$id);
		}
		if (self::started()) {
			return session_id();
		}
		return self::$id;
	}

/**
 * Removes a variable from session.
 *
 * @param string $name Session variable to remove
 * @return boolean Success
 */
	public static function delete($name) {
		if (self::check($name)) {
			self::_overwrite($_SESSION, Set::remove($_SESSION, $name));
			return (self::check($name) == false);
		}
		self::_setError(2, __d('cake_dev', "%s doesn't exist", $name));
		return false;
	}

/**
 * Used to write new data to _SESSION, since PHP doesn't like us setting the _SESSION var itself
 *
 * @param array $old Set of old variables => values
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
 * @param integer $errorNumber Error to set
 * @return string Error as string
 */
	protected static function _error($errorNumber) {
		if (!is_array(self::$error) || !array_key_exists($errorNumber, self::$error)) {
			return false;
		} else {
			return self::$error[$errorNumber];
		}
	}

/**
 * Returns last occurred error as a string, if any.
 *
 * @return mixed Error description as a string, or false.
 */
	public static function error() {
		if (self::$lastError) {
			return self::_error(self::$lastError);
		}
		return false;
	}

/**
 * Returns true if session is valid.
 *
 * @return boolean Success
 */
	public static function valid() {
		if (self::read('Config')) {
			if (self::_validAgentAndTime() && self::$error === false) {
				self::$valid = true;
			} else {
				self::$valid = false;
				self::_setError(1, 'Session Highjacking Attempted !!!');
			}
		}
		return self::$valid;
	}

/**
 * Tests that the user agent is valid and that the session hasn't 'timed out'.
 * Since timeouts are implemented in CakeSession it checks the current self::$time
 * against the time the session is set to expire.  The User agent is only checked
 * if Session.checkAgent == true.
 *
 * @return boolean
 */
	protected static function _validAgentAndTime() {
		$config = self::read('Config');
		$validAgent = (
			Configure::read('Session.checkAgent') === false ||
			self::$_userAgent == $config['userAgent']
		);
		return ($validAgent && self::$time <= $config['time']);
	}

/**
 * Get / Set the userAgent
 *
 * @param string $userAgent Set the userAgent
 * @return void
 */
	public static function userAgent($userAgent = null) {
		if ($userAgent) {
			self::$_userAgent = $userAgent;
		}
		return self::$_userAgent;
	}

/**
 * Returns given session variable, or all of them, if no parameters given.
 *
 * @param mixed $name The name of the session variable (or a path as sent to Set.extract)
 * @return mixed The value of the session variable
 */
	public static function read($name = null) {
		if (!self::started() && !self::start()) {
			return false;
		}
		if (is_null($name)) {
			return self::_returnSessionVars();
		}
		if (empty($name)) {
			return false;
		}
		$result = Set::classicExtract($_SESSION, $name);

		if (!is_null($result)) {
			return $result;
		}
		self::_setError(2, "$name doesn't exist");
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
		self::_setError(2, 'No Session vars set');
		return false;
	}

/**
 * Writes value to given session variable name.
 *
 * @param mixed $name Name of variable
 * @param string $value Value to write
 * @return boolean True if the write was successful, false if the write failed
 */
	public static function write($name, $value = null) {
		if (!self::started() && !self::start()) {
			return false;
		}
		if (empty($name)) {
			return false;
		}
		$write = $name;
		if (!is_array($name)) {
			$write = array($name => $value);
		}
		foreach ($write as $key => $val) {
			self::_overwrite($_SESSION, Set::insert($_SESSION, $key, $val));
			if (Set::classicExtract($_SESSION, $key) !== $val) {
				return false;
			}
		}
		return true;
	}

/**
 * Helper method to destroy invalid sessions.
 *
 * @return void
 */
	public static function destroy() {
		if (self::started()) {
			session_destroy();
		}
		self::clear();
	}

/**
 * Clears the session, the session id, and renew's the session.
 *
 * @return void
 */
	public static function clear() {
		$_SESSION = null;
		self::$id = null;
		self::start();
		self::renew();
	}

/**
 * Helper method to initialize a session, based on Cake core settings.
 *
 * Sessions can be configured with a few shortcut names as well as have any number of ini settings declared.
 *
 * @return void
 * @throws CakeSessionException Throws exceptions when ini_set() fails.
 */
	protected static function _configureSession() {
		$sessionConfig = Configure::read('Session');
		$iniSet = function_exists('ini_set');

		if (isset($sessionConfig['defaults'])) {
			$defaults = self::_defaultConfig($sessionConfig['defaults']);
			if ($defaults) {
				$sessionConfig = Set::merge($defaults, $sessionConfig);
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

		if (empty($_SESSION)) {
			if (!empty($sessionConfig['ini']) && is_array($sessionConfig['ini'])) {
				foreach ($sessionConfig['ini'] as $setting => $value) {
					if (ini_set($setting, $value) === false) {
						throw new CakeSessionException(sprintf(
							__d('cake_dev', 'Unable to configure the session, setting %s failed.'),
							$setting
						));
					}
				}
			}
		}
		if (!empty($sessionConfig['handler']) && !isset($sessionConfig['handler']['engine'])) {
			call_user_func_array('session_set_save_handler', $sessionConfig['handler']);
		}
		if (!empty($sessionConfig['handler']['engine'])) {
			$handler = self::_getHandler($sessionConfig['handler']['engine']);
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
		self::$sessionTime = self::$time + ($sessionConfig['timeout'] * 60);
	}

/**
 * Find the handler class and make sure it implements the correct interface.
 *
 * @param string $handler
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
 * @param string $name
 * @return boolean|array
 */
	protected static function _defaultConfig($name) {
		$defaults = array(
			'php' => array(
				'cookie' => 'CAKEPHP',
				'timeout' => 240,
				'cookieTimeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
					'session.cookie_path' => self::$path
				)
			),
			'cake' => array(
				'cookie' => 'CAKEPHP',
				'timeout' => 240,
				'cookieTimeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
					'url_rewriter.tags' => '',
					'session.serialize_handler' => 'php',
					'session.use_cookies' => 1,
					'session.cookie_path' => self::$path,
					'session.auto_start' => 0,
					'session.save_path' => TMP . 'sessions',
					'session.save_handler' => 'files'
				)
			),
			'cache' => array(
				'cookie' => 'CAKEPHP',
				'timeout' => 240,
				'cookieTimeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
					'url_rewriter.tags' => '',
					'session.auto_start' => 0,
					'session.use_cookies' => 1,
					'session.cookie_path' => self::$path,
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
				'cookieTimeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
					'url_rewriter.tags' => '',
					'session.auto_start' => 0,
					'session.use_cookies' => 1,
					'session.cookie_path' => self::$path,
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
 * @return boolean Success
 */
	protected static function _startSession() {
		if (headers_sent()) {
			if (empty($_SESSION)) {
				$_SESSION = array();
			}
		} elseif (!isset($_SESSION)) {
			session_cache_limiter ("must-revalidate");
			session_start();
			header ('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
		} else {
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
		if (!self::started() && !self::start()) {
			self::$valid = false;
			return false;
		}
		if ($config = self::read('Config')) {
			$sessionConfig = Configure::read('Session');

			if (self::_validAgentAndTime()) {
				self::write('Config.time', self::$sessionTime);
				if (isset($sessionConfig['autoRegenerate']) && $sessionConfig['autoRegenerate'] === true) {
					$check = $config['countdown'];
					$check -= 1;
					self::write('Config.countdown', $check);

					if ($check < 1) {
						self::renew();
						self::write('Config.countdown', self::$requestCountdown);
					}
				}
				self::$valid = true;
			} else {
				self::destroy();
				self::$valid = false;
				self::_setError(1, 'Session Highjacking Attempted !!!');
			}
		} else {
			self::write('Config.userAgent', self::$_userAgent);
			self::write('Config.time', self::$sessionTime);
			self::write('Config.countdown', self::$requestCountdown);
			self::$valid = true;
		}
	}

/**
 * Restarts this session.
 *
 * @return void
 */
	public static function renew() {
		if (session_id()) {
			if (session_id() != '' || isset($_COOKIE[session_name()])) {
				setcookie(Configure::read('Session.cookie'), '', time() - 42000, self::$path);
			}
			session_regenerate_id(true);
		}
	}

/**
 * Helper method to set an internal error message.
 *
 * @param integer $errorNumber Number of the error
 * @param string $errorMessage Description of the error
 * @return void
 */
	protected static function _setError($errorNumber, $errorMessage) {
		if (self::$error === false) {
			self::$error = array();
		}
		self::$error[$errorNumber] = $errorMessage;
		self::$lastError = $errorNumber;
	}
}


/**
 * Interface for Session handlers.  Custom session handler classes should implement
 * this interface as it allows CakeSession know how to map methods to session_set_save_handler()
 *
 * @package       Cake.Model.Datasource
 */
interface CakeSessionHandlerInterface {
/**
 * Method called on open of a session.
 *
 * @return boolean Success
 */
	public function open();

/**
 * Method called on close of a session.
 *
 * @return boolean Success
 */
	public function close();

/**
 * Method used to read from a session.
 *
 * @param mixed $id The key of the value to read
 * @return mixed The value of the key or false if it does not exist
 */
	public function read($id);

/**
 * Helper function called on write for sessions.
 *
 * @param integer $id ID that uniquely identifies session in database
 * @param mixed $data The value of the data to be saved.
 * @return boolean True for successful write, false otherwise.
 */
	public function write($id, $data);

/**
 * Method called on the destruction of a session.
 *
 * @param integer $id ID that uniquely identifies session in database
 * @return boolean True for successful delete, false otherwise.
 */
	public function destroy($id);

/**
 * Run the Garbage collection on the session storage.  This method should vacuum all
 * expired or dead sessions.
 *
 * @param integer $expires Timestamp (defaults to current time)
 * @return boolean Success
 */
	public function gc($expires = null);
}


// Initialize the session
CakeSession::init();
