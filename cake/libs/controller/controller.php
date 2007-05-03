<?php
/* SVN FILE: $Id$ */
/**
 * Base controller class.
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
 * @since			CakePHP(tm) v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Include files
 */
	uses('controller' . DS . 'component', 'view' . DS . 'view');
/**
 * Controller
 *
 * Application controller (controllers are where you put all the actual code)
 * Provides basic functionality, such as rendering views (aka displaying templates).
 * Automatically selects model name from on singularized object class name
 * and creates the model object if proper class exists.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller
 *
 */
class Controller extends Object {
/**
 * Tshe name of this controller. Controller names are plural, named after the model they manipulate.
 *
 * @var string
 * @access public
 */
	var $name = null;
/**
 * Stores the current URL, based from the webroot.
 *
 * @var string
 * @access public
 */
	var $here = null;
/**
 * The webroot of the application. Helpful if your application is placed in a folder under the current domain name.
 *
 * @var string
 * @access public
 */
	var $webroot = null;
/**
 * The name of the controller action that was requested.
 *
 * @var string
 * @access public
 */
	var $action = null;
/**
 * An array containing the class names of models this controller uses.
 *
 * Example: var $uses = array('Product', 'Post', 'Comment');
 *
 * @var mixed A single name as a string or a list of names as an array.
 * @access protected
 */
	var $uses = false;
/**
 * An array containing the names of helpers this controller uses. The array elements should
 * not contain the -Helper part of the classname.
 *
 * Example: var $helpers = array('Html', 'Javascript', 'Time', 'Ajax');
 *
 * @var mixed A single name as a string or a list of names as an array.
 * @access protected
 */
	var $helpers = array('Html');
/**
 * Parameters received in the current request: GET and POST data, information
 * about the request, etc.
 *
 * @var array
 * @access public
 */
	var $params = array();
/**
 * Data POSTed to the controller using the HtmlHelper. Data here is accessible
 * using the $this->data['ModelName']['fieldName'] pattern.
 *
 * @var array
 * @access public
 */
	var $data = array();
/**
 * Holds pagination defaults for controller actions. The keys that can be included
 * in this array are: 'conditions', 'fields', 'order', 'limit', 'page', and 'recursive',
 * similar to the parameters of Model->findAll().
 *
 * Pagination defaults can also be supplied in a model-by-model basis by using
 * the name of the model as a key for a pagination array:
 *
 * var $paginate = array(
 * 		'Post' => array(...),
 * 		'Comment' => array(...)
 * 	);
 *
 * See the manual chapter on Pagination for more information.
 *
 * @var array
 * @access public
 */
	var $paginate = array('limit' => 20, 'page' => 1);
/**
 * The name of the views subfolder containing views for this controller.
 *
 * @var string
 */
	var $viewPath = null;
/**
 * Sub-path for layout files.
 *
 * @var string
 */
	var $layoutPath = null;
/**
 * Contains variables to be handed to the view.
 *
 * @var array
 * @access public
 */
	var $viewVars = array();
/**
 * Text to be used for the $title_for_layout layout variable (usually
 * placed inside <title> tags.)
 *
 * @var boolean
 * @access public
 */
	var $pageTitle = false;
/**
 * An array containing the class names of the models this controller uses.
 *
 * @var array Array of model objects.
 * @access public
 */
	var $modelNames = array();
/**
 * Base URL path.
 *
 * @var string
 * @access public
 */
	var $base = null;
/**
 * The name of the layout file to render views inside of. The name specified
 * is the filename of the layout in /app/views/layouts without the .ctp
 * extension.
 *
 * @var string
 * @access public
 */
	var $layout = 'default';
/**
 * Set to true to automatically render the view
 * after action logic.
 *
 * @var boolean
 * @access public
 */
	var $autoRender = true;
/**
 * Set to true to automatically render the layout around views.
 *
 * @var boolean
 * @access public
 */
	var $autoLayout = true;
/**
 * Array containing the names of components this controller uses. Component names
 * should not contain the -Component portion of the classname.
 *
 * Example: var $components = array('Session', 'RequestHandler', 'Acl');
 *
 * @var array
 * @access public
 */
	var $components = array();
/**
 * The name of the View class this controller sends output to.
 *
 * @var string
 * @access public
 */
	var $view = 'View';
/**
 * File extension for view templates. Defaults to Cake's conventional ".ctp".
 *
 * @var string
 * @access public
 */
	var $ext = '.ctp';
/**
 * Instance of $view class create by a controller
 *
 * @var object
 * @access private
 */
	var $__viewClass = null;
/**
 * The output of the requested action.  Contains either a variable
 * returned from the action, or the data of the rendered view;
 * You can use this var in Child controllers' afterFilter() to alter output.
 *
 * @var string
 * @access public
 */
	var $output = null;
/**
 * Automatically set to the name of a plugin.
 *
 * @var string
 * @access public
 */
	var $plugin = null;
/**
 * Used to define methods a controller that will be cached. To cache a
 * single action, the value is set to an array containing keys that match
 * action names and values that denote cache expiration times (in seconds).
 *
 * Example: var $cacheAction = array(
		'view/23/' => 21600,
		'recalled/' => 86400
	);
 *
 * $cacheAction can also be set to a strtotime() compatible string. This
 * marks all the actions in the controller for view caching.
 *
 * @var mixed
 * @access public
 */
	var $cacheAction = false;
/**
 * Used to create cached instances of models a controller uses.
 * When set to true, all models related to the controller will be cached.
 * This can increase performance in many cases.
 *
 * @var boolean
 * @access public
 */
	var $persistModel = false;
/**
 * Used in CakePHP webservices routing.
 *
 * @var unknown_type
 */
	var $webservices = null;
/**
 * Set to true to enable named URL parameters (/controller/action/name:value).
 *
 * @var mixed
 */
	var $namedArgs = true;
/**
 * The character that separates named arguments in URLs.
 *
 *  Example URL: /posts/view/title:first+post/category:general
 *
 * @var string
 */
	var $argSeparator = ':';
/**
 * Constructor.
 *
 */
	function __construct() {
		if ($this->name === null) {
			$r = null;

			if (!preg_match('/(.*)Controller/i', get_class($this), $r)) {
				die (__("Controller::__construct() : Can not get or parse my own class name, exiting."));
			}
			$this->name = $r[1];
		}

		if ($this->viewPath == null) {
			$this->viewPath = Inflector::underscore($this->name);
		}

		$this->modelClass = Inflector::classify($this->name);
		$this->modelKey = Inflector::underscore($this->modelClass);

		if (is_subclass_of($this, 'AppController')) {
			$appVars = get_class_vars('AppController');
			$uses = $appVars['uses'];
			$merge = array('components', 'helpers');

			if ($uses == $this->uses && !empty($this->uses)) {
				array_unshift($this->uses, $this->modelClass);
			} elseif ($this->uses !== null || $this->uses !== false) {
				$merge[] = 'uses';
			}

			foreach($merge as $var) {
				if (isset($appVars[$var]) && !empty($appVars[$var]) && is_array($this->{$var})) {
					$this->{$var} = array_merge($this->{$var}, array_diff($appVars[$var], $this->{$var}));
				}
			}
		}
		parent::__construct();
	}

	function _initComponents() {
		$component = new Component();
		$component->init($this);
	}
/**
 * Loads and instantiates models required by this controller.
 * If Controller::persistModel; is true, controller will create cached model instances on first request,
 * additional request will used cached models
 *
 * @return mixed true when single model found and instance created error returned if models not found.
 * @access public
 */
	function constructClasses() {
		if($this->uses === null || ($this->uses === array())){
			return false;
		}
		if (empty($this->passedArgs) || !isset($this->passedArgs['0'])) {
			$id = false;
		} else {
			$id = $this->passedArgs['0'];
		}
		$cached = false;
		$object = null;

		if($this->uses === false) {
			if(!class_exists($this->modelClass)){
				loadModel($this->modelClass);
			}
		}

		if (class_exists($this->modelClass) && ($this->uses === false)) {
			if ($this->persistModel === true) {
				$cached = $this->_persist($this->modelClass, null, $object);
			}

			if (($cached === false)) {
				$model =& new $this->modelClass($id);
				$this->modelNames[] = $this->modelClass;
				$this->{$this->modelClass} =& $model;

				if ($this->persistModel === true) {
					$this->_persist($this->modelClass, true, $model);
					$registry = ClassRegistry::getInstance();
					$this->_persist($this->modelClass . 'registry', true, $registry->_objects, 'registry');
				}
			} else {
				$this->_persist($this->modelClass . 'registry', true, $object, 'registry');
				$this->_persist($this->modelClass, true, $object);
				$this->modelNames[] = $this->modelClass;
			}
			return true;
		} elseif ($this->uses === false) {
			return $this->cakeError('missingModel', array(array('className' => $this->modelClass, 'webroot' => '', 'base' => $this->base)));
		}

		if ($this->uses) {
			$uses = is_array($this->uses) ? $this->uses : array($this->uses);
			$this->modelClass = $uses[0];

			foreach($uses as $modelClass) {
				$id = false;
				$cached = false;
				$object = null;
				$modelKey = Inflector::underscore($modelClass);

				if(!class_exists($modelClass)){
					loadModel($modelClass);
				}

				if (class_exists($modelClass)) {
					if ($this->persistModel === true) {
						$cached = $this->_persist($modelClass, null, $object);
					}

					if (($cached === false)) {
						$model =& new $modelClass($id);
						$this->modelNames[] = $modelClass;
						$this->{$modelClass} =& $model;

						if ($this->persistModel === true) {
							$this->_persist($modelClass, true, $model);
							$registry = ClassRegistry::getInstance();
							$this->_persist($modelClass . 'registry', true, $registry->_objects, 'registry');
						}
					} else {
						$this->_persist($modelClass . 'registry', true, $object, 'registry');
						$this->_persist($modelClass, true, $object);
						$this->modelNames[] = $modelClass;
					}
				} else {
					return $this->cakeError('missingModel', array(array('className' => $modelClass, 'webroot' => '', 'base' => $this->base)));
				}
			}
			return true;
		}
	}
/**
 * Redirects to given $url, after turning off $this->autoRender.
 * Please notice that the script execution is not stopped after the redirect.
 *
 * @param mixed $url A string or array-based URL pointing to another location
 *                   within the app, or an absolute URL
 * @param integer $status Optional HTTP status code
 * @param boolean $exit If true, exit() will be called after the redirect
 * @access public
 */
	function redirect($url, $status = null, $exit = false) {
		$this->autoRender = false;

		if (is_array($status)) {
			extract($status, EXTR_OVERWRITE);
		}

		if (function_exists('session_write_close')) {
			session_write_close();
		}

		if (!empty($status)) {
			$codes = array(
				100 => "Continue",
				101 => "Switching Protocols",
				200 => "OK",
				201 => "Created",
				202 => "Accepted",
				203 => "Non-Authoritative Information",
				204 => "No Content",
				205 => "Reset Content",
				206 => "Partial Content",
				300 => "Multiple Choices",
				301 => "Moved Permanently",
				302 => "Found",
				303 => "See Other",
				304 => "Not Modified",
				305 => "Use Proxy",
				307 => "Temporary Redirect",
				400 => "Bad Request",
				401 => "Unauthorized",
				402 => "Payment Required",
				403 => "Forbidden",
				404 => "Not Found",
				405 => "Method Not Allowed",
				406 => "Not Acceptable",
				407 => "Proxy Authentication Required",
				408 => "Request Time-out",
				409 => "Conflict",
				410 => "Gone",
				411 => "Length Required",
				412 => "Precondition Failed",
				413 => "Request Entity Too Large",
				414 => "Request-URI Too Large",
				415 => "Unsupported Media Type",
				416 => "Requested range not satisfiable",
				417 => "Expectation Failed",
				500 => "Internal Server Error",
				501 => "Not Implemented",
				502 => "Bad Gateway",
				503 => "Service Unavailable",
				504 => "Gateway Time-out"
			);
			if (is_string($status)) {
				$codes = array_combine(array_values($codes), array_keys($codes));
			}
			if (isset($codes[$status])) {
				$code = ife(is_numeric($status), $status, $codes[$status]);
				$msg  = ife(is_string($status),  $status, $codes[$status]);
				$status = "HTTP/1.1 {$code} {$msg}";
			} else {
				$status = null;
			}
		}
		if (!empty($status)) {
			header($status);
		}
		if ($url !== null) {
			header('Location: ' . Router::url($url, true));
		}
		if (!empty($status)) {
			header($status);
		}
		if ($exit) {
			exit();
		}
	}
/**
 * Saves a variable to use inside a template.
 *
 * @param mixed $one A string or an array of data.
 * @param mixed $two Value in case $one is a string (which then works as the key).
 * 				Unused if $one is an associative array, otherwise serves as the values to $one's keys.
 * @return void
 */
	function set($one, $two = null) {
		$data = array();

		if (is_array($one)) {
			if (is_array($two)) {
				$data = array_combine($one, $two);
			} else {
				$data = $one;
			}
		} else {
			$data = array($one => $two);
		}

		foreach($data as $name => $value) {
			if ($name == 'title') {
				$this->pageTitle = $value;
			} else {
				$this->viewVars[$name] = $value;
			}
		}
	}
/**
 * Internally redirects one action to another
 *
 * @param string $action The new action to be redirected to
 * @param mixed  Any other parameters passed to this method will be passed as
 *               parameters to the new action.
 */
	function setAction($action) {
		$this->action = $action;
		$args = func_get_args();
		unset($args[0]);
		call_user_func_array(array(&$this, $action), $args);
	}
/**
 * Returns number of errors in a submitted FORM.
 *
 * @return int Number of errors
 */
	function validate() {
		$args = func_get_args();
		$errors = call_user_func_array(array(&$this, 'validateErrors'), $args);

		if ($errors === false) {
			return 0;
		}
		return count($errors);
	}
/**
 * Validates a FORM according to the rules set up in the Model.
 *
 * @return int Number of errors
 */
	function validateErrors() {
		$objects = func_get_args();
		if (!count($objects)) {
			return false;
		}

		$errors = array();
		foreach($objects as $object) {
			$this->{$object->name}->set($object->data);
			$errors = array_merge($errors, $this->{$object->name}->invalidFields());
		}
		return $this->validationErrors = (count($errors) ? $errors : false);
	}
/**
 * Gets an instance of the view object & prepares it for rendering the output, then
 * asks the view to actualy do the job.
 *
 * @param unknown_type $action
 * @param unknown_type $layout
 * @param unknown_type $file
 * @return unknown
 */
	function render($action = null, $layout = null, $file = null) {
		$viewClass = $this->view;
		if ($this->view != 'View') {
			$viewClass = $this->view . 'View';
			loadView($this->view);
		}
		$this->beforeRender();
		$this->params['models'] = $this->modelNames;

		$this->__viewClass =& new $viewClass($this);
		if (!empty($this->modelNames)) {
			$models = array();
			foreach($this->modelNames as $currentModel) {
				if (isset($this->$currentModel) && is_a($this->$currentModel, 'Model')) {
					$models[] = Inflector::underscore($currentModel);
				}
				if (isset($this->$currentModel) && is_a($this->$currentModel, 'Model') && !empty($this->$currentModel->validationErrors)) {
					$this->__viewClass->validationErrors[Inflector::camelize($currentModel)] =& $this->$currentModel->validationErrors;
				}
			}
			$models = array_diff(ClassRegistry::keys(), $models);
			foreach($models as $currentModel) {
				if (ClassRegistry::isKeySet($currentModel)) {
					$currentObject =& ClassRegistry::getObject($currentModel);
					if(is_a($currentObject, 'Model') && !empty($currentObject->validationErrors)) {
						$this->__viewClass->validationErrors[Inflector::camelize($currentModel)] =& $currentObject->validationErrors;
					}
				}
			}
		}

		$this->autoRender = false;
		return $this->__viewClass->render($action, $layout, $file);
	}
/**
 * Gets the referring URL of this request
 *
 * @param string $default Default URL to use if HTTP_REFERER cannot be read from headers
 * @param boolean $local If true, restrict referring URLs to local server
 * @access public
 */
	function referer($default = null, $local = false) {
		$ref = env('HTTP_REFERER');
		$base = FULL_BASE_URL . $this->webroot;

		if ($ref != null && defined('FULL_BASE_URL')) {
			if (strpos($ref, $base) === 0) {
				return substr($ref, strlen($base) - 1);
			} elseif(!$local) {
				return $ref;
			}
		}

		if ($default != null) {
			return $default;
		} else {
			return '/';
		}
	}
/**
 * Tells the browser not to cache the results of the current request
 *
 * @return void
 * @access public
 */
	function disableCache() {
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	}
/**
 * Shows a message to the user $time seconds, then redirects to $url
 * Uses flash.thtml as a layout for the messages
 *
 * @param string $message Message to display to the user
 * @param string $url Relative URL to redirect to after the time expires
 * @param int $time Time to show the message
 */
	function flash($message, $url, $pause = 1) {
		$this->autoRender = false;
		$this->autoLayout = false;
		$this->set('url', Router::url($url));
		$this->set('message', $message);
		$this->set('pause', $pause);
		$this->set('page_title', $message);

		if (file_exists(VIEWS . 'layouts' . DS . 'flash.ctp')) {
			$flash = VIEWS . 'layouts' . DS . 'flash.ctp';
		} elseif (file_exists(VIEWS . 'layouts' . DS . 'flash.thtml')) {
			$flash = VIEWS . 'layouts' . DS . 'flash.thtml';
		} elseif ($flash = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . "layouts" . DS . 'flash.ctp')) {
		}
		$this->render(null, false, $flash);
	}
/**
 * This function creates a $fieldNames array for the view to use.
 * @todo Map more database field types to html form fields.
 * @todo View the database field types from all the supported databases.
 *
 */
	function generateFieldNames($data = null, $doCreateOptions = true) {
		$fieldNames = array();
		$model = $this->modelClass;
		$modelKey = $this->modelKey;
		$modelObj =& ClassRegistry::getObject($modelKey);

		foreach($modelObj->_tableInfo->value as $column) {
 			if ($modelObj->isForeignKey($column['name'])) {
				foreach($modelObj->belongsTo as $associationName => $assoc) {
					if($column['name'] == $assoc['foreignKey']) {
						$fkNames = $modelObj->keyToTable[$column['name']];
						$fieldNames[$column['name']]['table'] = $fkNames[0];
						$fieldNames[$column['name']]['label'] = Inflector::humanize($associationName);
						$fieldNames[$column['name']]['prompt'] = $fieldNames[$column['name']]['label'];
						$fieldNames[$column['name']]['model'] = Inflector::classify($associationName);
						$fieldNames[$column['name']]['modelKey'] = Inflector::underscore($modelObj->tableToModel[$fieldNames[$column['name']]['table']]);
						$fieldNames[$column['name']]['controller'] = Inflector::pluralize($fieldNames[$column['name']]['modelKey']);
						$fieldNames[$column['name']]['foreignKey'] = true;
						break;
					}
				}


			} else {
				$fieldNames[$column['name']]['label'] = Inflector::humanize($column['name']);
				$fieldNames[$column['name']]['prompt'] = $fieldNames[$column['name']]['label'];
			}
			$fieldNames[$column['name']]['tagName'] = $model . '/' . $column['name'];
			$fieldNames[$column['name']]['name'] = $column['name'];
			$fieldNames[$column['name']]['class'] = 'optional';
			$validationFields = $modelObj->validate;
			if (isset($validationFields[$column['name']])) {
				if (VALID_NOT_EMPTY == $validationFields[$column['name']]) {
					$fieldNames[$column['name']]['required'] = true;
					$fieldNames[$column['name']]['class'] = 'required';
					$fieldNames[$column['name']]['error'] = "Required Field";
				}
			}
			$lParenPos = strpos($column['type'], '(');
			$rParenPos = strpos($column['type'], ')');

			if (false != $lParenPos) {
				$type = substr($column['type'], 0, $lParenPos);
				$fieldLength = substr($column['type'], $lParenPos + 1, $rParenPos - $lParenPos - 1);
			} else {
				$type = $column['type'];
			}
			switch($type) {
				case "text":
					$fieldNames[$column['name']]['type'] = 'textarea';
					$fieldNames[$column['name']]['cols'] = '30';
					$fieldNames[$column['name']]['rows'] = '10';
				break;
				case "string":
					if (isset($fieldNames[$column['name']]['foreignKey'])) {
						$fieldNames[$column['name']]['type'] = 'select';
						$fieldNames[$column['name']]['options'] = array();
						$otherModelObj =& ClassRegistry::getObject($fieldNames[$column['name']]['modelKey']);
						if (is_object($otherModelObj)) {
							if ($doCreateOptions) {
								$fieldNames[$column['name']]['options'] = $otherModelObj->generateList();
							}
							$fieldNames[$column['name']]['selected'] = $data[$model][$column['name']];
						}
					} else {
						$fieldNames[$column['name']]['type'] = 'text';
					}
				break;
				case "boolean":
						$fieldNames[$column['name']]['type'] = 'checkbox';
				break;
				case "integer":
				case "float":
					if (strcmp($column['name'], $this->$model->primaryKey) == 0) {
						$fieldNames[$column['name']]['type'] = 'hidden';
					} else if(isset($fieldNames[$column['name']]['foreignKey'])) {
						$fieldNames[$column['name']]['type'] = 'select';
						$fieldNames[$column['name']]['options'] = array();

						$otherModelObj =& ClassRegistry::getObject($fieldNames[$column['name']]['modelKey']);
						if (is_object($otherModelObj)) {
							if ($doCreateOptions) {
								$fieldNames[$column['name']]['options'] = $otherModelObj->generateList();
							}
							$fieldNames[$column['name']]['selected'] = $data[$model][$column['name']];
						}
					} else {
						$fieldNames[$column['name']]['type'] = 'text';
					}

				break;
				case "enum":
					$fieldNames[$column['name']]['type'] = 'select';
					$fieldNames[$column['name']]['options'] = array();
					$enumValues = split(',', $fieldLength);

					foreach($enumValues as $enum) {
						$enum = trim($enum, "'");
						$fieldNames[$column['name']]['options'][$enum] = $enum;
					}
					$fieldNames[$column['name']]['selected'] = $data[$model][$column['name']];
				break;
				case "date":
				case "datetime":
				case "time":
				case "year":
					if (0 != strncmp("created", $column['name'], 7) && 0 != strncmp("modified", $column['name'], 8) && 0 != strncmp("updated", $column['name'], 7)) {
						$fieldNames[$column['name']]['type'] = $type;
						if (isset($data[$model][$column['name']])) {
							$fieldNames[$column['name']]['selected'] = $data[$model][$column['name']];
						} else {
							$fieldNames[$column['name']]['selected'] = null;
						}
					} else {
						unset($fieldNames[$column['name']]);
					}
				break;
				default:
				break;
			}
		}

		foreach($modelObj->hasAndBelongsToMany as $associationName => $assocData) {
			$otherModelKey = Inflector::underscore($assocData['className']);
			$otherModelObj = &ClassRegistry::getObject($otherModelKey);
			if ($doCreateOptions) {
				$fieldNames[$associationName]['model'] = $associationName;
				$fieldNames[$associationName]['label'] = "Related " . Inflector::humanize(Inflector::pluralize($associationName));
				$fieldNames[$associationName]['prompt'] = $fieldNames[$associationName]['label'];
				$fieldNames[$associationName]['type'] = "select";
				$fieldNames[$associationName]['multiple'] = "multiple";
				$fieldNames[$associationName]['tagName'] = $associationName . '/' . $associationName;
				$fieldNames[$associationName]['name'] = $associationName;
				$fieldNames[$associationName]['class'] = 'optional';
				$fieldNames[$associationName]['options'] = $otherModelObj->generateList();
				if (isset($data[$associationName])) {
					$fieldNames[$associationName]['selected'] = $this->_selectedArray($data[$associationName], $otherModelObj->primaryKey);
				}
			}
		}

		return $fieldNames;
	}
/**
 * Converts POST'ed model data to a model conditions array, suitable for a find
 * or findAll Model query
 *
 * @param array $data POST'ed data organized by model and field
 * @param mixed $op A string containing an SQL comparison operator, or an array matching operators to fields
 * @param string $bool SQL boolean operator: AND, OR, XOR, etc.
 * @param boolean $exclusive If true, and $op is an array, fields not included in $op will not be included in the returned conditions
 * @return array An array of model conditions
 */
	function postConditions($data = array(), $op = null, $bool = 'AND', $exclusive = false) {
		if ((!is_array($data) || empty($data)) && empty($this->data)) {
			return null;
		} elseif (!empty($this->data)) {
			$data = $this->data;
		}

		$cond = array();
		if ($op === null) {
			$op = '';
		}

		foreach($data as $model => $fields) {
			foreach($fields as $field => $value) {
				$key = $model . '.' . $field;
				if (is_string($op)) {
					$cond[$key] = $this->__postConditionMatch($op, $value);
				} elseif (is_array($op)) {
					$opFields = array_keys($op);
					if (in_array($key, $opFields) || in_array($field, $opFields)) {
						if (in_array($key, $opFields)) {
							$cond[$key] = $this->__postConditionMatch($op[$key], $value);
						} else {
							$cond[$key] = $this->__postConditionMatch($op[$field], $value);
						}
					} elseif (!$exclusive) {
						$cond[$key] = $this->__postConditionMatch(null, $value);
					}
				}
			}
		}
		if ($bool != null && up($bool) != 'AND') {
			$cond = array($bool => $cond);
		}
		return $cond;
	}
/**
 * Private method used by postConditions
 *
 */
	function __postConditionMatch($op, $value) {

		if (is_string($op)) {
			$op = up(trim($op));
		}

		switch($op) {
			case '':
			case '=':
			case null:
				return $value;
			break;
			case 'LIKE':
				return 'LIKE %' . $value . '%';
			break;
			default:
				return $op . ' ' . $value;
			break;
		}
	}
/**
 * Cleans up the date fields of current Model.
 *
 */
	function cleanUpFields($modelClass = null) {
		if ($modelClass == null) {
			$modelClass = $this->modelClass;
		}
		foreach($this->{$modelClass}->_tableInfo->value as $field) {
			$useNewDate = false;
			$dateFields = array('Y'=>'_year', 'm'=>'_month', 'd'=>'_day', 'H'=>'_hour', 'i'=>'_min', 's'=>'_sec');
			foreach ($dateFields as $default => $var) {
				if(isset($this->data[$modelClass][$field['name'] . $var])) {
					${$var} = $this->data[$modelClass][$field['name'] . $var];
					 unset($this->data[$modelClass][$field['name'] . $var]);
					 $useNewDate = true;
				} else {
					${$var} = date($default);
				}
			}
			if ($_hour != 12 && (isset($this->data[$modelClass][$field['name'] . '_meridian']) && 'pm' == $this->data[$modelClass][$field['name'] . '_meridian'])) {
				$_hour = $_hour + 12;
			}
			unset($this->data[$modelClass][$field['name'] . '_meridian']);

			$newDate = null;
			if (in_array($field['type'], array('datetime', 'timestamp')) && $useNewDate) {
				$newDate = "{$_year}-{$_month}-{$_day} {$_hour}:{$_min}:{$_sec}";
			} else if ('date' == $field['type'] && $useNewDate) {
				$newDate = "{$_year}-{$_month}-{$_day}";
			} else if ('time' == $field['type'] && $useNewDate) {
				$newDate = "{$_hour}:{$_min}:{$_sec}";
			}
			if($newDate && !in_array($field['name'], array('created', 'updated', 'modified'))) {
				$this->data[$modelClass][$field['name']] = $newDate;
			}
		}
	}
/**
 * Handles automatic pagination of model records
 *
 * @param mixed $object
 * @param mixed $scope
 * @param array $whitelist
 * @return array Model query results
 */
	function paginate($object = null, $scope = array(), $whitelist = array()) {

		if (is_array($object)) {
			$whitelist = $scope;
			$scope = $object;
			$object = null;
		}

		if (is_string($object)) {
			if (isset($this->{$object})) {
				$object = $this->{$object};
			} elseif (isset($this->{$this->modelClass}) && isset($this->{$this->modelClass}->{$object})) {
				$object = $this->{$this->modelClass}->{$object};
			} elseif (!empty($this->uses)) {
				for ($i = 0; $i < count($this->uses); $i++) {
					$model = $this->uses[$i];
					if (isset($this->{$model}->{$object})) {
						$object = $this->{$model}->{$object};
						break;
					}
				}
			}
		} elseif (empty($object) || $object == null) {
			if (isset($this->{$this->modelClass})) {
				$object = $this->{$this->modelClass};
			} else {
				$object = $this->{$this->uses[0]};
			}
		}

		if (!is_object($object)) {
			// Error: can't find object
			return array();
		}
		$options = am($this->params, $this->params['url'], $this->passedArgs);
		if (isset($this->paginate[$object->name])) {
			$defaults = $this->paginate[$object->name];
		} else {
			$defaults = $this->paginate;
		}

		if (isset($options['show'])) {
			$options['limit'] = $options['show'];
		}

		if (isset($options['sort']) && isset($options['direction'])) {
			$options['order'] = array($options['sort'] => $options['direction']);
		} elseif (isset($options['sort'])) {
			$options['order'] = array($options['sort'] => 'asc');
		}

		if (!empty($options['order']) && is_array($options['order'])) {
			$key = key($options['order']);
			if (strpos($key, '.') === false && $object->hasField($key)) {
				$options['order'][$object->name . '.' . $key] = $options['order'][$key];
				unset($options['order'][$key]);
			}
		}

		$vars = array('fields', 'order', 'limit', 'page', 'recursive');
		$keys = array_keys($options);
		$count = count($keys);

		for($i = 0; $i < $count; $i++) {
			if (!in_array($keys[$i], $vars)) {
				unset($options[$keys[$i]]);
			}
			if (empty($whitelist) && ($keys[$i] == 'fields' || $keys[$i] == 'recursive')) {
				unset($options[$keys[$i]]);
			} elseif (!empty($whitelist) && !in_array($keys[$i], $whitelist)) {
				unset($options[$keys[$i]]);
			}
		}

		$conditions = $fields = $order = $limit = $page = $recursive = null;
		if (!isset($defaults['conditions'])) {
			$defaults['conditions'] = array();
		}

		extract($options = am(array('page' => 1, 'limit' => 20), $defaults, $options));
		if (is_array($scope) && !empty($scope)) {
			$conditions = am($conditions, $scope);
		} elseif (is_string($scope)) {
			$conditions = array($conditions, $scope);
		}
		$recursive = $object->recursive;
		$count = $object->findCount($conditions, $recursive);
		$pageCount = ceil($count / $limit);

		if($page == 'last') {
			$options['page'] = $page = $pageCount;
		}

		$results = $object->findAll($conditions, $fields, $order, $limit, $page, $recursive);
		$paging = array(
			'page'		=> $page,
			'current'	=> count($results),
			'count'		=> $count,
			'prevPage'	=> ($page > 1),
			'nextPage'	=> ($count > ($page * $limit)),
			'pageCount'	=> $pageCount,
			'defaults'	=> am(array('limit' => 20, 'step' => 1), $defaults),
			'options'	=> $options
		);

		$this->params['paging'][$object->name] = $paging;

		if (!in_array('Paginator', $this->helpers) && !array_key_exists('Paginator', $this->helpers)) {
			$this->helpers[] = 'Paginator';
		}

		return $results;
	}
/**
 * Called before the controller action.  Overridden in subclasses.
 *
 */
	function beforeFilter() {
	}
/**
 * Called after the controller action is run, but before the view is rendered.  Overridden in subclasses.
 *
 */
	function beforeRender() {
	}
/**
 * Called after the controller action is run and rendered.  Overridden in subclasses.
 *
 */
	function afterFilter() {
	}
/**
 * This method should be overridden in child classes.
 *
 * @param string $method name of method called example index, edit, etc.
 * @return boolean
 */
	function _beforeScaffold($method) {
		return true;
	}
/**
 * This method should be overridden in child classes.
 *
 * @param string $method name of method called either edit or update.
 * @return boolean
 */
	function _afterScaffoldSave($method) {
		return true;
	}
/**
 * This method should be overridden in child classes.
 *
 * @param string $method name of method called either edit or update.
 * @return boolean
 */
	function _afterScaffoldSaveError($method) {
		return true;
	}
/**
 * This method should be overridden in child classes.
 * If not it will render a scaffold error.
 * Method MUST return true in child classes
 *
 * @param string $method name of method called example index, edit, etc.
 * @return boolean
 */
	function _scaffoldError($method) {
		return false;
	}
/**
 * Enter description here...
 *
 * @param unknown_type $data
 * @param unknown_type $key
 * @return unknown
 */
	function _selectedArray($data, $key = 'id') {
		if(!is_array($data)) {
			$model = $data;
			if(!empty($this->data[$model][$model])) {
				return $this->data[$model][$model];
			}
			if(!empty($this->data[$model])) {
				$data = $this->data[$model];
			}
		}
		$array = array();
		if(!empty($data)) {
			foreach($data as $var) {
				$array[$var[$key]] = $var[$key];
			}
		}
		return $array;
	}
}

?>