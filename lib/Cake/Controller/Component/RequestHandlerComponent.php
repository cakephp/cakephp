<?php
/**
 * Request object for handling alternative HTTP requests
 *
 * Alternative HTTP requests can come from wireless units like mobile phones, palmtop computers,
 * and the like. These units have no use for Ajax requests, and this Component can tell how Cake
 * should respond to the different needs of a handheld computer and a desktop machine.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Controller.Component
 * @since         CakePHP(tm) v 0.10.4.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Component', 'Controller');
App::uses('Xml', 'Utility');

/**
 * Request object for handling alternative HTTP requests
 *
 * Alternative HTTP requests can come from wireless units like mobile phones, palmtop computers,
 * and the like. These units have no use for Ajax requests, and this Component can tell how Cake
 * should respond to the different needs of a handheld computer and a desktop machine.
 *
 * @package       Cake.Controller.Component
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/request-handling.html
 *
 */
class RequestHandlerComponent extends Component {

/**
 * The layout that will be switched to for Ajax requests
 *
 * @var string
 * @see RequestHandler::setAjax()
 */
	public $ajaxLayout = 'ajax';

/**
 * Determines whether or not callbacks will be fired on this component
 *
 * @var bool
 */
	public $enabled = true;

/**
 * Holds the reference to Controller::$request
 *
 * @var CakeRequest
 */
	public $request;

/**
 * Holds the reference to Controller::$response
 *
 * @var CakeResponse
 */
	public $response;

/**
 * Contains the file extension parsed out by the Router
 *
 * @var string
 * @see Router::parseExtensions()
 */
	public $ext = null;

/**
 * The template to use when rendering the given content type.
 *
 * @var string
 */
	protected $_renderType = null;

/**
 * A mapping between extensions and deserializers for request bodies of that type.
 * By default only JSON and XML are mapped, use RequestHandlerComponent::addInputType()
 *
 * @var array
 */
	protected $_inputTypeMap = array(
		'json' => array('json_decode', true)
	);

/**
 * A mapping between type and viewClass
 * By default only JSON and XML are mapped, use RequestHandlerComponent::viewClassMap()
 *
 * @var array
 */
	protected $_viewClassMap = array(
		'json' => 'Json',
		'xml' => 'Xml'
	);

/**
 * Constructor. Parses the accepted content types accepted by the client using HTTP_ACCEPT
 *
 * @param ComponentCollection $collection ComponentCollection object.
 * @param array $settings Array of settings.
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings + array('checkHttpCache' => true));
		$this->addInputType('xml', array(array($this, 'convertXml')));

		$Controller = $collection->getController();
		$this->request = $Controller->request;
		$this->response = $Controller->response;
	}

/**
 * Checks to see if a file extension has been parsed by the Router, or if the
 * HTTP_ACCEPT_TYPE has matches only one content type with the supported extensions.
 * If there is only one matching type between the supported content types & extensions,
 * and the requested mime-types, RequestHandler::$ext is set to that value.
 *
 * @param Controller $controller A reference to the controller
 * @return void
 * @see Router::parseExtensions()
 */
	public function initialize(Controller $controller) {
		if (isset($this->request->params['ext'])) {
			$this->ext = $this->request->params['ext'];
		}
		if (empty($this->ext) || $this->ext === 'html') {
			$this->_setExtension();
		}
		$this->params = $controller->params;
		if (!empty($this->settings['viewClassMap'])) {
			$this->viewClassMap($this->settings['viewClassMap']);
		}
	}

/**
 * Set the extension based on the accept headers.
 * Compares the accepted types and configured extensions.
 * If there is one common type, that is assigned as the ext/content type
 * for the response.
 * Type with the highest weight will be set. If the highest weight has more
 * then one type matching the extensions, the order in which extensions are specified
 * determines which type will be set.
 *
 * If html is one of the preferred types, no content type will be set, this
 * is to avoid issues with browsers that prefer html and several other content types.
 *
 * @return void
 */
	protected function _setExtension() {
		$accept = $this->request->parseAccept();
		if (empty($accept)) {
			return;
		}

		$accepts = $this->response->mapType($accept);
		$preferedTypes = current($accepts);
		if (array_intersect($preferedTypes, array('html', 'xhtml'))) {
			return null;
		}

		$extensions = Router::extensions();
		foreach ($accepts as $types) {
			$ext = array_intersect($extensions, $types);
			if ($ext) {
				$this->ext = current($ext);
				break;
			}
		}
	}

/**
 * The startup method of the RequestHandler enables several automatic behaviors
 * related to the detection of certain properties of the HTTP request, including:
 *
 * - Disabling layout rendering for Ajax requests (based on the HTTP_X_REQUESTED_WITH header)
 * - If Router::parseExtensions() is enabled, the layout and template type are
 *   switched based on the parsed extension or Accept-Type header. For example, if `controller/action.xml`
 *   is requested, the view path becomes `app/View/Controller/xml/action.ctp`. Also if
 *   `controller/action` is requested with `Accept-Type: application/xml` in the headers
 *   the view path will become `app/View/Controller/xml/action.ctp`. Layout and template
 *   types will only switch to mime-types recognized by CakeResponse. If you need to declare
 *   additional mime-types, you can do so using CakeResponse::type() in your controllers beforeFilter()
 *   method.
 * - If a helper with the same name as the extension exists, it is added to the controller.
 * - If the extension is of a type that RequestHandler understands, it will set that
 *   Content-type in the response header.
 * - If the XML data is POSTed, the data is parsed into an XML object, which is assigned
 *   to the $data property of the controller, which can then be saved to a model object.
 *
 * @param Controller $controller A reference to the controller
 * @return void
 */
	public function startup(Controller $controller) {
		$controller->request->params['isAjax'] = $this->request->is('ajax');
		$isRecognized = (
			!in_array($this->ext, array('html', 'htm')) &&
			$this->response->getMimeType($this->ext)
		);

		if (!empty($this->ext) && $isRecognized) {
			$this->renderAs($controller, $this->ext);
		} elseif ($this->request->is('ajax')) {
			$this->renderAs($controller, 'ajax');
		} elseif (empty($this->ext) || in_array($this->ext, array('html', 'htm'))) {
			$this->respondAs('html', array('charset' => Configure::read('App.encoding')));
		}

		foreach ($this->_inputTypeMap as $type => $handler) {
			if ($this->requestedWith($type)) {
				$input = call_user_func_array(array($controller->request, 'input'), $handler);
				$controller->request->data = $input;
			}
		}
	}

/**
 * Helper method to parse xml input data, due to lack of anonymous functions
 * this lives here.
 *
 * @param string $xml XML string.
 * @return array Xml array data
 */
	public function convertXml($xml) {
		try {
			$xml = Xml::build($xml);
			if (isset($xml->data)) {
				return Xml::toArray($xml->data);
			}
			return Xml::toArray($xml);
		} catch (XmlException $e) {
			return array();
		}
	}

/**
 * Handles (fakes) redirects for Ajax requests using requestAction()
 * Modifies the $_POST and $_SERVER['REQUEST_METHOD'] to simulate a new GET request.
 *
 * @param Controller $controller A reference to the controller
 * @param string|array $url A string or array containing the redirect location
 * @param int|array $status HTTP Status for redirect
 * @param bool $exit Whether to exit script, defaults to `true`.
 * @return void
 */
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) {
		if (!$this->request->is('ajax')) {
			return;
		}
		if (empty($url)) {
			return;
		}
		$_SERVER['REQUEST_METHOD'] = 'GET';
		foreach ($_POST as $key => $val) {
			unset($_POST[$key]);
		}
		if (is_array($url)) {
			$url = Router::url($url + array('base' => false));
		}
		if (!empty($status)) {
			$statusCode = $this->response->httpCodes($status);
			$code = key($statusCode);
			$this->response->statusCode($code);
		}
		$this->response->body($this->requestAction($url, array('return', 'bare' => false)));
		$this->response->send();
		$this->_stop();
	}

/**
 * Checks if the response can be considered different according to the request
 * headers, and the caching response headers. If it was not modified, then the
 * render process is skipped. And the client will get a blank response with a
 * "304 Not Modified" header.
 *
 * @param Controller $controller Controller instance.
 * @return bool false if the render process should be aborted
 */
	public function beforeRender(Controller $controller) {
		if ($this->settings['checkHttpCache'] && $this->response->checkNotModified($this->request)) {
			return false;
		}
	}

/**
 * Returns true if the current HTTP request is Ajax, false otherwise
 *
 * @return bool True if call is Ajax
 * @deprecated use `$this->request->is('ajax')` instead.
 */
	public function isAjax() {
		return $this->request->is('ajax');
	}

/**
 * Returns true if the current HTTP request is coming from a Flash-based client
 *
 * @return bool True if call is from Flash
 * @deprecated use `$this->request->is('flash')` instead.
 */
	public function isFlash() {
		return $this->request->is('flash');
	}

/**
 * Returns true if the current request is over HTTPS, false otherwise.
 *
 * @return bool True if call is over HTTPS
 * @deprecated use `$this->request->is('ssl')` instead.
 */
	public function isSSL() {
		return $this->request->is('ssl');
	}

/**
 * Returns true if the current call accepts an XML response, false otherwise
 *
 * @return bool True if client accepts an XML response
 */
	public function isXml() {
		return $this->prefers('xml');
	}

/**
 * Returns true if the current call accepts an RSS response, false otherwise
 *
 * @return bool True if client accepts an RSS response
 */
	public function isRss() {
		return $this->prefers('rss');
	}

/**
 * Returns true if the current call accepts an Atom response, false otherwise
 *
 * @return bool True if client accepts an RSS response
 */
	public function isAtom() {
		return $this->prefers('atom');
	}

/**
 * Returns true if user agent string matches a mobile web browser, or if the
 * client accepts WAP content.
 *
 * @return bool True if user agent is a mobile web browser
 */
	public function isMobile() {
		return $this->request->is('mobile') || $this->accepts('wap');
	}

/**
 * Returns true if the client accepts WAP content
 *
 * @return bool
 */
	public function isWap() {
		return $this->prefers('wap');
	}

/**
 * Returns true if the current call a POST request
 *
 * @return bool True if call is a POST
 * @deprecated Use $this->request->is('post'); from your controller.
 */
	public function isPost() {
		return $this->request->is('post');
	}

/**
 * Returns true if the current call a PUT request
 *
 * @return bool True if call is a PUT
 * @deprecated Use $this->request->is('put'); from your controller.
 */
	public function isPut() {
		return $this->request->is('put');
	}

/**
 * Returns true if the current call a GET request
 *
 * @return bool True if call is a GET
 * @deprecated Use $this->request->is('get'); from your controller.
 */
	public function isGet() {
		return $this->request->is('get');
	}

/**
 * Returns true if the current call a DELETE request
 *
 * @return bool True if call is a DELETE
 * @deprecated Use $this->request->is('delete'); from your controller.
 */
	public function isDelete() {
		return $this->request->is('delete');
	}

/**
 * Gets Prototype version if call is Ajax, otherwise empty string.
 * The Prototype library sets a special "Prototype version" HTTP header.
 *
 * @return string|bool When Ajax the prototype version of component making the call otherwise false
 */
	public function getAjaxVersion() {
		$httpX = env('HTTP_X_PROTOTYPE_VERSION');
		return ($httpX === null) ? false : $httpX;
	}

/**
 * Adds/sets the Content-type(s) for the given name. This method allows
 * content-types to be mapped to friendly aliases (or extensions), which allows
 * RequestHandler to automatically respond to requests of that type in the
 * startup method.
 *
 * @param string $name The name of the Content-type, i.e. "html", "xml", "css"
 * @param string|array $type The Content-type or array of Content-types assigned to the name,
 *    i.e. "text/html", or "application/xml"
 * @return void
 * @deprecated use `$this->response->type()` instead.
 */
	public function setContent($name, $type = null) {
		$this->response->type(array($name => $type));
	}

/**
 * Gets the server name from which this request was referred
 *
 * @return string Server address
 * @deprecated use $this->request->referer() from your controller instead
 */
	public function getReferer() {
		return $this->request->referer(false);
	}

/**
 * Gets remote client IP
 *
 * @param bool $safe Use safe = false when you think the user might manipulate
 *   their HTTP_CLIENT_IP header. Setting $safe = false will also look at HTTP_X_FORWARDED_FOR
 * @return string Client IP address
 * @deprecated use $this->request->clientIp() from your, controller instead.
 */
	public function getClientIP($safe = true) {
		return $this->request->clientIp($safe);
	}

/**
 * Determines which content types the client accepts. Acceptance is based on
 * the file extension parsed by the Router (if present), and by the HTTP_ACCEPT
 * header. Unlike CakeRequest::accepts() this method deals entirely with mapped content types.
 *
 * Usage:
 *
 * `$this->RequestHandler->accepts(array('xml', 'html', 'json'));`
 *
 * Returns true if the client accepts any of the supplied types.
 *
 * `$this->RequestHandler->accepts('xml');`
 *
 * Returns true if the client accepts xml.
 *
 * @param string|array $type Can be null (or no parameter), a string type name, or an
 *   array of types
 * @return mixed If null or no parameter is passed, returns an array of content
 *   types the client accepts. If a string is passed, returns true
 *   if the client accepts it. If an array is passed, returns true
 *   if the client accepts one or more elements in the array.
 * @see RequestHandlerComponent::setContent()
 */
	public function accepts($type = null) {
		$accepted = $this->request->accepts();

		if (!$type) {
			return $this->mapType($accepted);
		}
		if (is_array($type)) {
			foreach ($type as $t) {
				$t = $this->mapAlias($t);
				if (in_array($t, $accepted)) {
					return true;
				}
			}
			return false;
		}
		if (is_string($type)) {
			return in_array($this->mapAlias($type), $accepted);
		}
		return false;
	}

/**
 * Determines the content type of the data the client has sent (i.e. in a POST request)
 *
 * @param string|array $type Can be null (or no parameter), a string type name, or an array of types
 * @return mixed If a single type is supplied a boolean will be returned. If no type is provided
 *   The mapped value of CONTENT_TYPE will be returned. If an array is supplied the first type
 *   in the request content type will be returned.
 */
	public function requestedWith($type = null) {
		if (!$this->request->is('post') && !$this->request->is('put') && !$this->request->is('delete')) {
			return null;
		}
		if (is_array($type)) {
			foreach ($type as $t) {
				if ($this->requestedWith($t)) {
					return $t;
				}
			}
			return false;
		}

		list($contentType) = explode(';', env('CONTENT_TYPE'));
		if ($contentType === '') {
			list($contentType) = explode(';', CakeRequest::header('CONTENT_TYPE'));
		}
		if (!$type) {
			return $this->mapType($contentType);
		}
		if (is_string($type)) {
			return ($type === $this->mapType($contentType));
		}
	}

/**
 * Determines which content-types the client prefers. If no parameters are given,
 * the single content-type that the client most likely prefers is returned. If $type is
 * an array, the first item in the array that the client accepts is returned.
 * Preference is determined primarily by the file extension parsed by the Router
 * if provided, and secondarily by the list of content-types provided in
 * HTTP_ACCEPT.
 *
 * @param string|array $type An optional array of 'friendly' content-type names, i.e.
 *   'html', 'xml', 'js', etc.
 * @return mixed If $type is null or not provided, the first content-type in the
 *    list, based on preference, is returned. If a single type is provided
 *    a boolean will be returned if that type is preferred.
 *    If an array of types are provided then the first preferred type is returned.
 *    If no type is provided the first preferred type is returned.
 * @see RequestHandlerComponent::setContent()
 */
	public function prefers($type = null) {
		$acceptRaw = $this->request->parseAccept();

		if (empty($acceptRaw)) {
			return $this->ext;
		}
		$accepts = $this->mapType(array_shift($acceptRaw));

		if (!$type) {
			if (empty($this->ext) && !empty($accepts)) {
				return $accepts[0];
			}
			return $this->ext;
		}

		$types = (array)$type;

		if (count($types) === 1) {
			if (!empty($this->ext)) {
				return in_array($this->ext, $types);
			}
			return in_array($types[0], $accepts);
		}

		$intersect = array_values(array_intersect($accepts, $types));
		if (empty($intersect)) {
			return false;
		}
		return $intersect[0];
	}

/**
 * Sets the layout and template paths for the content type defined by $type.
 *
 * ### Usage:
 *
 * Render the response as an 'ajax' response.
 *
 * `$this->RequestHandler->renderAs($this, 'ajax');`
 *
 * Render the response as an xml file and force the result as a file download.
 *
 * `$this->RequestHandler->renderAs($this, 'xml', array('attachment' => 'myfile.xml');`
 *
 * @param Controller $controller A reference to a controller object
 * @param string $type Type of response to send (e.g: 'ajax')
 * @param array $options Array of options to use
 * @return void
 * @see RequestHandlerComponent::setContent()
 * @see RequestHandlerComponent::respondAs()
 */
	public function renderAs(Controller $controller, $type, $options = array()) {
		$defaults = array('charset' => 'UTF-8');

		if (Configure::read('App.encoding') !== null) {
			$defaults['charset'] = Configure::read('App.encoding');
		}
		$options += $defaults;

		if ($type === 'ajax') {
			$controller->layout = $this->ajaxLayout;
			return $this->respondAs('html', $options);
		}

		$pluginDot = null;
		$viewClassMap = $this->viewClassMap();
		if (array_key_exists($type, $viewClassMap)) {
			list($pluginDot, $viewClass) = pluginSplit($viewClassMap[$type], true);
		} else {
			$viewClass = Inflector::classify($type);
		}
		$viewName = $viewClass . 'View';
		if (!class_exists($viewName)) {
			App::uses($viewName, $pluginDot . 'View');
		}
		if (class_exists($viewName)) {
			$controller->viewClass = $viewClass;
		} elseif (empty($this->_renderType)) {
			$controller->viewPath .= DS . $type;
		} else {
			$controller->viewPath = preg_replace(
				"/([\/\\\\]{$this->_renderType})$/",
				DS . $type,
				$controller->viewPath
			);
		}
		$this->_renderType = $type;
		$controller->layoutPath = $type;

		if ($this->response->getMimeType($type)) {
			$this->respondAs($type, $options);
		}

		$helper = ucfirst($type);

		if (!in_array($helper, $controller->helpers) && empty($controller->helpers[$helper])) {
			App::uses('AppHelper', 'View/Helper');
			App::uses($helper . 'Helper', 'View/Helper');
			if (class_exists($helper . 'Helper')) {
				$controller->helpers[] = $helper;
			}
		}
	}

/**
 * Sets the response header based on type map index name. This wraps several methods
 * available on CakeResponse. It also allows you to use Content-Type aliases.
 *
 * @param string|array $type Friendly type name, i.e. 'html' or 'xml', or a full content-type,
 *    like 'application/x-shockwave'.
 * @param array $options If $type is a friendly type name that is associated with
 *    more than one type of content, $index is used to select which content-type to use.
 * @return bool Returns false if the friendly type name given in $type does
 *    not exist in the type map, or if the Content-type header has
 *    already been set by this method.
 * @see RequestHandlerComponent::setContent()
 */
	public function respondAs($type, $options = array()) {
		$defaults = array('index' => null, 'charset' => null, 'attachment' => false);
		$options = $options + $defaults;

		$cType = $type;
		if (strpos($type, '/') === false) {
			$cType = $this->response->getMimeType($type);
		}
		if (is_array($cType)) {
			if (isset($cType[$options['index']])) {
				$cType = $cType[$options['index']];
			}

			if ($this->prefers($cType)) {
				$cType = $this->prefers($cType);
			} else {
				$cType = $cType[0];
			}
		}

		if (!$type) {
			return false;
		}
		if (empty($this->request->params['requested'])) {
			$this->response->type($cType);
		}
		if (!empty($options['charset'])) {
			$this->response->charset($options['charset']);
		}
		if (!empty($options['attachment'])) {
			$this->response->download($options['attachment']);
		}
		return true;
	}

/**
 * Returns the current response type (Content-type header), or null if not alias exists
 *
 * @return mixed A string content type alias, or raw content type if no alias map exists,
 *	otherwise null
 */
	public function responseType() {
		return $this->mapType($this->response->type());
	}

/**
 * Maps a content-type back to an alias
 *
 * @param string|array $cType Either a string content type to map, or an array of types.
 * @return string|array Aliases for the types provided.
 * @deprecated Use $this->response->mapType() in your controller instead.
 */
	public function mapType($cType) {
		return $this->response->mapType($cType);
	}

/**
 * Maps a content type alias back to its mime-type(s)
 *
 * @param string|array $alias String alias to convert back into a content type. Or an array of aliases to map.
 * @return string Null on an undefined alias. String value of the mapped alias type. If an
 *   alias maps to more than one content type, the first one will be returned.
 */
	public function mapAlias($alias) {
		if (is_array($alias)) {
			return array_map(array($this, 'mapAlias'), $alias);
		}
		$type = $this->response->getMimeType($alias);
		if ($type) {
			if (is_array($type)) {
				return $type[0];
			}
			return $type;
		}
		return null;
	}

/**
 * Add a new mapped input type. Mapped input types are automatically
 * converted by RequestHandlerComponent during the startup() callback.
 *
 * @param string $type The type alias being converted, ie. json
 * @param array $handler The handler array for the type. The first index should
 *    be the handling callback, all other arguments should be additional parameters
 *    for the handler.
 * @return void
 * @throws CakeException
 */
	public function addInputType($type, $handler) {
		if (!is_array($handler) || !isset($handler[0]) || !is_callable($handler[0])) {
			throw new CakeException(__d('cake_dev', 'You must give a handler callback.'));
		}
		$this->_inputTypeMap[$type] = $handler;
	}

/**
 * Getter/setter for viewClassMap
 *
 * @param array|string $type The type string or array with format `array('type' => 'viewClass')` to map one or more
 * @param array $viewClass The viewClass to be used for the type without `View` appended
 * @return array|string Returns viewClass when only string $type is set, else array with viewClassMap
 */
	public function viewClassMap($type = null, $viewClass = null) {
		if (!$viewClass && is_string($type) && isset($this->_viewClassMap[$type])) {
			return $this->_viewClassMap[$type];
		}
		if (is_string($type)) {
			$this->_viewClassMap[$type] = $viewClass;
		} elseif (is_array($type)) {
			foreach ($type as $key => $value) {
				$this->viewClassMap($key, $value);
			}
		}
		return $this->_viewClassMap;
	}

}
