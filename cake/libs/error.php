<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
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
 * @subpackage		cake.cake.libs
 * @since			CakePHP v 0.10.5.1732
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
uses('sanitize');
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class ErrorHandler extends Object{
	var $controller = null;

/**
 * Class constructor.
 *
 * @param string $method
 * @param array $messages
 * @return unknown
 */
	function __construct($method, $messages) {
		parent::__construct();
		static $__previousError = null;

		$allow = array('.', '/', '_');
	    if(substr(PHP_OS,0,3) == "WIN") {
            $allow = array_merge($allow, array('\\', ':') );
        }
		$clean = new Sanitize();
		$messages = $clean->paranoid($messages, $allow);
		$this->__dispatch =& new Dispatcher();

		if ($__previousError != array($method, $messages)) {
			$__previousError = array($method, $messages);

			if (!class_exists('AppController')) {
				loadController(null);
			}

			$this->controller =& new AppController();
			$this->controller->_initComponents();
			$this->__dispatch->start($this->controller);

			if (method_exists($this->controller, 'apperror')) {
				return $this->controller->appError($method, $messages);
			}
		} else {
			$this->controller =& new Controller();
		}
		if (DEBUG > 0 || $method == 'error') {
			call_user_func_array(array(&$this, $method), $messages);
		} else {
			call_user_func_array(array(&$this, 'error404'), $messages);
		}
	}
/**
 * Displays an error page (e.g. 404 Not found).
 *
 * @param array $params
 */
	function error($params) {
		extract($params);
		$this->controller->base = $base;
		$this->controller->webroot = $this->_webroot();
		$this->controller->viewPath='errors';
		$this->controller->set(array('code' => $code,
										'name' => $name,
										'message' => $message,
										'title' => $code . ' ' . $name));
		$this->controller->render('error404');
		exit();
	}
/**
 * Convenience method to display a 404 page.
 *
 * @param array $params
 */
	function error404($params) {
		extract($params);

		if (!isset($url)) {
			$url = $action;
		}
		if (!isset($message)) {
			$message = '';
		}
		if (!isset($base)) {
			$base = '';
		}

		header("HTTP/1.0 404 Not Found");
		$this->error(array('code' => '404',
							'name' => 'Not found',
							'message' => sprintf(__("The requested address %s was not found on this server.", true), $url, $message),
							'base' => $base));
		exit();
	}
/**
 * Renders the Missing Controller web page.
 *
 * @param array $params
 */
	function missingController($params) {
		extract($params);
		$this->controller->base = $base;
		$this->controller->webroot = $webroot;
		$this->controller->viewPath ='errors';
		$controllerName = str_replace('Controller', '', $className);
		$this->controller->set(array('controller' => $className,
										'controllerName' => $controllerName,
										'title' => 'Missing Controller'));
		$this->controller->render('missingController');
		exit();
	}
/**
 * Renders the Missing Action web page.
 *
 * @param array $params
 */
	function missingAction($params) {
		extract($params);
		$this->controller->base = $base;
		$this->controller->webroot = $webroot;
		$this->controller->viewPath = 'errors';
		$this->controller->set(array('controller' => $className,
										'action' => $action,
										'title' => 'Missing Method in Controller'));
		$this->controller->render('missingAction');
		exit();
	}
/**
 * Renders the Private Action web page.
 *
 * @param array $params
 */
	function privateAction($params) {
		extract($params);
		$this->controller->base = $base;
		$this->controller->webroot = $webroot;
		$this->controller->viewPath = 'errors';
		$this->controller->set(array('controller' => $className,
										'action' => $action,
										'title' => 'Trying to access private method in class'));
		$this->controller->render('privateAction');
		exit();
	}
/**
 * Renders the Missing Table web page.
 *
 * @param array $params
 */
	function missingTable($params) {
		extract($params);
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('model' => $className,
										'table' => $table,
										'title' => 'Missing Database Table'));
		$this->controller->render('missingTable');
		exit();
	}
/**
 * Renders the Missing Database web page.
 *
 * @param array $params
 */
	function missingDatabase($params = array()) {
		extract($params);
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('title' => 'Scaffold Missing Database Connection'));
		$this->controller->render('missingScaffolddb');
		exit();
	}
/**
 * Renders the Missing View web page.
 *
 * @param array $params
 */
	function missingView($params) {
		extract($params);
		$this->controller->base = $base;
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('controller' => $className,
										'action' => $action,
										'file' => $file,
										'title' => 'Missing View'));
		$this->controller->render('missingView');
		exit();
	}
/**
 * Renders the Missing Layout web page.
 *
 * @param array $params
 */
	function missingLayout($params) {
		extract($params);
		$this->controller->base = $base;
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->layout = 'default';
		$this->controller->set(array('file'  => $file,
										'title' => 'Missing Layout'));
		$this->controller->render('missingLayout');
		exit();
	}
/**
 * Renders the Database Connection web page.
 *
 * @param array $params
 */
	function missingConnection($params) {
		extract($params);
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('model' => $className,
										'title' => 'Missing Database Connection'));
		$this->controller->render('missingConnection');
		exit();
	}
/**
 * Renders the Missing Helper file web page.
 *
 * @param array $params
 */
	function missingHelperFile($params) {
		extract($params);
		$this->controller->base = $base;
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('helperClass' => Inflector::camelize($helper) . "Helper",
										'file' => $file,
										'title' => 'Missing Helper File'));
		$this->controller->render('missingHelperFile');
		exit();
	}
/**
 * Renders the Missing Helper class web page.
 *
 * @param array $params
 */
	function missingHelperClass($params) {
		extract($params);
		$this->controller->base = $base;
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('helperClass' => Inflector::camelize($helper) . "Helper",
										'file' => $file,
										'title' => 'Missing Helper Class'));
		$this->controller->render('missingHelperClass');
		exit();
	}
/**
 * Renders the Missing Component file web page.
 *
 * @param array $params
 */
	function missingComponentFile($params) {
		extract($params);
		$this->controller->base = $base;
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('controller' => $className,
										'component' => $component,
										'file' => $file,
										'title' => 'Missing Component File'));
		$this->controller->render('missingComponentFile');
		exit();
	}
/**
 * Renders the Missing Component class web page.
 *
 * @param array $params
 */
	function missingComponentClass($params) {
		extract($params);
		$this->controller->base = $base;
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('controller' => $className,
										'component' => $component,
										'file' => $file,
										'title' => 'Missing Component Class'));
		$this->controller->render('missingComponentClass');
		exit();
	}
/**
 * Renders the Missing Model class web page.
 *
 * @param unknown_type $params
 */
	function missingModel($params) {
		extract($params);
		$this->controller->base = $base;
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('model' => $className,
										'title' => 'Missing Model'));
		$this->controller->render('missingModel');
		exit();
	}
/**
 * Path to the web root.
 *
 * @return string full web root path
 */
	function _webroot() {
		$this->__dispatch->baseUrl();
		return $this->__dispatch->webroot;
	}
}
?>