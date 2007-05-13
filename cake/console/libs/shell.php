<?php
/* SVN FILE: $Id$ */
/**
 * Base class for Shells
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
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
 * @subpackage		cake.cake.scripts
 * @since			CakePHP(tm) v 1.2.0.4604
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Base class for command-line utilities for automating programmer chores.
 *
 * @package		cake
 * @subpackage	cake.cake.console.libs
 */
require_once CAKE . 'console' . DS . 'error.php';

class Shell extends Object {

/**
 * ShellDispatcher object
 *
 * @var object An instance of the ShellDispatcher object that loaded this script
 */
	var $Dispatch = null;
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
	var $className = null;
/**
 * The command called if public methods are available.
 *
 * @var string
 */
	var $command = null;
/**
 * The name of the shell in camelized.
 *
 * @var string
 */
	var $name = null;
/**
 * Contains tasks to load and instantiate
 *
 * @var array
 */
	var $tasks = array();
/**
 * Contains the loaded tasks 
 *
 * @var array
 */
	var $taskNames = array();	
/**
 * Contains models to load and instantiate
 *
 * @var array
 */
	var $uses = array();
/**
 *  Constructs this Shell instance.
 *
 */
	function __construct(&$dispatch) {
		$this->Dispatch = & $dispatch;
		$vars = array('params', 'args', 'shell', 'shellName'=> 'name', 'shellClass'=> 'className', 'shellCommand'=> 'command');
		foreach($vars as $key => $var) {
			if(is_string($key)){
				$this->{$var} = & $this->Dispatch->{$key};
			} else {
				$this->{$var} = & $this->Dispatch->{$var};
			}
		}
		$this->_loadTasks();
	}
/**
 * Initializes the Shell
 * can be overriden in subclasses
 *
 * @return void
 */
	function initialize() {
		$this->_loadModels();
		$this->_welcome();
	}
/**
 * Displays a header for the shell
 *
 * @return void
 */
	function _welcome() {
		$this->hr();
		$this->out('App : '. APP_DIR);
		$this->out('Path: '. ROOT . DS . APP_DIR);
		$this->hr();
	}
/**
 * Loads database file and constructs DATABASE_CONFIG class
 * makes $this->dbConfig available to subclasses
 *
 * @return bool
 */
	function _loadDbConfig() {
		if(config('database')) {
			if (class_exists('DATABASE_CONFIG')) {
				$this->dbConfig = new DATABASE_CONFIG();
				return true;
			}
		}
		$this->err('Database config could not be loaded');
		$this->out('Run \'bake\' to create the database configuration');
		return false;
	}
/**
 * if var $uses = true
 * Loads AppModel file and constructs AppModel class
 * makes $this->AppModel available to subclasses
 * if var $uses is an array of models
 *
 * @return bool
 */
	function _loadModels() {

		if($this->uses === null || $this->uses === false) {
			return;
		}

		uses ('model'.DS.'connection_manager',
			'model'.DS.'datasources'.DS.'dbo_source', 'model'.DS.'model'
		);

		if($this->uses === true && loadModel()) {
			$this->AppModel = & new AppModel(false, false, false);
			return true;
		}

		if ($this->uses !== true && !empty($this->uses)) {
			$uses = is_array($this->uses) ? $this->uses : array($this->uses);
			$this->modelClass = $uses[0];

			foreach($uses as $modelClass) {
				$modelKey = Inflector::underscore($modelClass);

				if(!class_exists($modelClass)){
					loadModel($modelClass);
				}
				if(class_exists($modelClass)) {
					$model =& new $modelClass();
					$this->modelNames[] = $modelClass;
					$this->{$modelClass} =& $model;
					ClassRegistry::addObject($modelKey, $model);
				} else {
					return $this->cakeError('missingModel', array(array('className' => $modelClass)));
				}
			}
			return true;
		}
		return false;
	}
/**
 * Loads tasks defined in var $tasks
 *
 * @return bool
 */
	function _loadTasks() {
		if($this->tasks === null || $this->tasks === false) {
			return;
		}

		if ($this->tasks !== true && !empty($this->tasks)) {

			$tasks = $this->tasks;
			if(!is_array($tasks)) {
				$tasks = array($tasks);
			}

			$this->taskClass = $tasks[0];

			foreach($tasks as $taskName) {
				$taskKey = Inflector::underscore($taskName);

				$loaded = false;
				foreach($this->Dispatch->shellPaths as $path) {
					$taskPath = $path . 'tasks' . DS . Inflector::underscore($taskName).'.php';
					if (file_exists($taskPath)) {
						$loaded = true;
						break;
					}
				}

				if ($loaded) {
					require $taskPath;
					$taskClass = Inflector::camelize($taskName.'Task');
					if(class_exists($taskClass)) {
						$task =& new $taskClass($this->Dispatch);
						$this->taskNames[] = $taskName;
						$this->{$taskName} =& $task;
						ClassRegistry::addObject($taskKey, $task);
					} else{
						$this->err("Task '".$taskName."' could not be loaded");
						exit();
					}
				} else {
					$this->err("Task '".$taskName."' could not be found");
					exit();
				}
			}
		}

		return false;
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
		return $this->Dispatch->getInput($prompt, $options, $default);
	}
/**
 * Outputs to the stdout filehandle.
 *
 * @param string $string String to output.
 * @param boolean $newline If true, the outputs gets an added newline.
 */
	function out($string, $newline = true) {
		return $this->Dispatch->stdout($string, $newline);
	}
/**
 * Outputs to the stderr filehandle.
 *
 * @param string $string Error text to output.
 */
	function err($string) {
		return $this->Dispatch->stderr($string."\n");
	}
/**
 * Outputs a series of minus characters to the standard output, acts as a visual separator.
 *
 */
	function hr($newline = false) {
		if ($newline) {
			$this->out("\n");
		}
		$this->out('---------------------------------------------------------------');
		if ($newline) {
			$this->out("\n");
		}
	}
/**
 * Displays a formatted error message
 *
 * @param unknown_type $title
 * @param unknown_type $msg
 */
	function displayError($title, $msg) {
		$out = "\n";
		$out .= "Error: $title\n";
		$out .= "$msg\n";
		$out .= "\n";
		$this->out($out);
		exit();
	}
/**
 * Will check the number args matches otherwise throw an error
 *
 * @param unknown_type $expectedNum
 * @param unknown_type $command
 */
	function _checkArgs($expectedNum, $command = null) {
		if(!$command) {
			$command = $this->command;
		}
		if (count($this->args) < $expectedNum) {
			$this->displayError('Wrong number of parameters: '.count($this->args), 'Please type \'cake '.$this->Dispatch->shell.' help\' for help on usage of the '.$command.' command.');
		}
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
			$this->out(__("File exists, overwrite?", true). " {$path} (y/n/q):");
			$key = trim(fgets($this->stdin));

			if ($key == 'q') {
				$this->out(__("Quitting.", true) ."\n");
				exit;
			} elseif ($key == 'a') {
				$this->dont_ask = true;
			} elseif ($key == 'y') {
			} else {
				$this->out(__("Skip", true) ." {$path}\n");
				return false;
			}
		}

		uses('file');
		if ($File = new File($path, true)) {
			$File->write($contents);
			$this->out(__("Wrote", true) ."{$path}\n");
			return true;
		} else {
			$this->err(__("Error! Could not write to", true)." {$path}.\n");
			return false;
		}
	}


/**
 * Outputs usage text on the standard output. Implement it in subclasses.
 *
 */
	function help() {
		if($this->command != null) {
			$this->err("Unknown {$this->name} command '$this->command'.\nFor usage, try 'cake {$this->shell} help'.\n\n");
		} else{
			$this->Dispatch->help();
		}
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
}

?>