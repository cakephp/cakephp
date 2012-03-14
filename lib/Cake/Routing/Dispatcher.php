<?php
/**
 * Dispatcher takes the URL information, parses it for parameters and
 * tells the involved controllers what to do.
 *
 * This is the heart of Cake's operation.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Routing
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Router', 'Routing');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('Controller', 'Controller');
App::uses('Scaffold', 'Controller');
App::uses('View', 'View');
App::uses('Debugger', 'Utility');

/**
 * Dispatcher converts Requests into controller actions.  It uses the dispatched Request
 * to locate and load the correct controller.  If found, the requested action is called on
 * the controller.
 *
 * @package       Cake.Routing
 */
class Dispatcher {

/**
 * Constructor.
 *
 * @param string $base The base directory for the application. Writes `App.base` to Configure.
 */
	public function __construct($base = false) {
		if ($base !== false) {
			Configure::write('App.base', $base);
		}
	}

/**
 * Dispatches and invokes given Request, handing over control to the involved controller. If the controller is set
 * to autoRender, via Controller::$autoRender, then Dispatcher will render the view.
 *
 * Actions in CakePHP can be any public method on a controller, that is not declared in Controller.  If you
 * want controller methods to be public and in-accessible by URL, then prefix them with a `_`.
 * For example `public function _loadPosts() { }` would not be accessible via URL.  Private and protected methods
 * are also not accessible via URL.
 *
 * If no controller of given name can be found, invoke() will throw an exception.
 * If the controller is found, and the action is not found an exception will be thrown.
 *
 * @param CakeRequest $request Request object to dispatch.
 * @param CakeResponse $response Response object to put the results of the dispatch into.
 * @param array $additionalParams Settings array ("bare", "return") which is melded with the GET and POST params
 * @return boolean Success
 * @throws MissingControllerException When the controller is missing.
 */
	public function dispatch(CakeRequest $request, CakeResponse $response, $additionalParams = array()) {
		if ($this->asset($request->url, $response) || $this->cached($request->here())) {
			return;
		}

		Router::setRequestInfo($request);
		$request = $this->parseParams($request, $additionalParams);
		$controller = $this->_getController($request, $response);

		if (!($controller instanceof Controller)) {
			throw new MissingControllerException(array(
				'class' => Inflector::camelize($request->params['controller']) . 'Controller',
				'plugin' => empty($request->params['plugin']) ? null : Inflector::camelize($request->params['plugin'])
			));
		}

		return $this->_invoke($controller, $request, $response);
	}

/**
 * Initializes the components and models a controller will be using.
 * Triggers the controller action, and invokes the rendering if Controller::$autoRender is true and echo's the output.
 * Otherwise the return value of the controller action are returned.
 *
 * @param Controller $controller Controller to invoke
 * @param CakeRequest $request The request object to invoke the controller for.
 * @param CakeResponse $response The response object to receive the output
 * @return void
 */
	protected function _invoke(Controller $controller, CakeRequest $request, CakeResponse $response) {
		$controller->constructClasses();
		$controller->startupProcess();

		$render = true;
		$result = $controller->invokeAction($request);
		if ($result instanceof CakeResponse) {
			$render = false;
			$response = $result;
		}

		if ($render && $controller->autoRender) {
			$response = $controller->render();
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
 * If Routes have not been loaded they will be loaded, and app/Config/routes.php will be run.
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
 * @param CakeRequest $request Request object
 * @param CakeResponse $response Response for the controller.
 * @return mixed name of controller if not loaded, or object if loaded
 */
	protected function _getController($request, $response) {
		$ctrlClass = $this->_loadController($request);
		if (!$ctrlClass) {
			return false;
		}
		$reflection = new ReflectionClass($ctrlClass);
		if ($reflection->isAbstract() || $reflection->isInterface()) {
			return false;
		}
		return $reflection->newInstance($request, $response);
	}

/**
 * Load controller and return controller classname
 *
 * @param CakeRequest $request
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
			$class = $controller . 'Controller';
			App::uses('AppController', 'Controller');
			App::uses($pluginName . 'AppController', $pluginPath . 'Controller');
			App::uses($class, $pluginPath . 'Controller');
			if (class_exists($class)) {
				return $class;
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
		include APP . 'Config' . DS . 'routes.php';
	}

/**
 * Outputs cached dispatch view cache
 *
 * @param string $path Requested URL path with any query string parameters
 * @return string|boolean False if is not cached or output
 */
	public function cached($path) {
		if (Configure::read('Cache.check') === true) {
			if ($path == '/') {
				$path = 'home';
			}
			$path = strtolower(Inflector::slug($path));

			$filename = CACHE . 'views' . DS . $path . '.php';

			if (!file_exists($filename)) {
				$filename = CACHE . 'views' . DS . $path . '_index.php';
			}
			if (file_exists($filename)) {
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
 * @param string $url Requested URL
 * @param CakeResponse $response The response object to put the file contents in.
 * @return boolean True on success if the asset file was found and sent
 */
	public function asset($url, CakeResponse $response) {
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
		if (($isCss && empty($filters['css'])) || ($isJs && empty($filters['js']))) {
			$response->statusCode(404);
			$response->send();
			return true;
		} elseif ($isCss) {
			include WWW_ROOT . DS . $filters['css'];
			return true;
		} elseif ($isJs) {
			include WWW_ROOT . DS . $filters['js'];
			return true;
		}
		$pathSegments = explode('.', $url);
		$ext = array_pop($pathSegments);
		$parts = explode('/', $url);
		$assetFile = null;

		if ($parts[0] === 'theme') {
			$themeName = $parts[1];
			unset($parts[0], $parts[1]);
			$fileFragment = urldecode(implode(DS, $parts));
			$path = App::themePath($themeName) . 'webroot' . DS;
			if (file_exists($path . $fileFragment)) {
				$assetFile = $path . $fileFragment;
			}
		} else {
			$plugin = Inflector::camelize($parts[0]);
			if (CakePlugin::loaded($plugin)) {
				unset($parts[0]);
				$fileFragment = urldecode(implode(DS, $parts));
				$pluginWebroot = CakePlugin::path($plugin) . 'webroot' . DS;
				if (file_exists($pluginWebroot . $fileFragment)) {
					$assetFile = $pluginWebroot . $fileFragment;
				}
			}
		}

		if ($assetFile !== null) {
			$this->_deliverAsset($response, $assetFile, $ext);
			return true;
		}
		return false;
	}

/**
 * Sends an asset file to the client
 *
 * @param CakeResponse $response The response object to use.
 * @param string $assetFile Path to the asset file in the file system
 * @param string $ext The extension of the file to determine its mime type
 * @return void
 */
	protected function _deliverAsset(CakeResponse $response, $assetFile, $ext) {
		ob_start();
		$compressionEnabled = Configure::read('Asset.compress') && $response->compress();
		if ($response->type($ext) == $ext) {
			$contentType = 'application/octet-stream';
			$agent = env('HTTP_USER_AGENT');
			if (preg_match('%Opera(/| )([0-9].[0-9]{1,2})%', $agent) || preg_match('/MSIE ([0-9].[0-9]{1,2})/', $agent)) {
				$contentType = 'application/octetstream';
			}
			$response->type($contentType);
		}
		if (!$compressionEnabled) {
			$response->header('Content-Length', filesize($assetFile));
		}
		$response->cache(filemtime($assetFile));
		$response->send();
		ob_clean();
		if ($ext === 'css' || $ext === 'js') {
			include $assetFile;
		} else {
			readfile($assetFile);
		}

		if ($compressionEnabled) {
			ob_end_flush();
		}
	}

}
