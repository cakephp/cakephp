<?php
/* SVN FILE: $Id$ */
/**
 * Parses the request URL into controller, action, and parameters.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
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
 * @subpackage		cake.cake.libs
 * @since			CakePHP v 0.2.9
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
	 uses ('object');
}

/**
 * Parses the request URL into controller, action, and parameters.
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
uses('overloadable');

class Router extends Overloadable {
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
 * TODO: Better description. Returns this object's routes array. Returns false if there are no routes available.
 *
 * @param string $route			An empty string, or a route string "/"
 * @param array $default		NULL or an array describing the default route
 * @param array $params			An array matching the named elements in the route to regular expressions which that element should match.
 * @see routes
 * @return array			Array of routes
 */
	function connect($route, $default = array(), $params = array()) {

		$_this =& Router::getInstance();
		$parsed = $names = array();

		if (defined('CAKE_ADMIN') && $default == null) {
			if ($route == CAKE_ADMIN) {
				$_this->routes[] = $_this->__admin;
				$_this->__admin = null;
			}
		}

		if (!empty($default) && !isset($default['action'])) {
			$default['action'] = 'index';
		}

		if (!isset($default['plugin']) || empty($default['plugin'])) {
			$default['plugin'] = null;
		}

		$r = null;
		if (($route == '') || ($route == '/')) {
			$regexp = '/^[\/]*$/';
			$_this->routes[] = array($route, $regexp, array(), $default, array());
		} else {
			$elements = array();

			foreach(explode('/', $route) as $element) {
				if (trim($element)) {
					$elements[] = $element;
				}
			}

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

			$regexp = '#^' . join('', $parsed) . '[\/]*$#';
			$_this->routes[] = array($route, $regexp, $names, $default, $params);
		}
		return $_this->routes;
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

		if ($url && ('/' != $url[0])) {
			if (!defined('SERVER_IIS')) {
				$url = '/' . $url;
			}
		}
		$out = array();
		$r = null;
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
			$_this->connect('/rest/:controller/:action/*', array('webservices' => 'Rest'));
			$_this->connect('/rss/:controller/:action/*', array('webservices' => 'Rss'));
			$_this->connect('/soap/:controller/:action/*', array('webservices' => 'Soap'));
			$_this->connect('/xml/:controller/:action/*', array('webservices' => 'Xml'));
			$_this->connect('/xmlrpc/:controller/:action/*', array('webservices' => 'XmlRpc'));
		}
		$_this->routes[] = $default_route;
		$ext = array();

		if (strpos($url, '?') !== false) {
			$url = substr($url, 0, strpos($url, '?'));
		}
		if ($_this->__parseExtensions && preg_match('/\.[0-9a-zA-Z]*$/', $url, $ext) == 1) {
			$ext = substr($ext[0], 1);
			$url = substr($url, 0, strpos($url, '.' . $ext));
		}

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
				$ii = 0;

				if (is_array($defaults)) {
					foreach($defaults as $name => $value) {
						if (preg_match('#[a-zA-Z_\-]#i', $name)) {
							$out[$name] = $value;
						} else {
							$out['pass'][] = $value;
						}
					}
				}

				foreach($r as $found) {
					// if $found is a named url element (i.e. ':action')
					if (isset($names[$ii])) {
						$out[$names[$ii]] = $found;
					} else {
						// unnamed elements go in as 'pass'
						$out['pass'] = array_filter(explode('/', $found));
					}
					$ii++;
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
 * Takes parameter and path information back from the Dispatcher
 *
 * @param array
 * @return void
 */
	function setParams($params) {
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

		$params = isset($_this->__params[0]) ? $_this->__params[0] : array();
		$path = isset($_this->__paths[0]) ? $_this->__paths[0] : array();
		$base = $_this->stripPlugin($path['base'], $params['plugin']);
		$extension = $output = $mapped = $q = null;

		if (is_array($url) && !empty($url)) {
			$url = array_filter($url);

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

			$url = am(
				array('controller' => $params['controller'], 'plugin' => $params['plugin']),
				$url
			);
			if (isset($url['ext'])) {
				$extension = '.' . $url['ext'];
			}
			if (defined('CAKE_ADMIN') && !isset($url[CAKE_ADMIN]) && isset($params[CAKE_ADMIN])) {
				$url[CAKE_ADMIN] = $params[CAKE_ADMIN];
				$url['action'] = str_replace(CAKE_ADMIN . '_', '', $url['action']);
			} elseif (defined('CAKE_ADMIN') && isset($url[CAKE_ADMIN]) && $url[CAKE_ADMIN] == false) {
				unset($url[CAKE_ADMIN]);
			}

			foreach ($_this->routes as $route) {
				$match = $_this->mapRouteElements($route, $url);
				if ($match !== false) {
					list($output, $url) = $match;
					if ($output{0} == '/') {
						$output = substr($output, 1);
					}
					break;
				}
			}

			$named = $args = array();
			$keys = array_keys($url);
			$count = count($keys);

			for ($i = 0; $i < $count; $i++) {
				if (is_numeric($keys[$i])) {
					$args[] = $url[$keys[$i]];
				} else {
					if (!in_array($keys[$i], array('action', 'controller', 'plugin', 'ext', '?'))) {
						if (defined('CAKE_ADMIN') && isset($keys[$i]) && $keys[$i] != CAKE_ADMIN) {
							$named[] = array($keys[$i], $url[$keys[$i]]);
						}
					}
				}
			}

			if ($match === false) {
				if (empty($named) && empty($args) && $url['action'] == 'index') {
					$url['action'] = null;
				}
			}

			$combined = '';
			if (isset($path['namedArgs']) && !empty($path['namedArgs'])) {
				if ($path['namedArgs'] === true) {
					$sep = $path['argSeparator'];
				} elseif (is_array($path['namedArgs'])) {
					$sep = '/';
				}

				$count = count($named);
				for ($i = 0; $i < $count; $i++) {
					$named[$i] = join($path['argSeparator'], $named[$i]);
				}

				$combined = join('/', $named);
			}

			if ($match === false) {
				$urlOut = array_filter(array($url['plugin'], $url['controller'], $url['action'], join('/', array_filter($args)), $combined));
				if (defined('CAKE_ADMIN') && isset($url[CAKE_ADMIN]) && $url[CAKE_ADMIN]) {
					array_unshift($urlOut, CAKE_ADMIN);
				}
				$output = join('/', $urlOut);
			} elseif (!empty($combined)) {
				$output .= '/' . $combined;
			}

			$output = $base . '/' . $output;
		} else {
			if (((strpos($url, '://')) || (strpos($url, 'javascript:') === 0) || (strpos($url, 'mailto:') === 0)) || ($url{0} == '#')) {
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
		$params = $route[2];
		$defaults = am(array('plugin' => null), $route[3]);
		$required = array_diff_key($defaults, $params);
		$url = am(array('action' => 'index', 'plugin' => null), $url);

		foreach ($defaults as $key => $val) {
			if (!is_numeric($key) && !isset($url[$key])) {
				$url[$key] = $val;
			}
		}

		sort($route[3]);
		sort($url);

		if ($route[3] == $url) {
			return array(Router::__mapRoute($route, $url), array());
		} elseif (!empty($params)) {
			$filled = array_intersect_key($url, array_combine($params, array_keys($params)));
			$keysFilled = array_keys($filled);
			sort($params);
			sort($keysFilled);
			if ($keysFilled != $params) {
				return false;
			}
		} else {
			return false;
		}

		/*if (!empty($diffs)) {
			if (isset($route[3]['controller']) && in_array('controller', $diffed)) {
				return false;
			}
			if (isset($route[3]['action']) && in_array('action', $diffed)) {
				return false;
			}
		}*/

		//$required = array_diff(array_diff($route[2], array_keys($route[3])), array_keys($url));
		/*sort($required);
		if (!empty($required)) {
			return false;
		}*/

		foreach ($route[4] as $key => $reg) {
			if (isset($url[$key]) && !preg_match('/' . $reg . '/', $url[$key])) {
				return false;
			}
		}

		//$out = str_replace(array('/bare', '/*', '/ajax'), '', $route[0]);
		return array(Router::__mapRoute($route, $url), $url);
	}
/**
 * Merges URL parameters into a route string
 *
 * @param array Route
 * @param array $params
 * @return string
 */
	function __mapRoute($route, $params = array()) {
		if (isset($params['pass']) && is_array($params['pass'])) {
			$params['pass'] = implode('/', array_filter($params['pass']));
		} elseif (!isset($params['pass'])) {
			$params['pass'] = '';
		}

		if (strpos($route[0], '*')) {
			$out = str_replace('*', $params['pass'], $route[0]);
		} else {
			$out = $route[0] . $params['pass'];
		}

		foreach ($route[2] as $key) {
			$out = str_replace(':' . $key, $params[$key], $out);
			unset($params[$key]);
		}

		// Do something else here for leftover params
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
		if (empty($q)) {
			return null;
		}
		return '?' . $q;
	}
/**
 * Returns the route matching the current request URL
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
 * removes the plugin name from the base url
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
 * @return void
 */
	function parseExtensions() {
		$_this =& Router::getInstance();
		$_this->__parseExtensions = true;
	}
}

?>