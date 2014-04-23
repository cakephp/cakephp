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
namespace Cake\Console\Command\Task;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Task class for creating and updating controller files.
 *
 */
class ControllerTask extends BakeTask {

/**
 * Tasks to be loaded by this Task
 *
 * @var array
 */
	public $tasks = ['Model', 'Test', 'Template'];

/**
 * Path fragment for generated code.
 *
 * @var string
 */
	public $pathFragment = 'Controller/';

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	public function main($name = null) {
		parent::main();

		if (empty($name)) {
			$this->out(__d('cake_console', 'Possible controllers based on your current database:'));
			foreach ($this->listAll() as $table) {
				$this->out('- ' . $this->_controllerName($table));
			}
			return true;
		}

		$controller = $this->_controllerName($name);
		$this->bake($controller);
	}

/**
 * Bake All the controllers at once. Will only bake controllers for models that exist.
 *
 * @return void
 */
	public function all() {
		$controllersCreated = 0;
		foreach ($this->listAll() as $table) {
			$controller = $this->_controllerName($table);
			$this->bake($controller);
			$this->bakeTest($controller);
			$controllersCreated++;
		}
	}

/**
 * Bake scaffold actions
 *
 * @param string $controllerName Controller name
 * @return string Baked actions
 */
	public function bakeActions($controllerName) {
		if (!empty($this->params['no-actions'])) {
			return '';
		}
		$currentModelName = $controllerName;
		$plugin = $this->plugin;
		if ($plugin) {
			$plugin .= '.';
		}

		$modelObj = TableRegistry::get($currentModelName);

		$pluralName = $this->_pluralName($currentModelName);
		$singularName = $this->_singularName($currentModelName);
		$singularHumanName = $this->_singularHumanName($controllerName);
		$pluralHumanName = $this->_pluralName($controllerName);

		$this->Template->set(compact(
			'plugin', 'admin', 'pluralName', 'singularName',
			'singularHumanName', 'pluralHumanName', 'modelObj', 'currentModelName'
		));
		$actions = $this->Template->generate('actions', 'controller_actions');
		return $actions;
	}

/**
 * Assembles and writes a Controller file
 *
 * @param string $controllerName Controller name already pluralized and correctly cased.
 * @return string Baked controller
 */
	public function bake($controllerName) {
		$this->out("\n" . __d('cake_console', 'Baking controller class for %s...', $controllerName), 1, Shell::QUIET);

		$actions = $this->bakeActions($controllerName);
		$helpers = $this->getHelpers();
		$components = $this->getComponents();

		$prefix = '';
		if (isset($this->params['prefix'])) {
			$prefix = '\\' . $this->params['prefix'];
		}

		$namespace = Configure::read('App.namespace');
		if ($this->plugin) {
			$namespace = $this->plugin;
		}

		$data = compact(
			'prefix',
			'actions',
			'helpers',
			'components',
			'namespace'
		);
		$data['name'] = $controllerName;

		$out = $this->bakeController($controllerName, $data);
		$this->bakeTest($controllerName);
		return $out;
	}

/**
 * Generate the controller code
 *
 * @param string $controllerName The name of the controller.
 * @param array $data The data to turn into code.
 * @return string The generated controller file.
 */
	public function bakeController($controllerName, array $data) {
		$data += [
			'name' => null,
			'namespace' => null,
			'prefix' => null,
			'actions' => null,
			'helpers' => null,
			'components' => null,
			'plugin' => null,
			'pluginPath' => null,
		];
		$this->Template->set($data);

		$contents = $this->Template->generate('classes', 'controller');

		$path = $this->getPath();
		$filename = $path . $controllerName . 'Controller.php';
		$this->createFile($filename, $contents);
		return $contents;
	}

/**
 * Gets the path for output. Checks the plugin property
 * and returns the correct path.
 *
 * @return string Path to output.
 */
	public function getPath() {
		$path = parent::getPath();
		if (!empty($this->params['prefix'])) {
			$path .= $this->_camelize($this->params['prefix']) . DS;
		}
		return $path;
	}

/**
 * Assembles and writes a unit test file
 *
 * @param string $className Controller class name
 * @return string Baked test
 */
	public function bakeTest($className) {
		if (!empty($this->params['no-test'])) {
			return;
		}
		$this->Test->plugin = $this->plugin;
		$this->Test->connection = $this->connection;
		if (!empty($this->params['prefix'])) {
			$className = $this->params['prefix'] . '\\' . $className;
		}
		return $this->Test->bake('Controller', $className);
	}

/**
 * Get the list of components for the controller.
 *
 * @return array
 */
	public function getComponents() {
		$components = [];
		if (!empty($this->params['components'])) {
			$components = explode(',', $this->params['components']);
			$components = array_values(array_filter(array_map('trim', $components)));
		}
		return $components;
	}

/**
 * Get the list of helpers for the controller.
 *
 * @return array
 */
	public function getHelpers() {
		$helpers = [];
		if (!empty($this->params['helpers'])) {
			$helpers = explode(',', $this->params['helpers']);
			$helpers = array_values(array_filter(array_map('trim', $helpers)));
		}
		return $helpers;
	}

/**
 * Outputs and gets the list of possible controllers from database
 *
 * @param string $useDbConfig Database configuration name
 * @return array Set of controllers
 */
	public function listAll() {
		$this->Model->connection = $this->connection;
		return $this->Model->listAll();
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return \Cake\Console\ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->description(
			__d('cake_console', 'Bake a controller skeleton.')
		)->addArgument('name', [
			'help' => __d('cake_console', 'Name of the controller to bake. Can use Plugin.name to bake controllers into plugins.')
		])->addOption('components', [
			'help' => __d('cake_console', 'The comma separated list of components to use.')
		])->addOption('helpers', [
			'help' => __d('cake_console', 'The comma separated list of helpers to use.')
		])->addOption('prefix', [
			'help' => __d('cake_console', 'The namespace/routing prefix to use.')
		])->addOption('no-test', [
			'boolean' => true,
			'help' => __d('cake_console', 'Do not generate a test skeleton.')
		])->addOption('no-actions', [
			'boolean' => true,
			'help' => __d('cake_console', 'Do not generate basic CRUD action methods.')
		])->addSubcommand('all', [
			'help' => __d('cake_console', 'Bake all controllers with CRUD methods.')
		]);

		return $parser;
	}

}
