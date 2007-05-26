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
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007,	Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.console
 * @since			CakePHP(tm) v 1.2.0.5012
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Shell dispatcher
 *
 * @package     cake
 * @subpackage  cake.cake.console
 */
class ShellDispatcher {
/**
 * Standard input stream.
 *
 * @var filehandle
 */
	var $stdin;
/**
 * Standard output stream.
 *
 * @var filehandle
 */
	var $stdout;
/**
 * Standard error stream.
 *
 * @var filehandle
 */
	var $stderr;
/**
 * Contains command switches parsed from the command line.
 *
 * @var array
 */
	var $params = array();
/**
 * Contains arguments parsed from the command line.
 *
 * @var array
 */
	var $args = array();
/**
 * The file name of the shell that was invoked.
 *
 * @var string
 */
	var $shell = null;
/**
 * The class name of the shell that was invoked.
 *
 * @var string
 */
	var $shellClass = null;

/**
 * The command called if public methods are available.
 *
 * @var string
 */
	var $shellCommand = null;


/**
 * The path location of shells.
 *
 * @var array
 */
	var $shellPaths = array();

/**
 * The path to the current shell location.
 *
 * @var string
 */
	var $shellPath = null;

/**
 * The name of the shell in camelized.
 *
 * @var string
 */
	var $shellName = null;


/**
 *  Constructs this ShellDispatcher instance.
 *
 * @param array $args the argv.
 * @return void
 */
	function ShellDispatcher($args = array()) {
		$this->__construct($args);
	}

	function __construct($args = array()) {
		$this->__initConstants();
		$this->parseParams($args);
		$this->__initEnvironment();
		$this->dispatch();
		die("\n");
	}
/**
 *  Defines core configuration.
 *
 * @return void
 */
	function __initConstants() {
		if (function_exists('ini_set')) {
			ini_set('display_errors', '1');
			ini_set('error_reporting', E_ALL);
			ini_set('html_errors', false);
			ini_set('implicit_flush', true);
			ini_set('max_execution_time', 60 * 5);
		}
		define('PHP5', (phpversion() >= 5));
		define('DS', DIRECTORY_SEPARATOR);
		define('CAKE_CORE_INCLUDE_PATH', dirname(dirname(dirname(__FILE__))));
		define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
		define('DISABLE_DEFAULT_ERROR_HANDLING', false);
	}
/**
 *  Defines current working environment.
 *
 * @return void
 */
	function __initEnvironment() {

		$this->stdin = fopen('php://stdin', 'r');
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');

		if (!isset($this->args[0]) || !isset($this->params['working'])) {
			$this->stdout("\nCakePHP Console: ");
			$this->stdout('This file has been loaded incorrectly and cannot continue.');
			$this->stdout('Please make sure that ' . DIRECTORY_SEPARATOR . 'cake' . DIRECTORY_SEPARATOR . 'console is in your system path,');
			$this->stdout('and check the manual for the correct usage of this command.');
			$this->stdout('(http://manual.cakephp.org/)');
			exit();
		}

		if (basename(__FILE__) !=  basename($this->args[0])) {
			$this->stdout("\nCakePHP Console: ");
			$this->stdout('Warning: the dispatcher may have been loaded incorrectly, which could lead to unexpected results...');
			if ($this->getInput('Continue anyway?', array('y', 'n'), 'y') == 'n') {
				exit();
			}
		}

		if (!$this->__bootstrap()) {
			$this->stdout("\nCakePHP Console: ");
			$this->stdout("\nUnable to load Cake core:");
			$this->stdout("\tMake sure " . DS . 'cake' . DS . 'libs exists in ' . CAKE_CORE_INCLUDE_PATH);
			exit();
		}

		$this->shiftArgs();

		$this->shellPaths = array(
								APP . 'vendors' . DS . 'shells' . DS,
								VENDORS . 'shells' . DS,
								CONSOLE_LIBS
							);
	}
/**
 * Initializes the environment and loads the Cake core.
 *
 * @return boolean Returns false if loading failed.
 */
	function __bootstrap() {

		define('ROOT', $this->params['root']);
		define('APP_DIR', $this->params['app']);
		define('APP_PATH', ROOT . DS . APP_DIR . DS);

		$includes = array(
			CORE_PATH . 'cake' . DS . 'basics.php',
			CORE_PATH . 'cake' . DS . 'config' . DS . 'paths.php',
		);

		if(!file_exists(APP_PATH . 'config' . DS . 'core.php')) {
			$includes[] = CORE_PATH . 'cake' . DS . 'console' . DS . 'libs' . DS . 'templates' . DS . 'skel' . DS . 'config' . DS . 'core.php';
		} else {
			$includes[] = APP_PATH . 'config' . DS . 'core.php';
		}

		foreach ($includes as $inc) {
			if (!@include_once($inc)) {
				$this->stdout("Failed to load Cake core file {$inc}");
				return false;
			}
		}

		$libraries = array('object', 'session', 'configure', 'inflector', 'model'.DS.'connection_manager',
							'debugger', 'security', 'controller' . DS . 'controller');
		foreach ($libraries as $inc) {
			if (!file_exists(LIBS . $inc . '.php')) {
				$this->stdout("Failed to load Cake core class " . ucwords($inc));
				$this->stdout("(" . LIBS.$inc.".php)");
				return false;
			}
			uses($inc);
		}
		Configure::getInstance(file_exists(CONFIGS . 'bootstrap.php'));
		Configure::write('debug', 1);
		return true;
	}

/**
 * Dispatches a CLI request
 *
 * @return void
 */
	function dispatch() {
		$this->stdout("\nWelcome to CakePHP v" . Configure::version() . " Console");
		$this->stdout("---------------------------------------------------------------");
		$protectedCommands = array('initialize', 'main','in','out','err','hr',
									'createFile', 'isDir','copyDir','Object','toString',
									'requestAction','log','cakeError', 'ShellDispatcher',
									'__initConstants','__initEnvironment','__construct',
									'dispatch','__bootstrap','getInput','stdout','stderr','parseParams','shiftArgs'
								);
		if (isset($this->args[0])) {
			// Load requested shell
			$this->shell = $this->args[0];
			$this->shiftArgs();
			$this->shellName = Inflector::camelize($this->shell);
			$this->shellClass = $this->shellName . 'Shell';

			if (method_exists($this, $this->shell) && !in_array($this->shell, $protectedCommands)) {
				$this->{$this->shell}();
			} else {
				$loaded = false;
				foreach($this->shellPaths as $path) {
					$this->shellPath = $path . $this->shell . ".php";
					if (file_exists($this->shellPath)) {
						$loaded = true;
						break;
					}
				}

				if ($loaded) {
					require CONSOLE_LIBS . 'shell.php';
					require $this->shellPath;
					if(class_exists($this->shellClass)) {
						$shell = new $this->shellClass($this);

						$command = null;
						if(isset($this->args[0])) {
							$command = $this->args[0];
						}

						if($command == 'help') {
							if(method_exists($shell, 'help')) {
								$shell->command = $command;
								$this->shiftArgs();
								$shell->initialize();
								$shell->help();
								exit();
							} else {
								$this->help();
							}
						}

						$task = Inflector::camelize($command);
						if(in_array($task, $shell->taskNames)) {
							$this->shiftArgs();
							$shell->{$task}->initialize();
							$shell->{$task}->execute();
							return;
						}

						$classMethods = get_class_methods($shell);

						$privateMethod = $missingCommand = false;
						if((in_array($command, $classMethods) || in_array(strtolower($command), $classMethods)) && strpos($command, '_', 0) === 0) {
							$privateMethod = true;
						}

						if(!in_array($command, $classMethods) && !in_array(strtolower($command), $classMethods)) {
							$missingCommand = true;
						}

						if (in_array(strtolower($command), $protectedCommands)) {
							$missingCommand = true;
						}


						if($missingCommand && method_exists($shell, 'main')) {
							$shell->initialize();
							$shell->main();
						} else if($missingCommand && method_exists($shell, 'help')) {
							$shell->command = $command;
							$this->shiftArgs();
							$shell->initialize();
							$shell->help();
						} else if(!$privateMethod && method_exists($shell, $command)) {
							$shell->command = $command;
							$this->shiftArgs();
							$shell->initialize();
							$shell->{$command}();
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
 */
	function getInput($prompt, $options = null, $default = null) {
		if (!is_array($options)) {
			$print_options = '';
		} else {
			$print_options = '(' . implode('/', $options) . ')';
		}

		if($default == null) {
			$this->stdout($prompt . " $print_options \n" . '> ', false);
		} else {
			$this->stdout($prompt . " $print_options \n" . "[$default] > ", false);
		}
		$result = trim(fgets($this->stdin));

		if($default != null && empty($result)) {
			return $default;
		} else {
			return $result;
		}
	}
/**
 * Outputs to the stdout filehandle.
 *
 * @param string $string String to output.
 * @param boolean $newline If true, the outputs gets an added newline.
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
 */
	function stderr($string) {
		fwrite($this->stderr, 'Error: '. $string);
	}
/**
 * Parses command line options
 *
 * @param array $params
 * @return void
 */
	function parseParams($params) {
		$out = array();
		for ($i = 0; $i < count($params); $i++) {
			if (strpos($params[$i], '-') === 0) {
				$this->params[substr($params[$i], 1)] = str_replace('"', '', $params[++$i]);
			} else {
				$this->args[] = $params[$i];
			}
		}

		$app = 'app';
		$root = dirname(dirname(dirname(__FILE__)));
		$working = $root;

		if(!empty($this->params['working'])) {
			$root = dirname($this->params['working']);
			$app = basename($this->params['working']);
 		} else {
			$this->params['working'] = $root;
		}

		if(!empty($this->params['app'])) {
			if($this->params['app']{0} == '/') {
				$root = dirname($this->params['app']);
				$app = basename($this->params['app']);
			} else {
				$root = realpath($this->params['working']);
				$app = $this->params['app'];
 			}
			unset($this->params['app']);
		}

		if(in_array($app, array('cake', 'console')) || realpath($root.DS.$app) === dirname(dirname(dirname(__FILE__)))) {
			$root = dirname(dirname(dirname(__FILE__)));
			$app = 'app';
		}

		$working = $root . DS . $app;

		$this->params = array_merge(array('app'=> $app, 'root'=> $root, 'working'=> $working), $this->params);
	}
/**
 * Removes first argument and shifts other arguments up
 *
 * @return boolean False if there are no arguments
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
 * @return void
 */
	function help() {
		$this->stdout("Current Paths:");
		$this->stdout(" -working: " . $this->params['working']);
		$this->stdout(" -root: " . ROOT);
		$this->stdout(" -app: ". APP);
		$this->stdout(" -core: " . CORE_PATH);
		$this->stdout("");
		$this->stdout("Changing Paths:");
		$this->stdout("your working path should be the same as your application path");
		$this->stdout("to change your path use the '-app' param.");
		$this->stdout("Example: -app relative/path/to/myapp or -app /absolute/path/to/myapp");

		$this->stdout("\nAvailable Shells:");
		foreach($this->shellPaths as $path) {
			if(is_dir($path)) {
				$shells = listClasses($path);
				$path = r(CORE_PATH, '', $path);
				$this->stdout("\n " . $path . ":");
				if(empty($shells)) {
					$this->stdout("\t - none");
				} else {
					foreach ($shells as $shell) {
						if ($shell != 'shell.php') {
							$this->stdout("\t " . r('.php', '', $shell));
						}
					}
				}
			}
		}
		$this->stdout("\nTo run a command, type 'cake shell_name [args]'");
		$this->stdout("To get help on a specific command, type 'cake shell_name help'");
		exit();
	}
}
if (!defined('DISABLE_AUTO_DISPATCH')) {
	$dispatcher = new ShellDispatcher($argv);
}
?>