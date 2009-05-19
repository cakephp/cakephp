<?php
/* SVN FILE: $Id$ */
/**
 * The ControllerTask handles creating and updating controller files.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008,	Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 * @since         CakePHP(tm) v 1.2
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Task class for creating and updating controller files.
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 */
class ControllerTask extends Shell {
/**
 * Name of plugin
 *
 * @var string
 * @access public
 */
	var $plugin = null;
/**
 * Tasks to be loaded by this Task
 *
 * @var array
 * @access public
 */
	var $tasks = array('Model', 'Project', 'Template', 'DbConfig');
/**
 * path to CONTROLLERS directory
 *
 * @var array
 * @access public
 */
	var $path = CONTROLLERS;
/**
 * Override initialize
 *
 * @access public
 */
	function initialize() {
	}

/**
 * Execution method always used for tasks
 *
 * @access public
 */
	function execute() {
		if (empty($this->args)) {
			$this->__interactive();
		}

		if (isset($this->args[0])) {
			if (!isset($this->connection)) {
				$this->connection = 'default';
			}
			if (strtolower($this->args[0]) == 'all') {
				return $this->all();
			}
			$controller = Inflector::camelize($this->args[0]);
			$actions = null;
			if (isset($this->args[1]) && $this->args[1] == 'scaffold') {
				$this->out('Baking scaffold for ' . $controller);
				$actions = $this->bakeActions($controller);
			} else {
				$actions = 'scaffold';
			}
			if ((isset($this->args[1]) && $this->args[1] == 'admin') || (isset($this->args[2]) && $this->args[2] == 'admin')) {
				if ($admin = $this->getAdmin()) {
					$this->out('Adding ' . Configure::read('Routing.admin') .' methods');
					if ($actions == 'scaffold') {
						$actions = $this->bakeActions($controller, $admin);
					} else {
						$actions .= $this->bakeActions($controller, $admin);
					}
				}
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
 * @access public
 * @return void
 **/
	function all() {
		$this->interactive = false;
		$this->listAll($this->connection, false);
		ClassRegistry::config('Model', array('ds' => $this->connection));
		foreach ($this->__tables as $table) {
			$model = $this->_modelName($table);
			$controller = $this->_controllerName($model);
			if (App::import('Model', $model)) {
				$actions = $this->bakeActions($controller);
				$this->bake($controller, $actions);
			}
		}
	}

/**
 * Interactive
 *
 * @access private
 */
	function __interactive() {
		$this->interactive = true;
		$this->hr();
		$this->out(sprintf("Bake Controller\nPath: %s", $this->path));
		$this->hr();

		if (empty($this->connection)) {
			$this->connection = $this->DbConfig->getConfig();
		}

		$controllerName = $this->getName();
		$this->hr();
		$this->out("Baking {$controllerName}Controller");
		$this->hr();
		
		$helpers = $components = array();
		$actions = '';
		$wannaUseSession = 'y';
		$wannaBakeAdminCrud = 'n';
		$useDynamicScaffold = 'n';
		$wannaBakeCrud = 'y';

		$controllerFile = strtolower(Inflector::underscore($controllerName));

		$question[] = __("Would you like to build your controller interactively?", true);
		if (file_exists($this->path . $controllerFile .'_controller.php')) {
			$question[] = sprintf(__("Warning: Choosing no will overwrite the %sController.", true), $controllerName);
		}
		$doItInteractive = $this->in(join("\n", $question), array('y', 'n'), 'y');

		if (strtolower($doItInteractive) == 'y') {
			$this->interactive = true;
			$useDynamicScaffold = $this->in(
				__("Would you like to use dynamic scaffolding?", true), array('y','n'), 'n'
			);

			if (strtolower($useDynamicScaffold) == 'y') {
				$wannaBakeCrud = 'n';
				$actions = 'scaffold';
			} else {
				list($wannaBakeCrud, $wannaBakeCrud) = $this->_askAboutMethods();
				
				$helpers = $this->doHelpers();
				$components = $this->doComponents();

				$wannaUseSession = $this->in(
					__("Would you like to use Session flash messages?", true), array('y','n'), 'y'
				);
			}
		} else {
			list($wannaBakeCrud, $wannaBakeCrud) = $this->_askAboutMethods();
		}

		if (strtolower($wannaBakeCrud) == 'y') {
			$actions = $this->bakeActions($controllerName, null, strtolower($wannaUseSession) == 'y');
		}
		if (strtolower($wannaBakeAdminCrud) == 'y') {
			$admin = $this->getAdmin();
			$actions .= $this->bakeActions($controllerName, $admin, strtolower($wannaUseSession) == 'y');
		}

		if ($this->interactive === true) {
			$this->confirmController($controllerName, $useDynamicScaffold, $uses, $helpers, $components);
			$looksGood = $this->in(__('Look okay?', true), array('y','n'), 'y');

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
	}

/**
 * Confirm a to be baked controller with the user
 *
 * @return void
 **/
	function confirmController($controllerName, $useDynamicScaffold, $helpers, $components) {
		$this->out('');
		$this->hr();
		$this->out('The following controller will be created:');
		$this->hr();
		$this->out("Controller Name:\n\t$controllerName");

		if (strtolower($useDynamicScaffold) == 'y') {
			$this->out("var \$scaffold;");
		}

		$properties = array(
			'helpers' => __("Helpers:", true),
			'components' => __('Components:', true),
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
 **/
	function _askAboutMethods() {
		$wannaBakeCrud = $this->in(
			__("Would you like to create some basic class methods \n(index(), add(), view(), edit())?", true),
			array('y','n'), 'n'
		);
		$wannaBakeAdminCrud = $this->in(
			__("Would you like to create the basic class methods for admin routing?", true), 
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
	function bakeActions($controllerName, $admin = null, $wannaUseSession = true) {
		$currentModelName = $this->_modelName($controllerName);
		if (!App::import('Model', $currentModelName)) {
			$this->err(__('You must have a model for this class to build basic methods. Please try again.', true));
			$this->_stop();
		}
		$actions = null;
		$modelObj =& ClassRegistry::init($currentModelName);
		$controllerPath = $this->_controllerPath($controllerName);
		$pluralName = $this->_pluralName($currentModelName);
		$singularName = Inflector::variable($currentModelName);
		$singularHumanName = Inflector::humanize($currentModelName);
		$pluralHumanName = Inflector::humanize($controllerName);

		$actions .= "\n";
		$actions .= "\tfunction {$admin}index() {\n";
		$actions .= "\t\t\$this->{$currentModelName}->recursive = 0;\n";
		$actions .= "\t\t\$this->set('{$pluralName}', \$this->paginate());\n";
		$actions .= "\t}\n";
		$actions .= "\n";
		$actions .= "\tfunction {$admin}view(\$id = null) {\n";
		$actions .= "\t\tif (!\$id) {\n";
		if ($wannaUseSession) {
			$actions .= "\t\t\t\$this->Session->setFlash(__('Invalid {$singularHumanName}.', true));\n";
			$actions .= "\t\t\t\$this->redirect(array('action'=>'index'));\n";
		} else {
			$actions .= "\t\t\t\$this->flash(__('Invalid {$singularHumanName}', true), array('action'=>'index'));\n";
		}
		$actions .= "\t\t}\n";
		$actions .= "\t\t\$this->set('".$singularName."', \$this->{$currentModelName}->read(null, \$id));\n";
		$actions .= "\t}\n";
		$actions .= "\n";

		/* ADD ACTION */
		$compact = array();
		$actions .= "\tfunction {$admin}add() {\n";
		$actions .= "\t\tif (!empty(\$this->data)) {\n";
		$actions .= "\t\t\t\$this->{$currentModelName}->create();\n";
		$actions .= "\t\t\tif (\$this->{$currentModelName}->save(\$this->data)) {\n";
		if ($wannaUseSession) {
			$actions .= "\t\t\t\t\$this->Session->setFlash(__('The ".$singularHumanName." has been saved', true));\n";
			$actions .= "\t\t\t\t\$this->redirect(array('action'=>'index'));\n";
		} else {
			$actions .= "\t\t\t\t\$this->flash(__('{$currentModelName} saved.', true), array('action'=>'index'));\n";
		}
		$actions .= "\t\t\t} else {\n";
		if ($wannaUseSession) {
			$actions .= "\t\t\t\t\$this->Session->setFlash(__('The {$singularHumanName} could not be saved. Please, try again.', true));\n";
		}
		$actions .= "\t\t\t}\n";
		$actions .= "\t\t}\n";
		foreach ($modelObj->hasAndBelongsToMany as $associationName => $relation) {
			if (!empty($associationName)) {
				$habtmModelName = $this->_modelName($associationName);
				$habtmSingularName = $this->_singularName($associationName);
				$habtmPluralName = $this->_pluralName($associationName);
				$actions .= "\t\t\${$habtmPluralName} = \$this->{$currentModelName}->{$habtmModelName}->find('list');\n";
				$compact[] = "'{$habtmPluralName}'";
			}
		}
		foreach ($modelObj->belongsTo as $associationName => $relation) {
			if (!empty($associationName)) {
				$belongsToModelName = $this->_modelName($associationName);
				$belongsToPluralName = $this->_pluralName($associationName);
				$actions .= "\t\t\${$belongsToPluralName} = \$this->{$currentModelName}->{$belongsToModelName}->find('list');\n";
				$compact[] = "'{$belongsToPluralName}'";
			}
		}
		if (!empty($compact)) {
			$actions .= "\t\t\$this->set(compact(".join(', ', $compact)."));\n";
		}
		$actions .= "\t}\n";
		$actions .= "\n";

		/* EDIT ACTION */
		$compact = array();
		$actions .= "\tfunction {$admin}edit(\$id = null) {\n";
		$actions .= "\t\tif (!\$id && empty(\$this->data)) {\n";
		if ($wannaUseSession) {
			$actions .= "\t\t\t\$this->Session->setFlash(__('Invalid {$singularHumanName}', true));\n";
			$actions .= "\t\t\t\$this->redirect(array('action'=>'index'));\n";
		} else {
			$actions .= "\t\t\t\$this->flash(__('Invalid {$singularHumanName}', true), array('action'=>'index'));\n";
		}
		$actions .= "\t\t}\n";
		$actions .= "\t\tif (!empty(\$this->data)) {\n";
		$actions .= "\t\t\tif (\$this->{$currentModelName}->save(\$this->data)) {\n";
		if ($wannaUseSession) {
			$actions .= "\t\t\t\t\$this->Session->setFlash(__('The ".$singularHumanName." has been saved', true));\n";
			$actions .= "\t\t\t\t\$this->redirect(array('action'=>'index'));\n";
		} else {
			$actions .= "\t\t\t\t\$this->flash(__('The ".$singularHumanName." has been saved.', true), array('action'=>'index'));\n";
		}
		$actions .= "\t\t\t} else {\n";
		if ($wannaUseSession) {
			$actions .= "\t\t\t\t\$this->Session->setFlash(__('The {$singularHumanName} could not be saved. Please, try again.', true));\n";
		}
		$actions .= "\t\t\t}\n";
		$actions .= "\t\t}\n";
		$actions .= "\t\tif (empty(\$this->data)) {\n";
		$actions .= "\t\t\t\$this->data = \$this->{$currentModelName}->read(null, \$id);\n";
		$actions .= "\t\t}\n";

		foreach ($modelObj->hasAndBelongsToMany as $associationName => $relation) {
			if (!empty($associationName)) {
				$habtmModelName = $this->_modelName($associationName);
				$habtmSingularName = $this->_singularName($associationName);
				$habtmPluralName = $this->_pluralName($associationName);
				$actions .= "\t\t\${$habtmPluralName} = \$this->{$currentModelName}->{$habtmModelName}->find('list');\n";
				$compact[] = "'{$habtmPluralName}'";
			}
		}
		foreach ($modelObj->belongsTo as $associationName => $relation) {
			if (!empty($associationName)) {
				$belongsToModelName = $this->_modelName($associationName);
				$belongsToPluralName = $this->_pluralName($associationName);
				$actions .= "\t\t\${$belongsToPluralName} = \$this->{$currentModelName}->{$belongsToModelName}->find('list');\n";
				$compact[] = "'{$belongsToPluralName}'";
			}
		}
		if (!empty($compact)) {
			$actions .= "\t\t\$this->set(compact(".join(',', $compact)."));\n";
		}
		$actions .= "\t}\n";
		$actions .= "\n";
		$actions .= "\tfunction {$admin}delete(\$id = null) {\n";
		$actions .= "\t\tif (!\$id) {\n";
		if ($wannaUseSession) {
			$actions .= "\t\t\t\$this->Session->setFlash(__('Invalid id for {$singularHumanName}', true));\n";
			$actions .= "\t\t\t\$this->redirect(array('action'=>'index'));\n";
		} else {
			$actions .= "\t\t\t\$this->flash(__('Invalid {$singularHumanName}', true), array('action'=>'index'));\n";
		}
		$actions .= "\t\t}\n";
		$actions .= "\t\tif (\$this->{$currentModelName}->del(\$id)) {\n";
		if ($wannaUseSession) {
			$actions .= "\t\t\t\$this->Session->setFlash(__('{$singularHumanName} deleted', true));\n";
			$actions .= "\t\t\t\$this->redirect(array('action'=>'index'));\n";
		} else {
			$actions .= "\t\t\t\$this->flash(__('{$singularHumanName} deleted', true), array('action'=>'index'));\n";
		}
		$actions .= "\t\t}\n";
		$actions .= "\t}\n";
		$actions .= "\n";
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
 * @access private
 */
	function bake($controllerName, $actions = '', $helpers = null, $components = null) {
		$isScaffold = ($actions === 'scaffold') ? true : false;

		$this->Template->set('plugin', $this->plugin);
		$this->Template->set(compact('controllerName', 'actions', 'helpers', 'components', 'isScaffold'));
		$contents = $this->Template->generate('objects', 'controller');

		$filename = $this->path . $this->_controllerPath($controllerName) . '_controller.php';
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
 * @access private
 */
	function bakeTest($className) {
		$import = $className;
		if ($this->plugin) {
			$import = $this->plugin . '.' . $className;
		}
		$out = "App::import('Controller', '$import');\n\n";
		$out .= "class Test{$className} extends {$className}Controller {\n";
		$out .= "\tvar \$autoRender = false;\n}\n\n";
		$out .= "class {$className}ControllerTest extends CakeTestCase {\n";
		$out .= "\tvar \${$className} = null;\n\n";
		$out .= "\tfunction startTest() {\n\t\t\$this->{$className} = new Test{$className}();";
		$out .= "\n\t\t\$this->{$className}->constructClasses();\n\t}\n\n";
		$out .= "\tfunction test{$className}ControllerInstance() {\n";
		$out .= "\t\t\$this->assertTrue(is_a(\$this->{$className}, '{$className}Controller'));\n\t}\n\n";
		$out .= "\tfunction endTest() {\n\t\tunset(\$this->{$className});\n\t}\n}\n";

		$path = CONTROLLER_TESTS;
		if (isset($this->plugin)) {
			$pluginPath = 'plugins' . DS . Inflector::underscore($this->plugin) . DS;
			$path = APP . $pluginPath . 'tests' . DS . 'cases' . DS . 'controllers' . DS;
		}

		$filename = Inflector::underscore($className).'_controller.test.php';
		$this->out("\nBaking unit test for $className...");

		$header = '$Id';
		$content = "<?php \n/* SVN FILE: $header$ */\n/* ". $className ."Controller Test cases generated on: " . date('Y-m-d H:m:s') . " : ". time() . "*/\n{$out}?>";
		return $this->createFile($path . $filename, $content);
	}

/**
 * Interact with the user and get a list of additional helpers
 *
 * @return array Helpers that the user wants to use.
 **/
	function doHelpers() {
		return $this->_doPropertyChoices(
			__("Would you like this controller to use other helpers\nbesides HtmlHelper and FormHelper?", true),
			__("Please provide a comma separated list of the other\nhelper names you'd like to use.\nExample: 'Ajax, Javascript, Time'", true)
		);
	}

/**
 * Interact with the user and get a list of additional components
 *
 * @return array Components the user wants to use.
 **/
	function doComponents() {
		return $this->_doPropertyChoices(
			__("Would you like this controller to use any components?", true),
			__("Please provide a comma separated list of the component names you'd like to use.\nExample: 'Acl, Security, RequestHandler'", true)
		);
	}

/**
 * Common code for property choice handling.
 *
 * @param string $prompt A yes/no question to precede the list
 * @param sting $example A question for a comma separated list, with examples.
 * @return array Array of values for property.
 **/
	function _doPropertyChoices($prompt, $example) {
		$proceed = $this->in($prompt, array('y','n'), 'n');
		$property = array();
		if (strtolower($proceed) == 'y') {
			$propertyList = $this->in($example);
			$propertyListTrimmed = str_replace(' ', '', $propertyList);
			$property = explode(',', $propertyListTrimmed);
		}
		return $property;
	}

/**
 * Outputs and gets the list of possible controllers from database
 *
 * @param string $useDbConfig Database configuration name
 * @param boolean $interactive Whether you are using listAll interactively and want options output.
 * @return array Set of controllers
 * @access public
 */
	function listAll($useDbConfig = null) {
		if (is_null($useDbConfig)) {
			$useDbConfig = $this->connection;
		}
		$this->__tables = $this->Model->getAllTables($useDbConfig);

		if ($this->interactive == true) {
			$this->out('Possible Controllers based on your current database:');
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
 * @access public
 */
	function getName($useDbConfig = null) {
		$controllers = $this->listAll($useDbConfig);
		$enteredController = '';

		while ($enteredController == '') {
			$enteredController = $this->in(__("Enter a number from the list above,\ntype in the name of another controller, or 'q' to exit", true), null, 'q');

			if ($enteredController === 'q') {
				$this->out(__("Exit", true));
				$this->_stop();
			}

			if ($enteredController == '' || intval($enteredController) > count($controllers)) {
				$this->err(__("The Controller name you supplied was empty,\nor the number you selected was not an option. Please try again.", true));
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
 * Displays help contents
 *
 * @access public
 */
	function help() {
		$this->hr();
		$this->out("Usage: cake bake controller <arg1> <arg2>...");
		$this->hr();
		$this->out('Commands:');
		$this->out("\n\tcontroller <name>\n\t\tbakes controller with var \$scaffold");
		$this->out("\n\tcontroller <name> scaffold\n\t\tbakes controller with scaffold actions.\n\t\t(index, view, add, edit, delete)");
		$this->out("\n\tcontroller <name> scaffold admin\n\t\tbakes a controller with scaffold actions for both public and Configure::read('Routing.admin')");
		$this->out("\n\tcontroller <name> admin\n\t\tbakes a controller with scaffold actions only for Configure::read('Routing.admin')");
		$this->out("\n\tcontroller all\n\t\tbakes all controllers with CRUD methods.");
		$this->out("");
		$this->_stop();
	}
}
?>
