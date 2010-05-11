<?php
/**
 * Base class for Shells
 *
 * PHP versions 4 and 5
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
 * @subpackage    cake.cake.console.libs
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Base class for command-line utilities for automating programmer chores.
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs
 */
class Shell extends Object {

/**
 * An instance of the ShellDispatcher object that loaded this script
 *
 * @var ShellDispatcher
 * @access public
 */
	var $Dispatch = null;

/**
 * If true, the script will ask for permission to perform actions.
 *
 * @var boolean
 * @access public
 */
	var $interactive = true;

/**
 * Holds the DATABASE_CONFIG object for the app. Null if database.php could not be found,
 * or the app does not exist.
 *
 * @var DATABASE_CONFIG
 * @access public
 */
	var $DbConfig = null;

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
	var $className = null;

/**
 * The command called if public methods are available.
 *
 * @var string
 * @access public
 */
	var $command = null;

/**
 * The name of the shell in camelized.
 *
 * @var string
 * @access public
 */
	var $name = null;

/**
 * An alias for the shell
 *
 * @var string
 * @access public
 */
	var $alias = null;

/**
 * Contains tasks to load and instantiate
 *
 * @var array
 * @access public
 */
	var $tasks = array();

/**
 * Contains the loaded tasks
 *
 * @var array
 * @access public
 */
	var $taskNames = array();

/**
 * Contains models to load and instantiate
 *
 * @var array
 * @access public
 */
	var $uses = array();

/**
 *  Constructs this Shell instance.
 *
 */
	function __construct(&$dispatch) {
		$vars = array('params', 'args', 'shell', 'shellCommand' => 'command');

		foreach ($vars as $key => $var) {
			if (is_string($key)) {
				$this->{$var} =& $dispatch->{$key};
			} else {
				$this->{$var} =& $dispatch->{$var};
			}
		}

		if ($this->name == null) {
			$this->name = get_class($this);
		}

		if ($this->alias == null) {
			$this->alias = $this->name;
		}

		ClassRegistry::addObject($this->name, $this);
		ClassRegistry::map($this->name, $this->alias);

		if (!PHP5 && isset($this->args[0])) {
			if (strpos($this->name, strtolower(Inflector::camelize($this->args[0]))) !== false) {
				$dispatch->shiftArgs();
			}
			if (strtolower($this->command) == strtolower(Inflector::variable($this->args[0])) && method_exists($this, $this->command)) {
				$dispatch->shiftArgs();
			}
		}

		$this->Dispatch =& $dispatch;
	}

/**
 * Initializes the Shell
 * acts as constructor for subclasses
 * allows configuration of tasks prior to shell execution
 *
 * @access public
 */
	function initialize() {
		$this->_loadModels();
	}

/**
 * Starts up the the Shell
 * allows for checking and configuring prior to command or main execution
 * can be overriden in subclasses
 *
 * @access public
 */
	function startup() {
		$this->_welcome();
	}

/**
 * Displays a header for the shell
 *
 * @access protected
 */
	function _welcome() {
		$this->Dispatch->clear();
		$this->out();
		$this->out('Welcome to CakePHP v' . Configure::version() . ' Console');
		$this->hr();
		$this->out('App : '. $this->params['app']);
		$this->out('Path: '. $this->params['working']);
		$this->hr();
	}

/**
 * Loads database file and constructs DATABASE_CONFIG class
 * makes $this->DbConfig available to subclasses
 *
 * @return bool
 * @access protected
 */
	function _loadDbConfig() {
		if (config('database') && class_exists('DATABASE_CONFIG')) {
			$this->DbConfig =& new DATABASE_CONFIG();
			return true;
		}
		$this->err('Database config could not be loaded.');
		$this->out('Run `bake` to create the database configuration.');
		return false;
	}

/**
 * if var $uses = true
 * Loads AppModel file and constructs AppModel class
 * makes $this->AppModel available to subclasses
 * if var $uses is an array of models will load those models
 *
 * @return bool
 * @access protected
 */
	function _loadModels() {
		if ($this->uses === null || $this->uses === false) {
			return;
		}

		if ($this->uses === true && App::import('Model', 'AppModel')) {
			$this->AppModel =& new AppModel(false, false, false);
			return true;
		}

		if ($this->uses !== true && !empty($this->uses)) {
			$uses = is_array($this->uses) ? $this->uses : array($this->uses);

			$modelClassName = $uses[0];
			if (strpos($uses[0], '.') !== false) {
				list($plugin, $modelClassName) = explode('.', $uses[0]);
			}
			$this->modelClass = $modelClassName;

			foreach ($uses as $modelClass) {
				list($plugin, $modelClass) = pluginSplit($modelClass, true);
				if (PHP5) {
					$this->{$modelClass} = ClassRegistry::init($plugin . $modelClass);
				} else {
					$this->{$modelClass} =& ClassRegistry::init($plugin . $modelClass);
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
 * @access public
 */
	function loadTasks() {
		if ($this->tasks === null || $this->tasks === false || $this->tasks === true || empty($this->tasks)) {
			return true;
		}

		$tasks = $this->tasks;
		if (!is_array($tasks)) {
			$tasks = array($tasks);
		}

		foreach ($tasks as $taskName) {
			$task = Inflector::underscore($taskName);
			$taskClass = Inflector::camelize($taskName . 'Task');

			if (!class_exists($taskClass)) {
				foreach ($this->Dispatch->shellPaths as $path) {
					$taskPath = $path . 'tasks' . DS . $task . '.php';
					if (file_exists($taskPath)) {
						require_once $taskPath;
						break;
					}
				}
			}
			$taskClassCheck = $taskClass;
			if (!PHP5) {
				$taskClassCheck = strtolower($taskClass);
			}
			if (ClassRegistry::isKeySet($taskClassCheck)) {
				$this->taskNames[] = $taskName;
				if (!PHP5) {
					$this->{$taskName} =& ClassRegistry::getObject($taskClassCheck);
				} else {
					$this->{$taskName} = ClassRegistry::getObject($taskClassCheck);
				}
			} else {
				$this->taskNames[] = $taskName;
				if (!PHP5) {
					$this->{$taskName} =& new $taskClass($this->Dispatch);
				} else {
					$this->{$taskName} = new $taskClass($this->Dispatch);
				}
			}

			if (!isset($this->{$taskName})) {
				$this->err("Task `{$taskName}` could not be loaded");
				$this->_stop();
			}
		}

		return true;
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
	function in($prompt, $options = null, $default = null) {
		if (!$this->interactive) {
			return $default;
		}
		$in = $this->Dispatch->getInput($prompt, $options, $default);

		if ($options && is_string($options)) {
			if (strpos($options, ',')) {
				$options = explode(',', $options);
			} elseif (strpos($options, '/')) {
				$options = explode('/', $options);
			} else {
				$options = array($options);
			}
		}
		if (is_array($options)) {
			while ($in == '' || ($in && (!in_array(strtolower($in), $options) && !in_array(strtoupper($in), $options)) && !in_array($in, $options))) {
				$in = $this->Dispatch->getInput($prompt, $options, $default);
			}
		}
		if ($in) {
			return $in;
		}
	}

/**
 * Outputs a single or multiple messages to stdout. If no parameters
 * are passed outputs just a newline.
 *
 * @param mixed $message A string or a an array of strings to output
 * @param integer $newlines Number of newlines to append
 * @return integer Returns the number of bytes returned from writing to stdout.
 * @access public
 */
	function out($message = null, $newlines = 1) {
		if (is_array($message)) {
			$message = implode($this->nl(), $message);
		}
		return $this->Dispatch->stdout($message . $this->nl($newlines), false);
	}

/**
 * Outputs a single or multiple error messages to stderr. If no parameters
 * are passed outputs just a newline.
 *
 * @param mixed $message A string or a an array of strings to output
 * @param integer $newlines Number of newlines to append
 * @access public
 */
	function err($message = null, $newlines = 1) {
		if (is_array($message)) {
			$message = implode($this->nl(), $message);
		}
		$this->Dispatch->stderr($message . $this->nl($newlines));
	}

/**
 * Returns a single or multiple linefeeds sequences.
 *
 * @param integer $multiplier Number of times the linefeed sequence should be repeated
 * @access public
 * @return string
 */
	function nl($multiplier = 1) {
		return str_repeat("\n", $multiplier);
	}

/**
 * Outputs a series of minus characters to the standard output, acts as a visual separator.
 *
 * @param integer $newlines Number of newlines to pre- and append
 * @access public
 */
	function hr($newlines = 0) {
		$this->out(null, $newlines);
		$this->out('---------------------------------------------------------------');
		$this->out(null, $newlines);
	}

/**
 * Displays a formatted error message
 * and exits the application with status code 1
 *
 * @param string $title Title of the error
 * @param string $message An optional error message
 * @access public
 */
	function error($title, $message = null) {
		$this->err(sprintf(__('Error: %s', true), $title));

		if (!empty($message)) {
			$this->err($message);
		}
		$this->_stop(1);
	}

/**
 * Will check the number args matches otherwise throw an error
 *
 * @param integer $expectedNum Expected number of paramters
 * @param string $command Command
 * @access protected
 */
	function _checkArgs($expectedNum, $command = null) {
		if (!$command) {
			$command = $this->command;
		}
		if (count($this->args) < $expectedNum) {
			$message[] = "Got: " . count($this->args);
			$message[] = "Expected: {$expectedNum}";
			$message[] = "Please type `cake {$this->shell} help` for help";
			$message[] = "on usage of the {$this->name} {$command}.";
			$this->error('Wrong number of parameters', $message);
		}
	}

/**
 * Creates a file at given path
 *
 * @param string $path Where to put the file.
 * @param string $contents Content to put in the file.
 * @return boolean Success
 * @access public
 */
	function createFile($path, $contents) {
		$path = str_replace(DS . DS, DS, $path);

		$this->out();
		$this->out(sprintf(__("Creating file %s", true), $path));

		if (is_file($path) && $this->interactive === true) {
			$prompt = sprintf(__('File `%s` exists, overwrite?', true), $path);
			$key = $this->in($prompt,  array('y', 'n', 'q'), 'n');

			if (strtolower($key) == 'q') {
				$this->out(__('Quitting.', true), 2);
				$this->_stop();
			} elseif (strtolower($key) != 'y') {
				$this->out(sprintf(__('Skip `%s`', true), $path), 2);
				return false;
			}
		}
		if (!class_exists('File')) {
			require LIBS . 'file.php';
		}

		if ($File = new File($path, true)) {
			$data = $File->prepare($contents);
			$File->write($data);
			$this->out(sprintf(__('Wrote `%s`', true), $path));
			return true;
		} else {
			$this->err(sprintf(__('Could not write to `%s`.', true), $path), 2);
			return false;
		}
	}

/**
 * Outputs usage text on the standard output. Implement it in subclasses.
 *
 * @access public
 */
	function help() {
		if ($this->command != null) {
			$this->err("Unknown {$this->name} command `{$this->command}`.");
			$this->err("For usage, try `cake {$this->shell} help`.", 2);
		} else {
			$this->Dispatch->help();
		}
	}

/**
 * Action to create a Unit Test
 *
 * @return boolean Success
 * @access protected
 */
	function _checkUnitTest() {
		if (App::import('vendor', 'simpletest' . DS . 'simpletest')) {
			return true;
		}
		$prompt = 'SimpleTest is not installed. Do you want to bake unit test files anyway?';
		$unitTest = $this->in($prompt, array('y','n'), 'y');
		$result = strtolower($unitTest) == 'y' || strtolower($unitTest) == 'yes';

		if ($result) {
			$this->out();
			$this->out('You can download SimpleTest from http://simpletest.org');
		}
		return $result;
	}

/**
 * Makes absolute file path easier to read
 *
 * @param string $file Absolute file path
 * @return sting short path
 * @access public
 */
	function shortPath($file) {
		$shortPath = str_replace(ROOT, null, $file);
		$shortPath = str_replace('..' . DS, '', $shortPath);
		return str_replace(DS . DS, DS, $shortPath);
	}

/**
 * Creates the proper controller path for the specified controller class name
 *
 * @param string $name Controller class name
 * @return string Path to controller
 * @access protected
 */
	function _controllerPath($name) {
		return strtolower(Inflector::underscore($name));
	}

/**
 * Creates the proper controller plural name for the specified controller class name
 *
 * @param string $name Controller class name
 * @return string Controller plural name
 * @access protected
 */
	function _controllerName($name) {
		return Inflector::pluralize(Inflector::camelize($name));
	}

/**
 * Creates the proper controller camelized name (singularized) for the specified name
 *
 * @param string $name Name
 * @return string Camelized and singularized controller name
 * @access protected
 */
	function _modelName($name) {
		return Inflector::camelize(Inflector::singularize($name));
	}

/**
 * Creates the proper underscored model key for associations
 *
 * @param string $name Model class name
 * @return string Singular model key
 * @access protected
 */
	function _modelKey($name) {
		return Inflector::underscore($name) . '_id';
	}

/**
 * Creates the proper model name from a foreign key
 *
 * @param string $key Foreign key
 * @return string Model name
 * @access protected
 */
	function _modelNameFromKey($key) {
		return Inflector::camelize(str_replace('_id', '', $key));
	}

/**
 * creates the singular name for use in views.
 *
 * @param string $name
 * @return string $name
 * @access protected
 */
	function _singularName($name) {
		return Inflector::variable(Inflector::singularize($name));
	}

/**
 * Creates the plural name for views
 *
 * @param string $name Name to use
 * @return string Plural name for views
 * @access protected
 */
	function _pluralName($name) {
		return Inflector::variable(Inflector::pluralize($name));
	}

/**
 * Creates the singular human name used in views
 *
 * @param string $name Controller name
 * @return string Singular human name
 * @access protected
 */
	function _singularHumanName($name) {
		return Inflector::humanize(Inflector::underscore(Inflector::singularize($name)));
	}

/**
 * Creates the plural human name used in views
 *
 * @param string $name Controller name
 * @return string Plural human name
 * @access protected
 */
	function _pluralHumanName($name) {
		return Inflector::humanize(Inflector::underscore($name));
	}

/**
 * Find the correct path for a plugin. Scans $pluginPaths for the plugin you want.
 *
 * @param string $pluginName Name of the plugin you want ie. DebugKit
 * @return string $path path to the correct plugin.
 */
	function _pluginPath($pluginName) {
		return App::pluginPath($pluginName);
	}
}
