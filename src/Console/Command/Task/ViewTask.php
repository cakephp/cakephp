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
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

/**
 * Task class for creating and updating view files.
 *
 */
class ViewTask extends BakeTask {

/**
 * Tasks to be loaded by this Task
 *
 * @var array
 */
	public $tasks = ['Model', 'Template'];

/**
 * path to View directory
 *
 * @var array
 */
	public $pathFragment = 'Template/';

/**
 * Name of the controller being used
 *
 * @var string
 */
	public $controllerName = null;

/**
 * Classname of the controller being used
 *
 * @var string
 */
	public $controllerClass = null;

/**
 * Name of the table views are being baked against.
 *
 * @var string
 */
	public $tableName = null;

/**
 * The template file to use
 *
 * @var string
 */
	public $template = null;

/**
 * Actions to use for scaffolding
 *
 * @var array
 */
	public $scaffoldActions = ['index', 'view', 'add', 'edit'];

/**
 * An array of action names that don't require templates. These
 * actions will not emit errors when doing bakeActions()
 *
 * @var array
 */
	public $noTemplateActions = ['delete'];

/**
 * Override initialize
 *
 * @return void
 */
	public function initialize() {
		$this->path = current(App::path('Template'));
	}

/**
 * Execution method always used for tasks
 *
 * @return mixed
 */
	public function main($name = null, $template = null, $action = null) {
		parent::main();

		if (empty($name)) {
			$this->out(__d('cake_console', 'Possible tables to bake views for based on your current database:'));
			$this->Model->connection = $this->connection;
			foreach ($this->Model->listAll() as $table) {
				$this->out('- ' . $this->_controllerName($table));
			}
			return true;
		}

		$controller = null;
		if (!empty($this->params['controller'])) {
			$controller = $this->params['controller'];
		}
		$this->controller($name, $controller);

		if (isset($template)) {
			$this->template = $template;
		}
		if (!$action) {
			$action = $this->template;
		}
		if ($action) {
			return $this->bake($action, true);
		}

		$vars = $this->_loadController();
		$methods = $this->_methodsToBake();

		foreach ($methods as $method) {
			$content = $this->getContent($method, $vars);
			if ($content) {
				$this->bake($method, $content);
			}
		}
	}

/**
 * Set the controller related properties.
 *
 * @param string $table The table/model that is being baked.
 * @param string $controller The controller name if specified.
 * @return void
 */
	public function controller($table, $controller = null) {
		$this->tableName = $this->_controllerName($table);
		if (empty($controller)) {
			$controller = $this->tableName;
		}
		$this->controllerName = $controller;

		$plugin = $prefix = null;
		if (!empty($this->params['plugin'])) {
			$plugin = $this->params['plugin'] . '.';
		}
		if (!empty($this->params['prefix'])) {
			$prefix = $this->params['prefix'] . '/';
		}
		$this->controllerClass = App::className($plugin . $prefix . $controller, 'Controller', 'Controller');
	}

/**
 * Get the path base for views.
 *
 * @return string
 */
	public function getPath() {
		$path = parent::getPath();
		if (!empty($this->params['prefix'])) {
			$path .= $this->_camelize($this->params['prefix']) . DS;
		}
		$path .= $this->controllerName . DS;
		return $path;
	}

/**
 * Get a list of actions that can / should have views baked for them.
 *
 * @return array Array of action names that should be baked
 */
	protected function _methodsToBake() {
		$base = Configure::read('App.namespace');

		$methods = [];
		if (class_exists($this->controllerClass)) {
			$methods = array_diff(
				array_map('strtolower', get_class_methods($this->controllerClass)),
				array_map('strtolower', get_class_methods($base . '\Controller\AppController'))
			);
		}
		if (empty($methods)) {
			$methods = $this->scaffoldActions;
		}
		foreach ($methods as $i => $method) {
			if ($method[0] === '_') {
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
	public function all() {
		$this->Model->connection = $this->connection;
		$tables = $this->Model->listAll();

		foreach ($tables as $table) {
			$this->main($table);
		}
	}

/**
 * Loads Controller and sets variables for the template
 * Available template variables:
 *
 * - 'modelClass'
 * - 'primaryKey'
 * - 'displayField'
 * - 'singularVar'
 * - 'pluralVar'
 * - 'singularHumanName'
 * - 'pluralHumanName'
 * - 'fields'
 * - 'keyFields'
 * - 'schema'
 *
 * @return array Returns an variables to be made available to a view template
 */
	protected function _loadController() {
		$modelObj = TableRegistry::get($this->tableName);

		$primaryKey = (array)$modelObj->primaryKey();
		$displayField = $modelObj->displayField();
		$singularVar = $this->_singularName($this->controllerName);
		$singularHumanName = $this->_singularHumanName($this->controllerName);
		$schema = $modelObj->schema();
		$fields = $schema->columns();
		$associations = $this->_associations($modelObj);
		$keyFields = [];
		if (!empty($associations['BelongsTo'])) {
			foreach ($associations['BelongsTo'] as $assoc) {
				$keyFields[$assoc['foreignKey']] = $assoc['variable'];
			}
		}

		$pluralVar = Inflector::variable($this->controllerName);
		$pluralHumanName = $this->_pluralHumanName($this->controllerName);

		return compact(
			'modelClass', 'schema',
			'primaryKey', 'displayField',
			'singularVar', 'pluralVar',
			'singularHumanName', 'pluralHumanName',
			'fields', 'associations', 'keyFields'
		);
	}

/**
 * Bake a view file for each of the supplied actions
 *
 * @param array $actions Array of actions to make files for.
 * @param array $vars
 * @return void
 */
	public function bakeActions(array $actions, $vars) {
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
	public function customAction() {
		$action = '';
		while (!$action) {
			$action = $this->in(__d('cake_console', 'Action Name? (use lowercase_underscored function name)'));
			if (!$action) {
				$this->out(__d('cake_console', 'The action name you supplied was empty. Please try again.'));
			}
		}
		$this->out();
		$this->hr();
		$this->out(__d('cake_console', 'The following view will be created:'));
		$this->hr();
		$this->out(__d('cake_console', 'Controller Name: %s', $this->controllerName));
		$this->out(__d('cake_console', 'Action Name:     %s', $action));
		$this->out(__d('cake_console', 'Path:            %s', $this->getPath() . $this->controllerName . DS . Inflector::underscore($action) . ".ctp"));
		$this->hr();
		$looksGood = $this->in(__d('cake_console', 'Look okay?'), ['y', 'n'], 'y');
		if (strtolower($looksGood) === 'y') {
			$this->bake($action, ' ');
			return $this->_stop();
		}
		$this->out(__d('cake_console', 'Bake Aborted.'));
	}

/**
 * Assembles and writes bakes the view file.
 *
 * @param string $action Action to bake.
 * @param string $content Content to write.
 * @return string Generated file content.
 */
	public function bake($action, $content = '') {
		if ($content === true) {
			$content = $this->getContent($action);
		}
		if (empty($content)) {
			return false;
		}
		$this->out("\n" . __d('cake_console', 'Baking `%s` view file...', $action), 1, Shell::QUIET);
		$path = $this->getPath();
		$filename = $path . Inflector::underscore($action) . '.ctp';
		$this->createFile($filename, $content);
		return $content;
	}

/**
 * Builds content from template and variables
 *
 * @param string $action name to generate content to
 * @param array $vars passed for use in templates
 * @return string content from template
 */
	public function getContent($action, $vars = null) {
		if (!$vars) {
			$vars = $this->_loadController();
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
 */
	public function getTemplate($action) {
		if ($action != $this->template && in_array($action, $this->noTemplateActions)) {
			return false;
		}
		if (!empty($this->template) && $action != $this->template) {
			return $this->template;
		}
		$themePath = $this->Template->getThemePath();

		if (!empty($this->params['prefix'])) {
			$prefixed = Inflector::underscore($this->params['prefix']) . '_' . $action;
			if (file_exists($themePath . 'views/' . $prefixed . '.ctp')) {
				return $prefixed;
			}
			$generic = preg_replace('/(.*)(_add|_edit)$/', '\1_form', $prefixed);
			if (file_exists($themePath . 'views/' . $generic . '.ctp')) {
				return $generic;
			}
		}
		if (file_exists($themePath . 'views/' . $action . '.ctp')) {
			return $action;
		}
		if (in_array($action, ['add', 'edit'])) {
			return 'form';
		}
		return $action;
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return \Cake\Console\ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(
			__d('cake_console', 'Bake views for a controller, using built-in or custom templates. ')
		)->addArgument('controller', [
			'help' => __d('cake_console', 'Name of the controller views to bake. Can be Plugin.name as a shortcut for plugin baking.')
		])->addArgument('action', [
			'help' => __d('cake_console', "Will bake a single action's file. core templates are (index, add, edit, view)")
		])->addArgument('alias', [
			'help' => __d('cake_console', 'Will bake the template in <action> but create the filename after <alias>.')
		])->addOption('controller', [
			'help' => __d('cake_console', 'The controller name if you have a controller that does not follow conventions.')
		])->addOption('prefix', [
			'help' => __d('cake_console', 'The routing prefix to generate views for.'),
		])->addSubcommand('all', [
			'help' => __d('cake_console', 'Bake all CRUD action views for all controllers. Requires models and controllers to exist.')
		]);

		return $parser;
	}

/**
 * Returns associations for controllers models.
 *
 * @param Table $model
 * @return array associations
 */
	protected function _associations(Table $model) {
		$keys = ['BelongsTo', 'HasOne', 'HasMany', 'BelongsToMany'];
		$associations = [];

		foreach ($keys as $type) {
			foreach ($model->associations()->type($type) as $assoc) {
				$target = $assoc->target();
				$assocName = $assoc->name();
				$alias = $target->alias();

				$associations[$type][$assocName] = [
					'property' => $assoc->property(),
					'variable' => Inflector::variable($assocName),
					'primaryKey' => (array)$target->primaryKey(),
					'displayField' => $target->displayField(),
					'foreignKey' => $assoc->foreignKey(),
					'controller' => Inflector::underscore($alias),
					'fields' => $target->schema()->columns(),
				];
			}
		}
		return $associations;
	}

}
