<?php
/**
 * Scaffold.
 *
 * Automatic forms and actions generation for rapid web application development.
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
 * @package       Cake.Controller
 * @since         Cake v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Scaffolding is a set of automatic actions for starting web development work faster.
 *
 * Scaffold inspects your database tables, and making educated guesses, sets up a
 * number of pages for each of your Models. These pages have data forms that work,
 * and afford the web developer an early look at the data, and the possibility to over-ride
 * scaffolded actions with custom-made ones.
 *
 * @package Cake.Controller
 * @deprecated 3.0.0 Dynamic scaffolding will be removed and replaced in 3.0
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
 * Valid session.
 *
 * @var bool
 */
	protected $_validSession = null;

/**
 * List of variables to collect from the associated controller
 *
 * @var array
 */
	protected $_passedVars = array(
		'layout', 'name', 'viewPath', 'request'
	);

/**
 * Title HTML element for current scaffolded view
 *
 * @var string
 */
	public $scaffoldTitle = null;

/**
 * Construct and set up given controller with given parameters.
 *
 * @param Controller $controller Controller to scaffold
 * @param CakeRequest $request Request parameters.
 * @throws MissingModelException
 */
	public function __construct(Controller $controller, CakeRequest $request) {
		$this->controller = $controller;

		$count = count($this->_passedVars);
		for ($j = 0; $j < $count; $j++) {
			$var = $this->_passedVars[$j];
			$this->{$var} = $controller->{$var};
		}

		$this->redirect = array('action' => 'index');

		$this->modelClass = $controller->modelClass;
		$this->modelKey = $controller->modelKey;

		if (!is_object($this->controller->{$this->modelClass})) {
			throw new MissingModelException($this->modelClass);
		}

		$this->ScaffoldModel = $this->controller->{$this->modelClass};
		$this->scaffoldTitle = Inflector::humanize(Inflector::underscore($this->viewPath));
		$this->scaffoldActions = $controller->scaffold;
		$title = __d('cake', 'Scaffold :: ') . Inflector::humanize($request->action) . ' :: ' . $this->scaffoldTitle;
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
			'modelClass', 'primaryKey', 'displayField', 'singularVar', 'pluralVar',
			'singularHumanName', 'pluralHumanName', 'scaffoldFields', 'associations'
		));
		$this->controller->set('title_for_layout', $title);

		if ($this->controller->viewClass) {
			$this->controller->viewClass = 'Scaffold';
		}
		$this->_validSession = (
			isset($this->controller->Session) && $this->controller->Session->valid()
		);
		$this->_scaffold($request);
	}

/**
 * Renders a view action of scaffolded model.
 *
 * @param CakeRequest $request Request Object for scaffolding
 * @return mixed A rendered view of a row from Models database table
 * @throws NotFoundException
 */
	protected function _scaffoldView(CakeRequest $request) {
		if ($this->controller->beforeScaffold('view')) {
			if (isset($request->params['pass'][0])) {
				$this->ScaffoldModel->id = $request->params['pass'][0];
			}
			if (!$this->ScaffoldModel->exists()) {
				throw new NotFoundException(__d('cake', 'Invalid %s', Inflector::humanize($this->modelKey)));
			}
			$this->ScaffoldModel->recursive = 1;
			$this->controller->request->data = $this->ScaffoldModel->read();
			$this->controller->set(
				Inflector::variable($this->controller->modelClass), $this->request->data
			);
			$this->controller->render($this->request['action'], $this->layout);
		} elseif ($this->controller->scaffoldError('view') === false) {
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
		if ($this->controller->beforeScaffold('index')) {
			$this->ScaffoldModel->recursive = 0;
			$this->controller->set(
				Inflector::variable($this->controller->name), $this->controller->paginate()
			);
			$this->controller->render($this->request['action'], $this->layout);
		} elseif ($this->controller->scaffoldError('index') === false) {
			return $this->_scaffoldError();
		}
	}

/**
 * Renders an add or edit action for scaffolded model.
 *
 * @param string $action Action (add or edit)
 * @return void
 */
	protected function _scaffoldForm($action = 'edit') {
		$this->controller->viewVars['scaffoldFields'] = array_merge(
			$this->controller->viewVars['scaffoldFields'],
			array_keys($this->ScaffoldModel->hasAndBelongsToMany)
		);
		$this->controller->render($action, $this->layout);
	}

/**
 * Saves or updates the scaffolded model.
 *
 * @param CakeRequest $request Request Object for scaffolding
 * @param string $action add or edit
 * @return mixed Success on save/update, add/edit form if data is empty or error if save or update fails
 * @throws NotFoundException
 */
	protected function _scaffoldSave(CakeRequest $request, $action = 'edit') {
		$formAction = 'edit';
		$success = __d('cake', 'updated');
		if ($action === 'add') {
			$formAction = 'add';
			$success = __d('cake', 'saved');
		}

		if ($this->controller->beforeScaffold($action)) {
			if ($action === 'edit') {
				if (isset($request->params['pass'][0])) {
					$this->ScaffoldModel->id = $request['pass'][0];
				}
				if (!$this->ScaffoldModel->exists()) {
					throw new NotFoundException(__d('cake', 'Invalid %s', Inflector::humanize($this->modelKey)));
				}
			}

			if (!empty($request->data)) {
				if ($action === 'create') {
					$this->ScaffoldModel->create();
				}

				if ($this->ScaffoldModel->save($request->data)) {
					if ($this->controller->afterScaffoldSave($action)) {
						$message = __d('cake',
							'The %1$s has been %2$s',
							Inflector::humanize($this->modelKey),
							$success
						);
						return $this->_sendMessage($message);
					}
					return $this->controller->afterScaffoldSaveError($action);
				}
				if ($this->_validSession) {
					$this->controller->Session->setFlash(__d('cake', 'Please correct errors below.'));
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
		} elseif ($this->controller->scaffoldError($action) === false) {
			return $this->_scaffoldError();
		}
	}

/**
 * Performs a delete on given scaffolded Model.
 *
 * @param CakeRequest $request Request for scaffolding
 * @return mixed Success on delete, error if delete fails
 * @throws MethodNotAllowedException When HTTP method is not a DELETE
 * @throws NotFoundException When id being deleted does not exist.
 */
	protected function _scaffoldDelete(CakeRequest $request) {
		if ($this->controller->beforeScaffold('delete')) {
			if (!$request->is('post')) {
				throw new MethodNotAllowedException();
			}
			$id = false;
			if (isset($request->params['pass'][0])) {
				$id = $request->params['pass'][0];
			}
			$this->ScaffoldModel->id = $id;
			if (!$this->ScaffoldModel->exists()) {
				throw new NotFoundException(__d('cake', 'Invalid %s', Inflector::humanize($this->modelClass)));
			}
			if ($this->ScaffoldModel->delete()) {
				$message = __d('cake', 'The %1$s with id: %2$s has been deleted.', Inflector::humanize($this->modelClass), $id);
				return $this->_sendMessage($message);
			}
			$message = __d('cake',
				'There was an error deleting the %1$s with id: %2$s',
				Inflector::humanize($this->modelClass),
				$id
			);
			return $this->_sendMessage($message);
		} elseif ($this->controller->scaffoldError('delete') === false) {
			return $this->_scaffoldError();
		}
	}

/**
 * Sends a message to the user. Either uses Sessions or flash messages depending
 * on the availability of a session
 *
 * @param string $message Message to display
 * @return void
 */
	protected function _sendMessage($message) {
		if ($this->_validSession) {
			$this->controller->Session->setFlash($message);
			return $this->controller->redirect($this->redirect);
		}
		$this->controller->flash($message, $this->redirect);
	}

/**
 * Show a scaffold error
 *
 * @return mixed A rendered view showing the error
 */
	protected function _scaffoldError() {
		return $this->controller->render('error', $this->layout);
	}

/**
 * When methods are now present in a controller
 * scaffoldView is used to call default Scaffold methods if:
 * `public $scaffold;` is placed in the controller's class definition.
 *
 * @param CakeRequest $request Request object for scaffolding
 * @return void
 * @throws MissingActionException When methods are not scaffolded.
 * @throws MissingDatabaseException When the database connection is undefined.
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
				throw new MissingActionException(array(
					'controller' => get_class($this->controller),
					'action' => $request->action
				));
			}
		} else {
			throw new MissingDatabaseException(array('connection' => $this->ScaffoldModel->useDbConfig));
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

		foreach ($keys as $type) {
			foreach ($this->ScaffoldModel->{$type} as $assocKey => $assocData) {
				$associations[$type][$assocKey]['primaryKey'] =
					$this->ScaffoldModel->{$assocKey}->primaryKey;

				$associations[$type][$assocKey]['displayField'] =
					$this->ScaffoldModel->{$assocKey}->displayField;

				$associations[$type][$assocKey]['foreignKey'] =
					$assocData['foreignKey'];

				list($plugin, $model) = pluginSplit($assocData['className']);
				if ($plugin) {
					$plugin = Inflector::underscore($plugin);
				}
				$associations[$type][$assocKey]['plugin'] = $plugin;

				$associations[$type][$assocKey]['controller'] =
					Inflector::pluralize(Inflector::underscore($model));

				if ($type === 'hasAndBelongsToMany') {
					$associations[$type][$assocKey]['with'] = $assocData['with'];
				}
			}
		}
		return $associations;
	}

}
