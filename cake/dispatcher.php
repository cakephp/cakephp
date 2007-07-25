<?php
/* SVN FILE: $Id$ */
/**
 * Dispatcher takes the URL information, parses it for paramters and
 * tells the involved controllers what to do.
 *
 * This is the heart of Cake's operation.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework <http://www.cakephp.org/>
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
 * @subpackage		cake.cake
 * @since			CakePHP(tm) v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * List of helpers to include
 */
	uses('router', DS.'controller'.DS.'controller');
/**
 * Dispatcher translates URLs to controller-action-paramter triads.
 *
 * Dispatches the request, creating appropriate models and controllers.
 *
 * @package		cake
 * @subpackage	cake.cake
 */
class Dispatcher extends Object {
/**
 * Base URL
 *
 * @var string
 * @access public
 */
	var $base = false;
/**
 * Current URL
 *
 * @var string
 * @access public
 */
	var $here = false;
/**
 * Admin route (if on it)
 *
 * @var string
 * @access public
 */
	var $admin = false;
/**
 * Webservice route
 *
 * @var string
 * @access public
 */
	var $webservices = null;
/**
 * Plugin being served (if any)
 *
 * @var string
 * @access public
 */
	var $plugin = null;
/**
 * Constructor.
 */
	function __construct($url = null) {
		parent::__construct();
		if ($url !== null) {
			return $this->dispatch($url);
		}
	}
/**
 * Dispatches and invokes given URL, handing over control to the involved controllers, and then renders the results (if autoRender is set).
 *
 * If no controller of given name can be found, invoke() shows error messages in
 * the form of Missing Controllers information. It does the same with Actions (methods of Controllers are called
 * Actions).
 *
 * @param string $url	URL information to work on.
 * @param array $additionalParams	Settings array ("bare", "return"),
 * which is melded with the GET and POST params.
 * @return boolean		Success
 * @access public
 */
	function dispatch($url, $additionalParams = array()) {
		$params = array_merge($this->parseParams($url), $additionalParams);
		$missingAction = $missingView = $privateAction = false;

		$this->base = $this->baseUrl();

		$pluginPath = null;
		if (!empty($params['plugin'])) {
			$this->plugin = $params['plugin'];
			$pluginPath = Inflector::camelize($this->plugin).'.';
		}

		if (!empty($params['controller'])) {
			$ctrlName = Inflector::camelize($params['controller']);
			$ctrlClass = $ctrlName.'Controller';
		}

		if (!loadController($pluginPath . $ctrlName)) {
			if (preg_match('/([\\.]+)/', $ctrlName)) {
				Router::setRequestInfo(array($params, array('base' => $this->base, 'webroot' => $this->webroot)));

				return $this->cakeError('error404',	array(array('url' => strtolower($ctrlName),
														'message' => 'Was not found on this server',
														'base' => $this->base)));
			} else {
				Router::setRequestInfo(array($params, array('base' => $this->base, 'webroot' => $this->webroot)));
				return $this->cakeError('missingController', array(
					array(
						'className' => Inflector::camelize($params['controller']."Controller"),
						'webroot' => $this->webroot,
						'url' => $url,
						'base' => $this->base
					)
				));
			}
		} else {
			$controller =& new $ctrlClass();
		}

		if (empty($params['action'])) {
			$params['action'] = 'index';
		}

		if (defined('CAKE_ADMIN')) {
			if (isset($params[CAKE_ADMIN])) {
				$this->admin = '/'.CAKE_ADMIN ;
				$url = preg_replace('/'.CAKE_ADMIN.'(\/|$)/', '', $url);
				$params['action'] = CAKE_ADMIN.'_'.$params['action'];
			} elseif (strpos($params['action'], CAKE_ADMIN) === 0) {
				$privateAction = true;
			}
		}

		$this->here = $this->base . $this->admin . '/' . $url;


		$protected = array('constructclasses', 'redirect', 'set', 'setAction', 'isauthorized', 'validate', 'validateerrors',
							'render', 'referer', 'disablecache', 'flash', 'generatefieldnames', 'postconditions', 'cleanupfields',
							'paginate', 'beforefilter', 'beforerender', 'afterfilter', 'object', 'tostring', 'requestaction', 'log',
							'cakeerror');

		$classMethods = array_map("low", get_class_methods($controller));

		if (in_array(low($params['action']), $protected)  || strpos($params['action'], '_', 0) === 0) {
			$privateAction = true;
		}

		if (!in_array(low($params['action']), $classMethods)) {
			$missingAction = true;
		}

		if (in_array('return', array_keys($params)) && $params['return'] == 1) {
			$controller->autoRender = false;
		}

		$controller->base = $this->base;
		$controller->here = $this->here;
		$controller->webroot = $this->webroot;
		$controller->params = $params;
		$controller->plugin = $this->plugin;
		$controller->action = $params['action'];
		$controller->webservices = $params['webservices'];

		list($passedArgs, $namedArgs) = Router::getArgs($params, $controller->namedArgs, $controller->argSeparator);
		$controller->passedArgs = $passedArgs;
		$controller->namedArgs = $namedArgs;

		if (!empty($controller->params['data'])) {
			$controller->data =& $controller->params['data'];
		} else {
			$controller->data = null;
		}

		if (!empty($params['bare'])) {
			$controller->autoLayout = false;
		}

		if (isset($params['layout'])) {
			if ($params['layout'] === '') {
				$controller->autoLayout = false;
			} else {
				$controller->layout = $params['layout'];
			}
		}

		if (isset($params['viewPath'])) {
			$controller->viewPath = $params['viewPath'];
		}

		foreach (array('components', 'helpers') as $var) {
			if (isset($params[$var]) && !empty($params[$var]) && is_array($controller->{$var})) {
				$diff = array_diff($params[$var], $controller->{$var});
				$controller->{$var} = array_merge($controller->{$var}, $diff);
			}
		}

		if (!is_null($controller->webservices)) {
			array_push($controller->components, $controller->webservices);
			array_push($controller->helpers, $controller->webservices);
		}
		Router::setRequestInfo(array($params, array('base' => $this->base, 'here' => $this->here, 'webroot' => $this->webroot, 'passedArgs' => $controller->passedArgs, 'argSeparator' => $controller->argSeparator, 'namedArgs' => $controller->namedArgs, 'webservices' => $controller->webservices)));
		$controller->_initComponents();
		$controller->constructClasses();

		if ($privateAction) {
			$this->start($controller);
			return $this->cakeError('privateAction', array(
				array(
					'className' => Inflector::camelize($params['controller']."Controller"),
					'action' => $params['action'],
					'webroot' => $this->webroot,
					'url' => $url,
					'base' => $this->base
				)
			));
		}

		return $this->_invoke($controller, $params, $missingAction);
	}
/**
 * Invokes given controller's render action if autoRender option is set. Otherwise the
 * contents of the operation are returned as a string.
 *
 * @param object $controller Controller to invoke
 * @param array $params Parameters with at least the 'action' to invoke
 * @param boolean $missingAction Set to true if missing action should be rendered, false otherwise
 * @return string Output as sent by controller
 * @access protected
 */
	function _invoke (&$controller, $params, $missingAction = false) {
		$this->start($controller);
		$classVars = get_object_vars($controller);

		if ($missingAction && in_array('scaffold', array_keys($classVars))) {
			uses('controller'. DS . 'scaffold');
			return new Scaffold($controller, $params);
		} elseif ($missingAction && !in_array('scaffold', array_keys($classVars))) {
				return $this->cakeError('missingAction', array(
					array(
						'className' => Inflector::camelize($params['controller']."Controller"),
						'action' => $params['action'],
						'webroot' => $this->webroot,
						'url' => $this->here,
						'base' => $this->base
					)
				));
		} else {
			$output = call_user_func_array(array(&$controller, $params['action']), empty($params['pass'])? null: $params['pass']);
		}

		if ($controller->autoRender) {
			$output = $controller->render();
		}

		$controller->output =& $output;

		foreach ($controller->components as $c) {
			$path = preg_split('/\/|\./', $c);
			$c = $path[count($path) - 1];
			if (isset($controller->{$c}) && is_object($controller->{$c}) && is_callable(array($controller->{$c}, 'shutdown'))) {
				if (!array_key_exists('enabled', get_object_vars($controller->{$c})) || $controller->{$c}->enabled == true) {
					$controller->{$c}->shutdown($controller);
				}
			}
		}
		$controller->afterFilter();
		return $controller->output;
	}
/**
 * Starts up a controller (by calling its beforeFilter methods and
 * starting its components)
 *
 * @param object $controller Controller to start
 * @access public
 */
	function start(&$controller) {
		if (!empty($controller->beforeFilter)) {
			if (is_array($controller->beforeFilter)) {

				foreach ($controller->beforeFilter as $filter) {
					if (is_callable(array($controller,$filter)) && $filter != 'beforeFilter') {
						$controller->$filter();
					}
				}
			} else {
				if (is_callable(array($controller, $controller->beforeFilter)) && $controller->beforeFilter != 'beforeFilter') {
					$controller->{$controller->beforeFilter}();
				}
			}
		}
		$controller->beforeFilter();

		foreach ($controller->components as $c) {
			$path = preg_split('/\/|\./', $c);
			$c = $path[count($path) - 1];
			if (isset($controller->{$c}) && is_object($controller->{$c}) && is_callable(array($controller->{$c}, 'startup'))) {
				if (!array_key_exists('enabled', get_object_vars($controller->{$c})) || $controller->{$c}->enabled == true) {
					$controller->{$c}->startup($controller);
				}
			}
		}
	}

/**
 * Returns array of GET and POST parameters. GET parameters are taken from given URL.
 *
 * @param string $fromUrl	URL to mine for parameter information.
 * @return array Parameters found in POST and GET.
 * @access public
 */
	function parseParams($fromUrl) {
		$Route = Router::getInstance();
		extract(Router::getNamedExpressions());
		include CONFIGS.'routes.php';
		$params = Router::parse($fromUrl);

		if (isset($_POST)) {
			if (ini_get('magic_quotes_gpc') == 1) {
				$params['form'] = stripslashes_deep($_POST);
			} else {
				$params['form'] = $_POST;
			}
		}

		if (isset($params['form']['data'])) {
			$params['data'] = Router::stripEscape($params['form']['data']);
			unset($params['form']['data']);
		}

		if (isset($_GET)) {
			if (ini_get('magic_quotes_gpc') == 1) {
				$url = stripslashes_deep($_GET);
			} else {
				$url = $_GET;
			}
			if (isset($params['url'])) {
				$params['url'] = am($params['url'], $url);
			} else {
				$params['url'] = $url;
			}
		}

		foreach ($_FILES as $name => $data) {
			if ($name != 'data') {
				$params['form'][$name] = $data;
			}
		}

		if (isset($_FILES['data'])) {
			foreach ($_FILES['data'] as $key => $data) {
				foreach ($data as $model => $fields) {
					foreach ($fields as $field => $value) {
						$params['data'][$model][$field][$key] = $value;
					}
				}
			}
		}
		$params['bare'] = empty($params['ajax']) ? (empty($params['bare']) ? 0: 1) : 1;
		$params['webservices'] = empty($params['webservices']) ? null : $params['webservices'];
		return $params;
	}
/**
 * Returns a base URL and sets the proper webroot
 *
 * @return string	Base URL
 * @access public
 */
	function baseUrl() {
		$base = $this->base;
		$this->webroot = '/';

		$baseUrl = Configure::read('baseUrl');
		$app = Configure::read('app');
		$webroot = Configure::read('webroot');

		$file = $script = null;
		if (!$baseUrl && $this->base == false) {
			$docRoot = env('DOCUMENT_ROOT');
			$script = env('SCRIPT_FILENAME');
			$base = r($docRoot, '', $script);
		} elseif ($baseUrl && $this->base == false) {
			$base = $baseUrl;
		}

		$file = basename($base);
		if (($baseUrl || $this->base) && strpos($file, '.php') !== false) {
			$baseUrl = true;
			$file = '/'. $file;
		}

		$base = dirname($base);

		if ($base == '/' || $base == '.') {
			$base = '';
		}

		if (!$baseUrl && strpos($script, $app) !== false && $app === 'app') {
			$base =  str_replace($app.'/', '', $base);
		}

		$base = str_replace('//', '/', str_replace('/'.$webroot, '', $base));

		$this->webroot = $base .'/';

		if (!$baseUrl) {
			return $base;
		}

		if ($baseUrl && $base == '') {
			return $file;
		}

		if (strpos($base, $app) === false) {
			$this->webroot .=  '/' . $app . '/' ;
		}

		if ($baseUrl && strpos($this->webroot, $webroot) === false) {
			$this->webroot .= $webroot . '/';
		}
		$this->webroot = str_replace('//', '/', $this->webroot);

		return $base . $file;
	}
/**
 * Restructure params in case we're serving a plugin.
 *
 * @param array $params Array on where to re-set 'controller', 'action', and 'pass' indexes
 * @return array Restructured array
 * @access protected
 */
	function _restructureParams($params) {
		$params['controller'] = $params['action'];

		if (isset($params['pass'][0])) {
			$params['action'] = $params['pass'][0];
			array_shift($params['pass']);
		} else {
			$params['action'] = null;
		}
		return $params;
	}
}

?>