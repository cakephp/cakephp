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
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 0.10.5.1732
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class ErrorHandler extends Object {
/**
 * Controller instance.
 *
 * @var object
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
		App::import('Controller', 'App');
		App::import('Core', 'Sanitize');

		$this->controller =& new AppController();
		$this->controller->_set(Router::getPaths());
		$this->controller->params = Router::getParams();
		$this->controller->constructClasses();
		$this->controller->_set(array('cacheAction' => false, 'viewPath' => 'errors'));

		$allow = array('.', '/', '_', ' ', '-', '~');
		if (substr(PHP_OS, 0, 3) == "WIN") {
			$allow = array_merge($allow, array('\\', ':'));
		}

		$messages = Sanitize::paranoid($messages, $allow);

		if (!isset($messages[0])) {
			$messages = array($messages);
		}

		if (method_exists($this->controller, 'apperror')) {
			return $this->controller->appError($method, $messages);
		}

		if (!in_array(strtolower($method), array_map('strtolower', get_class_methods($this)))) {
			$method = 'error';
		}

		if ($method == 'error') {
			$this->dispatchMethod($method, $messages);
			$this->stop();
		} elseif (Configure::read() == 0 && (isset($code) && $code == 500)) {
			$this->dispatchMethod('error500', $messages);
			exit();
		} elseif (Configure::read() == 0) {
			$this->dispatchMethod('error404', $messages);
			$this->stop();
		} else {
			$this->dispatchMethod($method, $messages);
			$this->stop();
		}
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
		$this->__outputMessage('error404');
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
		$this->__outputMessage('error404');
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
		$this->controller->set(array('controller' => $className,
										'controllerName' => $controllerName,
										'title' => __('Missing Controller', true)));
		$this->__outputMessage('missingController');
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
		$this->controller->set(array('controller' => $className,
										'controllerName' => $controllerName,
										'action' => $action,
										'title' => __('Missing Method in Controller', true)));
		$this->__outputMessage('missingAction');
	}
/**
 * Renders the Private Action web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function privateAction($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array('controller' => $className,
										'action' => $action,
										'title' => __('Trying to access private method in class', true)));
		$this->__outputMessage('privateAction');
	}
/**
 * Renders the Missing Table web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingTable($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array('model' => $className,
										'table' => $table,
										'title' => __('Missing Database Table', true)));
		$this->__outputMessage('missingTable');
	}
/**
 * Renders the Missing Database web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingDatabase($params = array()) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array('title' => __('Scaffold Missing Database Connection', true)));
		$this->__outputMessage('missingScaffolddb');
	}
/**
 * Renders the Missing View web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingView($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array('controller' => $className,
										'action' => $action,
										'file' => $file,
										'title' => __('Missing View', true)));
		$this->__outputMessage('missingView');

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
		$this->controller->set(array('file' => $file,
										'title' => __('Missing Layout', true)));
		$this->__outputMessage('missingLayout');
	}
/**
 * Renders the Database Connection web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingConnection($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array('model' => $className,
										'title' => __('Missing Database Connection', true)));
		$this->__outputMessage('missingConnection');
	}
/**
 * Renders the Missing Helper file web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingHelperFile($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array('helperClass' => Inflector::camelize($helper) . "Helper",
										'file' => $file,
										'title' => __('Missing Helper File', true)));
		$this->__outputMessage('missingHelperFile');
	}
/**
 * Renders the Missing Helper class web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingHelperClass($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array('helperClass' => Inflector::camelize($helper) . "Helper",
										'file' => $file,
										'title' => __('Missing Helper Class', true)));
		$this->__outputMessage('missingHelperClass');
	}
/**
 * Renders the Missing Component file web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingComponentFile($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array('controller' => $className,
										'component' => $component,
										'file' => $file,
										'title' => __('Missing Component File', true)));
		$this->__outputMessage('missingComponentFile');
	}
/**
 * Renders the Missing Component class web page.
 *
 * @param array $params Parameters for controller
 * @access public
 */
	function missingComponentClass($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array('controller' => $className,
										'component' => $component,
										'file' => $file,
										'title' => __('Missing Component Class', true)));
		$this->__outputMessage('missingComponentClass');
	}
/**
 * Renders the Missing Model class web page.
 *
 * @param unknown_type $params Parameters for controller
 * @access public
 */
	function missingModel($params) {
		extract($params, EXTR_OVERWRITE);

		$this->controller->set(array('model' => $className,
										'title' => __('Missing Model', true)));
		$this->__outputMessage('missingModel');
	}
/**
 * Output message
 *
 * @access private
 */
	function __outputMessage($template) {
		$this->controller->render($template);
		$this->controller->afterFilter();
		e($this->controller->output);
	}
}
?>