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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing;

use Cake\Core\App;
use Cake\Error;
use Cake\Routing\Router;
use Cake\Routing\Route\Route;
use Cake\Utility\Inflector;

/**
 * Contains a collection of routes related to a specific path scope.
 * Path scopes can be read with the `path()` method.
 */
class ScopedRouteCollection {

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
 * The extensions that should be set into the routes connected.
 *
 * @var array
 */
	protected $_extensions;

/**
 * The path prefix scope that this collection uses.
 *
 * @var string
 */
	protected $_path;

/**
 * The scope parameters if there are any.
 *
 * @var array
 */
	protected $_params;

/**
 * The routes connected to this collection.
 *
 * @var array
 */
	protected $_routes = [];

/**
 * The hash map of named routes that are in this collection.
 *
 * @var array
 */
	protected $_named = [];

/**
 * Constructor
 *
 * @param string $path The path prefix the scope is for.
 * @param array $params The scope's routing parameters.
 */
	public function __construct($path, array $params = [], array $extensions = []) {
		$this->_path = $path;
		$this->_params = $params;
		$this->_extensions = $extensions;
	}

/**
 * Get or set the extensions in this route collection.
 *
 * Setting extensions does not modify existing routes.
 *
 * @param null|array $extensions Either the extensions to use or null.
 * @return array|void
 */
	public function extensions($extensions = null) {
		if ($extensions === null) {
			return $this->_extensions;
		}
		$this->_extensions = $extensions;
	}

/**
 * Get the path this scope is for.
 *
 * @return string
 */
	public function path() {
		$routeKey = strpos($this->_path, ':');
		if ($routeKey !== false) {
			return substr($this->_path, 0, $routeKey);
		}
		return $this->_path;
	}

/**
 * Get the parameter names/values for this scope.
 *
 * @return string
 */
	public function params() {
		return $this->_params;
	}

/**
 * Get the explicity named routes in the collection.
 *
 * @return array An array of named routes indexed by their name.
 */
	public function named() {
		return $this->_named;
	}

/**
 * Get all the routes in this collection.
 *
 * @return array An array of routes.
 */
	public function routes() {
		return $this->_routes;
	}

/**
 * Get a route by its name.
 *
 * *Note* This method only works on explicitly named routes.
 *
 * @param string $name The name of the route to get.
 * @return false|\Cake\Routing\Route The route.
 */
	public function get($name) {
		if (isset($this->_named[$name])) {
			return $this->_named[$name];
		}
		return false;
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
 * $routes->resources('Posts');
 * }}}
 *
 * Connect resource routes for the Comments controller in the
 * Comments plugin:
 *
 * {{{
 * Router::plugin('Comments', function ($routes) {
 *   $routes->resources('Comments');
 * });
 * }}}
 *
 * Plugins will create lower_case underscored resource routes. e.g
 * `/comments/comments`
 *
 * Connect resource routes for the Articles controller in the
 * Admin prefix:
 *
 * {{{
 * Router::prefix('admin', function ($routes) {
 *   $routes->resources('Articles');
 * });
 * }}}
 *
 * Prefixes will create lower_case underscored resource routes. e.g
 * `/admin/posts`
 *
 * You can create nested resources by passing a callback in:
 *
 * {{{
 * $routes->resources('Articles', function($routes) {
 *   $routes->resources('Comments');
 * });
 * }}}
 *
 * The above would generate both resource routes for `/articles`, and `/articles/:article_id/comments`.
 *
 * ### Options:
 *
 * - 'id' - The regular expression fragment to use when matching IDs. By default, matches
 *    integer values and UUIDs.
 *
 * @param string|array $controller A controller name or array of controller names (i.e. "Posts" or "ListItems")
 * @param array $options Options to use when generating REST routes
 * @param callable $callback An optional callback to be executed in a nested scope. Nested
 *   scopes inherit the existing path and 'id' parameter.
 * @return array Array of mapped resources
 */
	public function resources($name, $options = [], $callback = null) {
		if (is_callable($options) && $callback === null) {
			$callback = $options;
			$options = [];
		}
		$options += array(
			'connectOptions' => [],
			'id' => static::ID . '|' . static::UUID
		);
		$connectOptions = $options['connectOptions'];
		unset($options['connectOptions']);

		$urlName = Inflector::underscore($name);

		$ext = null;
		if (!empty($options['_ext'])) {
			$ext = $options['_ext'];
		}

		foreach (static::$_resourceMap as $params) {
			$id = $params['id'] ? ':id' : '';
			$url = '/' . implode('/', array_filter(array($urlName, $id)));
			$params = array(
				'controller' => $name,
				'action' => $params['action'],
				'[method]' => $params['method'],
				'_ext' => $ext
			);
			$routeOptions = $connectOptions + [
				'id' => $options['id'],
				'pass' => ['id']
			];
			$this->connect($url, $params, $routeOptions);
		}

		if (is_callable($callback)) {
			$idName = Inflector::singularize($urlName) . '_id';
			$path = $this->_path . '/' . $urlName . '/:' . $idName;
			Router::scope($path, $this->params(), $callback);
		}
	}

/**
 * Connects a new Route.
 *
 * Routes are a way of connecting request URLs to objects in your application.
 * At their core routes are a set or regular expressions that are used to
 * match requests to destinations.
 *
 * Examples:
 *
 * `$routes->connect('/:controller/:action/*');`
 *
 * The first parameter will be used as a controller name while the second is
 * used as the action name. The '/*' syntax makes this route greedy in that
 * it will match requests like `/posts/index` as well as requests
 * like `/posts/edit/1/foo/bar`.
 *
 * `$routes->connect('/home-page', ['controller' => 'Pages', 'action' => 'display', 'home']);`
 *
 * The above shows the use of route parameter defaults. And providing routing
 * parameters for a static route.
 *
 * {{{
 * $routes->connect(
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
 *   See {@link ScopedRouteCollection::extensions()}.
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
 * `$routes->connect('/tasks', array('controller' => 'Tasks', 'action' => 'index', '[method]' => 'GET'));`
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
 * @return void
 * @throws \Cake\Error\Exception
 */
	public function connect($route, array $defaults = [], $options = []) {
		if (empty($options['action'])) {
			$defaults += array('action' => 'index');
		}

		if (empty($options['_ext'])) {
			$options['_ext'] = $this->_extensions;
		}

		$route = $this->_makeRoute($route, $defaults, $options);
		if (isset($options['_name'])) {
			$this->_named[$options['_name']] = $route;
		}

		$name = $route->getName();
		if (!isset($this->_routeTable[$name])) {
			$this->_routeTable[$name] = [];
		}
		$this->_routeTable[$name][] = $route;
		$this->_routes[] = $route;
	}

/**
 * Create a route object, or return the provided object.
 *
 * @param string|\Cake\Routing\Route\Route $route The route template or route object.
 * @param array $defaults Default parameters.
 * @param array $options Additional options parameters.
 * @return \Cake\Routing\Route\Route
 * @throws \Cake\Error\Exception when route class or route object is invalid.
 */
	protected function _makeRoute($route, $defaults, $options) {
		if (is_string($route)) {
			$routeClass = 'Cake\Routing\Route\Route';
			if (isset($options['routeClass'])) {
				$routeClass = App::className($options['routeClass'], 'Routing/Route');
			}
			if ($routeClass === false) {
				throw new Error\Exception(sprintf('Cannot find route class %s', $options['routeClass']));
			}
			unset($options['routeClass']);

			$route = str_replace('//', '/', $this->_path . $route);
			if (!is_array($defaults)) {
				debug(\Cake\Utility\Debugger::trace());
			}
			foreach ($this->_params as $param => $val) {
				if (isset($defaults[$param]) && $defaults[$param] !== $val) {
					$msg = 'You cannot define routes that conflict with the scope. ' .
						'Scope had %s = %s, while route had %s = %s';
					throw new Error\Exception(sprintf(
						$msg,
						$param,
						$val,
						$param,
						$defaults[$param]
					));
				}
			}
			$defaults += $this->_params;
			$defaults += ['plugin' => null];

			$route = new $routeClass($route, $defaults, $options);
		}

		if ($route instanceof Route) {
			return $route;
		}
		throw new Error\Exception('Route class not found, or route class is not a subclass of Cake\Routing\Route\Route');
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
 * `$routes->redirect('/home/*', array('controller' => 'posts', 'action' => 'view'));`
 *
 * Redirects /home/* to /posts/view and passes the parameters to /posts/view. Using an array as the
 * redirect destination allows you to use other routes to define where an URL string should be redirected to.
 *
 * `$routes-redirect('/posts/*', 'http://google.com', array('status' => 302));`
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
 * @return array Array of routes
 */
	public function redirect($route, $url, $options = []) {
		$options['routeClass'] = 'Cake\Routing\Route\RedirectRoute';
		if (is_string($url)) {
			$url = array('redirect' => $url);
		}
		return $this->connect($route, $url, $options);
	}

/**
 * Add prefixed routes.
 *
 * This method creates a scoped route collection that includes
 * relevant prefix information.
 *
 * The path parameter is used to generate the routing parameter name.
 * For example a path of `admin` would result in `'prefix' => 'admin'` being
 * applied to all connected routes.
 *
 * You can re-open a prefix as many times as necessary, as well as nest prefixes.
 * Nested prefixes will result in prefix values like `admin/api` which translates
 * to the `Controller\Admin\Api\` namespace.
 *
 * @param string $name The prefix name to use.
 * @param callable $callback The callback to invoke that builds the prefixed routes.
 * @return void
 */
	public function prefix($name, callable $callback) {
		$name = Inflector::underscore($name);
		$path = $this->_path . '/' . $name;
		if (isset($this->_params['prefix'])) {
			$name = $this->_params['prefix'] . '/' . $name;
		}
		$params = ['prefix' => $name] + $this->_params;
		Router::scope($path, $params, $callback);
	}

/**
 * Add plugin routes.
 *
 * This method creates a new scoped route collection that includes
 * relevant plugin information.
 *
 * The plugin name will be inflected to the underscore version to create
 * the routing path. If you want a custom path name, use the `path` option.
 *
 * Routes connected in the scoped collection will have the correct path segment
 * prepended, and have a matching plugin routing key set.
 *
 * @param string $path The path name to use for the prefix.
 * @param array|callable $options Either the options to use, or a callback.
 * @param callable $callback The callback to invoke that builds the plugin routes.
 *   Only required when $options is defined.
 * @return void
 */
	public function plugin($name, $options = [], $callback = null) {
		if ($callback === null) {
			$callback = $options;
			$options = [];
		}
		$params = ['plugin' => $name] + $this->_params;
		if (empty($options['path'])) {
			$options['path'] = '/' . Inflector::underscore($name);
		}
		$options['path'] = $this->_path . $options['path'];
		Router::scope($options['path'], $params, $callback);
	}

/**
 * Takes the URL string and iterates the routes until one is able to parse the route.
 *
 * @param string $url Url to parse.
 * @return array An array of request parameters parsed from the url.
 */
	public function parse($url) {
		$queryParameters = null;
		if (strpos($url, '?') !== false) {
			list($url, $queryParameters) = explode('?', $url, 2);
			parse_str($queryParameters, $queryParameters);
		}
		$out = [];
		for ($i = 0, $len = count($this->_routes); $i < $len; $i++) {
			$r = $this->_routes[$i]->parse($url);
			if ($r === false) {
				continue;
			}
			if ($queryParameters) {
				$r['?'] = $queryParameters;
				return $r;
			}
			return $r;
		}
		return $out;
	}

/**
 * Reverse route or match a $url array with the defined routes.
 * Returns either the string URL generate by the route, or false on failure.
 *
 * @param array $url The url to match.
 * @param array $context The request context to use. Contains _base, _port,
 *    _host, and _scheme keys.
 * @return string|false Either a string on match, or false on failure.
 */
	public function match($url, $context) {
		foreach ($this->_getNames($url) as $name) {
			if (empty($this->_routeTable[$name])) {
				continue;
			}
			foreach ($this->_routeTable[$name] as $route) {
				$match = $route->match($url, $context);
				if ($match) {
					return strlen($match) > 1 ? trim($match, '/') : $match;
				}
			}
		}
		return false;
	}

/**
 * Get the set of names from the $url.  Accepts both older style array urls,
 * and newer style urls containing '_name'
 *
 * @param array $url The url to match.
 * @return string The name of the url
 */
	protected function _getNames($url) {
		$name = false;
		if (isset($url['_name'])) {
			return [$url['_name']];
		}
		$plugin = false;
		if (isset($url['plugin'])) {
			$plugin = $url['plugin'];
		}
		$fallbacks = [
			'%2$s:%3$s',
			'%2$s:_action',
			'_controller:%3$s',
			'_controller:_action'
		];
		if ($plugin) {
			$fallbacks = [
				'%1$s.%2$s:%3$s',
				'%1$s.%2$s:_action',
				'%1$s._controller:%3$s',
				'%1$s._controller:_action',
				'_plugin.%2$s:%3$s',
				'_plugin._controller:%3$s',
				'_plugin._controller:_action',
				'_controller:_action'
			];
		}
		foreach ($fallbacks as $i => $template) {
			$fallbacks[$i] = strtolower(sprintf($template, $plugin, $url['controller'], $url['action']));
		}
		if ($name) {
			array_unshift($fallbacks, $name);
		}
		return $fallbacks;
	}

/**
 * Merge another ScopedRouteCollection with this one.
 *
 * Combines all the routes, from one collection into the current one.
 * Used internally when scopes are duplicated.
 *
 * @param \Cake\Routing\ScopedRouteCollection $collection
 * @return void
 */
	public function merge(ScopedRouteCollection $collection) {
		foreach ($collection->routes() as $route) {
			$name = $route->getName();
			if (!isset($this->_routeTable[$name])) {
				$this->_routeTable[$name] = [];
			}
			$this->_routeTable[$name][] = $route;
			$this->_routes[] = $route;
		}
		$this->_named += $collection->named();
	}

/**
 * Connect the `/:controller` and `/:controller/:action/*` fallback routes.
 *
 * This is a shortcut method for connecting fallback routes in a given scope.
 *
 * @return void
 */
	public function fallbacks() {
		$this->connect('/:controller', ['action' => 'index'], ['routeClass' => 'InflectedRoute']);
		$this->connect('/:controller/:action/*', [], ['routeClass' => 'InflectedRoute']);
	}

}
