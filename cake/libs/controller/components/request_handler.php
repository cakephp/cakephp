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
 * Request object for handling alternative HTTP requests
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller.components
 *
 */
class RequestHandlerComponent extends Object{

	var $ajaxLayout = 'ajax';

	var $disableStartup = false;

	var $enabled = true;

	var $__responseTypeSet = false;

	var $params = array();

	var $__requestContent = array(
		'javascript'	=> 'text/javascript',
		'js'			=> 'text/javascript',
		'css'			=> 'text/css',
		'html'			=> array('text/html', '*/*'),
		'text'			=> 'text/plain',
		'form'			=> 'application/x-www-form-urlencoded',
		'file'			=> 'multipart/form-data',
		'xhtml'			=> array('application/xhtml+xml', 'application/xhtml', 'text/xhtml'),
		'xml'			=> array('application/xml', 'text/xml'),
		'rss'			=> 'application/rss+xml',
		'atom'			=> 'application/atom+xml',
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
 * Initialize
 *
 * @param object A reference to the controller
 * @return void
 */
	function initialize(&$controller) {
		$this->params =& $controller->params;
		if (isset($this->params['url']['extension'])) {
			$ext = $this->params['url']['extension'];
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
 * Startup
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

		if (isset($this->params['url']['extension'])) {
			$ext = $this->params['url']['extension'];
			if (in_array($ext, array_keys($this->__requestContent))) {
				if ($ext != 'html' && $ext != 'htm' && !empty($ext)) {
					$controller->ext = '.ctp';
				}
				$controller->viewPath .= '/' . $ext;
				$controller->layoutPath = $ext;
				if (in_array($ext, array_keys($this->__requestContent))) {
					$this->respondAs($ext);
				}
			}
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
 * Sets a controller's layout based on whether or not the current call is Ajax
 *
 * @param object The controller object
 * @return boolean True if call is Ajax, otherwise false
 */
	function setAjax(&$controller) {
		if ($this->isAjax()) {
			$controller->layout=$this->ajaxLayout;

			// Add UTF-8 header for IE6 on XPsp2 bug
			header ('Content-Type: text/html; charset=UTF-8');
			return true;
		}
		return false;
	}
/**
 * Returns true if the current call is from Ajax, false otherwise
 *
 * @return bool True if call is Ajax
 */
	function isAjax() {
		return env('HTTP_X_REQUESTED_WITH') === "XMLHttpRequest";
	}
/**
 * Returns true if the current call accepts an XML response, false otherwise
 *
 * @return bool True if client accepts an XML response
 */
	function isXml() {
		return $this->accepts('xml');
	}
/**
 * Returns true if the current call accepts an RSS response, false otherwise
 *
 * @return bool True if client accepts an RSS response
 */
	function isRss() {
		return $this->accepts('rss');
	}
/**
 * Returns true if the current call accepts an RSS response, false otherwise
 *
 * @return bool True if client accepts an RSS response
 */
	function isAtom() {
		return $this->accepts('atom');
	}
/**
 * Returns true if user agent string matches a mobile web browser, or if the client accepts WAP content
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
		return $this->accepts('wap');
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
 * Adds/sets the Content-type(s) for the given name
 *
 * @param string $name The name of the Content-type, i.e. "html", "xml", "css"
 * @param mixed $type The Content-type or array of Content-types assigned to the name
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
 * Determines which content types the client accepts
 *
 * @param mixed $type Can be null (or no parameter), a string type name, or an
 *					array of types
 * @returns mixed If null or no parameter is passed, returns an array of content
 *				types the client accepts.  If a string is passed, returns true
 *				if the client accepts it.  If an array is passed, returns true
 *				if the client accepts one or more elements in the array.
 * @access public
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
 * Determines which content types the client prefers
 *
 * @param mixed $type
 * @returns mixed
 * @access public
 */
	function prefers($type = null) {
		if ($type == null) {
			return $this->accepts(null);
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
 * Sets the response header based on type map index name
 *
 * @param mixed $type
 * @param mixed $index
 * @return boolean
 * @access public
 */
	function respondAs($type, $index = 0) {
		if ($this->__responseTypeSet) {
			return false;
		}
		if (!array_key_exists($type, $this->__requestContent)) {
			return false;
		}

		$cType = null;
		if (is_array($this->__requestContent[$type]) && isset($this->__requestContent[$type][$index]['content'])) {
			$cType = $this->__requestContent[$type][$index];
		} elseif (is_array($this->__requestContent[$type]) && isset($this->__requestContent[$type][0]['content'])) {
			pr($this->__requestContent[$type]);
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

		if ($cType != null) {
			header('Content-type: ' . $cType);
			$this->__responseTypeSet = true;
			return true;
		} else {
			return false;
		}
	}
/**
 * Maps a content-type back to an alias
 *
 * @param mixed $type
 * @returns mixed
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