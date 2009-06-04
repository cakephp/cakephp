<?php
/* SVN FILE: $Id$ */
/**
 * The View Tasks handles creating and updating view files.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
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
App::import('Core', 'Controller');
/**
 * Task class for creating and updating view files.
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 */
class ViewTask extends Shell {
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
	var $tasks = array('Project', 'Controller', 'DbConfig', 'Template');
/**
 * path to VIEWS directory
 *
 * @var array
 * @access public
 */
	var $path = VIEWS;
/**
 * Name of the controller being used
 *
 * @var string
 * @access public
 */
	var $controllerName = null;
/**
 * Path to controller to put views
 *
 * @var string
 * @access public
 */
	var $controllerPath = null;
/**
 * The template file to use
 *
 * @var string
 * @access public
 */
	var $template = null;
/**
 * Actions to use for scaffolding
 *
 * @var array
 * @access public
 */
	var $scaffoldActions = array('index', 'view', 'add', 'edit');
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
			$controller = $action = $alias = null;
			$this->controllerName = Inflector::camelize($this->args[0]);
			$this->controllerPath = Inflector::underscore($this->controllerName);

			if (isset($this->args[1])) {
				$this->template = $this->args[1];
			}

			if (isset($this->args[2])) {
				$action = $this->args[2];
			}

			if (!$action) {
				$action = $this->template;
			}

			if (strtolower($this->args[0]) == 'all') {
				return $this->all();
			}

			if (in_array($action, $this->scaffoldActions)) {
				$this->bake($action, true);
			} elseif ($action) {
				$this->bake($action, true);
			} else {
				$vars = $this->__loadController();
				if ($vars) {

					$methods =  array_diff(
						array_map('strtolower', get_class_methods($this->controllerName . 'Controller')),
						array_map('strtolower', get_class_methods('appcontroller'))
					);
					if (empty($methods)) {
						$methods = $this->scaffoldActions;
					}
					$adminDelete = null;

					$adminRoute = Configure::read('Routing.admin');
					if (!empty($adminRoute)) {
						$adminDelete = $adminRoute.'_delete';
					}
					foreach ($methods as $method) {
						if ($method{0} != '_' && !in_array($method, array('delete', $adminDelete))) {
							$content = $this->getContent($method, $vars);
							$this->bake($method, $content);
						}
					}
				}
			}
		}
	}

/**
 * Bake All views for All controllers.
 *
 * @return void
 **/
	function all() {
		$actions = $this->scaffoldActions;
		$tables = $this->Controller->listAll($this->connection, false);
		$this->interactive = false;
		foreach ($tables as $table) {
			$model = $this->_modelName($table);
			$this->controllerName = $this->_controllerName($model);
			$this->controllerPath = Inflector::underscore($this->controllerName);
			if (App::import('Model', $model)) {
				$vars = $this->__loadController();
				if ($vars) {
					$this->bakeActions($actions, $vars);
				}
			}
		}
	}

/**
 * Handles interactive baking
 *
 * @access private
 */
	function __interactive() {
		$this->hr();
		$this->out(sprintf("Bake View\nPath: %s", $this->path));
		$this->hr();

		if (empty($this->connection)) {
			$this->connection = $this->DbConfig->getConfig();
		}

		$wannaDoAdmin = 'n';
		$wannaDoScaffold = 'y';
		$admin = false;

		$this->Controller->connection = $this->connection;
		$this->controllerName = $this->Controller->getName();

		$this->controllerPath = strtolower(Inflector::underscore($this->controllerName));

		$prompt = sprintf(__("Would you like bake to build your views interactively?\nWarning: Choosing no will overwrite %s views if it exist.", true),  $this->controllerName);
		$interactive = $this->in($prompt, array('y', 'n'), 'n');

		if (strtolower($interactive) == 'n') {
			$this->interactive = false;
		}

		$prompt = __("Would you like to create some CRUD views\n(index, add, view, edit) for this controller?\nNOTE: Before doing so, you'll need to create your controller\nand model classes (including associated models).", true);
		$wannaDoScaffold = $this->in($prompt, array('y','n'), 'n');

		if (strtolower($wannaDoScaffold) == 'y') {
			$wannaDoAdmin = $this->in(__("Would you like to create the views for admin routing?", true), array('y','n'), 'y');
		}

		if (strtolower($wannaDoAdmin) == 'y') {
			$admin = $this->getAdmin();
		}

		if (strtolower($wannaDoScaffold) == 'y') {
			$actions = $this->scaffoldActions;
			if ($admin) {
				foreach ($actions as $action) {
					$actions[] = $admin . $action;
				}
			}
			$vars = $this->__loadController();
			if ($vars) {
				$this->bakeActions($actions, $vars);
			}
			$this->hr();
			$this->out('');
			$this->out(__("View Scaffolding Complete.\n", true));
		} else {
			$this->customAction();
		}
	}

/**
 * Loads Controller and sets variables for the template
 * Available template variables
 *	'modelClass', 'primaryKey', 'displayField', 'singularVar', 'pluralVar',
 *	'singularHumanName', 'pluralHumanName', 'fields', 'foreignKeys',
 *	'belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany'
 *
 * @return array Returns an variables to be made available to a view template
 * @access private
 */
	function __loadController() {
		if (!$this->controllerName) {
			$this->err(__('Controller not found', true));
		}

		$import = $this->controllerName;
		if ($this->plugin) {
			$import = $this->plugin . '.' . $this->controllerName;
		}

		if (!App::import('Controller', $import)) {
			$file = $this->controllerPath . '_controller.php';
			$this->err(sprintf(__("The file '%s' could not be found.\nIn order to bake a view, you'll need to first create the controller.", true), $file));
			$this->_stop();
		}
		$controllerClassName = $this->controllerName . 'Controller';
		$controllerObj =& new $controllerClassName();
		$controllerObj->constructClasses();
		$modelClass = $controllerObj->modelClass;
		$modelObj =& ClassRegistry::getObject($controllerObj->modelKey);

		if ($modelObj) {
			$primaryKey = $modelObj->primaryKey;
			$displayField = $modelObj->displayField;
			$singularVar = Inflector::variable($modelClass);
			$pluralVar = Inflector::variable($this->controllerName);
			$singularHumanName = Inflector::humanize($modelClass);
			$pluralHumanName = Inflector::humanize($this->controllerName);
			$schema = $modelObj->schema();
			$fields = array_keys($schema);
			$associations = $this->__associations($modelObj);
		} else {
			$primaryKey = null;
			$displayField = null;
			$singularVar = Inflector::variable(Inflector::singularize($this->controllerName));
			$pluralVar = Inflector::variable($this->controllerName);
			$singularHumanName = Inflector::humanize(Inflector::singularize($this->controllerName));
			$pluralHumanName = Inflector::humanize($this->controllerName);
			$fields = array();
			$schema = array();
			$associations = array();
		}

		return compact('modelClass', 'schema', 'primaryKey', 'displayField', 'singularVar', 'pluralVar',
				'singularHumanName', 'pluralHumanName', 'fields','associations');
	}

/**
 * Bake a view file for each of the supplied actions
 *
 * @param array $actions Array of actions to make files for.
 * @return void
 **/
	function bakeActions($actions, $vars) {
		foreach ($actions as $action) {
			$content = $this->getContent($action, $vars);
			$this->bake($action, $content);
		}
	}

/**
 * handle creation of baking a custom action view file
 *
 * @return void
 **/
	function customAction() {
		$action = '';
		while ($action == '') {
			$action = $this->in(__('Action Name? (use lowercase_underscored function name)', true));
			if ($action == '') {
				$this->out(__('The action name you supplied was empty. Please try again.', true));
			}
		}
		$this->out('');
		$this->hr();
		$this->out(__('The following view will be created:', true));
		$this->hr();
		$this->out(sprintf(__('Controller Name: %s', true), $this->controllerName));
		$this->out(sprintf(__('Action Name:     %s', true), $action));
		$this->out(sprintf(__('Path:            %s', true), $this->params['app'] . DS . $this->controllerPath . DS . Inflector::underscore($action) . ".ctp"));
		$this->hr();
		$looksGood = $this->in(__('Look okay?', true), array('y','n'), 'y');
		if (strtolower($looksGood) == 'y') {
			$this->bake($action);
			$this->_stop();
		} else {
			$this->out(__('Bake Aborted.', true));
		}
	}

/**
 * Assembles and writes bakes the view file.
 *
 * @param string $action Action to bake
 * @param string $content Content to write
 * @return boolean Success
 * @access public
 */
	function bake($action, $content = '') {
		if ($content === true) {
			$content = $this->getContent();
		}
		$filename = $this->path . $this->controllerPath . DS . Inflector::underscore($action) . '.ctp';
		$Folder =& new Folder($this->path . $this->controllerPath, true);
		$errors = $Folder->errors();
		if (empty($errors)) {
			return $this->createFile($filename, $content);
		} else {
			foreach ($errors as $error) {
				$this->err($error);
			}
		}
		return false;
	}

/**
 * Builds content from template and variables
 *
 * @param string $template file to use
 * @param array $vars passed for use in templates
 * @return string content from template
 * @access public
 */
	function getContent($template = null, $vars = null) {
		if (!$template) {
			$template = $this->template;
		}
		$action = $template;

		$adminRoute = Configure::read('Routing.admin');
		if (!empty($adminRoute) && strpos($template, $adminRoute) !== false) {
			$template = str_replace($adminRoute.'_', '', $template);
		}
		if (in_array($template, array('add', 'edit'))) {
			$action = $template;
			$template = 'form';
		}
		if (!$vars) {
			$vars = $this->__loadController();
		}

		$this->Template->set('action', $action);
		$this->Template->set('plugin', $this->plugin);
		$this->Template->set($vars);
		$output = $this->Template->generate('views', $template);

		if (!empty($output)) {
			return $output;
		}
		$this->hr();
		$this->err(sprintf(__('Template for %s could not be found', true), $template));
		return false;
	}

/**
 * Displays help contents
 *
 * @access public
 */
	function help() {
		$this->hr();
		$this->out("Usage: cake bake view <arg1> <arg2>...");
		$this->hr();
		$this->out('Commands:');
		$this->out('');
		$this->out("view <controller>");
		$this->out("\twill read the given controller for methods");
		$this->out("\tand bake corresponding views.");
		$this->out("\tIf var scaffold is found it will bake the scaffolded actions");
		$this->out("\t(index,view,add,edit)");
		$this->out('');
		$this->out("view <controller> <action>");
		$this->out("\twill bake a template. core templates: (index, add, edit, view)");
		$this->out('');
		$this->out("view <controller> <template> <alias>");
		$this->out("\twill use the template specified");
		$this->out("\tbut name the file based on the alias");
		$this->out("");
		$this->_stop();
	}
/**
 * Returns associations for controllers models.
 *
 * @return  array $associations
 * @access private
 */
	function __associations($model) {
		$keys = array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany');
		$associations = array();

		foreach ($keys as $key => $type) {
			foreach ($model->{$type} as $assocKey => $assocData) {
				$associations[$type][$assocKey]['primaryKey'] = $model->{$assocKey}->primaryKey;
				$associations[$type][$assocKey]['displayField'] = $model->{$assocKey}->displayField;
				$associations[$type][$assocKey]['foreignKey'] = $assocData['foreignKey'];
				$associations[$type][$assocKey]['controller'] = Inflector::pluralize(Inflector::underscore($assocData['className']));
				$associations[$type][$assocKey]['fields'] =  array_keys($model->{$assocKey}->schema());
			}
		}
		return $associations;
	}
}

?>