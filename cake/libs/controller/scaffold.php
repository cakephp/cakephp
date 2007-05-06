<?php
/* SVN FILE: $Id$ */
/**
 * Scaffold.
 *
 * Automatic forms and actions generation for rapid web application development.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.controller
 * @since		Cake v 0.10.0.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Scaffolding is a set of automatic views, forms and controllers for starting web development work faster.
 *
 * Scaffold inspects your database tables, and making educated guesses, sets up a
 * number of pages for each of your Models. These pages have data forms that work,
 * and afford the web developer an early look at the data, and the possibility to over-ride
 * scaffolded actions with custom-made ones.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller
 */
class Scaffold extends Object {
/**
 * Controller object
 *
 * @var Controller
 */
	var $controller = null;
/**
 * Name of the controller to scaffold
 *
 * @var string Name of controller
 * @access public
 */
	var $name = null;
/**
 * Action to be performed.
 *
 * @var string Name of action
 * @access public
 */
	var $action = null;
/**
 * Name of current model this view context is attached to
 *
 * @var string
 */
	var $model = null;
/**
 * Path to View.
 *
 * @var string Path to View
 */
	var $viewPath;
/**
 * Path parts for creating links in views.
 *
 * @var string Base URL
 * @access public
 */
	var $base = null;
/**
 * Name of layout to use with this View.
 *
 * @var string
 * @access public
 */
	var $layout = 'default';
/**
 * Array of parameter data
 *
 * @var array Parameter data
 */
	var $params;
/**
 * File extension. Defaults to Cake's template ".ctp".
 *
 * @var array
 */
	var $ext = '.ctp';
/**
 * Sub-directory for this view file.
 *
 * @var string
 */
	var $subDir = null;
/**
 * Plugin name. A Plugin is a sub-application. New in Cake RC4.
 *
 * @link http://wiki.cakephp.org/docs:plugins
 * @var string
 */
	var $plugin = null;
/**
 * Controller URL-generation data
 *
 * @var mixed
 */
	var $namedArgs = null;
/**
 * Controller URL-generation data
 *
 * @var string
 */
	var $argSeparator = null;
/**
 * List of variables to collect from the associated controller
 *
 * @var array
 * @access protected
 */
	var $__passedVars = array('action', 'base', 'webroot', 'layout', 'name', 'viewPath', 'ext', 'params', 'data', 'webservices', 'plugin', 'namedArgs', 'argSeparator', 'cacheAction');
/**
 * Title HTML element for current scaffolded view
 *
 * @var string
 */
	var $scaffoldTitle = null;
/**
 * Construct and set up given controller with given parameters.
 *
 * @param string $controller_class Name of controller
 * @param array $params
 */
	function __construct(&$controller, $params) {
		$this->controller = &$controller;

		$count = count($this->__passedVars);
		for ($j = 0; $j < $count; $j++) {
			$var = $this->__passedVars[$j];
			$this->{$var} = $controller->{$var};
		}
		$this->redirect = '/' . $this->viewPath;
		if(!is_null($this->plugin)) {
			$this->redirect = '/' . $this->plugin . '/' . $this->viewPath;
		}

		if (!in_array('Form', $this->controller->helpers)) {
			$this->controller->helpers[] = 'Form';
		}

		if($this->controller->constructClasses() === false) {
			return $this->cakeError('missingModel', array(array('className' => $this->modelKey, 'webroot' => '', 'base' => $this->controller->base)));
		}

		if(!empty($controller->uses) && class_exists($controller->uses[0])) {
			$controller->modelClass = $controller->uses[0];
			$controller->modelKey = Inflector::underscore($controller->modelClass);
		}
		$this->modelClass = $controller->modelClass;
		$this->modelKey = $controller->modelKey;

		if(!is_object($this->controller->{$this->modelClass})) {
			return $this->cakeError('missingModel', array(array('className' => $this->modelClass, 'webroot' => '', 'base' => $controller->base)));
		}
		$this->ScaffoldModel =& $this->controller->{$this->modelClass};
		$this->scaffoldTitle = Inflector::humanize($this->viewPath);
		$this->scaffoldActions = $controller->scaffold;
		$this->controller->pageTitle= __('Scaffold :: ', true) . Inflector::humanize($this->action) . ' :: ' . $this->scaffoldTitle;
		$path = '/';
		$this->controller->set('path', $path);
		$this->controller->set('controllerName', $this->name);
		$this->controller->set('controllerAction', $this->action);
		$this->controller->set('modelClass', $this->modelClass);
		$this->controller->set('modelKey', $this->modelKey);
		$this->controller->set('viewPath', $this->viewPath);
		$this->controller->set('humanSingularName', Inflector::humanize($this->modelKey));
		$this->controller->set('humanPluralName', Inflector::humanize($this->viewPath));
		$alias = null;
		if(!empty($this->ScaffoldModel->alias)) {
			$alias = array_combine(array_keys($this->ScaffoldModel->alias), array_keys($this->ScaffoldModel->alias));
		}
		$this->controller->set('alias', $alias);
		$this->controller->set('primaryKey', $this->ScaffoldModel->primaryKey);
		$this->controller->set('displayField', $this->ScaffoldModel->getDisplayfield());
		$this->__scaffold($params);
	 }
/**
 * Renders a view action of scaffolded model.
 *
 * @param array $params
 * @return A rendered view of a row from Models database table
 * @access private
 */
	function __scaffoldView($params) {
		if ($this->controller->_beforeScaffold('view')) {

			if(isset($params['pass'][0])){
				$this->ScaffoldModel->id = $params['pass'][0];
			} elseif (isset($this->controller->Session) && $this->controller->Session->valid != false) {

				$this->controller->Session->setFlash(sprintf(__("No id set for %s::view()", true), Inflector::humanize($this->modelKey)));
				$this->controller->redirect($this->redirect);
			} else {
				return $this->controller->flash(sprintf(__("No id set for %s::view()", true), Inflector::humanize($this->modelKey)),
																		'/' . Inflector::underscore($this->controller->viewPath));
			}
			$this->controller->data = $this->ScaffoldModel->read();
			$this->controller->set('data', $this->controller->data);
			$this->controller->set('fieldNames', $this->controller->generateFieldNames($this->controller->data, false));

			$this->controller->render($this->action, $this->layout, $this->__getViewFileName($this->action));
		} elseif ($this->controller->_scaffoldError('view') === false) {
			return $this->__scaffoldError();
		}
	}
/**
 * Renders index action of scaffolded model.
 *
 * @param array $params
 * @return A rendered view listing rows from Models database table
 * @access private
 */
	function __scaffoldIndex($params) {
		if ($this->controller->_beforeScaffold('index')) {
			$this->controller->set('fieldNames', $this->controller->generateFieldNames(null, false));
	 		$this->ScaffoldModel->recursive = 0;
	 		$this->controller->set('data', $this->controller->paginate());
	 		$this->controller->render($this->action, $this->layout, $this->__getViewFileName($this->action));
		} elseif ($this->controller->_scaffoldError('index') === false) {
			return $this->__scaffoldError();
		}
	}
/**
 * Renders an add or edit action for scaffolded model.
 *
 * @param array $params
 * @param string $params add or edit
 * @return A rendered view with a form to edit or add a record in the Models database table
 * @access private
 */
	function __scaffoldForm($action = 'edit') {
		$this->controller->render($action, $this->layout, $this->__getViewFileName('edit'));
	}
/**
 * Saves or updates the scaffolded model.
 *
 * @param array $params
 * @param string $type create or update
 * @return success on save/update, add/edit form if data is empty or error if save or update fails
 * @access private
 */
	function __scaffoldSave($params = array(), $action = 'edit') {
		$formName = 'Edit';
		$formAction = 'edit';
		$viewFileName = 'edit';
		$success = __('updated', true);

		if ($action === 'add') {
			$formName = 'New';
			$formAction = 'add';
			$viewFileName = 'add';
			$success = __('saved', true);
		}

		$this->controller->set('formName', $formName);

		if ($this->controller->_beforeScaffold($action)) {

			if (empty($this->controller->data)) {
				if ($action == 'edit') {
					if(isset($params['pass'][0])){
						$this->ScaffoldModel->id = $params['pass'][0];
					} elseif (isset($this->controller->Session) && $this->controller->Session->valid != false) {
						$this->controller->Session->setFlash(sprintf(__("No id set for %s::edit()", true), Inflector::humanize($this->modelKey)));
						$this->controller->redirect($this->redirect);
					} else {
						return $this->controller->flash(sprintf(__("No id set for %s::edit()", true), Inflector::humanize($this->modelKey)),
																	'/' . Inflector::underscore($this->controller->viewPath));
					}
					$this->controller->data = $this->ScaffoldModel->read();
					$this->controller->set('fieldNames', $this->controller->generateFieldNames($this->controller->data));
					$this->controller->set('data', $this->controller->data);
				} else {
					$this->controller->set('fieldNames', $this->controller->generateFieldNames());
				}
				return $this->__scaffoldForm($formAction);
			}

			$this->controller->set('fieldNames', $this->controller->generateFieldNames());
			$this->controller->cleanUpFields();

			if ($action == 'create') {
				$this->ScaffoldModel->create();
			}

			if ($this->ScaffoldModel->save($this->controller->data)) {
				if ($this->controller->_afterScaffoldSave($action)) {
					if (isset($this->controller->Session) && $this->controller->Session->valid != false) {
						$this->controller->Session->setFlash(sprintf(__('The %1$s has been %2$s', true), Inflector::humanize($this->modelClass), $success));
						$this->controller->redirect($this->redirect);
					} else {
						return $this->controller->flash(sprintf(__('The %1$s has been %2$s', true), Inflector::humanize($this->modelClass), $success), '/' . $this->viewPath);
					}
				} else {
					return $this->controller->_afterScaffoldSaveError($action);
				}
			} else {
				if (isset($this->controller->Session) && $this->controller->Session->valid != false) {
					$this->controller->Session->setFlash(__('Please correct errors below.', true));
				}

				$this->controller->set('data', $this->controller->data);
				$this->controller->set('fieldNames', $this->controller->generateFieldNames($this->__rebuild($this->controller->data)));
				$this->controller->validateErrors($this->ScaffoldModel);
				$this->controller->render($formAction, $this->layout, $this->__getViewFileName('edit'));
			}
		} else if($this->controller->_scaffoldError($action) === false) {
			return $this->__scaffoldError();
		}
	}
/**
 * Performs a delete on given scaffolded Model.
 *
 * @param array $params
 * @return success on delete error if delete fails
 * @access private
 */
	function __scaffoldDelete($params = array()) {
		if ($this->controller->_beforeScaffold('delete')) {

			if(isset($params['pass'][0])){
				$id = $params['pass'][0];
			} elseif (isset($this->controller->Session) && $this->controller->Session->valid != false) {
				$this->controller->Session->setFlash(sprintf(__("No id set for %s::delete()", true), Inflector::humanize($this->modelKey)));
				$this->controller->redirect($this->redirect);
			} else {
				return $this->controller->flash(sprintf(__("No id set for %s::delete()", true), Inflector::humanize($this->modelKey)),
																	'/' . Inflector::underscore($this->controller->viewPath));
			}

			if ($this->ScaffoldModel->del($id)) {
				if (isset($this->controller->Session) && $this->controller->Session->valid != false) {
					$this->controller->Session->setFlash(sprintf(__('The %1$s with id: %2$d has been deleted.', true), Inflector::humanize($this->modelClass), $id));
					$this->controller->redirect($this->redirect);
				} else {
					return $this->controller->flash(sprintf(__('The %1$s with id: %2$d has been deleted.', true), Inflector::humanize($this->modelClass), $id), '/' . $this->viewPath);
				}
			} else {
				if (isset($this->controller->Session) && $this->controller->Session->valid != false) {
					$this->controller->Session->setFlash(sprintf(__('There was an error deleting the %1$s with id: %2$d', true), Inflector::humanize($this->modelClass), $id));
					$this->controller->redirect($this->redirect);
				} else {
					return $this->controller->flash(sprintf(__('There was an error deleting the %1$s with id: %2$d', true), Inflector::humanize($this->modelClass), $id), '/' . $this->viewPath);
				}
			}

		} elseif ($this->controller->_scaffoldError('delete') === false) {
			return $this->__scaffoldError();
		}
	}
/**
 * Enter description here...
 *
 * @return error
 */
	function __scaffoldError() {
		$pathToViewFile = '';

		if (file_exists(APP . 'views' . DS . $this->viewPath . DS . 'scaffolds' . DS. 'scaffold.error.ctp')) {
			$pathToViewFile = APP . 'views' . DS . $this->viewPath . DS . 'scaffolds' . DS . 'scaffold.error.ctp';
		} elseif (file_exists(APP . 'views' . DS . $this->viewPath . DS . 'scaffolds' . DS. 'scaffold.error.thtml')) {
			$pathToViewFile = APP . 'views' . DS . $this->viewPath . DS . 'scaffolds' . DS . 'scaffold.error.thtml';
		} elseif (file_exists(APP . 'views' . DS . 'scaffolds' . DS . 'scaffold.error.ctp')) {
			$pathToViewFile = APP . 'views' . DS . 'scaffolds' . DS . 'scaffold.error.ctp';
		} elseif (file_exists(APP . 'views' . DS . 'scaffolds' . DS . 'scaffold.error.thtml')) {
			$pathToViewFile = APP . 'views' . DS . 'scaffolds' . DS . 'scaffold.error.thtml';
		} else {
			$pathToViewFile = LIBS . 'view' . DS . 'templates' . DS . 'errors' . DS . 'scaffold_error.ctp';
		}

		return $this->controller->render($this->action, $this->layout, $pathToViewFile);
	}
/**
 * When forms are submited the arrays need to be rebuilt if
 * an error occured, here the arrays are rebuilt to structure needed
 *
 * @param array $params data passed to forms
 * @return array rebuilds the association arrays to pass back to Controller::generateFieldNames()
 */
	function __rebuild($params) {
		foreach($params as $model => $field) {
			if (!empty($field) && is_array($field)) {
				$match=array_keys($field);

				if ($model == $match[0]) {
					$count = 0;

					foreach($field[$model] as $value) {
						$params[$model][$count][$this->ScaffoldModel->primaryKey] = $value;
						$count++;
					}
					unset ($params[$model][$model]);
				}
			}
		}
		return $params;
	}
/**
 * When methods are now present in a controller
 * scaffoldView is used to call default Scaffold methods if:
 * <code>
 * var $scaffold;
 * </code>
 * is placed in the controller's class definition.
 *
 * @param string $url
 * @param string $controller_class
 * @param array $params
 * @since Cake v 0.10.0.172
 * @access private
 */
	function __scaffold($params) {
		$db = &ConnectionManager::getDataSource($this->ScaffoldModel->useDbConfig);

		if (isset($db)) {
			if(empty($this->scaffoldActions)) {
				$this->scaffoldActions = array('index', 'list', 'view', 'add', 'create', 'edit', 'update', 'delete');
			}

			if (in_array($params['action'], $this->scaffoldActions)) {
				switch($params['action']) {
					case 'index':
						$this->__scaffoldIndex($params);
					break;
					case 'view':
						$this->__scaffoldView($params);
					break;
					case 'list':
						$this->__scaffoldIndex($params);
					break;
					case 'add':
						$this->__scaffoldSave($params, 'add');
					break;
					case 'edit':
						$this->__scaffoldSave($params, 'edit');
					break;
					case 'create':
						$this->__scaffoldSave($params, 'add');
					break;
					case 'update':
						$this->__scaffoldSave($params, 'edit');
					break;
					case 'delete':
						$this->__scaffoldDelete($params);
					break;
				}
			} else {
				return $this->cakeError('missingAction', array(array('className' => $this->controller->name . "Controller",
																						'base' => $this->controller->base,
																						'action' => $this->action,
																						'webroot' => $this->controller->webroot)));
			}
		} else {
			return $this->cakeError('missingDatabase', array(array('webroot' => $this->controller->webroot)));
		}
	}
/**
 * Returns scaffold view filename of given action's template file (.ctp) as a string.
 *
 * @param string $action Controller action to find template filename for
 * @return string Template filename
 * @access private
 */
	function __getViewFileName($action) {
		$action = Inflector::underscore($action);
		$scaffoldAction = 'scaffold.'.$action;
		$paths = Configure::getInstance();

		if (!is_null($this->webservices)) {
			$type = strtolower($this->webservices) . DS;
		} else {
			$type = null;
		}

		if (!is_null($this->plugin)) {

			if (file_exists(APP . 'views' . DS . 'plugins' . DS . $this->plugin . DS . $this->subDir . $type . $scaffoldAction . $this->ext)) {
				return APP . 'views' . DS . 'plugins' . DS . $this->plugin . DS . $this->subDir . $type . $scaffoldAction . $this->ext;
			} elseif (file_exists(APP . 'views' . DS . 'plugins' . DS . $this->plugin . DS . $this->subDir . $type . $scaffoldAction . $this->ext)) {
				return APP . 'views' . DS . 'plugins' . DS . $this->plugin . DS . $this->subDir . $type . $scaffoldAction . $this->ext;
			} elseif (file_exists(APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . $this->viewPath . DS . $scaffoldAction . $this->ext)) {
				return APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . $this->viewPath . DS . $scaffoldAction . $this->ext;
			} elseif (file_exists(APP . 'views' . DS . 'plugins' . DS . $this->plugin . DS . $this->subDir . $type . $scaffoldAction . '.ctp')) {
				return APP . 'views' . DS . 'plugins' . DS . $this->plugin . DS . $this->subDir . $type . $scaffoldAction . '.ctp';
			} elseif (file_exists(APP . 'views' . DS . 'plugins' . DS . $this->plugin . DS . $this->subDir . $type . $scaffoldAction . '.thtml')) {
				return APP . 'views' . DS . 'plugins' . DS . $this->plugin . DS . $this->subDir . $type . $scaffoldAction . '.thtml';
			} elseif (file_exists(APP . 'views' . DS . 'plugins' . DS . 'scaffolds'. DS . $this->subDir . $type . $scaffoldAction . '.ctp')) {
				return APP . 'views' . DS . 'plugins' . DS . 'scaffolds'. DS . $this->subDir . $type . $scaffoldAction . '.ctp';
			} elseif (file_exists(APP . 'views' . DS . 'plugins' . DS . 'scaffolds'. DS . $this->subDir . $type . $scaffoldAction . '.thtml')) {
				return APP . 'views' . DS . 'plugins' . DS . 'scaffolds'. DS . $this->subDir . $type . $scaffoldAction . '.thtml';
			} elseif (file_exists(APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . $this->viewPath . DS . $scaffoldAction . '.ctp')) {
				return APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . $this->viewPath . DS . $scaffoldAction . '.ctp';
			} elseif (file_exists(APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . $this->viewPath . DS . $scaffoldAction . '.thtml')) {
				return APP . 'plugins' . DS . $this->plugin . DS . 'views' . DS . $this->viewPath . DS . $scaffoldAction . '.thtml';
			}
		}

		foreach($paths->viewPaths as $path) {
			if (file_exists($path . $this->viewPath . DS . $this->subDir . $type . $scaffoldAction . $this->ext)) {
				return $path . $this->viewPath . DS . $this->subDir . $type . $scaffoldAction . $this->ext;
			} elseif (file_exists($path . $this->viewPath . DS . 'scaffolds' . DS . $this->subDir . $type . $scaffoldAction . $this->ext)) {
				return $path . $this->viewPath . DS . 'scaffolds' . DS . $this->subDir . $type . $scaffoldAction . $this->ext;
			} elseif (file_exists($path . $this->viewPath . DS . $this->subDir . $type . $scaffoldAction . '.ctp')) {
				return $path . $this->viewPath . DS . $this->subDir . $type . $scaffoldAction . '.ctp';
			} elseif (file_exists($path . $this->viewPath . DS . $this->subDir . $type . $scaffoldAction . '.thtml')) {
				return $path . $this->viewPath . DS . $this->subDir . $type . $scaffoldAction . '.thtml';
			} elseif (file_exists($path . $this->viewPath . DS . 'scaffolds' . DS . $this->subDir . $type . $scaffoldAction . '.ctp')) {
				return $path . $this->viewPath . DS . 'scaffolds' . DS . $this->subDir . $type . $scaffoldAction . '.ctp';
			} elseif (file_exists($path . $this->viewPath . DS . 'scaffolds' . DS . $this->subDir . $type . $scaffoldAction . '.thtml')) {
				return $path . $this->viewPath . DS . 'scaffolds' . DS . $this->subDir . $type . $scaffoldAction . '.thtml';
			}
		}
		return LIBS . 'view' . DS . 'templates' . DS . 'scaffolds' . DS . $action . '.ctp';
	}
}
?>