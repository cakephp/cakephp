<?php
/* SVN FILE: $Id$ */
/**
 * The View Tasks handles creating and updating view files.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
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
	var $tasks = array('Project', 'Controller');
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
 * Handles interactive baking
 *
 * @access private
 */
	function __interactive() {
		$this->hr();
		$this->out(sprintf("Bake View\nPath: %s", $this->path));
		$this->hr();
		$wannaDoAdmin = 'n';
		$wannaDoScaffold = 'y';
		$this->interactive = false;

		$this->controllerName = $this->Controller->getName();

		$this->controllerPath = strtolower(Inflector::underscore($this->controllerName));

		$interactive = $this->in("Would you like bake to build your views interactively?\nWarning: Choosing no will overwrite {$this->controllerName} views if it exist.", array('y','n'), 'y');

		if (strtolower($interactive) == 'y' || strtolower($interactive) == 'yes') {
			$this->interactive = true;
			$wannaDoScaffold = $this->in("Would you like to create some scaffolded views (index, add, view, edit) for this controller?\nNOTE: Before doing so, you'll need to create your controller and model classes (including associated models).", array('y','n'), 'n');
		}

		if (strtolower($wannaDoScaffold) == 'y' || strtolower($wannaDoScaffold) == 'yes') {
			$wannaDoAdmin = $this->in("Would you like to create the views for admin routing?", array('y','n'), 'y');
		}
		$admin = false;

		if ((strtolower($wannaDoAdmin) == 'y' || strtolower($wannaDoAdmin) == 'yes')) {
			$admin = $this->getAdmin();
		}

		if (strtolower($wannaDoScaffold) == 'y' || strtolower($wannaDoScaffold) == 'yes') {
			$actions = $this->scaffoldActions;
			if ($admin) {
				foreach ($actions as $action) {
					$actions[] = $admin . $action;
				}
			}
			$vars = $this->__loadController();
			if ($vars) {
				foreach ($actions as $action) {
					$content = $this->getContent($action, $vars);
					$this->bake($action, $content);
				}
			}
			$this->hr();
			$this->out('');
			$this->out('View Scaffolding Complete.'."\n");
		} else {
			$action = '';
			while ($action == '') {
				$action = $this->in('Action Name? (use camelCased function name)');
				if ($action == '') {
					$this->out('The action name you supplied was empty. Please try again.');
				}
			}
			$this->out('');
			$this->hr();
			$this->out('The following view will be created:');
			$this->hr();
			$this->out("Controller Name: {$this->controllerName}");
			$this->out("Action Name:	 {$action}");
			$this->out("Path:			 ".$this->params['app'] . DS . $this->controllerPath . DS . Inflector::underscore($action) . ".ctp");
			$this->hr();
			$looksGood = $this->in('Look okay?', array('y','n'), 'y');
			if (strtolower($looksGood) == 'y' || strtolower($looksGood) == 'yes') {
				$this->bake($action);
				$this->_stop();
			} else {
				$this->out('Bake Aborted.');
			}
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
		$controllerObj = & new $controllerClassName();
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
			$path = $Folder->slashTerm($Folder->pwd());
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
		$loaded = false;
		foreach ($this->Dispatch->shellPaths as $path) {
			$templatePath = $path . 'templates' . DS . 'views' . DS .Inflector::underscore($template).'.ctp';
			if (file_exists($templatePath) && is_file($templatePath)) {
				$loaded = true;
				break;
			}
		}
		if (!$vars) {
			$vars = $this->__loadController();
		}
		if ($loaded) {
			extract($vars);
			ob_start();
			ob_implicit_flush(0);
			include($templatePath);
			$content = ob_get_clean();
			return $content;
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
		$this->out("\n\tview <controller>\n\t\twill read the given controller for methods\n\t\tand bake corresponding views.\n\t\tIf var scaffold is found it will bake the scaffolded actions\n\t\t(index,view,add,edit)");
		$this->out("\n\tview <controller> <action>\n\t\twill bake a template. core templates: (index, add, edit, view)");
		$this->out("\n\tview <controller> <template> <alias>\n\t\twill use the template specified but name the file based on the alias");
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