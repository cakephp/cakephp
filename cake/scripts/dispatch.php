#!/usr/bin/php -q
<?php
/* SVN FILE: $Id$ */
/**
 * Command-line code generation utility to automate programmer chores.
 *
 * CLI dispatcher class
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
 * @subpackage		cake.cake.scripts.bake
 * @since			CakePHP(tm) v 1.2
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

if (!defined('DISABLE_AUTO_DISPATCH')) {
	$dispatcher = new ConsoleDispatcher($argv);
}

class ConsoleDispatcher {
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
 * The file name of the script that was invoked.
 *
 * @var string
 */
	var $script = null;
/**
 * The class name of the script that was invoked.
 *
 * @var string
 */
	var $scriptClass = null;

/**
 * The path location of scripts.
 *
 * @var array
 */
	var $scriptPaths = array();

	function ConsoleDispatcher($args = array()) {
		$this->__construct($args);
	}

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
		define('ROOT', dirname($this->params['working']));
		define('APP_DIR', basename($this->params['working']));
		define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
		define('DISABLE_DEFAULT_ERROR_HANDLING', false);
	}

	function __initEnvironment() {
		$this->stdin = fopen('php://stdin', 'r');
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');

		if (!isset($this->args[0]) || !isset($this->params['working'])) {
			$this->stdout("\nCakePHP Console: ");
			$this->stdout('This file has been loaded incorrectly and cannot continue.');
			$this->stdout('Please make sure that ' . DIRECTORY_SEPARATOR . 'cake' . DIRECTORY_SEPARATOR . 'scripts is in your system path,');
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
		$this->shiftArgs();

		if (!$this->bootstrap()) {
			$this->stdout("\nCakePHP Console: ");
			$this->stdout("\nUnable to load Cake core:");
			$this->stdout("\tMake sure " . DS . 'cake' . DS . 'libs exists in ' . CAKE_CORE_INCLUDE_PATH);
			exit();
		}

		$this->scriptPaths = array(
								VENDORS . 'scritps' . DS,
								APP . 'vendors' . DS . 'scripts' . DS,
								SCRIPTS
							);
	}

	function __construct($args = array()) {
		$this->parseParams($args);
		$this->__initConstants();
		$this->__initEnvironment();
		$this->dispatch();
		die("\n");
	}
/**
 * Dispatches a CLI request
 *
 * @return void
 */
	function dispatch() {
		$this->stdout("\nWelcome to CakePHP v" . Configure::version() . " Console");
		if (!isset($this->args[0]) || $this->args[0] != 'help') {
			$this->stdout("Type 'cake help' for help\n");
		}
		$protectedCommands = array('initialize', 'main','in','out','err','hr',
									'createFile', 'isDir','copyDir','Object','toString',
									'requestAction','log','cakeError', 'ConsoleDispatcher',
									'__initConstants','__initEnvironment','__construct',
									'dispatch','bootstrap','getInput','stdout','stderr','parseParams','shiftArgs'
								);
		if (isset($this->args[0])) {
			// Load requested script
			$this->script = $this->args[0];
			$this->shiftArgs();
			$this->scriptName = Inflector::camelize($this->script);
			$this->scriptClass = $this->scriptName . 'Script';
			
			if (method_exists($this, $this->script) && !in_array($this->script, $protectedCommands)) {
				$this->{$this->script}();
			} else {
				$loaded = false;
				foreach($this->scriptPaths as $path) {
					$this->scriptPath = $path . $this->script . ".php";
					if (file_exists($this->scriptPath)) {
						$loaded = true;
						break;
					}
				}

				if (!$loaded) {
					$this->stdout('Unable to dispatch requested script: ', false);
					$this->stdout("'{$script}.php' does not exist in: \n" . implode("\nor ", $this->scriptPaths));
					exit();
				} else {
					require SCRIPTS . 'cake_script.php';
					require $this->scriptPath;
					if(class_exists($this->scriptClass)) {
						$script = new $this->scriptClass($this);

						$command = null;
						if(isset($this->args[0])) {
							$command = $this->args[0];
						}
						$classMethods = get_class_methods($script);
						
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
						if($command == 'help') {
							if(method_exists($script, 'help')) {
								$script->initialize();
								$script->help();
							} else {
								$this->help();
							}
						} else if($missingCommand && method_exists($script, 'main')) {
							$script->initialize();
							$script->main();
						} else if(!$privateMethod && method_exists($script, $command)) {
							$script->command = $command;
							$this->shiftArgs();
							$script->initialize();
							$script->{$command}();
						} else {
							$this->stderr("Unknown {$this->scriptName} command '$command'.\nFor usage, try 'cake {$this->script} help'.\n\n");
						}
					} else {
						$this->stderr('Class '.$this->scriptClass.' could not be loaded');
					}
				}
			}
		} else {
			$this->stdout('Available Scripts:');
			foreach (listClasses(CAKE . 'scripts') as $script) {
				if ($script != 'dispatch.php' && $script != 'cake_script.php') {
					$this->stdout("\t - " . r('.php', '', $script));
				}
			}
			$this->stdout("\nTo run a command, type 'cake script_name [args]'");
			$this->stdout("To get help on a specific command, type 'cake script_name help'");
		}
	}
/**
 * Initializes the environment and loads the Cake core.
 *
 * @return boolean Returns false if loading failed.
 */
	function bootstrap() {
		$includes = array(
			CORE_PATH . 'cake' . DS . 'basics.php',
			CORE_PATH . 'cake' . DS . 'config' . DS . 'paths.php',
			CORE_PATH . 'cake' . DS . 'scripts'.DS.'templates'.DS.'skel'.DS.'config'.DS.'core.php'
		);

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
		$this->stdout("\nConsole Help:");
		$this->stdout('-------------');
		echo 'Args ';
		print_r($this->args);
		echo 'Params ';
		print_r($this->params);
	}
}
?>