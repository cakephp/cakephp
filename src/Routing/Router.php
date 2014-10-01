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

use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
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
 * Have routes been loaded
 *
 * @var bool
 */
	public static $initialized = false;

/**
 * Default route class.
 *
 * @var bool
 */
	protected static $_defaultRouteClass = 'Cake\Routing\Route\Route';

/**
 * Contains the base string that will be applied to all generated URLs
 * For example `https://example.com`
 *
 * @var string
 */
	protected static $_fullBaseUrl;

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

	protected static $_collection;

/**
 * A hash of request context data.
 *
 * @var array
 */
	protected static $_requestContext = [];

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
 * Get or set default route class.
 *
 * @param string|null $routeClass Class name.
 * @return string|void
 */
	public static function defaultRouteClass($routeClass = null) {
		if ($routeClass == null) {
			return static::$_defaultRouteClass;
		}
		static::$_defaultRouteClass = $routeClass;
	}

/**
 * Gets the named route patterns for use in config/routes.php
 *
 * @return array Named route elements
 * @see Router::$_namedExpressions
 */
	public static function getNamedExpressions() {
		return static::$_namedExpressions;
	}

/**
 * Connects a new Route in the router.
 *
 * Compatibility proxy to \Cake\Routing\RouteBuilder::connect() in the `/` scope.
 *
 * @param string $route A string describing the template of the route
 * @param array $defaults An array describing the default route parameters. These parameters will be used by default
 *   and can supply routing parameters that are not dynamic. See above.
 * @param array $options An array matching the named elements in the route to regular expressions which that
 *   element should match. Also contains additional parameters such as which routed parameters should be
 *   shifted into the passed arguments, supplying patterns for routing parameters and supplying the name of a
 *   custom routing class.
 * @return void
 * @throws \Cake\Core\Exception\Exception
 * @see \Cake\Routing\RouteBuilder::connect()
 * @see \Cake\Routing\Router::scope()
 */
	public static function connect($route, $defaults = [], $options = []) {
		static::$initialized = true;
		static::scope('/', function ($routes) use ($route, $defaults, $options) {
			$routes->connect($route, $defaults, $options);
		});
	}

/**
 * Connects a new redirection Route in the router.
 *
 * Compatibility proxy to \Cake\Routing\RouteBuilder::redirect() in the `/` scope.
 *
 * @param string $route A string describing the template of the route
 * @param array $url An URL to redirect to. Can be a string or a Cake array-based URL
 * @param array $options An array matching the named elements in the route to regular expressions which that
 *   element should match. Also contains additional parameters such as which routed parameters should be
 *   shifted into the passed arguments. As well as supplying patterns for routing parameters.
 * @return array Array of routes
 * @see \Cake\Routing\RouteBuilder::redirect()
 */
	public static function redirect($route, $url, $options = []) {
		$options['routeClass'] = 'Cake\Routing\Route\RedirectRoute';
		if (is_string($url)) {
			$url = ['redirect' => $url];
		}
		return static::connect($route, $url, $options);
	}

/**
 * Generate REST resource routes for the given controller(s).
 *
 * Compatibility proxy to \Cake\Routing\RouteBuilder::resources(). Additional, compatibility
 * around prefixes and plugins and prefixes is handled by this method.
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
 * @return void
 */
	public static function mapResources($controller, $options = []) {
		foreach ((array)$controller as $name) {
			list($plugin, $name) = pluginSplit($name);

			$prefix = $pluginUrl = false;
			if (!empty($options['prefix'])) {
				$prefix = $options['prefix'];
			}
			if ($plugin) {
				$pluginUrl = Inflector::underscore($plugin);
			}

			$callback = function ($routes) use ($name, $options) {
				$routes->resources($name, $options);
			};

			if ($plugin && $prefix) {
				$path = '/' . implode('/', [$prefix, $pluginUrl]);
				$params = ['prefix' => $prefix, 'plugin' => $plugin];
				return static::scope($path, $params, $callback);
			}

			if ($prefix) {
				return static::prefix($prefix, $callback);
			}

			if ($plugin) {
				return static::plugin($plugin, $callback);
			}

			return static::scope('/', $callback);
		}
	}

/**
 * Parses given URL string. Returns 'routing' parameters for that URL.
 *
 * @param string $url URL to be parsed
 * @return array Parsed elements from URL
 * @throws \Cake\Routing\Exception\MissingRouteException When a route cannot be handled
 */
	public static function parse($url) {
		if (!static::$initialized) {
			static::_loadRoutes();
		}
		if (strpos($url, '/') !== 0) {
			$url = '/' . $url;
		}
		return static::$_collection->parse($url);
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
 * @param \Cake\Network\Request $request Request instance.
 * @return void
 */
	public static function pushRequest(Request $request) {
		static::$_requests[] = $request;
		static::_setContext($request);
	}

/**
 * Store the request context for a given request.
 *
 * @param \Cake\Network\Request $request The request instance.
 * @return void
 */
	protected static function _setContext($request) {
		static::$_requestContext = [
			'_base' => $request->base,
			'_port' => $request->port(),
			'_scheme' => $request->scheme(),
			'_host' => $request->host()
		];
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
			static::_setContext($last);
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
			static::$_collection = new RouteCollection();
			static::$_initialState = get_class_vars(get_called_class());
			return;
		}
		foreach (static::$_initialState as $key => $val) {
			if ($key != '_initialState') {
				static::${$key} = $val;
			}
		}
		static::$_collection = new RouteCollection();
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
 * - `Router::url(['controller' => 'posts', 'action' => 'edit']);` Returns a URL
 *   generated through reverse routing.
 * - `Router::url(['_name' => 'custom-name', ...]);` Returns a URL generated
 *   through reverse routing. This form allows you to leverage named routes.
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
 * - `_ssl` - Set to true to convert the generated URL to https, or false to force http.
 * - `_name` - Name of route. If you have setup named routes you can use this key
 *   to specify it.
 *
 * @param string|array $url An array specifying any of the following:
 *   'controller', 'action', 'plugin' additionally, you can provide routed
 *   elements or query string parameters. If string it can be name any valid url
 *   string.
 * @param bool $full If true, the full base URL will be prepended to the result.
 *   Default is false.
 * @return string Full translated URL with base path.
 * @throws \Cake\Core\Exception\Exception When the route name is not found
 */
	public static function url($url = null, $full = false) {
		if (!static::$initialized) {
			static::_loadRoutes();
		}

		$params = array(
			'plugin' => null,
			'controller' => null,
			'action' => 'index',
			'_ext' => null,
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
		} elseif (is_array($url)) {
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
			if (isset($url['_ssl'])) {
				$url['_scheme'] = ($url['_ssl'] === true) ? 'https' : 'http';
				unset($url['_ssl']);
			}

			if (!isset($url['_name'])) {
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
					'_ext' => null
				);
			}

			$url = static::_applyUrlFilters($url);
			$output = static::$_collection->match($url, static::$_requestContext);
		} else {
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
 * Deprecated method for backwards compatibility.
 *
 * @param string|array $extensions List of extensions to be added.
 * @param bool $merge Whether to merge with or override existing extensions.
 *   Defaults to `true`.
 * @return array Extensions list.
 * @deprecated 3.0.0 Use Router::extensions() instead.
 */
	public static function parseExtensions($extensions = null, $merge = true) {
		trigger_error(
			'Router::parseExtensions() is deprecated should use Router::extensions() instead.',
			E_USER_DEPRECATED
		);
		return static::extensions($extensions, $merge);
	}

/**
 * Get/Set valid extensions. Instructs the router to parse out file extensions
 * from the URL. For example, http://example.com/posts.rss would yield a file
 * extension of "rss". The file extension itself is made available in the
 * controller as `$this->request->params['_ext']`, and is used by the RequestHandler
 * component to automatically switch to alternate layouts and templates, and
 * load helpers corresponding to the given content, i.e. RssHelper. Switching
 * layouts and helpers requires that the chosen extension has a defined mime type
 * in `Cake\Network\Response`.
 *
 * A string or an array of valid extensions can be passed to this method.
 * If called without any parameters it will return current list of set extensions.
 *
 * @param array|string $extensions List of extensions to be added.
 * @param bool $merge Whether to merge with or override existing extensions.
 *   Defaults to `true`.
 * @return array Array of extensions Router is configured to parse.
 */
	public static function extensions($extensions = null, $merge = true) {
		$collection = static::$_collection;
		if ($extensions === null) {
			if (!static::$initialized) {
				static::_loadRoutes();
			}
			return $collection->extensions();
		}

		return $collection->extensions($extensions, $merge);
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
 * @param \Cake\Network\Request $request The request object to modify.
 * @param array $options The array of options.
 * @return \Cake\Network\Request The modified request
 */
	public static function parseNamedParams(Request $request, array $options = []) {
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
 * Create a routing scope.
 *
 * Routing scopes allow you to keep your routes DRY and avoid repeating
 * common path prefixes, and or parameter sets.
 *
 * Scoped collections will be indexed by path for faster route parsing. If you
 * re-open or re-use a scope the connected routes will be merged with the
 * existing ones.
 *
 * ### Example
 *
 * {{{
 * Router::scope('/blog', ['plugin' => 'Blog'], function ($routes) {
 *    $routes->connect('/', ['controller' => 'Articles']);
 * });
 * }}}
 *
 * The above would result in a `/blog/` route being created, with both the
 * plugin & controller default parameters set.
 *
 * You can use Router::plugin() and Router::prefix() as shortcuts to creating
 * specific kinds of scopes.
 *
 * Routing scopes will inherit the globally set extensions configured with
 * Router::extensions(). You can also set valid extensions using
 * `$routes->extensions()` in your closure.
 *
 * @param string $path The path prefix for the scope. This path will be prepended
 *   to all routes connected in the scoped collection.
 * @param array|callable $params An array of routing defaults to add to each connected route.
 *   If you have no parameters, this argument can be a callable.
 * @param callable $callback The callback to invoke with the scoped collection.
 * @throws \InvalidArgumentException When an invalid callable is provided.
 * @return null|\Cake\Routing\RouteBuilder The route builder
 *   was created/used.
 */
	public static function scope($path, $params = [], $callback = null) {
		$builder = new RouteBuilder(static::$_collection, '/', [], [
			'routeClass' => static::defaultRouteClass(),
			'extensions' => static::$_collection->extensions()
		]);
		$builder->scope($path, $params, $callback);
	}

/**
 * Create prefixed routes.
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
	public static function prefix($name, $callback) {
		$name = Inflector::underscore($name);
		$path = '/' . $name;
		static::scope($path, ['prefix' => $name], $callback);
	}

/**
 * Add plugin routes.
 *
 * This method creates a scoped route collection that includes
 * relevant plugin information.
 *
 * The plugin name will be inflected to the underscore version to create
 * the routing path. If you want a custom path name, use the `path` option.
 *
 * Routes connected in the scoped collection will have the correct path segment
 * prepended, and have a matching plugin routing key set.
 *
 * @param string $name The plugin name to build routes for
 * @param array|callable $options Either the options to use, or a callback
 * @param callable $callback The callback to invoke that builds the plugin routes.
 *   Only required when $options is defined
 * @return void
 */
	public static function plugin($name, $options = [], $callback = null) {
		if ($callback === null) {
			$callback = $options;
			$options = [];
		}
		$params = ['plugin' => $name];
		if (empty($options['path'])) {
			$options['path'] = '/' . Inflector::underscore($name);
		}
		static::scope($options['path'], $params, $callback);
	}

/**
 * Get the route scopes and their connected routes.
 *
 * @return array
 */
	public static function routes() {
		return static::$_collection->routes();
	}

/**
 * Loads route configuration
 *
 * @return void
 */
	protected static function _loadRoutes() {
		static::$initialized = true;
		include CONFIG . 'routes.php';
	}

}

//Save the initial state
Router::reload();
