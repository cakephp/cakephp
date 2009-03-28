<?php
/* SVN FILE: $Id$ */
/**
 * Error handler
 *
 * Provides Error Capturing for Framework errors.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 0.10.5.1732
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Controller', 'App');
/**
 * Error Handling Controller
 *
 * Controller used by ErrorHandler to render error views.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class CakeErrorController extends AppController {
	var $name = 'CakeError';
/**
 * Uses Property
 *
 * @var array
 */
	var $uses = array();
/**
 * __construct
 *
 * @access public
 * @return void
 */
	function __construct() {
		parent::__construct();
		$this->_set(Router::getPaths());
		$this->params = Router::getParams();
		$this->constructClasses();
		$this->Component->initialize($this);
		$this->_set(array('cacheAction' => false, 'viewPath' => 'errors'));
	}
}
/**
 * Error Handler.
 *
 * Captures and handles all cakeError() calls.
 * Displays helpful framework errors when debug > 1.
 * When debug < 1 cakeError() will render 404 or 500 errors.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class ErrorHandler extends Object {
/**
 * Controller instance.
 *
 * @var Controller
 * @access public
 */
	var $controller = null;
/**
 * Class constructor.
 *
 * @param string $method Method producing the error
 * @param array $messages Error messages
 */
	function __construct($method, $messages) {
		App::import('Core', 'Sanitize');
		static $__previousError = null;

		if ($__previousError != array($method, $messages)) {
			$__previousError = array($method, $messages);
			$this->controller =& new CakeErrorController();
		} else {
			$this->controller =& new Controller();
			$this->controller->viewPath = 'errors';
		}

		$options = array('escape' => false);
		$messages = Sanitize::clean($messages, $options);

		if (!isset($messages[0])) {
			$messages = array($messages);
		}

		if (method_exists($this->controller, 'apperror')) {
			return $this->controller->appError($method, $messages);
		}

		if (!in_array(strtolower($method), array_map('strtolower', get_class_methods($this)))) {
			$method = 'error';
		}

		if ($method !== 'error') {
			if (Configure::read() == 0) {
				$method = 'error404';
				if (isset($code) && $code == 500) {
					$method = 'error500';
				}
			}
		}
		$this->dispatchMethod($method, $messages);
		$this->_stop();
	}
/**
 * Displays an error page (e.g. 404 Not found).
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function error($params) {
		extract($params, EXTR_OVERWRITE);
		$this->controller->set(array(
			'code' => $code,
			'name' => $name,
			'message' => $message,
			'title' => $code . ' ' . $name
		));
		$this->_outputMessage('error404');
	}
/**
 * Convenience method to display a 404 page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function error404($params) {
		extract($params, EXTR_OVERWRITE);

		if (!isset($url)) {
			$url = $this->controller->here;
		}
		$url = Router::normalize($url);
		header("HTTP/1.0 404 Not Found");
		$this->controller->set(array(
			'code' => '404',
			'name' => __('Not Found', true),
			'message' => h($url),
			'base' => $this->controller->base
		));
		$this->_outputMessage('error404');
	}
/**
 * Renders the Missing Controller web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingController($params) {
		extract($params, EXTR_OVERWRITE);

		$controllerName = str_replace('Controller', '', $className);
		$this->controller->set(array(
			'controller' => $className,
			'controllerName' => $controllerName,
			'title' => __('Missing Controller', true)
		));
		$this->_outputMessage('missingController');
	}
/**
 * Renders the Missing Action web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingAction($params) {
		extract($params, EXTR_OVERWRITE);

		$controllerName = str_replace('Controller', '', $className);
		$this->controller->set(array(
			'controller' => $className,
			'controllerName' => $controllerName,
			'action' => $action,
			'title' => __('Missing Method in Controller', true)
		));
		$this->_outputMessage('missingAction');
	}
/**
 * Renders the Private Action web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function privateAction($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array(
			'controller' => $className,
			'action' => $action,
			'title' => __('Trying to access private method in class', true)
		));
		$this->_outputMessage('privateAction');
	}
/**
 * Renders the Missing Table web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingTable($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array(
			'model' => $className,
			'table' => $table,
			'title' => __('Missing Database Table', true)
		));
		$this->_outputMessage('missingTable');
	}
/**
 * Renders the Missing Database web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingDatabase($params = array()) {
		$this->controller->set(array(
			'title' => __('Scaffold Missing Database Connection', true)
		));
		$this->_outputMessage('missingScaffolddb');
	}
/**
 * Renders the Missing View web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingView($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array(
			'controller' => $className,
			'action' => $action,
			'file' => $file,
			'title' => __('Missing View', true)
		));
		$this->_outputMessage('missingView');
	}
/**
 * Renders the Missing Layout web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingLayout($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->layout = 'default';
		$this->controller->set(array(
			'file' => $file,
			'title' => __('Missing Layout', true)
		));
		$this->_outputMessage('missingLayout');
	}
/**
 * Renders the Database Connection web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingConnection($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array(
			'model' => $className,
			'title' => __('Missing Database Connection', true)
		));
		$this->_outputMessage('missingConnection');
	}
/**
 * Renders the Missing Helper file web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingHelperFile($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array(
			'helperClass' => Inflector::camelize($helper) . "Helper",
			'file' => $file,
			'title' => __('Missing Helper File', true)
		));
		$this->_outputMessage('missingHelperFile');
	}
/**
 * Renders the Missing Helper class web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingHelperClass($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array(
			'helperClass' => Inflector::camelize($helper) . "Helper",
			'file' => $file,
			'title' => __('Missing Helper Class', true)
		));
		$this->_outputMessage('missingHelperClass');
	}
/**
 * Renders the Missing Component file web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingComponentFile($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array(
			'controller' => $className,
			'component' => $component,
			'file' => $file,
			'title' => __('Missing Component File', true)
		));
		$this->_outputMessage('missingComponentFile');
	}
/**
 * Renders the Missing Component class web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingComponentClass($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array(
			'controller' => $className,
			'component' => $component,
			'file' => $file,
			'title' => __('Missing Component Class', true)
		));
		$this->_outputMessage('missingComponentClass');
	}
/**
 * Renders the Missing Model class web page.
 *
 * @param unknown_type $params Parameters for controller
 * @access public
 */
	function missingModel($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array(
			'model' => $className,
			'title' => __('Missing Model', true)
		));
		$this->_outputMessage('missingModel');
	}
/**
 * Output message
 *
 * @access protected
 */
	function _outputMessage($template) {
		$this->controller->render($template);
		$this->controller->afterFilter();
		echo $this->controller->output;
	}
}
?>