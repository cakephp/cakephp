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
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
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
 * List of action prefixes used in connected routes
 *
 * @var array
 * @access private
 */
	var $__prefixes = array();
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
		'ID'		=> '[0-9]+',
		'UUID'		=> '[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12}'
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
		array('action' => 'index',	'method' => 'GET',		'id' => false),
		array('action' => 'view',	'method' => 'GET',		'id' => true),
		array('action' => 'add',	'method' => 'POST',		'id' => false),
		array('action' => 'edit',	'method' => 'PUT', 		'id' => true),
		array('action' => 'delete',	'method' => 'DELETE',	'id' => true),
		array('action' => 'edit',	'method' => 'POST', 	'id' => true)
	);
/**
 * List of resource-mapped controllers
 *
 * @var array
 * @access private
 */
	var $__resourceMapped = array();
/**
 * Maintains the parameter stack for the current request
 *
 * @var array
 * @access private
 */
	var $__params = array();
/**
 * List of named arguments allowed in routes
 *
 * @var array
 * @access private
 */
	var $__namedArgs = array();
/**
 * Separator used to join/split/detect named arguments
 *
 * @var string
 * @access private
 */
	var $__argSeparator = ':';
/**
 * Maintains the path stack for the current request
 *
 * @var array
 * @access private
 */
	var $__paths = array();
/**
 * Keeps Router state to determine if default routes have already been connected
 *
 * @var boolean
 * @access private
 */
	var $__defaultsMapped = false;
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
 * @see Router::$__named
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
		$admin = Configure::read('Routing.admin');
		$default = array_merge(array('action' => null), $default);

		if (!empty($default) && empty($default['action'])) {
			$default['action'] = 'index';
		}

		if(isset($default[$admin])) {
			$default['prefix'] = $admin;
		}

		if (isset($default['prefix'])) {
			$_this->__prefixes[] = $default['prefix'];
			$_this->__prefixes = array_unique($_this->__prefixes);
		}

		if (list($pattern, $names) = $_this->writeRoute($route, $default, $params)) {
			$_this->routes[] = array($route, $pattern, $names, array_merge(array('plugin' => null, 'controller' => null), $default), $params);
		}
		return $_this->routes;
	}
/**
 * Connects an array of named arguments (with optional scoping options)
 *
 * @param array $named			List of named arguments
 * @param array $options		Named argument handling options
 * @access public
 * @static
 */
	function connectNamed($named, $options = array()) {
		$_this =& Router::getInstance();

		if (isset($options['argSeparator'])) {
			$_this->__argSeparator = $options['argSeparator'];
		}

		foreach ($named as $key => $val) {
			if (is_numeric($key)) {
				$_this->__namedArgs[$val] = true;
			} else {
				$_this->__namedArgs[$key] = $val;
			}
		}
	}
/**
 * Creates REST resource routes for the given controller(s)
 *
 * @param mixed $controller		A controller name or array of controller names (i.e. "Posts" or "ListItems")
 * @param array $options		Options to use when generating REST routes
 *					'id' -		The regular expression fragment to use when matching IDs.  By default, matches
 *								integer values and UUIDs.
 *					'prefix' -	URL prefix to use for the generated routes.  Defaults to '/'.
 * @access public
 * @static
 */
	function mapResources($controller, $options = array()) {
		$_this =& Router::getInstance();
		$options = array_merge(array('prefix' => '/', 'id' => $_this->__named['ID'] . '|' . $_this->__named['UUID']), $options);
		$prefix = $options['prefix'];

		foreach ((array)$controller as $ctlName) {
			$urlName = Inflector::underscore($ctlName);
			foreach ($_this->__resourceMap as $params) {
				extract($params);
				$id = ife($id, '/:id', '');

				Router::connect(
					"{$prefix}{$urlName}{$id}",
					array('controller' => $urlName, 'action' => $action, '[method]' => $params['method']),
					array('id' => $options['id'], 'pass' => array('id'))
				);
			}
			$this->__resourceMapped[] = $urlName;
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
			return array('/^[\/]*$/', array());
		}
		$names = array();
		$elements = explode('/', $route);

		foreach ($elements as $element) {
			if (empty($element)) {
				continue;
			}
			$q = null;
			$element = trim($element);
			$namedParam = strpos($element, ':') !== false;
			if ($namedParam && preg_match('/^:([^:]+)$/', $element, $r)) {
				if (isset($params[$r[1]])) {
					if ($r[1] != 'plugin' && array_key_exists($r[1], $default)) {
						$q = '?';
					}
					$parsed[] = '(?:/(' . $params[$r[1]] . ')' . $q . ')' . $q;
				} else {
					$parsed[] = '(?:/([^\/]+))?';
				}
				$names[] = $r[1];
			} elseif ($element == '*') {
				$parsed[] = '(?:/(.*))?';
			} else if ($namedParam && preg_match_all('/(?!\\\\):([^:\\\\]+)/', $element, $matches)) {
				foreach ($matches[1] as $i => $name) {
					$pos = strpos($element, ':'.$name);
					$before = substr($element, 0, $pos);
					$element = substr($element, $pos+strlen($name)+1);

					if ($i == 0) {
						$before = '/'.$before;
					}
					$before = preg_quote($before, '#');
					if (isset($params[$name])) {
						if (array_key_exists($name, $default) && $name != 'plugin') {
							$q = '?';
						}
						$parsed[] = '(?:'.$before.'(' . $params[$name] . ')' . $q . ')' . $q;
					} else {
						$parsed[] = '(?:'.$before.'([^\/]+))?';
					}
					$names[] = $name;
				}
			} else {
				$parsed[] = '/' . $element;
			}
		}
		return array('#^' . join('', $parsed) . '[\/]*$#', $names);
	}
/**
 * Returns the list of prefixes used in connected routes
 *
 * @return array A list of prefixes used in connected routes
 * @access public
 * @static
 */
	function prefixes() {
		$_this =& Router::getInstance();
		return $_this->__prefixes;
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
		if (!$_this->__defaultsMapped) {
			$_this->__connectDefaultRoutes();
		}
		$out = array('pass' => array(), 'named' => array());
		$r = $ext = null;

		if (ini_get('magic_quotes_gpc') == 1) {
			$url = stripslashes_deep($url);
		}

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
				list($route, $regexp, $names, $defaults, $params) = $route;

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
				foreach ($r as $key => $found) {
					if (empty($found)) {
						continue;
					}
					// if $found is a named url element (i.e. ':action')
					if (isset($names[$key])) {
						$out[$names[$key]] = $_this->stripEscape($found);
					} elseif (isset($names[$key]) && empty($names[$key]) && empty($out[$names[$key]])) {
						break; //leave the default values;
					} else {
						extract($_this->getArgs($found));
						$out['pass'] = array_merge($out['pass'], $pass);
						$out['named'] = $named;
					}
				}
				
				if (isset($params['pass'])) {
					foreach ($params['pass'] as $param) {
						if (isset($out[$param])) {
							$out['pass'][] = $out[$param];
						}
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
				if ($key{0} == '[' && preg_match('/^\[(\w+)\]$/', $key, $header)) {
					if (isset($_this->__headerMap[$header[1]])) {
						$header = $_this->__headerMap[$header[1]];
					} else {
						$header = 'http_' . $header[1];
					}

					if (!is_array($val)) {
						$val = array($val);
					}
					$h = false;
					foreach ($val as $v) {
						if (env(strtoupper($header)) == $v) {
							$h = true;
						}
					}
					if (!$h) {
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
		if ($_this->__defaultsMapped) {
			return;
		}

		if ($admin = Configure::read('Routing.admin')) {
			$params = array('prefix' => $admin, $admin => true);
		}

		if ($plugins = Configure::listObjects('plugin')) {
			$Inflector =& Inflector::getInstance();
			$plugins = array_map(array(&$Inflector, 'underscore'), $plugins);
		}

		if(!empty($plugins)) {
			$match = array('plugin' => implode('|', $plugins));
			$_this->connect('/:plugin/:controller/:action/*', array(), $match);

			if ($admin) {
				$_this->connect("/{$admin}/:plugin/:controller", $params, $match);
				$_this->connect("/{$admin}/:plugin/:controller/:action/*", $params, $match);
			}
		}

		if ($admin) {
			$_this->connect("/{$admin}/:controller", $params);
			$_this->connect("/{$admin}/:controller/:action/*", $params);
		}
		$_this->connect('/:controller', array('action' => 'index'));
		$_this->connect('/:controller/:action/*');

		if (empty($_this->__namedArgs)) {
			$_this->connectNamed(array('page', 'fields', 'order', 'limit', 'recursive', 'sort', 'direction', 'step'));
		}
		$_this->__defaultsMapped = true;
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
		$params[0] = array_merge($defaults, (array)$params[0]);
		$params[1] = array_merge($defaults, (array)$params[1]);
		list($_this->__params[], $_this->__paths[]) = $params;

		if (count($_this->__paths)) {
			if (isset($_this->__paths[0]['namedArgs'])) {
				foreach ($_this->__paths[0]['namedArgs'] as $arg => $value) {
					$_this->__namedArgs[$arg] = true;
				}
			}
		}
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
		if (isset($_this->__params[0])) {
			return $_this->__params[0];
		}
		return array();
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
		if (!isset($_this->__paths[0])) {
			return array('base' => null);
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
 * Promote a route (by default, the last one added) to the beginning of the list
 *
 * @param $which A zero-based array index representing the route to move. For example,
 *               if 3 routes have been added, the last route would be 2.
 * @return boolean Retuns false if no route exists at the position specified by $which.
 * @access public
 * @static
 */
	function promote($which = null) {
		$_this =& Router::getInstance();
		if ($which == null) {
			$which = count($_this->routes) - 1;
		}
		if (!isset($_this->routes[$which])) {
			return false;
		}
		$route = $_this->routes[$which];
		unset($_this->routes[$which]);
		array_unshift($_this->routes, $route);
		return true;
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
 * @param boolean $full If true, the full base URL will be prepended to the result
 * @return string  Full translated URL with base path.
 * @access public
 * @static
 */
	function url($url = null, $full = false) {
		$_this =& Router::getInstance();
		$defaults = $params = array('plugin' => null, 'controller' => null, 'action' => 'index');
		$admin = Configure::read('Routing.admin');

		if (!empty($_this->__params)) {
			if (isset($this) && !isset($this->params['requested'])) {
				$params = $_this->__params[0];
			} else {
				$params = end($_this->__params);
			}
		}
		$path = array('base' => null);

		if (!empty($_this->__paths)) {
			if (isset($this) && !isset($this->params['requested'])) {
				$path = $_this->__paths[0];
			} else {
				$path = end($_this->__paths);
			}
		}
		$base = $path['base'];
		$extension = $output = $mapped = $q = $frag = null;

		if (is_array($url) && !empty($url)) {
			if (array_key_exists('base', $url) && $url['base'] === false) {
				$base = null;
				unset($url['base']);
			}
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
			if (empty($url['action'])) {
				if (empty($url['controller']) || $params['controller'] == $url['controller']) {
					$url['action'] = $params['action'];
				} else {
					$url['action'] = 'index';
				}
			}
			if ($admin) {
				if (!isset($url[$admin]) && !empty($params[$admin])) {
					$url[$admin] = true;
				} elseif ($admin && array_key_exists($admin, $url) && !$url[$admin]) {
					unset($url[$admin]);
				}
			}

			$plugin = false;
			if (array_key_exists('plugin', $url)) {
				$plugin = $url['plugin'];
			}

			$url = array_merge(array('controller' => $params['controller'], 'plugin' => $params['plugin']), Set::filter($url, true));

			if ($plugin !== false) {
				$url['plugin'] = $plugin;
			}

			if (isset($url['ext'])) {
				$extension = '.' . $url['ext'];
				unset($url['ext']);
			}
			$match = false;

			foreach ($_this->routes as $route) {
				if ($match = $_this->mapRouteElements($route, $url)) {
					$output = trim($match, '/');
					$url = array();
					break;
				}
			}
			$named = $args = array();
			$skip = array('bare', 'action', 'controller', 'plugin', 'ext', '?', '#', 'prefix', $admin);

			$keys = array_values(array_diff(array_keys($url), $skip));
			$count = count($keys);

			// Remove this once parsed URL parameters can be inserted into 'pass'
			for ($i = 0; $i < $count; $i++) {
				if ($i == 0 && is_numeric($keys[$i]) && in_array('id', $keys)) {
					$args[0] = $url[$keys[$i]];
				} elseif (is_numeric($keys[$i]) || $keys[$i] == 'id') {
					$args[] = $url[$keys[$i]];
				} else {
					$named[$keys[$i]] = $url[$keys[$i]];
				}
			}

			if ($match === false) {
				list($args, $named)  = array(Set::filter($args, true), Set::filter($named));

				if (empty($named) && empty($args) && (!isset($url['action']) || $url['action'] == 'index')) {
					$url['action'] = null;
				}

				$urlOut = Set::filter(array($url['controller'], $url['action']));

				if (isset($url['plugin']) && $url['plugin'] != $url['controller']) {
					array_unshift($urlOut, $url['plugin']);
				}

				if($admin && isset($url[$admin])) {
					array_unshift($urlOut, $admin);
				}
				$output = join('/', $urlOut) . '/';
			}

			if (!empty($args)) {
				$args = join('/', $args);
				if ($output{strlen($output) - 1} != '/') {
					$args = '/'. $args;
				}
				$output .= $args;
			}

			if (!empty($named)) {
				foreach ($named as $name => $value) {
					$output .= '/' . $name . $_this->__argSeparator . $value;
				}
			}

			$output = str_replace('//', '/', $base . '/' . $output);
		} else {
			if (((strpos($url, '://')) || (strpos($url, 'javascript:') === 0) || (strpos($url, 'mailto:') === 0)) || (substr($url, 0, 1) == '#')) {
				return $url;
			}
			if (empty($url)) {
				return $path['here'];
			} elseif (substr($url, 0, 1) == '/') {
				$output = $base . $url;
			} else {
				$output = $base . '/';
				if ($admin && isset($params[$admin])) {
					$output .= $admin . '/';
				}
				if (!empty($params['plugin']) && $params['plugin'] !== $params['controller']) {
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
		if (isset($route[3]['prefix'])) {
			$prefix = $route[3]['prefix'];
			unset($route[3]['prefix']);
		}

		$pass = array();
		$defaults = $route[3];
		$routeParams = $route[2];
		$params = Set::diff($url, $defaults);
		$urlInv = array_combine(array_values($url), array_keys($url));

		$i = 0;
		while (isset($defaults[$i])) {
			if (isset($urlInv[$defaults[$i]])) {
				if (!in_array($defaults[$i], $url) && is_int($urlInv[$defaults[$i]])) {
					return false;
				}
				unset($urlInv[$defaults[$i]], $defaults[$i]);
			} else {
				return false;
			}
			$i++;
		}

		foreach ($params as $key => $value) {
			if (is_int($key)) {
				$pass[] = $value;
				unset($params[$key]);
			}
		}
		list($named, $params) = $_this->getNamedElements($params);

		if (!strpos($route[0], '*') && (!empty($pass) || !empty($named))) {
			return false;
		}

		$urlKeys = array_keys($url);
		$paramsKeys = array_keys($params);
		$defaultsKeys = array_keys($defaults);

		if (!empty($params)) {
			if (array_diff($paramsKeys, $routeParams) != array()) {
				return false;
			}
			$required = array_values(array_diff($routeParams, $urlKeys));
			$reqCount = count($required);

			for ($i = 0; $i < $reqCount; $i++) {
				if (array_key_exists($required[$i], $defaults) && $defaults[$required[$i]] === null) {
					unset($required[$i]);
				}
			}
		}
		$isFilled = true;

		if (!empty($routeParams)) {
			$filled = array_intersect_key($url, array_combine($routeParams, array_keys($routeParams)));
			$isFilled = (array_diff($routeParams, array_keys($filled)) == array());
			if (!$isFilled && empty($params)) {
				return false;
			}
		}

		if (empty($params)) {
			return Router::__mapRoute($route, array_merge($url, compact('pass', 'named', 'prefix')));
		} elseif (!empty($routeParams) && !empty($route[3])) {

			if (!empty($required)) {
			 	return false;
			}
			foreach ($params as $key => $val) {
				if ((!isset($url[$key]) || $url[$key] != $val) || (!isset($defaults[$key]) || $defaults[$key] != $val) && !in_array($key, $routeParams)) {
					if (array_key_exists($key, $defaults) && $defaults[$key] === null) {
						continue;
					}
					return false;
				}
			}
		} else {
			if (empty($required) && $defaults['plugin'] == $url['plugin'] && $defaults['controller'] == $url['controller'] && $defaults['action'] == $url['action']) {
				return Router::__mapRoute($route, array_merge($url, compact('pass', 'named', 'prefix')));
			}
			return false;
		}

		if (!empty($route[4])) {
			foreach ($route[4] as $key => $reg) {
				if (array_key_exists($key, $url) && !preg_match('/' . $reg . '/', $url[$key])) {
					return false;
				}
			}
		}
		return Router::__mapRoute($route, array_merge($filled, compact('pass', 'named', 'prefix')));
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

		if(isset($params['plugin']) && isset($params['controller']) && $params['plugin'] === $params['controller']) {
			unset($params['controller']);
		}

		if (isset($params['prefix']) && isset($params['action'])) {
			$params['action'] = str_replace($params['prefix'] . '_', '', $params['action']);
			unset($params['prefix']);
		}

		if (isset($params['pass']) && is_array($params['pass'])) {
 			$params['pass'] = implode('/', Set::filter($params['pass'], true));
		} elseif (!isset($params['pass'])) {
			$params['pass'] = '';
		}

		if (isset($params['named'])) {
			if (is_array($params['named'])) {
				$count = count($params['named']);
				$keys = array_keys($params['named']);
				$named = array();

				for ($i = 0; $i < $count; $i++) {
					$named[] = $keys[$i] . $_this->__argSeparator . $params['named'][$keys[$i]];
				}
				$params['named'] = join('/', $named);
			}
			$params['pass'] = str_replace('//', '/', $params['pass'] . '/' . $params['named']);
		}
		$out = $route[0];

		foreach ($route[2] as $key) {
			$string = null;
			if (isset($params[$key])) {
				$string = $params[$key];
				unset($params[$key]);
			}
			$out = str_replace(':' . $key, $string, $out);
		}

		if (strpos($route[0], '*')) {
			$out = str_replace('*', $params['pass'], $out);
		}
		return $out;
	}
/**
 * Takes an array of URL parameters and separates the ones that can be used as named arguments
 *
 * @param array $params			Associative array of URL parameters.
 * @param string $controller	Name of controller being routed.  Used in scoping.
 * @param string $action	 	Name of action being routed.  Used in scoping.
 * @return array
 * @access public
 * @static
 */
	function getNamedElements($params, $controller = null, $action = null) {
		$_this =& Router::getInstance();
		$named = array();

		foreach ($params as $key => $val) {
			if (isset($_this->__namedArgs[$key])) {
				$match = true;

				if (is_array($_this->__namedArgs[$key])) {
					$opts = $_this->__namedArgs[$key];
					if (isset($opts['controller']) && !in_array($controller, (array)$opts['controller'])) {
						$match = false;
					}
					if (isset($opts['action']) && !in_array($action, (array)$opts['action'])) {
						$match = false;
					}
					if (isset($opts['match']) && !preg_match('/' . $opts['match'] . '/', $val)) {
						$match = false;
					}
				} elseif (!$_this->__namedArgs[$key]) {
					$match = false;
				}
				if ($match) {
					$named[$key] = $val;
					unset($params[$key]);
				}
			}
		}
		return array($named, $params);
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
			$q = array_merge($extra, $q);
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
 * Normalizes a URL for purposes of comparison
 *
 * @param mixed $url URL to normalize
 * @return string Normalized URL
 * @access public
 */
	function normalize($url = '/') {
		if (is_array($url)) {
			$url = Router::url($url);
		}
		$paths = Router::getPaths();

		if (!empty($paths['base']) && stristr($url, $paths['base'])) {
			$url = str_replace($paths['base'], '', $url);
		}
		$url = '/' . $url;

		while (strpos($url, '//') !== false) {
			$url = str_replace('//', '/', $url);
		}
		$url = preg_replace('/(\/$)/', '', $url);

		if (empty($url)) {
			return '/';
		}
		return $url;
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
 * Takes an passed params and converts it to args
 *
 * @access public
 * @param array $params
 * @static
 */
	function getArgs($args) {
		$_this =& Router::getInstance();
		$pass = $named = array();
		$args = explode('/', $args);
		foreach ($args as $param) {
			if (empty($param) && $param !== '0' && $param !== 0) {
				continue;
			}
			$param = $_this->stripEscape($param);
			if (strpos($param, $_this->__argSeparator)) {
				$param = explode($_this->__argSeparator, $param, 2);
				$named[$param[0]] = $param[1];
			} else {
				$pass[] = $param;
			}
		}
		return compact('pass', 'named');
	}
}
?>