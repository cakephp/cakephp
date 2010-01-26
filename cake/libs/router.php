<?php
/**
 * Parses the request URL into controller, action, and parameters.
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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Parses the request URL into controller, action, and parameters.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class Router {

/**
 * Array of routes connected with Router::connect()
 *
 * @var array
 * @access public
 */
	var $routes = array();

/**
 * List of action prefixes used in connected routes.
 * Includes admin prefix
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
 * Keeps track of whether the connection of default routes is enabled or disabled.
 *
 * @var boolean
 * @access private
 */
	var $__connectDefaults = true;

/**
 * Constructor for Router.
 * Builds __prefixes
 *
 * @return void
 */
	function Router() {
		$this->__setPrefixes();
	}

/**
 * Sets the Routing prefixes. Includes compatibilty for existing Routing.admin
 * configurations.
 *
 * @return void
 * @access private
 * @todo Remove support for Routing.admin in future versions.
 */
	function __setPrefixes() {
		$routing = Configure::read('Routing');
		if (!empty($routing['admin'])) {
			$this->__prefixes[] = $routing['admin'];
		}
		if (!empty($routing['prefixes'])) {
			$this->__prefixes = array_merge($this->__prefixes, (array)$routing['prefixes']);
		}
	}

/**
 * Gets a reference to the Router object instance
 *
 * @return Router Instance of the Router.
 * @access public
 * @static
 */
	function &getInstance() {
		static $instance = array();

		if (!$instance) {
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
		$self =& Router::getInstance();
		return $self->__named;
	}

/**
 * Connects a new Route in the router.
 *
 * Routes are a way of connecting request urls to objects in your application.  At their core routes
 * are a set or regular expressions that are used to match requests to destinations.
 *
 * Examples:
 *
 * `Router::connect('/:controller/:action/*');`
 *
 * The first parameter will be used as a controller name while the second is used as the action name.
 * the '/*' syntax makes this route greedy in that it will match requests like `/posts/index` as well as requests
 * like `/posts/edit/1/foo/bar`.
 *
 * `Router::connect('/home-page', array('controller' => 'pages', 'action' => 'display', 'home'));`
 *
 * The above shows the use of route parameter defaults. And providing routing parameters for a static route.
 *
 * {{{
 * Router::connect(
 *   '/:lang/:controller/:action/:id',
 *   array(),
 *   array('id' => '[0-9]+', 'lang' => '[a-z]{3}')
 * );
 * }}}
 *
 * Shows connecting a route with custom route parameters as well as providing patterns for those parameters.
 * Patterns for routing parameters do not need capturing groups, as one will be added for each route params.
 *
 * $options offers two 'special' keys. `pass` and `persist` have special meaning in the $options array.
 *
 * `pass` is used to define which of the routed parameters should be shifted into the pass array.  Adding a
 * parameter to pass will remove it from the regular route array. Ex. `'pass' => array('slug')`
 *
 * `persist` is used to define which route parameters should be automatically included when generating
 * new urls. You can override peristent parameters by redifining them in a url or remove them by
 * setting the parameter to `false`.  Ex. `'persist' => array('lang')`
 *
 * @param string $route A string describing the template of the route
 * @param array $defaults An array describing the default route parameters. These parameters will be used by default
 *   and can supply routing parameters that are not dynamic. See above.
 * @param array $options An array matching the named elements in the route to regular expressions which that
 *   element should match.  Also contains additional parameters such as which routed parameters should be
 *   shifted into the passed arguments. As well as supplying patterns for routing parameters.
 * @see routes
 * @return array Array of routes
 * @access public
 * @static
 */
	function connect($route, $defaults = array(), $options = array()) {
		$self =& Router::getInstance();

		foreach ($self->__prefixes as $prefix) {
			if (isset($defaults[$prefix])) {
				$defaults['prefix'] = $prefix;
				break;
			}
		}
		if (isset($defaults['prefix'])) {
			$self->__prefixes[] = $defaults['prefix'];
			$self->__prefixes = array_keys(array_flip($self->__prefixes));
		}
		$defaults += array('action' => 'index', 'plugin' => null);
		$routeClass = 'CakeRoute';
		if (isset($options['routeClass'])) {
			$routeClass = $options['routeClass'];
			unset($options['routeClass']);
		}
		//TODO 2.0 refactor this to use a string class name, throw exception, and then construct.
		$Route =& new $routeClass($route, $defaults, $options);
		if ($routeClass !== 'CakeRoute' && !is_subclass_of($Route, 'CakeRoute')) {
			trigger_error(__('Route classes must extend CakeRoute', true), E_USER_WARNING);
			return false;
		}
		$self->routes[] =& $Route;
		return $self->routes;
	}

/**
 * Specifies what named parameters CakePHP should be parsing. The most common setups are:
 *
 * Do not parse any named parameters:
 *
 * {{{ Router::connectNamed(false); }}}
 *
 * Parse only default parameters used for CakePHP's pagination:
 *
 * {{{ Router::connectNamed(false, array('default' => true)); }}}
 *
 * Parse only the page parameter if its value is a number:
 *
 * {{{ Router::connectNamed(array('page' => '[\d]+'), array('default' => false, 'greedy' => false)); }}}
 *
 * Parse only the page parameter no mater what.
 *
 * {{{ Router::connectNamed(array('page'), array('default' => false, 'greedy' => false)); }}}
 *
 * Parse only the page parameter if the current action is 'index'.
 *
 * {{{
 * Router::connectNamed(
 *    array('page' => array('action' => 'index')),
 *    array('default' => false, 'greedy' => false)
 * );
 * }}}
 *
 * Parse only the page parameter if the current action is 'index' and the controller is 'pages'.
 *
 * {{{
 * Router::connectNamed(
 *    array('page' => array('action' => 'index', 'controller' => 'pages')),
 *    array('default' => false, 'greedy' => false)
 * ); 
 * }}}
 *
 * @param array $named A list of named parameters. Key value pairs are accepted where values are 
 *    either regex strings to match, or arrays as seen above.
 * @param array $options Allows to control all settings: separator, greedy, reset, default
 * @return array
 * @access public
 * @static
 */
	function connectNamed($named, $options = array()) {
		$self =& Router::getInstance();

		if (isset($options['argSeparator'])) {
			$self->named['separator'] = $options['argSeparator'];
			unset($options['argSeparator']);
		}

		if ($named === true || $named === false) {
			$options = array_merge(array('default' => $named, 'reset' => true, 'greedy' => $named), $options);
			$named = array();
		} else {
			$options = array_merge(array('default' => false, 'reset' => false, 'greedy' => true), $options);
		}

		if ($options['reset'] == true || $self->named['rules'] === false) {
			$self->named['rules'] = array();
		}

		if ($options['default']) {
			$named = array_merge($named, $self->named['default']);
		}

		foreach ($named as $key => $val) {
			if (is_numeric($key)) {
				$self->named['rules'][$val] = true;
			} else {
				$self->named['rules'][$key] = $val;
			}
		}
		$self->named['greedy'] = $options['greedy'];
		return $self->named;
	}

/**
 * Tell router to connect or not connect the default routes.
 *
 * If default routes are disabled all automatic route generation will be disabled
 * and you will need to manually configure all the routes you want.
 *
 * @param boolean $connect Set to true or false depending on whether you want or don't want default routes.
 * @return void
 * @access public
 * @static
 */
	function defaults($connect = true) {
		$self =& Router::getInstance();
		$self->__connectDefaults = $connect;
	}

/**
 * Creates REST resource routes for the given controller(s)
 *
 * ### Options:
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
		$self =& Router::getInstance();
		$options = array_merge(array('prefix' => '/', 'id' => $self->__named['ID'] . '|' . $self->__named['UUID']), $options);
		$prefix = $options['prefix'];

		foreach ((array)$controller as $ctlName) {
			$urlName = Inflector::underscore($ctlName);

			foreach ($self->__resourceMap as $params) {
				extract($params);
				$url = $prefix . $urlName . (($id) ? '/:id' : '');

				Router::connect($url,
					array('controller' => $urlName, 'action' => $action, '[method]' => $params['method']),
					array('id' => $options['id'], 'pass' => array('id'))
				);
			}
			$self->__resourceMapped[] = $urlName;
		}
	}

/**
 * Returns the list of prefixes used in connected routes
 *
 * @return array A list of prefixes used in connected routes
 * @access public
 * @static
 */
	function prefixes() {
		$self =& Router::getInstance();
		return $self->__prefixes;
	}

/**
 * Parses given URL and returns an array of controller, action and parameters
 * taken from that URL.
 *
 * @param string $url URL to be parsed
 * @return array Parsed elements from URL
 * @access public
 * @static
 */
	function parse($url) {
		$self =& Router::getInstance();
		if (!$self->__defaultsMapped && $self->__connectDefaults) {
			$self->__connectDefaultRoutes();
		}
		$out = array(
			'pass' => array(),
			'named' => array(),
		);
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
		extract($self->__parseExtension($url));

		for ($i = 0, $len = count($self->routes); $i < $len; $i++) {
			$route =& $self->routes[$i];
			if (($r = $route->parse($url)) !== false) {
				$self->__currentRoute[] =& $route;

				$params = $route->options;
				$argOptions = array();

				if (array_key_exists('named', $params)) {
					$argOptions['named'] = $params['named'];
					unset($params['named']);
				}
				if (array_key_exists('greedy', $params)) {
					$argOptions['greedy'] = $params['greedy'];
					unset($params['greedy']);
				}
				$out = $r;

				if (isset($out['_args_'])) {
					$argOptions['context'] = array('action' => $out['action'], 'controller' => $out['controller']);
					$parsedArgs = $self->getArgs($out['_args_'], $argOptions);
					$out['pass'] = array_merge($out['pass'], $parsedArgs['pass']);
					$out['named'] = $parsedArgs['named'];
					unset($out['_args_']);
				}

				if (isset($params['pass'])) {
					$j = count($params['pass']);
					while($j--) {
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
 * Connects the default, built-in routes, including prefix and plugin routes. The following routes are created
 * in the order below:
 *
 * - `/:prefix/:plugin/:controller`
 * - `/:prefix/:plugin/:controller/:action/*`
 * - `/:prefix/:controller`
 * - `/:prefix/:controller/:action/*`
 * - `/:plugin/:controller`
 * - `/:plugin/:controller/:action/*`
 * - `/:controller'
 * - `/:controller/:action/*'
 *
 * A prefix route is generated for each Routing.prefixes declared in core.php. You can disable the
 * connection of default routes with Router::defaults().
 *
 * @return void
 * @access private
 */
	function __connectDefaultRoutes() {
		if ($plugins = App::objects('plugin')) {
			foreach ($plugins as $key => $value) {
				$plugins[$key] = Inflector::underscore($value);
			}
			$match = array('plugin' => implode('|', $plugins));

			foreach ($this->__prefixes as $prefix) {
				$params = array('prefix' => $prefix, $prefix => true);
				$indexParams = $params + array('action' => 'index');
				$this->connect("/{$prefix}/:plugin/:controller", $indexParams, $match);
				$this->connect("/{$prefix}/:plugin/:controller/:action/*", $params, $match);
			}
			$this->connect('/:plugin/:controller', array('action' => 'index'), $match);
			$this->connect('/:plugin/:controller/:action/*', array(), $match);
		}

		foreach ($this->__prefixes as $prefix) {
			$params = array('prefix' => $prefix, $prefix => true);
			$indexParams = $params + array('action' => 'index');
			$this->connect("/{$prefix}/:controller", $indexParams);
			$this->connect("/{$prefix}/:controller/:action/*", $params);
		}
		$this->connect('/:controller', array('action' => 'index'));
		$this->connect('/:controller/:action/*');

		if ($this->named['rules'] === false) {
			$this->connectNamed(true);
		}
		$this->__defaultsMapped = true;
	}

/**
 * Takes parameter and path information back from the Dispatcher, sets these
 * parameters as the current request parameters that are merged with url arrays 
 * created later in the request.
 *
 * @param array $params Parameters and path information
 * @return void
 * @access public
 * @static
 */
	function setRequestInfo($params) {
		$self =& Router::getInstance();
		$defaults = array('plugin' => null, 'controller' => null, 'action' => null);
		$params[0] = array_merge($defaults, (array)$params[0]);
		$params[1] = array_merge($defaults, (array)$params[1]);
		list($self->__params[], $self->__paths[]) = $params;

		if (count($self->__paths)) {
			if (isset($self->__paths[0]['namedArgs'])) {
				foreach ($self->__paths[0]['namedArgs'] as $arg => $value) {
					$self->named['rules'][$arg] = true;
				}
			}
		}
	}

/**
 * Gets parameter information
 *
 * @param boolean $current Get current request parameter, useful when using requestAction
 * @return array Parameter information
 * @access public
 * @static
 */
	function getParams($current = false) {
		$self =& Router::getInstance();
		if ($current) {
			return $self->__params[count($self->__params) - 1];
		}
		if (isset($self->__params[0])) {
			return $self->__params[0];
		}
		return array();
	}

/**
 * Gets URL parameter by name
 *
 * @param string $name Parameter name
 * @param boolean $current Current parameter, useful when using requestAction
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
 * @param boolean $current Current parameter, useful when using requestAction
 * @return array
 * @access public
 * @static
 */
	function getPaths($current = false) {
		$self =& Router::getInstance();
		if ($current) {
			return $self->__paths[count($self->__paths) - 1];
		}
		if (!isset($self->__paths[0])) {
			return array('base' => null);
		}
		return $self->__paths[0];
	}

/**
 * Reloads default Router settings.  Resets all class variables and 
 * removes all connected routes.
 *
 * @access public
 * @return void
 * @static
 */
	function reload() {
		$self =& Router::getInstance();
		foreach (get_class_vars('Router') as $key => $val) {
			$self->{$key} = $val;
		}
		$self->__setPrefixes();
	}

/**
 * Promote a route (by default, the last one added) to the beginning of the list
 *
 * @param $which A zero-based array index representing the route to move. For example,
 *    if 3 routes have been added, the last route would be 2.
 * @return boolean Retuns false if no route exists at the position specified by $which.
 * @access public
 * @static
 */
	function promote($which = null) {
		$self =& Router::getInstance();
		if ($which === null) {
			$which = count($self->routes) - 1;
		}
		if (!isset($self->routes[$which])) {
			return false;
		}
		$route =& $self->routes[$which];
		unset($self->routes[$which]);
		array_unshift($self->routes, $route);
		return true;
	}

/**
 * Finds URL for specified action.
 *
 * Returns an URL pointing to a combination of controller and action. Param
 * $url can be:
 *
 * - Empty - the method will find address to actuall controller/action.
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
		$self =& Router::getInstance();
		$defaults = $params = array('plugin' => null, 'controller' => null, 'action' => 'index');

		if (is_bool($full)) {
			$escape = false;
		} else {
			extract($full + array('escape' => false, 'full' => false));
		}

		if (!empty($self->__params)) {
			if (isset($this) && !isset($this->params['requested'])) {
				$params = $self->__params[0];
			} else {
				$params = end($self->__params);
			}
			if (isset($params['prefix']) && strpos($params['action'], $params['prefix']) === 0) {
				$params['action'] = substr($params['action'], strlen($params['prefix']) + 1);
			}
		}
		$path = array('base' => null);

		if (!empty($self->__paths)) {
			if (isset($this) && !isset($this->params['requested'])) {
				$path = $self->__paths[0];
			} else {
				$path = end($self->__paths);
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

			$prefixExists = (array_intersect_key($url, array_flip($self->__prefixes)));
			foreach ($self->__prefixes as $prefix) {
				if (!isset($url[$prefix]) && !empty($params[$prefix]) && !$prefixExists) {
					$url[$prefix] = true;
				} elseif (isset($url[$prefix]) && !$url[$prefix]) {
					unset($url[$prefix]);
				}
			}

			$url += array('controller' => $params['controller'], 'plugin' => $params['plugin']);

			if (isset($url['ext'])) {
				$extension = '.' . $url['ext'];
				unset($url['ext']);
			}
			$match = false;

			for ($i = 0, $len = count($self->routes); $i < $len; $i++) {
				$originalUrl = $url;

				if (isset($self->routes[$i]->options['persist'], $params)) {
					$url = $self->routes[$i]->persistParams($url, $params);
				}

				if ($match = $self->routes[$i]->match($url)) {
					$output = trim($match, '/');
					$url = array();
					break;
				}
				$url = $originalUrl;
			}
			if ($match === false) {
				$output = $self->_handleNoRoute($url);
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
				foreach ($self->__prefixes as $prefix) {
					if (isset($params[$prefix])) {
						$output .= $prefix . '/';
						break;
					}
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

		return $output . $extension . $self->queryString($q, array(), $escape) . $frag;
	}

/**
 * A special fallback method that handles url arrays that cannot match
 * any defined routes.
 *
 * @param array $url A url that didn't match any routes
 * @return string A generated url for the array
 * @access protected
 * @see Router::url()
 */
	function _handleNoRoute($url) {
		$named = $args = array();
		$skip = array_merge(
			array('bare', 'action', 'controller', 'plugin', 'prefix'),
			$this->__prefixes
		);

		$keys = array_values(array_diff(array_keys($url), $skip));
		$count = count($keys);

		// Remove this once parsed URL parameters can be inserted into 'pass'
		for ($i = 0; $i < $count; $i++) {
			if (is_numeric($keys[$i])) {
				$args[] = $url[$keys[$i]];
			} else {
				$named[$keys[$i]] = $url[$keys[$i]];
			}
		}

		list($args, $named) = array(Set::filter($args, true), Set::filter($named, true));
		foreach ($this->__prefixes as $prefix) {
			if (!empty($url[$prefix])) {
				$url['action'] = str_replace($prefix . '_', '', $url['action']);
				break;
			}
		}

		if (empty($named) && empty($args) && (!isset($url['action']) || $url['action'] === 'index')) {
			$url['action'] = null;
		}

		$urlOut = array_filter(array($url['controller'], $url['action']));

		if (isset($url['plugin']) && $url['plugin'] != $url['controller']) {
			array_unshift($urlOut, $url['plugin']);
		}

		foreach ($this->__prefixes as $prefix) {
			if (isset($url[$prefix])) {
				array_unshift($urlOut, $prefix);
				break;
			}
		}
		$output = implode('/', $urlOut);

		if (!empty($args)) {
			$output .= '/' . implode('/', $args);
		}

		if (!empty($named)) {
			foreach ($named as $name => $value) {
				$output .= '/' . $name . $this->named['separator'] . $value;
			}
		}
		return $output;
	}

/**
 * Takes an array of URL parameters and separates the ones that can be used as named arguments
 *
 * @param array $params Associative array of URL parameters.
 * @param string $controller Name of controller being routed.  Used in scoping.
 * @param string $action Name of action being routed.  Used in scoping.
 * @return array
 * @access public
 * @static
 */
	function getNamedElements($params, $controller = null, $action = null) {
		$self =& Router::getInstance();
		$named = array();

		foreach ($params as $param => $val) {
			if (isset($self->named['rules'][$param])) {
				$rule = $self->named['rules'][$param];
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
 * @static
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
		return (!isset($rule['match']) || preg_match('/' . $rule['match'] . '/', $val));
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
 * Reverses a parsed parameter array into a string. Works similarily to Router::url(), but
 * Since parsed URL's contain additional 'pass' and 'named' as well as 'url.url' keys.
 * Those keys need to be specially handled in order to reverse a params array into a string url.
 *
 * @param array $param The params array that needs to be reversed.
 * @return string The string that is the reversed result of the array
 * @access public
 * @static
 */
	function reverse($params) {
		$pass = $params['pass'];
		$named = $params['named'];
		$url = $params['url'];
		unset($params['pass'], $params['named'], $params['paging'], $params['models'], $params['url'], $url['url']);
		$params = array_merge($params, $pass, $named);
		if (!empty($url)) {
			$params['?'] = $url;
		}
		return Router::url($params);
	}

/**
 * Normalizes a URL for purposes of comparison
 *
 * @param mixed $url URL to normalize
 * @return string Normalized URL
 * @access public
 * @static
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
 * @return CakeRoute Matching route object.
 * @access public
 * @static
 */
	function &requestRoute() {
		$self =& Router::getInstance();
		return $self->__currentRoute[0];
	}

/**
 * Returns the route matching the current request (useful for requestAction traces)
 *
 * @return CakeRoute Matching route object.
 * @access public
 * @static
 */
	function &currentRoute() {
		$self =& Router::getInstance();
		return $self->__currentRoute[count($self->__currentRoute) - 1];
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
		$self =& Router::getInstance();
		$self->__parseExtensions = true;
		if (func_num_args() > 0) {
			$self->__validExtensions = func_get_args();
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
		$self =& Router::getInstance();
		$pass = $named = array();
		$args = explode('/', $args);

		$greedy = isset($options['greedy']) ? $options['greedy'] : $self->named['greedy'];
		$context = array();
		if (isset($options['context'])) {
			$context = $options['context'];
		}
		$rules = $self->named['rules'];
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

			$separatorIsPresent = strpos($param, $self->named['separator']) !== false;
			if ((!isset($options['named']) || !empty($options['named'])) && $separatorIsPresent) {
				list($key, $val) = explode($self->named['separator'], $param, 2);
				$hasRule = isset($rules[$key]);
				$passIt = (!$hasRule && !$greedy) || ($hasRule && !$self->matchNamed($key, $val, $rules[$key], $context));
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

/**
 * A single Route used by the Router to connect requests to
 * parameter maps.
 *
 * Not normally created as a standalone.  Use Router::connect() to create
 * Routes for your application.
 *
 * @package cake.libs
 * @since 1.3.0
 * @see Router::connect()
 */
class CakeRoute {

/**
 * An array of named segments in a Route.
 * `/:controller/:action/:id` has 3 key elements
 *
 * @var array
 * @access public
 */
	var $keys = array();

/**
 * An array of additional parameters for the Route.
 *
 * @var array
 * @access public
 */
	var $options = array();

/**
 * Default parameters for a Route
 *
 * @var array
 * @access public
 */
	var $defaults = array();

/**
 * The routes template string.
 *
 * @var string
 * @access public
 */
	var $template = null;

/**
 * Is this route a greedy route?  Greedy routes have a `/*` in their
 * template
 *
 * @var string
 * @access protected
 */
	var $_greedy = false;

/**
 * The compiled route regular expresssion
 *
 * @var string
 * @access protected
 */
	var $_compiledRoute = null;

/**
 * HTTP header shortcut map.  Used for evaluating header-based route expressions.
 *
 * @var array
 * @access private
 */
	var $__headerMap = array(
		'type' => 'content_type',
		'method' => 'request_method',
		'server' => 'server_name'
	);

/**
 * Constructor for a Route
 *
 * @param string $template Template string with parameter placeholders
 * @param array $defaults Array of defaults for the route.
 * @param string $params Array of parameters and additional options for the Route
 * @return void
 * @access public
 */
	function CakeRoute($template, $defaults = array(), $options = array()) {
		$this->template = $template;
		$this->defaults = (array)$defaults;
		$this->options = (array)$options;
	}

/**
 * Check if a Route has been compiled into a regular expression.
 *
 * @return boolean
 * @access public
 */
	function compiled() {
		return !empty($this->_compiledRoute);
	}

/**
 * Compiles the route's regular expression.  Modifies defaults property so all necessary keys are set
 * and populates $this->names with the named routing elements.
 *
 * @return array Returns a string regular expression of the compiled route.
 * @access public
 */
	function compile() {
		if ($this->compiled()) {
			return $this->_compiledRoute;
		}
		$this->_writeRoute();
		return $this->_compiledRoute;
	}

/**
 * Builds a route regular expression.  Uses the template, defaults and options
 * properties to compile a regular expression that can be used to parse request strings.
 *
 * @return void
 * @access protected
 */
	function _writeRoute() {
		if (empty($this->template) || ($this->template === '/')) {
			$this->_compiledRoute = '#^/*$#';
			$this->keys = array();
			return;
		}
		$route = $this->template;
		$names = $replacements = $search = array();
		$parsed = preg_quote($this->template, '#');

		preg_match_all('#:([A-Za-z0-9_-]+[A-Z0-9a-z])#', $route, $namedElements);
		foreach ($namedElements[1] as $i => $name) {
			if (isset($this->options[$name])) {
				$option = null;
				if ($name !== 'plugin' && array_key_exists($name, $this->defaults)) {
					$option = '?';
				}
				$slashParam = '/\\' . $namedElements[0][$i];
				if (strpos($parsed, $slashParam) !== false) {
					$replacements[] = '(?:/(?P<' . $name . '>' . $this->options[$name] . ')' . $option . ')' . $option;
					$search[] = $slashParam;
				} else {
					$search[] = '\\' . $namedElements[0][$i];
					$replacements[] = '(?:(?P<' . $name . '>' . $this->options[$name] . ')' . $option . ')' . $option;
				}
			} else {
				$replacements[] = '(?:(?P<' . $name . '>[^/]+))';
				$search[] = '\\' . $namedElements[0][$i];
			}
			$names[] = $name;
		}
		if (preg_match('#\/\*$#', $route, $m)) {
			$parsed = preg_replace('#/\\\\\*$#', '(?:/(?P<_args_>.*))?', $parsed);
			$this->_greedy = true;
		}
		$parsed = str_replace($search, $replacements, $parsed);
		$this->_compiledRoute = '#^' . $parsed . '[/]*$#';
		$this->keys = $names;
	}

/**
 * Checks to see if the given URL can be parsed by this route.
 * If the route can be parsed an array of parameters will be returned if not
 * false will be returned. String urls are parsed if they match a routes regular expression.
 *
 * @param string $url The url to attempt to parse.
 * @return mixed Boolean false on failure, otherwise an array or parameters
 * @access public
 */
	function parse($url) {
		if (!$this->compiled()) {
			$this->compile();
		}
		if (!preg_match($this->_compiledRoute, $url, $route)) {
			return false;
		} else {
			foreach ($this->defaults as $key => $val) {
				if ($key[0] === '[' && preg_match('/^\[(\w+)\]$/', $key, $header)) {
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
			array_shift($route);
			$count = count($this->keys);
			for ($i = 0; $i <= $count; $i++) {
				unset($route[$i]);
			}
			$route['pass'] = $route['named'] = array();
			$route += $this->defaults;

			foreach ($route as $key => $value) {
				if (is_integer($key)) {
					$route['pass'][] = $value;
					unset($route[$key]);
				}
			}
			return $route;
		}
	}

/**
 * Apply persistent parameters to a url array. Persistant parameters are a special 
 * key used during route creation to force route parameters to persist when omitted from 
 * a url array.
 *
 * @param array $url The array to apply persistent parameters to.
 * @param array $params An array of persistent values to replace persistent ones.
 * @return array An array with persistent parameters applied.
 * @access public
 */
	function persistParams($url, $params) {
		foreach ($this->options['persist'] as $persistKey) {
			if (array_key_exists($persistKey, $params) && !isset($url[$persistKey])) {
				$url[$persistKey] = $params[$persistKey];
			}
		}
		return $url;
	}

/**
 * Attempt to match a url array.  If the url matches the route parameters + settings, then
 * return a generated string url.  If the url doesn't match the route parameters false will be returned.
 * This method handles the reverse routing or conversion of url arrays into string urls.
 *
 * @param array $url An array of parameters to check matching with.
 * @return mixed Either a string url for the parameters if they match or false.
 * @access public
 */
	function match($url) {
		if (!$this->compiled()) {
			$this->compile();
		}
		$defaults = $this->defaults;

		if (isset($defaults['prefix'])) {
			$url['prefix'] = $defaults['prefix'];
		}

		//check that all the key names are in the url
		$keyNames = array_flip($this->keys);
		if (array_intersect_key($keyNames, $url) != $keyNames) {
			return false;
		}

		$diffUnfiltered = Set::diff($url, $defaults);
		$diff = array();

		foreach ($diffUnfiltered as $key => $var) {
			if ($var === 0 || $var === '0' || !empty($var)) {
				$diff[$key] = $var;
			}
		}

		//if a not a greedy route, no extra params are allowed.
		if (!$this->_greedy && array_diff_key($diff, $keyNames) != array()) {
			return false;
		}

		//remove defaults that are also keys. They can cause match failures
		foreach ($this->keys as $key) {
			unset($defaults[$key]);
		}
		$filteredDefaults = array_filter($defaults);

		//if the difference between the url diff and defaults contains keys from defaults its not a match
		if (array_intersect_key($filteredDefaults, $diffUnfiltered) !== array()) {
			return false;
		}

		$passedArgsAndParams = array_diff_key($diff, $filteredDefaults, $keyNames);
		list($named, $params) = Router::getNamedElements($passedArgsAndParams, $url['controller'], $url['action']);

		//remove any pass params, they have numeric indexes, skip any params that are in the defaults
		$pass = array();
		$i = 0;
		while (isset($url[$i])) {
			if (!isset($diff[$i])) {
				$i++;
				continue;
			}
			$pass[] = $url[$i];
			unset($url[$i], $params[$i]);
			$i++;
		}

		//still some left over parameters that weren't named or passed args, bail.
		if (!empty($params)) {
			return false;
		}

		//check patterns for routed params
		if (!empty($this->options)) {
			foreach ($this->options as $key => $pattern) {
				if (array_key_exists($key, $url) && !preg_match('#^' . $pattern . '$#', $url[$key])) {
					return false;
				}
			}
		}
		return $this->_writeUrl(array_merge($url, compact('pass', 'named')));
	}

/**
 * Converts a matching route array into a url string. Composes the string url using the template
 * used to create the route.
 *
 * @param array $params The params to convert to a string url.
 * @return string Composed route string.
 * @access protected
 */
	function _writeUrl($params) {
		if (isset($params['plugin'], $params['controller']) && $params['plugin'] === $params['controller']) {
			unset($params['controller']);
		}

		if (isset($params['prefix'], $params['action'])) {
			$params['action'] = str_replace($params['prefix'] . '_', '', $params['action']);
			unset($params['prefix']);
		}

		if (is_array($params['pass'])) {
			$params['pass'] = implode('/', $params['pass']);
		}

		$instance =& Router::getInstance();
		$separator = $instance->named['separator'];

		if (!empty($params['named'])) {
			if (is_array($params['named'])) {
				$named = array();
				foreach ($params['named'] as $key => $value) {
					$named[] = $key . $separator . $value;
				}
				$params['pass'] = $params['pass'] . '/' . implode('/', $named);;
			}
		}
		$out = $this->template;

		$search = $replace = array();
		foreach ($this->keys as $key) {
			$string = null;
			if (isset($params[$key])) {
				$string = $params[$key];
			} elseif (strpos($out, $key) != strlen($out) - strlen($key)) {
				$key = $key . '/';
			}
			$search[] = ':' . $key;
			$replace[] = $string;
		}
		$out = str_replace($search, $replace, $out);

		if (strpos($this->template, '*')) {
			$out = str_replace('*', $params['pass'], $out);
		}
		$out = str_replace('//', '/', $out);
		return $out;
	}
}
?>