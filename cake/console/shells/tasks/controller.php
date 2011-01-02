<?php
/**
 * The ControllerTask handles creating and updating controller files.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.console.shells.tasks
 * @since         CakePHP(tm) v 1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

include_once dirname(__FILE__) . DS . 'bake.php';

/**
 * Task class for creating and updating controller files.
 *
 * @package       cake.console.shells.tasks
 */
class ControllerTask extends BakeTask {

/**
 * Tasks to be loaded by this Task
 *
 * @var array
 * @access public
 */
	public $tasks = array('Model', 'Test', 'Template', 'DbConfig', 'Project');

/**
 * path to CONTROLLERS directory
 *
 * @var array
 * @access public
 */
	public $path = CONTROLLERS;

/**
 * Override initialize
 *
 */
	public function initialize() {
	}

/**
 * Execution method always used for tasks
 *
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
			if (strtolower($this->args[0]) == 'all') {
				return $this->all();
			}

			$controller = $this->_controllerName($this->args[0]);
			$actions = '';

			if (!empty($this->params['public'])) {
				$this->out(__('Baking basic crud methods for ') . $controller);
				$actions .= $this->bakeActions($controller);
			}
			if (!empty($this->params['admin'])) {
				$admin = $this->Project->getPrefix();
				if ($admin) {
					$this->out(__('Adding %s methods', $admin));
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
 * Bake All the controllers at once.  Will only bake controllers for models that exist.
 *
 * @return void
 */
	public function all() {
		$this->interactive = false;
		$this->listAll($this->connection, false);
		ClassRegistry::config('Model', array('ds' => $this->connection));
		$unitTestExists = $this->_checkUnitTest();
		foreach ($this->__tables as $table) {
			$model = $this->_modelName($table);
			$controller = $this->_controllerName($model);
			if (App::import('Model', $model)) {
				$actions = $this->bakeActions($controller);
				if ($this->bake($controller, $actions) && $unitTestExists) {
					$this->bakeTest($controller);
				}
			}
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
		$this->out(__("Bake Controller\nPath: %s", $this->path));
		$this->hr();

		if (empty($this->connection)) {
			$this->connection = $this->DbConfig->getConfig();
		}

		$controllerName = $this->getName();
		$this->hr();
		$this->out(__('Baking %sController', $controllerName));
		$this->hr();

		$helpers = $components = array();
		$actions = '';
		$wannaUseSession = 'y';
		$wannaBakeAdminCrud = 'n';
		$useDynamicScaffold = 'n';
		$wannaBakeCrud = 'y';

		$controllerFile = strtolower(Inflector::underscore($controllerName));

		$question[] = __("Would you like to build your controller interactively?");
		if (file_exists($this->path . $controllerFile .'_controller.php')) {
			$question[] = __("Warning: Choosing no will overwrite the %sController.", $controllerName);
		}
		$doItInteractive = $this->in(implode("\n", $question), array('y','n'), 'y');

		if (strtolower($doItInteractive) == 'y') {
			$this->interactive = true;
			$useDynamicScaffold = $this->in(
				__("Would you like to use dynamic scaffolding?"), array('y','n'), 'n'
			);

			if (strtolower($useDynamicScaffold) == 'y') {
				$wannaBakeCrud = 'n';
				$actions = 'scaffold';
			} else {
				list($wannaBakeCrud, $wannaBakeAdminCrud) = $this->_askAboutMethods();

				$helpers = $this->doHelpers();
				$components = $this->doComponents();

				$wannaUseSession = $this->in(
					__("Would you like to use Session flash messages?"), array('y','n'), 'y'
				);
			}
		} else {
			list($wannaBakeCrud, $wannaBakeAdminCrud) = $this->_askAboutMethods();
		}

		if (strtolower($wannaBakeCrud) == 'y') {
			$actions = $this->bakeActions($controllerName, null, strtolower($wannaUseSession) == 'y');
		}
		if (strtolower($wannaBakeAdminCrud) == 'y') {
			$admin = $this->Project->getPrefix();
			$actions .= $this->bakeActions($controllerName, $admin, strtolower($wannaUseSession) == 'y');
		}

		$baked = false;
		if ($this->interactive === true) {
			$this->confirmController($controllerName, $useDynamicScaffold, $helpers, $components);
			$looksGood = $this->in(__('Look okay?'), array('y','n'), 'y');

			if (strtolower($looksGood) == 'y') {
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
 * @return void
 */
	public function confirmController($controllerName, $useDynamicScaffold, $helpers, $components) {
		$this->out();
		$this->hr();
		$this->out(__('The following controller will be created:'));
		$this->hr();
		$this->out(__("Controller Name:\n\t%s", $controllerName));

		if (strtolower($useDynamicScaffold) == 'y') {
			$this->out("var \$scaffold;");
		}

		$properties = array(
			'helpers' => __('Helpers:'),
			'components' => __('Components:'),
		);

		foreach ($properties as $var => $title) {
			if (count($$var)) {
				$output = '';
				$length = count($$var);
				foreach ($$var as $i => $propElement) {
					if ($i != $length -1) {
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
			__("Would you like to create some basic class methods \n(index(), add(), view(), edit())?"),
			array('y','n'), 'n'
		);
		$wannaBakeAdminCrud = $this->in(
			__("Would you like to create the basic class methods for admin routing?"),
			array('y','n'), 'n'
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
 * @access private
 */
	public function bakeActions($controllerName, $admin = null, $wannaUseSession = true) {
		$currentModelName = $modelImport = $this->_modelName($controllerName);
		$plugin = $this->plugin;
		if ($plugin) {
			$modelImport = $plugin . '.' . $modelImport;
		}
		if (!App::import('Model', $modelImport)) {
			$this->err(__('You must have a model for this class to build basic methods. Please try again.'));
			$this->_stop();
		}

		$modelObj = ClassRegistry::init($currentModelName);
		$controllerPath = $this->_controllerPath($controllerName);
		$pluralName = $this->_pluralName($currentModelName);
		$singularName = Inflector::variable($currentModelName);
		$singularHumanName = $this->_singularHumanName($controllerName);
		$pluralHumanName = $this->_pluralName($controllerName);

		$this->Template->set(compact('plugin', 'admin', 'controllerPath', 'pluralName', 'singularName', 'singularHumanName',
			'pluralHumanName', 'modelObj', 'wannaUseSession', 'currentModelName'));
		$actions = $this->Template->generate('actions', 'controller_actions');
		return $actions;
	}

/**
 * Assembles and writes a Controller file
 *
 * @param string $controllerName Controller name
 * @param string $actions Actions to add, or set the whole controller to use $scaffold (set $actions to 'scaffold')
 * @param array $helpers Helpers to use in controller
 * @param array $components Components to use in controller
 * @param array $uses Models to use in controller
 * @return string Baked controller
 */
	public function bake($controllerName, $actions = '', $helpers = null, $components = null) {
		$this->out("\nBaking controller class for $controllerName...", 1, Shell::QUIET);

		$isScaffold = ($actions === 'scaffold') ? true : false;

		$this->Template->set('plugin', Inflector::camelize($this->plugin));
		$this->Template->set(compact('controllerName', 'actions', 'helpers', 'components', 'isScaffold'));
		$contents = $this->Template->generate('classes', 'controller');

		$path = $this->getPath();
		$filename = $path . $this->_controllerPath($controllerName) . '_controller.php';
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
			__("Would you like this controller to use other helpers\nbesides HtmlHelper and FormHelper?"),
			__("Please provide a comma separated list of the other\nhelper names you'd like to use.\nExample: 'Ajax, Javascript, Time'")
		);
	}

/**
 * Interact with the user and get a list of additional components
 *
 * @return array Components the user wants to use.
 */
	public function doComponents() {
		return $this->_doPropertyChoices(
			__("Would you like this controller to use any components?"),
			__("Please provide a comma separated list of the component names you'd like to use.\nExample: 'Acl, Security, RequestHandler'")
		);
	}

/**
 * Common code for property choice handling.
 *
 * @param string $prompt A yes/no question to precede the list
 * @param sting $example A question for a comma separated list, with examples.
 * @return array Array of values for property.
 */
	protected function _doPropertyChoices($prompt, $example) {
		$proceed = $this->in($prompt, array('y','n'), 'n');
		$property = array();
		if (strtolower($proceed) == 'y') {
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
 * @param boolean $interactive Whether you are using listAll interactively and want options output.
 * @return array Set of controllers
 */
	public function listAll($useDbConfig = null) {
		if (is_null($useDbConfig)) {
			$useDbConfig = $this->connection;
		}
		$this->__tables = $this->Model->getAllTables($useDbConfig);

		if ($this->interactive == true) {
			$this->out(__('Possible Controllers based on your current database:'));
			$this->_controllerNames = array();
			$count = count($this->__tables);
			for ($i = 0; $i < $count; $i++) {
				$this->_controllerNames[] = $this->_controllerName($this->_modelName($this->__tables[$i]));
				$this->out($i + 1 . ". " . $this->_controllerNames[$i]);
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

		while ($enteredController == '') {
			$enteredController = $this->in(__("Enter a number from the list above,\ntype in the name of another controller, or 'q' to exit"), null, 'q');
			if ($enteredController === 'q') {
				$this->out(__('Exit'));
				return $this->_stop();
			}

			if ($enteredController == '' || intval($enteredController) > count($controllers)) {
				$this->err(__("The Controller name you supplied was empty,\nor the number you selected was not an option. Please try again."));
				$enteredController = '';
			}
		}

		if (intval($enteredController) > 0 && intval($enteredController) <= count($controllers) ) {
			$controllerName = $controllers[intval($enteredController) - 1];
		} else {
			$controllerName = Inflector::camelize($enteredController);
		}
		return $controllerName;
	}

/**
 * get the option parser.
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(
				__('Bake a controller for a model. Using options you can bake public, admin or both.')
			)->addArgument('name', array(
				'help' => __('Name of the controller to bake. Can use Plugin.name to bake controllers into plugins.')
			))->addOption('public', array(
				'help' => __('Bake a controller with basic crud actions (index, view, add, edit, delete).'),
				'boolean' => true
			))->addOption('admin', array(
				'help' => __('Bake a controller with crud actions for one of the Routing.prefixes.'),
				'boolean' => true
			))->addOption('plugin', array(
				'short' => 'p',
				'help' => __('Plugin to bake the controller into.')
			))->addOption('connection', array(
				'short' => 'c',
				'help' => __('The connection the controller\'s model is on.')
			))->addSubcommand('all', array(
				'help' => __('Bake all controllers with CRUD methods.')
			))->epilog(__('Omitting all arguments and options will enter into an interactive mode.'));
	}

/**
 * Displays help contents
 *
 */
	public function help() {
		$this->hr();
		$this->out("Usage: cake bake controller <arg1> <arg2>...");
		$this->hr();
		$this->out('Arguments:');
		$this->out();
		$this->out("<name>");
		$this->out("\tName of the controller to bake. Can use Plugin.name");
		$this->out("\tas a shortcut for plugin baking.");
		$this->out();
		$this->out('Commands:');
		$this->out();
		$this->out("controller <name>");
		$this->out("\tbakes controller with var \$scaffold");
		$this->out();
		$this->out("controller <name> public");
		$this->out("\tbakes controller with basic crud actions");
		$this->out("\t(index, view, add, edit, delete)");
		$this->out();
		$this->out("controller <name> admin");
		$this->out("\tbakes a controller with basic crud actions for one of the");
		$this->out("\tConfigure::read('Routing.prefixes') methods.");
		$this->out();
		$this->out("controller <name> public admin");
		$this->out("\tbakes a controller with basic crud actions for one");
		$this->out("\tConfigure::read('Routing.prefixes') and non admin methods.");
		$this->out("\t(index, view, add, edit, delete,");
		$this->out("\tadmin_index, admin_view, admin_edit, admin_add, admin_delete)");
		$this->out();
		$this->out("controller all");
		$this->out("\tbakes all controllers with CRUD methods.");
		$this->out();
		$this->_stop();
	}
}
