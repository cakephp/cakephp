<?php
/* SVN FILE: $Id$ */
/**
 * Scaffold.
 *
 * Automatic forms and actions generation for rapid web application development.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
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
 *  Name of controller to scaffold
 *
 * @var string
 */
 	var $name = null;
/**
 *  Name of view to render
 *
 * @var string
 */
	var $action = null;
/**
 * Class name of model
 *
 * @var unknown_type
 */
	var $modelClass = null;
/**
 * Registry key of model
 *
 * @var string
 */
	var $modelKey = null;
/**
 * View path for scaffolded controller
 *
 * @var string
 */
	var $viewPath = null;
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
		$this->name = $controller->name;
		$this->action = $controller->action;

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
		$this->viewPath = Inflector::underscore($this->name);
		$this->scaffoldTitle = Inflector::humanize($this->viewPath);
		$this->scaffoldActions = $controller->scaffold;
		$this->controller->pageTitle= 'Scaffold :: ' . Inflector::humanize($this->action) . ' :: ' . $this->scaffoldTitle;
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
 * Renders a view view of scaffolded Model.
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
				$this->controller->Session->setFlash('No id set for ' . Inflector::humanize($this->modelKey) . '::view().');
				$this->controller->redirect('/' . Inflector::underscore($this->controller->viewPath));
			} else {
				return $this->controller->flash('No id set for ' . Inflector::humanize($this->modelKey) . '::view().',
																		'/' . Inflector::underscore($this->controller->viewPath));
			}
			$this->controller->data = $this->ScaffoldModel->read();
			$this->controller->set('data', $this->controller->data);
			$this->controller->set('fieldNames', $this->controller->generateFieldNames($this->controller->data, false));

			if (file_exists(APP . 'views' . DS . $this->viewPath . DS . 'scaffold.view.thtml')) {
				return $this->controller->render($this->action, '',
															APP . 'views' . DS . $this->viewPath . DS . 'scaffold.view.thtml');
			} elseif (file_exists(APP . 'views' . DS . 'scaffold' . DS . 'scaffold.view.thtml')) {
					 return $this->controller->render($this->action, '',
					 											APP . 'views' . DS . 'scaffold' . DS . 'scaffold.view.thtml');
			} else {
				return $this->controller->render($this->action, '',
															LIBS . 'view' . DS . 'templates' . DS . 'scaffolds' . DS . 'view.thtml');
			}

		  } elseif ($this->controller->_scaffoldError('view') === false) {
		  	return $this->__scaffoldError();
		  }
	 }
/**
 * Renders List view of scaffolded Model.
 *
 * @param array $params
 * @return A rendered view listing rows from Models database table
 * @access private
 */
	function __scaffoldIndex($params) {
		if ($this->controller->_beforeScaffold('index')) {

			$this->controller->set('fieldNames', $this->controller->generateFieldNames(null, false));
	 		$this->ScaffoldModel->recursive = 0;
	 		$this->controller->set('data', $this->ScaffoldModel->findAll(null, null, $this->modelClass.'.'.$this->ScaffoldModel->primaryKey.' DESC'));

	 		if (file_exists(APP . 'views' . DS . $this->viewPath . DS . 'scaffold.index.thtml')) {
	 			return $this->controller->render($this->action, '',
															APP . 'views' . DS . $this->viewPath . DS . 'scaffold.index.thtml');
	 		} elseif (file_exists(APP . 'views' . DS . 'scaffold' . DS . 'scaffold.index.thtml')) {
	 			return $this->controller->render($this->action, '',
															APP . 'views' . DS . 'scaffold' . DS . 'scaffold.index.thtml');
	 		} else {
	 			return $this->controller->render($this->action, '',
	 														LIBS . 'view' . DS . 'templates' . DS . 'scaffolds' . DS . 'index.thtml');
	 		}

	 	} elseif ($this->controller->_scaffoldError('index') === false) {
	 		return $this->__scaffoldError();
	 	}
	 }

/**
 * Renders an Add or Edit view for scaffolded Model.
 *
 * @param array $params
 * @param string $params add or edit
 * @return A rendered view with a form to edit or add a record in the Models database table
 * @access private
 */
	function __scaffoldForm($params = array(), $action = 'edit') {
		if ($this->controller->_beforeScaffold($action)) {

			$this->controller->set('formName', ucwords($action));

			if ($action == 'edit') {

				if(isset($params['pass'][0])){
					$this->ScaffoldModel->id = $params['pass'][0];
				} elseif (isset($this->controller->Session) && $this->controller->Session->valid != false) {
					$this->controller->Session->setFlash('No id set for ' . Inflector::humanize($this->modelKey) . '::edit().');
					$this->controller->redirect('/' . Inflector::underscore($this->controller->viewPath));
				} else {
					return $this->controller->flash('No id set for ' . Inflector::humanize($this->modelKey) . '::edit().',
																		'/' . Inflector::underscore($this->controller->viewPath));
				}
				$this->controller->data = $this->ScaffoldModel->read();
				$this->controller->set('fieldNames', $this->controller->generateFieldNames($this->controller->data));
				$this->controller->set('data', $this->controller->data);
				} else {
					$this->controller->set('fieldNames', $this->controller->generateFieldNames());
				}

				if (file_exists(APP . 'views' . DS . $this->viewPath . DS . 'scaffold.' . $action . '.thtml')) {
					 return $this->controller->render($action, '', APP . 'views' . DS . $this->viewPath . DS . 'scaffold.' . $action . '.thtml');
				} elseif(file_exists(APP . 'views' . DS . 'scaffold' . DS . 'scaffold.' . $action . '.thtml')) {
					 return $this->controller->render($action, '', APP . 'views' . DS . 'scaffold' . DS . 'scaffold.' . $action . '.thtml');
				} else {
					 return $this->controller->render($action, '', LIBS . 'view' . DS . 'templates' . DS . 'scaffolds' . DS . $action . '.thtml');
				}

		  } else if($this->controller->_scaffoldError($action) === false) {
				return $this->__scaffoldError();
		  }
	 }
/**
 * Saves or updates a model.
 *
 * @param array $params
 * @param string $type create or update
 * @return success on save/update, add/edit form if data is empty or error if save or update fails
 * @access private
 */
	function __scaffoldSave($params = array(), $action = 'update') {
		$formName  = 'Edit';
		$formAction = 'edit';
		$viewFileName = 'edit';
		$success = 'updated';

		if ($action === 'create') {
			$formName  = 'New';
			$formAction = 'add';
			$viewFileName = 'add';
			$success = 'saved';
		}

		$this->controller->set('formName', $formName);

		if ($this->controller->_beforeScaffold($action)) {

			if (empty($this->controller->data)) {
				return $this->__scaffoldForm($params, $formAction);
			}

			$this->controller->set('fieldNames', $this->controller->generateFieldNames());
			$this->controller->cleanUpFields();

			if ($action == 'create') {
				$this->ScaffoldModel->create();
			}

			if ($this->ScaffoldModel->save($this->controller->data)) {
				if ($this->controller->_afterScaffoldSave($action)) {
					if (isset($this->controller->Session) && $this->controller->Session->valid != false) {
						$this->controller->Session->setFlash('The ' . Inflector::humanize($this->modelClass) . ' has been ' . $success . '.');
						$this->controller->redirect('/' . $this->viewPath);
					} else {
						return $this->controller->flash('The ' . Inflector::humanize($this->modelClass) . ' has been ' . $success. '.','/' . $this->viewPath);
					}
				} else {
					return $this->controller->_afterScaffoldSaveError($action);
				}
			} else {
				if (isset($this->controller->Session) && $this->controller->Session->valid != false) {
				  $this->controller->Session->setFlash('Please correct errors below.');
				}

				$this->controller->set('data', $this->controller->data);
				$this->controller->set('fieldNames', $this->controller->generateFieldNames($this->__rebuild($this->controller->data)));
				$this->controller->validateErrors($this->ScaffoldModel);

				if (file_exists(APP . 'views' . DS . $this->viewPath . DS . 'scaffolds' . DS . 'scaffold.' . $viewFileName . '.thtml')) {
				  return $this->controller->render($viewFileName, '', APP . 'views' . DS . $this->viewPath . DS . 'scaffolds' . DS . 'scaffold.' . $viewFileName . '.thtml');
				} elseif(file_exists(APP . 'views' . DS . 'scaffold' . DS . 'scaffold.' . $viewFileName . '.thtml')) {
				  return $this->controller->render($viewFileName, '', APP . 'views' . DS . 'scaffold' . DS . 'scaffold.' . $viewFileName . '.thtml');
				} else {
				  return $this->controller->render($viewFileName, '', LIBS . 'view' . DS . 'templates' . DS . 'scaffolds' . DS . 'edit.thtml');
				}
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
				$this->controller->Session->setFlash('No id set for ' . Inflector::humanize($this->modelKey) . '::delete().');
				$this->controller->redirect('/' . Inflector::underscore($this->controller->viewPath));
			} else {
				return $this->controller->flash('No id set for ' . Inflector::humanize($this->modelKey) . '::delete().',
																	'/' . Inflector::underscore($this->controller->viewPath));
			}

			if ($this->ScaffoldModel->del($id)) {
				if (isset($this->controller->Session) && $this->controller->Session->valid != false) {
					$this->controller->Session->setFlash('The ' . Inflector::humanize($this->modelClass) . ' with id: ' . $id . ' has been deleted.');
					$this->controller->redirect('/' . $this->viewPath);
				} else {
					return $this->controller->flash('The ' . Inflector::humanize($this->modelClass) . ' with id: ' . $id . ' has been deleted.', '/' . $this->viewPath);
				}
			} else {
				if (isset($this->controller->Session) && $this->controller->Session->valid != false) {
					$this->controller->Session->setFlash('There was an error deleting the ' . Inflector::humanize($this->modelClass) . ' with the id ' . $id);
					$this->controller->redirect('/' . $this->viewPath);
				} else {
					return $this->controller->flash('There was an error deleting the ' . Inflector::humanize($this->modelClass) . ' with the id ' . $id, '/' . $this->viewPath);
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
		if (file_exists(APP . 'views' . DS . $this->viewPath . DS . 'scaffolds' . DS. 'scaffold.error.thtml')) {
			return $this->controller->render($this->action, '',
														APP . 'views' . DS . $this->viewPath . DS . 'scaffolds' . DS . 'scaffold.error.thtml');
		} elseif (file_exists(APP . 'views' . DS . 'scaffold' . DS . 'scaffold.error.thtml')) {
				return $this->controller->render($this->action, '',
															APP . 'views' . DS . 'scaffold' . DS . 'scaffold.error.thtml');
		} else {
			return $this->controller->render($this->action, '',
														LIBS . 'view' . DS . 'templates' . DS . 'errors' . DS . 'scaffold_error.thtml');
		}
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
						$this->__scaffoldForm($params, 'add');
					break;
					case 'edit':
						$this->__scaffoldForm($params, 'edit');
					break;
					case 'create':
						$this->__scaffoldSave($params, 'create');
					break;
					case 'update':
						$this->__scaffoldSave($params, 'update');
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
}
?>