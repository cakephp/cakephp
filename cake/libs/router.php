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
 */
	var $routes = array();
/**
 * CAKE_ADMIN route
 *
 * @var array
 */
	var $__admin = null;
/**
 * Directive for Router to parse out file extensions for mapping to Content-types.
 *
 * @var boolean
 */
	var $__parseExtensions = false;
/**
 * List of valid extensions to parse from a URL.  If null, any extension is allowed.
 *
 * @var array
 */
	var $__validExtensions = null;
/**
 * 'Constant' regular expression definitions for named route elements
 *
 * @var array
 */
	var $__named = array(
		'Action'	=> 'index|show|list|add|create|edit|update|remove|del|delete|new|view|item',
		'Year'		=> '[12][0-9]{3}',
		'Month'		=> '(0[1-9]|1[012])',
		'Day'		=> '(0[1-9]|[12][0-9]|3[01])',
		'ID'		=> '[0-9]+'
	);
/**
 * The route matching the URL of the current request
 *
 * @var array
 */
	var $__currentRoute = array();
/**
 * Maintains the parameter stack for the current request
 *
 * @var array
 */
	var $__params = array();
/**
 * Maintains the path stack for the current request
 *
 * @var array
 */
	var $__paths = array();
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
 * @return object
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
 * @return array
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
 */
	function connect($route, $default = array(), $params = array()) {
		$_this =& Router::getInstance();
		$parsed = array();

		if (defined('CAKE_ADMIN') && $default == null) {
			if ($route == CAKE_ADMIN) {
				$_this->routes[] = $_this->__admin;
				$_this->__admin = null;
			}
		}

		if (empty($default['plugin'])) {
			$default['plugin'] = null;
		}
		if (empty($default['controller'])) {
			$default['controller'] = null;
		}
		if (!empty($default) && empty($default['action'])) {
			$default['action'] = 'index';
		}
		if ($route = $_this->writeRoute($route, $default, $params)) {
			$_this->routes[] = $route;
		}
		return $_this->routes;
	}
/**
 * Builds a route regular expression
 *
 * @access public
 * @param string $route			An empty string, or a route string "/"
 * @param array $default		NULL or an array describing the default route
 * @param array $params			An array matching the named elements in the route to regular expressions which that element should match.
 * @return string
 * @see routes
 */
	function writeRoute($route, $default, $params) {
		if (empty($route) || ($route == '/')) {
			return array($route, '/^[\/]*$/', array(), $default, array());
		} else {
			$names = array();
			$elements = $this->__filter(array_map('trim', explode('/', $route)));

			if (!count($elements)) {
				return false;
			}

			foreach($elements as $element) {
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
				} elseif(preg_match('/^\*$/', $element, $r)) {
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
 * @return array
 */
	function parse($url) {
		$_this =& Router::getInstance();
		$_this->__connectDefaultRoutes();
		$out = array();
		$r = $ext = null;

		if ($url && strpos($url, '/') !== 0 && !defined('SERVER_IIS')) {
			$url = '/' . $url;
		}
		if (strpos($url, '?') !== false) {
			$url = substr($url, 0, strpos($url, '?'));
		}
		extract($_this->__parseExtension($url));

		foreach($_this->routes as $route) {
			list($route, $regexp, $names, $defaults) = $route;

			if (preg_match($regexp, $url, $r)) {
				$_this->__currentRoute[] = $route;

				// remove the first element, which is the url
				array_shift ($r);
				// hack, pre-fill the default route names
				foreach($names as $name) {
					$out[$name] = null;
				}

				if (is_array($defaults)) {
					foreach($defaults as $name => $value) {
						if (preg_match('#[a-zA-Z_\-]#i', $name)) {
							$out[$name] = $value;
						} else {
							$out['pass'][] = $value;
						}
					}
				}

				foreach($_this->__filter($r, true) as $key => $found) {
					// if $found is a named url element (i.e. ':action')
					if (isset($names[$key])) {
						$out[$names[$key]] = $found;
					} else if (isset($names[$key]) && empty($names[$key]) && empty($out[$names[$key]])) {
						break; //leave the default values;
					} else {
						// unnamed elements go in as 'pass'
						$out['pass'] = explode('/', $found);
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
 * Parses a file extension out of a URL, if Router::parseExtensions() is enabled.
 *
 * @param string $url
 * @return array Returns an array containing the altered URL and the parsed extension.
 */
	function __parseExtension($url) {
		$ext = null;
		$_this =& Router::getInstance();

		if ($_this->__parseExtensions) {
			if(preg_match('/\.[0-9a-zA-Z]*$/', $url, $match) == 1) {
				$match = substr($match[0], 1);
				if(empty($_this->__validExtensions)) {
					$url = substr($url, 0, strpos($url, '.' . $match));
					$ext = $match;
				} else {
					foreach($_this->__validExtensions as $name) {
						if(strcasecmp($name, $match) === 0) {
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
 * @return void
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
 * @param array
 * @return void
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
 * @param boolean $current
 * @return array
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
 * @param string $name
 * @param boolean $current
 * @return string
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
 * @param boolean $current
 * @return array
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
 * @return void
 */
	function reload() {
		$_this =& Router::getInstance();
		foreach (get_class_vars('Router') as $key => $val) {
			$_this->{$key} = $val;
		}
	}
/**
 * Filters empty elements out of a route array, excluding '0'.
 *
 * @return mixed
 */
	function __filter($var, $isArray = false) {
		if (is_array($var) && (!empty($var) || $isArray)) {
			$_this =& Router::getInstance();
			return array_filter($var, array(&$_this, '__filter'));
		} else {
			if($var === '0' || !empty($var)) {
				return true;
			} else {
				return false;
			}
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
 */
	function url($url = null, $full = false) {
		$_this =& Router::getInstance();

		$defaults = $params = array('plugin' => null, 'controller' => null, 'action' => 'index');

		if(!empty($_this->__params)) {
			$params = end($_this->__params);
		}

		$path = array('base' => null);
		if(!empty($_this->__paths)) {
			$path = end($_this->__paths);
		}

		$base = $_this->stripPlugin($path['base'], $params['plugin']);
		$extension = $output = $mapped = $q = null;

		if (is_array($url) && !empty($url)) {
			if (isset($url['full_base']) && $url['full_base'] == true) {
				$full = true;
				unset($url['full_base']);
			}
			if (isset($url['?'])) {
				$q = $url['?'];
				unset($url['?']);
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

			$_this->__mapped = array();
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
			$skip = am(array_keys($_this->__mapped), array('bare', 'action', 'controller', 'plugin', 'ext', '?'));
			if(defined('CAKE_ADMIN')) {
				$skip[] = CAKE_ADMIN;
			}

			$keys = array_values(array_diff(array_keys($url), $skip));
			$count = count($keys);
			for ($i = 0; $i < $count; $i++) {
				if (is_numeric($keys[$i])) {
					$args[] = $url[$keys[$i]];
				} else if(!empty($path['namedArgs']) && in_array($keys[$i], array_keys($path['namedArgs'])) && !empty($url[$keys[$i]])) {
					$named[] = $keys[$i] . $path['argSeparator'] . $url[$keys[$i]];
				} else if(!empty($url[$keys[$i]])){
					$named[] = $keys[$i] . $path['argSeparator'] . $url[$keys[$i]];
				}
			}

			if ($match === false) {
				if (empty($named) && empty($args) && (!isset($url['action']) || $url['action'] == 'index')) {
					$url['action'] = null;
				}

				$urlOut = $_this->__filter(array($url['plugin'], $url['controller'], $url['action']));

				if($url['plugin'] == $url['controller']) {
					array_shift($urlOut);
				}
				if (defined('CAKE_ADMIN') && isset($url[CAKE_ADMIN]) && $url[CAKE_ADMIN]) {
					array_unshift($urlOut, CAKE_ADMIN);
				}
				$output = join('/', $urlOut);
			}

			if (!empty($args)) {
				if($output{strlen($output)-1} == '/') {
					$output .= join('/', $_this->__filter($args, true));
				} else {
					$output .= '/'. join('/', $_this->__filter($args, true));
				}
			}

			if (!empty($named)) {
				if($output{strlen($output)-1} == '/') {
					$output .= join('/', $_this->__filter($named, true));
				} else {
					$output .= '/'. join('/', $_this->__filter($named, true));
				}
			}

			$output = str_replace('//', '/', $base . '/' . $output);
		} else {
			if (((strpos($url, '://')) || (strpos($url, 'javascript:') === 0) || (strpos($url, 'mailto:') === 0)) || (substr($url,0,1) == '#')) {
				return $url;
			}

			if (empty($url)) {
				return $path['here'];
			} elseif($url{0} == '/') {
				$output = $base . $url;
			} else {
				$output = $base . '/';
				if (defined('CAKE_ADMIN') && isset($params[CAKE_ADMIN])) {
					$output .= CAKE_ADMIN . '/';
				}
				$output .= strtolower($params['controller']) . '/' . $url;
			}
		}
		if ($full) {
			$output = FULL_BASE_URL . $output;
		}
		return $output . $extension . $_this->queryString($q);
	}
/**
 * Maps a URL array onto a route and returns the string result, or false if no match
 *
 * @param array Route
 * @param array URL
 * @return mixed
 */
	function mapRouteElements($route, $url) {
		$_this =& Router::getInstance();

		$params = $route[2];
		$defaults = am(array('plugin'=> null, 'controller'=> null, 'action'=> null), $route[3]);

		$pass = Set::diff($url, $defaults);
		foreach($pass as $key => $value) {
			if(!is_numeric($key)) {
				unset($pass[$key]);
			}
		}

		if (!strpos($route[0], '*') && !empty($pass)) {
			return false;
		}
		/* removing this for now
		foreach ($defaults as $key => $default) {
			if(!array_key_exists($key, $url) && (!is_numeric($key) || !isset($pass[$key]))) {
				$url[$key] = $default;
			}
		}
		*/
		krsort($defaults);
		krsort($url);

		if (Set::diff($defaults, $url) == array()) {
			return array(Router::__mapRoute($route, am($url, array('pass' => $pass))), array());
		} elseif (!empty($params) && !empty($route[3])) {
			$required = array_diff(array_keys($defaults), array_keys($url));
			if(!empty($required)) {
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
			if(empty($required) && $defaults['plugin'] == $url['plugin'] && $defaults['controller'] == $url['controller'] && $defaults['action'] == $url['action']) {
				return array(Router::__mapRoute($route, am($url, array('pass' => $pass))), $url);
			}
			return false;
		}

		if(isset($route[3]['controller']) && !empty($route[3]['controller']) && $url['controller'] != $route[3]['controller']) {
			return false;
		}

		if(!empty($route[4])) {
			foreach ($route[4] as $key => $reg) {
				if (isset($url[$key]) && !preg_match('/' . $reg . '/', $url[$key])) {
					return false;
				}
			}
		}
		return array(Router::__mapRoute($route, am($url, array('pass' => $pass))), $url);
	}
/**
 * Merges URL parameters into a route string
 *
 * @param array Route
 * @param array $params
 * @return string
 */
	function __mapRoute($route, $params = array()) {
		$_this =& Router::getInstance();
		if (isset($params['pass']) && is_array($params['pass'])) {
			$_this->__mapped = $params['pass'];
 			$params['pass'] = implode('/', $_this->__filter($params['pass'], true));
		} elseif (!isset($params['pass'])) {
			$params['pass'] = '';
		}
		if (strpos($route[0], '*')) {
			$out = str_replace('*', $params['pass'], $route[0]);
		} else {
			$out = $route[0];
		}

		foreach ($route[2] as $key) {
			$out = str_replace(':' . $key, $params[$key], $out);
			$_this->__mapped[$key] = $params[$key];
			unset($params[$key]);
		}

		return $out;
	}
/**
 * Generates a well-formed querystring from $q
 *
 * @param mixed Querystring
 * @param array Extra querystring parameters
 * @return array
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
 * @return array
 */
	function requestRoute() {
		$_this =& Router::getInstance();
		return $_this->__currentRoute[0];
	}
/**
 * Returns the route matching the current request (useful for requestAction traces)
 *
 * @return array
 */
	function currentRoute() {
		$_this =& Router::getInstance();
		return $_this->__currentRoute[count($_this->__currentRoute) - 1];
	}
/**
 * Removes the plugin name from the base URL.
 *
 * @param string $base
 * @param string $plugin
 * @return base url with plugin name removed if present
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
 * @param string $ext
 * @param string $ext
 * @param string $ext
 * @param string ...
 * @return void
 */
	function parseExtensions() {
		$_this =& Router::getInstance();
		$_this->__parseExtensions = true;
		if (func_num_args() > 0) {
			$_this->__validExtensions = func_get_args();
		}
	}
}
/**
 * Implements http_build_query for PHP4.
 *
 * @param string $data
 * @param string $prefix
 * @param string $argSep
 * @param string $baseKey
 * @return string
 * @see http://php.net/http_build_query
 */
if(!function_exists('http_build_query')) {
	function http_build_query($data, $prefix = null, $argSep = null, $baseKey = null) {
		if(empty($argSep)) {
			$argSep = ini_get('arg_separator.output');
		}
		if (is_object($data)) {
			$data = get_object_vars($data);
		}
		$out = array();

		foreach((array)$data as $key => $val) {
			if(is_numeric($key) && !empty($prefix)) {
				$key = $prefix . $key;
			}
			$key = urlencode($key);

			if(!empty($baseKey)) {
				$key = $baseKey . '[' . $key . ']';
			}

			if(is_array($v) || is_object($v)) {
				$out[] = http_build_query($v, $prefix, $argSep, $key);
			} else {
				$out[] = $k . '=' . urlencode($v);
			}
		}
		return implode($argSep, $out);
	}
}

?>
