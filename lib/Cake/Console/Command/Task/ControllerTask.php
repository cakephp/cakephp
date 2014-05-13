<?php
/**
 * The ControllerTask handles creating and updating controller files.
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
 * @since         CakePHP(tm) v 1.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppShell', 'Console/Command');
App::uses('BakeTask', 'Console/Command/Task');
App::uses('AppModel', 'Model');

/**
 * Task class for creating and updating controller files.
 *
 * @package       Cake.Console.Command.Task
 */
class ControllerTask extends BakeTask {

/**
 * Tasks to be loaded by this Task
 *
 * @var array
 */
	public $tasks = array('Model', 'Test', 'Template', 'DbConfig', 'Project');

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
		if (empty($this->args)) {
			return $this->_interactive();
		}

		if (isset($this->args[0])) {
			if (!isset($this->connection)) {
				$this->connection = 'default';
			}
			if (strtolower($this->args[0]) === 'all') {
				return $this->all();
			}

			$controller = $this->_controllerName($this->args[0]);
			$actions = '';

			if (!empty($this->params['public'])) {
				$this->out(__d('cake_console', 'Baking basic crud methods for ') . $controller);
				$actions .= $this->bakeActions($controller);
			}
			if (!empty($this->params['admin'])) {
				$admin = $this->Project->getPrefix();
				if ($admin) {
					$this->out(__d('cake_console', 'Adding %s methods', $admin));
					$actions .= "\n" . $this->bakeActions($controller, $admin);
				}
			}
			if (empty($actions)) {
				$actions = 'scaffold';
			}

			if ($this->bake($controller, $actions)) {
				if ($this->_checkUnitTest()) {
					$this->bakeTest($controller);
				}
			}
		}
	}

/**
 * Bake All the controllers at once. Will only bake controllers for models that exist.
 *
 * @return void
 */
	public function all() {
		$this->interactive = false;
		$this->listAll($this->connection, false);
		ClassRegistry::config('Model', array('ds' => $this->connection));
		$unitTestExists = $this->_checkUnitTest();

		$admin = false;
		if (!empty($this->params['admin'])) {
			$admin = $this->Project->getPrefix();
		}

		$controllersCreated = 0;
		foreach ($this->__tables as $table) {
			$model = $this->_modelName($table);
			$controller = $this->_controllerName($model);
			App::uses($model, 'Model');
			if (class_exists($model)) {
				$actions = $this->bakeActions($controller);
				if ($admin) {
					$this->out(__d('cake_console', 'Adding %s methods', $admin));
					$actions .= "\n" . $this->bakeActions($controller, $admin);
				}
				if ($this->bake($controller, $actions) && $unitTestExists) {
					$this->bakeTest($controller);
				}
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

		$helpers = $components = array();
		$actions = '';
		$wannaUseSession = 'y';
		$wannaBakeAdminCrud = 'n';
		$useDynamicScaffold = 'n';
		$wannaBakeCrud = 'y';

		$question[] = __d('cake_console', "Would you like to build your controller interactively?");
		if (file_exists($this->path . $controllerName . 'Controller.php')) {
			$question[] = __d('cake_console', "Warning: Choosing no will overwrite the %sController.", $controllerName);
		}
		$doItInteractive = $this->in(implode("\n", $question), array('y', 'n'), 'y');

		if (strtolower($doItInteractive) === 'y') {
			$this->interactive = true;
			$useDynamicScaffold = $this->in(
				__d('cake_console', "Would you like to use dynamic scaffolding?"), array('y', 'n'), 'n'
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
			$actions = $this->bakeActions($controllerName, null, strtolower($wannaUseSession) === 'y');
		}
		if (strtolower($wannaBakeAdminCrud) === 'y') {
			$admin = $this->Project->getPrefix();
			$actions .= $this->bakeActions($controllerName, $admin, strtolower($wannaUseSession) === 'y');
		}

		$baked = false;
		if ($this->interactive === true) {
			$this->confirmController($controllerName, $useDynamicScaffold, $helpers, $components);
			$looksGood = $this->in(__d('cake_console', 'Look okay?'), array('y', 'n'), 'y');

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

		$properties = array(
			'helpers' => __d('cake_console', 'Helpers:'),
			'components' => __d('cake_console', 'Components:'),
		);

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
			array('y', 'n'), 'n'
		);
		$wannaBakeAdminCrud = $this->in(
			__d('cake_console', "Would you like to create the basic class methods for admin routing?"),
			array('y', 'n'), 'n'
		);
		return array($wannaBakeCrud, $wannaBakeAdminCrud);
	}

/**
 * Bake scaffold actions
 *
 * @param string $controllerName Controller name
 * @param string $admin Admin route to use
 * @param boolean $wannaUseSession Set to true to use sessions, false otherwise
 * @return string Baked actions
 */
	public function bakeActions($controllerName, $admin = null, $wannaUseSession = true) {
		$currentModelName = $modelImport = $this->_modelName($controllerName);
		$plugin = $this->plugin;
		if ($plugin) {
			$plugin .= '.';
		}
		App::uses($modelImport, $plugin . 'Model');
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
			'singularHumanName', 'pluralHumanName', 'modelObj', 'wannaUseSession', 'currentModelName',
			'displayField', 'primaryKey'
		));
		$actions = $this->Template->generate('actions', 'controller_actions');
		return $actions;
	}

/**
 * Assembles and writes a Controller file
 *
 * @param string $controllerName Controller name already pluralized and correctly cased.
 * @param string $actions Actions to add, or set the whole controller to use $scaffold (set $actions to 'scaffold')
 * @param array $helpers Helpers to use in controller
 * @param array $components Components to use in controller
 * @return string Baked controller
 */
	public function bake($controllerName, $actions = '', $helpers = null, $components = null) {
		$this->out("\n" . __d('cake_console', 'Baking controller class for %s...', $controllerName), 1, Shell::QUIET);

		$isScaffold = ($actions === 'scaffold') ? true : false;

		$this->Template->set(array(
			'plugin' => $this->plugin,
			'pluginPath' => empty($this->plugin) ? '' : $this->plugin . '.'
		));

		if (!in_array('Paginator', (array)$components)) {
			$components[] = 'Paginator';
		}

		$this->Template->set(compact('controllerName', 'actions', 'helpers', 'components', 'isScaffold'));
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
		$this->Test->plugin = $this->plugin;
		$this->Test->connection = $this->connection;
		$this->Test->interactive = $this->interactive;
		return $this->Test->bake('Controller', $className);
	}

/**
 * Interact with the user and get a list of additional helpers
 *
 * @return array Helpers that the user wants to use.
 */
	public function doHelpers() {
		return $this->_doPropertyChoices(
			__d('cake_console', "Would you like this controller to use other helpers\nbesides HtmlHelper and FormHelper?"),
			__d('cake_console', "Please provide a comma separated list of the other\nhelper names you'd like to use.\nExample: 'Text, Js, Time'")
		);
	}

/**
 * Interact with the user and get a list of additional components
 *
 * @return array Components the user wants to use.
 */
	public function doComponents() {
		$components = array('Paginator');
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
		$proceed = $this->in($prompt, array('y', 'n'), 'n');
		$property = array();
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
	public function listAll($useDbConfig = null) {
		if ($useDbConfig === null) {
			$useDbConfig = $this->connection;
		}
		$this->__tables = $this->Model->getAllTables($useDbConfig);

		if ($this->interactive) {
			$this->out(__d('cake_console', 'Possible Controllers based on your current database:'));
			$this->hr();
			$this->_controllerNames = array();
			$count = count($this->__tables);
			for ($i = 0; $i < $count; $i++) {
				$this->_controllerNames[] = $this->_controllerName($this->_modelName($this->__tables[$i]));
				$this->out(sprintf("%2d. %s", $i + 1, $this->_controllerNames[$i]));
			}
			return $this->_controllerNames;
		}
		return $this->__tables;
	}

/**
 * Forces the user to specify the controller he wants to bake, and returns the selected controller name.
 *
 * @param string $useDbConfig Connection name to get a controller name for.
 * @return string Controller name
 */
	public function getName($useDbConfig = null) {
		$controllers = $this->listAll($useDbConfig);
		$enteredController = '';

		while (!$enteredController) {
			$enteredController = $this->in(__d('cake_console', "Enter a number from the list above,\ntype in the name of another controller, or 'q' to exit"), null, 'q');
			if ($enteredController === 'q') {
				$this->out(__d('cake_console', 'Exit'));
				return $this->_stop();
			}

			if (!$enteredController || intval($enteredController) > count($controllers)) {
				$this->err(__d('cake_console', "The Controller name you supplied was empty,\nor the number you selected was not an option. Please try again."));
				$enteredController = '';
			}
		}

		if (intval($enteredController) > 0 && intval($enteredController) <= count($controllers)) {
			$controllerName = $controllers[intval($enteredController) - 1];
		} else {
			$controllerName = Inflector::camelize($enteredController);
		}
		return $controllerName;
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(
			__d('cake_console', 'Bake a controller for a model. Using options you can bake public, admin or both.'
		))->addArgument('name', array(
			'help' => __d('cake_console', 'Name of the controller to bake. Can use Plugin.name to bake controllers into plugins.')
		))->addOption('public', array(
			'help' => __d('cake_console', 'Bake a controller with basic crud actions (index, view, add, edit, delete).'),
			'boolean' => true
		))->addOption('admin', array(
			'help' => __d('cake_console', 'Bake a controller with crud actions for one of the Routing.prefixes.'),
			'boolean' => true
		))->addOption('plugin', array(
			'short' => 'p',
			'help' => __d('cake_console', 'Plugin to bake the controller into.')
		))->addOption('connection', array(
			'short' => 'c',
			'help' => __d('cake_console', 'The connection the controller\'s model is on.')
		))->addOption('theme', array(
			'short' => 't',
			'help' => __d('cake_console', 'Theme to use when baking code.')
		))->addOption('force', array(
			'short' => 'f',
			'help' => __d('cake_console', 'Force overwriting existing files without prompting.')
		))->addSubcommand('all', array(
			'help' => __d('cake_console', 'Bake all controllers with CRUD methods.')
		))->epilog(
			__d('cake_console', 'Omitting all arguments and options will enter into an interactive mode.')
		);

		return $parser;
	}

}
