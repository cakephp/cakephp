<?php
/**
 * Session class for Cake.
 *
 * Cake abstracts the handling of sessions.
 * There are several convenient methods to access session information.
 * This class is the implementation of those methods.
 * They are mostly used by the Session Component.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v .0.10.0.1222
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
if (!class_exists('Security')) {
	App::import('Core', 'Security');
}

/**
 * Session class for Cake.
 *
 * Cake abstracts the handling of sessions. There are several convenient methods to access session information.
 * This class is the implementation of those methods. They are mostly used by the Session Component.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class CakeSession extends Object {

/**
 * True if the Session is still valid
 *
 * @var boolean
 * @access public
 */
	var $valid = false;

/**
 * Error messages for this session
 *
 * @var array
 * @access public
 */
	var $error = false;

/**
 * User agent string
 *
 * @var string
 * @access protected
 */
	var $_userAgent = '';

/**
 * Path to where the session is active.
 *
 * @var string
 * @access public
 */
	var $path = '/';

/**
 * Error number of last occurred error
 *
 * @var integer
 * @access public
 */
	var $lastError = null;

/**
 * 'Security.level' setting, "high", "medium", or "low".
 *
 * @var string
 * @access public
 */
	var $security = null;

/**
 * Start time for this session.
 *
 * @var integer
 * @access public
 */
	var $time = false;

/**
 * Time when this session becomes invalid.
 *
 * @var integer
 * @access public
 */
	var $sessionTime = false;

/**
 * The number of seconds to set for session.cookie_lifetime.  0 means
 * at browser close.
 *
 * @var integer
 */
	var $cookieLifeTime = false;

/**
 * Keeps track of keys to watch for writes on
 *
 * @var array
 * @access public
 */
	var $watchKeys = array();

/**
 * Current Session id
 *
 * @var string
 * @access public
 */
	var $id = null;

/**
 * Hostname
 *
 * @var string
 * @access public
 */
	var $host = null;

/**
 * Session timeout multiplier factor
 *
 * @var integer
 * @access public
 */
	var $timeout = null;

/**
 * Constructor.
 *
 * @param string $base The base path for the Session
 * @param boolean $start Should session be started right now
 * @access public
 */
	function __construct($base = null, $start = true) {
		App::import('Core', array('Set', 'Security'));
		$this->time = time();

		if (Configure::read('Session.checkAgent') === true || Configure::read('Session.checkAgent') === null) {
			if (env('HTTP_USER_AGENT') != null) {
				$this->_userAgent = md5(env('HTTP_USER_AGENT') . Configure::read('Security.salt'));
			}
		}
		if (Configure::read('Session.save') === 'database') {
			$modelName = Configure::read('Session.model');
			$database = Configure::read('Session.database');
			$table = Configure::read('Session.table');

			if (empty($database)) {
				$database = 'default';
			}
			$settings = array(
				'class' => 'Session',
				'alias' => 'Session',
				'table' => 'cake_sessions',
				'ds' => $database
			);
			if (!empty($modelName)) {
				$settings['class'] = $modelName;
			}
			if (!empty($table)) {
				$settings['table'] = $table;
			}
			ClassRegistry::init($settings);
		}
		if ($start === true) {
			if (!empty($base)) {
				$this->path = $base;
				if (strpos($base, 'index.php') !== false) {
				   $this->path = str_replace('index.php', '', $base);
				}
				if (strpos($base, '?') !== false) {
				   $this->path = str_replace('?', '', $base);
				}
			}
			$this->host = env('HTTP_HOST');

			if (strpos($this->host, ':') !== false) {
				$this->host = substr($this->host, 0, strpos($this->host, ':'));
			}
		}
		if (isset($_SESSION) || $start === true) {
			$this->sessionTime = $this->time + (Security::inactiveMins() * Configure::read('Session.timeout'));
			$this->security = Configure::read('Security.level');
		}
		parent::__construct();
	}

/**
 * Starts the Session.
 *
 * @return boolean True if session was started
 * @access public
 */
	function start() {
		if ($this->started()) {
			return true;
		}
		if (function_exists('session_write_close')) {
			session_write_close();
		}
		$this->__initSession();
		$this->__startSession();
		return $this->started();
	}

/**
 * Determine if Session has been started.
 *
 * @access public
 * @return boolean True if session has been started.
 */
	function started() {
		if (isset($_SESSION) && session_id()) {
			return true;
		}
		return false;
	}

/**
 * Returns true if given variable is set in session.
 *
 * @param string $name Variable name to check for
 * @return boolean True if variable is there
 * @access public
 */
	function check($name) {
		if (empty($name)) {
			return false;
		}
		$result = Set::classicExtract($_SESSION, $name);
		return isset($result);
	}

/**
 * Returns the Session id
 *
 * @param id $name string
 * @return string Session id
 * @access public
 */
	function id($id = null) {
		if ($id) {
			$this->id = $id;
			session_id($this->id);
		}
		if ($this->started()) {
			return session_id();
		} else {
			return $this->id;
		}
	}

/**
 * Removes a variable from session.
 *
 * @param string $name Session variable to remove
 * @return boolean Success
 * @access public
 */
	function delete($name) {
		if ($this->check($name)) {
			if (in_array($name, $this->watchKeys)) {
				trigger_error(sprintf(__('Deleting session key {%s}', true), $name), E_USER_NOTICE);
			}
			$this->__overwrite($_SESSION, Set::remove($_SESSION, $name));
			return ($this->check($name) == false);
		}
		$this->__setError(2, sprintf(__("%s doesn't exist", true), $name));
		return false;
	}

/**
 * Used to write new data to _SESSION, since PHP doesn't like us setting the _SESSION var itself
 *
 * @param array $old Set of old variables => values
 * @param array $new New set of variable => value
 * @access private
 */
	function __overwrite(&$old, $new) {
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
 * @access private
 */
	function __error($errorNumber) {
		if (!is_array($this->error) || !array_key_exists($errorNumber, $this->error)) {
			return false;
		} else {
			return $this->error[$errorNumber];
		}
	}

/**
 * Returns last occurred error as a string, if any.
 *
 * @return mixed Error description as a string, or false.
 * @access public
 */
	function error() {
		if ($this->lastError) {
			return $this->__error($this->lastError);
		} else {
			return false;
		}
	}

/**
 * Returns true if session is valid.
 *
 * @return boolean Success
 * @access public
 */
	function valid() {
		if ($this->read('Config')) {
			if ((Configure::read('Session.checkAgent') === false || $this->_userAgent == $this->read('Config.userAgent')) && $this->time <= $this->read('Config.time')) {
				if ($this->error === false) {
					$this->valid = true;
				}
			} else {
				$this->valid = false;
				$this->__setError(1, 'Session Highjacking Attempted !!!');
			}
		}
		return $this->valid;
	}

/**
 * Returns given session variable, or all of them, if no parameters given.
 *
 * @param mixed $name The name of the session variable (or a path as sent to Set.extract)
 * @return mixed The value of the session variable
 * @access public
 */
	function read($name = null) {
		if (is_null($name)) {
			return $this->__returnSessionVars();
		}
		if (empty($name)) {
			return false;
		}
		$result = Set::classicExtract($_SESSION, $name);

		if (!is_null($result)) {
			return $result;
		}
		$this->__setError(2, "$name doesn't exist");
		return null;
	}

/**
 * Returns all session variables.
 *
 * @return mixed Full $_SESSION array, or false on error.
 * @access private
 */
	function __returnSessionVars() {
		if (!empty($_SESSION)) {
			return $_SESSION;
		}
		$this->__setError(2, "No Session vars set");
		return false;
	}

/**
 * Tells Session to write a notification when a certain session path or subpath is written to
 *
 * @param mixed $var The variable path to watch
 * @return void
 * @access public
 */
	function watch($var) {
		if (empty($var)) {
			return false;
		}
		if (!in_array($var, $this->watchKeys, true)) {
			$this->watchKeys[] = $var;
		}
	}

/**
 * Tells Session to stop watching a given key path
 *
 * @param mixed $var The variable path to watch
 * @return void
 * @access public
 */
	function ignore($var) {
		if (!in_array($var, $this->watchKeys)) {
			return;
		}
		foreach ($this->watchKeys as $i => $key) {
			if ($key == $var) {
				unset($this->watchKeys[$i]);
				$this->watchKeys = array_values($this->watchKeys);
				return;
			}
		}
	}

/**
 * Writes value to given session variable name.
 *
 * @param mixed $name Name of variable
 * @param string $value Value to write
 * @return boolean True if the write was successful, false if the write failed
 * @access public
 */
	function write($name, $value) {
		if (empty($name)) {
			return false;
		}
		if (in_array($name, $this->watchKeys)) {
			trigger_error(sprintf(__('Writing session key {%s}: %s', true), $name, Debugger::exportVar($value)), E_USER_NOTICE);
		}
		$this->__overwrite($_SESSION, Set::insert($_SESSION, $name, $value));
		return (Set::classicExtract($_SESSION, $name) === $value);
	}

/**
 * Helper method to destroy invalid sessions.
 *
 * @return void
 * @access public
 */
	function destroy() {
		if ($this->started()) {
			session_destroy();
		}
		$_SESSION = null;
		$this->__construct($this->path);
		$this->start();
		$this->renew();
		$this->_checkValid();
	}

/**
 * Helper method to initialize a session, based on Cake core settings.
 *
 * @access private
 */
	function __initSession() {
		$iniSet = function_exists('ini_set');
		if ($iniSet && env('HTTPS')) {
			ini_set('session.cookie_secure', 1);
		}
		if ($iniSet && ($this->security === 'high' || $this->security === 'medium')) {
			ini_set('session.referer_check', $this->host);
		}

		if ($this->security == 'high') {
			$this->cookieLifeTime = 0;
		} else {
			$this->cookieLifeTime = Configure::read('Session.timeout') * (Security::inactiveMins() * 60);
		}

		switch (Configure::read('Session.save')) {
			case 'cake':
				if (empty($_SESSION)) {
					if ($iniSet) {
						ini_set('session.use_trans_sid', 0);
						ini_set('url_rewriter.tags', '');
						ini_set('session.serialize_handler', 'php');
						ini_set('session.use_cookies', 1);
						ini_set('session.name', Configure::read('Session.cookie'));
						ini_set('session.cookie_lifetime', $this->cookieLifeTime);
						ini_set('session.cookie_path', $this->path);
						ini_set('session.auto_start', 0);
						ini_set('session.save_path', TMP . 'sessions');
					}
				}
			break;
			case 'database':
				if (empty($_SESSION)) {
					if (Configure::read('Session.model') === null) {
						trigger_error(__("You must set the all Configure::write('Session.*') in core.php to use database storage"), E_USER_WARNING);
						$this->_stop();
					}
					if ($iniSet) {
						ini_set('session.use_trans_sid', 0);
						ini_set('url_rewriter.tags', '');
						ini_set('session.save_handler', 'user');
						ini_set('session.serialize_handler', 'php');
						ini_set('session.use_cookies', 1);
						ini_set('session.name', Configure::read('Session.cookie'));
						ini_set('session.cookie_lifetime', $this->cookieLifeTime);
						ini_set('session.cookie_path', $this->path);
						ini_set('session.auto_start', 0);
					}
				}
				session_set_save_handler(
					array('CakeSession','__open'),
					array('CakeSession', '__close'),
					array('CakeSession', '__read'),
					array('CakeSession', '__write'),
					array('CakeSession', '__destroy'),
					array('CakeSession', '__gc')
				);
			break;
			case 'php':
				if (empty($_SESSION)) {
					if ($iniSet) {
						ini_set('session.use_trans_sid', 0);
						ini_set('session.name', Configure::read('Session.cookie'));
						ini_set('session.cookie_lifetime', $this->cookieLifeTime);
						ini_set('session.cookie_path', $this->path);
					}
				}
			break;
			case 'cache':
				if (empty($_SESSION)) {
					if (!class_exists('Cache')) {
						require LIBS . 'cache.php';
					}
					if ($iniSet) {
						ini_set('session.use_trans_sid', 0);
						ini_set('url_rewriter.tags', '');
						ini_set('session.save_handler', 'user');
						ini_set('session.use_cookies', 1);
						ini_set('session.name', Configure::read('Session.cookie'));
						ini_set('session.cookie_lifetime', $this->cookieLifeTime);
						ini_set('session.cookie_path', $this->path);
					}
				}
				session_set_save_handler(
					array('CakeSession','__open'),
					array('CakeSession', '__close'),
					array('Cache', 'read'),
					array('Cache', 'write'),
					array('Cache', 'delete'),
					array('Cache', 'gc')
				);
			break;
			default:
				$config = CONFIGS . Configure::read('Session.save') . '.php';

				if (is_file($config)) {
					require($config);
				}
			break;
		}
	}

/**
 * Helper method to start a session
 *
 * @access private
 */
	function __startSession() {
		if (headers_sent()) {
			if (empty($_SESSION)) {
				$_SESSION = array();
			}
			return true;
		} elseif (!isset($_SESSION)) {
			session_cache_limiter ("must-revalidate");
			session_start();
			header ('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
			return true;
		} else {
			session_start();
			return true;
		}
	}

/**
 * Helper method to create a new session.
 *
 * @return void
 * @access protected
 */
	function _checkValid() {
		if ($this->read('Config')) {
			if ((Configure::read('Session.checkAgent') === false || $this->_userAgent == $this->read('Config.userAgent')) && $this->time <= $this->read('Config.time')) {
				$time = $this->read('Config.time');
				$this->write('Config.time', $this->sessionTime);
				if (Configure::read('Security.level') === 'high') {
					$check = $this->read('Config.timeout');
					$check -= 1;
					$this->write('Config.timeout', $check);

					if (time() > ($time - (Security::inactiveMins() * Configure::read('Session.timeout')) + 2) || $check < 1) {
						$this->renew();
						$this->write('Config.timeout', 10);
					}
				}
				$this->valid = true;
			} else {
				$this->destroy();
				$this->valid = false;
				$this->__setError(1, 'Session Highjacking Attempted !!!');
			}
		} else {
			$this->write('Config.userAgent', $this->_userAgent);
			$this->write('Config.time', $this->sessionTime);
			$this->write('Config.timeout', 10);
			$this->valid = true;
			$this->__setError(1, 'Session is valid');
		}
	}

/**
 * Helper method to restart a session.
 *
 * @return void
 * @access private
 */
	function __regenerateId() {
		$oldSessionId = session_id();
		if ($oldSessionId) {
			if (session_id() != ''|| isset($_COOKIE[session_name()])) {
				setcookie(Configure::read('Session.cookie'), '', time() - 42000, $this->path);
			}
			session_regenerate_id(true);
			if (PHP_VERSION < 5.1) {
				$sessionPath = session_save_path();
				if (empty($sessionPath)) {
					$sessionPath = '/tmp';
				}
				$newSessid = session_id();

				if (function_exists('session_write_close')) {
					session_write_close();
				}
				$this->__initSession();
				session_id($oldSessionId);
				session_start();
				session_destroy();
				$file = $sessionPath . DS . 'sess_' . $oldSessionId;
				@unlink($file);
				$this->__initSession();
				session_id($newSessid);
				session_start();
			}
		}
	}

/**
 * Restarts this session.
 *
 * @access public
 */
	function renew() {
		$this->__regenerateId();
	}

/**
 * Helper method to set an internal error message.
 *
 * @param integer $errorNumber Number of the error
 * @param string $errorMessage Description of the error
 * @return void
 * @access private
 */
	function __setError($errorNumber, $errorMessage) {
		if ($this->error === false) {
			$this->error = array();
		}
		$this->error[$errorNumber] = $errorMessage;
		$this->lastError = $errorNumber;
	}

/**
 * Method called on open of a database session.
 *
 * @return boolean Success
 * @access private
 */
	function __open() {
		return true;
	}

/**
 * Method called on close of a database session.
 *
 * @return boolean Success
 * @access private
 */
	function __close() {
		$probability = mt_rand(1, 150);
		if ($probability <= 3) {
			switch (Configure::read('Session.save')) {
				case 'cache':
					Cache::gc();
				break;
				default:
					CakeSession::__gc();
				break;
			}
		}
		return true;
	}

/**
 * Method used to read from a database session.
 *
 * @param mixed $id The key of the value to read
 * @return mixed The value of the key or false if it does not exist
 * @access private
 */
	function __read($id) {
		$model =& ClassRegistry::getObject('Session');

		$row = $model->find('first', array(
			'conditions' => array($model->primaryKey => $id)
		));

		if (empty($row[$model->alias]['data'])) {
			return false;
		}

		return $row[$model->alias]['data'];
	}

/**
 * Helper function called on write for database sessions.
 *
 * @param integer $id ID that uniquely identifies session in database
 * @param mixed $data The value of the data to be saved.
 * @return boolean True for successful write, false otherwise.
 * @access private
 */
	function __write($id, $data) {
		if (!$id) {
			return false;
		}
		$expires = time() + Configure::read('Session.timeout') * Security::inactiveMins();
		$model =& ClassRegistry::getObject('Session');
		$return = $model->save(array($model->primaryKey => $id) + compact('data', 'expires'));
		return $return;
	}

/**
 * Method called on the destruction of a database session.
 *
 * @param integer $id ID that uniquely identifies session in database
 * @return boolean True for successful delete, false otherwise.
 * @access private
 */
	function __destroy($id) {
		$model =& ClassRegistry::getObject('Session');
		$return = $model->delete($id);

		return $return;
	}

/**
 * Helper function called on gc for database sessions.
 *
 * @param integer $expires Timestamp (defaults to current time)
 * @return boolean Success
 * @access private
 */
	function __gc($expires = null) {
		$model =& ClassRegistry::getObject('Session');

		if (!$expires) {
			$expires = time();
		}

		$return = $model->deleteAll(array($model->alias . ".expires <" => $expires), false, false);
		return $return;
	}
}
