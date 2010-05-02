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
App::import('Core', 'CakeRequest');
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
	public $base = false;

/**
 * webroot path
 *
 * @var string
 * @access public
 */
	public $webroot = '/';

/**
 * Current URL
 *
 * @var string
 * @access public
 */
	public $here = false;

/**
 * the params for this request
 *
 * @var string
 * @access public
 */
	public $params = null;

/**
 * Constructor.
 */
	public function __construct($url = null, $base = false) {
		if ($base !== false) {
			Configure::write('App.base', $base);
		}
		if ($url !== null) {
			$this->dispatch($url);
		}
	}

/**
 * Dispatches and invokes given URL, handing over control to the involved controllers, and then renders the 
 * results (if autoRender is set).
 *
 * If no controller of given name can be found, invoke() shows error messages in
 * the form of Missing Controllers information. It does the same with Actions (methods of Controllers are called
 * Actions).
 *
 * @param mixed $url Either a string url or a CakeRequest object information to work on.  If $url is a string
 *   It will be used to create the request object.
 * @param array $additionalParams Settings array ("bare", "return") which is melded with the GET and POST params
 * @return boolean Success
 */
	public function dispatch($url = null, $additionalParams = array()) {
		if (is_array($url)) {
			$url = $this->_extractParams($url, $additionalParams);
		}
		if ($url instanceof CakeRequest) {
			$request = $url;
		} else {
			$request = new CakeRequest($url);
		}
		$this->here = $request->here;

		if ($this->asset($request->url) || $this->cached($request->url)) {
			return $this->_stop();
		}

		$request = $this->parseParams($request, $additionalParams);
		$this->params = $request;

		$controller = $this->_getController();

		if (!is_object($controller)) {
			Router::setRequestInfo(array(
				$this->params, array('base' => $request->base, 'webroot' => $request->webroot)
			));
			return $this->cakeError('missingController', array(array(
				'className' => Inflector::camelize($request->params['controller']) . 'Controller',
				'webroot' => $request->webroot,
				'url' => $url,
				'base' => $request->base
			)));
		}
		$privateAction = $request->params['action'][0] === '_';
		$prefixes = Router::prefixes();

		if (!empty($prefixes)) {
			if (isset($request->params['prefix'])) {
				$request->params['action'] = $request->params['prefix'] . '_' . $request->params['action'];
			} elseif (strpos($request->params['action'], '_') > 0) {
				list($prefix, $action) = explode('_', $request->params['action']);
				$privateAction = in_array($prefix, $prefixes);
			}
		}

		Router::setRequestInfo(array(
			$request->params, array('base' => $request->base, 'here' => $request->here, 'webroot' => $request->webroot)
		));

		if ($privateAction) {
			return $this->cakeError('privateAction', array(array(
				'className' => Inflector::camelize($request->params['controller'] . "Controller"),
				'action' => $request->params['action'],
				'webroot' => $request->webroot,
				'url' => $request->url,
				'base' => $request->base
			)));
		}
		$controller->base = $request->base;
		$controller->here = $request->here;
		$controller->webroot = $request->webroot;
		$controller->plugin = isset($request->params['plugin']) ? $request->params['plugin'] : null;
		$controller->params = $request;
		$controller->request = $request;
		$controller->action =& $request->params['action'];
		$controller->passedArgs = array_merge($request->params['pass'], $request->params['named']);

		$controller->data = null;
		if (!empty($request->params['data'])) {
			$controller->data =& $request->params['data'];
		}
		if (array_key_exists('return', $request->params) && $request->params['return'] == 1) {
			$controller->autoRender = false;
		}
		if (!empty($request->params['bare'])) {
			$controller->autoLayout = false;
		}
		return $this->_invoke($controller, $request);
	}

/**
 * Initializes the components and models a controller will be using.
 * Triggers the controller action, and invokes the rendering if Controller::$autoRender is true and echo's the output.
 * Otherwise the return value of the controller action are returned.
 *
 * @param object $controller Controller to invoke
 * @param array $params Parameters with at least the 'action' to invoke
 * @param boolean $missingAction Set to true if missing action should be rendered, false otherwise
 * @return string Output as sent by controller
 */
	protected function _invoke(&$controller, $request) {
		$controller->constructClasses();
		$controller->startupProcess();

		$methods = array_flip($controller->methods);

		if (!isset($methods[strtolower($request['action'])])) {
			if ($controller->scaffold !== false) {
				App::import('Controller', 'Scaffold', false);
				return new Scaffold($controller, $request);
			}
			return $this->cakeError('missingAction', array(array(
				'className' => Inflector::camelize($request->params['controller']."Controller"),
				'action' => $request->params['action'],
				'webroot' => $request->webroot,
				'url' => $request->here,
				'base' => $request->base
			)));
		}
		$output = call_user_func_array(array(&$controller, $request->params['action']), $request->params['pass']);

		if ($controller->autoRender) {
			$controller->output = $controller->render();
		} elseif (empty($controller->output)) {
			$controller->output = $output;
		}
		$controller->shutdownProcess();

		if (isset($request->params['return'])) {
			return $controller->output;
		}
		echo($controller->output);
	}

/**
 * Sets the params when $url is passed as an array to Object::requestAction();
 * Merges the $url and $additionalParams and creates a string url.
 *
 * @param array $url Array or request parameters
 * @param array $additionalParams Array of additional parameters.
 * @return string $url The generated url string.
 */
	protected function _extractParams($url, $additionalParams = array()) {
		$defaults = array('pass' => array(), 'named' => array(), 'form' => array());
		$this->params = array_merge($defaults, $url, $additionalParams);
		return Router::url($url);
	}

/**
 * Returns array of GET and POST parameters. GET parameters are taken from given URL.
 *
 * @param CakeRequest $fromUrl CakeRequest object to mine for parameter information.
 * @return array Parameters found in POST and GET.
 */
	public function parseParams(CakeRequest $request, $additionalParams = array()) {
		$namedExpressions = Router::getNamedExpressions();
		extract($namedExpressions);
		include CONFIGS . 'routes.php';

		$request = Router::parse($request);

		if (!empty($additionalParams)) {
			$request->params = array_merge($request->params, $additionalParams);
		}
		return $request;
	}

/**
 * Get controller to use, either plugin controller or application controller
 *
 * @param array $params Array of parameters
 * @return mixed name of controller if not loaded, or object if loaded
 */
	protected function &_getController() {
		$controller = false;
		$ctrlClass = $this->__loadController($this->params);
		if (!$ctrlClass) {
			return $controller;
		}
		$ctrlClass .= 'Controller';
		if (class_exists($ctrlClass)) {
			$controller =& new $ctrlClass();
		}
		return $controller;
	}

/**
 * Load controller and return controller classname
 *
 * @param array $params Array of parameters
 * @return string|bool Name of controller class name
 * @access private
 */
	function __loadController($params) {
		$pluginName = $pluginPath = $controller = null;
		if (!empty($params['plugin'])) {
			$pluginName = $controller = Inflector::camelize($params['plugin']);
			$pluginPath = $pluginName . '.';
		}
		if (!empty($params['controller'])) {
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
 * Outputs cached dispatch view cache
 *
 * @param string $url Requested URL
 */
	public function cached($url) {
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
				$return = $view->renderCache($filename, microtime(true));
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
 */
	public function asset($url) {
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
			include WWW_ROOT . DS . $filters['css'];
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
 */
	protected function _deliverAsset($assetFile, $ext) {
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