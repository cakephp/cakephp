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
 * @package       cake.console
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Shell dispatcher handles dispatching cli commands.
 *
 * @package       cake.console
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
 * Constructor
 *
 * The execution of the script is stopped after dispatching the request with
 * a status code of either 0 or 1 according to the result of the dispatch.
 *
 * @param array $args the argv
 * @return void
 */
	public function __construct($args = array(), $bootstrap = true) {
		set_time_limit(0);

		if ($bootstrap) {
			$this->__initConstants();
		}
		$this->parseParams($args);
		if ($bootstrap) {
			$this->_initEnvironment();
		}
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
			ini_set('html_errors', false);
			ini_set('implicit_flush', true);
			ini_set('max_execution_time', 0);
		}

		if (!defined('CAKE_CORE_INCLUDE_PATH')) {
			define('DS', DIRECTORY_SEPARATOR);
			define('CAKE_CORE_INCLUDE_PATH', dirname(dirname(dirname(__FILE__))));
			define('CAKEPHP_SHELL', true);
			if (!defined('CORE_PATH')) {
				define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
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
			throw new CakeException($message);
		}

		if (!isset($this->args[0]) || !isset($this->params['working'])) {
			$message = "This file has been loaded incorrectly and cannot continue.\n" .
				"Please make sure that " . DIRECTORY_SEPARATOR . "cake" . DIRECTORY_SEPARATOR . "console is in your system path,\n" . 
				"and check the cookbook for the correct usage of this command.\n" .
				"(http://book.cakephp.org/)";
			throw new CakeException($message);
		}

		$this->shiftArgs();
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
			define('TMP', CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS . 'console' . DS . 'templates' . DS . 'skel' . DS . 'tmp' . DS);
		}

		$boot = file_exists(ROOT . DS . APP_DIR . DS . 'config' . DS . 'bootstrap.php');
		require CORE_PATH . 'cake' . DS . 'bootstrap.php';

		if (!file_exists(APP_PATH . 'config' . DS . 'core.php')) {
			include_once CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS . 'console' . DS . 'templates' . DS . 'skel' . DS . 'config' . DS . 'core.php';
			App::build();
		}
		require_once CONSOLE_LIBS . 'console_error_handler.php';
		set_exception_handler(array('ConsoleErrorHandler', 'handleException'));
		set_error_handler(array('ConsoleErrorHandler', 'handleError'), Configure::read('Error.level'));

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
		$shell = $this->shiftArgs();

		if (!$shell) {
			$this->help();
			return false;
		}
		if (in_array($shell, array('help', '--help', '-h'))) {
			$this->help();
			return true;
		}

		$Shell = $this->_getShell($shell);

		$command = null;
		if (isset($this->args[0])) {
			$command = $this->args[0];
		}

		if ($Shell instanceof Shell) {
			$Shell->initialize();
			$Shell->loadTasks();
			return $Shell->runCommand($command, $this->args);
		}
		$methods = array_diff(get_class_methods($Shell), get_class_methods('Shell'));
		$added = in_array($command, $methods);
		$private = $command[0] == '_' && method_exists($Shell, $command);

		if (!$private) {
			if ($added) {
				$this->shiftArgs();
				$Shell->startup();
				return $Shell->{$command}();
			}
			if (method_exists($Shell, 'main')) {
				$Shell->startup();
				return $Shell->main();
			}
		}
		throw new MissingShellMethodException(array('shell' => $shell, 'method' => $arg));
	}

/**
 * Get shell to use, either plugin shell or application shell
 *
 * All paths in the loaded shell paths are searched.
 *
 * @param string $shell Optionally the name of a plugin
 * @return mixed False if no shell could be found or an object on success
 * @throws MissingShellFileException, MissingShellClassException when errors are encountered.
 */
	protected function _getShell($shell) {
		list($plugin, $shell) = pluginSplit($shell, true);

		$loaded = App::import('Shell', $plugin . $shell);
		$class = Inflector::camelize($shell) . 'Shell';
	
		if (!$loaded) {
			throw new MissingShellFileException(array('shell' => $shell));
		}
		if (!class_exists($class)) {
			throw new MissingShellClassException(array('shell' => $class));
		}
		$Shell = new $class();
		return $Shell;
	}

/**
 * Parses command line options and extracts the directory paths from $params
 *
 * @param array $params Parameters to parse
 */
	public function parseParams($args) {
		$this->_parsePaths($args);

		$defaults = array(
			'app' => 'app', 
			'root' => dirname(dirname(dirname(__FILE__))),
			'working' => null, 
			'webroot' => 'webroot'
		);
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
 * Parses out the paths from from the argv
 *
 * @return void
 */
	protected function _parsePaths($args) {
		$parsed = array();
		$keys = array('-working', '--working', '-app', '--app', '-root', '--root');
		foreach ($keys as $key) {
			$index = array_search($key, $args);
			if ($index !== false) {
				$keyname = str_replace('-', '', $key);
				$valueIndex = $index + 1;
				$parsed[$keyname] = $args[$valueIndex];
				array_splice($args, $index, 2);
			}
		}
		$this->args = $args;
		$this->params = $parsed;
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
		$this->args = array_merge(array('command_list'), $this->args);
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