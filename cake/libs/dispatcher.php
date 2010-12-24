<?php
/**
 * Dispatcher takes the URL information, parses it for paramters and
 * tells the involved controllers what to do.
 *
 * This is the heart of Cake's operation.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * List of helpers to include
 */
App::import('Core', 'Router', false);
App::import('Core', 'CakeRequest', false);
App::import('Core', 'CakeResponse', false);
App::import('Controller', 'Controller', false);

/**
 * Dispatcher converts Requests into controller actions.  It uses the dispatched Request
 * to locate and load the correct controller.  If found, the requested action is called on 
 * the controller.
 *
 * @package       cake
 */
class Dispatcher {

/**
 * Current URL
 *
 * @var string
 * @access public
 */
	public $here = false;

/**
 * The request object
 *
 * @var CakeRequest
 * @access public
 */
	public $request = null;

/**
 * Response object used for asset/cached responses.
 *
 * @var CakeResponse
 */
	public $response = null;

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
 * Dispatches and invokes given Request, handing over control to the involved controller. If the controller is set
 * to autoRender, via Controller::$autoRender, then Dispatcher will render the view.
 *
 * Actions in CakePHP can be any public method on a controller, that is not declared in Controller.  If you
 * want controller methods to be public and in-accesible by URL, then prefix them with a `_`.  
 * For example `public function _loadPosts() { }` would not be accessible via URL.  Private and protected methods
 * are also not accessible via URL.
 *
 * If no controller of given name can be found, invoke() will throw an exception.
 * If the controller is found, and the action is not found an exception will be thrown.
 *
 * @param CakeRequest $request Request object to dispatch.
 * @param array $additionalParams Settings array ("bare", "return") which is melded with the GET and POST params
 * @return boolean Success
 * @throws MissingControllerException, MissingActionException, PrivateActionException if any of those error states
 *    are encountered.
 */
	public function dispatch(CakeRequest $request, $additionalParams = array()) {
		$this->here = $request->here;

		if ($this->asset($request->url) || $this->cached($request->url)) {
			return;
		}

		$this->request = $this->parseParams($request, $additionalParams);
		$controller = $this->_getController($this->request);

		if (!is_object($controller)) {
			Router::setRequestInfo($request);
			throw new MissingControllerException(array(
				'controller' => Inflector::camelize($request->params['controller']) . 'Controller'
			));
		}

		Router::setRequestInfo($request);

		if ($this->_isPrivateAction($request)) {
			throw new PrivateActionException(array(
				'controller' => Inflector::camelize($request->params['controller']) . "Controller",
				'action' => $request->params['action']
			));
		}

		return $this->_invoke($controller, $request);
	}

/**
 * Check if the request's action is marked as private, with an underscore, of if the request is attempting to 
 * directly accessing a prefixed action.
 *
 * @param CakeRequest $request The request to check
 * @return boolean
 */
	protected function _isPrivateAction($request) {
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
		return $privateAction;
	}

/**
 * Initializes the components and models a controller will be using.
 * Triggers the controller action, and invokes the rendering if Controller::$autoRender is true and echo's the output.
 * Otherwise the return value of the controller action are returned.
 *
 * @param Controller $controller Controller to invoke
 * @param CakeRequest $request The request object to invoke the controller for.
 * @return string Output as sent by controller
 * @throws MissingActionException when the action being called is missing.
 */
	protected function _invoke(Controller $controller, CakeRequest $request) {
		$controller->constructClasses();
		$controller->startupProcess();

		$methods = array_flip($controller->methods);

		if (!isset($methods[$request->params['action']])) {
			if ($controller->scaffold !== false) {
				App::import('Controller', 'Scaffold', false);
				return new Scaffold($controller, $request);
			}
			throw new MissingActionException(array(
				'controller' => Inflector::camelize($request->params['controller']) . "Controller",
				'action' => $request->params['action']
			));
		}
		$result = call_user_func_array(array(&$controller, $request->params['action']), $request->params['pass']);
		$response = $controller->getResponse();

		if ($controller->autoRender) {
			$controller->render();
		} elseif ($response->body() === null) {
			$response->body($result);
		}
		$controller->shutdownProcess();

		if (isset($request->params['return'])) {
			return $response->body();
		}
		$response->send();
	}

/**
 * Applies Routing and additionalParameters to the request to be dispatched.
 * If Routes have not been loaded they will be loaded, and app/config/routes.php will be run.
 *
 * @param CakeRequest $request CakeRequest object to mine for parameter information.
 * @param array $additionalParams An array of additional parameters to set to the request.
 *   Useful when Object::requestAction() is involved
 * @return CakeRequest The request object with routing params set.
 */
	public function parseParams(CakeRequest $request, $additionalParams = array()) {
		if (count(Router::$routes) == 0) {
			$namedExpressions = Router::getNamedExpressions();
			extract($namedExpressions);
			$this->_loadRoutes();
		}

		$params = Router::parse($request->url);
		$request->addParams($params);

		if (!empty($additionalParams)) {
			$request->addParams($additionalParams);
		}
		return $request;
	}

/**
 * Get controller to use, either plugin controller or application controller
 *
 * @param array $params Array of parameters
 * @return mixed name of controller if not loaded, or object if loaded
 */
	protected function _getController($request) {
		$ctrlClass = $this->_loadController($request);
		if (!$ctrlClass) {
			return false;
		}
		$ctrlClass .= 'Controller';
		if (class_exists($ctrlClass)) {
			return new $ctrlClass($request);
		}
	}

/**
 * Load controller and return controller classname
 *
 * @param array $params Array of parameters
 * @return string|bool Name of controller class name
 */
	protected function _loadController($request) {
		$pluginName = $pluginPath = $controller = null;
		if (!empty($request->params['plugin'])) {
			$pluginName = $controller = Inflector::camelize($request->params['plugin']);
			$pluginPath = $pluginName . '.';
		}
		if (!empty($request->params['controller'])) {
			$controller = Inflector::camelize($request->params['controller']);
		}
		if ($pluginPath . $controller) {
			if (App::import('Controller', $pluginPath . $controller)) {
				return $controller;
			}
		}
		return false;
	}

/**
 * Loads route configuration
 *
 * @return void
 */
	protected function _loadRoutes() {
		include CONFIGS . 'routes.php';
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
				$view = new View($controller);
				return $view->renderCache($filename, microtime(true));
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
		$isCss = (
			strpos($url, 'ccss/') === 0 || 
			preg_match('#^(theme/([^/]+)/ccss/)|(([^/]+)(?<!css)/ccss)/#i', $url)
		);
		$isJs = (
			strpos($url, 'cjs/') === 0 ||
			preg_match('#^/((theme/[^/]+)/cjs/)|(([^/]+)(?<!js)/cjs)/#i', $url)
		);
		if (!$this->response) {
			$this->response = new CakeResponse();
		}
		if (($isCss && empty($filters['css'])) || ($isJs && empty($filters['js']))) {
			$this->response->statusCode(404);
			$this->response->send();
			return $this->_stop();
		} elseif ($isCss) {
			include WWW_ROOT . DS . $filters['css'];
			$this->_stop();
		} elseif ($isJs) {
			include WWW_ROOT . DS . $filters['js'];
			$this->_stop();
		}
		$controller = null;
		$pathSegments = explode('.', $url);
		$ext = array_pop($pathSegments);
		$parts = explode('/', $url);
		$assetFile = null;

		if ($parts[0] === 'theme') {
			$themeName = $parts[1];
			unset($parts[0], $parts[1]);
			$fileFragment = implode(DS, $parts);
			$path = App::themePath($themeName) . 'webroot' . DS;
			if (file_exists($path . $fileFragment)) {
				$assetFile = $path . $fileFragment;
			}
		} else {
			$plugin = $parts[0];
			unset($parts[0]);
			$fileFragment = implode(DS, $parts);
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
		ob_start();
		$compressionEnabled = Configure::read('Asset.compress') && $this->response->compress();
		if ($this->response->type($ext) == $ext) {
			$contentType = 'application/octet-stream';
			$agent = env('HTTP_USER_AGENT');
			if (preg_match('%Opera(/| )([0-9].[0-9]{1,2})%', $agent) || preg_match('/MSIE ([0-9].[0-9]{1,2})/', $agent)) {
				$contentType = 'application/octetstream';
			}
			$this->response->type($contentType);
		}
		$this->response->cache(filemtime($assetFile));
		$this->response->send();
		ob_clean();
		if ($ext === 'css' || $ext === 'js') {
			include($assetFile);
		} else {
			readfile($assetFile);
		}

		if ($compressionEnabled) {
			ob_end_flush();
		}
	}
}
