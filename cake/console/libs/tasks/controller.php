<?php
/* SVN FILE: $Id$ */
/**
 * The ControllerTask handles creating and updating controller files.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010,	Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
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
	var $tasks = array('Project');
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
 * Interactive
 *
 * @access private
 */
	function __interactive($controllerName = false) {
		if (!$controllerName) {
			$this->interactive = true;
			$this->hr();
			$this->out(sprintf("Bake Controller\nPath: %s", $this->path));
			$this->hr();
			$actions = '';
			$uses = array();
			$helpers = array();
			$components = array();
			$wannaUseSession = 'y';
			$wannaDoAdmin = 'n';
			$wannaUseScaffold = 'n';
			$wannaDoScaffolding = 'y';
			$controllerName = $this->getName();
		}
		$this->hr();
		$this->out("Baking {$controllerName}Controller");
		$this->hr();

		$controllerFile = strtolower(Inflector::underscore($controllerName));

		$question[] = __("Would you like to build your controller interactively?", true);
		if (file_exists($this->path . $controllerFile .'_controller.php')) {
			$question[] = sprintf(__("Warning: Choosing no will overwrite the %sController.", true), $controllerName);
		}
		$doItInteractive = $this->in(implode("\n", $question), array('y','n'), 'y');

		if (strtolower($doItInteractive) == 'y' || strtolower($doItInteractive) == 'yes') {
			$this->interactive = true;

			$wannaUseScaffold = $this->in(__("Would you like to use scaffolding?", true), array('y','n'), 'n');

			if (strtolower($wannaUseScaffold) == 'n' || strtolower($wannaUseScaffold) == 'no') {

				$wannaDoScaffolding = $this->in(__("Would you like to include some basic class methods (index(), add(), view(), edit())?", true), array('y','n'), 'n');

				if (strtolower($wannaDoScaffolding) == 'y' || strtolower($wannaDoScaffolding) == 'yes') {
					$wannaDoAdmin = $this->in(__("Would you like to create the methods for admin routing?", true), array('y','n'), 'n');
				}

				$wannaDoHelpers = $this->in(__("Would you like this controller to use other helpers besides HtmlHelper and FormHelper?", true), array('y','n'), 'n');

				if (strtolower($wannaDoHelpers) == 'y' || strtolower($wannaDoHelpers) == 'yes') {
					$helpersList = $this->in(__("Please provide a comma separated list of the other helper names you'd like to use.\nExample: 'Ajax, Javascript, Time'", true));
					$helpersListTrimmed = str_replace(' ', '', $helpersList);
					$helpers = explode(',', $helpersListTrimmed);
				}
				$wannaDoComponents = $this->in(__("Would you like this controller to use any components?", true), array('y','n'), 'n');

				if (strtolower($wannaDoComponents) == 'y' || strtolower($wannaDoComponents) == 'yes') {
					$componentsList = $this->in(__("Please provide a comma separated list of the component names you'd like to use.\nExample: 'Acl, Security, RequestHandler'", true));
					$componentsListTrimmed = str_replace(' ', '', $componentsList);
					$components = explode(',', $componentsListTrimmed);
				}

				$wannaUseSession = $this->in(__("Would you like to use Sessions?", true), array('y','n'), 'y');
			} else {
				$wannaDoScaffolding = 'n';
			}
		} else {
			$wannaDoScaffolding = $this->in(__("Would you like to include some basic class methods (index(), add(), view(), edit())?", true), array('y','n'), 'y');

			if (strtolower($wannaDoScaffolding) == 'y' || strtolower($wannaDoScaffolding) == 'yes') {
				$wannaDoAdmin = $this->in(__("Would you like to create the methods for admin routing?", true), array('y','n'), 'y');
			}
		}
		$admin = false;

		if ((strtolower($wannaDoAdmin) == 'y' || strtolower($wannaDoAdmin) == 'yes')) {
			$admin = $this->getAdmin();
		}

		if (strtolower($wannaDoScaffolding) == 'y' || strtolower($wannaDoScaffolding) == 'yes') {
			$actions = $this->bakeActions($controllerName, null, in_array(strtolower($wannaUseSession), array('y', 'yes')));
			if ($admin) {
				$actions .= $this->bakeActions($controllerName, $admin, in_array(strtolower($wannaUseSession), array('y', 'yes')));
			}
		}

		if ($this->interactive === true) {
			$this->out('');
			$this->hr();
			$this->out('The following controller will be created:');
			$this->hr();
			$this->out("Controller Name:  $controllerName");

			if (strtolower($wannaUseScaffold) == 'y' || strtolower($wannaUseScaffold) == 'yes') {
				$this->out("		   var \$scaffold;");
				$actions = 'scaffold';
			}

			if (count($helpers)) {
				$this->out("Helpers:      ", false);

				foreach ($helpers as $help) {
					if ($help != $helpers[count($helpers) - 1]) {
						$this->out(ucfirst($help) . ", ", false);
					} else {
						$this->out(ucfirst($help));
					}
				}
			}

			if (count($components)) {
				$this->out("Components:      ", false);

				foreach ($components as $comp) {
					if ($comp != $components[count($components) - 1]) {
						$this->out(ucfirst($comp) . ", ", false);
					} else {
						$this->out(ucfirst($comp));
					}
				}
			}
			$this->hr();
			$looksGood = $this->in(__('Look okay?', true), array('y','n'), 'y');

			if (strtolower($looksGood) == 'y' || strtolower($looksGood) == 'yes') {
				$baked = $this->bake($controllerName, $actions, $helpers, $components, $uses);
				if ($baked && $this->_checkUnitTest()) {
					$this->bakeTest($controllerName);
				}
			} else {
				$this->__interactive($controllerName);
			}
		} else {
			$baked = $this->bake($controllerName, $actions, $helpers, $components, $uses);
			if ($baked && $this->_checkUnitTest()) {
				$this->bakeTest($controllerName);
			}
		}
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
		$currentModelName = $modelImport = $this->_modelName($controllerName);
		if ($this->plugin) {
			$modelImport = $this->plugin . '.' . $modelImport;
		}
		if (!App::import('Model', $modelImport)) {
			$this->err(__('You must have a model for this class to build scaffold methods. Please try again.', true));
			exit;
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
			$actions .= "\t\t\t\$this->Session->setFlash(__('Invalid {$singularHumanName}', true));\n";
			$actions .= "\t\t\t\$this->redirect(array('action' => 'index'));\n";
		} else {
			$actions .= "\t\t\t\$this->flash(__('Invalid {$singularHumanName}', true), array('action' => 'index'));\n";
		}
		$actions .= "\t\t}\n";
		$actions .= "\t\t\$this->set('" . $singularName . "', \$this->{$currentModelName}->read(null, \$id));\n";
		$actions .= "\t}\n";
		$actions .= "\n";

		/* ADD ACTION */
		$compact = array();
		$actions .= "\tfunction {$admin}add() {\n";
		$actions .= "\t\tif (!empty(\$this->data)) {\n";
		$actions .= "\t\t\t\$this->{$currentModelName}->create();\n";
		$actions .= "\t\t\tif (\$this->{$currentModelName}->save(\$this->data)) {\n";
		if ($wannaUseSession) {
			$actions .= "\t\t\t\t\$this->Session->setFlash(__('The " . $singularHumanName . " has been saved', true));\n";
			$actions .= "\t\t\t\t\$this->redirect(array('action' => 'index'));\n";
		} else {
			$actions .= "\t\t\t\t\$this->flash(__('{$currentModelName} saved.', true), array('action' => 'index'));\n";
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
			$actions .= "\t\t\$this->set(compact(" . implode(', ', $compact) . "));\n";
		}
		$actions .= "\t}\n";
		$actions .= "\n";

		/* EDIT ACTION */
		$compact = array();
		$actions .= "\tfunction {$admin}edit(\$id = null) {\n";
		$actions .= "\t\tif (!\$id && empty(\$this->data)) {\n";
		if ($wannaUseSession) {
			$actions .= "\t\t\t\$this->Session->setFlash(__('Invalid {$singularHumanName}', true));\n";
			$actions .= "\t\t\t\$this->redirect(array('action' => 'index'));\n";
		} else {
			$actions .= "\t\t\t\$this->flash(__('Invalid {$singularHumanName}', true), array('action' => 'index'));\n";
		}
		$actions .= "\t\t}\n";
		$actions .= "\t\tif (!empty(\$this->data)) {\n";
		$actions .= "\t\t\tif (\$this->{$currentModelName}->save(\$this->data)) {\n";
		if ($wannaUseSession) {
			$actions .= "\t\t\t\t\$this->Session->setFlash(__('The " . $singularHumanName . " has been saved', true));\n";
			$actions .= "\t\t\t\t\$this->redirect(array('action' => 'index'));\n";
		} else {
			$actions .= "\t\t\t\t\$this->flash(__('The " . $singularHumanName . " has been saved.', true), array('action' => 'index'));\n";
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
			$actions .= "\t\t\$this->set(compact(" . implode(',', $compact) . "));\n";
		}
		$actions .= "\t}\n";
		$actions .= "\n";
		$actions .= "\tfunction {$admin}delete(\$id = null) {\n";
		$actions .= "\t\tif (!\$id) {\n";
		if ($wannaUseSession) {
			$actions .= "\t\t\t\$this->Session->setFlash(__('Invalid id for {$singularHumanName}', true));\n";
			$actions .= "\t\t\t\$this->redirect(array('action' => 'index'));\n";
		} else {
			$actions .= "\t\t\t\$this->flash(__('Invalid {$singularHumanName}', true), array('action' => 'index'));\n";
		}
		$actions .= "\t\t}\n";
		$actions .= "\t\tif (\$this->{$currentModelName}->del(\$id)) {\n";
		if ($wannaUseSession) {
			$actions .= "\t\t\t\$this->Session->setFlash(__('{$singularHumanName} deleted', true));\n";
			$actions .= "\t\t\t\$this->redirect(array('action' => 'index'));\n";
		} else {
			$actions .= "\t\t\t\$this->flash(__('{$singularHumanName} deleted', true), array('action' => 'index'));\n";
		}
		$actions .= "\t\t}\n";
		if ($wannaUseSession) {
			$actions .= "\t\t\$this->Session->setFlash(__('The {$singularHumanName} could not be deleted. Please, try again.', true));\n";
			$actions .= "\t\t\$this->redirect(array('action' => 'index'));\n";
		} else {
			$actions .= "\t\t\$this->flash(__('The {$singularHumanName} could not be deleted. Please, try again.', true), array('action' => 'index'));\n";
		}
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
	function bake($controllerName, $actions = '', $helpers = null, $components = null, $uses = null) {
		$out = "<?php\n";
		$out .= "class $controllerName" . "Controller extends {$this->plugin}AppController {\n\n";
		$out .= "\tvar \$name = '$controllerName';\n";

		if (strtolower($actions) == 'scaffold') {
			$out .= "\tvar \$scaffold;\n";
		} else {
			if (count($uses)) {
				$out .= "\tvar \$uses = array('" . $this->_modelName($controllerName) . "', ";

				foreach ($uses as $use) {
					if ($use != $uses[count($uses) - 1]) {
						$out .= "'" . $this->_modelName($use) . "', ";
					} else {
						$out .= "'" . $this->_modelName($use) . "'";
					}
				}
				$out .= ");\n";
			}

			$out .= "\tvar \$helpers = array('Html', 'Form'";
			if (count($helpers)) {
				foreach ($helpers as $help) {
					$out .= ", '" . Inflector::camelize($help) . "'";
				}
			}
			$out .= ");\n";

			if (count($components)) {
				$out .= "\tvar \$components = array(";

				foreach ($components as $comp) {
					if ($comp != $components[count($components) - 1]) {
						$out .= "'" . Inflector::camelize($comp) . "', ";
					} else {
						$out .= "'" . Inflector::camelize($comp) . "'";
					}
				}
				$out .= ");\n";
			}
			$out .= $actions;
		}
		$out .= "}\n";
		$out .= "?>";
		$filename = $this->path . $this->_controllerPath($controllerName) . '_controller.php';
		return $this->createFile($filename, $out);
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
		$content = "<?php \n/* SVN FILE: $header$ */\n/* " . $className . "Controller Test cases generated on: " . date('Y-m-d H:i:s') . " : ". time() . "*/\n{$out}?>";
		return $this->createFile($path . $filename, $content);
	}
/**
 * Outputs and gets the list of possible models or controllers from database
 *
 * @param string $useDbConfig Database configuration name
 * @return array Set of controllers
 * @access public
 */
	function listAll($useDbConfig = 'default') {
		$db =& ConnectionManager::getDataSource($useDbConfig);
		$usePrefix = empty($db->config['prefix']) ? '' : $db->config['prefix'];
		if ($usePrefix) {
			$tables = array();
			foreach ($db->listSources() as $table) {
				if (!strncmp($table, $usePrefix, strlen($usePrefix))) {
					$tables[] = substr($table, strlen($usePrefix));
				}
			}
		} else {
			$tables = $db->listSources();
		}

		if (empty($tables)) {
			$this->err(__('Your database does not have any tables.', true));
			$this->_stop();
		}

		$this->__tables = $tables;
		$this->out('Possible Controllers based on your current database:');
		$this->_controllerNames = array();
		$count = count($tables);
		for ($i = 0; $i < $count; $i++) {
			$this->_controllerNames[] = $this->_controllerName($this->_modelName($tables[$i]));
			$this->out($i + 1 . ". " . $this->_controllerNames[$i]);
		}
		return $this->_controllerNames;
	}

/**
 * Forces the user to specify the controller he wants to bake, and returns the selected controller name.
 *
 * @return string Controller name
 * @access public
 */
	function getName() {
		$useDbConfig = 'default';
		$controllers = $this->listAll($useDbConfig, 'Controllers');
		$enteredController = '';

		while ($enteredController == '') {
			$enteredController = $this->in(__("Enter a number from the list above, type in the name of another controller, or 'q' to exit", true), null, 'q');

			if ($enteredController === 'q') {
				$this->out(__("Exit", true));
				$this->_stop();
			}

			if ($enteredController == '' || intval($enteredController) > count($controllers)) {
				$this->out(__('Error:', true));
				$this->out(__("The Controller name you supplied was empty, or the number \nyou selected was not an option. Please try again.", true));
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
		$this->out("");
		$this->_stop();
	}
}
?>