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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command;

use Cake\Cache\Cache;
use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Model\Model;
use Cake\Utility\ConventionsTrait;
use Cake\Utility\Inflector;

/**
 * Command-line code generation utility to automate programmer chores.
 *
 * Bake is CakePHP's code generation script, which can help you kickstart
 * application development by writing fully functional skeleton controllers,
 * models, and views. Going further, Bake can also write Unit Tests for you.
 *
 * @link http://book.cakephp.org/3.0/en/console-and-shells/code-generation-with-bake.html
 */
class BakeShell extends Shell {

	use ConventionsTrait;

/**
 * The connection being used.
 *
 * @var string
 */
	public $connection = 'default';

/**
 * Assign $this->connection to the active task if a connection param is set.
 *
 * @return void
 */
	public function startup() {
		parent::startup();
		Configure::write('debug', true);
		Cache::disable();

		$task = $this->_camelize($this->command);
		if (isset($this->{$task}) && !in_array($task, ['Project'])) {
			if (isset($this->params['connection'])) {
				$this->{$task}->connection = $this->params['connection'];
			}
		}
		if (isset($this->params['connection'])) {
			$this->connection = $this->params['connection'];
		}
	}

/**
 * Override main() to handle action
 *
 * @return mixed
 */
	public function main() {
		$connections = ConnectionManager::configured();
		if (empty($connections)) {
			$this->out(__d('cake_console', 'Your database configuration was not found.'));
			$this->out(__d('cake_console', 'Add your database connection information to App/Config/app.php.'));
			return false;
		}
		$this->out(__d('cake_console', 'The following commands you can generate skeleton code your your application.'));
		$this->out('');
		$this->out(__d('cake_console', '<info>Available bake commands:</info>'));
		$this->out('');
		foreach ($this->tasks as $task) {
			list($p, $name) = pluginSplit($task);
			$this->out(Inflector::underscore($name));
		}
		$this->out('');
		$this->out(__d('cake_console', 'By using <info>Console/cake bake [name]</info> you can invoke a specific bake task.'));
	}

/**
 * Locate the tasks bake will use.
 *
 * Scans the following paths for tasks that are subclasses of
 * Cake\Console\Command\Task\BakeTask:
 *
 * - Cake/Console/Command/Task/
 * - App/Console/Command/Task/
 * - Console/Command/Task for each loaded plugin
 *
 * @return void
 */
	public function loadTasks() {
		$tasks = [];
		$tasks = $this->_findTasks($tasks, CAKE, 'Cake');
		$tasks = $this->_findTasks($tasks, APP, Configure::read('App.namespace'));
		foreach (Plugin::loaded() as $plugin) {
			$tasks = $this->_findTasks(
				$tasks,
				Plugin::path($plugin),
				Plugin::getNamespace($plugin),
				$plugin
			);
		}
		$this->tasks = array_values($tasks);
		parent::loadTasks();
	}

/**
 * Append matching tasks in $path to the $tasks array.
 *
 * @param array $tasks The task list to modify and return.
 * @param string $path The base path to look in.
 * @param string $namespace The base namespace.
 * @param string $prefix The prefix to append.
 * @return array Updated tasks.
 */
	protected function _findTasks($tasks, $path, $namespace, $prefix = false) {
		$path .= 'Console/Command/Task';
		if (!is_dir($path)) {
			return $tasks;
		}
		$candidates = $this->_findClassFiles($path, $namespace);
		$classes = $this->_findTaskClasses($candidates);
		foreach ($classes as $class) {
			list($ns, $name) = namespaceSplit($class);
			$name = substr($name, 0, -4);
			$fullName = ($prefix ? $prefix . '.' : '') . $name;
			$tasks[$name] = $fullName;
		}
		return $tasks;
	}

/**
 * Find task classes in a given path.
 *
 * @param string $path The path to scan.
 * @return array An array of files that may contain bake tasks.
 */
	protected function _findClassFiles($path, $namespace) {
		$iterator = new \DirectoryIterator($path);
		$candidates = [];
		foreach ($iterator as $item) {
			if ($item->isDot() || $item->isDir()) {
				continue;
			}
			$name = $item->getBasename('.php');
			$candidates[] = $namespace . '\Console\Command\Task\\' . $name;
		}
		return $candidates;
	}

/**
 * Find bake tasks in a given set of files.
 *
 * @param array $files The array of files.
 * @return array An array of matching classes.
 */
	protected function _findTaskClasses($files) {
		$classes = [];
		foreach ($files as $classname) {
			if (!class_exists($classname)) {
				continue;
			}
			$reflect = new \ReflectionClass($classname);
			if (!$reflect->isInstantiable()) {
				continue;
			}
			if (!$reflect->isSubclassOf('Cake\Console\Command\Task\BakeTask')) {
				continue;
			}
			$classes[] = $classname;
		}
		return $classes;
	}

/**
 * Quickly bake the MVC
 *
 * @return void
 */
	public function all($name = null) {
		$this->out('Bake All');
		$this->hr();

		if (!empty($this->params['connection'])) {
			$this->connection = $this->params['connection'];
		}

		if (empty($name)) {
			$this->Model->connection = $this->connection;
			$this->out(__d('cake_console', 'Possible model names based on your database'));
			foreach ($this->Model->listAll() as $table) {
				$this->out('- ' . $table);
			}
			$this->out(__d('cake_console', 'Run <info>cake bake all [name]</info>. To generate skeleton files.'));
			return false;
		}

		foreach (['Model', 'Controller', 'View'] as $task) {
			$this->{$task}->connection = $this->connection;
		}

		$name = $this->_modelName($name);

		$this->Model->bake($name);
		$this->Controller->bake($name);

		$this->View->main($name);

		$this->out(__d('cake_console', '<success>Bake All complete</success>'), 1, Shell::QUIET);
		return true;
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return \Cake\Console\ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(
			__d('cake_console', 'The Bake script generates controllers, views and models for your application.' .
			' If run with no command line arguments, Bake guides the user through the class creation process.' .
			' You can customize the generation process by telling Bake where different parts of your application are using command line arguments.'
		))->addSubcommand('all', [
			'help' => __d('cake_console', 'Bake a complete MVC. optional <name> of a Model'),
		])->addOption('connection', [
			'help' => __d('cake_console', 'Database connection to use in conjunction with `bake all`.'),
			'short' => 'c',
			'default' => 'default'
		])->addOption('theme', [
			'short' => 't',
			'help' => __d('cake_console', 'Theme to use when baking code.')
		]);

		foreach ($this->_taskMap as $task => $config) {
			$taskParser = $this->{$task}->getOptionParser();
			$parser->addSubcommand(Inflector::underscore($task), [
				'help' => $taskParser->description(),
				'parser' => $taskParser
			]);
		}

		return $parser;
	}

}
