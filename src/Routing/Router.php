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
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Parses the request URL into controller, action, and parameters. Uses the connected routes
 * to match the incoming URL string to parameters that will allow the request to be dispatched. Also
 * handles converting parameter lists into URL strings, using the connected routes. Routing allows you to decouple
 * the way the world interacts with your application (URLs) and the implementation (controllers and actions).
 *
 * ### Connecting routes
 *
 * Connecting routes is done using Router::connect(). When parsing incoming requests or reverse matching
 * parameters, routes are enumerated in the order they were connected. For more information on routes and
 * how to connect them see Router::connect().
 */
class Router
{

    /**
     * Have routes been loaded
     *
     * @var bool
     */
    public static $initialized = false;

    /**
     * Default route class.
     *
     * @var string
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

    /**
     * The route collection routes would be added to.
     *
     * @var \Cake\Routing\RouteCollection
     */
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
    protected static $_namedExpressions = [
        'Action' => Router::ACTION,
        'Year' => Router::YEAR,
        'Month' => Router::MONTH,
        'Day' => Router::DAY,
        'ID' => Router::ID,
        'UUID' => Router::UUID
    ];

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
     * @var callable[]
     */
    protected static $_urlFilters = [];

    /**
     * Default extensions defined with Router::extensions()
     *
     * @var array
     */
    protected static $_defaultExtensions = [];

    /**
     * Get or set default route class.
     *
     * @param string|null $routeClass Class name.
     * @return string|null
     */
    public static function defaultRouteClass($routeClass = null)
    {
        if ($routeClass === null) {
            return static::$_defaultRouteClass;
        }
        static::$_defaultRouteClass = $routeClass;
    }

    /**
     * Gets the named route patterns for use in config/routes.php
     *
     * @return array Named route elements
     * @see \Cake\Routing\Router::$_namedExpressions
     */
    public static function getNamedExpressions()
    {
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
    public static function connect($route, $defaults = [], $options = [])
    {
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
     * @return void
     * @see \Cake\Routing\RouteBuilder::redirect()
     * @deprecated 3.3.0 Use Router::scope() and RouteBuilder::redirect() instead.
     */
    public static function redirect($route, $url, $options = [])
    {
        if (is_string($url)) {
            $url = ['redirect' => $url];
        }
        if (!isset($options['routeClass'])) {
            $options['routeClass'] = 'Cake\Routing\Route\RedirectRoute';
        }
        static::connect($route, $url, $options);
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
     * ```
     * Router::mapResources('Posts');
     * ```
     *
     * Connect resource routes for the Comment controller in the
     * Comments plugin:
     *
     * ```
     * Router::mapResources('Comments.Comment');
     * ```
     *
     * Plugins will create lower_case underscored resource routes. e.g
     * `/comments/comment`
     *
     * Connect resource routes for the Posts controller in the
     * Admin prefix:
     *
     * ```
     * Router::mapResources('Posts', ['prefix' => 'admin']);
     * ```
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
     * - 'only' - Only connect the specific list of actions.
     * - 'actions' - Override the method names used for connecting actions.
     * - 'map' - Additional resource routes that should be connected. If you define 'only' and 'map',
     *   make sure that your mapped methods are also in the 'only' list.
     *
     * @param string|array $controller A controller name or array of controller names (i.e. "Posts" or "ListItems")
     * @param array $options Options to use when generating REST routes
     * @see \Cake\Routing\RouteBuilder::resources()
     * @deprecated 3.3.0 Use Router::scope() and RouteBuilder::resources() instead.
     * @return void
     */
    public static function mapResources($controller, $options = [])
    {
        foreach ((array)$controller as $name) {
            list($plugin, $name) = pluginSplit($name);

            $prefix = $pluginUrl = false;
            if (!empty($options['prefix'])) {
                $prefix = $options['prefix'];
                unset($options['prefix']);
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
                static::scope($path, $params, $callback);

                return;
            }

            if ($prefix) {
                static::prefix($prefix, $callback);

                return;
            }

            if ($plugin) {
                static::plugin($plugin, $callback);

                return;
            }

            static::scope('/', $callback);

            return;
        }
    }

    /**
     * Parses given URL string. Returns 'routing' parameters for that URL.
     *
     * @param string $url URL to be parsed.
     * @param string $method The HTTP method being used.
     * @return array Parsed elements from URL.
     * @throws \Cake\Routing\Exception\MissingRouteException When a route cannot be handled
     * @deprecated 3.4.0 Use Router::parseRequest() instead.
     */
    public static function parse($url, $method = '')
    {
        if (!static::$initialized) {
            static::_loadRoutes();
        }
        if (strpos($url, '/') !== 0) {
            $url = '/' . $url;
        }

        return static::$_collection->parse($url, $method);
    }

    /**
     * Get the routing parameters for the request is possible.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to parse request data from.
     * @return array Parsed elements from URL.
     * @throws \Cake\Routing\Exception\MissingRouteException When a route cannot be handled
     */
    public static function parseRequest(ServerRequestInterface $request)
    {
        if (!static::$initialized) {
            static::_loadRoutes();
        }

        return static::$_collection->parseRequest($request);
    }

    /**
     * Takes parameter and path information back from the Dispatcher, sets these
     * parameters as the current request parameters that are merged with URL arrays
     * created later in the request.
     *
     * Nested requests will create a stack of requests. You can remove requests using
     * Router::popRequest(). This is done automatically when using Object::requestAction().
     *
     * Will accept either a Cake\Http\ServerRequest object or an array of arrays. Support for
     * accepting arrays may be removed in the future.
     *
     * @param \Cake\Http\ServerRequest|array $request Parameters and path information or a Cake\Http\ServerRequest object.
     * @return void
     */
    public static function setRequestInfo($request)
    {
        if ($request instanceof ServerRequest) {
            static::pushRequest($request);
        } else {
            $requestData = $request;
            $requestData += [[], []];
            $requestData[0] += [
                'controller' => false,
                'action' => false,
                'plugin' => null
            ];
            $request = new ServerRequest();
            $request->addParams($requestData[0])->addPaths($requestData[1]);
            static::pushRequest($request);
        }
    }

    /**
     * Push a request onto the request stack. Pushing a request
     * sets the request context used when generating URLs.
     *
     * @param \Cake\Http\ServerRequest $request Request instance.
     * @return void
     */
    public static function pushRequest(ServerRequest $request)
    {
        static::$_requests[] = $request;
        static::setRequestContext($request);
    }

    /**
     * Store the request context for a given request.
     *
     * @param \Cake\Http\ServerRequest|\Psr\Http\Message\ServerRequestInterface $request The request instance.
     * @return void
     * @throws InvalidArgumentException When parameter is an incorrect type.
     */
    public static function setRequestContext($request)
    {
        if ($request instanceof ServerRequest) {
            static::$_requestContext = [
                '_base' => $request->base,
                '_port' => $request->port(),
                '_scheme' => $request->scheme(),
                '_host' => $request->host()
            ];

            return;
        }
        if ($request instanceof ServerRequestInterface) {
            $uri = $request->getUri();
            static::$_requestContext = [
                '_base' => $request->getAttribute('base'),
                '_port' => $uri->getPort(),
                '_scheme' => $uri->getScheme(),
                '_host' => $uri->getHost(),
            ];

            return;
        }
        throw new InvalidArgumentException('Unknown request type received.');
    }

    /**
     * Pops a request off of the request stack. Used when doing requestAction
     *
     * @return \Cake\Http\ServerRequest The request removed from the stack.
     * @see \Cake\Routing\Router::pushRequest()
     * @see \Cake\Routing\RequestActionTrait::requestAction()
     */
    public static function popRequest()
    {
        $removed = array_pop(static::$_requests);
        $last = end(static::$_requests);
        if ($last) {
            static::setRequestContext($last);
            reset(static::$_requests);
        }

        return $removed;
    }

    /**
     * Get the current request object, or the first one.
     *
     * @param bool $current True to get the current request, or false to get the first one.
     * @return \Cake\Http\ServerRequest|null
     */
    public static function getRequest($current = false)
    {
        if ($current) {
            $request = end(static::$_requests);

            return $request ?: null;
        }

        return isset(static::$_requests[0]) ? static::$_requests[0] : null;
    }

    /**
     * Reloads default Router settings. Resets all class variables and
     * removes all connected routes.
     *
     * @return void
     */
    public static function reload()
    {
        if (empty(static::$_initialState)) {
            static::$_collection = new RouteCollection();
            static::$_initialState = get_class_vars(get_called_class());

            return;
        }
        foreach (static::$_initialState as $key => $val) {
            if ($key !== '_initialState') {
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
     * ```
     * Router::addUrlFilter(function ($params, $request) {
     *  if ($request->getParam('lang') && !isset($params['lang'])) {
     *    $params['lang'] = $request->getParam('lang');
     *  }
     *  return $params;
     * });
     * ```
     *
     * @param callable $function The function to add
     * @return void
     */
    public static function addUrlFilter(callable $function)
    {
        static::$_urlFilters[] = $function;
    }

    /**
     * Applies all the connected URL filters to the URL.
     *
     * @param array $url The URL array being modified.
     * @return array The modified URL.
     * @see \Cake\Routing\Router::url()
     * @see \Cake\Routing\Router::addUrlFilter()
     */
    protected static function _applyUrlFilters($url)
    {
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
     * - `_host` - Set the host to use for the link. Defaults to the current host.
     * - `_port` - Set the port if you need to create links on non-standard ports.
     * - `_full` - If true output of `Router::fullBaseUrl()` will be prepended to generated URLs.
     * - `#` - Allows you to set URL hash fragments.
     * - `_ssl` - Set to true to convert the generated URL to https, or false to force http.
     * - `_name` - Name of route. If you have setup named routes you can use this key
     *   to specify it.
     *
     * @param string|array|null $url An array specifying any of the following:
     *   'controller', 'action', 'plugin' additionally, you can provide routed
     *   elements or query string parameters. If string it can be name any valid url
     *   string.
     * @param bool $full If true, the full base URL will be prepended to the result.
     *   Default is false.
     * @return string Full translated URL with base path.
     * @throws \Cake\Core\Exception\Exception When the route name is not found
     */
    public static function url($url = null, $full = false)
    {
        if (!static::$initialized) {
            static::_loadRoutes();
        }

        $params = [
            'plugin' => null,
            'controller' => null,
            'action' => 'index',
            '_ext' => null,
        ];
        $here = $base = $output = $frag = null;

        // In 4.x this should be replaced with state injected via setRequestContext
        $request = static::getRequest(true);
        if ($request) {
            $params = $request->params;
            $here = $request->here;
            $base = $request->getAttribute('base');
        } else {
            $base = Configure::read('App.base');
            if (isset(static::$_requestContext['_base'])) {
                $base = static::$_requestContext['_base'];
            }
        }

        if (empty($url)) {
            $output = isset($here) ? $here : $base . '/';
            if ($full) {
                $output = static::fullBaseUrl() . $output;
            }

            return $output;
        }
        if (is_array($url)) {
            if (isset($url['_full']) && $url['_full'] === true) {
                $full = true;
                unset($url['_full']);
            }
            if (isset($url['#'])) {
                $frag = '#' . $url['#'];
                unset($url['#']);
            }
            if (isset($url['_ssl'])) {
                $url['_scheme'] = ($url['_ssl'] === true) ? 'https' : 'http';
                unset($url['_ssl']);
            }

            $url = static::_applyUrlFilters($url);

            if (!isset($url['_name'])) {
                // Copy the current action if the controller is the current one.
                if (empty($url['action']) &&
                    (empty($url['controller']) || $params['controller'] === $url['controller'])
                ) {
                    $url['action'] = $params['action'];
                }

                // Keep the current prefix around if none set.
                if (isset($params['prefix']) && !isset($url['prefix'])) {
                    $url['prefix'] = $params['prefix'];
                }

                $url += [
                    'plugin' => $params['plugin'],
                    'controller' => $params['controller'],
                    'action' => 'index',
                    '_ext' => null
                ];
            }

            $output = static::$_collection->match($url, static::$_requestContext + ['params' => $params]);
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
     * ### Note:
     *
     * If you change the configuration value `App.fullBaseUrl` during runtime
     * and expect the router to produce links using the new setting, you are
     * required to call this method passing such value again.
     *
     * @param string|null $base the prefix for URLs generated containing the domain.
     * For example: `http://example.com`
     * @return string
     */
    public static function fullBaseUrl($base = null)
    {
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
     * Reverses a parsed parameter array into an array.
     *
     * Works similarly to Router::url(), but since parsed URL's contain additional
     * 'pass' as well as 'url.url' keys. Those keys need to be specially
     * handled in order to reverse a params array into a string URL.
     *
     * This will strip out 'autoRender', 'bare', 'requested', and 'return' param names as those
     * are used for CakePHP internals and should not normally be part of an output URL.
     *
     * @param \Cake\Http\ServerRequest|array $params The params array or
     *     Cake\Http\ServerRequest object that needs to be reversed.
     * @return array The URL array ready to be used for redirect or HTML link.
     */
    public static function reverseToArray($params)
    {
        $url = [];
        if ($params instanceof ServerRequest) {
            $url = $params->query;
            $params = $params->params;
        } elseif (isset($params['url'])) {
            $url = $params['url'];
        }
        $pass = isset($params['pass']) ? $params['pass'] : [];

        unset(
            $params['pass'],
            $params['paging'],
            $params['models'],
            $params['url'],
            $url['url'],
            $params['autoRender'],
            $params['bare'],
            $params['requested'],
            $params['return'],
            $params['_Token'],
            $params['_matchedRoute']
        );
        $params = array_merge($params, $pass);
        if (!empty($url)) {
            $params['?'] = $url;
        }

        return $params;
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
     * @param \Cake\Http\ServerRequest|array $params The params array or
     *     Cake\Network\Request object that needs to be reversed.
     * @param bool $full Set to true to include the full URL including the
     *     protocol when reversing the URL.
     * @return string The string that is the reversed result of the array
     */
    public static function reverse($params, $full = false)
    {
        $params = static::reverseToArray($params);

        return static::url($params, $full);
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
    public static function normalize($url = '/')
    {
        if (is_array($url)) {
            $url = static::url($url);
        }
        if (preg_match('/^[a-z\-]+:\/\//', $url)) {
            return $url;
        }
        $request = static::getRequest();

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
     * Get or set valid extensions for all routes connected later.
     *
     * Instructs the router to parse out file extensions
     * from the URL. For example, http://example.com/posts.rss would yield a file
     * extension of "rss". The file extension itself is made available in the
     * controller as `$this->request->getParam('_ext')`, and is used by the RequestHandler
     * component to automatically switch to alternate layouts and templates, and
     * load helpers corresponding to the given content, i.e. RssHelper. Switching
     * layouts and helpers requires that the chosen extension has a defined mime type
     * in `Cake\Http\Response`.
     *
     * A string or an array of valid extensions can be passed to this method.
     * If called without any parameters it will return current list of set extensions.
     *
     * @param array|string|null $extensions List of extensions to be added.
     * @param bool $merge Whether to merge with or override existing extensions.
     *   Defaults to `true`.
     * @return array Array of extensions Router is configured to parse.
     */
    public static function extensions($extensions = null, $merge = true)
    {
        $collection = static::$_collection;
        if ($extensions === null) {
            if (!static::$initialized) {
                static::_loadRoutes();
            }

            return array_unique(array_merge(static::$_defaultExtensions, $collection->extensions()));
        }
        $extensions = (array)$extensions;
        if ($merge) {
            $extensions = array_unique(array_merge(static::$_defaultExtensions, $extensions));
        }

        return static::$_defaultExtensions = $extensions;
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
     * - `separator` The string to use as a separator. Defaults to `:`.
     *
     * @param \Cake\Http\ServerRequest $request The request object to modify.
     * @param array $options The array of options.
     * @return \Cake\Http\ServerRequest The modified request
     * @deprecated 3.3.0 Named parameter backwards compatibility will be removed in 4.0.
     */
    public static function parseNamedParams(ServerRequest $request, array $options = [])
    {
        $options += ['separator' => ':'];
        if (empty($request->params['pass'])) {
            $request->params['named'] = [];

            return $request;
        }
        $named = [];
        foreach ($request->getParam('pass') as $key => $value) {
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
                        $arr = [$arr];
                    } else {
                        $arr = [
                            $match[1] => $arr
                        ];
                    }
                }
                $value = $arr;
            }
            $named = array_merge_recursive($named, [$key => $value]);
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
     * ### Options
     *
     * The `$params` array allows you to define options for the routing scope.
     * The options listed below *are not* available to be used as routing defaults
     *
     * - `routeClass` The route class to use in this scope. Defaults to
     *   `Router::defaultRouteClass()`
     * - `extensions` The extensions to enable in this scope. Defaults to the globally
     *   enabled extensions set with `Router::extensions()`
     *
     * ### Example
     *
     * ```
     * Router::scope('/blog', ['plugin' => 'Blog'], function ($routes) {
     *    $routes->connect('/', ['controller' => 'Articles']);
     * });
     * ```
     *
     * The above would result in a `/blog/` route being created, with both the
     * plugin & controller default parameters set.
     *
     * You can use `Router::plugin()` and `Router::prefix()` as shortcuts to creating
     * specific kinds of scopes.
     *
     * @param string $path The path prefix for the scope. This path will be prepended
     *   to all routes connected in the scoped collection.
     * @param array|callable $params An array of routing defaults to add to each connected route.
     *   If you have no parameters, this argument can be a callable.
     * @param callable|null $callback The callback to invoke with the scoped collection.
     * @throws \InvalidArgumentException When an invalid callable is provided.
     * @return void
     */
    public static function scope($path, $params = [], $callback = null)
    {
        $options = [
            'routeClass' => static::defaultRouteClass(),
            'extensions' => static::$_defaultExtensions,
        ];
        if (is_array($params)) {
            $options = $params + $options;
            unset($params['routeClass'], $params['extensions']);
        }
        $builder = new RouteBuilder(static::$_collection, '/', [], [
            'routeClass' => $options['routeClass'],
            'extensions' => $options['extensions'],
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
     * The prefix name will be inflected to the underscore version to create
     * the routing path. If you want a custom path name, use the `path` option.
     *
     * You can re-open a prefix as many times as necessary, as well as nest prefixes.
     * Nested prefixes will result in prefix values like `admin/api` which translates
     * to the `Controller\Admin\Api\` namespace.
     *
     * @param string $name The prefix name to use.
     * @param array|callable $params An array of routing defaults to add to each connected route.
     *   If you have no parameters, this argument can be a callable.
     * @param callable|null $callback The callback to invoke that builds the prefixed routes.
     * @return void
     */
    public static function prefix($name, $params = [], $callback = null)
    {
        if ($callback === null) {
            $callback = $params;
            $params = [];
        }
        $name = Inflector::underscore($name);

        if (empty($params['path'])) {
            $path = '/' . $name;
        } else {
            $path = $params['path'];
            unset($params['path']);
        }

        $params = array_merge($params, ['prefix' => $name]);
        static::scope($path, $params, $callback);
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
     * @param callable|null $callback The callback to invoke that builds the plugin routes.
     *   Only required when $options is defined
     * @return void
     */
    public static function plugin($name, $options = [], $callback = null)
    {
        if ($callback === null) {
            $callback = $options;
            $options = [];
        }
        $params = ['plugin' => $name];
        if (empty($options['path'])) {
            $options['path'] = '/' . Inflector::underscore($name);
        }
        if (isset($options['_namePrefix'])) {
            $params['_namePrefix'] = $options['_namePrefix'];
        }
        static::scope($options['path'], $params, $callback);
    }

    /**
     * Get the route scopes and their connected routes.
     *
     * @return \Cake\Routing\Route\Route[]
     */
    public static function routes()
    {
        if (!static::$initialized) {
            static::_loadRoutes();
        }

        return static::$_collection->routes();
    }

    /**
     * Loads route configuration
     *
     * @return void
     */
    protected static function _loadRoutes()
    {
        static::$initialized = true;
        include CONFIG . 'routes.php';
    }
}
