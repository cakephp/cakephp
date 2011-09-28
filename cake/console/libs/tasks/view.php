<?php
/**
 * The View Tasks handles creating and updating view files.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 * @since         CakePHP(tm) v 1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Controller', 'Controller', false);
include_once dirname(__FILE__) . DS . 'bake.php';

/**
 * Task class for creating and updating view files.
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 */
class ViewTask extends BakeTask {

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
 * An array of action names that don't require templates.  These
 * actions will not emit errors when doing bakeActions()
 *
 * @var array
 * @access public
 */
	var $noTemplateActions = array('delete');

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
		if (empty($this->args[0])) {
			return;
		}
		if (!isset($this->connection)) {
			$this->connection = 'default';
		}
		$controller = $action = $alias = null;
		$this->controllerName = $this->_controllerName($this->args[0]);
		$this->controllerPath = $this->_controllerPath($this->controllerName);

		$this->Project->interactive = false;
		if (strtolower($this->args[0]) == 'all') {
			return $this->all();
		}

		if (isset($this->args[1])) {
			$this->template = $this->args[1];
		}
		if (isset($this->args[2])) {
			$action = $this->args[2];
		}
		if (!$action) {
			$action = $this->template;
		}
		if ($action) {
			return $this->bake($action, true);
		}

		$vars = $this->__loadController();
		$methods = $this->_methodsToBake();

		foreach ($methods as $method) {
			$content = $this->getContent($method, $vars);
			if ($content) {
				$this->bake($method, $content);
			}
		}
	}

/**
 * Get a list of actions that can / should have views baked for them.
 *
 * @return array Array of action names that should be baked
 */
	function _methodsToBake() {
		$methods =  array_diff(
			array_map('strtolower', get_class_methods($this->controllerName . 'Controller')),
			array_map('strtolower', get_class_methods('appcontroller'))
		);
		$scaffoldActions = false;
		if (empty($methods)) {
			$scaffoldActions = true;
			$methods = $this->scaffoldActions;
		}
		$adminRoute = $this->Project->getPrefix();
		foreach ($methods as $i => $method) {
			if ($adminRoute && isset($this->params['admin'])) {
				if ($scaffoldActions) {
					$methods[$i] = $adminRoute . $method;
					continue;
				} elseif (strpos($method, $adminRoute) === false) {
					unset($methods[$i]);
				}
			}
			if ($method[0] === '_' || $method == strtolower($this->controllerName . 'Controller')) {
				unset($methods[$i]);
			}
		}
		return $methods;
	}

/**
 * Bake All views for All controllers.
 *
 * @return void
 */
	function all() {
		$this->Controller->interactive = false;
		$tables = $this->Controller->listAll($this->connection, false);

		$actions = null;
		if (isset($this->args[1])) {
			$actions = array($this->args[1]);
		}
		$this->interactive = false;
		foreach ($tables as $table) {
			$model = $this->_modelName($table);
			$this->controllerName = $this->_controllerName($model);
			$this->controllerPath = Inflector::underscore($this->controllerName);
			if (App::import('Model', $model)) {
				$vars = $this->__loadController();
				if (!$actions) {
					$actions = $this->_methodsToBake();
				}
				$this->bakeActions($actions, $vars);
				$actions = null;
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

		$this->DbConfig->interactive = $this->Controller->interactive = $this->interactive = true;

		if (empty($this->connection)) {
			$this->connection = $this->DbConfig->getConfig();
		}

		$this->Controller->connection = $this->connection;
		$this->controllerName = $this->Controller->getName();

		$this->controllerPath = strtolower(Inflector::underscore($this->controllerName));

		$prompt = sprintf(__("Would you like bake to build your views interactively?\nWarning: Choosing no will overwrite %s views if it exist.", true),  $this->controllerName);
		$interactive = $this->in($prompt, array('y', 'n'), 'n');

		if (strtolower($interactive) == 'n') {
			$this->interactive = false;
		}

		$prompt = __("Would you like to create some CRUD views\n(index, add, view, edit) for this controller?\nNOTE: Before doing so, you'll need to create your controller\nand model classes (including associated models).", true);
		$wannaDoScaffold = $this->in($prompt, array('y','n'), 'y');

		$wannaDoAdmin = $this->in(__("Would you like to create the views for admin routing?", true), array('y','n'), 'n');

		if (strtolower($wannaDoScaffold) == 'y' || strtolower($wannaDoAdmin) == 'y') {
			$vars = $this->__loadController();
			if (strtolower($wannaDoScaffold) == 'y') {
				$actions = $this->scaffoldActions;
				$this->bakeActions($actions, $vars);
			}
			if (strtolower($wannaDoAdmin) == 'y') {
				$admin = $this->Project->getPrefix();
				$regularActions = $this->scaffoldActions;
				$adminActions = array();
				foreach ($regularActions as $action) {
					$adminActions[] = $admin . $action;
				}
				$this->bakeActions($adminActions, $vars);
			}
			$this->hr();
			$this->out();
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
		$controllerObj->plugin = $this->plugin;
		$controllerObj->constructClasses();
		$modelClass = $controllerObj->modelClass;
		$modelObj =& $controllerObj->{$controllerObj->modelClass};

		if ($modelObj) {
			$primaryKey = $modelObj->primaryKey;
			$displayField = $modelObj->displayField;
			$singularVar = Inflector::variable($modelClass);
			$singularHumanName = $this->_singularHumanName($this->controllerName);
			$schema = $modelObj->schema(true);
			$fields = array_keys($schema);
			$associations = $this->__associations($modelObj);
		} else {
			$primaryKey = $displayField = null;
			$singularVar = Inflector::variable(Inflector::singularize($this->controllerName));
			$singularHumanName = $this->_singularHumanName($this->controllerName);
			$fields = $schema = $associations = array();
		}
		$pluralVar = Inflector::variable($this->controllerName);
		$pluralHumanName = $this->_pluralHumanName($this->controllerName);

		return compact('modelClass', 'schema', 'primaryKey', 'displayField', 'singularVar', 'pluralVar',
				'singularHumanName', 'pluralHumanName', 'fields','associations');
	}

/**
 * Bake a view file for each of the supplied actions
 *
 * @param array $actions Array of actions to make files for.
 * @return void
 */
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
 */
	function customAction() {
		$action = '';
		while ($action == '') {
			$action = $this->in(__('Action Name? (use lowercase_underscored function name)', true));
			if ($action == '') {
				$this->out(__('The action name you supplied was empty. Please try again.', true));
			}
		}
		$this->out();
		$this->hr();
		$this->out(__('The following view will be created:', true));
		$this->hr();
		$this->out(sprintf(__('Controller Name: %s', true), $this->controllerName));
		$this->out(sprintf(__('Action Name:     %s', true), $action));
		$this->out(sprintf(__('Path:            %s', true), $this->params['app'] . DS . 'views' . DS . $this->controllerPath . DS . Inflector::underscore($action) . ".ctp"));
		$this->hr();
		$looksGood = $this->in(__('Look okay?', true), array('y','n'), 'y');
		if (strtolower($looksGood) == 'y') {
			$this->bake($action, true);
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
			$content = $this->getContent($action);
		}
		if (empty($content)) {
			return false;
		}
		$path = $this->getPath();
		$filename = $path . $this->controllerPath . DS . Inflector::underscore($action) . '.ctp';
		return $this->createFile($filename, $content);
	}

/**
 * Builds content from template and variables
 *
 * @param string $action name to generate content to
 * @param array $vars passed for use in templates
 * @return string content from template
 * @access public
 */
	function getContent($action, $vars = null) {
		if (!$vars) {
			$vars = $this->__loadController();
		}

		$this->Template->set('action', $action);
		$this->Template->set('plugin', $this->plugin);
		$this->Template->set($vars);
		$template = $this->getTemplate($action);
		if ($template) {
			return $this->Template->generate('views', $template);
		}
		return false;
	}

/**
 * Gets the template name based on the action name
 *
 * @param string $action name
 * @return string template name
 * @access public
 */
	function getTemplate($action) {
		if ($action != $this->template && in_array($action, $this->noTemplateActions)) {
			return false;
		}
		if (!empty($this->template) && $action != $this->template) {
			return $this->template;
		} 
		$template = $action;
		$prefixes = Configure::read('Routing.prefixes');
		foreach ((array)$prefixes as $prefix) {
			if (strpos($template, $prefix) !== false) {
				$template = str_replace($prefix . '_', '', $template);
			}
		}
		if (in_array($template, array('add', 'edit'))) {
			$template = 'form';
		} elseif (preg_match('@(_add|_edit)$@', $template)) {
			$template = str_replace(array('_add', '_edit'), '_form', $template);
		}
		return $template;
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
		$this->out('Arguments:');
		$this->out();
		$this->out("<controller>");
		$this->out("\tName of the controller views to bake. Can use Plugin.name");
		$this->out("\tas a shortcut for plugin baking.");
		$this->out();
		$this->out("<action>");
		$this->out("\tName of the action view to bake");
		$this->out();
		$this->out('Commands:');
		$this->out();
		$this->out("view <controller>");
		$this->out("\tWill read the given controller for methods");
		$this->out("\tand bake corresponding views.");
		$this->out("\tUsing the -admin flag will only bake views for actions");
		$this->out("\tthat begin with Routing.admin.");
		$this->out("\tIf var scaffold is found it will bake the CRUD actions");
		$this->out("\t(index,view,add,edit)");
		$this->out();
		$this->out("view <controller> <action>");
		$this->out("\tWill bake a template. core templates: (index, add, edit, view)");
		$this->out();
		$this->out("view <controller> <template> <alias>");
		$this->out("\tWill use the template specified");
		$this->out("\tbut name the file based on the alias");
		$this->out();
		$this->out("view all");
		$this->out("\tBake all CRUD action views for all controllers.");
		$this->out("\tRequires that models and controllers exist.");
		$this->_stop();
	}

/**
 * Returns associations for controllers models.
 *
 * @return  array $associations
 * @access private
 */
	function __associations(&$model) {
		$keys = array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany');
		$associations = array();

		foreach ($keys as $key => $type) {
			foreach ($model->{$type} as $assocKey => $assocData) {
				$associations[$type][$assocKey]['primaryKey'] = $model->{$assocKey}->primaryKey;
				$associations[$type][$assocKey]['displayField'] = $model->{$assocKey}->displayField;
				$associations[$type][$assocKey]['foreignKey'] = $assocData['foreignKey'];
				$associations[$type][$assocKey]['controller'] = Inflector::pluralize(Inflector::underscore($assocData['className']));
				$associations[$type][$assocKey]['fields'] =  array_keys($model->{$assocKey}->schema(true));
			}
		}
		return $associations;
	}
}
