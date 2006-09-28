<?php
/* SVN FILE: $Id$ */
/**
 * Base controller class.
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
 * @since			CakePHP v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Include files
 */
	uses(DS . 'controller' . DS . 'component', DS . 'view' . DS . 'view');
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
 * Name of the controller.
 *
 * @var unknown_type
 * @access public
 */
	var $name = null;
/**
 * Stores the current URL (for links etc.)
 *
 * @var string Current URL
 */
	var $here = null;
/**
 * The webroot of the application
 *
 * @var string
 */
	var $webroot = null;
/**
 * Action to be performed.
 *
 * @var string
 * @access public
 */
	var $action = null;
/**
 * An array of names of models the particular controller wants to use.
 *
 * @var mixed A single name as a string or a list of names as an array.
 * @access protected
 */
	var $uses = false;
/**
 * An array of names of built-in helpers to include.
 *
 * @var mixed A single name as a string or a list of names as an array.
 * @access protected
 */
	var $helpers = array('Html');
/**
 * Parameters received in the current request, i.e. GET and POST data
 *
 * @var array
 * @access public
 */
	var $params = array();
/**
 * POST'ed model data
 *
 * @var array
 * @access public
 */
	var $data = array();
/**
 * Sub-path for view files
 *
 * @var string
 */
	var $viewPath = null;
/**
 * Sub-path for layout files
 *
 * @var string
 */
	var $layoutPath = '';
/**
 * Variables for the view
 *
 * @var array
 * @access private
 */
	var $_viewVars = array();
/**
 * Web page title
 *
 * @var boolean
 * @access private
 */
	var $pageTitle = false;
/**
 * An array of model objects.
 *
 * @var array Array of model objects.
 * @access public
 */
	var $modelNames = array();
/**
 * Enter description here...
 *
 * @var unknown_type
 * @access public
 */
	var $base = null;
/**
 * Layout file to use (see /app/views/layouts/default.thtml)
 *
 * @var string
 * @access public
 */
	var $layout = 'default';
/**
 * Automatically render the view (the dispatcher checks for this variable before running render())
 *
 * @var boolean
 * @access public
 */
	var $autoRender = true;
/**
 * Enter description here...
 *
 * @var boolean
 * @access public
 */
	var $autoLayout = true;
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $beforeFilter = null;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $components = array();
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $view = 'View';
/**
 * File extension for view templates. Defaults to Cake's conventional ".thtml".
 *
 * @var array
 */
	var $ext = '.thtml';
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $_viewClass = null;
/**
 * The output of the requested action.  Contains either a variable
 * returned from the action, or the data of the rendered view;
 *
 * @var unknown_type
 */
	var $output = null;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $plugin = null;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $cacheAction = false;
/**
 * Enter description here...
 *
 * @var boolean
 */
	var $persistModel = false;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $webservices = null;
/**
 * Enter description here...
 *
 * @var mixed
 */
	var $namedArgs = false;
/**
 * Enter description here...
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
				die ("Controller::__construct() : Can't get or parse my own class name, exiting.");
			}
			$this->name = $r[1];
		}

		if ($this->viewPath == null) {
			$this->viewPath = Inflector::underscore($this->name);
		}

		$this->modelClass = ucwords(Inflector::singularize($this->name));
		$this->modelKey = Inflector::underscore($this->modelClass);

		if (!defined('AUTO_SESSION') || AUTO_SESSION == true) {
			$this->components[] = 'Session';
		}

		if (is_subclass_of($this, 'AppController')) {
			$appVars = get_class_vars('AppController');

			foreach(array('components', 'helpers', 'uses') as $var) {
				if (isset($appVars[$var]) && !empty($appVars[$var]) && is_array($this->{$var})) {
					$diff = array_diff($appVars[$var], $this->{$var});
					$this->{$var} = array_merge($this->{$var}, $diff);
				}
			}
		}
		parent::__construct();
	}

	function _initComponents(){
		if (!empty($this->components)) {
			$component = new Component();
			$component->init($this);
		}
	}

/**
 * Loads and instantiates classes required by this controller,
 * including components and models
 *
 */
	function constructClasses() {
		if (empty($this->params['pass'])) {
			$id = false;
		} else {
			$id = $this->params['pass']['0'];
		}
		$cached = false;
		$object = null;

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
				return true;
			}
		} elseif ($this->uses === false) {
			return $this->cakeError('missingModel', array(array('className' => $this->modelClass, 'webroot' => '', 'base' => $this->base)));
		}

		if ($this->uses) {
			$uses = is_array($this->uses) ? $this->uses : array($this->uses);

			foreach($uses as $modelClass) {
				$id = false;
				$cached = false;
				$object = null;
				$modelKey = Inflector::underscore($modelClass);

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
						return true;
					}
				} else {
					return $this->cakeError('missingModel', array(array('className' => $modelClass, 'webroot' => '', 'base' => $this->base)));
				}
			}
		}
	}
/**
 * Redirects to given $url, after turning off $this->autoRender. Please notice that the script execution is not stopped
 * after the redirect.
 *
 * @param string $url
 * @param integer $status
 */
	function redirect($url, $status = null) {
		$this->autoRender = false;

		if (function_exists('session_write_close')) {
			session_write_close();
		}

		if ($status != null) {
			$codes = array(
				100 => "HTTP/1.1 100 Continue",
				101 => "HTTP/1.1 101 Switching Protocols",
				200 => "HTTP/1.1 200 OK",
				201 => "HTTP/1.1 201 Created",
				202 => "HTTP/1.1 202 Accepted",
				203 => "HTTP/1.1 203 Non-Authoritative Information",
				204 => "HTTP/1.1 204 No Content",
				205 => "HTTP/1.1 205 Reset Content",
				206 => "HTTP/1.1 206 Partial Content",
				300 => "HTTP/1.1 300 Multiple Choices",
				301 => "HTTP/1.1 301 Moved Permanently",
				302 => "HTTP/1.1 302 Found",
				303 => "HTTP/1.1 303 See Other",
				304 => "HTTP/1.1 304 Not Modified",
				305 => "HTTP/1.1 305 Use Proxy",
				307 => "HTTP/1.1 307 Temporary Redirect",
				400 => "HTTP/1.1 400 Bad Request",
				401 => "HTTP/1.1 401 Unauthorized",
				402 => "HTTP/1.1 402 Payment Required",
				403 => "HTTP/1.1 403 Forbidden",
				404 => "HTTP/1.1 404 Not Found",
				405 => "HTTP/1.1 405 Method Not Allowed",
				406 => "HTTP/1.1 406 Not Acceptable",
				407 => "HTTP/1.1 407 Proxy Authentication Required",
				408 => "HTTP/1.1 408 Request Time-out",
				409 => "HTTP/1.1 409 Conflict",
				410 => "HTTP/1.1 410 Gone",
				411 => "HTTP/1.1 411 Length Required",
				412 => "HTTP/1.1 412 Precondition Failed",
				413 => "HTTP/1.1 413 Request Entity Too Large",
				414 => "HTTP/1.1 414 Request-URI Too Large",
				415 => "HTTP/1.1 415 Unsupported Media Type",
				416 => "HTTP/1.1 416 Requested range not satisfiable",
				417 => "HTTP/1.1 417 Expectation Failed",
				500 => "HTTP/1.1 500 Internal Server Error",
				501 => "HTTP/1.1 501 Not Implemented",
				502 => "HTTP/1.1 502 Bad Gateway",
				503 => "HTTP/1.1 503 Service Unavailable",
				504 => "HTTP/1.1 504 Gateway Time-out"
			);

			if (isset($codes[$status])) {
				header($codes[$status]);
			}
		}
		header('Location: ' . Router::url($url, defined('SERVER_IIS')));
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
				$this->_viewVars[$name] = $value;
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
			$errors = array_merge($errors, $this->{$object->name}->invalidFields($object->data));
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
		$this->_viewClass =& new $viewClass($this);

		if (!empty($this->modelNames)) {
			foreach($this->modelNames as $model) {
				if (!empty($this->{$model}->validationErrors)) {
					$this->_viewClass->validationErrors[$model] = &$this->{$model}->validationErrors;
				}
			}
		}
		$this->autoRender = false;
		return $this->_viewClass->render($action, $layout, $file);
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

		if ($ref != null && (defined(FULL_BASE_URL) || FULL_BASE_URL)) {
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
 * @deprecated
 */
	function _setTitle($pageTitle) {
		$this->pageTitle = $pageTitle;
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
		$this->set('url', $this->base . $url);
		$this->set('message', $message);
		$this->set('pause', $pause);
		$this->set('page_title', $message);

		if (file_exists(VIEWS . 'layouts' . DS . 'flash.thtml')) {
			$flash = VIEWS . 'layouts' . DS . 'flash.thtml';
		} elseif ($flash = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . "layouts" . DS . 'flash.thtml')) {
		}
		$this->render(null, false, $flash);
	}
/**
 * Shows a message to the user $time seconds, then redirects to $url
 * Uses flash.thtml as a layout for the messages
 *
 * @param string $message Message to display to the user
 * @param string $url URL to redirect to after the time expires
 * @param int $time Time to show the message
 *
 * @param unknown_type $message
 * @param unknown_type $url
 * @param unknown_type $time
 */
	function flashOut($message, $url, $pause = 1) {
		$this->autoRender = false;
		$this->autoLayout = false;
		$this->set('url', $url);
		$this->set('message', $message);
		$this->set('pause', $pause);
		$this->set('page_title', $message);

		if (file_exists(VIEWS . 'layouts' . DS . 'flash.thtml')) {
			$flash = VIEWS . 'layouts' . DS . 'flash.thtml';
		} elseif($flash = fileExistsInPath(LIBS . 'view' . DS . 'templates' . DS . "layouts" . DS . 'flash.thtml')) {
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
		$table = $this->{$model}->table;
		//$association = array_search($table, $this->{$model}->alias);
		$objRegistryModel =& ClassRegistry::getObject($modelKey);

		foreach($objRegistryModel->_tableInfo as $tables) {
			foreach($tables as $tabl) {

				if ($objRegistryModel->isForeignKey($tabl['name'])) {
					if(false !== strpos($tabl['name'], "_id")) {
						$niceName = substr($tabl['name'], 0, strpos($tabl['name'], "_id" ));
					} else {
						$niceName = $niceName = $tabl['name'];
					}
					$fkNames = $this->{$model}->keyToTable[$tabl['name']];
					$fieldNames[$tabl['name']]['table'] = $fkNames[0];
					//$association = array_search($fieldNames[$tabl['name']]['table'], $this->{$model}->alias);
					$fieldNames[$tabl['name']]['prompt'] = Inflector::humanize($niceName);
					$fieldNames[$tabl['name']]['model'] = $fkNames[1];
					$fieldNames[$tabl['name']]['modelKey'] = $this->{$model}->tableToModel[$fieldNames[$tabl['name']]['table']];
					$fieldNames[$tabl['name']]['controller'] = Inflector::pluralize($this->{$model}->tableToModel[$fkNames[0]]);
					$fieldNames[$tabl['name']]['foreignKey'] = true;

				} else if('created' != $tabl['name'] && 'updated' != $tabl['name']) {
					$fieldNames[$tabl['name']]['prompt'] = Inflector::humanize($tabl['name']);
				} else if('created' == $tabl['name']) {
					$fieldNames[$tabl['name']]['prompt'] = 'Created';
				} else if('updated' == $tabl['name']) {
					$fieldNames[$tabl['name']]['prompt'] = 'Modified';
				}
				$fieldNames[$tabl['name']]['tagName'] = $model . '/' . $tabl['name'];
				$validationFields = $objRegistryModel->validate;

				if (isset($validationFields[$tabl['name']])) {
					if (VALID_NOT_EMPTY == $validationFields[$tabl['name']]) {
						$fieldNames[$tabl['name']]['required'] = true;
						$fieldNames[$tabl['name']]['errorMsg'] = "Required Field";
					}
				}
				$lParenPos = strpos($tabl['type'], '(');
				$rParenPos = strpos($tabl['type'], ')');

				if (false != $lParenPos) {
					$type = substr($tabl['type'], 0, $lParenPos);
					$fieldLength = substr($tabl['type'], $lParenPos + 1, $rParenPos - $lParenPos - 1);
				} else {
					$type = $tabl['type'];
				}

				switch($type) {
					case "text":
						$fieldNames[$tabl['name']]['type'] = 'area';
					break;
					case "string":
						if (isset($fieldNames[$tabl['name']]['foreignKey'])) {
							$fieldNames[$tabl['name']]['type'] = 'select';
							$fieldNames[$tabl['name']]['options'] = array();
							$otherModel =& ClassRegistry::getObject(Inflector::underscore($fieldNames[$tabl['name']]['modelKey']));

							if (is_object($otherModel)) {

								if ($doCreateOptions) {
									$otherDisplayField = $otherModel->getDisplayField();
									$otherModel->recursive = 0;
									$rec = $otherModel->findAll();

									foreach($rec as $pass) {
										foreach($pass as $key => $value) {
											if ($key == $this->{$model}->tableToModel[$fieldNames[$tabl['name']]['table']] && isset($value[$otherModel->primaryKey]) && isset($value[$otherDisplayField])) {
													$fieldNames[$tabl['name']]['options'][$value[$otherModel->primaryKey]] = $value[$otherDisplayField];
											}
										}
									}
								}
								$fieldNames[$tabl['name']]['selected'] = $data[$model][$tabl['name']];
							}
						} else {
							$fieldNames[$tabl['name']]['type'] = 'input';
						}
					break;
					case "boolean":
							$fieldNames[$tabl['name']]['type'] = 'checkbox';
					break;
					case "integer":
					case "float":
						if (strcmp($tabl['name'], $this->$model->primaryKey) == 0) {
							$fieldNames[$tabl['name']]['type'] = 'hidden';
						} else if(isset($fieldNames[$tabl['name']]['foreignKey'])) {
							$fieldNames[$tabl['name']]['type'] = 'select';
							$fieldNames[$tabl['name']]['options'] = array();
							$otherModel =& ClassRegistry::getObject(Inflector::underscore($fieldNames[$tabl['name']]['modelKey']));

							if (is_object($otherModel)) {
								if ($doCreateOptions) {
									$otherDisplayField = $otherModel->getDisplayField();
									$otherModel->recursive = 0;
									$rec = $otherModel->findAll();

									foreach($rec as $pass) {
										foreach($pass as $key => $value) {
											if ($key == $this->{$model}->tableToModel[$fieldNames[$tabl['name']]['table']] && isset($value[$otherModel->primaryKey]) && isset($value[$otherDisplayField])) {
												$fieldNames[$tabl['name']]['options'][$value[$otherModel->primaryKey]] = $value[$otherDisplayField];
											}
										}
									}
								}
								$fieldNames[$tabl['name']]['selected'] = $data[$model][$tabl['name']];
							}
						} else {
							$fieldNames[$tabl['name']]['type'] = 'input';
						}

					break;
					case "enum":
						$fieldNames[$tabl['name']]['type'] = 'select';
						$fieldNames[$tabl['name']]['options'] = array();
						$enumValues = split(',', $fieldLength);

						foreach($enumValues as $enum) {
							$enum = trim($enum, "'");
							$fieldNames[$tabl['name']]['options'][$enum] = $enum;
						}

						$fieldNames[$tabl['name']]['selected'] = $data[$model][$tabl['name']];
					break;
					case "date":
					case "datetime":
					case "time":
						if (0 != strncmp("created", $tabl['name'], 7) && 0 != strncmp("modified", $tabl['name'], 8)) {
							$fieldNames[$tabl['name']]['type'] = $type;
						}

						if (isset($data[$model][$tabl['name']])) {
							$fieldNames[$tabl['name']]['selected'] = $data[$model][$tabl['name']];
						} else {
							$fieldNames[$tabl['name']]['selected'] = null;
						}

					break;
					default:
					break;
				}
			}

			foreach($objRegistryModel->hasAndBelongsToMany as $relation => $relData) {
				$modelName = $relData['className'];
				$manyAssociation = $relation;
				$modelKeyM = Inflector::underscore($modelName);
				$modelObject =& new $modelName();

				if ($doCreateOptions) {
					$otherDisplayField = $modelObject->getDisplayField();
					$fieldNames[$modelKeyM]['model'] = $modelName;
					$fieldNames[$modelKeyM]['prompt'] = "Related " . Inflector::humanize(Inflector::pluralize($modelName));
					$fieldNames[$modelKeyM]['type'] = "selectMultiple";
					$fieldNames[$modelKeyM]['tagName'] = $manyAssociation . '/' . $manyAssociation;
					$modelObject->recursive = 0;
					$rec = $modelObject->findAll();

					foreach($rec as $pass) {
						foreach($pass as $key => $value) {
							if ($key == $modelName && isset($value[$modelObject->primaryKey]) && isset($value[$otherDisplayField])) {
								$fieldNames[$modelKeyM]['options'][$value[$modelObject->primaryKey]] = $value[$otherDisplayField];
							}
						}
					}

					if (isset($data[$manyAssociation])) {
						foreach($data[$manyAssociation] as $key => $row) {
							$fieldNames[$modelKeyM]['selected'][$row[$modelObject->primaryKey]] = $row[$modelObject->primaryKey];
						}
					}
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
		} elseif ((!is_array($data) || empty($data)) && !empty($this->data)) {
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

		foreach($this->{$modelClass}->_tableInfo as $table) {
			foreach($table as $field) {

				if ('date' == $field['type'] && isset($this->params['data'][$modelClass][$field['name'] . '_year'])) {
					$newDate = $this->params['data'][$modelClass][$field['name'] . '_year'] . '-';
					$newDate .= $this->params['data'][$modelClass][$field['name'] . '_month'] . '-';
					$newDate .= $this->params['data'][$modelClass][$field['name'] . '_day'];
					unset($this->params['data'][$modelClass][$field['name'] . '_year']);
					unset($this->params['data'][$modelClass][$field['name'] . '_month']);
					unset($this->params['data'][$modelClass][$field['name'] . '_day']);
					unset($this->params['data'][$modelClass][$field['name'] . '_hour']);
					unset($this->params['data'][$modelClass][$field['name'] . '_min']);
					unset($this->params['data'][$modelClass][$field['name'] . '_meridian']);
					$this->params['data'][$modelClass][$field['name']] = $newDate;
					$this->data[$modelClass][$field['name']] = $newDate;

				} elseif('datetime' == $field['type'] && isset($this->params['data'][$modelClass][$field['name'] . '_year'])) {
					$hour = $this->params['data'][$modelClass][$field['name'] . '_hour'];

					if ($hour != 12 && (isset($this->params['data'][$modelClass][$field['name'] . '_meridian']) && 'pm' == $this->params['data'][$modelClass][$field['name'] . '_meridian'])) {
						$hour = $hour + 12;
					}

					$newDate  = $this->params['data'][$modelClass][$field['name'] . '_year'] . '-';
					$newDate .= $this->params['data'][$modelClass][$field['name'] . '_month'] . '-';
					$newDate .= $this->params['data'][$modelClass][$field['name'] . '_day'] . ' ';
					$newDate .= $hour . ':' . $this->params['data'][$modelClass][$field['name'] . '_min'] . ':00';
					unset($this->params['data'][$modelClass][$field['name'] . '_year']);
					unset($this->params['data'][$modelClass][$field['name'] . '_month']);
					unset($this->params['data'][$modelClass][$field['name'] . '_day']);
					unset($this->params['data'][$modelClass][$field['name'] . '_hour']);
					unset($this->params['data'][$modelClass][$field['name'] . '_min']);
					unset($this->params['data'][$modelClass][$field['name'] . '_meridian']);
					$this->params['data'][$modelClass][$field['name']] = $newDate;
					$this->data[$modelClass][$field['name']] = $newDate;

				} elseif('time' == $field['type'] && isset($this->params['data'][$modelClass][$field['name'] . '_hour'])) {
					$hour = $this->params['data'][$modelClass][$field['name'] . '_hour'];

					if ($hour != 12 && (isset($this->params['data'][$modelClass][$field['name'] . '_meridian']) && 'pm' == $this->params['data'][$modelClass][$field['name'] . '_meridian'])) {
						$hour = $hour + 12;
					}

					$newDate = $hour . ':' . $this->params['data'][$modelClass][$field['name'] . '_min'] . ':00';
					unset($this->params['data'][$modelClass][$field['name'] . '_hour']);
					unset($this->params['data'][$modelClass][$field['name'] . '_min']);
					unset($this->params['data'][$modelClass][$field['name'] . '_meridian']);
					$this->params['data'][$modelClass][$field['name']] = $newDate;
					$this->data[$modelClass][$field['name']] = $newDate;
				}
			}
		}
	}
/**
 * Handles automatic pagination of model records
 *
 * @param mixed $object
 * @param array $options
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

		$options = am($this->params, $this->passedArgs);
		if (isset($this->pagination[$object->name])) {
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
			$options['order'] = $options['sort'];
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
		$options = am($defaults, $options);
		if (isset($this->paginate[$object->name])) {
			$defaults = $this->paginate[$object->name];
		} else {
			$defaults = $this->paginate;
		}
		if (!isset($defaults['conditions'])) {
			$defaults['conditions'] = array();
		}

		extract(am($defaults, $options));
		if ((is_array($scope) || is_string($scope)) && !empty($scope)) {
			$conditions = array($conditions, $scope);
		}
		$results = $object->findAll($conditions, $fields, $order, $limit, $page, $recursive);

		$count = $object->findCount($conditions);
		$paging = array(
			'current'	=> count($results),
			'count'		=> $count,
			'prevPage'	=> ($page > 1),
			'nextPage'	=> ($count > ($page * $limit)),
			'pageCount'	=> ceil($count / $limit),
			'defaults'	=> $defaults,
			'options'	=> $options
		);
		$this->params['paging'][$object->name] = $paging;

		if (!in_array('Paginator', $this->helpers)) {
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
}

?>