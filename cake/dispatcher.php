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
 * CakePHP : Rapid Development Framework <http://www.cakephp.org/>
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
 * @subpackage		cake.cake
 * @since			CakePHP v 0.2.9
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
 * @var string
 */
	var $base = false;
/**
 * @var string
 */
	var $admin = false;
/**
 * @var string
 */
	var $webservices = null;
/**
 * @var string
 */
	var $plugin = null;
/**
 * Constructor.
 */
	function __construct() {
		parent::__construct();
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
 */
	function dispatch($url, $additionalParams=array()) {
		$params = array_merge($this->parseParams($url), $additionalParams);
		$missingController = false;
		$missingAction = false;
		$missingView = false;
		$privateAction = false;
		$this->base = $this->baseUrl();

		if (empty($params['controller'])) {
			$missingController = true;
		} else {
			$ctrlName = Inflector::camelize($params['controller']);
			$ctrlClass = $ctrlName.'Controller';

			if(!class_exists($ctrlClass)) {
				if (!loadController($ctrlName)) {
					$pluginName = Inflector::camelize($params['action']);
					if (!loadPluginController(Inflector::underscore($ctrlName), $pluginName)) {
						if(preg_match('/([\\.]+)/', $ctrlName)) {
							return $this->cakeError('error404', array(
															array('url' => strtolower($ctrlName),
																	'message' => 'Was not found on this server',
																	'base' => $this->base)));
																	exit();
						} else {
							$missingController = true;
						}
					} else {
						$params['plugin'] = Inflector::underscore($ctrlName);
					}
				} else {
					$params['plugin'] = null;
					$this->plugin = null;
				}
			}
		}

		if(isset($params['plugin'])){
			$plugin = $params['plugin'];
			$pluginName = Inflector::camelize($params['action']);
			$pluginClass = $pluginName.'Controller';
			$ctrlClass = $pluginClass;
			$oldAction = $params['action'];
			$params = $this->_restructureParams($params);
			$this->plugin = $plugin;
			loadPluginModels($plugin);
			$this->base = $this->base.'/'.Inflector::underscore($ctrlName);

			if(empty($params['controller']) || !class_exists($pluginClass)) {
				$params['controller'] = Inflector::underscore($ctrlName);
				$ctrlClass = $ctrlName.'Controller';
				if (!is_null($params['action'])) {
					array_unshift($params['pass'], $params['action']);
				}
				$params['action'] = $oldAction;
			}
		}

		if(defined('CAKE_ADMIN')) {
			if(isset($params[CAKE_ADMIN])) {
				$this->admin = '/'.CAKE_ADMIN ;
				$url = preg_replace('/'.CAKE_ADMIN.'\//', '', $url);

				if (empty($params['action'])) {
					$params['action'] = CAKE_ADMIN.'_'.'index';
				} else {
					$params['action'] = CAKE_ADMIN.'_'.$params['action'];
				}
			} else {
				$__access = strpos($params['action'], CAKE_ADMIN);
				if ($__access === 0) {
					$privateAction = true;
				}
			}
		}

		if ($missingController) {
			return $this->cakeError('missingController', array(
											array('className' => Inflector::camelize($params['controller']."Controller"),
													'webroot' => $this->webroot,
													'url' => $url,
													'base' => $this->base)));
		} else {
			$controller =& new $ctrlClass();
		}

		$classMethods = get_class_methods($controller);
		$classVars = get_object_vars($controller);

		if (empty($params['action'])) {
			$params['action'] = 'index';
		}

		if((in_array($params['action'], $classMethods) || in_array(strtolower($params['action']), $classMethods)) && strpos($params['action'], '_', 0) === 0) {
			$privateAction = true;
		}

		if(!in_array($params['action'], $classMethods) && !in_array(strtolower($params['action']), $classMethods)) {
			$missingAction = true;
		}

		if (in_array(strtolower($params['action']), array('beforefilter', 'beforerender', 'afterfilter'))) {
			$missingAction = true;
		}

		if(in_array('return', array_keys($params)) && $params['return'] == 1) {
			$controller->autoRender = false;
		}

		$controller->base = $this->base;
		$base = strip_plugin($this->base, $this->plugin);
		if(defined("BASE_URL")) {
			$controller->here = $base . $this->admin . $url;
		} else {
			$controller->here = $base . $this->admin . '/' . $url;
		}
		$controller->webroot = $this->webroot;
		$controller->params = $params;
		$controller->action = $params['action'];

		if (!empty($controller->params['data'])) {
			$controller->data =& $controller->params['data'];
		} else {
			$controller->data = null;
		}

		if (!empty($controller->params['pass'])) {
			$controller->passed_args =& $controller->params['pass'];
			$controller->passedArgs =& $controller->params['pass'];
			if (is_array($controller->namedArgs) && in_array($params['action'], array_keys($controller->namedArgs))) {

				$this->passedArgs =& $controller->params['pass'];
				$this->_getNamedArgs($controller->namedArgs[$params['action']]);

				$controller->passedArgs =& $this->passedArgs;
				$controller->namedArgs =& $this->namedArgs;
			} elseif ($controller->namedArgs === true) {
				$c = count($controller->passedArgs);
				for ($i = $c - 1; $i > -1; $i--) {
					if (strpos($controller->passedArgs[$i], $controller->argSeparator) !== false) {
						list($argKey, $argVal) = explode($controller->argSeparator, $controller->passedArgs[$i]);
						$controller->passedArgs[$argKey] = $argVal;
						unset($controller->passedArgs[$i]);
						unset($params['pass'][$i]);
					}
				}
			}
		} else {
			$controller->passed_args = null;
			$controller->passedArgs = null;
		}

		if (!empty($params['bare'])) {
			$controller->autoLayout = !$params['bare'];
		} else {
			$controller->autoLayout = $controller->autoLayout;
		}

		$controller->webservices = $params['webservices'];
		$controller->plugin = $this->plugin;

		if(!is_null($controller->webservices)) {
			array_push($controller->components, $controller->webservices);
			array_push($controller->helpers, $controller->webservices);
			$component =& new Component($controller);
		}
		$controller->_initComponents();
		$controller->constructClasses();

		if ($missingAction && !in_array('scaffold', array_keys($classVars))){
			return $this->cakeError('missingAction', array(
											array('className' => Inflector::camelize($params['controller']."Controller"),
													'action' => $params['action'],
													'webroot' => $this->webroot,
													'url' => $url,
													'base' => $this->base)));
		}

		if ($privateAction) {
			return $this->cakeError('privateAction', array(
											array('className' => Inflector::camelize($params['controller']."Controller"),
													'action' => $params['action'],
													'webroot' => $this->webroot,
													'url' => $url,
													'base' => $this->base)));
		}
		return $this->_invoke($controller, $params, $missingAction);
	}
/**
 * Invokes given controller's render action if autoRender option is set. Otherwise the contents of the operation are returned as a string.
 *
 * @param object $controller
 * @param array $params
 * @param boolean $missingAction
 * @return string
 */
	function _invoke (&$controller, $params, $missingAction = false) {
		$this->start($controller);
		$classVars = get_object_vars($controller);

		if ($missingAction && in_array('scaffold', array_keys($classVars))) {
			uses(DS.'controller'.DS.'scaffold');
			return new Scaffold($controller, $params);
		} else {
			$output = call_user_func_array(array(&$controller, $params['action']), empty($params['pass'])? null: $params['pass']);
		}
		if ($controller->autoRender) {
			$output = $controller->render();
		}
		$controller->output =& $output;
		$controller->afterFilter();
		return $controller->output;
	}
/**
 * Starts up a controller
 *
 * @param object $controller
 */
	function start(&$controller) {
		if (!empty($controller->beforeFilter)) {
			if(is_array($controller->beforeFilter)) {

				foreach($controller->beforeFilter as $filter) {
					if(is_callable(array($controller,$filter)) && $filter != 'beforeFilter') {
						$controller->$filter();
					}
				}
			} else {
				if(is_callable(array($controller, $controller->beforeFilter)) && $controller->beforeFilter != 'beforeFilter') {
					$controller->{$controller->beforeFilter}();
				}
			}
		}
		$controller->beforeFilter();

		foreach($controller->components as $c) {
			if (isset($controller->{$c}) && is_object($controller->{$c}) && is_callable(array($controller->{$c}, 'startup'))) {
				$controller->{$c}->startup($controller);
			}
		}
	}

/**
 * Returns array of GET and POST parameters. GET parameters are taken from given URL.
 *
 * @param string $from_url	URL to mine for parameter information.
 * @return array Parameters found in POST and GET.
 */
	function parseParams($from_url) {
		$Route = new Router();
		include CONFIGS.'routes.php';
		$params = $Route->parse ($from_url);

		if (ini_get('magic_quotes_gpc') == 1) {
			if(!empty($_POST)) {
				$params['form'] = stripslashes_deep($_POST);
			}
		} else {
			$params['form'] = $_POST;
		}

		if (isset($params['form']['data'])) {
			$params['data'] = $params['form']['data'];
		}

		if (isset($_GET)) {
			if (ini_get('magic_quotes_gpc') == 1) {
				$params['url'] = stripslashes_deep($_GET);
			} else {
				$params['url'] = $_GET;
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
		$params['bare'] = empty($params['ajax'])? (empty($params['bare'])? 0: 1): 1;
		$params['webservices'] = empty($params['webservices']) ? null : $params['webservices'];
		return $params;
	}
/**
 * Returns a base URL.
 *
 * @return string	Base URL
 */
	function baseUrl() {
		$htaccess = null;
		$base = $this->admin;
		$this->webroot = '';

		if (defined('BASE_URL')) {
			$base = BASE_URL.$this->admin;
		}

		$docRoot = env('DOCUMENT_ROOT');
		$scriptName = env('PHP_SELF');
		$r = null;
		$appDirName = str_replace('/','\/',preg_quote(APP_DIR));
		$webrootDirName = str_replace('/', '\/', preg_quote(WEBROOT_DIR));

		if (preg_match('/'.$appDirName.'\\'.DS.$webrootDirName.'/', $docRoot)) {
			$this->webroot = '/';

			if (preg_match('/^(.*)\/index\.php$/', $scriptName, $r)) {

				if(!empty($r[1])) {
					return  $base.$r[1];
				}
			}
		} else {
			if (defined('BASE_URL')) {
				$webroot = setUri();
				$htaccess = preg_replace('/(?:'.APP_DIR.'(.*)|index\\.php(.*))/i', '', $webroot).APP_DIR.'/'.$webrootDirName.'/';
			}

			if (preg_match('/^(.*)\\/'.$appDirName.'\\/'.$webrootDirName.'\\/index\\.php$/', $scriptName, $regs)) {

				if(APP_DIR === 'app') {
					$appDir = null;
				} else {
					$appDir = '/'.APP_DIR;
				}
				!empty($htaccess)? $this->webroot = $htaccess : $this->webroot = $regs[1].$appDir.'/';
				return  $base.$regs[1].$appDir;

			} elseif (preg_match('/^(.*)\\/'.$webrootDirName.'([^\/i]*)|index\\\.php$/', $scriptName, $regs)) {
				!empty($htaccess)? $this->webroot = $htaccess : $this->webroot = $regs[0].'/';
				return  $base.$regs[0];

			} else {
				!empty($htaccess)? $this->webroot = $htaccess : $this->webroot = '/';
				return $base;
			}
		}
		return $base;
	}
/**
 * Enter description here...
 *
 * @param unknown_type $params
 * @return unknown
 */
	function _restructureParams($params) {
		$params['controller'] = $params['action'];

		if(isset($params['pass'][0])) {
			$params['action'] = $params['pass'][0];
			array_shift($params['pass']);
		} else {
			$params['action'] = null;
		}
		return $params;
	}
/**
 * Enter description here...
 *
 * @param unknown_type $params
 * @return unknown
 */
	function _getNamedArgs($args) {
		if(!is_array($args)) {
			$args = func_get_args();
		}
		if(!is_array($args)) {
			$args = explode(',',$controller->namedArgs[$params['action']]);
		}

		$this->namedArgs = array();

		if(is_array($this->passedArgs) && is_array($args)) {
			foreach ($args as $arg) {
				if(in_array($arg, $this->passedArgs)) {
					$key = array_search($arg,$this->passedArgs);
					$value = $key + 1;
					$this->namedArgs[$arg] = $this->passedArgs[$value];

					unset($this->passedArgs[$key]);
					unset($this->passedArgs[$value]);
				}
			}
			$this->passedArgs = array_values($this->passedArgs);
			return $this->namedArgs;
		}
	}
}

?>