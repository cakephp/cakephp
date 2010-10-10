<?php
/**
 * ShellDispatcher file
 *
 * PHP 5
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
 * @subpackage    cake.cake.console
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
require_once 'console_option_parser.php';

/**
 * Shell dispatcher handles dispatching cli commands.
 *
 * @package       cake
 * @subpackage    cake.cake.console
 */
class ShellDispatcher {

/**
 * Contains command switches parsed from the command line.
 *
 * @var array
 * @access public
 */
	public $params = array();

/**
 * Contains arguments parsed from the command line.
 *
 * @var array
 * @access public
 */
	public $args = array();

/**
 * The file name of the shell that was invoked.
 *
 * @var string
 * @access public
 */
	public $shell = null;

/**
 * The class name of the shell that was invoked.
 *
 * @var string
 * @access public
 */
	public $shellClass = null;

/**
 * The command called if public methods are available.
 *
 * @var string
 * @access public
 */
	public $shellCommand = null;

/**
 * The path locations of shells.
 *
 * @var array
 * @access public
 */
	public $shellPaths = array();

/**
 * The path to the current shell location.
 *
 * @var string
 * @access public
 */
	public $shellPath = null;

/**
 * The name of the shell in camelized.
 *
 * @var string
 * @access public
 */
	public $shellName = null;

/**
 * TaskCollection object for the command
 *
 * @var TaskCollection
 */
	protected $_Tasks;

/**
 * Constructor
 *
 * The execution of the script is stopped after dispatching the request with
 * a status code of either 0 or 1 according to the result of the dispatch.
 *
 * @param array $args the argv
 * @return void
 */
	public function __construct($args = array()) {
		set_time_limit(0);

		$this->__initConstants();
		$this->parseParams($args);
		$this->_initEnvironment();
		$this->__buildPaths();
	}

/**
 * Run the dispatcher
 *
 * @return void
 */
	public static function run($argv) {
		$dispatcher = new ShellDispatcher($argv);
		$dispatcher->_stop($dispatcher->dispatch() === false ? 1 : 0);
	}

/**
 * Defines core configuration.
 *
 * @access private
 */
	function __initConstants() {
		if (function_exists('ini_set')) {
			ini_set('display_errors', '1');
			ini_set('error_reporting', E_ALL & ~E_DEPRECATED);
			ini_set('html_errors', false);
			ini_set('implicit_flush', true);
			ini_set('max_execution_time', 0);
		}

		if (!defined('CAKE_CORE_INCLUDE_PATH')) {
			define('DS', DIRECTORY_SEPARATOR);
			define('CAKE_CORE_INCLUDE_PATH', dirname(dirname(dirname(__FILE__))));
			define('DISABLE_DEFAULT_ERROR_HANDLING', false);
			define('CAKEPHP_SHELL', true);
			if (!defined('CORE_PATH')) {
				if (function_exists('ini_set') && ini_set('include_path', CAKE_CORE_INCLUDE_PATH . PATH_SEPARATOR . ini_get('include_path'))) {
					define('CORE_PATH', null);
				} else {
					define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
				}
			}
		}
	}

/**
 * Defines current working environment.
 *
 */
	protected function _initEnvironment() {
		if (!$this->__bootstrap()) {
			$message = "Unable to load CakePHP core.\nMake sure " . DS . 'cake' . DS . 'libs exists in ' . CAKE_CORE_INCLUDE_PATH;
			throw new RuntimeException($message);
		}

		if (!isset($this->args[0]) || !isset($this->params['working'])) {
			$message = "This file has been loaded incorrectly and cannot continue.\n" .
				"Please make sure that " . DIRECTORY_SEPARATOR . "cake" . DIRECTORY_SEPARATOR . "console is in your system path,\n" . 
				"and check the cookbook for the correct usage of this command.\n" .
				"(http://book.cakephp.org/)";
			throw new RuntimeException($message);
		}

		$this->shiftArgs();
	}

/**
 * Builds the shell paths.
 *
 * @access private
 * @return void
 */
	function __buildPaths() {
		$paths = array();

		$plugins = App::objects('plugin', null, false);
		foreach ((array)$plugins as $plugin) {
			$pluginPath = App::pluginPath($plugin);
			$path = $pluginPath . 'vendors' . DS . 'shells' . DS;
			if (file_exists($path)) {
				$paths[] = $path;
			}
		}

		$vendorPaths = array_values(App::path('vendors'));
		foreach ($vendorPaths as $vendorPath) {
			$path = rtrim($vendorPath, DS) . DS . 'shells' . DS;
			if (file_exists($path)) {
				$paths[] = $path;
			}
		}

		$this->shellPaths = array_values(array_unique(array_merge($paths, App::path('shells'))));
	}

/**
 * Initializes the environment and loads the Cake core.
 *
 * @return boolean Success.
 * @access private
 */
	function __bootstrap() {

		define('ROOT', $this->params['root']);
		define('APP_DIR', $this->params['app']);
		define('APP_PATH', $this->params['working'] . DS);
		define('WWW_ROOT', APP_PATH . $this->params['webroot'] . DS);
		if (!is_dir(ROOT . DS . APP_DIR . DS . 'tmp')) {
			define('TMP', CORE_PATH . 'cake' . DS . 'console' . DS . 'templates' . DS . 'skel' . DS . 'tmp' . DS);
		}

		$boot = file_exists(ROOT . DS . APP_DIR . DS . 'config' . DS . 'bootstrap.php');
		require CORE_PATH . 'cake' . DS . 'bootstrap.php';
		require_once CORE_PATH . 'cake' . DS . 'console' . DS . 'console_error_handler.php';
		set_exception_handler(array('ConsoleErrorHandler', 'handleException'));

		if (!file_exists(APP_PATH . 'config' . DS . 'core.php')) {
			include_once CORE_PATH . 'cake' . DS . 'console' . DS . 'templates' . DS . 'skel' . DS . 'config' . DS . 'core.php';
			App::build();
		}
		if (!defined('FULL_BASE_URL')) {
			define('FULL_BASE_URL', '/');
		}

		return true;
	}

/**
 * Dispatches a CLI request
 *
 * @return boolean
 */
	public function dispatch() {
		$command = $this->shiftArgs();

		if (!$command) {
			$this->help();
			return false;
		}
		if (in_array($command, array('help', '--help', '-h'))) {
			$this->help();
			return true;
		}

		list($plugin, $shell) = pluginSplit($command);
		$this->shell = $shell;
		$this->shellName = Inflector::camelize($shell);
		$this->shellClass = $this->shellName . 'Shell';

		$arg = null;

		if (isset($this->args[0])) {
			$arg = $this->args[0];
			$this->shellCommand = Inflector::variable($arg);
		}

		$Shell = $this->_getShell($plugin);

		$methods = array();

		if ($Shell instanceof Shell) {
			$Shell->initialize();
			$Shell->loadTasks();

			$task = Inflector::camelize($arg);

			if (in_array($task, $Shell->taskNames)) {
				$this->shiftArgs();
				$Shell->{$task}->startup();

				if (isset($this->args[0]) && $this->args[0] == 'help') {
					if (method_exists($Shell->{$task}, 'help')) {
						$Shell->{$task}->help();
					} else {
						$this->help();
					}
					return true;
				}
				return $Shell->{$task}->execute();
			}
			$methods = array_diff(get_class_methods('Shell'), array('help'));
		}
		$methods = array_diff(get_class_methods($Shell), $methods);
		$added = in_array(strtolower($arg), array_map('strtolower', $methods));
		$private = $arg[0] == '_' && method_exists($Shell, $arg);

		if (!$private) {
			if ($added) {
				$this->shiftArgs();
				$Shell->startup();
				return $Shell->{$arg}();
			}
			if (method_exists($Shell, 'main')) {
				$Shell->startup();
				return $Shell->main();
			}
		}
		throw new MissingShellMethodException(array('shell' => $this->shell, 'method' => $arg));
	}

/**
 * Get shell to use, either plugin shell or application shell
 *
 * All paths in the shellPaths property are searched.
 * shell, shellPath and shellClass properties are taken into account.
 *
 * @param string $plugin Optionally the name of a plugin
 * @return mixed False if no shell could be found or an object on success
 */
	protected function _getShell($plugin = null) {
		foreach ($this->shellPaths as $path) {
			$this->shellPath = $path . $this->shell . '.php';
			$pluginShellPath =  DS . $plugin . DS . 'vendors' . DS . 'shells' . DS;

			if ((strpos($path, $pluginShellPath) !== false || !$plugin) && file_exists($this->shellPath)) {
				$loaded = true;
				break;
			}
		}
		if (!isset($loaded)) {
			throw new MissingShellFileException(array('shell' => $this->shell . '.php'));
		}

		if (!class_exists('Shell')) {
			require CONSOLE_LIBS . 'shell.php';
		}

		if (!class_exists($this->shellClass)) {
			require $this->shellPath;
		}
		if (!class_exists($this->shellClass)) {
			throw new MissingShellClassException(array('shell' => $this->shell));
		}
		$Shell = new $this->shellClass($this);
		return $Shell;
	}

/**
 * Parses command line options
 *
 * @param array $params Parameters to parse
 */
	public function parseParams($params) {
		$this->__parseParams($params);
		$defaults = array('app' => 'app', 'root' => dirname(dirname(dirname(__FILE__))), 'working' => null, 'webroot' => 'webroot');
		$params = array_merge($defaults, array_intersect_key($this->params, $defaults));
		$isWin = false;
		foreach ($defaults as $default => $value) {
			if (strpos($params[$default], '\\') !== false) {
				$isWin = true;
				break;
			}
		}
		$params = str_replace('\\', '/', $params);

		if (!empty($params['working']) && (!isset($this->args[0]) || isset($this->args[0]) && $this->args[0]{0} !== '.')) {
			if (empty($this->params['app']) && $params['working'] != $params['root']) {
				$params['root'] = dirname($params['working']);
				$params['app'] = basename($params['working']);
			} else {
				$params['root'] = $params['working'];
			}
		}

		if ($params['app'][0] == '/' || preg_match('/([a-z])(:)/i', $params['app'], $matches)) {
			$params['root'] = dirname($params['app']);
		} elseif (strpos($params['app'], '/')) {
			$params['root'] .= '/' . dirname($params['app']);
		}

		$params['app'] = basename($params['app']);
		$params['working'] = rtrim($params['root'], '/') . '/' . $params['app'];

		if (!empty($matches[0]) || !empty($isWin)) {
			$params = str_replace('/', '\\', $params);
		}

		$this->params = array_merge($this->params, $params);
	}

/**
 * Helper for recursively parsing params
 *
 * @return array params
 * @access private
 */
	function __parseParams($params) {
		$count = count($params);
		for ($i = 0; $i < $count; $i++) {
			if (isset($params[$i])) {
				if ($params[$i]{0} === '-') {
					$key = substr($params[$i], 1);
					$this->params[$key] = true;
					unset($params[$i]);
					if (isset($params[++$i])) {
						if ($params[$i]{0} !== '-') {
							$this->params[$key] = str_replace('"', '', $params[$i]);
							unset($params[$i]);
						} else {
							$i--;
							$this->__parseParams($params);
						}
					}
				} else {
					$this->args[] = $params[$i];
					unset($params[$i]);
				}

			}
		}
	}

/**
 * Removes first argument and shifts other arguments up
 *
 * @return mixed Null if there are no arguments otherwise the shifted argument
 */
	public function shiftArgs() {
		return array_shift($this->args);
	}

/**
 * Shows console help.  Performs an internal dispatch to the CommandList Shell
 *
 */
	public function help() {
		$this->args = array('command_list');
		$this->dispatch();
	}

/**
 * Stop execution of the current script
 *
 * @param $status see http://php.net/exit for values
 * @return void
 */
	protected function _stop($status = 0) {
		exit($status);
	}
}