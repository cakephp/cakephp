<?php
/**
 * Request object for handling alternative HTTP requests
 *
 * Alternative HTTP requests can come from wireless units like mobile phones, palmtop computers,
 * and the like.  These units have no use for Ajax requests, and this Component can tell how Cake
 * should respond to the different needs of a handheld computer and a desktop machine.
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
 * @subpackage    cake.cake.libs.controller.components
 * @since         CakePHP(tm) v 0.10.4.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Request object for handling HTTP requests
 *
 * @package       cake
 * @subpackage    cake.cake.libs.controller.components
 * @link http://book.cakephp.org/view/1291/Request-Handling
 *
 */
class RequestHandlerComponent extends Object {

/**
 * The layout that will be switched to for Ajax requests
 *
 * @var string
 * @access public
 * @see RequestHandler::setAjax()
 */
	var $ajaxLayout = 'ajax';

/**
 * Determines whether or not callbacks will be fired on this component
 *
 * @var boolean
 * @access public
 */
	var $enabled = true;

/**
 * Holds the content-type of the response that is set when using
 * RequestHandler::respondAs()
 *
 * @var string
 * @access private
 */
	var $__responseTypeSet = null;

/**
 * Holds the copy of Controller::$params
 *
 * @var array
 * @access public
 */
	var $params = array();

/**
 * Friendly content-type mappings used to set response types and determine
 * request types.  Can be modified with RequestHandler::setContent()
 *
 * @var array
 * @access private
 * @see RequestHandlerComponent::setContent
 */
	var $__requestContent = array(
		'javascript'	=> 'text/javascript',
		'js'			=> 'text/javascript',
		'json'			=> 'application/json',
		'css'			=> 'text/css',
		'html'			=> array('text/html', '*/*'),
		'text'			=> 'text/plain',
		'txt'			=> 'text/plain',
		'csv'			=> array('application/vnd.ms-excel', 'text/plain'),
		'form'			=> 'application/x-www-form-urlencoded',
		'file'			=> 'multipart/form-data',
		'xhtml'			=> array('application/xhtml+xml', 'application/xhtml', 'text/xhtml'),
		'xhtml-mobile'	=> 'application/vnd.wap.xhtml+xml',
		'xml'			=> array('application/xml', 'text/xml'),
		'rss'			=> 'application/rss+xml',
		'atom'			=> 'application/atom+xml',
		'amf'			=> 'application/x-amf',
		'wap'			=> array(
			'text/vnd.wap.wml',
			'text/vnd.wap.wmlscript',
			'image/vnd.wap.wbmp'
		),
		'wml'			=> 'text/vnd.wap.wml',
		'wmlscript'		=> 'text/vnd.wap.wmlscript',
		'wbmp'			=> 'image/vnd.wap.wbmp',
		'pdf'			=> 'application/pdf',
		'zip'			=> 'application/x-zip',
		'tar'			=> 'application/x-tar'
	);

/**
 * List of regular expressions for matching mobile device's user agent string
 *
 * @var array
 * @access public
 */
	var $mobileUA = array(
		'Android',
		'AvantGo',
		'BlackBerry',
		'DoCoMo',
		'iPod',
		'iPhone',
		'J2ME',
		'MIDP',
		'NetFront',
		'Nokia',
		'Opera Mini',
		'PalmOS',
		'PalmSource',
		'portalmmm',
		'Plucker',
		'ReqwirelessWeb',
		'SonyEricsson',
		'Symbian',
		'UP\.Browser',
		'webOS',
		'Windows CE',
		'Xiino'
	);

/**
 * Content-types accepted by the client.  If extension parsing is enabled in the
 * Router, and an extension is detected, the corresponding content-type will be
 * used as the overriding primary content-type accepted.
 *
 * @var array
 * @access private
 * @see Router::parseExtensions()
 */
	var $__acceptTypes = array();

/**
 * The template to use when rendering the given content type.
 *
 * @var string
 * @access private
 */
	var $__renderType = null;

/**
 * Contains the file extension parsed out by the Router
 *
 * @var string
 * @access public
 * @see Router::parseExtensions()
 */
	var $ext = null;

/**
 * Flag set when MIME types have been initialized
 *
 * @var boolean
 * @access private
 * @see RequestHandler::__initializeTypes()
 */
	var $__typesInitialized = false;

/**
 * Constructor. Parses the accepted content types accepted by the client using HTTP_ACCEPT
 *
 */
	function __construct() {
		$this->__acceptTypes = explode(',', env('HTTP_ACCEPT'));

		foreach ($this->__acceptTypes as $i => $type) {
			if (strpos($type, ';')) {
				$type = explode(';', $type);
				$this->__acceptTypes[$i] = $type[0];
			}
		}
		parent::__construct();
	}

/**
 * Initializes the component, gets a reference to Controller::$parameters, and
 * checks to see if a file extension has been parsed by the Router.  If yes, the
 * corresponding content-type is pushed onto the list of accepted content-types
 * as the first item.
 *
 * @param object $controller A reference to the controller
 * @param array $settings Array of settings to _set().
 * @return void
 * @see Router::parseExtensions()
 * @access public
 */
	function initialize(&$controller, $settings = array()) {
		if (isset($controller->params['url']['ext'])) {
			$this->ext = $controller->params['url']['ext'];
		}
		$this->_set($settings);
	}

/**
 * The startup method of the RequestHandler enables several automatic behaviors
 * related to the detection of certain properties of the HTTP request, including:
 *
 * - Disabling layout rendering for Ajax requests (based on the HTTP_X_REQUESTED_WITH header)
 * - If Router::parseExtensions() is enabled, the layout and template type are
 *   switched based on the parsed extension.  For example, if controller/action.xml
 *   is requested, the view path becomes <i>app/views/controller/xml/action.ctp</i>.
 * - If a helper with the same name as the extension exists, it is added to the controller.
 * - If the extension is of a type that RequestHandler understands, it will set that
 *   Content-type in the response header.
 * - If the XML data is POSTed, the data is parsed into an XML object, which is assigned
 *   to the $data property of the controller, which can then be saved to a model object.
 *
 * @param object $controller A reference to the controller
 * @return void
 * @access public
 */
	function startup(&$controller) {
		if (!$this->enabled) {
			return;
		}

		$this->__initializeTypes();
		$controller->params['isAjax'] = $this->isAjax();
		$isRecognized = (
			!in_array($this->ext, array('html', 'htm')) &&
			in_array($this->ext, array_keys($this->__requestContent))
		);

		if (!empty($this->ext) && $isRecognized) {
			$this->renderAs($controller, $this->ext);
		} elseif ($this->isAjax()) {
			$this->renderAs($controller, 'ajax');
		}

		if ($this->requestedWith('xml')) {
			if (!class_exists('XmlNode')) {
				App::import('Core', 'Xml');
			}
			$xml = new Xml(trim(file_get_contents('php://input')));

			if (count($xml->children) == 1 && is_object($dataNode = $xml->child('data'))) {
				$controller->data = $dataNode->toArray();
			} else {
				$controller->data = $xml->toArray();
			}
		}
	}

/**
 * Handles (fakes) redirects for Ajax requests using requestAction()
 *
 * @param object $controller A reference to the controller
 * @param mixed $url A string or array containing the redirect location
 * @access public
 */
	function beforeRedirect(&$controller, $url) {
		if (!$this->isAjax()) {
			return;
		}
		foreach ($_POST as $key => $val) {
			unset($_POST[$key]);
		}
		if (is_array($url)) {
			$url = Router::url($url + array('base' => false));
		}
		echo $this->requestAction($url, array('return'));
		$this->_stop();
	}

/**
 * Returns true if the current HTTP request is Ajax, false otherwise
 *
 * @return boolean True if call is Ajax
 * @access public
 */
	function isAjax() {
		return env('HTTP_X_REQUESTED_WITH') === "XMLHttpRequest";
	}

/**
 * Returns true if the current HTTP request is coming from a Flash-based client
 *
 * @return boolean True if call is from Flash
 * @access public
 */
	function isFlash() {
		return (preg_match('/^(Shockwave|Adobe) Flash/', env('HTTP_USER_AGENT')) == 1);
	}

/**
 * Returns true if the current request is over HTTPS, false otherwise.
 *
 * @return bool True if call is over HTTPS
 * @access public
 */
	function isSSL() {
		return env('HTTPS');
	}

/**
 * Returns true if the current call accepts an XML response, false otherwise
 *
 * @return boolean True if client accepts an XML response
 * @access public
 */
	function isXml() {
		return $this->prefers('xml');
	}

/**
 * Returns true if the current call accepts an RSS response, false otherwise
 *
 * @return boolean True if client accepts an RSS response
 * @access public
 */
	function isRss() {
		return $this->prefers('rss');
	}

/**
 * Returns true if the current call accepts an Atom response, false otherwise
 *
 * @return boolean True if client accepts an RSS response
 * @access public
 */
	function isAtom() {
		return $this->prefers('atom');
	}

/**
 * Returns true if user agent string matches a mobile web browser, or if the
 * client accepts WAP content.
 *
 * @return boolean True if user agent is a mobile web browser
 * @access public
 * @deprecated Use of constant REQUEST_MOBILE_UA is deprecated and will be removed in future versions
 */
	function isMobile() {
		if (defined('REQUEST_MOBILE_UA')) {
			$regex = '/' . REQUEST_MOBILE_UA . '/i';
		} else {
			$regex = '/' . implode('|', $this->mobileUA) . '/i';
		}

		if (preg_match($regex, env('HTTP_USER_AGENT')) || $this->accepts('wap')) {
			return true;
		}
		return false;
	}

/**
 * Returns true if the client accepts WAP content
 *
 * @return bool
 * @access public
 */
	function isWap() {
		return $this->prefers('wap');
	}

/**
 * Returns true if the current call a POST request
 *
 * @return boolean True if call is a POST
 * @access public
 */
	function isPost() {
		return (strtolower(env('REQUEST_METHOD')) == 'post');
	}

/**
 * Returns true if the current call a PUT request
 *
 * @return boolean True if call is a PUT
 * @access public
 */
	function isPut() {
		return (strtolower(env('REQUEST_METHOD')) == 'put');
	}

/**
 * Returns true if the current call a GET request
 *
 * @return boolean True if call is a GET
 * @access public
 */
	function isGet() {
		return (strtolower(env('REQUEST_METHOD')) == 'get');
	}

/**
 * Returns true if the current call a DELETE request
 *
 * @return boolean True if call is a DELETE
 * @access public
 */
	function isDelete() {
		return (strtolower(env('REQUEST_METHOD')) == 'delete');
	}

/**
 * Gets Prototype version if call is Ajax, otherwise empty string.
 * The Prototype library sets a special "Prototype version" HTTP header.
 *
 * @return string Prototype version of component making Ajax call
 * @access public
 */
	function getAjaxVersion() {
		if (env('HTTP_X_PROTOTYPE_VERSION') != null) {
			return env('HTTP_X_PROTOTYPE_VERSION');
		}
		return false;
	}

/**
 * Adds/sets the Content-type(s) for the given name.  This method allows
 * content-types to be mapped to friendly aliases (or extensions), which allows
 * RequestHandler to automatically respond to requests of that type in the
 * startup method.
 *
 * @param string $name The name of the Content-type, i.e. "html", "xml", "css"
 * @param mixed $type The Content-type or array of Content-types assigned to the name,
 *    i.e. "text/html", or "application/xml"
 * @return void
 * @access public
 */
	function setContent($name, $type = null) {
		if (is_array($name)) {
			$this->__requestContent = array_merge($this->__requestContent, $name);
			return;
		}
		$this->__requestContent[$name] = $type;
	}

/**
 * Gets the server name from which this request was referred
 *
 * @return string Server address
 * @access public
 */
	function getReferer() {
		if (env('HTTP_HOST') != null) {
			$sessHost = env('HTTP_HOST');
		}

		if (env('HTTP_X_FORWARDED_HOST') != null) {
			$sessHost = env('HTTP_X_FORWARDED_HOST');
		}
		return trim(preg_replace('/(?:\:.*)/', '', $sessHost));
	}

/**
 * Gets remote client IP
 *
 * @return string Client IP address
 * @access public
 */
	function getClientIP($safe = true) {
		if (!$safe && env('HTTP_X_FORWARDED_FOR') != null) {
			$ipaddr = preg_replace('/(?:,.*)/', '', env('HTTP_X_FORWARDED_FOR'));
		} else {
			if (env('HTTP_CLIENT_IP') != null) {
				$ipaddr = env('HTTP_CLIENT_IP');
			} else {
				$ipaddr = env('REMOTE_ADDR');
			}
		}

		if (env('HTTP_CLIENTADDRESS') != null) {
			$tmpipaddr = env('HTTP_CLIENTADDRESS');

			if (!empty($tmpipaddr)) {
				$ipaddr = preg_replace('/(?:,.*)/', '', $tmpipaddr);
			}
		}
		return trim($ipaddr);
	}

/**
 * Determines which content types the client accepts.  Acceptance is based on
 * the file extension parsed by the Router (if present), and by the HTTP_ACCEPT
 * header.
 *
 * @param mixed $type Can be null (or no parameter), a string type name, or an
 *   array of types
 * @return mixed If null or no parameter is passed, returns an array of content
 *   types the client accepts.  If a string is passed, returns true
 *   if the client accepts it.  If an array is passed, returns true
 *   if the client accepts one or more elements in the array.
 * @access public
 * @see RequestHandlerComponent::setContent()
 */
	function accepts($type = null) {
		$this->__initializeTypes();

		if ($type == null) {
			return $this->mapType($this->__acceptTypes);

		} elseif (is_array($type)) {
			foreach ($type as $t) {
				if ($this->accepts($t) == true) {
					return true;
				}
			}
			return false;
		} elseif (is_string($type)) {

			if (!isset($this->__requestContent[$type])) {
				return false;
			}

			$content = $this->__requestContent[$type];

			if (is_array($content)) {
				foreach ($content as $c) {
					if (in_array($c, $this->__acceptTypes)) {
						return true;
					}
				}
			} else {
				if (in_array($content, $this->__acceptTypes)) {
					return true;
				}
			}
		}
	}

/**
 * Determines the content type of the data the client has sent (i.e. in a POST request)
 *
 * @param mixed $type Can be null (or no parameter), a string type name, or an array of types
 * @return mixed
 * @access public
 */
	function requestedWith($type = null) {
		if (!$this->isPost() && !$this->isPut()) {
			return null;
		}

		list($contentType) = explode(';', env('CONTENT_TYPE'));
		if ($type == null) {
			return $this->mapType($contentType);
		} elseif (is_array($type)) {
			foreach ($type as $t) {
				if ($this->requestedWith($t)) {
					return $this->mapType($t);
				}
			}
			return false;
		} elseif (is_string($type)) {
			return ($type == $this->mapType($contentType));
		}
	}

/**
 * Determines which content-types the client prefers.  If no parameters are given,
 * the content-type that the client most likely prefers is returned.  If $type is
 * an array, the first item in the array that the client accepts is returned.
 * Preference is determined primarily by the file extension parsed by the Router
 * if provided, and secondarily by the list of content-types provided in
 * HTTP_ACCEPT.
 *
 * @param mixed $type An optional array of 'friendly' content-type names, i.e.
 *   'html', 'xml', 'js', etc.
 * @return mixed If $type is null or not provided, the first content-type in the
 *    list, based on preference, is returned.
 * @access public
 * @see RequestHandlerComponent::setContent()
 */
	function prefers($type = null) {
		$this->__initializeTypes();
		$accept = $this->accepts();

		if ($type == null) {
			if (empty($this->ext)) {
				if (is_array($accept)) {
					return $accept[0];
				}
				return $accept;
			}
			return $this->ext;
		}

		$types = $type;
		if (is_string($type)) {
			$types = array($type);
		}

		if (count($types) === 1) {
			if (!empty($this->ext)) {
				return ($types[0] == $this->ext);
			}
			return ($types[0] == $accept[0]);
		}
		$accepts = array();

		foreach ($types as $type) {
			if (in_array($type, $accept)) {
				$accepts[] = $type;
			}
		}

		if (count($accepts) === 0) {
			return false;
		} elseif (count($types) === 1) {
			return ($types[0] === $accepts[0]);
		} elseif (count($accepts) === 1) {
			return $accepts[0];
		}

		$acceptedTypes = array();
		foreach ($this->__acceptTypes as $type) {
			$acceptedTypes[] = $this->mapType($type);
		}
		$accepts = array_intersect($acceptedTypes, $accepts);
		return $accepts[0];
	}

/**
 * Sets the layout and template paths for the content type defined by $type.
 *
 * @param object $controller A reference to a controller object
 * @param string $type Type of response to send (e.g: 'ajax')
 * @return void
 * @access public
 * @see RequestHandlerComponent::setContent()
 * @see RequestHandlerComponent::respondAs()
 */
	function renderAs(&$controller, $type) {
		$this->__initializeTypes();
		$options = array('charset' => 'UTF-8');

		if (Configure::read('App.encoding') !== null) {
			$options = array('charset' => Configure::read('App.encoding'));
		}

		if ($type == 'ajax') {
			$controller->layout = $this->ajaxLayout;
			return $this->respondAs('html', $options);
		}
		$controller->ext = '.ctp';

		if (empty($this->__renderType)) {
			$controller->viewPath .= DS . $type;
		} else {
			$remove = preg_replace("/([\/\\\\]{$this->__renderType})$/", DS . $type, $controller->viewPath);
			$controller->viewPath = $remove;
		}
		$this->__renderType = $type;
		$controller->layoutPath = $type;

		if (isset($this->__requestContent[$type])) {
			$this->respondAs($type, $options);
		}

		$helper = ucfirst($type);
		$isAdded = (
			in_array($helper, $controller->helpers) ||
			array_key_exists($helper, $controller->helpers)
		);

		if (!$isAdded) {
			if (App::import('Helper', $helper)) {
				$controller->helpers[] = $helper;
			}
		}
	}

/**
 * Sets the response header based on type map index name.  If DEBUG is greater than 2, the header
 * is not set.
 *
 * @param mixed $type Friendly type name, i.e. 'html' or 'xml', or a full content-type,
 *    like 'application/x-shockwave'.
 * @param array $options If $type is a friendly type name that is associated with
 *    more than one type of content, $index is used to select which content-type to use.
 *
 * @return boolean Returns false if the friendly type name given in $type does
 *    not exist in the type map, or if the Content-type header has
 *    already been set by this method.
 * @access public
 * @see RequestHandlerComponent::setContent()
 */
	function respondAs($type, $options = array()) {
		$this->__initializeTypes();
		if ($this->__responseTypeSet != null) {
			return false;
		}
		if (!array_key_exists($type, $this->__requestContent) && strpos($type, '/') === false) {
			return false;
		}
		$defaults = array('index' => 0, 'charset' => null, 'attachment' => false);
		$options = array_merge($defaults, $options);

		if (strpos($type, '/') === false && isset($this->__requestContent[$type])) {
			$cType = null;
			if (is_array($this->__requestContent[$type]) && isset($this->__requestContent[$type][$options['index']])) {
				$cType = $this->__requestContent[$type][$options['index']];
			} elseif (is_array($this->__requestContent[$type]) && isset($this->__requestContent[$type][0])) {
				$cType = $this->__requestContent[$type][0];
			} elseif (isset($this->__requestContent[$type])) {
				$cType = $this->__requestContent[$type];
			} else {
				return false;
			}

			if (is_array($cType)) {
				if ($this->prefers($cType)) {
					$cType = $this->prefers($cType);
				} else {
					$cType = $cType[0];
				}
			}
		} else {
			$cType = $type;
		}

		if ($cType != null) {
			$header = 'Content-type: ' . $cType;

			if (!empty($options['charset'])) {
				$header .= '; charset=' . $options['charset'];
			}
			if (!empty($options['attachment'])) {
				header("Content-Disposition: attachment; filename=\"{$options['attachment']}\"");
			}
			if (Configure::read() < 2 && !defined('CAKEPHP_SHELL')) {
				@header($header);
			}
			$this->__responseTypeSet = $cType;
			return true;
		}
		return false;
	}

/**
 * Returns the current response type (Content-type header), or null if none has been set
 *
 * @return mixed A string content type alias, or raw content type if no alias map exists,
 *    otherwise null
 * @access public
 */
	function responseType() {
		if ($this->__responseTypeSet == null) {
			return null;
		}
		return $this->mapType($this->__responseTypeSet);
	}

/**
 * Maps a content-type back to an alias
 *
 * @param mixed $type Content type
 * @return mixed Alias
 * @access public
 */
	function mapType($ctype) {
		if (is_array($ctype)) {
			$out = array();
			foreach ($ctype as $t) {
				$out[] = $this->mapType($t);
			}
			return $out;
		} else {
			$keys = array_keys($this->__requestContent);
			$count = count($keys);

			for ($i = 0; $i < $count; $i++) {
				$name = $keys[$i];
				$type = $this->__requestContent[$name];

				if (is_array($type) && in_array($ctype, $type)) {
					return $name;
				} elseif (!is_array($type) && $type == $ctype) {
					return $name;
				}
			}
			return $ctype;
		}
	}

/**
 * Initializes MIME types
 *
 * @return void
 * @access private
 */
	function __initializeTypes() {
		if ($this->__typesInitialized) {
			return;
		}
		if (isset($this->__requestContent[$this->ext])) {
			$content = $this->__requestContent[$this->ext];
			if (is_array($content)) {
				$content = $content[0];
			}
			array_unshift($this->__acceptTypes, $content);
		}
		$this->__typesInitialized = true;
	}
}

?>