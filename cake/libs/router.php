<?php
/* SVN FILE: $Id$ */
/**
 * Parses the request URL into controller, action, and parameters.
 *
 * Long description for file
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
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 0.2.9
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Included libraries.
 *
 */
if (!class_exists('Object')) {
	App::import('Core', 'Object');
}
/**
 * Parses the request URL into controller, action, and parameters.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
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
 * Caches admin setting from Configure class
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
		'Action'	=> 'index|show|add|create|edit|update|remove|del|delete|view|item',
		'Year'		=> '[12][0-9]{3}',
		'Month'		=> '0[1-9]|1[012]',
		'Day'		=> '0[1-9]|[12][0-9]|3[01]',
		'ID'		=> '[0-9]+',
		'UUID'		=> '[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12}'
	);
/**
 * Stores all information necessary to decide what named arguments are parsed under what conditions.
 *
 * @var string
 * @access public
 */
	var $named = array(
		'default' => array('page', 'fields', 'order', 'limit', 'recursive', 'sort', 'direction', 'step'),
		'greedy' => true,
		'separator' => ':',
		'rules' => false,
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

		if (!$instance) {
			$instance[0] =& new Router();
			$instance[0]->__admin = Configure::read('Routing.admin');
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

		if (!isset($default['action'])) {
			$default['action'] = 'index';
		}
		if (isset($default[$_this->__admin])) {
			$default['prefix'] = $_this->__admin;
		}
		if (isset($default['prefix'])) {
			$_this->__prefixes[] = $default['prefix'];
			$_this->__prefixes = array_keys(array_flip($_this->__prefixes));
		}
		$_this->routes[] = array($route, $default, $params);
		return $_this->routes;
	}
/**
 * Specifies what named parameters CakePHP should be parsing. The most common setups are:
 *
 * Do not parse any named parameters:
 * {{{ Router::connectNamed(false); }}}
 *
 * Parse only default parameters used for CakePHP's pagination:
 * {{{ Router::connectNamed(false, array('default' => true)); }}}
 *
 * Parse only the page parameter if its value is a number:
 * {{{ Router::connectNamed(array('page' => '[\d]+'), array('default' => false, 'greedy' => false)); }}}
 *
 * Parse only the page parameter no mater what.
 * {{{ Router::connectNamed(array('page'), array('default' => false, 'greedy' => false)); }}}
 *
 * Parse only the page parameter if the current action is 'index'.
 * {{{ Router::connectNamed(array('page' => array('action' => 'index')), array('default' => false, 'greedy' => false)); }}}
 *
 * Parse only the page parameter if the current action is 'index' and the controller is 'pages'.
 * {{{ Router::connectNamed(array('page' => array('action' => 'index', 'controller' => 'pages')), array('default' => false, 'greedy' => false)); }}}
 *
 * @param array $named A list of named parameters. Key value pairs are accepted where values are either regex strings to match, or arrays as seen above.
 * @param array $options Allows to control all settings: separator, greedy, reset, default
 * @return array
 * @access public
 * @static
 */
	function connectNamed($named, $options = array()) {
		$_this =& Router::getInstance();

		if (isset($options['argSeparator'])) {
			$_this->named['separator'] = $options['argSeparator'];
			unset($options['argSeparator']);
		}

		if ($named === true || $named === false) {
			$options = array_merge(array('default' => $named, 'reset' => true, 'greedy' => $named), $options);
			$named = array();
		} else {
			$options = array_merge(array('default' => false, 'reset' => false, 'greedy' => true), $options);
		}

		if ($options['reset'] == true || $_this->named['rules'] === false) {
			$_this->named['rules'] = array();
		}

		if ($options['default']) {
			$named = array_merge($named, $_this->named['default']);
		}

		foreach ($named as $key => $val) {
			if (is_numeric($key)) {
				$_this->named['rules'][$val] = true;
			} else {
				$_this->named['rules'][$key] = $val;
			}
		}
		$_this->named['greedy'] = $options['greedy'];
		return $_this->named;
	}
/**
 * Creates REST resource routes for the given controller(s)
 *
 * Options:
 *
 * - 'id' - The regular expression fragment to use when matching IDs.  By default, matches
 *    integer values and UUIDs.
 * - 'prefix' - URL prefix to use for the generated routes.  Defaults to '/'.
 *
 * @param mixed $controller A controller name or array of controller names (i.e. "Posts" or "ListItems")
 * @param array $options Options to use when generating REST routes
 * @return void
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
				$url = $prefix . $urlName . (($id) ? '/:id' : '');

				Router::connect($url,
					array('controller' => $urlName, 'action' => $action, '[method]' => $params['method']),
					array('id' => $options['id'], 'pass' => array('id'))
				);
			}
			$_this->__resourceMapped[] = $urlName;
		}
	}
/**
 * Builds a route regular expression
 *
 * @param string $route An empty string, or a route string "/"
 * @param array $default NULL or an array describing the default route
 * @param array $params An array matching the named elements in the route to regular expressions which that element should match.
 * @return array
 * @see routes
 * @access public
 * @static
 */
	function writeRoute($route, $default, $params) {
		if (empty($route) || ($route === '/')) {
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
			} elseif ($element === '*') {
				$parsed[] = '(?:/(.*))?';
			} else if ($namedParam && preg_match_all('/(?!\\\\):([a-z_0-9]+)/i', $element, $matches)) {
				$matchCount = count($matches[1]);

				foreach ($matches[1] as $i => $name) {
					$pos = strpos($element, ':' . $name);
					$before = substr($element, 0, $pos);
					$element = substr($element, $pos + strlen($name) + 1);
					$after = null;

					if ($i + 1 === $matchCount && $element) {
						$after = preg_quote($element);
					}

					if ($i === 0) {
						$before = '/' . $before;
					}
					$before = preg_quote($before, '#');

					if (isset($params[$name])) {
						if (isset($default[$name]) && $name != 'plugin') {
							$q = '?';
						}
						$parsed[] = '(?:' . $before . '(' . $params[$name] . ')' . $q . $after . ')' . $q;
					} else {
						$parsed[] = '(?:' . $before . '([^\/]+)' . $after . ')?';
					}
					$names[] = $name;
				}
			} else {
				$parsed[] = '/' . $element;
			}
		}
		return array('#^' . implode('', $parsed) . '[\/]*$#', $names);
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

		if (ini_get('magic_quotes_gpc') === '1') {
			$url = stripslashes_deep($url);
		}

		if ($url && strpos($url, '/') !== 0) {
			$url = '/' . $url;
		}
		if (strpos($url, '?') !== false) {
			$url = substr($url, 0, strpos($url, '?'));
		}
		extract($_this->__parseExtension($url));

		foreach ($_this->routes as $i => $route) {
			if (count($route) === 3) {
				$route = $_this->compile($i);
			}

			if (($r = $_this->__matchRoute($route, $url)) !== false) {
				$_this->__currentRoute[] = $route;
				list($route, $regexp, $names, $defaults, $params) = $route;
				$argOptions = array();

				if (array_key_exists('named', $params)) {
					$argOptions['named'] = $params['named'];
					unset($params['named']);
				}
				if (array_key_exists('greedy', $params)) {
					$argOptions['greedy'] = $params['greedy'];
					unset($params['greedy']);
				}
				array_shift($r);

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
					if (empty($found) && $found != 0) {
						continue;
					}

					if (isset($names[$key])) {
						$out[$names[$key]] = $_this->stripEscape($found);
					} else {
						$argOptions['context'] = array('action' => $out['action'], 'controller' => $out['controller']);
						extract($_this->getArgs($found, $argOptions));
						$out['pass'] = array_merge($out['pass'], $pass);
						$out['named'] = $named;
					}
				}

				if (isset($params['pass'])) {
					for ($j = count($params['pass']) - 1; $j > -1; $j--) {
						if (isset($out[$params['pass'][$j]])) {
							array_unshift($out['pass'], $out[$params['pass'][$j]]);
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
 * @access private
 */
	function __matchRoute($route, $url) {
		list($route, $regexp, $names, $defaults) = $route;

		if (!preg_match($regexp, $url, $r)) {
			return false;
		} else {
			foreach ($defaults as $key => $val) {
				if ($key{0} === '[' && preg_match('/^\[(\w+)\]$/', $key, $header)) {
					if (isset($this->__headerMap[$header[1]])) {
						$header = $this->__headerMap[$header[1]];
					} else {
						$header = 'http_' . $header[1];
					}

					$val = (array)$val;
					$h = false;

					foreach ($val as $v) {
						if (env(strtoupper($header)) === $v) {
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
 * Compiles a route by numeric key and returns the compiled expression, replacing
 * the existing uncompiled route.  Do not call statically.
 *
 * @param integer $i
 * @return array Returns an array containing the compiled route
 * @access public
 */
	function compile($i) {
		$route = $this->routes[$i];

		list($pattern, $names) = $this->writeRoute($route[0], $route[1], $route[2]);
		$this->routes[$i] = array(
			$route[0], $pattern, $names,
			array_merge(array('plugin' => null, 'controller' => null), (array)$route[1]),
			$route[2]
		);
		return $this->routes[$i];
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

		if ($this->__parseExtensions) {
			if (preg_match('/\.[0-9a-zA-Z]*$/', $url, $match) === 1) {
				$match = substr($match[0], 1);
				if (empty($this->__validExtensions)) {
					$url = substr($url, 0, strpos($url, '.' . $match));
					$ext = $match;
				} else {
					foreach ($this->__validExtensions as $name) {
						if (strcasecmp($name, $match) === 0) {
							$url = substr($url, 0, strpos($url, '.' . $name));
							$ext = $match;
							break;
						}
					}
				}
			}
			if (empty($ext)) {
				$ext = 'html';
			}
		}
		return compact('ext', 'url');
	}
/**
 * Connects the default, built-in routes, including admin routes, and (deprecated) web services
 * routes.
 *
 * @return void
 * @access private
 */
	function __connectDefaultRoutes() {
		if ($this->__admin) {
			$params = array('prefix' => $this->__admin, $this->__admin => true);
		}

		if ($plugins = Configure::listObjects('plugin')) {
			foreach ($plugins as $key => $value) {
				$plugins[$key] = Inflector::underscore($value);
			}

			$match = array('plugin' => implode('|', $plugins));
			$this->connect('/:plugin/:controller/:action/*', array(), $match);

			if ($this->__admin) {
				$this->connect("/{$this->__admin}/:plugin/:controller", $params, $match);
				$this->connect("/{$this->__admin}/:plugin/:controller/:action/*", $params, $match);
			}
		}

		if ($this->__admin) {
			$this->connect("/{$this->__admin}/:controller", $params);
			$this->connect("/{$this->__admin}/:controller/:action/*", $params);
		}
		$this->connect('/:controller', array('action' => 'index'));
		$this->connect('/:controller/:action/*');

		if ($this->named['rules'] === false) {
			$this->connectNamed(true);
		}
		$this->__defaultsMapped = true;
	}
/**
 * Takes parameter and path information back from the Dispatcher
 *
 * @param array $params Parameters and path information
 * @return void
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
					$_this->named['rules'][$arg] = true;
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
 * @return void
 * @static
 */
	function reload() {
		$_this =& Router::getInstance();
		foreach (get_class_vars('Router') as $key => $val) {
			$_this->{$key} = $val;
		}
		$_this->__admin = Configure::read('Routing.admin');
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
		if ($which === null) {
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
 *
 * - Empty - the method will find adress to actuall controller/action.
 * - '/' - the method will find base URL of application.
 * - A combination of controller/action - the method will find url for it.
 *
 * @param mixed $url Cake-relative URL, like "/products/edit/92" or "/presidents/elect/4"
 *   or an array specifying any of the following: 'controller', 'action',
 *   and/or 'plugin', in addition to named arguments (keyed array elements),
 *   and standard URL arguments (indexed array elements)
 * @param mixed $full If (bool) true, the full base URL will be prepended to the result.
 *   If an array accepts the following keys
 *    - escape - used when making urls embedded in html escapes query string '&'
 *    - full - if true the full base URL will be prepended.
 * @return string Full translated URL with base path.
 * @access public
 * @static
 */
	function url($url = null, $full = false) {
		$_this =& Router::getInstance();
		$defaults = $params = array('plugin' => null, 'controller' => null, 'action' => 'index');

		if (is_bool($full)) {
			$escape = false;
		} else {
			extract(array_merge(array('escape' => false, 'full' => false), $full));
		}

		if (!empty($_this->__params)) {
			if (isset($this) && !isset($this->params['requested'])) {
				$params = $_this->__params[0];
			} else {
				$params = end($_this->__params);
			}
			if (isset($params['prefix']) && strpos($params['action'], $params['prefix']) === 0) {
				$params['action'] = substr($params['action'], strlen($params['prefix']) + 1);
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

		if (is_array($url)) {
			if (isset($url['base']) && $url['base'] === false) {
				$base = null;
				unset($url['base']);
			}
			if (isset($url['full_base']) && $url['full_base'] === true) {
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
				if (empty($url['controller']) || $params['controller'] === $url['controller']) {
					$url['action'] = $params['action'];
				} else {
					$url['action'] = 'index';
				}
			}
			if ($_this->__admin) {
				if (!isset($url[$_this->__admin]) && !empty($params[$_this->__admin])) {
					$url[$_this->__admin] = true;
				} elseif ($_this->__admin && isset($url[$_this->__admin]) && !$url[$_this->__admin]) {
					unset($url[$_this->__admin]);
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

			foreach ($_this->routes as $i => $route) {
				if (count($route) === 3) {
					$route = $_this->compile($i);
				}
				$originalUrl = $url;

				if (isset($route[4]['persist'], $_this->__params[0])) {
					$url = array_merge(array_intersect_key($params, Set::combine($route[4]['persist'], '/')), $url);
				}
				if ($match = $_this->mapRouteElements($route, $url)) {
					$output = trim($match, '/');
					$url = array();
					break;
				}
				$url = $originalUrl;
			}

			$named = $args = array();
			$skip = array(
				'bare', 'action', 'controller', 'plugin', 'ext', '?', '#', 'prefix', $_this->__admin
			);

			$keys = array_values(array_diff(array_keys($url), $skip));
			$count = count($keys);

			// Remove this once parsed URL parameters can be inserted into 'pass'
			for ($i = 0; $i < $count; $i++) {
				if ($i === 0 && is_numeric($keys[$i]) && in_array('id', $keys)) {
					$args[0] = $url[$keys[$i]];
				} elseif (is_numeric($keys[$i]) || $keys[$i] === 'id') {
					$args[] = $url[$keys[$i]];
				} else {
					$named[$keys[$i]] = $url[$keys[$i]];
				}
			}

			if ($match === false) {
				list($args, $named)  = array(Set::filter($args, true), Set::filter($named));
				if (!empty($url[$_this->__admin])) {
					$url['action'] = str_replace($_this->__admin . '_', '', $url['action']);
				}

				if (empty($named) && empty($args) && (!isset($url['action']) || $url['action'] === 'index')) {
					$url['action'] = null;
				}

				$urlOut = Set::filter(array($url['controller'], $url['action']));

				if (isset($url['plugin']) && $url['plugin'] != $url['controller']) {
					array_unshift($urlOut, $url['plugin']);
				}

				if ($_this->__admin && isset($url[$_this->__admin])) {
					array_unshift($urlOut, $_this->__admin);
				}
				$output = implode('/', $urlOut) . '/';
			}

			if (!empty($args)) {
				$args = implode('/', $args);
				if ($output{strlen($output) - 1} != '/') {
					$args = '/'. $args;
				}
				$output .= $args;
			}

			if (!empty($named)) {
				foreach ($named as $name => $value) {
					$output .= '/' . $name . $_this->named['separator'] . $value;
				}
			}
			$output = str_replace('//', '/', $base . '/' . $output);
		} else {
			if (((strpos($url, '://')) || (strpos($url, 'javascript:') === 0) || (strpos($url, 'mailto:') === 0)) || (!strncmp($url, '#', 1))) {
				return $url;
			}
			if (empty($url)) {
				if (!isset($path['here'])) {
					$path['here'] = '/';
				}
				$output = $path['here'];
			} elseif (substr($url, 0, 1) === '/') {
				$output = $base . $url;
			} else {
				$output = $base . '/';
				if ($_this->__admin && isset($params[$_this->__admin])) {
					$output .= $_this->__admin . '/';
				}
				if (!empty($params['plugin']) && $params['plugin'] !== $params['controller']) {
					$output .= Inflector::underscore($params['plugin']) . '/';
				}
				$output .= Inflector::underscore($params['controller']) . '/' . $url;
			}
			$output = str_replace('//', '/', $output);
		}
		if ($full && defined('FULL_BASE_URL')) {
			$output = FULL_BASE_URL . $output;
		}
		if (!empty($extension) && substr($output, -1) === '/') {
			$output = substr($output, 0, -1);
		}

		return $output . $extension . $_this->queryString($q, array(), $escape) . $frag;
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
		list($named, $params) = Router::getNamedElements($params);

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
			$isFilled = (array_diff($routeParams, array_keys($filled)) === array());
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
					if (!isset($defaults[$key])) {
						continue;
					}
					return false;
				}
			}
		} else {
			if (empty($required) && $defaults['plugin'] === $url['plugin'] && $defaults['controller'] === $url['controller'] && $defaults['action'] === $url['action']) {
				return Router::__mapRoute($route, array_merge($url, compact('pass', 'named', 'prefix')));
			}
			return false;
		}

		if (!empty($route[4])) {
			foreach ($route[4] as $key => $reg) {
				if (array_key_exists($key, $url) && !preg_match('#' . $reg . '#', $url[$key])) {
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
		if (isset($params['plugin']) && isset($params['controller']) && $params['plugin'] === $params['controller']) {
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
					$named[] = $keys[$i] . $this->named['separator'] . $params['named'][$keys[$i]];
				}
				$params['named'] = implode('/', $named);
			}
			$params['pass'] = str_replace('//', '/', $params['pass'] . '/' . $params['named']);
		}
		$out = $route[0];

		foreach ($route[2] as $key) {
			$string = null;
			if (isset($params[$key])) {
				$string = $params[$key];
				unset($params[$key]);
			} elseif (strpos($out, $key) != strlen($out) - strlen($key)) {
				$key = $key . '/';
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

		foreach ($params as $param => $val) {
			if (isset($_this->named['rules'][$param])) {
				$rule = $_this->named['rules'][$param];
				if (Router::matchNamed($param, $val, $rule, compact('controller', 'action'))) {
					$named[$param] = $val;
					unset($params[$param]);
				}
			}
		}
		return array($named, $params);
	}
/**
 * Return true if a given named $param's $val matches a given $rule depending on $context. Currently implemented
 * rule types are controller, action and match that can be combined with each other.
 *
 * @param string $param The name of the named parameter
 * @param string $val The value of the named parameter
 * @param array $rule The rule(s) to apply, can also be a match string
 * @param string $context An array with additional context information (controller / action)
 * @return boolean
 * @access public
 */
	function matchNamed($param, $val, $rule, $context = array()) {
		if ($rule === true || $rule === false) {
			return $rule;
		}
		if (is_string($rule)) {
			$rule = array('match' => $rule);
		}
		if (!is_array($rule)) {
			return false;
		}

		$controllerMatches = !isset($rule['controller'], $context['controller']) || in_array($context['controller'], (array)$rule['controller']);
		if (!$controllerMatches) {
			return false;
		}
		$actionMatches = !isset($rule['action'], $context['action']) || in_array($context['action'], (array)$rule['action']);
		if (!$actionMatches) {
			return false;
		}
		$valueMatches = !isset($rule['match']) || preg_match(sprintf('/%s/', $rule['match']), $val);
		return $valueMatches;
	}
/**
 * Generates a well-formed querystring from $q
 *
 * @param mixed $q Query string
 * @param array $extra Extra querystring parameters.
 * @param bool $escape Whether or not to use escaped &
 * @return array
 * @access public
 * @static
 */
	function queryString($q, $extra = array(), $escape = false) {
		if (empty($q) && empty($extra)) {
			return null;
		}
		$join = '&';
		if ($escape === true) {
			$join = '&amp;';
		}
		$out = '';

		if (is_array($q)) {
			$q = array_merge($extra, $q);
		} else {
			$out = $q;
			$q = $extra;
		}
		$out .= http_build_query($q, null, $join);
		if (isset($out[0]) && $out[0] != '?') {
			$out = '?' . $out;
		}
		return $out;
	}
/**
 * Normalizes a URL for purposes of comparison.  Will strip the base path off
 * and replace any double /'s.  It will not unify the casing and underscoring
 * of the input value.
 *
 * @param mixed $url URL to normalize Either an array or a string url.
 * @return string Normalized URL
 * @access public
 */
	function normalize($url = '/') {
		if (is_array($url)) {
			$url = Router::url($url);
		} elseif (preg_match('/^[a-z\-]+:\/\//', $url)) {
			return $url;
		}
		$paths = Router::getPaths();

		if (!empty($paths['base']) && stristr($url, $paths['base'])) {
			$url = preg_replace('/^' . preg_quote($paths['base'], '/') . '/', '', $url, 1);
		}
		$url = '/' . $url;

		while (strpos($url, '//') !== false) {
			$url = str_replace('//', '/', $url);
		}
		$url = preg_replace('/(?:(\/$))/', '', $url);

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
	function stripPlugin($base, $plugin = null) {
		if ($plugin != null) {
			$base = preg_replace('/(?:' . $plugin . ')/', '', $base);
			$base = str_replace('//', '', $base);
			$pos1 = strrpos($base, '/');
			$char = strlen($base) - 1;

			if ($pos1 === $char) {
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

			return preg_replace('/^(?:[\\t ]*(?:-!)+)/', '', $param);
		}

		foreach ($param as $key => $value) {
			if (is_string($value)) {
				$return[$key] = preg_replace('/^(?:[\\t ]*(?:-!)+)/', '', $value);
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
 * @return void
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
 * @param array $params
 * @return array Array containing passed and named parameters
 * @access public
 * @static
 */
	function getArgs($args, $options = array()) {
		$_this =& Router::getInstance();
		$pass = $named = array();
		$args = explode('/', $args);

		$greedy = $_this->named['greedy'];
		if (isset($options['greedy'])) {
			$greedy = $options['greedy'];
		}
		$context = array();
		if (isset($options['context'])) {
			$context = $options['context'];
		}
		$rules = $_this->named['rules'];
		if (isset($options['named'])) {
			$greedy = isset($options['greedy']) && $options['greedy'] === true;
			foreach ((array)$options['named'] as $key => $val) {
				if (is_numeric($key)) {
					$rules[$val] = true;
					continue;
				}
				$rules[$key] = $val;
			}
		}

		foreach ($args as $param) {
			if (empty($param) && $param !== '0' && $param !== 0) {
				continue;
			}
			$param = $_this->stripEscape($param);

			$separatorIsPresent = strpos($param, $_this->named['separator']) !== false;
			if ((!isset($options['named']) || !empty($options['named'])) && $separatorIsPresent) {
				list($key, $val) = explode($_this->named['separator'], $param, 2);
				$hasRule = isset($rules[$key]);
				$passIt = (!$hasRule && !$greedy) || ($hasRule && !Router::matchNamed($key, $val, $rules[$key], $context));
				if ($passIt) {
					$pass[] = $param;
				} else {
					$named[$key] = $val;
				}
			} else {
				$pass[] = $param;
			}
		}
		return compact('pass', 'named');
	}
}
?>