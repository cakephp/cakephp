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
 * CakePHP(tm) : Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake
 * @since         CakePHP(tm) v 0.2.9
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * List of helpers to include
 */
App::import('Core', array('Router', 'Controller'));
/**
 * Dispatcher translates URLs to controller-action-paramter triads.
 *
 * Dispatches the request, creating appropriate models and controllers.
 *
 * @package       cake
 * @subpackage    cake.cake
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
 * webroot path
 *
 * @var string
 * @access public
 */
	var $webroot = '/';
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
 * Plugin being served (if any)
 *
 * @var string
 * @access public
 */
	var $plugin = null;
/**
 * the params for this request
 *
 * @var string
 * @access public
 */
	var $params = null;
/**
 * Constructor.
 */
	function __construct($url = null, $base = false) {
		if ($base !== false) {
			Configure::write('App.base', $base);
		}
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
 * @param string $url URL information to work on
 * @param array $additionalParams Settings array ("bare", "return") which is melded with the GET and POST params
 * @return boolean Success
 * @access public
 */
	function dispatch($url = null, $additionalParams = array()) {
		if ($this->base === false) {
			$this->base = $this->baseUrl();
		}

		if (is_array($url)) {
			$url = $this->__extractParams($url, $additionalParams);
		} else {
			if ($url) {
				$_GET['url'] = $url;
			}
			$url = $this->getUrl();
			$this->params = array_merge($this->parseParams($url), $additionalParams);
		}

		$this->here = $this->base . '/' . $url;

		if ($this->cached($url)) {
			$this->_stop();
		}

		$controller =& $this->__getController();

		if (!is_object($controller)) {
			Router::setRequestInfo(array($this->params, array('base' => $this->base, 'webroot' => $this->webroot)));
			return $this->cakeError('missingController', array(array(
				'className' => Inflector::camelize($this->params['controller']) . 'Controller',
				'webroot' => $this->webroot,
				'url' => $url,
				'base' => $this->base
			)));
		}

		$privateAction = (bool)(strpos($this->params['action'], '_', 0) === 0);
		$prefixes = Router::prefixes();

		if (!empty($prefixes)) {
			if (isset($this->params['prefix'])) {
				$this->params['action'] = $this->params['prefix'] . '_' . $this->params['action'];
			} elseif (strpos($this->params['action'], '_') !== false && !$privateAction) {
				list($prefix, $action) = explode('_', $this->params['action']);
				$privateAction = in_array($prefix, $prefixes);
			}
		}

		Router::setRequestInfo(array(
			$this->params, array('base' => $this->base, 'here' => $this->here, 'webroot' => $this->webroot)
		));

		if ($privateAction) {
			return $this->cakeError('privateAction', array(array(
				'className' => Inflector::camelize($this->params['controller'] . "Controller"),
				'action' => $this->params['action'],
				'webroot' => $this->webroot,
				'url' => $url,
				'base' => $this->base
			)));
		}

		$controller->base = $this->base;
		$controller->here = $this->here;
		$controller->webroot = $this->webroot;
		$controller->plugin = $this->plugin;
		$controller->params =& $this->params;
		$controller->action =& $this->params['action'];
		$controller->passedArgs = array_merge($this->params['pass'], $this->params['named']);

		if (!empty($this->params['data'])) {
			$controller->data =& $this->params['data'];
		} else {
			$controller->data = null;
		}
		if (array_key_exists('return', $this->params) && $this->params['return'] == 1) {
			$controller->autoRender = false;
		}
		if (!empty($this->params['bare'])) {
			$controller->autoLayout = false;
		}
		if (array_key_exists('layout', $this->params)) {
			if (empty($this->params['layout'])) {
				$controller->autoLayout = false;
			} else {
				$controller->layout = $this->params['layout'];
			}
		}
		if (isset($this->params['viewPath'])) {
			$controller->viewPath = $this->params['viewPath'];
		}
		return $this->_invoke($controller, $this->params);
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
	function _invoke(&$controller, $params) {
		$controller->constructClasses();
		$controller->Component->initialize($controller);
		$controller->beforeFilter();
		$controller->Component->startup($controller);

		$methods = array_flip($controller->methods);

		if (!isset($methods[strtolower($params['action'])])) {
			if ($controller->scaffold !== false) {
				App::import('Core', 'Scaffold');
				return new Scaffold($controller, $params);
			}
			return $this->cakeError('missingAction', array(array(
				'className' => Inflector::camelize($params['controller']."Controller"),
				'action' => $params['action'],
				'webroot' => $this->webroot,
				'url' => $this->here,
				'base' => $this->base
			)));
		}
		$output = $controller->dispatchMethod($params['action'], $params['pass']);

		if ($controller->autoRender) {
			$controller->output = $controller->render();
		} elseif (empty($controller->output)) {
			$controller->output = $output;
		}
		$controller->Component->shutdown($controller);
		$controller->afterFilter();

		if (isset($params['return'])) {
			return $controller->output;
		}
		echo($controller->output);
	}
/**
 * Sets the params when $url is passed as an array to Object::requestAction();
 *
 * @param array $url
 * @param array $additionalParams
 * @return string $url
 * @access private
 */
	function __extractParams($url, $additionalParams = array()) {
		$defaults = array('pass' => array(), 'named' => array(), 'form' => array());
		$this->params = array_merge($defaults, $url, $additionalParams);
		return Router::url($url);
	}
/**
 * Returns array of GET and POST parameters. GET parameters are taken from given URL.
 *
 * @param string $fromUrl URL to mine for parameter information.
 * @return array Parameters found in POST and GET.
 * @access public
 */
	function parseParams($fromUrl) {
		$params = array();

		if (isset($_POST)) {
			$params['form'] = $_POST;
			if (ini_get('magic_quotes_gpc') === '1') {
				$params['form'] = stripslashes_deep($params['form']);
			}
			if (env('HTTP_X_HTTP_METHOD_OVERRIDE')) {
				$params['form']['_method'] = env('HTTP_X_HTTP_METHOD_OVERRIDE');
			}
			if (isset($params['form']['_method'])) {
				if (isset($_SERVER) && !empty($_SERVER)) {
					$_SERVER['REQUEST_METHOD'] = $params['form']['_method'];
				} else {
					$_ENV['REQUEST_METHOD'] = $params['form']['_method'];
				}
				unset($params['form']['_method']);
			}
		}
		$namedExpressions = Router::getNamedExpressions();
		extract($namedExpressions);
		include CONFIGS . 'routes.php';
		$params = array_merge(Router::parse($fromUrl), $params);

		if (strlen($params['action']) === 0) {
			$params['action'] = 'index';
		}
		if (isset($params['form']['data'])) {
			$params['data'] = Router::stripEscape($params['form']['data']);
			unset($params['form']['data']);
		}
		if (isset($_GET)) {
			if (ini_get('magic_quotes_gpc') === '1') {
				$url = stripslashes_deep($_GET);
			} else {
				$url = $_GET;
			}
			if (isset($params['url'])) {
				$params['url'] = array_merge($params['url'], $url);
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
					if (is_array($fields)) {
						foreach ($fields as $field => $value) {
							if (is_array($value)) {
								foreach ($value as $k => $v) {
									$params['data'][$model][$field][$k][$key] = $v;
								}
							} else {
								$params['data'][$model][$field][$key] = $value;
							}
						}
					} else {
						$params['data'][$model][$key] = $fields;
					}
				}
			}
		}
		return $params;
	}
/**
 * Returns a base URL and sets the proper webroot
 *
 * @return string Base URL
 * @access public
 */
	function baseUrl() {
		$dir = $webroot = null;
		$config = Configure::read('App');
		extract($config);

		if (!$base) {
			$base = $this->base;
		}
		if ($base !== false) {
			$this->webroot = $base . '/';
			return $this->base = $base;
		}
		if (!$baseUrl) {
			$replace = array('<', '>', '*', '\'', '"');
			$base = str_replace($replace, '', dirname(env('PHP_SELF')));

			if ($webroot === 'webroot' && $webroot === basename($base)) {
				$base = dirname($base);
			}
			if ($dir === 'app' && $dir === basename($base)) {
				$base = dirname($base);
			}

			if ($base === DS || $base === '.') {
				$base = '';
			}

			$this->webroot = $base .'/';
			return $base;
		}
		$file = null;

		if ($baseUrl) {
			$file = '/' . basename($baseUrl);
			$base = dirname($baseUrl);

			if ($base === DS || $base === '.') {
				$base = '';
			}
			$this->webroot = $base .'/';

			if (strpos($this->webroot, $dir) === false) {
				$this->webroot .= $dir . '/' ;
			}
			if (strpos($this->webroot, $webroot) === false) {
				$this->webroot .= $webroot . '/';
			}
			return $base . $file;
		}
		return false;
	}
/**
 * Restructure params in case we're serving a plugin.
 *
 * @param array $params Array on where to re-set 'controller', 'action', and 'pass' indexes
 * @param boolean $reverse
 * @return array Restructured array
 * @access protected
 */
	function _restructureParams($params, $reverse = false) {
		if ($reverse === true) {
			extract(Router::getArgs($params['action']));
			$params = array_merge($params, array(
				'controller'=> $params['plugin'],
				'action'=> $params['controller'],
				'pass' => array_merge($pass, $params['pass']),
				'named' => array_merge($named, $params['named'])
			));
			$this->plugin = $params['plugin'];
		} else {
			$params['plugin'] = $params['controller'];
			$params['controller'] = $params['action'];
			if (isset($params['pass'][0])) {
				$params['action'] = $params['pass'][0];
				array_shift($params['pass']);
			} else {
				$params['action'] = null;
			}
		}
		return $params;
	}
/**
 * Get controller to use, either plugin controller or application controller
 *
 * @param array $params Array of parameters
 * @return mixed name of controller if not loaded, or object if loaded
 * @access private
 */
	function &__getController($params = null) {
		if (!is_array($params)) {
			$original = $params = $this->params;
		}
		$controller = false;
		$ctrlClass = $this->__loadController($params);
		if (!$ctrlClass) {
			if (!isset($params['plugin'])) {
				$params = $this->_restructureParams($params);
			} else {
				if (empty($original['pass']) && $original['action'] == 'index') {
					$params['action'] = null;
				}
				$params = $this->_restructureParams($params, true);
			}
			$ctrlClass = $this->__loadController($params);
			if (!$ctrlClass) {
				$this->params = $original;
				return $controller;
			}
		} else {
			$params = $this->params;
		}
		$name = $ctrlClass;
		$ctrlClass = $ctrlClass . 'Controller';
		if (class_exists($ctrlClass)) {
			if (strtolower(get_parent_class($ctrlClass)) === strtolower($name . 'AppController') && empty($params['plugin'])) {
				$params = $this->_restructureParams($params);
				$params = $this->_restructureParams($params, true);
			}
			$this->params = $params;
			$controller =& new $ctrlClass();
		}
		return $controller;
	}
/**
 * Load controller and return controller class
 *
 * @param array $params Array of parameters
 * @return string|bool Name of controller class name
 * @access private
 */
	function __loadController($params) {
		$pluginName = $pluginPath = $controller = null;
		if (!empty($params['plugin'])) {
			$this->plugin = $params['plugin'];
			$pluginName = Inflector::camelize($params['plugin']);
			$pluginPath = $pluginName . '.';
			$this->params['controller'] = $this->plugin;
			$controller = $pluginName;
		}
		if (!empty($params['controller'])) {
			$this->params['controller'] = $params['controller'];
			$controller = Inflector::camelize($params['controller']);
		}
		if ($pluginPath . $controller) {
			if (App::import('Controller', $pluginPath . $controller)) {
				return $controller;
			}
		}
		return false;
	}
/**
 * Returns the REQUEST_URI from the server environment, or, failing that,
 * constructs a new one, using the PHP_SELF constant and other variables.
 *
 * @return string URI
 * @access public
 */
	function uri() {
		foreach (array('HTTP_X_REWRITE_URL', 'REQUEST_URI', 'argv') as $var) {
			if ($uri = env($var)) {
				if ($var == 'argv') {
					$uri = $uri[0];
				}
				break;
			}
		}
		$base = preg_replace('/^\//', '', '' . Configure::read('App.baseUrl'));

		if ($base) {
			$uri = preg_replace('/^(?:\/)?(?:' . preg_quote($base, '/') . ')?(?:url=)?/', '', $uri);
		}
		if (PHP_SAPI == 'isapi') {
			$uri = preg_replace('/^(?:\/)?(?:\/)?(?:\?)?(?:url=)?/', '', $uri);
		}
		if (!empty($uri)) {
			if (key($_GET) && strpos(key($_GET), '?') !== false) {
				unset($_GET[key($_GET)]);
			}
			$uri = preg_split('/\?/', $uri, 2);

			if (isset($uri[1])) {
				parse_str($uri[1], $_GET);
			}
			$uri = $uri[0];
		} else {
			$uri = env('QUERY_STRING');
		}
		if (is_string($uri) && strpos($uri, 'index.php') !== false) {
			list(, $uri) = explode('index.php', $uri, 2);
		}
		if (empty($uri) || $uri == '/' || $uri == '//') {
			return '';
		}
		return str_replace('//', '/', '/' . $uri);
	}
/**
 * Returns and sets the $_GET[url] derived from the REQUEST_URI
 *
 * @param string $uri Request URI
 * @param string $base Base path
 * @return string URL
 * @access public
 */
	function getUrl($uri = null, $base = null) {
		if (empty($_GET['url'])) {
			if ($uri == null) {
				$uri = $this->uri();
			}
			if ($base == null) {
				$base = $this->base;
			}
			$url = null;
			$tmpUri = preg_replace('/^(?:\?)?(?:\/)?/', '', $uri);
			$baseDir = preg_replace('/^\//', '', dirname($base)) . '/';

			if ($tmpUri === '/' || $tmpUri == $baseDir || $tmpUri == $base) {
				$url = $_GET['url'] = '/';
			} else {
				if ($base && strpos($uri, $base) !== false) {
					$elements = explode($base, $uri);
				} elseif (preg_match('/^[\/\?\/|\/\?|\?\/]/', $uri)) {
					$elements = array(1 => preg_replace('/^[\/\?\/|\/\?|\?\/]/', '', $uri));
				} else {
					$elements = array();
				}

				if (!empty($elements[1])) {
					$_GET['url'] = $elements[1];
					$url = $elements[1];
				} else {
					$url = $_GET['url'] = '/';
				}

				if (strpos($url, '/') === 0 && $url != '/') {
					$url = $_GET['url'] = substr($url, 1);
				}
			}
		} else {
			$url = $_GET['url'];
		}
		if ($url{0} == '/') {
			$url = substr($url, 1);
		}
		return $url;
	}
/**
 * Outputs cached dispatch for js, css, img, view cache
 *
 * @param string $url Requested URL
 * @access public
 */
	function cached($url) {
		if (strpos($url, 'css/') !== false || strpos($url, 'js/') !== false || strpos($url, 'img/') !== false) {
			if (strpos($url, 'ccss/') === 0) {
				include WWW_ROOT . DS . Configure::read('Asset.filter.css');
				$this->_stop();
			} elseif (strpos($url, 'cjs/') === 0) {
				include WWW_ROOT . DS . Configure::read('Asset.filter.js');
				$this->_stop();
			}
			$isAsset = false;
			$assets = array('js' => 'text/javascript', 'css' => 'text/css', 'gif' => 'image/gif', 'jpg' => 'image/jpeg', 'png' => 'image/png');
			$ext = array_pop(explode('.', $url));

			foreach ($assets as $type => $contentType) {
				if ($type === $ext) {
					if ($type === 'css' || $type === 'js') {
						$pos = strpos($url, $type . '/');
					} else {
						$pos = strpos($url, 'img/');
					}
					$isAsset = true;
					break;
				}
			}

			if ($isAsset === true) {
				$ob = @ini_get("zlib.output_compression") !== '1' && extension_loaded("zlib") && (strpos(env('HTTP_ACCEPT_ENCODING'), 'gzip') !== false);

				if ($ob && Configure::read('Asset.compress')) {
					ob_start();
					ob_start('ob_gzhandler');
				}
				$assetFile = null;
				$paths = array();

				if ($pos > 0) {
					$plugin = substr($url, 0, $pos - 1);
					$url = preg_replace('/^' . preg_quote($plugin, '/') . '\//i', '', $url);
					$pluginPaths = Configure::read('pluginPaths');
					$count = count($pluginPaths);
					for ($i = 0; $i < $count; $i++) {
						$paths[] = $pluginPaths[$i] . $plugin . DS . 'vendors' . DS;
					}
				}
				$paths = array_merge($paths, Configure::read('vendorPaths'));
				foreach ($paths as $path) {
					if (is_file($path . $url) && file_exists($path . $url)) {
						$assetFile = $path . $url;
						break;
					}
				}

				if ($assetFile !== null) {
					$fileModified = filemtime($assetFile);
					header("Date: " . date("D, j M Y G:i:s ", $fileModified) . 'GMT');
					header('Content-type: ' . $assets[$type]);
					header("Expires: " . gmdate("D, j M Y H:i:s", time() + DAY) . " GMT");
					header("Cache-Control: cache");
					header("Pragma: cache");
					if ($type === 'css' || $type === 'js') {
						include($assetFile);
					} else {
						readfile($assetFile);
					}

					if (Configure::read('Asset.compress')) {
						ob_end_flush();
					}
					return true;
				}
			}
		}

		if (Configure::read('Cache.check') === true) {
			$path = $this->here;
			if ($this->here == '/') {
				$path = 'home';
			}
			$path = strtolower(Inflector::slug($path));

			$filename = CACHE . 'views' . DS . $path . '.php';

			if (!file_exists($filename)) {
				$filename = CACHE . 'views' . DS . $path . '_index.php';
			}

			if (file_exists($filename)) {
				if (!class_exists('View')) {
					App::import('Core', 'View');
				}
				$controller = null;
				$view =& new View($controller, false);
				return $view->renderCache($filename, getMicrotime());
			}
		}
		return false;
	}
}
?>