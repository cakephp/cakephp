<?php
/**
 * Base class for Shells
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('TaskCollection', 'Console');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('ConsoleInputSubcommand', 'Console');
App::uses('ConsoleOptionParser', 'Console');
App::uses('ClassRegistry', 'Utility');
App::uses('File', 'Utility');

/**
 * Base class for command-line utilities for automating programmer chores.
 *
 * @package       Cake.Console
 */
class Shell extends Object {

/**
 * Default error code
 *
 * @var int
 */
	const CODE_ERROR = 1;

/**
 * Output constant making verbose shells.
 *
 * @var int
 */
	const VERBOSE = 2;

/**
 * Output constant for making normal shells.
 *
 * @var int
 */
	const NORMAL = 1;

/**
 * Output constants for making quiet shells.
 *
 * @var int
 */
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
 * @var bool
 */
	public $interactive = true;

/**
 * Contains command switches parsed from the command line.
 *
 * @var array
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
 */
	public $args = array();

/**
 * The name of the shell in camelized.
 *
 * @var string
 */
	public $name = null;

/**
 * The name of the plugin the shell belongs to.
 * Is automatically set by ShellDispatcher when a shell is constructed.
 *
 * @var string
 */
	public $plugin = null;

/**
 * Contains tasks to load and instantiate
 *
 * @var array
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::$tasks
 */
	public $tasks = array();

/**
 * Contains the loaded tasks
 *
 * @var array
 */
	public $taskNames = array();

/**
 * Contains models to load and instantiate
 *
 * @var array
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::$uses
 */
	public $uses = array();

/**
 * This shell's primary model class name, the first model in the $uses property
 *
 * @var string
 */
	public $modelClass = null;

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
 * The number of bytes last written to the output stream
 * used when overwriting the previous message.
 *
 * @var int
 */
	protected $_lastWritten = 0;

/**
 * Contains helpers which have been previously instantiated
 *
 * @var array
 */
	protected $_helpers = array();

/**
 *  Constructs this Shell instance.
 *
 * @param ConsoleOutput $stdout A ConsoleOutput object for stdout.
 * @param ConsoleOutput $stderr A ConsoleOutput object for stderr.
 * @param ConsoleInput $stdin A ConsoleInput object for stdin.
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell
 */
	public function __construct($stdout = null, $stderr = null, $stdin = null) {
		if (!$this->name) {
			$this->name = Inflector::camelize(str_replace(array('Shell', 'Task'), '', get_class($this)));
		}
		$this->Tasks = new TaskCollection($this);

		$this->stdout = $stdout ? $stdout : new ConsoleOutput('php://stdout');
		$this->stderr = $stderr ? $stderr : new ConsoleOutput('php://stderr');
		$this->stdin = $stdin ? $stdin : new ConsoleInput('php://stdin');

		$this->_useLogger();
		$parent = get_parent_class($this);
		if ($this->tasks !== null && $this->tasks !== false) {
			$this->_mergeVars(array('tasks'), $parent, true);
		}
		if (!empty($this->uses)) {
			$this->_mergeVars(array('uses'), $parent, false);
		}
	}

/**
 * Initializes the Shell
 * acts as constructor for subclasses
 * allows configuration of tasks prior to shell execution
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::initialize
 */
	public function initialize() {
		$this->_loadModels();
		$this->loadTasks();
	}

/**
 * Starts up the Shell and displays the welcome message.
 * Allows for checking and configuring prior to command or main execution
 *
 * Override this method if you want to remove the welcome information,
 * or otherwise modify the pre-command flow.
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::startup
 */
	public function startup() {
		$this->_welcome();
	}

/**
 * Displays a header for the shell
 *
 * @return void
 */
	protected function _welcome() {
		$this->out();
		$this->out(__d('cake_console', '<info>Welcome to CakePHP %s Console</info>', 'v' . Configure::version()));
		$this->hr();
		$this->out(__d('cake_console', 'App : %s', APP_DIR));
		$this->out(__d('cake_console', 'Path: %s', APP));
		$this->hr();
	}

/**
 * If $uses is an array load each of the models in the array
 *
 * @return bool
 */
	protected function _loadModels() {
		if (is_array($this->uses)) {
			list(, $this->modelClass) = pluginSplit(current($this->uses));
			foreach ($this->uses as $modelClass) {
				$this->loadModel($modelClass);
			}
		}
		return true;
	}

/**
 * Lazy loads models using the loadModel() method if declared in $uses
 *
 * @param string $name The name of the model to look for.
 * @return void
 */
	public function __isset($name) {
		if (is_array($this->uses)) {
			foreach ($this->uses as $modelClass) {
				list(, $class) = pluginSplit($modelClass);
				if ($name === $class) {
					return $this->loadModel($modelClass);
				}
			}
		}
	}

/**
 * Loads and instantiates models required by this shell.
 *
 * @param string $modelClass Name of model class to load
 * @param mixed $id Initial ID the instanced model class should have
 * @return mixed true when single model found and instance created, error returned if model not found.
 * @throws MissingModelException if the model class cannot be found.
 */
	public function loadModel($modelClass = null, $id = null) {
		if ($modelClass === null) {
			$modelClass = $this->modelClass;
		}

		$this->uses = ($this->uses) ? (array)$this->uses : array();
		if (!in_array($modelClass, $this->uses)) {
			$this->uses[] = $modelClass;
		}

		list($plugin, $modelClass) = pluginSplit($modelClass, true);
		if (!isset($this->modelClass)) {
			$this->modelClass = $modelClass;
		}

		$this->{$modelClass} = ClassRegistry::init(array(
			'class' => $plugin . $modelClass, 'alias' => $modelClass, 'id' => $id
		));
		if (!$this->{$modelClass}) {
			throw new MissingModelException($modelClass);
		}
		return true;
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
		$this->taskNames = array_merge($this->taskNames, array_keys($this->_taskMap));
		return true;
	}

/**
 * Check to see if this shell has a task with the provided name.
 *
 * @param string $task The task name to check.
 * @return bool Success
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::hasTask
 */
	public function hasTask($task) {
		return isset($this->_taskMap[Inflector::camelize($task)]);
	}

/**
 * Check to see if this shell has a callable method by the given name.
 *
 * @param string $name The method name to check.
 * @return bool
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::hasMethod
 */
	public function hasMethod($name) {
		try {
			$method = new ReflectionMethod($this, $name);
			if (!$method->isPublic() || substr($name, 0, 1) === '_') {
				return false;
			}
			if ($method->getDeclaringClass()->name === 'Shell') {
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
 * With a string command:
 *
 *	`return $this->dispatchShell('schema create DbAcl');`
 *
 * Avoid using this form if you have string arguments, with spaces in them.
 * The dispatched will be invoked incorrectly. Only use this form for simple
 * command dispatching.
 *
 * With an array command:
 *
 * `return $this->dispatchShell('schema', 'create', 'i18n', '--dry');`
 *
 * @return mixed The return of the other shell.
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::dispatchShell
 */
	public function dispatchShell() {
		$args = func_get_args();
		if (is_string($args[0]) && count($args) === 1) {
			$args = explode(' ', $args[0]);
		}

		$Dispatcher = new ShellDispatcher($args, false);
		return $Dispatcher->dispatch();
	}

/**
 * Runs the Shell with the provided argv.
 *
 * Delegates calls to Tasks and resolves methods inside the class. Commands are looked
 * up with the following order:
 *
 * - Method on the shell.
 * - Matching task name.
 * - `main()` method.
 *
 * If a shell implements a `main()` method, all missing method calls will be sent to
 * `main()` with the original method name in the argv.
 *
 * @param string $command The command name to run on this shell. If this argument is empty,
 *   and the shell has a `main()` method, that will be called instead.
 * @param array $argv Array of arguments to run the shell with. This array should be missing the shell name.
 * @return void
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::runCommand
 */
	public function runCommand($command, $argv) {
		$isTask = $this->hasTask($command);
		$isMethod = $this->hasMethod($command);
		$isMain = $this->hasMethod('main');

		if ($isTask || $isMethod && $command !== 'execute') {
			array_shift($argv);
		}

		$this->OptionParser = $this->getOptionParser();
		try {
			list($this->params, $this->args) = $this->OptionParser->parse($argv, $command);
		} catch (ConsoleException $e) {
			$this->out($this->OptionParser->help($command));
			return false;
		}

		if (!empty($this->params['quiet'])) {
			$this->_useLogger(false);
		}
		if (!empty($this->params['plugin'])) {
			CakePlugin::load($this->params['plugin']);
		}
		$this->command = $command;
		if (!empty($this->params['help'])) {
			return $this->_displayHelp($command);
		}

		if (($isTask || $isMethod || $isMain) && $command !== 'execute') {
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
		$this->out($this->OptionParser->help($command));
		return false;
	}

/**
 * Display the help in the correct format
 *
 * @param string $command The command to get help for.
 * @return void
 */
	protected function _displayHelp($command) {
		$format = 'text';
		if (!empty($this->args[0]) && $this->args[0] === 'xml') {
			$format = 'xml';
			$this->stdout->outputAs(ConsoleOutput::RAW);
		} else {
			$this->_welcome();
		}
		return $this->out($this->OptionParser->help($command, $format));
	}

/**
 * Gets the option parser instance and configures it.
 *
 * By overriding this method you can configure the ConsoleOptionParser before returning it.
 *
 * @return ConsoleOptionParser
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::getOptionParser
 */
	public function getOptionParser() {
		$name = ($this->plugin ? $this->plugin . '.' : '') . $this->name;
		$parser = new ConsoleOptionParser($name);
		return $parser;
	}

/**
 * Overload get for lazy building of tasks
 *
 * @param string $name The property name to access.
 * @return Shell Object of Task
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
 * Safely access the values in $this->params.
 *
 * @param string $name The name of the parameter to get.
 * @return string|bool|null Value. Will return null if it doesn't exist.
 */
	public function param($name) {
		if (!isset($this->params[$name])) {
			return null;
		}
		return $this->params[$name];
	}

/**
 * Prompts the user for input, and returns it.
 *
 * @param string $prompt Prompt text.
 * @param string|array $options Array or string of options.
 * @param string $default Default input value.
 * @return mixed Either the default value, or the user-provided input.
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::in
 */
	public function in($prompt, $options = null, $default = null) {
		if (!$this->interactive) {
			return $default;
		}
		$originalOptions = $options;
		$in = $this->_getInput($prompt, $originalOptions, $default);

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
			$options = array_merge(
				array_map('strtolower', $options),
				array_map('strtoupper', $options),
				$options
			);
			while ($in === '' || !in_array($in, $options)) {
				$in = $this->_getInput($prompt, $originalOptions, $default);
			}
		}
		return $in;
	}

/**
 * Prompts the user for input, and returns it.
 *
 * @param string $prompt Prompt text.
 * @param string|array $options Array or string of options.
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
			$this->_stop(self::CODE_ERROR);
			return self::CODE_ERROR;
		}
		$result = trim($result);

		if ($default !== null && ($result === '' || $result === null)) {
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
 * - `width` The width to wrap to. Defaults to 72
 * - `wordWrap` Only wrap on words breaks (spaces) Defaults to true.
 * - `indent` Indent the text with the string provided. Defaults to null.
 *
 * @param string $text Text the text to format.
 * @param string|int|array $options Array of options to use, or an integer to wrap the text to.
 * @return string Wrapped / indented text
 * @see CakeText::wrap()
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::wrapText
 */
	public function wrapText($text, $options = array()) {
		return CakeText::wrap($text, $options);
	}

/**
 * Outputs a single or multiple messages to stdout. If no parameters
 * are passed outputs just a newline.
 *
 * ### Output levels
 *
 * There are 3 built-in output level. Shell::QUIET, Shell::NORMAL, Shell::VERBOSE.
 * The verbose and quiet output levels, map to the `verbose` and `quiet` output switches
 * present in most shells. Using Shell::QUIET for a message means it will always display.
 * While using Shell::VERBOSE means it will only display when verbose output is toggled.
 *
 * @param string|array $message A string or an array of strings to output
 * @param int $newlines Number of newlines to append
 * @param int $level The message's output level, see above.
 * @return int|bool Returns the number of bytes returned from writing to stdout.
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::out
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
			$this->_lastWritten = $this->stdout->write($message, $newlines);
			return $this->_lastWritten;
		}
		return true;
	}

/**
 * Overwrite some already output text.
 *
 * Useful for building progress bars, or when you want to replace
 * text already output to the screen with new text.
 *
 * **Warning** You cannot overwrite text that contains newlines.
 *
 * @param array|string $message The message to output.
 * @param int $newlines Number of newlines to append.
 * @param int $size The number of bytes to overwrite. Defaults to the length of the last message output.
 * @return int|bool Returns the number of bytes returned from writing to stdout.
 */
	public function overwrite($message, $newlines = 1, $size = null) {
		$size = $size ? $size : $this->_lastWritten;

		// Output backspaces.
		$this->out(str_repeat("\x08", $size), 0);

		$newBytes = $this->out($message, 0);

		// Fill any remaining bytes with spaces.
		$fill = $size - $newBytes;
		if ($fill > 0) {
			$this->out(str_repeat(' ', $fill), 0);
		}
		if ($newlines) {
			$this->out($this->nl($newlines), 0);
		}
	}

/**
 * Outputs a single or multiple error messages to stderr. If no parameters
 * are passed outputs just a newline.
 *
 * @param string|array $message A string or an array of strings to output
 * @param int $newlines Number of newlines to append
 * @return void
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::err
 */
	public function err($message = null, $newlines = 1) {
		$this->stderr->write($message, $newlines);
	}

/**
 * Returns a single or multiple linefeeds sequences.
 *
 * @param int $multiplier Number of times the linefeed sequence should be repeated
 * @return string
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::nl
 */
	public function nl($multiplier = 1) {
		return str_repeat(ConsoleOutput::LF, $multiplier);
	}

/**
 * Outputs a series of minus characters to the standard output, acts as a visual separator.
 *
 * @param int $newlines Number of newlines to pre- and append
 * @param int $width Width of the line, defaults to 63
 * @return void
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::hr
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
 * @return void
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::error
 */
	public function error($title, $message = null) {
		$this->err(__d('cake_console', '<error>Error:</error> %s', $title));

		if (!empty($message)) {
			$this->err($message);
		}
		$this->_stop(self::CODE_ERROR);
		return self::CODE_ERROR;
	}

/**
 * Clear the console
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::clear
 */
	public function clear() {
		if (empty($this->params['noclear'])) {
			if (DS === '/') {
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
 * @return bool Success
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::createFile
 */
	public function createFile($path, $contents) {
		$path = str_replace(DS . DS, DS, $path);

		$this->out();

		if (is_file($path) && empty($this->params['force']) && $this->interactive === true) {
			$this->out(__d('cake_console', '<warning>File `%s` exists</warning>', $path));
			$key = $this->in(__d('cake_console', 'Do you want to overwrite?'), array('y', 'n', 'q'), 'n');

			if (strtolower($key) === 'q') {
				$this->out(__d('cake_console', '<error>Quitting</error>.'), 2);
				$this->_stop();
				return true;
			} elseif (strtolower($key) !== 'y') {
				$this->out(__d('cake_console', 'Skip `%s`', $path), 2);
				return false;
			}
		} else {
			$this->out(__d('cake_console', 'Creating file %s', $path));
		}

		$File = new File($path, true);
		if ($File->exists() && $File->writable()) {
			$data = $File->prepare($contents);
			$File->write($data);
			$this->out(__d('cake_console', '<success>Wrote</success> `%s`', $path));
			return true;
		}

		$this->err(__d('cake_console', '<error>Could not write to `%s`</error>.', $path), 2);
		return false;
	}

/**
 * Load given shell helper class
 *
 * @param string $name Name of the helper class. Supports plugin syntax.
 * @return BaseShellHelper Instance of helper class
 * @throws RuntimeException If invalid class name is provided
 */
	public function helper($name) {
		if (isset($this->_helpers[$name])) {
			return $this->_helpers[$name];
		}
		list($plugin, $helperClassName) = pluginSplit($name, true);
		$helperClassName = Inflector::camelize($name) . "ShellHelper";
		App::uses($helperClassName, $plugin . "Console/Helper");
		if (!class_exists($helperClassName)) {
			throw new RuntimeException("Class " . $helperClassName . " not found");
		}
		$helper = new $helperClassName($this->stdout);
		$this->_helpers[$name] = $helper;
		return $helper;
	}

/**
 * Action to create a Unit Test
 *
 * @return bool Success
 */
	protected function _checkUnitTest() {
		if (class_exists('PHPUnit_Framework_TestCase')) {
			return true;
			//@codingStandardsIgnoreStart
		} elseif (@include 'PHPUnit' . DS . 'Autoload.php') {
			//@codingStandardsIgnoreEnd
			return true;
		} elseif (App::import('Vendor', 'phpunit', array('file' => 'PHPUnit' . DS . 'Autoload.php'))) {
			return true;
		}

		$prompt = __d('cake_console', 'PHPUnit is not installed. Do you want to bake unit test files anyway?');
		$unitTest = $this->in($prompt, array('y', 'n'), 'y');
		$result = strtolower($unitTest) === 'y' || strtolower($unitTest) === 'yes';

		if ($result) {
			$this->out();
			$this->out(__d('cake_console', 'You can download PHPUnit from %s', 'http://phpunit.de'));
		}
		return $result;
	}

/**
 * Makes absolute file path easier to read
 *
 * @param string $file Absolute file path
 * @return string short path
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::shortPath
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
		return Inflector::underscore($name);
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
 * Creates the proper model camelized name (singularized) for the specified name
 *
 * @param string $name Name
 * @return string Camelized and singularized model name
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
 * @param string $name The plural underscored value.
 * @return string name
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
 * @return string path path to the correct plugin.
 */
	protected function _pluginPath($pluginName) {
		if (CakePlugin::loaded($pluginName)) {
			return CakePlugin::path($pluginName);
		}
		return current(App::path('plugins')) . $pluginName . DS;
	}

/**
 * Used to enable or disable logging stream output to stdout and stderr
 * If you don't wish to see in your stdout or stderr everything that is logged
 * through CakeLog, call this function with first param as false
 *
 * @param bool $enable whether to enable CakeLog output or not
 * @return void
 */
	protected function _useLogger($enable = true) {
		if (!$enable) {
			CakeLog::drop('stdout');
			CakeLog::drop('stderr');
			return;
		}
		CakeLog::config('stdout', array(
			'engine' => 'Console',
			'types' => array('notice', 'info'),
			'stream' => $this->stdout,
		));
		CakeLog::config('stderr', array(
			'engine' => 'Console',
			'types' => array('emergency', 'alert', 'critical', 'error', 'warning', 'debug'),
			'stream' => $this->stderr,
		));
	}
}
