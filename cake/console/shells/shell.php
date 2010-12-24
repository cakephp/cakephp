<?php
/**
 * Base class for Shells
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
 * @package       cake.console.shells
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
require_once CONSOLE_LIBS . 'task_collection.php';
require_once CONSOLE_LIBS . 'console_output.php';
require_once CONSOLE_LIBS . 'console_input.php';
require_once CONSOLE_LIBS . 'console_option_parser.php';

/**
 * Base class for command-line utilities for automating programmer chores.
 *
 * @package       cake.console.shells
 */
class Shell extends Object {

/**
 * Output constants for making verbose and quiet shells.
 */
	const VERBOSE = 2;
	const NORMAL = 1;
	const QUIET = 0;

/**
 * An instance of ConsoleOptionParser that has been configured for this class.
 *
 * @var ConsoleOptionParser
 */
	public $OptionParser;

/**
 * If true, the script will ask for permission to perform actions.
 *
 * @var boolean
 * @access public
 */
	public $interactive = true;

/**
 * Contains command switches parsed from the command line.
 *
 * @var array
 * @access public
 */
	public $params = array();

/**
 * The command (method/task) that is being run.
 *
 * @var string
 */
	public $command;

/**
 * Contains arguments parsed from the command line.
 *
 * @var array
 * @access public
 */
	public $args = array();

/**
 * The name of the shell in camelized.
 *
 * @var string
 * @access public
 */
	public $name = null;

/**
 * Contains tasks to load and instantiate
 *
 * @var array
 * @access public
 */
	public $tasks = array();

/**
 * Contains the loaded tasks
 *
 * @var array
 * @access public
 */
	public $taskNames = array();

/**
 * Contains models to load and instantiate
 *
 * @var array
 * @access public
 */
	public $uses = array();

/**
 * Task Collection for the command, used to create Tasks.
 *
 * @var TaskCollection
 */
	public $Tasks;

/**
 * Normalized map of tasks.
 *
 * @var string
 */
	protected $_taskMap = array();

/**
 * stdout object.
 *
 * @var ConsoleOutput
 */
	public $stdout;

/**
 * stderr object.
 *
 * @var ConsoleOutput
 */
	public $stderr;

/**
 * stdin object
 *
 * @var ConsoleInput
 */
	public $stdin;

/**
 *  Constructs this Shell instance.
 *
 */
	function __construct($stdout = null, $stderr = null, $stdin = null) {
		if ($this->name == null) {
			$this->name = Inflector::underscore(str_replace(array('Shell', 'Task'), '', get_class($this)));
		}
		$this->Tasks = new TaskCollection($this);

		$this->stdout = $stdout;
		$this->stderr = $stderr;
		$this->stdin = $stdin;
		if ($this->stdout == null) {
			$this->stdout = new ConsoleOutput('php://stdout');
		}
		if ($this->stderr == null) {
			$this->stderr = new ConsoleOutput('php://stderr');
		}
		if ($this->stdin == null) {
			$this->stdin = new ConsoleInput('php://stdin');
		}
		
		$parent = get_parent_class($this);
		if ($this->tasks !== null && $this->tasks !== false) {
			$this->_mergeVars(array('tasks'), $parent, true);
		}
		if ($this->uses !== null && $this->uses !== false) {
			$this->_mergeVars(array('uses'), $parent, false);
		}
	}

/**
 * Initializes the Shell
 * acts as constructor for subclasses
 * allows configuration of tasks prior to shell execution
 *
 */
	public function initialize() {
		$this->_loadModels();
	}

/**
 * Starts up the Shell
 * allows for checking and configuring prior to command or main execution
 * can be overriden in subclasses
 *
 */
	public function startup() {
		$this->_welcome();
	}

/**
 * Displays a header for the shell
 *
 */
	protected function _welcome() {
		$this->clear();
		$this->out();
		$this->out('<info>Welcome to CakePHP v' . Configure::version() . ' Console</info>');
		$this->hr();
		$this->out('App : '. APP_DIR);
		$this->out('Path: '. APP_PATH);
		$this->hr();
	}

/**
 * if public $uses = true
 * Loads AppModel file and constructs AppModel class
 * makes $this->AppModel available to subclasses
 * if public $uses is an array of models will load those models
 *
 * @return bool
 */
	protected function _loadModels() {
		if ($this->uses === null || $this->uses === false) {
			return;
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
				$this->{$modelClass} = ClassRegistry::init($plugin . $modelClass);
			}
			return true;
		}
		return false;
	}

/**
 * Loads tasks defined in public $tasks
 *
 * @return bool
 */
	public function loadTasks() {
		if ($this->tasks === true || empty($this->tasks) || empty($this->Tasks)) {
			return true;
		}
		$this->_taskMap = TaskCollection::normalizeObjectArray((array)$this->tasks);
		foreach ($this->_taskMap as $task => $properties) {
			$this->taskNames[] = $task;
		}
		return true;
	}

/**
 * Check to see if this shell has a task with the provided name.
 *
 * @param string $task The task name to check.
 * @return boolean Success
 */
	public function hasTask($task) {
		return isset($this->_taskMap[Inflector::camelize($task)]);
	}

/**
 * Check to see if this shell has a callable method by the given name.
 *
 * @param string $name The method name to check.
 * @return boolean
 */
	public function hasMethod($name) {
		if (empty($this->_reflection)) {
			$this->_reflection = new ReflectionClass($this);
		}
		try {
			$method = $this->_reflection->getMethod($name);
			if (!$method->isPublic() || substr($name, 0, 1) === '_') {
				return false;
			}
			if ($method->getDeclaringClass() != $this->_reflection) {
				return false;
			}
			return true;
		} catch (ReflectionException $e) {
			return false;
		}
	}

/**
 * Dispatch a command to another Shell. Similar to Object::requestAction()
 * but intended for running shells from other shells.
 *
 * ### Usage:
 * 
 * With a string commmand:
 *
 *	`return $this->dispatchShell('schema create DbAcl');`
 *
 * With an array command:
 *
 * `return $this->dispatchShell('schema', 'create', 'i18n', '--dry');` 
 *
 * @param mixed $command Either an array of args similar to $argv. Or a string command, that can be 
 *   exploded on space to simulate argv.
 * @return mixed. The return of the other shell.
 */
	public function dispatchShell() {
		$args = func_get_args();
		if (is_string($args[0]) && count($args) == 1) {
			$args = explode(' ', $args[0]);
		}

		$Dispatcher = new ShellDispatcher($args, false);
		return $Dispatcher->dispatch();
	}

/**
 * Runs the Shell with the provided argv
 *
 * @param array $argv Array of arguments to run the shell with. This array should be missing the shell name.
 * @return void
 */
	public function runCommand($command, $argv) {
		$isTask = $this->hasTask($command);
		$isMethod = $this->hasMethod($command);
		$isMain = $this->hasMethod('main');

		if ($isTask || $isMethod && $command !== 'execute') {
			array_shift($argv);
		}

		$this->OptionParser = $this->getOptionParser();
		list($this->params, $this->args) = $this->OptionParser->parse($argv, $command);
		$this->command = $command;

		if (!empty($this->params['help'])) {
			return $this->_displayHelp($command);
		}

		if (($isTask || $isMethod || $isMain) && $command !== 'execute' ) {
			$this->startup();
		}

		if ($isTask) {
			$command = Inflector::camelize($command);
			return $this->{$command}->runCommand('execute', $argv);
		}
		if ($isMethod) {
			return $this->{$command}();
		}
		if ($isMain) {
			return $this->main();
		}
		return $this->out($this->OptionParser->help($command));
	}

/**
 * Display the help in the correct format
 *
 * @return void
 */
	protected function _displayHelp($command) {
		$format = 'text';
		if (!empty($this->args[0]) && $this->args[0] == 'xml')  {
			$format = 'xml';
			$this->output->outputAs(ConsoleOutput::RAW);
		} else {
			$this->_welcome();
		}
		return $this->out($this->OptionParser->help($command, $format));
	}

/**
 * Gets the option parser instance and configures it.
 * By overriding this method you can configure the ConsoleOptionParser before returning it.
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = new ConsoleOptionParser($this->name);
		return $parser;
	}

/**
 * Overload get for lazy building of tasks
 *
 * @return void
 */
	public function __get($name) {
		if (empty($this->{$name}) && in_array($name, $this->taskNames)) {
			$properties = $this->_taskMap[$name];
			$this->{$name} = $this->Tasks->load($properties['class'], $properties['settings']);
			$this->{$name}->args =& $this->args;
			$this->{$name}->params =& $this->params;
			$this->{$name}->initialize();
			$this->{$name}->loadTasks();
		}
		return $this->{$name};
	}

/**
 * Prompts the user for input, and returns it.
 *
 * @param string $prompt Prompt text.
 * @param mixed $options Array or string of options.
 * @param string $default Default input value.
 * @return Either the default value, or the user-provided input.
 */
	public function in($prompt, $options = null, $default = null) {
		if (!$this->interactive) {
			return $default;
		}
		$in = $this->_getInput($prompt, $options, $default);

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
				$in = $this->_getInput($prompt, $options, $default);
			}
		}
		if ($in) {
			return $in;
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
	protected function _getInput($prompt, $options, $default) {
		if (!is_array($options)) {
			$printOptions = '';
		} else {
			$printOptions = '(' . implode('/', $options) . ')';
		}

		if ($default === null) {
			$this->stdout->write('<question>' . $prompt . '</question>' . " $printOptions \n" . '> ', 0);
		} else {
			$this->stdout->write('<question>' . $prompt . '</question>' . " $printOptions \n" . "[$default] > ", 0);
		}
		$result = $this->stdin->read();

		if ($result === false) {
			$this->_stop(1);
		}
		$result = trim($result);

		if ($default != null && empty($result)) {
			return $default;
		}
		return $result;
	}

/**
 * Wrap a block of text.
 * Allows you to set the width, and indenting on a block of text. 
 *
 * ### Options
 *
 * - `width` The width to wrap to.  Defaults to 72
 * - `wordWrap` Only wrap on words breaks (spaces) Defaults to true.
 * - `indent` Indent the text with the string provided. Defaults to null.
 *
 * @param string $text Text the text to format.
 * @param mixed $options Array of options to use, or an integer to wrap the text to. 
 * @return string Wrapped / indented text
 * @see String::wrap()
 */
	public function wrapText($text, $options = array()) {
		return String::wrap($text, $options);
	}

/**
 * Outputs a single or multiple messages to stdout. If no parameters
 * are passed outputs just a newline.
 *
 * ### Output levels
 *
 * There are 3 built-in output level.  Shell::QUIET, Shell::NORMAL, Shell::VERBOSE.
 * The verbose and quiet output levels, map to the `verbose` and `quiet` output switches
 * present in  most shells.  Using Shell::QUIET for a message means it will always display.
 * While using Shell::VERBOSE means it will only display when verbose output is toggled.
 *
 * @param mixed $message A string or a an array of strings to output
 * @param integer $newlines Number of newlines to append
 * @param integer $level The message's output level, see above.
 * @return integer Returns the number of bytes returned from writing to stdout.
 */
	public function out($message = null, $newlines = 1, $level = Shell::NORMAL) {
		$currentLevel = Shell::NORMAL;
		if (!empty($this->params['verbose'])) {
			$currentLevel = Shell::VERBOSE;
		}
		if (!empty($this->params['quiet'])) {
			$currentLevel = Shell::QUIET;
		}
		if ($level <= $currentLevel) {
			return $this->stdout->write($message, $newlines);
		}
		return true;
	}

/**
 * Outputs a single or multiple error messages to stderr. If no parameters
 * are passed outputs just a newline.
 *
 * @param mixed $message A string or a an array of strings to output
 * @param integer $newlines Number of newlines to append
 */
	public function err($message = null, $newlines = 1) {
		$this->stderr->write($message, $newlines);
	}

/**
 * Returns a single or multiple linefeeds sequences.
 *
 * @param integer $multiplier Number of times the linefeed sequence should be repeated
 * @access public
 * @return string
 */
	function nl($multiplier = 1) {
		return str_repeat(ConsoleOutput::LF, $multiplier);
	}

/**
 * Outputs a series of minus characters to the standard output, acts as a visual separator.
 *
 * @param integer $newlines Number of newlines to pre- and append
 * @param integer $width Width of the line, defaults to 63
 */
	public function hr($newlines = 0, $width = 63) {
		$this->out(null, $newlines);
		$this->out(str_repeat('-', $width));
		$this->out(null, $newlines);
	}

/**
 * Displays a formatted error message
 * and exits the application with status code 1
 *
 * @param string $title Title of the error
 * @param string $message An optional error message
 */
	public function error($title, $message = null) {
		$this->err(__('<error>Error:</error> %s', $title));

		if (!empty($message)) {
			$this->err($message);
		}
		$this->_stop(1);
	}

/**
 * Clear the console
 *
 * @return void
 */
	public function clear() {
		if (empty($this->params['noclear'])) {
			if ( DS === '/') {
				passthru('clear');
			} else {
				passthru('cls');
			}
		}
	}

/**
 * Creates a file at given path
 *
 * @param string $path Where to put the file.
 * @param string $contents Content to put in the file.
 * @return boolean Success
 */
	public function createFile($path, $contents) {
		$path = str_replace(DS . DS, DS, $path);

		$this->out();

		if (is_file($path) && $this->interactive === true) {
			$this->out(__('<warning>File `%s` exists</warning>', $path));
			$key = $this->in(__('Do you want to overwrite?'),  array('y', 'n', 'q'), 'n');

			if (strtolower($key) == 'q') {
				$this->out(__('<error>Quitting</error>.'), 2);
				$this->_stop();
			} elseif (strtolower($key) != 'y') {
				$this->out(__('Skip `%s`', $path), 2);
				return false;
			}
		} else {
			$this->out(__('Creating file %s', $path));
		}

		if (!class_exists('File')) {
			require LIBS . 'file.php';
		}

		if ($File = new File($path, true)) {
			$data = $File->prepare($contents);
			$File->write($data);
			$this->out(__('<success>Wrote</success> `%s`', $path));
			return true;
		} else {
			$this->err(__('<error>Could not write to `%s`</error>.', $path), 2);
			return false;
		}
	}

/**
 * Action to create a Unit Test
 *
 * @return boolean Success
 */
	protected function _checkUnitTest() {
		if (App::import('vendor', 'simpletest' . DS . 'simpletest')) {
			return true;
		}
		$prompt = 'PHPUnit is not installed. Do you want to bake unit test files anyway?';
		$unitTest = $this->in($prompt, array('y','n'), 'y');
		$result = strtolower($unitTest) == 'y' || strtolower($unitTest) == 'yes';

		if ($result) {
			$this->out();
			$this->out('You can download PHPUnit from http://phpunit.de');
		}
		return $result;
	}

/**
 * Makes absolute file path easier to read
 *
 * @param string $file Absolute file path
 * @return sting short path
 */
	public function shortPath($file) {
		$shortPath = str_replace(ROOT, null, $file);
		$shortPath = str_replace('..' . DS, '', $shortPath);
		return str_replace(DS . DS, DS, $shortPath);
	}

/**
 * Creates the proper controller path for the specified controller class name
 *
 * @param string $name Controller class name
 * @return string Path to controller
 */
	protected function _controllerPath($name) {
		return strtolower(Inflector::underscore($name));
	}

/**
 * Creates the proper controller plural name for the specified controller class name
 *
 * @param string $name Controller class name
 * @return string Controller plural name
 */
	protected function _controllerName($name) {
		return Inflector::pluralize(Inflector::camelize($name));
	}

/**
 * Creates the proper controller camelized name (singularized) for the specified name
 *
 * @param string $name Name
 * @return string Camelized and singularized controller name
 */
	protected function _modelName($name) {
		return Inflector::camelize(Inflector::singularize($name));
	}

/**
 * Creates the proper underscored model key for associations
 *
 * @param string $name Model class name
 * @return string Singular model key
 */
	protected function _modelKey($name) {
		return Inflector::underscore($name) . '_id';
	}

/**
 * Creates the proper model name from a foreign key
 *
 * @param string $key Foreign key
 * @return string Model name
 */
	protected function _modelNameFromKey($key) {
		return Inflector::camelize(str_replace('_id', '', $key));
	}

/**
 * creates the singular name for use in views.
 *
 * @param string $name
 * @return string $name
 */
	protected function _singularName($name) {
		return Inflector::variable(Inflector::singularize($name));
	}

/**
 * Creates the plural name for views
 *
 * @param string $name Name to use
 * @return string Plural name for views
 */
	protected function _pluralName($name) {
		return Inflector::variable(Inflector::pluralize($name));
	}

/**
 * Creates the singular human name used in views
 *
 * @param string $name Controller name
 * @return string Singular human name
 */
	protected function _singularHumanName($name) {
		return Inflector::humanize(Inflector::underscore(Inflector::singularize($name)));
	}

/**
 * Creates the plural human name used in views
 *
 * @param string $name Controller name
 * @return string Plural human name
 */
	protected function _pluralHumanName($name) {
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
