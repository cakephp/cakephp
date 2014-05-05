<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Error;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\RouteCollection;
use Cake\Routing\Route\Route;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * Parses the request URL into controller, action, and parameters. Uses the connected routes
 * to match the incoming URL string to parameters that will allow the request to be dispatched. Also
 * handles converting parameter lists into URL strings, using the connected routes. Routing allows you to decouple
 * the way the world interacts with your application (URLs) and the implementation (controllers and actions).
 *
 * ### Connecting routes
 *
 * Connecting routes is done using Router::connect(). When parsing incoming requests or reverse matching
 * parameters, routes are enumerated in the order they were connected. You can modify the order of connected
 * routes using Router::promote(). For more information on routes and how to connect them see Router::connect().
 *
 */
class Router {

/**
 * RouteCollection object containing all the connected routes.
 *
 * @var \Cake\Routing\RouteCollection
 */
	protected static $_routes;

/**
 * Have routes been loaded
 *
 * @var bool
 */
	public static $initialized = false;

/**
 * Contains the base string that will be applied to all generated URLs
 * For example `https://example.com`
 *
 * @var string
 */
	protected static $_fullBaseUrl;

/**
 * List of action prefixes used in connected routes.
 * Includes admin prefix
 *
 * @var array
 */
	protected static $_prefixes = [];

/**
 * List of valid extensions to parse from a URL. If null, any extension is allowed.
 *
 * @var array
 */
	protected static $_validExtensions = [];

/**
 * Regular expression for action names
 *
 * @var string
 */
	const ACTION = 'index|show|add|create|edit|update|remove|del|delete|view|item';

/**
 * Regular expression for years
 *
 * @var string
 */
	const YEAR = '[12][0-9]{3}';

/**
 * Regular expression for months
 *
 * @var string
 */
	const MONTH = '0[1-9]|1[012]';

/**
 * Regular expression for days
 *
 * @var string
 */
	const DAY = '0[1-9]|[12][0-9]|3[01]';

/**
 * Regular expression for auto increment IDs
 *
 * @var string
 */
	const ID = '[0-9]+';

/**
 * Regular expression for UUIDs
 *
 * @var string
 */
	const UUID = '[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12}';

/**
 * Named expressions
 *
 * @var array
 */
	protected static $_namedExpressions = array(
		'Action' => Router::ACTION,
		'Year' => Router::YEAR,
		'Month' => Router::MONTH,
		'Day' => Router::DAY,
		'ID' => Router::ID,
		'UUID' => Router::UUID
	);

/**
 * Default HTTP request method => controller action map.
 *
 * @var array
 */
	protected static $_resourceMap = array(
		array('action' => 'index', 'method' => 'GET', 'id' => false),
		array('action' => 'view', 'method' => 'GET', 'id' => true),
		array('action' => 'add', 'method' => 'POST', 'id' => false),
		array('action' => 'edit', 'method' => 'PUT', 'id' => true),
		array('action' => 'delete', 'method' => 'DELETE', 'id' => true),
		array('action' => 'edit', 'method' => 'POST', 'id' => true)
	);

/**
 * List of resource-mapped controllers
 *
 * @var array
 */
	protected static $_resourceMapped = [];

/**
 * Maintains the request object stack for the current request.
 * This will contain more than one request object when requestAction is used.
 *
 * @var array
 */
	protected static $_requests = [];

/**
 * Initial state is populated the first time reload() is called which is at the bottom
 * of this file. This is a cheat as get_class_vars() returns the value of static vars even if they
 * have changed.
 *
 * @var array
 */
	protected static $_initialState = [];

/**
 * The stack of URL filters to apply against routing URLs before passing the
 * parameters to the route collection.
 *
 * @var array
 */
	protected static $_urlFilters = [];

/**
 * Default route class to use
 *
 * @var string
 */
	protected static $_routeClass = 'Cake\Routing\Route\Route';

/**
 * Set the default route class to use or return the current one
 *
 * @param string $routeClass to set as default
 * @return mixed void|string
 * @throws \Cake\Error\Exception
 */
	public static function defaultRouteClass($routeClass = null) {
		if ($routeClass === null) {
			return static::$_routeClass;
		}

		static::$_routeClass = static::_validateRouteClass($routeClass);
	}

/**
 * Validates that the passed route class exists and is a subclass of Cake Route
 *
 * @param string $routeClass Route class name
 * @return string
 * @throws \Cake\Error\Exception
 */
	protected static function _validateRouteClass($routeClass) {
		if (
			$routeClass != 'Cake\Routing\Route\Route' &&
			(!class_exists($routeClass) || !is_subclass_of($routeClass, 'Cake\Routing\Route\Route'))
		) {
			throw new Error\Exception('Route class not found, or route class is not a subclass of Cake\Routing\Route\Route');
		}
		return $routeClass;
	}

/**
 * Sets the Routing prefixes.
 *
 * @return void
 */
	protected static function _setPrefixes() {
		$routing = Configure::read('Routing');
		if (!empty($routing['prefixes'])) {
			static::$_prefixes = array_merge(static::$_prefixes, (array)$routing['prefixes']);
		}
	}

/**
 * Gets the named route patterns for use in app/Config/routes.php
 *
 * @return array Named route elements
 * @see Router::$_namedExpressions
 */
	public static function getNamedExpressions() {
		return static::$_namedExpressions;
	}

/**
 * Resource map getter & setter.
 *
 * Allows you to define the default route configuration for REST routing and
 * Router::mapResources()
 *
 * @param array $resourceMap Resource map
 * @return mixed
 * @see Router::$_resourceMap
 */
	public static function resourceMap($resourceMap = null) {
		if ($resourceMap === null) {
			return static::$_resourceMap;
		}
		static::$_resourceMap = $resourceMap;
	}

/**
 * Connects a new Route in the router.
 *
 * Routes are a way of connecting request URLs to objects in your application.
 * At their core routes are a set or regular expressions that are used to
 * match requests to destinations.
 *
 * Examples:
 *
 * `Router::connect('/:controller/:action/*');`
 *
 * The first parameter will be used as a controller name while the second is
 * used as the action name. The '/*' syntax makes this route greedy in that
 * it will match requests like `/posts/index` as well as requests
 * like `/posts/edit/1/foo/bar`.
 *
 * `Router::connect('/home-page', ['controller' => 'pages', 'action' => 'display', 'home']);`
 *
 * The above shows the use of route parameter defaults. And providing routing
 * parameters for a static route.
 *
 * {{{
 * Router::connect(
 *   '/:lang/:controller/:action/:id',
 *   [],
 *   ['id' => '[0-9]+', 'lang' => '[a-z]{3}']
 * );
 * }}}
 *
 * Shows connecting a route with custom route parameters as well as
 * providing patterns for those parameters. Patterns for routing parameters
 * do not need capturing groups, as one will be added for each route params.
 *
 * $options offers several 'special' keys that have special meaning
 * in the $options array.
 *
 * - `pass` is used to define which of the routed parameters should be shifted
 *   into the pass array. Adding a parameter to pass will remove it from the
 *   regular route array. Ex. `'pass' => array('slug')`.
 * - `routeClass` is used to extend and change how individual routes parse requests
 *   and handle reverse routing, via a custom routing class.
 *   Ex. `'routeClass' => 'SlugRoute'`
 * - `_name` is used to define a specific name for routes. This can be used to optimize
 *   reverse routing lookups. If undefined a name will be generated for each
 *   connected route.
 * - `_ext` is an array of filename extensions that will be parsed out of the url if present.
 *   See {@link Route::parseExtensions()}.
 *
 * You can also add additional conditions for matching routes to the $defaults array.
 * The following conditions can be used:
 *
 * - `[type]` Only match requests for specific content types.
 * - `[method]` Only match requests with specific HTTP verbs.
 * - `[server]` Only match when $_SERVER['SERVER_NAME'] matches the given value.
 *
 * Example of using the `[method]` condition:
 *
 * `Router::connect('/tasks', array('controller' => 'tasks', 'action' => 'index', '[method]' => 'GET'));`
 *
 * The above route will only be matched for GET requests. POST requests will fail to match this route.
 *
 * @param string $route A string describing the template of the route
 * @param array $defaults An array describing the default route parameters. These parameters will be used by default
 *   and can supply routing parameters that are not dynamic. See above.
 * @param array $options An array matching the named elements in the route to regular expressions which that
 *   element should match. Also contains additional parameters such as which routed parameters should be
 *   shifted into the passed arguments, supplying patterns for routing parameters and supplying the name of a
 *   custom routing class.
 * @see routes
 * @return void
 * @throws \Cake\Error\Exception
 */
	public static function connect($route, $defaults = [], $options = []) {
		static::$initialized = true;

		$defaults += ['plugin' => null];
		if (empty($options['action'])) {
			$defaults += array('action' => 'index');
		}
		if (empty($options['_ext'])) {
			$options['_ext'] = static::$_validExtensions;
		}
		$routeClass = static::$_routeClass;
		if (isset($options['routeClass'])) {
			$routeClass = App::classname($options['routeClass'], 'Routing/Route');
			$routeClass = static::_validateRouteClass($routeClass);
			unset($options['routeClass']);
		}
		if ($routeClass === 'Cake\Routing\Route\RedirectRoute' && isset($defaults['redirect'])) {
			$defaults = $defaults['redirect'];
		}
		static::$_routes->add(new $routeClass($route, $defaults, $options));
	}

/**
 * Connects a new redirection Route in the router.
 *
 * Redirection routes are different from normal routes as they perform an actual
 * header redirection if a match is found. The redirection can occur within your
 * application or redirect to an outside location.
 *
 * Examples:
 *
 * `Router::redirect('/home/*', array('controller' => 'posts', 'action' => 'view'));`
 *
 * Redirects /home/* to /posts/view and passes the parameters to /posts/view. Using an array as the
 * redirect destination allows you to use other routes to define where an URL string should be redirected to.
 *
 * `Router::redirect('/posts/*', 'http://google.com', array('status' => 302));`
 *
 * Redirects /posts/* to http://google.com with a HTTP status of 302
 *
 * ### Options:
 *
 * - `status` Sets the HTTP status (default 301)
 * - `persist` Passes the params to the redirected route, if it can. This is useful with greedy routes,
 *   routes that end in `*` are greedy. As you can remap URLs and not loose any passed args.
 *
 * @param string $route A string describing the template of the route
 * @param array $url An URL to redirect to. Can be a string or a Cake array-based URL
 * @param array $options An array matching the named elements in the route to regular expressions which that
 *   element should match. Also contains additional parameters such as which routed parameters should be
 *   shifted into the passed arguments. As well as supplying patterns for routing parameters.
 * @see routes
 * @return array Array of routes
 */
	public static function redirect($route, $url, $options = []) {
		$options['routeClass'] = 'Cake\Routing\Route\RedirectRoute';
		if (is_string($url)) {
			$url = array('redirect' => $url);
		}
		return static::connect($route, $url, $options);
	}

/**
 * Generate REST resource routes for the given controller(s).
 *
 * A quick way to generate a default routes to a set of REST resources (controller(s)).
 *
 * ### Usage
 *
 * Connect resource routes for an app controller:
 *
 * {{{
 * Router::mapResources('Posts');
 * }}}
 *
 * Connect resource routes for the Comment controller in the
 * Comments plugin:
 *
 * {{{
 * Router::mapResources('Comments.Comment');
 * }}}
 *
 * Plugins will create lower_case underscored resource routes. e.g
 * `/comments/comment`
 *
 * Connect resource routes for the Posts controller in the
 * Admin prefix:
 *
 * {{{
 * Router::mapResources('Posts', ['prefix' => 'admin']);
 * }}}
 *
 * Prefixes will create lower_case underscored resource routes. e.g
 * `/admin/posts`
 *
 * ### Options:
 *
 * - 'id' - The regular expression fragment to use when matching IDs. By default, matches
 *    integer values and UUIDs.
 * - 'prefix' - Routing prefix to use for the generated routes. Defaults to ''.
 *   Using this option will create prefixed routes, similar to using Routing.prefixes.
 *
 * @param string|array $controller A controller name or array of controller names (i.e. "Posts" or "ListItems")
 * @param array $options Options to use when generating REST routes
 * @return array Array of mapped resources
 */
	public static function mapResources($controller, $options = []) {
		$options = array_merge(array(
			'connectOptions' => [],
			'id' => static::ID . '|' . static::UUID
		), $options);

		$connectOptions = $options['connectOptions'];
		unset($options['connectOptions']);

		foreach ((array)$controller as $name) {
			list($plugin, $name) = pluginSplit($name);
			$urlName = Inflector::underscore($name);

			if ($plugin) {
				$plugin = Inflector::underscore($plugin);
			}

			$prefix = $ext = null;
			if (!empty($options['prefix'])) {
				$prefix = $options['prefix'];
			}
			if (!empty($options['_ext'])) {
				$ext = $options['_ext'];
			}

			foreach (static::$_resourceMap as $params) {
				$id = $params['id'] ? ':id' : '';
				$url = '/' . implode('/', array_filter(array($prefix, $plugin, $urlName, $id)));
				$params = array(
					'plugin' => $plugin,
					'controller' => $urlName,
					'action' => $params['action'],
					'[method]' => $params['method'],
					'_ext' => $ext
				);
				if ($prefix) {
					$params['prefix'] = $prefix;
				}
				$routeOptions = array_merge(array(
					'id' => $options['id'],
					'pass' => array('id')
				), $connectOptions);
				Router::connect($url, $params, $routeOptions);
			}
			static::$_resourceMapped[] = $urlName;
		}
		return static::$_resourceMapped;
	}

/**
 * Returns the list of prefixes used in connected routes
 *
 * @return array A list of prefixes used in connected routes
 */
	public static function prefixes() {
		if (empty(static::$_prefixes)) {
			return (array)Configure::read('Routing.prefixes');
		}
		return static::$_prefixes;
	}

/**
 * Parses given URL string. Returns 'routing' parameters for that URL.
 *
 * @param string $url URL to be parsed
 * @return array Parsed elements from URL
 */
	public static function parse($url) {
		if (!static::$initialized) {
			static::_loadRoutes();
		}

		if (strlen($url) && strpos($url, '/') !== 0) {
			$url = '/' . $url;
		}
		return static::$_routes->parse($url);
	}

/**
 * Set the route collection object Router should use.
 *
 * @param \Cake\Routing\RouteCollection $routes
 * @return void
 */
	public static function setRouteCollection(RouteCollection $routes) {
		static::$_routes = $routes;
	}

/**
 * Takes parameter and path information back from the Dispatcher, sets these
 * parameters as the current request parameters that are merged with URL arrays
 * created later in the request.
 *
 * Nested requests will create a stack of requests. You can remove requests using
 * Router::popRequest(). This is done automatically when using Object::requestAction().
 *
 * Will accept either a Cake\Network\Request object or an array of arrays. Support for
 * accepting arrays may be removed in the future.
 *
 * @param \Cake\Network\Request|array $request Parameters and path information or a Cake\Network\Request object.
 * @return void
 */
	public static function setRequestInfo($request) {
		if ($request instanceof Request) {
			static::pushRequest($request);
		} else {
			$requestData = $request;
			$requestData += array([], []);
			$requestData[0] += array(
				'controller' => false,
				'action' => false,
				'plugin' => null
			);
			$request = new Request();
			$request->addParams($requestData[0])->addPaths($requestData[1]);
			static::pushRequest($request);
		}
	}

/**
 * Push a request onto the request stack. Pushing a request
 * sets the request context used when generating URLs.
 *
 * @param \Cake\Network\Request $request
 * @return void
 */
	public static function pushRequest(Request $request) {
		static::$_requests[] = $request;
		static::$_routes->setContext($request);
	}

/**
 * Pops a request off of the request stack.  Used when doing requestAction
 *
 * @return \Cake\Network\Request The request removed from the stack.
 * @see Router::pushRequest()
 * @see Object::requestAction()
 */
	public static function popRequest() {
		$removed = array_pop(static::$_requests);
		$last = end(static::$_requests);
		if ($last) {
			static::$_routes->setContext($last);
			reset(static::$_requests);
		}
		return $removed;
	}

/**
 * Get the current request object, or the first one.
 *
 * @param bool $current True to get the current request, or false to get the first one.
 * @return \Cake\Network\Request|null.
 */
	public static function getRequest($current = false) {
		if ($current) {
			return end(static::$_requests);
		}
		return isset(static::$_requests[0]) ? static::$_requests[0] : null;
	}

/**
 * Reloads default Router settings. Resets all class variables and
 * removes all connected routes.
 *
 * @return void
 */
	public static function reload() {
		if (empty(static::$_initialState)) {
			static::$_initialState = get_class_vars(get_called_class());
			static::_setPrefixes();
			static::$_routes = new RouteCollection();
			return;
		}
		foreach (static::$_initialState as $key => $val) {
			if ($key != '_initialState') {
				static::${$key} = $val;
			}
		}
		static::_setPrefixes();
		static::$_routes = new RouteCollection();
	}

/**
 * Promote a route (by default, the last one added) to the beginning of the list
 *
 * @param int $which A zero-based array index representing the route to move. For example,
 *    if 3 routes have been added, the last route would be 2.
 * @return bool Returns false if no route exists at the position specified by $which.
 */
	public static function promote($which = null) {
		return static::$_routes->promote($which);
	}

/**
 * Add a URL filter to Router.
 *
 * URL filter functions are applied to every array $url provided to
 * Router::url() before the URLs are sent to the route collection.
 *
 * Callback functions should expect the following parameters:
 *
 * - `$params` The URL params being processed.
 * - `$request` The current request.
 *
 * The URL filter function should *always* return the params even if unmodified.
 *
 * ### Usage
 *
 * URL filters allow you to easily implement features like persistent parameters.
 *
 * {{{
 * Router::addUrlFilter(function ($params, $request) {
 *  if (isset($request->params['lang']) && !isset($params['lang']) {
 *    $params['lang'] = $request->params['lang'];
 *  }
 *  return $params;
 * });
 * }}}
 *
 * @param callable $function The function to add
 * @return void
 */
	public static function addUrlFilter(callable $function) {
		static::$_urlFilters[] = $function;
	}

/**
 * Applies all the connected URL filters to the URL.
 *
 * @param array $url The URL array being modified.
 * @return array The modified URL.
 * @see Router::url()
 * @see Router::addUrlFilter()
 */
	protected static function _applyUrlFilters($url) {
		$request = static::getRequest(true);
		foreach (static::$_urlFilters as $filter) {
			$url = $filter($url, $request);
		}
		return $url;
	}

/**
 * Finds URL for specified action.
 *
 * Returns an URL pointing to a combination of controller and action.
 *
 * ### Usage
 *
 * - `Router::url('/posts/edit/1');` Returns the string with the base dir prepended.
 *   This usage does not use reverser routing.
 * - `Router::url(array('controller' => 'posts', 'action' => 'edit'));` Returns a URL
 *   generated through reverse routing.
 * - `Router::url('custom-name', array(...));` Returns a URL generated through reverse
 *   routing.  This form allows you to leverage named routes.
 *
 * There are a few 'special' parameters that can change the final URL string that is generated
 *
 * - `_base` - Set to false to remove the base path from the generated URL. If your application
 *   is not in the root directory, this can be used to generate URLs that are 'cake relative'.
 *   cake relative URLs are required when using requestAction.
 * - `_scheme` - Set to create links on different schemes like `webcal` or `ftp`. Defaults
 *   to the current scheme.
 * - `_host` - Set the host to use for the link.  Defaults to the current host.
 * - `_port` - Set the port if you need to create links on non-standard ports.
 * - `_full` - If true output of `Router::fullBaseUrl()` will be prepended to generated URLs.
 * - `#` - Allows you to set URL hash fragments.
 * - `ssl` - Set to true to convert the generated URL to https, or false to force http.
 *
 * @param string|array $url Cake-relative URL, like "/products/edit/92" or "/presidents/elect/4"
 *   or an array specifying any of the following: 'controller', 'action', 'plugin'
 *   additionally, you can provide routed elements or query string parameters.
 * @param bool|array $options If (bool) true, the full base URL will be prepended to the result.
 *   If an array accepts the following keys.  If used with a named route you can provide
 *   a list of query string parameters.
 * @return string Full translated URL with base path.
 * @throws \Cake\Error\Exception When the route name is not found
 */
	public static function url($url = null, $options = []) {
		if (!static::$initialized) {
			static::_loadRoutes();
		}

		$full = false;
		if (is_bool($options)) {
			list($full, $options) = array($options, []);
		}
		$urlType = gettype($url);
		$hasLeadingSlash = $plainString = false;

		if ($urlType === 'string') {
			$plainString = (
				strpos($url, 'javascript:') === 0 ||
				strpos($url, 'mailto:') === 0 ||
				strpos($url, 'tel:') === 0 ||
				strpos($url, 'sms:') === 0 ||
				strpos($url, '#') === 0 ||
				strpos($url, '?') === 0 ||
				strpos($url, '//') === 0 ||
				strpos($url, '://') !== false
			);

			$hasLeadingSlash = isset($url[0]) ? $url[0] === '/' : false;
		}

		$params = array(
			'plugin' => null,
			'controller' => null,
			'action' => 'index',
			'_ext' => null
		);
		$here = $base = $output = $frag = null;

		$request = static::getRequest(true);
		if ($request) {
			$params = $request->params;
			$here = $request->here;
			$base = $request->base;
		}
		if (!isset($base)) {
			$base = Configure::read('App.base');
		}

		if (empty($url)) {
			$output = isset($here) ? $here : '/';
			if ($full) {
				$output = static::fullBaseUrl() . $base . $output;
			}
			return $output;
		} elseif ($urlType === 'array') {
			if (isset($url['_full']) && $url['_full'] === true) {
				$full = true;
				unset($url['_full']);
			}
			// Compatibility for older versions.
			if (isset($url['?'])) {
				$q = $url['?'];
				unset($url['?']);
				$url = array_merge($url, $q);
			}
			if (isset($url['#'])) {
				$frag = '#' . $url['#'];
				unset($url['#']);
			}
			if (isset($url['ext'])) {
				$url['_ext'] = $url['ext'];
				unset($url['ext']);
			}
			if (isset($url['ssl'])) {
				$url['_scheme'] = ($url['ssl'] == true) ? 'https' : 'http';
				unset($url['ssl']);
			}

			// Copy the current action if the controller is the current one.
			if (
				empty($url['action']) &&
				(empty($url['controller']) || $params['controller'] === $url['controller'])
			) {
				$url['action'] = $params['action'];
			}

			// Keep the current prefix around if none set.
			if (isset($params['prefix']) && !isset($url['prefix'])) {
				$url['prefix'] = $params['prefix'];
			}

			$url += array(
				'plugin' => $params['plugin'],
				'controller' => $params['controller'],
				'action' => 'index',
				'_ext' => $params['_ext']

			);
			$url = static::_applyUrlFilters($url);
			$output = static::$_routes->match($url);
		} elseif (
			$urlType === 'string' &&
			!$hasLeadingSlash &&
			!$plainString
		) {
			// named route.
			$route = static::$_routes->get($url);
			if (!$route) {
				throw new Error\Exception(sprintf(
					'No route matching the name "%s" was found.',
					$url
				));
			}
			$url = $options +
				$route->defaults +
				array('_name' => $url);
			$url = static::_applyUrlFilters($url);
			$output = static::$_routes->match($url);
		} else {
			// String urls.
			if ($plainString) {
				return $url;
			}
			$output = $base . $url;
		}
		$protocol = preg_match('#^[a-z][a-z0-9+\-.]*\://#i', $output);
		if ($protocol === 0) {
			$output = str_replace('//', '/', '/' . $output);
			if ($full) {
				$output = static::fullBaseUrl() . $output;
			}
		}
		return $output . $frag;
	}

/**
 * Sets the full base URL that will be used as a prefix for generating
 * fully qualified URLs for this application. If not parameters are passed,
 * the currently configured value is returned.
 *
 * ## Note:
 *
 * If you change the configuration value ``App.fullBaseUrl`` during runtime
 * and expect the router to produce links using the new setting, you are
 * required to call this method passing such value again.
 *
 * @param string $base the prefix for URLs generated containing the domain.
 * For example: ``http://example.com``
 * @return string
 */
	public static function fullBaseUrl($base = null) {
		if ($base !== null) {
			static::$_fullBaseUrl = $base;
			Configure::write('App.fullBaseUrl', $base);
		}
		if (empty(static::$_fullBaseUrl)) {
			static::$_fullBaseUrl = Configure::read('App.fullBaseUrl');
		}
		return static::$_fullBaseUrl;
	}

/**
 * Reverses a parsed parameter array into a string.
 *
 * Works similarly to Router::url(), but since parsed URL's contain additional
 * 'pass' as well as 'url.url' keys. Those keys need to be specially
 * handled in order to reverse a params array into a string URL.
 *
 * This will strip out 'autoRender', 'bare', 'requested', and 'return' param names as those
 * are used for CakePHP internals and should not normally be part of an output URL.
 *
 * @param \Cake\Network\Request|array $params The params array or
 *     Cake\Network\Request object that needs to be reversed.
 * @param bool $full Set to true to include the full URL including the
 *     protocol when reversing the URL.
 * @return string The string that is the reversed result of the array
 */
	public static function reverse($params, $full = false) {
		$url = [];
		if ($params instanceof Request) {
			$url = $params->query;
			$params = $params->params;
		} elseif (isset($params['url'])) {
			$url = $params['url'];
		}
		$pass = isset($params['pass']) ? $params['pass'] : [];

		unset(
			$params['pass'], $params['paging'], $params['models'], $params['url'], $url['url'],
			$params['autoRender'], $params['bare'], $params['requested'], $params['return'],
			$params['_Token']
		);
		$params = array_merge($params, $pass);
		if (!empty($url)) {
			$params['?'] = $url;
		}
		return Router::url($params, $full);
	}

/**
 * Normalizes an URL for purposes of comparison.
 *
 * Will strip the base path off and replace any double /'s.
 * It will not unify the casing and underscoring of the input value.
 *
 * @param array|string $url URL to normalize Either an array or a string URL.
 * @return string Normalized URL
 */
	public static function normalize($url = '/') {
		if (is_array($url)) {
			$url = Router::url($url);
		}
		if (preg_match('/^[a-z\-]+:\/\//', $url)) {
			return $url;
		}
		$request = Router::getRequest();

		if (!empty($request->base) && stristr($url, $request->base)) {
			$url = preg_replace('/^' . preg_quote($request->base, '/') . '/', '', $url, 1);
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
 * Set/add valid extensions. Instructs the router to parse out file extensions
 * from the URL. For example, http://example.com/posts.rss would yield an file
 * extension of "rss". The file extension itself is made available in the
 * controller as `$this->params['_ext']`, and is used by the RequestHandler
 * component to automatically switch to alternate layouts and templates, and
 * load helpers corresponding to the given content, i.e. RssHelper. Switching
 * layouts and helpers requires that the chosen extension has a defined mime type
 * in `Cake\Network\Response`.
 *
 * An array of valid extension can be passed to this method. If called without
 * any parameters it will return current list of set extensions.
 *
 * @param array|string $extensions List of extensions to be added as valid extension
 * @param bool $merge Default true will merge extensions. Set to false to override
 *   current extensions
 * @return array
 */
	public static function parseExtensions($extensions = null, $merge = true) {
		if ($extensions === null) {
			return static::$_validExtensions;
		}
		$extensions = (array)$extensions;
		if ($merge) {
			$extensions = array_merge(static::$_validExtensions, $extensions);
		}
		static::$_routes->parseExtensions($extensions);
		return static::$_validExtensions = $extensions;
	}

/**
 * Get the list of extensions that can be parsed by Router.
 *
 * To add / update extensions use `Router::parseExtensions()`
 *
 * @return array Array of extensions Router is configured to parse.
 */
	public static function extensions() {
		if (!static::$initialized) {
			static::_loadRoutes();
		}

		return static::$_validExtensions;
	}

/**
 * Provides legacy support for named parameters on incoming URLs.
 *
 * Checks the passed parameters for elements containing `$options['separator']`
 * Those parameters are split and parsed as if they were old style named parameters.
 *
 * The parsed parameters will be moved from params['pass'] to params['named'].
 *
 * ### Options
 *
 * - `separator` The string to use as a separator.  Defaults to `:`.
 *
 * @param Request $request The request object to modify.
 * @param array $options The array of options.
 * @return \Cake\Network\Request The modified request
 */
	public static function parseNamedParams(Request $request, $options = []) {
		$options += array('separator' => ':');
		if (empty($request->params['pass'])) {
			$request->params['named'] = [];
			return $request;
		}
		$named = [];
		foreach ($request->params['pass'] as $key => $value) {
			if (strpos($value, $options['separator']) === false) {
				continue;
			}
			unset($request->params['pass'][$key]);
			list($key, $value) = explode($options['separator'], $value, 2);

			if (preg_match_all('/\[([A-Za-z0-9_-]+)?\]/', $key, $matches, PREG_SET_ORDER)) {
				$matches = array_reverse($matches);
				$parts = explode('[', $key);
				$key = array_shift($parts);
				$arr = $value;
				foreach ($matches as $match) {
					if (empty($match[1])) {
						$arr = array($arr);
					} else {
						$arr = array(
							$match[1] => $arr
						);
					}
				}
				$value = $arr;
			}
			$named = array_merge_recursive($named, array($key => $value));
		}
		$request->params['named'] = $named;
		return $request;
	}

/**
 * Loads route configuration
 *
 * @return void
 */
	protected static function _loadRoutes() {
		static::$initialized = true;
		include APP . 'Config/routes.php';
	}

}

//Save the initial state
Router::reload();
