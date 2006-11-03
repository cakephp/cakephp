<?php
/* SVN FILE: $Id$ */
/**
 * Request object for handling alternative HTTP requests
 *
 * Alternative HTTP requests can come from wireless units like mobile phones, palmtop computers, and the like.
 * These units have no use for Ajax requests, and this Component can tell how Cake should respond to the different
 * needs of a handheld computer and a desktop machine.
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
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
 * @subpackage		cake.cake.libs.controller.components
 * @since			CakePHP v 0.10.4.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

if (!defined('REQUEST_MOBILE_UA')) {
	define('REQUEST_MOBILE_UA', '(MIDP|AvantGo|BlackBerry|J2ME|Opera Mini|DoCoMo|NetFront|Nokia|PalmOS|PalmSource|portalmmm|Plucker|ReqwirelessWeb|SonyEricsson|Symbian|UP\.Browser|Windows CE|Xiino)');
}

/**
 * Request object for handling HTTP requests
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller.components
 *
 */
class RequestHandlerComponent extends Object {

	var $ajaxLayout = 'ajax';

	var $disableStartup = false;

	var $enabled = true;

	var $__responseTypeSet = null;

	var $params = array();

	var $__requestContent = array(
		'javascript'	=> 'text/javascript',
		'js'			=> 'text/javascript',
		'css'			=> 'text/css',
		'html'			=> array('text/html', '*/*'),
		'text'			=> 'text/plain',
		'txt'			=> 'text/plain',
		'form'			=> 'application/x-www-form-urlencoded',
		'file'			=> 'multipart/form-data',
		'xhtml'			=> array('application/xhtml+xml', 'application/xhtml', 'text/xhtml'),
		'xhtml-mobile'	=> 'application/vnd.wap.xhtml+xml',
		'xml'			=> array('application/xml', 'text/xml'),
		'rss'			=> 'application/rss+xml',
		'atom'			=> 'application/atom+xml',
		'amf'			=> 'application/x-amf',
		'wap'			=> array('text/vnd.wap.wml', 'text/vnd.wap.wmlscript', 'image/vnd.wap.wbmp'),
		'wml'			=> 'text/vnd.wap.wml',
		'wmlscript'		=> 'text/vnd.wap.wmlscript',
		'wbmp'			=> 'image/vnd.wap.wbmp',
		'pdf'			=> 'application/pdf',
		'zip'			=> 'application/x-zip',
		'tar'			=> 'application/x-tar'
	);

	var $__acceptTypes = array();

	function __construct() {
		$this->__acceptTypes = explode(',', env('HTTP_ACCEPT'));

		foreach($this->__acceptTypes as $i => $type) {
			if (strpos($type, ';')) {
				$type = explode(';', $type);
				$this->__acceptTypes[$i] = $type[0];
			}
		}

		foreach ($this->__requestContent as $type => $data) {
			$this->setContent($type, $data);
		}

		parent::__construct();
	}
/**
 * Initializes the component, gets a reference to Controller::$parameters, and
 * checks to see if a file extension has been parsed by the Router.  If yes, the
 * corresponding content-type is pushed onto the list of accepted content-types
 * as the first item.
 *
 * @param object A reference to the controller
 * @return void
 * @see Router::parseExtensions()
 */
	function initialize(&$controller) {
		$this->params =& $controller->params;
		if (isset($this->params['url']['ext'])) {
			$ext = $this->params['url']['ext'];
			if (isset($this->__requestContent[$ext])) {
				$content = $this->__requestContent[$ext]['content'];
				if (is_array($content)) {
					$content = $content[0];
				}
				array_unshift($this->__acceptTypes, $content);
			}
		}
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
 * @param object A reference to the controller
 * @return void
 */
	function startup(&$controller) {

		if ($this->disableStartup || !$this->enabled) {
			return;
		}

		$this->setView($controller);
		$controller->params['isAjax'] = $this->isAjax();

		if (isset($this->params['url']['ext']) && !empty($this->params['url']['ext'])) {
			$ext = $this->params['url']['ext'];
			if (in_array($ext, array_keys($this->__requestContent)) && !in_array($ext, array('html', 'htm'))) {

				$controller->ext = '.ctp';
				$controller->viewPath .= '/' . $ext;
				$controller->layoutPath = $ext;

				if (in_array($ext, array_keys($this->__requestContent))) {
					$this->respondAs($ext);
				}

				if (!in_array(ucfirst($ext), $controller->helpers)) {
					if (file_exists(HELPERS . $ext . '.php') || fileExistsInPath(LIBS . 'view' . DS . 'helpers' . DS . $ext . '.php')) {
						$controller->helpers[] = ucfirst($ext);
					}
				}
			}
		}

		if ($this->requestedWith('xml')) {
			if (!class_exists('xmlnode') && !class_exists('XMLNode')) {
				uses('xml');
			}
			$controller->data = new XML(trim(file_get_contents('php://input')));
		}
	}
/**
 * Sets a controller's layout/View class based on request headers
 *
 * @param object The controller object
 * @return null
 */
	function setView(&$controller) {
		if ($this->setAjax($controller)) {
			return;
		}
	}
/**
 * Sets a controller's layout based on whether or not the current call is Ajax.
 * Also sets the Content-type to html with UTF-8 encoding for IE6/XPsp2 bug.
 *
 * @param object The controller object
 * @return boolean True if call is Ajax, otherwise false
 */
	function setAjax(&$controller) {
		if ($this->isAjax()) {
			$controller->layout = $this->ajaxLayout;

			// Add UTF-8 header for IE6 on XPsp2 bug
			return $this->respondAs('html', array('charset' => 'UTF-8'));
		}
		return false;
	}
/**
 * Returns true if the current HTTP request is Ajax, false otherwise
 *
 * @return bool True if call is Ajax
 */
	function isAjax() {
		return env('HTTP_X_REQUESTED_WITH') === "XMLHttpRequest";
	}
/**
 * Returns true if the current request is over HTTPS, false otherwise.
 *
 * @return bool
 */
	function isSSL() {
		return env('HTTPS');
	}
/**
 * Returns true if the current call accepts an XML response, false otherwise
 *
 * @return bool True if client accepts an XML response
 */
	function isXml() {
		return $this->prefers('xml');
	}
/**
 * Returns true if the current call accepts an RSS response, false otherwise
 *
 * @return bool True if client accepts an RSS response
 */
	function isRss() {
		return $this->prefers('rss');
	}
/**
 * Returns true if the current call accepts an Atom response, false otherwise
 *
 * @return bool True if client accepts an RSS response
 */
	function isAtom() {
		return $this->prefers('atom');
	}
/**
 * Returns true if user agent string matches a mobile web browser, or if the
 * client accepts WAP content.
 *
 * @return bool True if user agent is a mobile web browser
 */
	function isMobile() {
		return (preg_match('/' . REQUEST_MOBILE_UA . '/i', env('HTTP_USER_AGENT')) > 0 || $this->accepts('wap'));
	}
/**
 * Returns true if the client accepts WAP content
 *
 * @return bool
 */
	function isWap() {
		return $this->prefers('wap');
	}
/**
 * Returns true if the current call a POST request
 *
 * @return bool True if call is a POST
 */
	function isPost() {
		return (strtolower(env('REQUEST_METHOD')) == 'post');
	}
/**
 * Returns true if the current call a PUT request
 *
 * @return bool True if call is a PUT
 */
	function isPut() {
		return (strtolower(env('REQUEST_METHOD')) == 'put');
	}
/**
 * Returns true if the current call a GET request
 *
 * @return bool True if call is a GET
 */
	function isGet() {
		return (strtolower(env('REQUEST_METHOD')) == 'get');
	}
/**
 * Returns true if the current call a DELETE request
 *
 * @return bool True if call is a DELETE
 */
	function isDelete() {
		return (strtolower(env('REQUEST_METHOD')) == 'delete');
	}
/**
 * Gets Prototype version if call is Ajax, otherwise empty string.
 * The Prototype library sets a special "Prototype version" HTTP header.
 *
 * @return string Prototype version of component making Ajax call
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
 *                    i.e. "text/html", or "application/xml"
 * @return void
 */
	function setContent($name, $type) {
		if (!is_array($type) || isset($type[0])) {
			$type = array(
				'layout'	=> Inflector::underscore($name),
				'view'		=> Inflector::camelize($name),
				'content'	=> $type
			);
		}
		$this->__requestContent[$name] = $type;
	}
/**
 * Gets the server name from which this request was referred
 *
 * @return string Server address
 */
	function getReferrer() {
		if (env('HTTP_HOST') != null) {
			$sess_host = env('HTTP_HOST');
		}

		if (env('HTTP_X_FORWARDED_HOST') != null) {
			$sess_host = env('HTTP_X_FORWARDED_HOST');
		}
		return trim(preg_replace('/:.*/', '', $sess_host));
	}
/**
 * Gets remote client IP
 *
 * @return string Client IP address
 */
	function getClientIP() {
		if (env('HTTP_X_FORWARDED_FOR') != null) {
			$ipaddr = preg_replace('/,.*/', '', env('HTTP_X_FORWARDED_FOR'));
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
				$ipaddr = preg_replace('/,.*/', '', $tmpipaddr);
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
 *					array of types
 * @returns mixed If null or no parameter is passed, returns an array of content
 *				types the client accepts.  If a string is passed, returns true
 *				if the client accepts it.  If an array is passed, returns true
 *				if the client accepts one or more elements in the array.
 * @access public
 * @see RequestHandlerComponent::setContent()
 */
	function accepts($type = null) {
		if ($type == null) {
			return $this->mapType($this->__acceptTypes);

		} else if(is_array($type)) {
			foreach($type as $t) {
				if ($this->accepts($t) == true) {
					return true;
				}
			}
			return false;
		} else if(is_string($type)) {

			if (!in_array($type, array_keys($this->__requestContent))) {
				return false;
			}

			$content = $this->__requestContent[$type]['content'];

			if (is_array($content)) {
				foreach($content as $c) {
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
 * @param mixed $type Can be null (or no parameter), a string type name, or an
 *					array of types
 * @access public
 */
	function requestedWith($type = null) {

		if (!$this->isPost() && !$this->isPut()) {
			return null;
		}

		if ($type == null) {
			return $this->mapType(env('CONTENT_TYPE'));

		} else if(is_array($type)) {
			foreach($type as $t) {
				if ($this->requestedWith($t)) {
					return $this->mapType($t);
				}
			}
			return false;
		} else if(is_string($type)) {

			return ($type == $this->mapType(env('CONTENT_TYPE')));
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
 *                     'html', 'xml', 'js', etc.
 * @returns mixed If $type is null or not provided, the first content-type in the
 *                list, based on preference, is returned.
 * @access public
 * @see RequestHandlerComponent::setContent()
 */
	function prefers($type = null) {
		if ($type == null) {
			if (!isset($this->params['url']['ext'])) {
				$accept = $this->accepts(null);
				if (is_array($accept)) {
					return $accept[0];
				}
				return $accept;
			} else {
				return $this->params['url']['ext'];
			}
		}

		$types = normalizeList($type, false);
		$accepts = array();
		foreach ($types as $type) {
			if ($this->accepts($type)) {
				$accepts[] = $type;
			}
		}

		if (count($accepts) == 0) {
			return false;
		} elseif (count($accepts) == 1) {
			return $accepts[0];
		} else {
			$accepts = array_intersect($this->__acceptTypes, $accepts);
			return $accepts[0];
		}
	}
/**
 * Sets the response header based on type map index name.  If DEBUG is greater
 * than 2, the header is not set.
 *
 * @param mixed $type Friendly type name, i.e. 'html' or 'xml', or a full
 *                    content-type, like 'application/x-shockwave'.
 * @param array $options If $type is a friendly type name that is associated with
 *                     more than one type of content, $index is used to select
 *                     which content-type to use.
 * @return boolean Returns false if the friendly type name given in $type does
 *                 not exist in the type map, or if the Content-type header has
 *                 already been set by this method.
 * @access public
 * @see RequestHandlerComponent::setContent()
 */
	function respondAs($type, $options = array()) {
		if ($this->__responseTypeSet != null) {
			return false;
		}
		if (!array_key_exists($type, $this->__requestContent) && strpos($type, '/') === false) {
			return false;
		}

		$options = am(
			array(
				'index' => 0,
				'charset' => null
			),
			$options
		);

		if (strpos($type, '/') === false && isset($this->__requestContent[$type])) {
			$cType = null;
			if (is_array($this->__requestContent[$type]) && isset($this->__requestContent[$type][$options['index']]['content'])) {
				$cType = $this->__requestContent[$type][$options['index']];
			} elseif (is_array($this->__requestContent[$type]) && isset($this->__requestContent[$type][0]['content'])) {
				$cType = $this->__requestContent[$type][0];
			} elseif (isset($this->__requestContent[$type]['content'])) {
				$cType = $this->__requestContent[$type]['content'];
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

			if ($options['charset'] != null) {
				$header .= '; charset=' . $options['charset'];
			}
			if (DEBUG < 2) {
				header($header);
			}
			$this->__responseTypeSet = $cType;
			return true;
		} else {
			return false;
		}
	}
/**
 * Returns the current response type (Content-type header), or null if none has been set
 *
 * @return mixed A string content type alias, or raw content type if no alias map exists,
 *               otherwise null
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
 * @param mixed $type
 * @return mixed
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

				if (is_array($type['content']) && in_array($ctype, $type['content'])) {
					return $name;
				} elseif (!is_array($type['content']) && $type['content'] == $ctype) {
					return $name;
				}
			}
			return $ctype;
		}
	}
}

?>