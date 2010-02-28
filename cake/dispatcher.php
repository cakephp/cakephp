<?php
/**
 * Dispatcher takes the URL information, parses it for paramters and
 * tells the involved controllers what to do.
 *
 * This is the heart of Cake's operation.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * List of helpers to include
 */
App::import('Core', 'Router');
App::import('Controller', 'Controller', false);

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

		if ($this->asset($url) || $this->cached($url)) {
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
		$privateAction = $this->params['action'][0] === '_';
		$prefixes = Router::prefixes();

		if (!empty($prefixes)) {
			if (isset($this->params['prefix'])) {
				$this->params['action'] = $this->params['prefix'] . '_' . $this->params['action'];
			} elseif (strpos($this->params['action'], '_') > 0) {
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
		$controller->plugin = isset($this->params['plugin']) ? $this->params['plugin'] : null;
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
		$controller->startupProcess();

		$methods = array_flip($controller->methods);

		if (!isset($methods[strtolower($params['action'])])) {
			if ($controller->scaffold !== false) {
				App::import('Controller', 'Scaffold', false);
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
		$output = call_user_func_array(array(&$controller, $params['action']), $params['pass']);

		if ($controller->autoRender) {
			$controller->output = $controller->render();
		} elseif (empty($controller->output)) {
			$controller->output = $output;
		}
		$controller->shutdownProcess();

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
				if (!empty($_SERVER)) {
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
			$params['data'] = $params['form']['data'];
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

		$file = '/' . basename($baseUrl);
		$base = dirname($baseUrl);

		if ($base === DS || $base === '.') {
			$base = '';
		}
		$this->webroot = $base .'/';

		if (!empty($base)) {
			if (strpos($this->webroot, $dir) === false) {
				$this->webroot .= $dir . '/' ;
			}
			if (strpos($this->webroot, $webroot) === false) {
				$this->webroot .= $webroot . '/';
			}
		}
		return $base . $file;
	}
/**
 * Restructure params in case we're serving a plugin.
 *
 * @param array $params Array on where to re-set 'controller', 'action', and 'pass' indexes
 * @param boolean $reverse  If true all the params are shifted one forward, so plugin becomes
 *   controller, controller becomes action etc.  If false, plugin is made equal to controller
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
		} else {
			$params['plugin'] = $params['controller'];
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
	function &__getController() {
		$original = $params = $this->params;

		$controller = false;
		$ctrlClass = $this->__loadController($params);
		if (!$ctrlClass) {
			if (!isset($params['plugin'])) {
				$params = $this->_restructureParams($params);
			} else {
				$params = $this->_restructureParams($params, true);
			}
			$ctrlClass = $this->__loadController($params);
			if (!$ctrlClass) {
				$this->params = $original;
				return $controller;
			}
		}
		$name = $ctrlClass;
		$ctrlClass .= 'Controller';
		if (class_exists($ctrlClass)) {
			if (empty($params['plugin']) && strtolower(get_parent_class($ctrlClass)) === strtolower($name . 'AppController')) {
				$params = $this->_restructureParams($params);
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
			$pluginName = Inflector::camelize($params['plugin']);
			$pluginPath = $pluginName . '.';
			$this->params['controller'] = $params['plugin'];
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
			$uri = explode('?', $uri, 2);

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
 * Outputs cached dispatch view cache
 *
 * @param string $url Requested URL
 * @access public
 */
	function cached($url) {
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
					App::import('View', 'View', false);
				}
				$controller = null;
				$view =& new View($controller);
				$return = $view->renderCache($filename, getMicrotime());
				if (!$return) {
					ClassRegistry::removeObject('view');
				}
				return $return;
			}
		}

		return false;
	}

/**
 * Checks if a requested asset exists and sends it to the browser
 *
 * @param $url string $url Requested URL
 * @return boolean True on success if the asset file was found and sent
 * @access public
 */
	function asset($url) {
		if (strpos($url, '..') !== false || strpos($url, '.') === false) {
			return false;
		}
		$filters = Configure::read('Asset.filter');
		$isCss = strpos($url, 'ccss/') === 0;
		$isJs = strpos($url, 'cjs/') === 0;

		if (($isCss && empty($filters['css'])) || ($isJs && empty($filters['js']))) {
			header('HTTP/1.1 404 Not Found');
			return $this->_stop();
		} elseif ($isCss) {
			include WWW_ROOT . DS . $filter['css'];
			$this->_stop();
		} elseif ($isJs) {
			include WWW_ROOT . DS . $filters['js'];
			$this->_stop();
		}
		$controller = null;
		$ext = array_pop(explode('.', $url));
		$parts = explode('/', $url);
		$assetFile = null;

		if ($parts[0] === 'theme') {
			$themeName = $parts[1];
			unset($parts[0], $parts[1]);
			$fileFragment = implode('/', $parts);

			$viewPaths = App::path('views');
			foreach ($viewPaths as $viewPath) {
				$path = $viewPath . 'themed' . DS . $themeName . DS . 'webroot' . DS;
				if (file_exists($path . $fileFragment)) {
					$assetFile = $path . $fileFragment;
					break;
				}
			}
		} else {
			$plugin = $parts[0];
			unset($parts[0]);
			$fileFragment = implode('/', $parts);
			$pluginWebroot = App::pluginPath($plugin) . 'webroot' . DS;
			if (file_exists($pluginWebroot . $fileFragment)) {
				$assetFile = $pluginWebroot . $fileFragment;
			}
		}

		if ($assetFile !== null) {
			$this->_deliverAsset($assetFile, $ext);
			return true;
		}
		return false;
	}

/**
 * Sends an asset file to the client
 *
 * @param string $assetFile Path to the asset file in the file system
 * @param string $ext The extension of the file to determine its mime type
 * @return void
 * @access protected
 */
	function _deliverAsset($assetFile, $ext) {
		$ob = @ini_get("zlib.output_compression") !== '1' && extension_loaded("zlib") && (strpos(env('HTTP_ACCEPT_ENCODING'), 'gzip') !== false);
		if ($ob && Configure::read('Asset.compress')) {
			ob_start();
			ob_start('ob_gzhandler');
		}

		App::import('View', 'Media', false);
		$Media = new MediaView($controller);
		if (isset($Media->mimeType[$ext])) {
			$contentType = $Media->mimeType[$ext];
		} else {
			$contentType = 'application/octet-stream';
			$agent = env('HTTP_USER_AGENT');
			if (preg_match('%Opera(/| )([0-9].[0-9]{1,2})%', $agent) || preg_match('/MSIE ([0-9].[0-9]{1,2})/', $agent)) {
				$contentType = 'application/octetstream';
			}
		}

		header("Date: " . date("D, j M Y G:i:s ", filemtime($assetFile)) . 'GMT');
		header('Content-type: ' . $contentType);
		header("Expires: " . gmdate("D, j M Y H:i:s", time() + DAY) . " GMT");
		header("Cache-Control: cache");
		header("Pragma: cache");

		if ($ext === 'css' || $ext === 'js') {
			include($assetFile);
		} else {
			readfile($assetFile);
		}

		if (Configure::read('Asset.compress')) {
			ob_end_flush();
		}
	}

}
?>