<?php
/**
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
use Cake\Utility\Hash;
use InvalidArgumentException;
use RuntimeException;
use SessionHandlerInterface;

/**
 * This class is a wrapper for the native PHP session functions. It provides
 * several defaults for the most common session configuration
 * via external handlers and helps with using session in cli without any warnings.
 *
 * Sessions can be created from the defaults using `Session::create()` or you can get
 * an instance of a new session by just instantiating this class and passing the complete
 * options you want to use.
 *
 * When specific options are omitted, this class will take its defaults from the configuration
 * values from the `session.*` directives in php.ini. This class will also alter such
 * directives when configuration values are provided.
 */
class Session
{

    /**
     * The Session handler instance used as an engine for persisting the session data.
     *
     * @var \SessionHandlerInterface
     */
    protected $_engine;

    /**
     * Indicates whether the sessions has already started
     *
     * @var bool
     */
    protected $_started;

    /**
     * The time in seconds the session will be valid for
     *
     * @var int
     */
    protected $_lifetime;

    /**
     * Whether this session is running under a CLI environment
     *
     * @var bool
     */
    protected $_isCLI = false;

    /**
     * Returns a new instance of a session after building a configuration bundle for it.
     * This function allows an options array which will be used for configuring the session
     * and the handler to be used. The most important key in the configuration array is
     * `defaults`, which indicates the set of configurations to inherit from, the possible
     * defaults are:
     *
     * - php: just use session as configured in php.ini
     * - cache: Use the CakePHP caching system as an storage for the session, you will need
     *   to pass the `config` key with the name of an already configured Cache engine.
     * - database: Use the CakePHP ORM to persist and manage sessions. By default this requires
     *   a table in your database named `sessions` or a `model` key in the configuration
     *   to indicate which Table object to use.
     * - cake: Use files for storing the sessions, but let CakePHP manage them and decide
     *   where to store them.
     *
     * The full list of options follows:
     *
     * - defaults: either 'php', 'database', 'cache' or 'cake' as explained above.
     * - handler: An array containing the handler configuration
     * - ini: A list of php.ini directives to set before the session starts.
     * - timeout: The time in minutes the session should stay active
     *
     * @param array $sessionConfig Session config.
     * @return static
     * @see \Cake\Network\Session::__construct()
     */
    public static function create($sessionConfig = [])
    {
        if (isset($sessionConfig['defaults'])) {
            $defaults = static::_defaultConfig($sessionConfig['defaults']);
            if ($defaults) {
                $sessionConfig = Hash::merge($defaults, $sessionConfig);
            }
        }

        if (!isset($sessionConfig['ini']['session.cookie_secure']) && env('HTTPS') && ini_get("session.cookie_secure") != 1) {
            $sessionConfig['ini']['session.cookie_secure'] = 1;
        }

        if (!isset($sessionConfig['ini']['session.name'])) {
            $sessionConfig['ini']['session.name'] = $sessionConfig['cookie'];
        }

        if (!empty($sessionConfig['handler'])) {
            $sessionConfig['ini']['session.save_handler'] = 'user';
        }

        if (!isset($sessionConfig['ini']['session.cookie_httponly']) && ini_get("session.cookie_httponly") != 1) {
            $sessionConfig['ini']['session.cookie_httponly'] = 1;
        }

        return new static($sessionConfig);
    }

    /**
     * Get one of the prebaked default session configurations.
     *
     * @param string $name Config name.
     * @return bool|array
     */
    protected static function _defaultConfig($name)
    {
        $defaults = [
            'php' => [
                'cookie' => 'CAKEPHP',
                'ini' => [
                    'session.use_trans_sid' => 0,
                ]
            ],
            'cake' => [
                'cookie' => 'CAKEPHP',
                'ini' => [
                    'session.use_trans_sid' => 0,
                    'session.serialize_handler' => 'php',
                    'session.use_cookies' => 1,
                    'session.save_path' => TMP . 'sessions',
                    'session.save_handler' => 'files'
                ]
            ],
            'cache' => [
                'cookie' => 'CAKEPHP',
                'ini' => [
                    'session.use_trans_sid' => 0,
                    'session.use_cookies' => 1,
                    'session.save_handler' => 'user',
                ],
                'handler' => [
                    'engine' => 'CacheSession',
                    'config' => 'default'
                ]
            ],
            'database' => [
                'cookie' => 'CAKEPHP',
                'ini' => [
                    'session.use_trans_sid' => 0,
                    'session.use_cookies' => 1,
                    'session.save_handler' => 'user',
                    'session.serialize_handler' => 'php',
                ],
                'handler' => [
                    'engine' => 'DatabaseSession'
                ]
            ]
        ];

        if (isset($defaults[$name])) {
            return $defaults[$name];
        }

        return false;
    }

    /**
     * Constructor.
     *
     * ### Configuration:
     *
     * - timeout: The time in minutes the session should be valid for.
     * - cookiePath: The url path for which session cookie is set. Maps to the
     *   `session.cookie_path` php.ini config. Defaults to base path of app.
     * - ini: A list of php.ini directives to change before the session start.
     * - handler: An array containing at least the `class` key. To be used as the session
     *   engine for persisting data. The rest of the keys in the array will be passed as
     *   the configuration array for the engine. You can set the `class` key to an already
     *   instantiated session handler object.
     *
     * @param array $config The Configuration to apply to this session object
     */
    public function __construct(array $config = [])
    {
        if (isset($config['timeout'])) {
            $config['ini']['session.gc_maxlifetime'] = 60 * $config['timeout'];
        }

        if (!empty($config['cookie'])) {
            $config['ini']['session.name'] = $config['cookie'];
        }

        if (!isset($config['ini']['session.cookie_path'])) {
            $cookiePath = empty($config['cookiePath']) ? '/' : $config['cookiePath'];
            $config['ini']['session.cookie_path'] = $cookiePath;
        }

        if (!empty($config['ini']) && is_array($config['ini'])) {
            $this->options($config['ini']);
        }

        if (!empty($config['handler']['engine'])) {
            $class = $config['handler']['engine'];
            unset($config['handler']['engine']);
            session_set_save_handler($this->engine($class, $config['handler']), false);
        }

        $this->_lifetime = ini_get('session.gc_maxlifetime');
        $this->_isCLI = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
        session_register_shutdown();
    }

    /**
     * Sets the session handler instance to use for this session.
     * If a string is passed for the first argument, it will be treated as the
     * class name and the second argument will be passed as the first argument
     * in the constructor.
     *
     * If an instance of a SessionHandlerInterface is provided as the first argument,
     * the handler will be set to it.
     *
     * If no arguments are passed it will return the currently configured handler instance
     * or null if none exists.
     *
     * @param string|\SessionHandlerInterface|null $class The session handler to use
     * @param array $options the options to pass to the SessionHandler constructor
     * @return \SessionHandlerInterface|null
     * @throws \InvalidArgumentException
     */
    public function engine($class = null, array $options = [])
    {
        if ($class instanceof SessionHandlerInterface) {
            return $this->_engine = $class;
        }

        if ($class === null) {
            return $this->_engine;
        }

        $className = App::className($class, 'Network/Session');
        if (!$className) {
            throw new InvalidArgumentException(
                sprintf('The class "%s" does not exist and cannot be used as a session engine', $class)
            );
        }

        $handler = new $className($options);
        if (!($handler instanceof SessionHandlerInterface)) {
            throw new InvalidArgumentException(
                'The chosen SessionHandler does not implement SessionHandlerInterface, it cannot be used as an engine.'
            );
        }

        return $this->_engine = $handler;
    }

    /**
     * Calls ini_set for each of the keys in `$options` and set them
     * to the respective value in the passed array.
     *
     * ### Example:
     *
     * ```
     * $session->options(['session.use_cookies' => 1]);
     * ```
     *
     * @param array $options Ini options to set.
     * @return void
     * @throws \RuntimeException if any directive could not be set
     */
    public function options(array $options)
    {
        if (session_status() === \PHP_SESSION_ACTIVE) {
            return;
        }

        foreach ($options as $setting => $value) {
            if (ini_set($setting, $value) === false) {
                throw new RuntimeException(
                    sprintf('Unable to configure the session, setting %s failed.', $setting)
                );
            }
        }
    }

    /**
     * Starts the Session.
     *
     * @return bool True if session was started
     * @throws \RuntimeException if the session was already started
     */
    public function start()
    {
        if ($this->_started) {
            return true;
        }

        if ($this->_isCLI) {
            $_SESSION = [];
            $this->id('cli');

            return $this->_started = true;
        }

        if (session_status() === \PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Session was already started');
        }

        if (ini_get('session.use_cookies') && headers_sent($file, $line)) {
            return false;
        }

        if (!session_start()) {
            throw new RuntimeException('Could not start the session');
        }

        $this->_started = true;

        if ($this->_timedOut()) {
            $this->destroy();

            return $this->start();
        }

        return $this->_started;
    }

    /**
     * Determine if Session has already been started.
     *
     * @return bool True if session has been started.
     */
    public function started()
    {
        return $this->_started || session_status() === \PHP_SESSION_ACTIVE;
    }

    /**
     * Returns true if given variable name is set in session.
     *
     * @param string|null $name Variable name to check for
     * @return bool True if variable is there
     */
    public function check($name = null)
    {
        if ($this->_hasSession() && !$this->started()) {
            $this->start();
        }

        if (!isset($_SESSION)) {
            return false;
        }

        return Hash::get($_SESSION, $name) !== null;
    }

    /**
     * Returns given session variable, or all of them, if no parameters given.
     *
     * @param string|null $name The name of the session variable (or a path as sent to Hash.extract)
     * @return string|null The value of the session variable, null if session not available,
     *   session not started, or provided name not found in the session.
     */
    public function read($name = null)
    {
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
     * Reads and deletes a variable from session.
     *
     * @param string $name The key to read and remove (or a path as sent to Hash.extract).
     * @return mixed The value of the session variable, null if session not available,
     *   session not started, or provided name not found in the session.
     */
    public function consume($name)
    {
        if (empty($name)) {
            return null;
        }
        $value = $this->read($name);
        if ($value !== null) {
            $this->_overwrite($_SESSION, Hash::remove($_SESSION, $name));
        }

        return $value;
    }

    /**
     * Writes value to given session variable name.
     *
     * @param string|array $name Name of variable
     * @param mixed $value Value to write
     * @return void
     */
    public function write($name, $value = null)
    {
        if (!$this->started()) {
            $this->start();
        }

        $write = $name;
        if (!is_array($name)) {
            $write = [$name => $value];
        }

        $data = isset($_SESSION) ? $_SESSION : [];
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
     * @param string|null $id Id to replace the current session id
     * @return string Session id
     */
    public function id($id = null)
    {
        if ($id !== null) {
            session_id($id);
        }

        return session_id();
    }

    /**
     * Removes a variable from session.
     *
     * @param string $name Session variable to remove
     * @return void
     */
    public function delete($name)
    {
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
    protected function _overwrite(&$old, $new)
    {
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
    public function destroy()
    {
        if ($this->_hasSession() && !$this->started()) {
            $this->start();
        }

        if (!$this->_isCLI && session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION = [];
        $this->_started = false;
    }

    /**
     * Clears the session.
     *
     * Optionally it also clears the session id and renews the session.
     *
     * @param bool $renew If session should be renewed, as well. Defaults to false.
     * @return void
     */
    public function clear($renew = false)
    {
        $_SESSION = [];
        if ($renew) {
            $this->renew();
        }
    }

    /**
     * Returns whether a session exists
     *
     * @return bool
     */
    protected function _hasSession()
    {
        return !ini_get('session.use_cookies')
            || isset($_COOKIE[session_name()])
            || $this->_isCLI;
    }

    /**
     * Restarts this session.
     *
     * @return void
     */
    public function renew()
    {
        if (!$this->_hasSession() || $this->_isCLI) {
            return;
        }

        $this->start();
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );

        if (session_id()) {
            session_regenerate_id(true);
        }
    }

    /**
     * Returns true if the session is no longer valid because the last time it was
     * accessed was after the configured timeout.
     *
     * @return bool
     */
    protected function _timedOut()
    {
        $time = $this->read('Config.time');
        $result = false;

        $checkTime = $time !== null && $this->_lifetime > 0;
        if ($checkTime && (time() - $time > $this->_lifetime)) {
            $result = true;
        }

        $this->write('Config.time', time());

        return $result;
    }
}
