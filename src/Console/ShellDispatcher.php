<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Error\Exception;
use Cake\Utility\Inflector;

/**
 * Shell dispatcher handles dispatching cli commands.
 *
 * Consult https://github.com/cakephp/app/tree/master/App/Console/cake.php
 * for how this class is used in practice.
 */
class ShellDispatcher {

/**
 * Contains arguments parsed from the command line.
 *
 * @var array
 */
	public $args = [];

/**
 * List of connected aliases.
 *
 * @var array
 */
	protected static $_aliases = [];

/**
 * Constructor
 *
 * The execution of the script is stopped after dispatching the request with
 * a status code of either 0 or 1 according to the result of the dispatch.
 *
 * @param array $args the argv from PHP
 * @param bool $bootstrap Should the environment be bootstrapped.
 */
	public function __construct($args = [], $bootstrap = true) {
		set_time_limit(0);
		$this->args = (array)$args;

		if ($bootstrap) {
			$this->_initEnvironment();
		}
	}

/**
 * Add an alias for a shell command.
 *
 * Aliases allow you to call shells by alternate names. This is most
 * useful when dealing with plugin shells that you want to have shorter
 * names for.
 *
 * If you re-use an alias the last alias set will be the one available.
 *
 * @param string $short The new short name for the shell.
 * @param string $original The original full name for the shell.
 * @return void
 */
	public static function alias($short, $original) {
		static::$_aliases[$short] = $original;
	}

/**
 * Clear any aliases that have been set.
 *
 * @return void
 */
	public static function resetAliases() {
		static::$_aliases = [];
	}

/**
 * Run the dispatcher
 *
 * @param array $argv The argv from PHP
 * @return int The exit code of the shell process.
 */
	public static function run($argv) {
		$dispatcher = new ShellDispatcher($argv);
		return $dispatcher->dispatch();
	}

/**
 * Defines current working environment.
 *
 * @return void
 * @throws \Cake\Error\Exception
 */
	protected function _initEnvironment() {
		if (!$this->_bootstrap()) {
			$message = "Unable to load CakePHP core.\nMake sure Cake exists in " . CAKE_CORE_INCLUDE_PATH;
			throw new Exception($message);
		}

		if (function_exists('ini_set')) {
			ini_set('html_errors', false);
			ini_set('implicit_flush', true);
			ini_set('max_execution_time', 0);
		}

		$this->shiftArgs();
	}

/**
 * Initializes the environment and loads the CakePHP core.
 *
 * @return bool Success.
 */
	protected function _bootstrap() {
		if (!Configure::read('App.fullBaseUrl')) {
			Configure::write('App.fullBaseUrl', 'http://localhost');
		}

		return true;
	}

/**
 * Dispatches a CLI request
 *
 * @return int The cli command exit code. 0 is success.
 */
	public function dispatch() {
		return $this->_dispatch() === true ? 0 : 1;
	}

/**
 * Dispatch a request.
 *
 * @return bool
 * @throws \Cake\Console\Error\MissingShellMethodException
 */
	protected function _dispatch() {
		$shell = $this->shiftArgs();

		if (!$shell) {
			$this->help();
			return false;
		}
		if (in_array($shell, ['help', '--help', '-h'])) {
			$this->help();
			return true;
		}

		$Shell = $this->findShell($shell);

		$Shell->initialize();
		return $Shell->runCommand($this->args, true);
	}

/**
 * Get shell to use, either plugin shell or application shell
 *
 * All paths in the loaded shell paths are searched.
 *
 * @param string $shell Optionally the name of a plugin
 * @return \Cake\Console\Shell A shell instance.
 * @throws \Cake\Console\Error\MissingShellException when errors are encountered.
 */
	public function findShell($shell) {
		$classname = $this->_shellExists($shell);
		if (!$classname && isset(static::$_aliases[$shell])) {
			$shell = static::$_aliases[$shell];
			$classname = $this->_shellExists($shell);
		}
		if ($classname) {
			list($plugin) = pluginSplit($shell);
			$instance = new $classname();
			$instance->plugin = Inflector::camelize(trim($plugin, '.'));
			return $instance;
		}
		throw new Error\MissingShellException([
			'class' => $shell,
		]);
	}

/**
 * Check if a shell class exists for the given name.
 *
 * @param string $shell The shell name to look for.
 * @return string|boolean Either the classname or false.
 */
	protected function _shellExists($shell) {
		$class = array_map('Cake\Utility\Inflector::camelize', explode('.', $shell));
		$class = implode('.', $class);
		$class = App::classname($class, 'Console/Command', 'Shell');
		if (class_exists($class)) {
			return $class;
		}
		return false;
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
 * Shows console help. Performs an internal dispatch to the CommandList Shell
 *
 * @return void
 */
	public function help() {
		$this->args = array_merge(['command_list'], $this->args);
		$this->dispatch();
	}

/**
 * Stop execution of the current script
 *
 * @param int|string $status see http://php.net/exit for values
 * @return void
 */
	protected function _stop($status = 0) {
		exit($status);
	}

}
