<?php
/* SVN FILE: $Id$ */
/**
 * Parses the request URL into controller, action, and parameters.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Included libraries.
 *
 */
if (!class_exists('Object')) {
	uses('object');
}

/**
 * Parses the request URL into controller, action, and parameters.
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class Router extends Object {

/**
 * Array of routes
 *
 * @var array
 * @access public
 */
	var $routes = array();
/**
 * CAKE_ADMIN route
 *
 * @var array
 * @access private
 */
	var $__admin = null;
/**
 * Directive for Router to parse out file extensions for mapping to Content-types.
 *
 * @var boolean
 * @access private
 */
	var $__parseExtensions = false;
/**
 * List of valid extensions to parse from a URL.  If null, any extension is allowed.
 *
 * @var array
 * @access private
 */
	var $__validExtensions = null;
/**
 * 'Constant' regular expression definitions for named route elements
 *
 * @var array
 * @access private
 */
	var $__named = array(
		'Action'	=> 'index|show|list|add|create|edit|update|remove|del|delete|new|view|item',
		'Year'		=> '[12][0-9]{3}',
		'Month'		=> '0[1-9]|1[012]',
		'Day'		=> '0[1-9]|[12][0-9]|3[01]',
		'ID'		=> '[0-9]+'
	);
/**
 * The route matching the URL of the current request
 *
 * @var array
 * @access private
 */
	var $__currentRoute = array();
/**
 * HTTP header shortcut map.  Used for evaluating header-based route expressions.
 *
 * @var array
 * @access private
 */
	var $__headerMap = array(
		'type'		=> 'content_type',
		'method'	=> 'request_method',
		'server'	=> 'server_name'
	);
/**
 * Default HTTP request method => controller action map.
 *
 * @var array
 * @access private
 */
	var $__resourceMap = array(
		'index'		=> array('method' => 'GET',		'id' => false),
		'view'		=> array('method' => 'GET',		'id' => true),
		'add'		=> array('method' => 'POST',	'id' => false),
		'edit'		=> array('method' => 'PUT', 	'id' => true),
		'delete'	=> array('method' => 'DELETE',	'id' => true)
	);
/**
 * Maintains the parameter stack for the current request
 *
 * @var array
 * @access private
 */
	var $__params = array();
/**
 * Maintains the path stack for the current request
 *
 * @var array
 * @access private
 */
	var $__paths = array();

/**
 * Maintains the mapped elements for array based urls
 *
 * @var array
 * @access private
 */
	var $__mapped = array();
/**
 * Initialize the Router object
 *
 */
	function __construct() {
		if (defined('CAKE_ADMIN')) {
			$admin = CAKE_ADMIN;
			if (!empty($admin)) {
				$this->__admin = array(
					'/:' . $admin . '/:controller/:action/*',
					'/^(?:\/(?:(' . $admin . ')(?:\\/([a-zA-Z0-9_\\-\\.\\;\\:]+)(?:\\/([a-zA-Z0-9_\\-\\.\\;\\:]+)(?:[\\/\\?](.*))?)?)?))[\/]*$/',
					array($admin, 'controller', 'action'), array()
				);
			}
		}
	}
/**
 * Gets a reference to the Router object instance
 *
 * @return object Object instance
 * @access public
 * @static
 */
	function &getInstance() {
		static $instance = array();

		if (!isset($instance[0]) || !$instance[0]) {
			$instance[0] =& new Router();
		}
		return $instance[0];
	}
/**
 * Gets the named route elements for use in app/config/routes.php
 *
 * @return array Named route elements
 * @access public
 * @static
 */
	function getNamedExpressions() {
		$_this =& Router::getInstance();
		return $_this->__named;
	}
/**
 * Returns this object's routes array. Returns false if there are no routes available.
 *
 * @param string $route			An empty string, or a route string "/"
 * @param array $default		NULL or an array describing the default route
 * @param array $params			An array matching the named elements in the route to regular expressions which that element should match.
 * @see routes
 * @return array			Array of routes
 * @access public
 * @static
 */
	function connect($route, $default = array(), $params = array()) {
		$_this =& Router::getInstance();

		if (defined('CAKE_ADMIN') && $default == null && $route == CAKE_ADMIN) {
			$_this->routes[] = $_this->__admin;
			$_this->__admin = null;
		}
		$default = am(array('plugin' => null, 'controller' => null), $default);

		if (!empty($default) && empty($default['action'])) {
			$default['action'] = 'index';
		}
		if ($route = $_this->writeRoute($route, $default, $params)) {
			$_this->routes[] = $route;
		}
		return $_this->routes;
	}
/**
 * Creates REST resource routes for the given controller(s)
 *
 * @param mixed $controller		A controller name or array of controller names (i.e. "Posts" or "ListItems")
 * @param array $options
 * @access public
 * @static
 */
	function mapResources($controller, $options = array()) {
		$_this =& Router::getInstance();
		$options = am(
			array('prefix' => '/'),
			$options
		);
		$prefix = $options['prefix'];

		foreach((array)$controller as $ctlName) {
			$urlName = Inflector::underscore($ctlName);
			foreach ($_this->__resourceMap as $action => $params) {
				$id = null;
				if ($params['id']) {
					$id = '/:id';
				}
				Router::connect(
					"{$prefix}{$urlName}{$id}",
					array('controller' => $urlName, 'action' => $action, '[method]' => $params['method'])
				);
			}
		}
	}
/**
 * Builds a route regular expression
 *
 * @param string $route			An empty string, or a route string "/"
 * @param array $default		NULL or an array describing the default route
 * @param array $params			An array matching the named elements in the route to regular expressions which that element should match.
 * @return string
 * @see routes
 * @access public
 * @static
 */
	function writeRoute($route, $default, $params) {
		if (empty($route) || ($route == '/')) {
			return array($route, '/^[\/]*$/', array(), $default, array());
		} else {
			$names = array();
			$elements = Set::filter(array_map('trim', explode('/', $route)));

			if (!count($elements)) {
				return false;
			}

			foreach ($elements as $element) {
				$q = null;

				if (preg_match('/^:(.+)$/', $element, $r)) {
					if (isset($params[$r[1]])) {
						if (array_key_exists($r[1], $default)) {
							$q = '?';
						}
						$parsed[] = '(?:\/(' . $params[$r[1]] . '))' . $q;
					} else {
						$parsed[] = '(?:\/([^\/]+))?';
					}
					$names[] = $r[1];
				} elseif (preg_match('/^\*$/', $element, $r)) {
					$parsed[] = '(?:\/(.*))?';
				} else {
					$parsed[] = '/' . $element;
				}
			}
			return array($route, '#^' . join('', $parsed) . '[\/]*$#', $names, $default, $params);
		}
	}
/**
 * Parses given URL and returns an array of controllers, action and parameters
 * taken from that URL.
 *
 * @param string $url URL to be parsed
 * @return array Parsed elements from URL
 * @access public
 * @static
 */
	function parse($url) {
		$_this =& Router::getInstance();
		$_this->__connectDefaultRoutes();
		$out = array('pass' => array());
		$r = $ext = null;

		if ($url && strpos($url, '/') !== 0) {
			$url = '/' . $url;
		}
		if (strpos($url, '?') !== false) {
			$url = substr($url, 0, strpos($url, '?'));
		}
		extract($_this->__parseExtension($url));

		foreach ($_this->routes as $route) {
			if (($r = $_this->matchRoute($route, $url)) !== false) {
				$_this->__currentRoute[] = $route;
				list($route, $regexp, $names, $defaults) = $route;

				// remove the first element, which is the url
				array_shift($r);
				// hack, pre-fill the default route names
				foreach ($names as $name) {
					$out[$name] = null;
				}

				if (is_array($defaults)) {
					foreach ($defaults as $name => $value) {
						if (preg_match('#[a-zA-Z_\-]#i', $name)) {
							$out[$name] = $value;
						} else {
							$out['pass'][] = $value;
						}
					}
				}

				foreach (Set::filter($r, true) as $key => $found) {
					// if $found is a named url element (i.e. ':action')
					if (isset($names[$key])) {
						$out[$names[$key]] = $_this->stripEscape($found);
					} elseif (isset($names[$key]) && empty($names[$key]) && empty($out[$names[$key]])) {
						break; //leave the default values;
					} else {
						// unnamed elements go in as 'pass'
						$out['pass'] = am($out['pass'], array_map(
							array(&$_this, 'stripEscape'),
							Set::filter(explode('/', $found), true)
						));
					}
				}
				break;
			}
		}
		if (!empty($ext)) {
			$out['url']['ext'] = $ext;
		}
		return $out;
	}
/**
 * Checks to see if the given URL matches the given route
 *
 * @param array $route
 * @param string $url
 * @return mixed Boolean false on failure, otherwise array
 * @access public
 */
	function matchRoute($route, $url) {
		$_this =& Router::getInstance();
		list($route, $regexp, $names, $defaults) = $route;

		if (!preg_match($regexp, $url, $r)) {
			return false;
		} else {
			foreach ($defaults as $key => $val) {
				if (preg_match('/^\[(\w+)\]$/', $key, $header)) {
					if (isset($_this->__headerMap[$header[1]])) {
						$header = $_this->__headerMap[$header[1]];
					} else {
						$header = 'http_' . $header[1];
					}
					if (env(strtoupper($header)) != $val) {
						return false;
					}
				}
			}
		}
		return $r;
	}
/**
 * Parses a file extension out of a URL, if Router::parseExtensions() is enabled.
 *
 * @param string $url
 * @return array Returns an array containing the altered URL and the parsed extension.
 * @access private
 */
	function __parseExtension($url) {
		$ext = null;
		$_this =& Router::getInstance();

		if ($_this->__parseExtensions) {
			if (preg_match('/\.[0-9a-zA-Z]*$/', $url, $match) == 1) {
				$match = substr($match[0], 1);
				if (empty($_this->__validExtensions)) {
					$url = substr($url, 0, strpos($url, '.' . $match));
					$ext = $match;
				} else {
					foreach ($_this->__validExtensions as $name) {
						if (strcasecmp($name, $match) === 0) {
							$url = substr($url, 0, strpos($url, '.' . $name));
							$ext = $match;
						}
					}
				}
			}
		}
		return compact('ext', 'url');
	}
/**
 * Connects the default, built-in routes, including admin routes, and (deprecated) web services
 * routes.
 *
 * @access private
 */
	function __connectDefaultRoutes() {
		$_this =& Router::getInstance();
		$default_route = array(
			'/:controller/:action/*',
			'/^(?:\/(?:([a-zA-Z0-9_\\-\\.\\;\\:]+)(?:\\/([a-zA-Z0-9_\\-\\.\\;\\:]+)(?:[\\/\\?](.*))?)?))[\\/]*$/',
			array('controller', 'action'), array()
		);

		if (defined('CAKE_ADMIN') && $_this->__admin != null) {
			$_this->routes[] = $_this->__admin;
			$_this->__admin = null;
		}
		$_this->connect('/bare/:controller/:action/*', array('bare' => '1'));
		$_this->connect('/ajax/:controller/:action/*', array('bare' => '1'));

		if (defined('WEBSERVICES') && WEBSERVICES == 'on') {
			trigger_error('Deprecated: webservices routes are deprecated and will not be supported in future versions.  Use Router::parseExtensions() instead.', E_USER_WARNING);
			$_this->connect('/rest/:controller/:action/*', array('webservices' => 'Rest'));
			$_this->connect('/rss/:controller/:action/*', array('webservices' => 'Rss'));
			$_this->connect('/soap/:controller/:action/*', array('webservices' => 'Soap'));
			$_this->connect('/xml/:controller/:action/*', array('webservices' => 'Xml'));
			$_this->connect('/xmlrpc/:controller/:action/*', array('webservices' => 'XmlRpc'));
		}
		$_this->routes[] = $default_route;
	}
/**
 * Takes parameter and path information back from the Dispatcher
 *
 * @param array $params Parameters and path information
 * @access public
 * @static
 */
	function setRequestInfo($params) {
		$_this =& Router::getInstance();
		$defaults = array('plugin' => null, 'controller' => null, 'action' => null);
		$params[0] = am($defaults, $params[0]);
		$params[1] = am($defaults, $params[1]);
		list($_this->__params[], $_this->__paths[]) = $params;
	}
/**
 * Gets parameter information
 *
 * @param boolean $current Get current parameter (true)
 * @return array Parameter information
 * @access public
 * @static
 */
	function getParams($current = false) {
		$_this =& Router::getInstance();
		if ($current) {
			return $_this->__params[count($_this->__params) - 1];
		}
		return $_this->__params[0];
	}
/**
 * Gets URL parameter by name
 *
 * @param string $name Parameter name
 * @param boolean $current Current parameter
 * @return string Parameter value
 * @access public
 * @static
 */
	function getParam($name = 'controller', $current = false) {
		$_this =& Router::getInstance();
		$params = Router::getParams($current);
		if (isset($params[$name])) {
			return $params[$name];
		}
		return null;
	}
/**
 * Gets path information
 *
 * @param boolean $current Current parameter
 * @return array
 * @access public
 * @static
 */
	function getPaths($current = false) {
		$_this =& Router::getInstance();
		if ($current) {
			return $_this->__paths[count($_this->__paths) - 1];
		}
		return $_this->__paths[0];
	}
/**
 * Reloads default Router settings
 *
 * @access public
 * @static
 */
	function reload() {
		$_this =& Router::getInstance();
		foreach (get_class_vars('Router') as $key => $val) {
			$_this->{$key} = $val;
		}
	}
/**
 * Finds URL for specified action.
 *
 * Returns an URL pointing to a combination of controller and action. Param
 * $url can be:
 *	+ Empty - the method will find adress to actuall controller/action.
 *	+ '/' - the method will find base URL of application.
 *	+ A combination of controller/action - the method will find url for it.
 *
 * @param  mixed  $url    Cake-relative URL, like "/products/edit/92" or "/presidents/elect/4"
 *                        or an array specifying any of the following: 'controller', 'action',
 *                        and/or 'plugin', in addition to named arguments (keyed array elements),
 *                        and standard URL arguments (indexed array elements)
 * @param boolean $full      If true, the full base URL will be prepended to the result
 * @return string  Full translated URL with base path.
 * @access public
 * @static
 */
	function url($url = null, $full = false) {
		$_this =& Router::getInstance();
		$defaults = $params = array('plugin' => null, 'controller' => null, 'action' => 'index');

		if (!empty($_this->__params)) {
			if (isset($this) && !isset($this->params['requested'])) {
				$params = $_this->__params[0];
			} elseif (isset($this) && isset($this->params['requested'])) {
				$params = end($_this->__params);
			} else {
				$params = end($_this->__params);
			}
		}
		$path = array('base' => null);

		if (!empty($_this->__paths)) {
			if (isset($this) && !isset($this->params['requested'])) {
				$path = $_this->__paths[0];
			} elseif (isset($this) && isset($this->params['requested'])) {
				$path = end($_this->__paths);
			} else {
				$path = end($_this->__paths);
			}
		}
		$base = $_this->stripPlugin($path['base'], $params['plugin']);
		$extension = $output = $mapped = $q = $frag = null;

		if (is_array($url) && !empty($url)) {
			if (isset($url['full_base']) && $url['full_base'] == true) {
				$full = true;
				unset($url['full_base']);
			}
			if (isset($url['?'])) {
				$q = $url['?'];
				unset($url['?']);
			}
			if (isset($url['#'])) {
				$frag = '#' . urlencode($url['#']);
				unset($url['#']);
			}
			if (!isset($url['action'])) {
				if (!isset($url['controller']) || $params['controller'] == $url['controller']) {
					$url['action'] = $params['action'];
				} else {
					$url['action'] = 'index';
				}
			}
			$url = am(array('controller' => $params['controller'], 'plugin' => $params['plugin']), $url);

			if (isset($url['ext'])) {
				$extension = '.' . $url['ext'];
			}
			if (defined('CAKE_ADMIN') && !isset($url[CAKE_ADMIN]) && isset($params[CAKE_ADMIN])) {
				$url[CAKE_ADMIN] = CAKE_ADMIN;
				$url['action'] = str_replace(CAKE_ADMIN.'_', '', $url['action']);
			} elseif (defined('CAKE_ADMIN') && isset($url[CAKE_ADMIN]) && $url[CAKE_ADMIN] == false) {
				unset($url[CAKE_ADMIN]);
			}
			$match = false;

			foreach ($_this->routes as $route) {
				if ($match = $_this->mapRouteElements($route, $url)) {
					list($output, $url) = $match;
					if (strpos($output, '/') === 0) {
						$output = substr($output, 1);
					}
					break;
				}
			}
			$named = $args = array();
			$skip = am(array_keys($_this->__mapped), array('bare', 'action', 'controller', 'plugin', 'ext', '?', '#'));

			if (defined('CAKE_ADMIN')) {
				$skip[] = CAKE_ADMIN;
			}
			$_this->__mapped = array();
			$keys = array_values(array_diff(array_keys($url), $skip));
			$count = count($keys);

			for ($i = 0; $i < $count; $i++) {
				if ($i == 0 && is_numeric($keys[$i]) && in_array('id', $keys)) {
					$args[0] = $url[$keys[$i]];
				} elseif (is_numeric($keys[$i]) || $keys[$i] == 'id') {
					$args[] = $url[$keys[$i]];
				} elseif (!empty($path['namedArgs']) && in_array($keys[$i], array_keys($path['namedArgs'])) && !empty($url[$keys[$i]])) {
					$named[] = $keys[$i] . $path['argSeparator'] . $url[$keys[$i]];
				} elseif (!empty($url[$keys[$i]]) || is_numeric($url[$keys[$i]])) {
					$named[] = $keys[$i] . $path['argSeparator'] . $url[$keys[$i]];
				}
			}

			if ($match === false) {
				list($args, $named)  = array(Set::filter($args, true), Set::filter($named, true));

				if (empty($named) && empty($args) && (!isset($url['action']) || $url['action'] == 'index')) {
					$url['action'] = null;
				}
				$urlOut = Set::filter(array($url['plugin'], $url['controller'], $url['action']));

				if ($url['plugin'] == $url['controller']) {
					array_shift($urlOut);
				}
				if (defined('CAKE_ADMIN') && isset($url[CAKE_ADMIN]) && $url[CAKE_ADMIN]) {
					array_unshift($urlOut, CAKE_ADMIN);
				}
				$output = join('/', $urlOut) . '/';
			}

			foreach (array('args', 'named') as $var) {
				if (!empty(${$var})) {
					${$var} = join('/', ${$var});
					if ($output{strlen($output) - 1} != '/') {
						${$var} = '/'. ${$var};
					}
					$output .= ${$var};
				}
			}
			$output = str_replace('//', '/', $base . '/' . $output);
		} else {
			if (((strpos($url, '://')) || (strpos($url, 'javascript:') === 0) || (strpos($url, 'mailto:') === 0)) || (substr($url, 0, 1) == '#')) {
				return $url;
			}

			if (empty($url)) {
				return $path['here'];
			} elseif ($url{0} == '/') {
				$output = $base . $url;
			} else {
				$output = $base . '/';
				if (defined('CAKE_ADMIN') && isset($params[CAKE_ADMIN])) {
					$output .= CAKE_ADMIN . '/';
				}
				if (!empty($params['plugin'])) {
					$output .= Inflector::underscore($params['plugin']) . '/';
				}
				$output .= Inflector::underscore($params['controller']) . '/' . $url;
			}
			$output = str_replace('//', '/', $output);
		}
		if ($full) {
			$output = FULL_BASE_URL . $output;
		}
		if (!empty($extension) && substr($output, -1) == '/') {
			$output = substr($output, 0, -1);
		}
		return $output . $extension . $_this->queryString($q) . $frag;
	}
/**
 * Maps a URL array onto a route and returns the string result, or false if no match
 *
 * @param array $route Route Route
 * @param array $url URL URL to map
 * @return mixed Result (as string) or false if no match
 * @access public
 * @static
 */
	function mapRouteElements($route, $url) {
		$_this =& Router::getInstance();

		$params = $route[2];
		$defaults = am(array('plugin'=> null, 'controller'=> null, 'action'=> null), $route[3]);
		$pass = Set::diff($url, $defaults);

		if (!strpos($route[0], '*') && !empty($pass)) {
			return false;
		}

		if (defined('CAKE_ADMIN') && isset($pass[CAKE_ADMIN])) {
			return false;
		}

		foreach ($pass as $key => $value) {
			if (!is_numeric($key)) {
				unset($pass[$key]);
			}
		}

		krsort($defaults);
		krsort($url);


		if (Set::diff($url, $defaults) == array()) {
			return array(Router::__mapRoute($route, am($url, compact('pass'))), array());
		} elseif (!empty($params) && !empty($route[3])) {
			$required = array_diff(array_keys($defaults), array_keys($url));

			if (!empty($required)) {
			 	return false;
			}
			$filled = array_intersect_key($url, array_combine($params, array_keys($params)));
			$keysFilled = array_keys($filled);
			sort($params);
			sort($keysFilled);

			if ($keysFilled != $params) {
				return false;
			}
			if (Set::diff($keysFilled, $params) != array()) {
				return false;
			}
		} else {
			$required = array_diff(array_keys($defaults), array_keys($url));
			if (empty($required) && $defaults['plugin'] == $url['plugin'] && $defaults['controller'] == $url['controller'] && $defaults['action'] == $url['action']) {
				return array(Router::__mapRoute($route, am($url, array('pass' => $pass))), $url);
			}
			return false;
		}

		if (isset($route[3]['controller']) && !empty($route[3]['controller']) && $url['controller'] != $route[3]['controller']) {
			return false;
		}

		if (!empty($route[4])) {
			foreach ($route[4] as $key => $reg) {
				if (isset($url[$key]) && !preg_match('/' . $reg . '/', $url[$key])) {
					return false;
				}
			}
		}
		return array(Router::__mapRoute($route, am($url, compact('pass'))), $url);
	}
/**
 * Merges URL parameters into a route string
 *
 * @param array $route Route
 * @param array $params Parameters
 * @return string Merged URL with parameters
 * @access private
 */
	function __mapRoute($route, $params = array()) {
		$_this =& Router::getInstance();
		if (isset($params['pass']) && is_array($params['pass'])) {
			$_this->__mapped = $params['pass'];
 			$params['pass'] = implode('/', Set::filter($params['pass'], true));
		} elseif (!isset($params['pass'])) {
			$params['pass'] = '';
		}

		if (isset($params['plugin'])) {
			if(strpos($route[0], 'plugin') === false && !empty($route[2])) {
				$route[2] = array_merge($route[2], array('plugin'));
				$route[0] = '/:plugin' . $route[0];
			}
		}

		if (strpos($route[0], '*')) {
			$out = str_replace('*', $params['pass'], $route[0]);
		} else {
			$out = $route[0];
		}

		foreach ($route[2] as $key) {
			$string = null;
			if (isset($params[$key])) {
				$string = $params[$key];
				unset($params[$key]);
			}
			$out = str_replace(':' . $key, $string, $out);
			$_this->__mapped[$key] = $string;
		}
		return $out;
	}
/**
 * Generates a well-formed querystring from $q
 *
 * @param mixed $q Query string
 * @param array $extra Extra querystring parameters
 * @return array
 * @access public
 * @static
 */
	function queryString($q, $extra = array()) {
		if (empty($q) && empty($extra)) {
			return null;
		}
		$out = '';

		if (is_array($q)) {
			$q = am($extra, $q);
		} else {
			$out = $q;
			$q = $extra;
		}
		$out .= http_build_query($q);
		if (strpos($out, '?') !== 0) {
			$out = '?' . $out;
		}
		return $out;
	}
/**
 * Returns the route matching the current request URL.
 *
 * @return array Matching route
 * @access public
 * @static
 */
	function requestRoute() {
		$_this =& Router::getInstance();
		return $_this->__currentRoute[0];
	}
/**
 * Returns the route matching the current request (useful for requestAction traces)
 *
 * @return array Matching route
 * @access public
 * @static
 */
	function currentRoute() {
		$_this =& Router::getInstance();
		return $_this->__currentRoute[count($_this->__currentRoute) - 1];
	}
/**
 * Removes the plugin name from the base URL.
 *
 * @param string $base Base URL
 * @param string $plugin Plugin name
 * @return base url with plugin name removed if present
 * @access public
 * @static
 */
	function stripPlugin($base, $plugin) {
		if ($plugin != null) {
			$base = preg_replace('/' . $plugin . '/', '', $base);
			$base = str_replace('//', '', $base);
			$pos1 = strrpos($base, '/');
			$char = strlen($base) - 1;

			if ($pos1 == $char) {
				$base = substr($base, 0, $char);
			}
		}
		return $base;
	}

/**
 * Strip escape characters from parameter values.
 *
 * @param mixed $param Either an array, or a string
 * @return mixed Array or string escaped
 * @access public
 * @static
 */
	function stripEscape($param) {
		$_this =& Router::getInstance();
		if (!is_array($param) || empty($param)) {
			if (is_bool($param)) {
				return $param;
			}

			$return = preg_replace('/^[\\t ]*(?:-!)+/', '', $param);
			return $return;
		}
		foreach ($param as $key => $value) {
			if (is_string($value)) {
				$return[$key] = preg_replace('/^[\\t ]*(?:-!)+/', '', $value);
			} else {
				foreach ($value as $array => $string) {
					$return[$key][$array] = $_this->stripEscape($string);
				}
			}
		}
		return $return;
	}
/**
 * Instructs the router to parse out file extensions from the URL. For example,
 * http://example.com/posts.rss would yield an file extension of "rss".
 * The file extension itself is made available in the controller as
 * $this->params['url']['ext'], and is used by the RequestHandler component to
 * automatically switch to alternate layouts and templates, and load helpers
 * corresponding to the given content, i.e. RssHelper.
 *
 * A list of valid extension can be passed to this method, i.e. Router::parseExtensions('rss', 'xml');
 * If no parameters are given, anything after the first . (dot) after the last / in the URL will be
 * parsed, excluding querystring parameters (i.e. ?q=...).
 *
 * @access public
 * @static
 */
	function parseExtensions() {
		$_this =& Router::getInstance();
		$_this->__parseExtensions = true;
		if (func_num_args() > 0) {
			$_this->__validExtensions = func_get_args();
		}
	}

/**
 * Takes an array of params and converts it to named args
 *
 * @access public
 * @param array $params
 * @param mixed $named
 * @param string $separator
 * @static
 */
	function getArgs($params, $named = true, $separator = ':') {
		$passedArgs = $namedArgs = array();
		if (is_array($named)) {
			if (array_key_exists($params['action'], $named)) {
				$named = $named[$params['action']];
			}
			$namedArgs = true;
		}
		if (!empty($params['pass'])) {
			$passedArgs = $params['pass'];
			if ($namedArgs === true || $named == true) {
				$namedArgs = array();
				$c = count($passedArgs);
				for ($i = 0; $i <= $c; $i++) {
					if (isset($passedArgs[$i]) && strpos($passedArgs[$i], $separator) !== false) {
						list($argKey, $argVal) = explode($separator, $passedArgs[$i]);
						if ($named === true || (!empty($named) && in_array($argKey, array_keys($named)))) {
							$passedArgs[$argKey] = $argVal;
							$namedArgs[$argKey] = $argVal;
							unset($passedArgs[$i]);
							unset($params['pass'][$i]);
						}
					} elseif ($separator === '/') {
						$ii = $i + 1;
						if (isset($passedArgs[$i]) && isset($passedArgs[$ii])) {
							$argKey = $passedArgs[$i];
							$argVal = $passedArgs[$ii];
							if (empty($namedArgs) || (!empty($namedArgs) && in_array($argKey, array_keys($namedArgs)))) {
								$passedArgs[$argKey] = $argVal;
								$namedArgs[$argKey] = $argVal;
								unset($passedArgs[$i], $passedArgs[$ii]);
								unset($params['pass'][$i], $params['pass'][$ii]);
							}
						}
					}
				}
			}
		}
		return array($passedArgs, $namedArgs);
	}
}

if (!function_exists('http_build_query')) {
/**
 * Implements http_build_query for PHP4.
 *
 * @param string $data Data to set in query string
 * @param string $prefix If numeric indices, prepend this to index for elements in base array.
 * @param string $argSep String used to separate arguments
 * @param string $baseKey Base key
 * @return string URL encoded query string
 * @see http://php.net/http_build_query
 */
	function http_build_query($data, $prefix = null, $argSep = null, $baseKey = null) {
		if (empty($argSep)) {
			$argSep = ini_get('arg_separator.output');
		}
		if (is_object($data)) {
			$data = get_object_vars($data);
		}
		$out = array();

		foreach ((array)$data as $key => $v) {
			if (is_numeric($key) && !empty($prefix)) {
				$key = $prefix . $key;
			}
			$key = urlencode($key);

			if (!empty($baseKey)) {
				$key = $baseKey . '[' . $key . ']';
			}

			if (is_array($v) || is_object($v)) {
				$out[] = http_build_query($v, $prefix, $argSep, $key);
			} else {
				$out[] = $key . '=' . urlencode($v);
			}
		}
		return implode($argSep, $out);
	}
}

?>