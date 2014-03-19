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
use Cake\Utility\ClassRegistry;
use Cake\Utility\Inflector;

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
	public $tasks = ['Model', 'Test', 'Template', 'DbConfig', 'Project'];

/**
 * path to Controller directory
 *
 * @var array
 */
	public $path = null;

/**
 * Override initialize
 *
 * @return void
 */
	public function initialize() {
		$this->path = current(App::path('Controller'));
	}

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	public function execute() {
		parent::execute();

		if (!isset($this->connection)) {
			$this->connection = 'default';
		}

		if (empty($this->args)) {
			$this->out(__d('cake_console', 'Possible Controllers based on your current database:'));
			foreach ($this->listAll() as $table) {
				$this->out('- ' . $this->_controllerName($table));
			}
			return true;
		}

		if (strtolower($this->args[0]) === 'all') {
			return $this->all();
		}

		$controller = $this->_controllerName($this->args[0]);

		$this->bake($controller);
	}

/**
 * Bake All the controllers at once. Will only bake controllers for models that exist.
 *
 * @return void
 */
	public function all() {
		$this->listAll();
		ClassRegistry::config('Model', ['ds' => $this->connection]);

		$admin = false;
		if (!empty($this->params['admin'])) {
			$admin = $this->Project->getPrefix();
		}

		$controllersCreated = 0;
		foreach ($this->__tables as $table) {
			$model = $this->_modelName($table);
			$controller = $this->_controllerName($model);
			$classname = App::classname($model, 'Model');
			if ($classname) {
				$actions = $this->bakeActions($controller);
				if ($admin) {
					$this->out(__d('cake_console', 'Adding %s methods', $admin));
					$actions .= "\n" . $this->bakeActions($controller, $admin);
				}
				$this->bake($controller, $actions);
				$this->bakeTest($controller);
				$controllersCreated++;
			}
		}

		if (!$controllersCreated) {
			$this->out(__d('cake_console', 'No Controllers were baked, Models need to exist before Controllers can be baked.'));
		}
	}

/**
 * Interactive
 *
 * @return void
 */
	protected function _interactive() {
		$this->interactive = true;
		$this->hr();
		$this->out(__d('cake_console', "Bake Controller\nPath: %s", $this->getPath()));
		$this->hr();

		if (empty($this->connection)) {
			$this->connection = $this->DbConfig->getConfig();
		}

		$controllerName = $this->getName();
		$this->hr();
		$this->out(__d('cake_console', 'Baking %sController', $controllerName));
		$this->hr();

		$helpers = $components = [];
		$actions = '';
		$wannaBakeAdminCrud = 'n';
		$useDynamicScaffold = 'n';
		$wannaBakeCrud = 'y';

		$question[] = __d('cake_console', "Would you like to build your controller interactively?");
		if (file_exists($this->path . $controllerName . 'Controller.php')) {
			$question[] = __d('cake_console', "Warning: Choosing no will overwrite the %sController.", $controllerName);
		}
		$doItInteractive = $this->in(implode("\n", $question), ['y', 'n'], 'y');

		if (strtolower($doItInteractive) === 'y') {
			$this->interactive = true;
			$useDynamicScaffold = $this->in(
				__d('cake_console', "Would you like to use dynamic scaffolding?"), ['y', 'n'], 'n'
			);

			if (strtolower($useDynamicScaffold) === 'y') {
				$wannaBakeCrud = 'n';
				$actions = 'scaffold';
			} else {
				list($wannaBakeCrud, $wannaBakeAdminCrud) = $this->_askAboutMethods();

				$helpers = $this->doHelpers();
				$components = $this->doComponents();

				$wannaUseSession = $this->in(
					__d('cake_console', "Would you like to use Session flash messages?"), array('y', 'n'), 'y'
				);

				if (strtolower($wannaUseSession) === 'y') {
					array_push($components, 'Session');
				}
			}
		} else {
			list($wannaBakeCrud, $wannaBakeAdminCrud) = $this->_askAboutMethods();
		}

		if (strtolower($wannaBakeCrud) === 'y') {
			$actions = $this->bakeActions($controllerName, null);
		}
		if (strtolower($wannaBakeAdminCrud) === 'y') {
			$admin = $this->Project->getPrefix();
			$actions .= $this->bakeActions($controllerName, $admin);
		}

		$baked = false;
		if ($this->interactive === true) {
			$this->confirmController($controllerName, $useDynamicScaffold, $helpers, $components);
			$looksGood = $this->in(__d('cake_console', 'Look okay?'), ['y', 'n'], 'y');

			if (strtolower($looksGood) === 'y') {
				$baked = $this->bake($controllerName, $actions, $helpers, $components);
				if ($baked && $this->_checkUnitTest()) {
					$this->bakeTest($controllerName);
				}
			}
		} else {
			$baked = $this->bake($controllerName, $actions, $helpers, $components);
			if ($baked && $this->_checkUnitTest()) {
				$this->bakeTest($controllerName);
			}
		}
		return $baked;
	}

/**
 * Confirm a to be baked controller with the user
 *
 * @param string $controllerName
 * @param string $useDynamicScaffold
 * @param array $helpers
 * @param array $components
 * @return void
 */
	public function confirmController($controllerName, $useDynamicScaffold, $helpers, $components) {
		$this->out();
		$this->hr();
		$this->out(__d('cake_console', 'The following controller will be created:'));
		$this->hr();
		$this->out(__d('cake_console', "Controller Name:\n\t%s", $controllerName));

		if (strtolower($useDynamicScaffold) === 'y') {
			$this->out("public \$scaffold;");
		}

		$properties = [
			'helpers' => __d('cake_console', 'Helpers:'),
			'components' => __d('cake_console', 'Components:'),
		];

		foreach ($properties as $var => $title) {
			if (count($$var)) {
				$output = '';
				$length = count($$var);
				foreach ($$var as $i => $propElement) {
					if ($i != $length - 1) {
						$output .= ucfirst($propElement) . ', ';
					} else {
						$output .= ucfirst($propElement);
					}
				}
				$this->out($title . "\n\t" . $output);
			}
		}
		$this->hr();
	}

/**
 * Interact with the user and ask about which methods (admin or regular they want to bake)
 *
 * @return array Array containing (bakeRegular, bakeAdmin) answers
 */
	protected function _askAboutMethods() {
		$wannaBakeCrud = $this->in(
			__d('cake_console', "Would you like to create some basic class methods \n(index(), add(), view(), edit())?"),
			['y', 'n'], 'n'
		);
		$wannaBakeAdminCrud = $this->in(
			__d('cake_console', "Would you like to create the basic class methods for admin routing?"),
			['y', 'n'], 'n'
		);
		return [$wannaBakeCrud, $wannaBakeAdminCrud];
	}

/**
 * Bake scaffold actions
 *
 * @param string $controllerName Controller name
 * @param string $admin Admin route to use
 * @return string Baked actions
 */
	public function bakeActions($controllerName, $admin = null) {
		$currentModelName = $modelImport = $this->_modelName($controllerName);
		$plugin = $this->plugin;
		if ($plugin) {
			$plugin .= '.';
		}
		$classname = App::classname($plugin . $modelImport, 'Model');
		if (!class_exists($modelImport)) {
			$this->err(__d('cake_console', 'You must have a model for this class to build basic methods. Please try again.'));
			return $this->_stop();
		}

		$modelObj = ClassRegistry::init($currentModelName);
		$controllerPath = $this->_controllerPath($controllerName);
		$pluralName = $this->_pluralName($currentModelName);
		$singularName = Inflector::variable($currentModelName);
		$singularHumanName = $this->_singularHumanName($controllerName);
		$pluralHumanName = $this->_pluralName($controllerName);
		$displayField = $modelObj->displayField;
		$primaryKey = $modelObj->primaryKey;

		$this->Template->set(compact(
			'plugin', 'admin', 'controllerPath', 'pluralName', 'singularName',
			'singularHumanName', 'pluralHumanName', 'modelObj', 'currentModelName',
			'displayField', 'primaryKey'
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
		$prefix = $this->params['prefix'];

		$namespace = Configure::read('App.namespace');
		$pluginPath = '';
		if ($this->plugin) {
			$namespace = $this->plugin;
			$pluginPath = $this->plugin . '.';
		}
		$data = compact(
			'actions', 'helpers', 'components',
			'prefix', 'namespace', 'pluginPath'
		);
		$data['name'] = $controllerName;

		$this->bakeController($controllerName, $data);
		$this->bakeTest($controllerName);
	}

	public function bakeController($controllerName, $data) {
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
		if ($this->createFile($filename, $contents)) {
			return $contents;
		}
		return false;
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
		$this->Test->interactive = $this->interactive;
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
		if (!in_array('Paginator', $components)) {
			$components[] = 'Paginator';
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
		if (count($helpers) && !in_array('Form', $helpers)) {
			$helpers[] = 'Form';
		}
		return $helpers;
	}

/**
 * Interact with the user and get a list of additional components
 *
 * @return array Components the user wants to use.
 */
	public function doComponents() {
		$components = ['Paginator'];
		return array_merge($components, $this->_doPropertyChoices(
			__d('cake_console', "Would you like this controller to use other components\nbesides PaginatorComponent?"),
			__d('cake_console', "Please provide a comma separated list of the component names you'd like to use.\nExample: 'Acl, Security, RequestHandler'")
		));
	}

/**
 * Common code for property choice handling.
 *
 * @param string $prompt A yes/no question to precede the list
 * @param string $example A question for a comma separated list, with examples.
 * @return array Array of values for property.
 */
	protected function _doPropertyChoices($prompt, $example) {
		$proceed = $this->in($prompt, ['y', 'n'], 'n');
		$property = [];
		if (strtolower($proceed) === 'y') {
			$propertyList = $this->in($example);
			$propertyListTrimmed = str_replace(' ', '', $propertyList);
			$property = explode(',', $propertyListTrimmed);
		}
		return array_filter($property);
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
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->description(
			__d('cake_console', 'Bake a controller for a model. Using options you can bake public, admin or both.')
		)->addArgument('name', [
			'help' => __d('cake_console', 'Name of the controller to bake. Can use Plugin.name to bake controllers into plugins.')
		])->addOption('plugin', [
			'short' => 'p',
			'help' => __d('cake_console', 'Plugin to bake the controller into.')
		])->addOption('connection', [
			'short' => 'c',
			'help' => __d('cake_console', 'The connection the controller\'s model is on.')
		])->addOption('theme', [
			'short' => 't',
			'help' => __d('cake_console', 'Theme to use when baking code.')
		])->addOption('components', [
			'help' => __d('cake_console', 'The comma separated list of components to use.')
		])->addOption('helpers', [
			'help' => __d('cake_console', 'The comma separated list of helpers to use.')
		])->addOption('prefix', [
			'help' => __d('cake_console', 'The namespace/routing prefix to use.')
		])->addOption('no-test', [
			'boolean' => true,
			'help' => __d('cake_console', 'Do not generate a test skeleton.')
		])->addOption('force', [
			'short' => 'f',
			'help' => __d('cake_console', 'Force overwriting existing files without prompting.')
		])->addSubcommand('all', [
			'help' => __d('cake_console', 'Bake all controllers with CRUD methods.')
		]);

		return $parser;
	}

}
