#!/usr/bin/php -q
<?php
/* SVN FILE: $Id$ */
/**
 * Command-line code generation utility to automate programmer chores.
 *
 * Shell dispatcher class
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008,	Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console
 * @since         CakePHP(tm) v 1.2.0.5012
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
if (!defined('E_DEPRECATED')) {
	define('E_DEPRECATED', 8192);
}
/**
 * Shell dispatcher
 *
 * @package       cake
 * @subpackage    cake.cake.console
 */
class ShellDispatcher {
/**
 * Standard input stream.
 *
 * @var filehandle
 * @access public
 */
	var $stdin;
/**
 * Standard output stream.
 *
 * @var filehandle
 * @access public
 */
	var $stdout;
/**
 * Standard error stream.
 *
 * @var filehandle
 * @access public
 */
	var $stderr;
/**
 * Contains command switches parsed from the command line.
 *
 * @var array
 * @access public
 */
	var $params = array();
/**
 * Contains arguments parsed from the command line.
 *
 * @var array
 * @access public
 */
	var $args = array();
/**
 * The file name of the shell that was invoked.
 *
 * @var string
 * @access public
 */
	var $shell = null;
/**
 * The class name of the shell that was invoked.
 *
 * @var string
 * @access public
 */
	var $shellClass = null;
/**
 * The command called if public methods are available.
 *
 * @var string
 * @access public
 */
	var $shellCommand = null;
/**
 * The path locations of shells.
 *
 * @var array
 * @access public
 */
	var $shellPaths = array();
/**
 * The path to the current shell location.
 *
 * @var string
 * @access public
 */
	var $shellPath = null;
/**
 * The name of the shell in camelized.
 *
 * @var string
 * @access public
 */
	var $shellName = null;
/**
 * Constructs this ShellDispatcher instance.
 *
 * @param array $args the argv.
 */
	function ShellDispatcher($args = array()) {
		$this->__construct($args);
	}
/**
 * Constructor
 *
 * @param array $args the argv.
 */
	function __construct($args = array()) {
		set_time_limit(0);
		$this->__initConstants();
		$this->parseParams($args);
		$this->_initEnvironment();
		$this->__buildPaths();
		$this->_stop($this->dispatch());
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
			define('PHP5', (PHP_VERSION >= 5));
			define('DS', DIRECTORY_SEPARATOR);
			define('CAKE_CORE_INCLUDE_PATH', dirname(dirname(dirname(__FILE__))));
			define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
			define('DISABLE_DEFAULT_ERROR_HANDLING', false);
			define('CAKEPHP_SHELL', true);
		}
		require_once(CORE_PATH . 'cake' . DS . 'basics.php');
	}
/**
 * Defines current working environment.
 *
 * @access protected
 */
	function _initEnvironment() {
		$this->stdin = fopen('php://stdin', 'r');
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');

		if (!$this->__bootstrap()) {
			$this->stderr("\nCakePHP Console: ");
			$this->stderr("\nUnable to load Cake core:");
			$this->stderr("\tMake sure " . DS . 'cake' . DS . 'libs exists in ' . CAKE_CORE_INCLUDE_PATH);
			$this->_stop();
		}

		if (!isset($this->args[0]) || !isset($this->params['working'])) {
			$this->stderr("\nCakePHP Console: ");
			$this->stderr('This file has been loaded incorrectly and cannot continue.');
			$this->stderr('Please make sure that ' . DIRECTORY_SEPARATOR . 'cake' . DIRECTORY_SEPARATOR . 'console is in your system path,');
			$this->stderr('and check the manual for the correct usage of this command.');
			$this->stderr('(http://manual.cakephp.org/)');
			$this->_stop();
		}

		if (basename(__FILE__) !=  basename($this->args[0])) {
			$this->stderr("\nCakePHP Console: ");
			$this->stderr('Warning: the dispatcher may have been loaded incorrectly, which could lead to unexpected results...');
			if ($this->getInput('Continue anyway?', array('y', 'n'), 'y') == 'n') {
				$this->_stop();
			}
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
		$pluginPaths = Configure::read('pluginPaths');
		if (!class_exists('Folder')) {
			require LIBS . 'folder.php';
		}

		foreach ($pluginPaths as $pluginPath) {
			$Folder = new Folder($pluginPath);
			list($plugins,) = $Folder->read(false, true);
			foreach ((array)$plugins as $plugin) {
				$path = $pluginPath . Inflector::underscore($plugin) . DS . 'vendors' . DS . 'shells' . DS;
				if (file_exists($path)) {
					$paths[] = $path;
				}
			}
		}

		$vendorPaths = array_values(Configure::read('vendorPaths'));
		foreach ($vendorPaths as $vendorPath) {
			$path = rtrim($vendorPath, DS) . DS . 'shells' . DS;
			if (file_exists($path)) {
				$paths[] = $path;
			}
		}

		$this->shellPaths = array_values(array_unique(array_merge($paths, Configure::read('shellPaths'))));
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

		$includes = array(
			CORE_PATH . 'cake' . DS . 'config' . DS . 'paths.php',
			CORE_PATH . 'cake' . DS . 'libs' . DS . 'object.php',
			CORE_PATH . 'cake' . DS . 'libs' . DS . 'inflector.php',
			CORE_PATH . 'cake' . DS . 'libs' . DS . 'configure.php',
			CORE_PATH . 'cake' . DS . 'libs' . DS . 'file.php',
			CORE_PATH . 'cake' . DS . 'libs' . DS . 'cache.php',
			CORE_PATH . 'cake' . DS . 'libs' . DS . 'string.php',
			CORE_PATH . 'cake' . DS . 'libs' . DS . 'class_registry.php',
			CORE_PATH . 'cake' . DS . 'console' . DS . 'error.php'
		);

		foreach ($includes as $inc) {
			if (!require($inc)) {
				$this->stderr("Failed to load Cake core file {$inc}");
				return false;
			}
		}

		Configure::getInstance(file_exists(CONFIGS . 'bootstrap.php'));

		if (!file_exists(APP_PATH . 'config' . DS . 'core.php')) {
			include_once CORE_PATH . 'cake' . DS . 'console' . DS . 'libs' . DS . 'templates' . DS . 'skel' . DS . 'config' . DS . 'core.php';
			Configure::buildPaths(array());
		}

		Configure::write('debug', 1);
		return true;
	}
/**
 * Dispatches a CLI request
 *
 * @access public
 */
	function dispatch() {
		if (isset($this->args[0])) {
			$plugin = null;
			$shell = $this->args[0];
			if (strpos($shell, '.') !== false)  {
				list($plugin, $shell) = explode('.', $this->args[0]);
			}

			$this->shell = $shell;
			$this->shiftArgs();
			$this->shellName = Inflector::camelize($this->shell);
			$this->shellClass = $this->shellName . 'Shell';

			if ($this->shell === 'help') {
				$this->help();
			} else {
				$loaded = false;
				foreach ($this->shellPaths as $path) {
					$this->shellPath = $path . $this->shell . '.php';

					$isPlugin = ($plugin && strpos($path, DS . $plugin . DS . 'vendors' . DS . 'shells' . DS) !== false);
					if (($isPlugin && file_exists($this->shellPath)) || (!$plugin && file_exists($this->shellPath))) {
						$loaded = true;
						break;
					}
				}

				if ($loaded) {
					if (!class_exists('Shell')) {
						require CONSOLE_LIBS . 'shell.php';
					}
					require $this->shellPath;
					if (class_exists($this->shellClass)) {
						$command = null;
						if (isset($this->args[0])) {
							$command = $this->args[0];
						}
						$this->shellCommand = Inflector::variable($command);
						$shell = new $this->shellClass($this);

						if (strtolower(get_parent_class($shell)) == 'shell') {
							$shell->initialize();
							$shell->loadTasks();

							foreach ($shell->taskNames as $task) {
								if (strtolower(get_parent_class($shell)) == 'shell') {
									$shell->{$task}->initialize();
									$shell->{$task}->loadTasks();
								}
							}

							$task = Inflector::camelize($command);
							if (in_array($task, $shell->taskNames)) {
								$this->shiftArgs();
								$shell->{$task}->startup();
								if (isset($this->args[0]) && $this->args[0] == 'help') {
									if (method_exists($shell->{$task}, 'help')) {
										$shell->{$task}->help();
										$this->_stop();
									} else {
										$this->help();
									}
								}
								return $shell->{$task}->execute();
							}
						}

						$classMethods = get_class_methods($shell);

						$privateMethod = $missingCommand = false;
						if ((in_array($command, $classMethods) || in_array(strtolower($command), $classMethods)) && strpos($command, '_', 0) === 0) {
							$privateMethod = true;
						}

						if (!in_array($command, $classMethods) && !in_array(strtolower($command), $classMethods)) {
							$missingCommand = true;
						}

						$protectedCommands = array(
							'initialize','in','out','err','hr',
							'createfile', 'isdir','copydir','object','tostring',
							'requestaction','log','cakeerror', 'shelldispatcher',
							'__initconstants','__initenvironment','__construct',
							'dispatch','__bootstrap','getinput','stdout','stderr','parseparams','shiftargs'
						);

						if (in_array(strtolower($command), $protectedCommands)) {
							$missingCommand = true;
						}

						if ($missingCommand && method_exists($shell, 'main')) {
							$shell->startup();
							return $shell->main();
						} elseif (!$privateMethod && method_exists($shell, $command)) {
							$this->shiftArgs();
							$shell->startup();
							return $shell->{$command}();
						} else {
							$this->stderr("Unknown {$this->shellName} command '$command'.\nFor usage, try 'cake {$this->shell} help'.\n\n");
						}
					} else {
						$this->stderr('Class '.$this->shellClass.' could not be loaded');
					}
				} else {
					$this->help();
				}
			}
		} else {
			$this->help();
		}
	}
/**
 * Prompts the user for input, and returns it.
 *
 * @param string $prompt Prompt text.
 * @param mixed $options Array or string of options.
 * @param string $default Default input value.
 * @return Either the default value, or the user-provided input.
 * @access public
 */
	function getInput($prompt, $options = null, $default = null) {
		if (!is_array($options)) {
			$printOptions = '';
		} else {
			$printOptions = '(' . implode('/', $options) . ')';
		}

		if ($default == null) {
			$this->stdout($prompt . " $printOptions \n" . '> ', false);
		} else {
			$this->stdout($prompt . " $printOptions \n" . "[$default] > ", false);
		}
		$result = fgets($this->stdin);

		if ($result === false) {
			exit;
		}
		$result = trim($result);

		if ($default != null && empty($result)) {
			return $default;
		}
		return $result;
	}
/**
 * Outputs to the stdout filehandle.
 *
 * @param string $string String to output.
 * @param boolean $newline If true, the outputs gets an added newline.
 * @access public
 */
	function stdout($string, $newline = true) {
		if ($newline) {
			fwrite($this->stdout, $string . "\n");
		} else {
			fwrite($this->stdout, $string);
		}
	}
/**
 * Outputs to the stderr filehandle.
 *
 * @param string $string Error text to output.
 * @access public
 */
	function stderr($string) {
		fwrite($this->stderr, 'Error: '. $string);
	}
/**
 * Parses command line options
 *
 * @param array $params Parameters to parse
 * @access public
 */
	function parseParams($params) {
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
 * Helper for recursively paraing params
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
 * @return boolean False if there are no arguments
 * @access public
 */
	function shiftArgs() {
		if (empty($this->args)) {
			return false;
		}
		unset($this->args[0]);
		$this->args = array_values($this->args);
		return true;
	}
/**
 * Shows console help
 *
 * @access public
 */
	function help() {
		$this->stdout("\nWelcome to CakePHP v" . Configure::version() . " Console");
		$this->stdout("---------------------------------------------------------------");
		$this->stdout("Current Paths:");
		$this->stdout(" -app: ". $this->params['app']);
		$this->stdout(" -working: " . rtrim($this->params['working'], DS));
		$this->stdout(" -root: " . rtrim($this->params['root'], DS));
		$this->stdout(" -core: " . rtrim(CORE_PATH, DS));
		$this->stdout("");
		$this->stdout("Changing Paths:");
		$this->stdout("your working path should be the same as your application path");
		$this->stdout("to change your path use the '-app' param.");
		$this->stdout("Example: -app relative/path/to/myapp or -app /absolute/path/to/myapp");

		$this->stdout("\nAvailable Shells:");
		$_shells = array();

		foreach ($this->shellPaths as $path) {
			if (is_dir($path)) {
				$shells = Configure::listObjects('file', $path);
				$path = str_replace(CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS, 'CORE' . DS, $path);
				$path = str_replace(APP, 'APP' . DS, $path);
				$path = str_replace(ROOT, 'ROOT', $path);
				$path = rtrim($path, DS);
				$this->stdout("\n " . $path . ":");
				if (empty($shells)) {
					$this->stdout("\t - none");
				} else {
					sort($shells);
					foreach ($shells as $shell) {
						if ($shell !== 'shell.php') {
							$this->stdout("\t " . str_replace('.php', '', $shell));
						}
					}
				}
			}
		}
		$this->stdout("\nTo run a command, type 'cake shell_name [args]'");
		$this->stdout("To get help on a specific command, type 'cake shell_name help'");
		$this->_stop();
	}
/**
 * Stop execution of the current script
 *
 * @param $status see http://php.net/exit for values
 * @return void
 * @access protected
 */
	function _stop($status = 0) {
		exit($status);
	}
}
if (!defined('DISABLE_AUTO_DISPATCH')) {
	$dispatcher = new ShellDispatcher($argv);
}
?>