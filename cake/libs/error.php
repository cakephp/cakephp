<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
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
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 0.10.5.1732
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
/**
 * Controller instance.
 *
 * @var object
 * @access private
 */
	var $controller = null;

/**
 * Class constructor.
 *
 * @param string $method Method producing the error
 * @param array $messages Error messages
 */
	function __construct($method, $messages) {
		parent::__construct();
		static $__previousError = null;

		$allow = array('.', '/', '_', ' ', '-', '~');
	    if(substr(PHP_OS,0,3) == "WIN") {
            $allow = array_merge($allow, array('\\', ':') );
        }
		$clean = new Sanitize();
		$messages = $clean->paranoid($messages, $allow);
		if(!class_exists('dispatcher')) {
			require CAKE . 'dispatcher.php';
		}
		$this->__dispatch =& new Dispatcher();

		if ($__previousError != array($method, $messages)) {
			$__previousError = array($method, $messages);

			if (!class_exists('AppController')) {
				loadController(null);
			}

			$this->controller =& new AppController();
			if(!empty($this->controller->uses)) {
				$this->controller->constructClasses();
			}
			$this->controller->_initComponents();
			$this->controller->cacheAction = false;
			$this->__dispatch->start($this->controller);

			if (method_exists($this->controller, 'apperror')) {
				return $this->controller->appError($method, $messages);
			}
		} else {
			$this->controller =& new Controller();
			$this->controller->cacheAction = false;
		}
		if (Configure::read() > 0 || $method == 'error') {
			call_user_func_array(array(&$this, $method), $messages);
		} else {
			call_user_func_array(array(&$this, 'error404'), $messages);
		}
	}
/**
 * Displays an error page (e.g. 404 Not found).
 *
 * @param array $params Parameters for controller
 * @access public
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
 * @param array $params Parameters for controller
 * @access public
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
 * @param array $params Parameters for controller
 * @access public
 */
	function missingController($params) {
		extract(Router::getPaths());
		extract($params, EXTR_OVERWRITE);

		$this->controller->base = $base;
		$this->controller->webroot = $webroot;
		$this->controller->viewPath ='errors';
		$controllerName = str_replace('Controller', '', $className);
		$this->controller->set(array('controller' => $className,
										'controllerName' => $controllerName,
										'title' => __('Missing Controller', true)));
		$this->controller->render('missingController');
		exit();
	}
/**
 * Renders the Missing Action web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingAction($params) {
		extract(Router::getPaths());
		extract($params, EXTR_OVERWRITE);

		$this->controller->base = $base;
		$this->controller->webroot = $webroot;
		$this->controller->viewPath = 'errors';
		$this->controller->set(array('controller' => $className,
										'action' => $action,
										'title' => __('Missing Method in Controller', true)));
		$this->controller->render('missingAction');
		exit();
	}
/**
 * Renders the Private Action web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function privateAction($params) {
		extract(Router::getPaths());
		extract($params, EXTR_OVERWRITE);

		$this->controller->base = $base;
		$this->controller->webroot = $webroot;
		$this->controller->viewPath = 'errors';
		$this->controller->set(array('controller' => $className,
										'action' => $action,
										'title' => __('Trying to access private method in class', true)));
		$this->controller->render('privateAction');
		exit();
	}
/**
 * Renders the Missing Table web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingTable($params) {
		extract(Router::getPaths());
		extract($params, EXTR_OVERWRITE);

		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('model' => $className,
										'table' => $table,
										'title' => __('Missing Database Table', true)));
		$this->controller->render('missingTable');
		exit();
	}
/**
 * Renders the Missing Database web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingDatabase($params = array()) {
		extract(Router::getPaths());
		extract($params, EXTR_OVERWRITE);

		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('title' => __('Scaffold Missing Database Connection', true)));
		$this->controller->render('missingScaffolddb');
		exit();
	}
/**
 * Renders the Missing View web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingView($params) {
		extract(Router::getPaths());
		extract($params, EXTR_OVERWRITE);

		$this->controller->base = $base;
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('controller' => $className,
										'action' => $action,
										'file' => $file,
										'title' => __('Missing View', true)));
		$this->controller->render('missingView');
		exit();
	}
/**
 * Renders the Missing Layout web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingLayout($params) {
		extract(Router::getPaths());
		extract($params, EXTR_OVERWRITE);

		$this->controller->base = $base;
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->layout = 'default';
		$this->controller->set(array('file'  => $file,
										'title' => __('Missing Layout', true)));
		$this->controller->render('missingLayout');
		exit();
	}
/**
 * Renders the Database Connection web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingConnection($params) {
		extract(Router::getPaths());
		extract($params, EXTR_OVERWRITE);

		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('model' => $className,
										'title' => __('Missing Database Connection', true)));
		$this->controller->render('missingConnection');
		exit();
	}
/**
 * Renders the Missing Helper file web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingHelperFile($params) {
		extract(Router::getPaths());
		extract($params, EXTR_OVERWRITE);

		$this->controller->base = $base;
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('helperClass' => Inflector::camelize($helper) . "Helper",
										'file' => $file,
										'title' => __('Missing Helper File', true)));
		$this->controller->render('missingHelperFile');
		exit();
	}
/**
 * Renders the Missing Helper class web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingHelperClass($params) {
		extract(Router::getPaths());
		extract($params, EXTR_OVERWRITE);

		$this->controller->base = $base;
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('helperClass' => Inflector::camelize($helper) . "Helper",
										'file' => $file,
										'title' => __('Missing Helper Class', true)));
		$this->controller->render('missingHelperClass');
		exit();
	}
/**
 * Renders the Missing Component file web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingComponentFile($params) {
		extract(Router::getPaths());
		extract($params, EXTR_OVERWRITE);

		$this->controller->base = $base;
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('controller' => $className,
										'component' => $component,
										'file' => $file,
										'title' => __('Missing Component File', true)));
		$this->controller->render('missingComponentFile');
		exit();
	}
/**
 * Renders the Missing Component class web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingComponentClass($params) {
		extract(Router::getPaths());
		extract($params, EXTR_OVERWRITE);

		$this->controller->base = $base;
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('controller' => $className,
										'component' => $component,
										'file' => $file,
										'title' => __('Missing Component Class', true)));
		$this->controller->render('missingComponentClass');
		exit();
	}
/**
 * Renders the Missing Model class web page.
 *
 * @param unknown_type $params Parameters for controller
 * @access public
 */
	function missingModel($params) {
		extract(Router::getPaths());
		extract($params, EXTR_OVERWRITE);

		$this->controller->base = $base;
		$this->controller->viewPath = 'errors';
		$this->controller->webroot = $this->_webroot();
		$this->controller->set(array('model' => $className,
										'title' => __('Missing Model', true)));
		$this->controller->render('missingModel');
		exit();
	}
/**
 * Path to the web root.
 *
 * @return string full web root path
 * @access private
 */
	function _webroot() {
		$this->__dispatch->baseUrl();
		return $this->__dispatch->webroot;
	}
}

?>