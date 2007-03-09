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

	function ConsoleDispatcher($args = array()) {
		$this->__construct($args);
	}

	function initConstants() {
		define('PHP5', (phpversion() >= 5));
		define('DS', DIRECTORY_SEPARATOR);
		define('CAKE_CORE_INCLUDE_PATH', dirname(dirname(dirname(__FILE__))));
		define('ROOT', dirname($this->params['working']));
		define('APP_DIR', basename($this->params['working']));
		define('APP_PATH', ROOT . DS . APP_DIR . DS);
		define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
		define('THE_CORE', CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS);
		define('DISABLE_DEFAULT_ERROR_HANDLING', true);
	}

	function initEnvironment() {
		if (function_exists('ini_set')) {
			ini_set('display_errors', '1');
			ini_set('error_reporting', E_ALL);
			ini_set('max_execution_time', 60 * 5);
		}
		$this->stdin = fopen('php://stdin', 'r');
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');

		if (!isset($this->args[0]) || !isset($this->params['working'])) {
			$this->stdout('This file has been loaded incorrectly and cannot continue.');
			$this->stdout('Please make sure that ' . DIRECTORY_SEPARATOR . 'cake' . DIRECTORY_SEPARATOR . 'scripts is in your system path,');
			$this->stdout('and check the manual for the correct usage of this command.');
			$this->stdout('(http://manual.cakephp.org/)');
			exit();
		}

		if (__FILE__ != $this->args[0]) {
			$this->stdout('Warning: this file may have been loaded incorrectly, which could lead to unexpected results...');
			if ($this->getInput('Continue anyway?', array('y', 'n'), 'y') == 'n') {
				exit();
			}
		}
		$this->shiftArgs();
	}

	function __construct($args = array()) {
		$this->parseParams($args);
		$this->initEnvironment();
		$this->initConstants();

		if (!$this->bootstrap()) {
			$this->stdout("\nUnable to load Cake core:");
			$this->stdout("\tMake sure " . DS . 'cake' . DS . 'libs exists in ' . CAKE_CORE_INCLUDE_PATH);
			exit();
		}
		$this->stdout("\nWelcome to CakePHP v" . Configure::version() . " Console");
		if (!isset($this->args[0]) || $this->args[0] != 'help') {
			$this->stdout("Type 'cake help' for help\n");
		}

		$this->dispatch();
		die("\n");
	}
/**
 * Dispatches a CLI request
 *
 * @return void
 */
	function dispatch() {
		if (isset($this->args[0])) {
			// Load requested script
			$script = $this->args[0];
			$this->script = SCRIPTS . $script . ".php";
			$this->scriptClass = Inflector::camelize($script);
			$this->shiftArgs();

			if (method_exists($this, $script)) {
				$this->{$script}();
			} elseif (!file_exists($this->script)) {
				$this->stdout('Unable to dispatch to requested script: ', false);
				$this->stdout("'{$script}.php' does not exist in " . SCRIPTS);
				exit();
			} else {
				require SCRIPTS . 'console_script.php';
				require $this->script;
				$Script = $this->scriptClass;
				$cli = new $Script($this);
				$cli->main();
			}
		} else {
			$this->stdout('Available commands:');
			foreach (listClasses(THE_CORE . 'scripts') as $script) {
				if ($script != 'dispatch.php' && $script != 'console_script.php') {
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
			THE_CORE . 'basics.php',
			THE_CORE . 'config'.DS.'paths.php',
			THE_CORE . 'dispatcher.php',
			THE_CORE . 'scripts'.DS.'templates'.DS.'skel'.DS.'config'.DS.'core.php'
		);

		foreach ($includes as $inc) {
			if (!@include_once($inc)) {
				$this->stdout("Failed to load Cake core file {$inc}");
				return false;
			}
		}

		$libraries = array('session', 'configure', 'inflector', 'model'.DS.'connection_manager', 'debugger', 'security', 'controller' . DS . 'controller');
		foreach ($libraries as $inc) {
			if (!file_exists(LIBS.$inc.'.php')) {
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
			$this->stdout('');
			$this->stdout($prompt . " $print_options \n" . '> ', false);
		} else {
			$this->stdout('');
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
		fwrite($this->stderr, $string, true);
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
		print_r($this->args);
		print_r($this->params);
	}
}


	$app = 'app';
	$root = dirname(dirname(dirname(__FILE__)));
	$core = null;
	$here = $argv[0];
	$help = null;
	$project = null;

	
	switch ($argv[$i]) {
		case '-a':
		case '-app':
			$app = $argv[$i + 1];
		break;
		case '-c':
		case '-core':
			$core = $argv[$i + 1];
		break;
		case '-r':
		case '-root':
			$root = $argv[$i + 1];
		break;
		case '-p':
		case '-project':
			$project = true;
			$projectPath = $argv[$i + 1];
			$app = $argv[$i + 1];
		break;
	}

	if(!is_dir($app)) {
		$project = true;
		$projectPath = $app;

	}

	if($project) {
		$app = $projectPath;
	}

	$shortPath = str_replace($root, '', $app);
	$shortPath = str_replace('..'.DS, '', $shortPath);
	$shortPath = str_replace(DS.DS, DS, $shortPath);

	$pathArray = explode(DS, $shortPath);
	if(end($pathArray) != '') {
		$appDir = array_pop($pathArray);
	} else {
		array_pop($pathArray);
		$appDir = array_pop($pathArray);
	}
	$rootDir = implode(DS, $pathArray);
	$rootDir = str_replace(DS.DS, DS, $rootDir);

	if(!$rootDir) {
		$rootDir = $root;
		$projectPath = $root.DS.$appDir;
	}

	define ('ROOT', $rootDir);
	define ('APP_DIR', $appDir);
	define ('DEBUG', 1);

/**
 * Base class for command-line utilities for automating programmer chores.
 *
 * @package		cake
 * @subpackage	cake.cake.scripts
 */
class CakeConsoleScript extends Object {

/**
 * CakeConsoleDispatcher object
 *
 * @var object An instance of the ConsoleDispatcher object that loaded this script
 */
	var $dispatch = null;
/**
 * If true, the script will ask for permission to perform actions.
 *
 * @var boolean
 */
	var $interactive = true;
/**
 * Holds the DATABASE_CONFIG object for the app. Null if database.php could not be found,
 * or the app does not exist.
 *
 * @var object
 */
	var $dbConfig = null;
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
 * Initializes this CakeConsoleScript instance.
 *
 */
	function __construct(&$dispatch) {
		$this->dispatch =& $dispatch;
		$this->params = $this->dispatch->params;
		$this->args = $this->dispatch->args;
		if(file_exists(CONFIGS.'database.php')) {
			require_once (CONFIGS . 'database.php');
			$this->dbConfig = new DATABASE_CONFIG();
		}
	}
	
/**
 * Main-loop method.
 *
 */
	function main() {

		$this->out('');
		$this->out('');
		$this->out('Baking...');
		$this->hr();
		$this->out('Name: '. APP_DIR);
		$this->out('Path: '. ROOT.DS.APP_DIR);
		$this->hr();

		if(empty($this->dbConfig)) {
			$this->out('');
			$this->out('Your database configuration was not found. Take a moment to create one:');
		}
		require_once (CONFIGS . 'database.php');
		

		$this->stdout('[M]odel');
		$this->stdout('[C]ontroller');
		$this->stdout('[V]iew');
		$invalidSelection = true;

		while ($invalidSelection) {
			$classToBake = strtoupper($this->in('What would you like to Bake?', array('M', 'V', 'C')));
			switch($classToBake) {
				case 'M':
					$invalidSelection = false;
					$this->doModel();
					break;
				case 'V':
					$invalidSelection = false;
					$this->doView();
					break;
				case 'C':
					$invalidSelection = false;
					$this->doController();
					break;
				default:
					$this->stdout('You have made an invalid selection. Please choose a type of class to Bake by entering M, V, or C.');
			}
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
	function in($prompt, $options = null, $default = null) {
		return $this->dispatch->getInput($prompt, $options, $default);
	}
/**
 * Outputs to the stdout filehandle.
 *
 * @param string $string String to output.
 * @param boolean $newline If true, the outputs gets an added newline.
 */
	function out($string, $newline = true) {
		return $this->dispatch->stdout($string, $newline);
	}
/**
 * Outputs to the stderr filehandle.
 *
 * @param string $string Error text to output.
 */
	function err($string) {
		return $this->dispatch->stderr($string);
	}
/**
 * Outputs a series of minus characters to the standard output, acts as a visual separator.
 *
 */
	function hr() {
		$this->out('---------------------------------------------------------------');
	}
/**
 * Creates a file at given path.
 *
 * @param string $path		Where to put the file.
 * @param string $contents Content to put in the file.
 * @return Success
 */
	function createFile ($path, $contents) {
		$path = str_replace(DS . DS, DS, $path);
		echo "\nCreating file $path\n";
		if (is_file($path) && $this->interactive === true) {
			fwrite($this->stdout, __("File exists, overwrite?", true). " {$path} (y/n/q):");
			$key = trim(fgets($this->stdin));

			if ($key == 'q') {
				fwrite($this->stdout, __("Quitting.", true) ."\n");
				exit;
			} elseif ($key == 'a') {
				$this->dont_ask = true;
			} elseif ($key == 'y') {
			} else {
				fwrite($this->stdout, __("Skip", true) ." {$path}\n");
				return false;
			}
		}

		if ($f = fopen($path, 'w')) {
			fwrite($f, $contents);
			fclose($f);
			fwrite($this->stdout, __("Wrote", true) ."{$path}\n");
			return true;
		} else {
			fwrite($this->stderr, __("Error! Could not write to", true)." {$path}.\n");
			return false;
		}
	}


/**
 * Outputs usage text on the standard output.
 *
 */
	function help() {
		$this->stdout('CakePHP Console:');
		$this->hr();
		$this->stdout('The Bake script generates controllers, views and models for your application.');
		$this->stdout('If run with no command line arguments, Bake guides the user through the class');
		$this->stdout('creation process. You can customize the generation process by telling Bake');
		$this->stdout('where different parts of your application are using command line arguments.');
		$this->stdout('');
		$this->hr('');
		$this->stdout('usage: php bake.php [command] [path...]');
		$this->stdout('');
		$this->stdout('commands:');
		$this->stdout('   -app [path...] Absolute path to Cake\'s app Folder.');
		$this->stdout('   -core [path...] Absolute path to Cake\'s cake Folder.');
		$this->stdout('   -help Shows this help message.');
		$this->stdout('   -project [path...]  Generates a new app folder in the path supplied.');
		$this->stdout('   -root [path...] Absolute path to Cake\'s \app\webroot Folder.');
		$this->stdout('');
	}
/**
 * Returns true if given path is a directory.
 *
 * @param string $path
 * @return True if given path is a directory.
 */
	function isDir($path) {
		if(is_dir($path)) {
			return true;
		} else {
			return false;
		}
	}
/**
 * Recursive directory copy.
 *
 * @param string $fromDir
 * @param string $toDir
 * @param octal $chmod
 * @param boolean	 $verbose
 * @return Success.
 */
	function copyDir($fromDir, $toDir, $chmod = 0755, $verbose = false) {
		$errors = array();
		$messages = array();

		if (!is_dir($toDir)) {
			uses('folder');
			$folder = new Folder();
			$folder->mkdirr($toDir, 0755);
		}

		if (!is_writable($toDir)) {
			$errors[] = 'target '.$toDir.' is not writable';
		}

		if (!is_dir($fromDir)) {
			$errors[] = 'source '.$fromDir.' is not a directory';
		}

		if (!empty($errors)) {
			if ($verbose) {
				foreach($errors as $err) {
					$this->stdout('Error: '.$err);
				}
			}
			return false;
		}
		$exceptions = array('.','..','.svn');
		$handle = opendir($fromDir);

		while (false !== ($item = readdir($handle))) {
			if (!in_array($item,$exceptions)) {
				$from = str_replace('//','/',$fromDir.'/'.$item);
				$to = str_replace('//','/',$toDir.'/'.$item);
				if (is_file($from)) {
					if (@copy($from, $to)) {
						chmod($to, $chmod);
						touch($to, filemtime($from));
						$messages[] = 'File copied from '.$from.' to '.$to;
					} else {
						$errors[] = 'cannot copy file from '.$from.' to '.$to;
					}
				}

				if (is_dir($from)) {
					if (@mkdir($to)) {
						chmod($to, $chmod);
						$messages[] = 'Directory created: '.$to;
					} else {
						$errors[] = 'cannot create directory '.$to;
					}
					$this->copyDir($from,$to,$chmod,$verbose);
				}
			}
		}
		closedir($handle);

		if ($verbose) {
			foreach($errors as $err) {
				$this->stdout('Error: '.$err);
			}
			foreach($messages as $msg) {
				$this->stdout($msg);
			}
		}
		return true;
	}

	function __addAdminRoute($name){
		$file = file_get_contents(CONFIGS.'core.php');
		if (preg_match('%([/\\t\\x20]*define\\(\'CAKE_ADMIN\',[\\t\\x20\'a-z]*\\);)%', $file, $match)) {
			$result = str_replace($match[0], 'define(\'CAKE_ADMIN\', \''.$name.'\');', $file);

			if(file_put_contents(CONFIGS.'core.php', $result)){
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}

?>