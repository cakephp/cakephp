<?php
/**
 * Scaffold.
 *
 * Automatic forms and actions generation for rapid web application development.
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
 * @subpackage    cake.cake.libs.controller
 * @since         Cake v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Scaffolding is a set of automatic actions for starting web development work faster.
 *
 * Scaffold inspects your database tables, and making educated guesses, sets up a
 * number of pages for each of your Models. These pages have data forms that work,
 * and afford the web developer an early look at the data, and the possibility to over-ride
 * scaffolded actions with custom-made ones.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.controller
 */
class Scaffold {

/**
 * Controller object
 *
 * @var Controller
 */
	public $controller = null;

/**
 * Name of the controller to scaffold
 *
 * @var string
 */
	public $name = null;

/**
 * Name of current model this view context is attached to
 *
 * @var string
 */
	public $model = null;

/**
 * Path to View.
 *
 * @var string
 */
	public $viewPath;

/**
 * Name of layout to use with this View.
 *
 * @var string
 */
	public $layout = 'default';

/**
 * Request object
 *
 * @var CakeRequest
 */
	public $request;

/**
 * valid session.
 *
 * @var boolean
 * @access public
 */
	protected $_validSession = null;

/**
 * List of variables to collect from the associated controller
 *
 * @var array
 * @access private
 */
	private $__passedVars = array(
		'layout', 'name', 'viewPath', 'request'
	);

/**
 * Title HTML element for current scaffolded view
 *
 * @var string
 * @access public
 */
	public $scaffoldTitle = null;

/**
 * Construct and set up given controller with given parameters.
 *
 * @param Controller $controller Controller to scaffold
 * @param CakeRequest $request Request parameters.
 */
	function __construct(Controller $controller, CakeRequest $request) {
		$this->controller = $controller;

		$count = count($this->__passedVars);
		for ($j = 0; $j < $count; $j++) {
			$var = $this->__passedVars[$j];
			$this->{$var} = $controller->{$var};
		}

		$this->redirect = array('action' => 'index');

		$this->modelClass = $controller->modelClass;
		$this->modelKey = $controller->modelKey;

		if (!is_object($this->controller->{$this->modelClass})) {
			return $this->cakeError('missingModel', array(array(
				'className' => $this->modelClass,
				'webroot' => $request->webroot,
				'base' => $request->base
			)));
		}

		$this->ScaffoldModel = $this->controller->{$this->modelClass};
		$this->scaffoldTitle = Inflector::humanize($this->viewPath);
		$this->scaffoldActions = $controller->scaffold;
		$title_for_layout = __('Scaffold :: ') . Inflector::humanize($request->action) . ' :: ' . $this->scaffoldTitle;
		$modelClass = $this->controller->modelClass;
		$primaryKey = $this->ScaffoldModel->primaryKey;
		$displayField = $this->ScaffoldModel->displayField;
		$singularVar = Inflector::variable($modelClass);
		$pluralVar = Inflector::variable($this->controller->name);
		$singularHumanName = Inflector::humanize(Inflector::underscore($modelClass));
		$pluralHumanName = Inflector::humanize(Inflector::underscore($this->controller->name));
		$scaffoldFields = array_keys($this->ScaffoldModel->schema());
		$associations = $this->_associations();

		$this->controller->set(compact(
			'title_for_layout', 'modelClass', 'primaryKey', 'displayField', 'singularVar', 'pluralVar',
			'singularHumanName', 'pluralHumanName', 'scaffoldFields', 'associations'
		));

		if ($this->controller->view && $this->controller->view !== 'Theme') {
			$this->controller->view = 'scaffold';
		}
		$this->_validSession = (
			isset($this->controller->Session) && $this->controller->Session->valid() != false
		);
		$this->_scaffold($request);
	}

/**
 * Outputs the content of a scaffold method passing it through the Controller::afterFilter()
 *
 * @return void
 */
	protected function _output() {
		$this->controller->afterFilter();
		$this->controller->getResponse()->send();
	}

/**
 * Renders a view action of scaffolded model.
 *
 * @param CakeRequest $request Request Object for scaffolding
 * @return mixed A rendered view of a row from Models database table
 */
	protected function _scaffoldView(CakeRequest $request) {
		if ($this->controller->_beforeScaffold('view')) {

			$message = __(sprintf("No id set for %s::view()", Inflector::humanize($this->modelKey)));
			if (isset($request->params['pass'][0])) {
				$this->ScaffoldModel->id = $request->params['pass'][0];
			} else {
				return $this->_sendMessage($message);
			}
			$this->ScaffoldModel->recursive = 1;
			$this->controller->request->data = $this->controller->data = $this->ScaffoldModel->read();
			$this->controller->set(
				Inflector::variable($this->controller->modelClass), $this->request->data
			);
			$this->controller->render($this->request['action'], $this->layout);
			$this->_output();
		} elseif ($this->controller->_scaffoldError('view') === false) {
			return $this->_scaffoldError();
		}
	}

/**
 * Renders index action of scaffolded model.
 *
 * @param array $params Parameters for scaffolding
 * @return mixed A rendered view listing rows from Models database table
 */
	protected function _scaffoldIndex($params) {
		if ($this->controller->_beforeScaffold('index')) {
			$this->ScaffoldModel->recursive = 0;
			$this->controller->set(
				Inflector::variable($this->controller->name), $this->controller->paginate()
			);
			$this->controller->render($this->request['action'], $this->layout);
			$this->_output();
		} elseif ($this->controller->_scaffoldError('index') === false) {
			return $this->_scaffoldError();
		}
	}

/**
 * Renders an add or edit action for scaffolded model.
 *
 * @param string $action Action (add or edit)
 * @return mixed A rendered view with a form to edit or add a record in the Models database table
 */
	protected function _scaffoldForm($action = 'edit') {
		$this->controller->viewVars['scaffoldFields'] = array_merge(
			$this->controller->viewVars['scaffoldFields'],
			array_keys($this->ScaffoldModel->hasAndBelongsToMany)
		);
		$this->controller->render($action, $this->layout);
		$this->_output();
	}

/**
 * Saves or updates the scaffolded model.
 *
 * @param CakeRequest $request Request Object for scaffolding
 * @param string $action add or edt
 * @return mixed Success on save/update, add/edit form if data is empty or error if save or update fails
 */
	protected function _scaffoldSave(CakeRequest $request, $action = 'edit') {
		$formAction = 'edit';
		$success = __('updated');
		if ($action === 'add') {
			$formAction = 'add';
			$success = __('saved');
		}

		if ($this->controller->_beforeScaffold($action)) {
			if ($action == 'edit') {
				if (isset($request->params['pass'][0])) {
					$this->ScaffoldModel->id = $request['pass'][0];
				}

				if (!$this->ScaffoldModel->exists()) {
					$message = __(sprintf("Invalid id for %s::edit()", Inflector::humanize($this->modelKey)));
					return $this->_sendMessage($message);
				}
			}

			if (!empty($request->data)) {
				if ($action == 'create') {
					$this->ScaffoldModel->create();
				}

				if ($this->ScaffoldModel->save($request->data)) {
					if ($this->controller->_afterScaffoldSave($action)) {
						$message = __(
							sprintf('The %1$s has been %2$s', Inflector::humanize($this->modelKey), $success)
						);
						return $this->_sendMessage($message);
					} else {
						return $this->controller->_afterScaffoldSaveError($action);
					}
				} else {
					if ($this->_validSession) {
						$this->controller->Session->setFlash(__('Please correct errors below.'));
					}
				}
			}

			if (empty($request->data)) {
				if ($this->ScaffoldModel->id) {
					$this->controller->data = $request->data = $this->ScaffoldModel->read();
				} else {
					$this->controller->data = $request->data = $this->ScaffoldModel->create();
				}
			}

			foreach ($this->ScaffoldModel->belongsTo as $assocName => $assocData) {
				$varName = Inflector::variable(Inflector::pluralize(
					preg_replace('/(?:_id)$/', '', $assocData['foreignKey'])
				));
				$this->controller->set($varName, $this->ScaffoldModel->{$assocName}->find('list'));
			}
			foreach ($this->ScaffoldModel->hasAndBelongsToMany as $assocName => $assocData) {
				$varName = Inflector::variable(Inflector::pluralize($assocName));
				$this->controller->set($varName, $this->ScaffoldModel->{$assocName}->find('list'));
			}

			return $this->_scaffoldForm($formAction);
		} elseif ($this->controller->_scaffoldError($action) === false) {
			return $this->_scaffoldError();
		}
	}

/**
 * Performs a delete on given scaffolded Model.
 *
 * @param array $params Parameters for scaffolding
 * @return mixed Success on delete, error if delete fails
 */
	protected function _scaffoldDelete(CakeRequest $request) {
		if ($this->controller->_beforeScaffold('delete')) {
			$message = __(
				sprintf("No id set for %s::delete()", Inflector::humanize($this->modelKey))
			);
			if (isset($request->params['pass'][0])) {
				$id = $request->params['pass'][0];
			} else {
				return $this->_sendMessage($message);
			}

			if ($this->ScaffoldModel->delete($id)) {
				$message = __(
					sprintf('The %1$s with id: %2$d has been deleted.', Inflector::humanize($this->modelClass), $id)
				);
				return $this->_sendMessage($message);
			} else {
				$message = __(sprintf(
					'There was an error deleting the %1$s with id: %2$d',
					Inflector::humanize($this->modelClass), $id
				));
				return $this->_sendMessage($message);
			}
		} elseif ($this->controller->_scaffoldError('delete') === false) {
			return $this->_scaffoldError();
		}
	}

/**
 * Sends a message to the user.  Either uses Sessions or flash messages depending 
 * on the availability of a session
 *
 * @param string $message Message to display
 * @return void
 */
	protected function _sendMessage($message) {
		if ($this->_validSession) {
			$this->controller->Session->setFlash($message);
			$this->controller->redirect($this->redirect);
		} else {
			$this->controller->flash($message, $this->redirect);
			$this->_output();
		}
	}

/**
 * Show a scaffold error
 *
 * @return mixed A rendered view showing the error
 */
	protected function _scaffoldError() {
		return $this->controller->render('error', $this->layout);
		$this->_output();
	}

/**
 * When methods are now present in a controller
 * scaffoldView is used to call default Scaffold methods if:
 * `public $scaffold;` is placed in the controller's class definition.
 *
 * @param CakeRequest $request Request object for scaffolding
 * @return mixed A rendered view of scaffold action, or showing the error
 */
	protected function _scaffold(CakeRequest $request) {
		$db = ConnectionManager::getDataSource($this->ScaffoldModel->useDbConfig);
		$prefixes = Configure::read('Routing.prefixes');
		$scaffoldPrefix = $this->scaffoldActions;

		if (isset($db)) {
			if (empty($this->scaffoldActions)) {
				$this->scaffoldActions = array(
					'index', 'list', 'view', 'add', 'create', 'edit', 'update', 'delete'
				);
			} elseif (!empty($prefixes) && in_array($scaffoldPrefix, $prefixes)) {
				$this->scaffoldActions = array(
					$scaffoldPrefix . '_index',
					$scaffoldPrefix . '_list',
					$scaffoldPrefix . '_view',
					$scaffoldPrefix . '_add',
					$scaffoldPrefix . '_create',
					$scaffoldPrefix . '_edit',
					$scaffoldPrefix . '_update',
					$scaffoldPrefix . '_delete'
				);
			}

			if (in_array($request->params['action'], $this->scaffoldActions)) {
				if (!empty($prefixes)) {
					$request->params['action'] = str_replace($scaffoldPrefix . '_', '', $request->params['action']);
				}
				switch ($request->params['action']) {
					case 'index':
					case 'list':
						$this->_scaffoldIndex($request);
					break;
					case 'view':
						$this->_scaffoldView($request);
					break;
					case 'add':
					case 'create':
						$this->_scaffoldSave($request, 'add');
					break;
					case 'edit':
					case 'update':
						$this->_scaffoldSave($request, 'edit');
					break;
					case 'delete':
						$this->_scaffoldDelete($request);
					break;
				}
			} else {
				return $this->cakeError('missingAction', array(array(
					'className' => $this->controller->name . "Controller",
					'base' => $request->base,
					'action' => $request->action,
					'webroot' => $request->webroot
				)));
			}
		} else {
			return $this->cakeError('missingDatabase', array(array(
				'webroot' => $request->webroot
			)));
		}
	}

/**
 * Returns associations for controllers models.
 *
 * @return array Associations for model
 */
	protected function _associations() {
		$keys = array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany');
		$associations = array();

		foreach ($keys as $key => $type) {
			foreach ($this->ScaffoldModel->{$type} as $assocKey => $assocData) {
				$associations[$type][$assocKey]['primaryKey'] =
					$this->ScaffoldModel->{$assocKey}->primaryKey;

				$associations[$type][$assocKey]['displayField'] =
					$this->ScaffoldModel->{$assocKey}->displayField;

				$associations[$type][$assocKey]['foreignKey'] =
					$assocData['foreignKey'];

				$associations[$type][$assocKey]['controller'] =
					Inflector::pluralize(Inflector::underscore($assocData['className']));

				if ($type == 'hasAndBelongsToMany') {
					$associations[$type][$assocKey]['with'] = $assocData['with'];
				}
			}
		}
		return $associations;
	}
}

/**
 * Scaffold View.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.controller
*/
if (!class_exists('ThemeView')) {
	App::import('View', 'Theme');
}

/**
 * ScaffoldView provides specific view file loading features for scaffolded views.
 *
 * @package cake.libs.view
 */
class ScaffoldView extends ThemeView {

/**
 * Override _getViewFileName Appends special scaffolding views in.
 *
 * @param string $name name of the view file to get.
 * @return string action
 */
	protected function _getViewFileName($name = null) {
		if ($name === null) {
			$name = $this->action;
		}
		$name = Inflector::underscore($name);
		$prefixes = Configure::read('Routing.prefixes');

		if (!empty($prefixes)) {
			foreach ($prefixes as $prefix) {
				if (strpos($name, $prefix . '_') !== false) {
					$name = substr($name, strlen($prefix) + 1);
					break;
				}
			}
		}

		if ($name === 'add') {
			$name = 'edit';
		}

		$scaffoldAction = 'scaffold.' . $name;

		if (!is_null($this->subDir)) {
			$subDir = strtolower($this->subDir) . DS;
		} else {
			$subDir = null;
		}

		$names[] = $this->viewPath . DS . $subDir . $scaffoldAction;
		$names[] = 'scaffolds' . DS . $subDir . $name;

		$paths = $this->_paths($this->plugin);
		$exts = array($this->ext);
		if ($this->ext !== '.ctp') {
			array_push($exts, '.ctp');
		}
		foreach ($exts as $ext) {
			foreach ($paths as $path) {
				foreach ($names as $name) {
					if (file_exists($path . $name . $ext)) {
						return $path . $name . $ext;
					}
				}
			}
		}

		if ($name === 'scaffolds' . DS . $subDir . 'error') {
			return LIBS . 'view' . DS . 'errors' . DS . 'scaffold_error.ctp';
		}

		return $this->_missingView($paths[0] . $name . $this->ext, 'missingView');
	}
}
