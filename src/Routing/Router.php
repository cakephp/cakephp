<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Utility\Inflector;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;
use Throwable;

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
     * Default route class.
     *
     * @var string
     */
    protected static $_defaultRouteClass = Route\Route::class;

    /**
     * Contains the base string that will be applied to all generated URLs
     * For example `https://example.com`
     *
     * @var string|null
     */
    protected static $_fullBaseUrl;

    /**
     * Regular expression for action names
     *
     * @var string
     */
    public const ACTION = 'index|show|add|create|edit|update|remove|del|delete|view|item';

    /**
     * Regular expression for years
     *
     * @var string
     */
    public const YEAR = '[12][0-9]{3}';

    /**
     * Regular expression for months
     *
     * @var string
     */
    public const MONTH = '0[1-9]|1[012]';

    /**
     * Regular expression for days
     *
     * @var string
     */
    public const DAY = '0[1-9]|[12][0-9]|3[01]';

    /**
     * Regular expression for auto increment IDs
     *
     * @var string
     */
    public const ID = '[0-9]+';

    /**
     * Regular expression for UUIDs
     *
     * @var string
     */
    public const UUID = '[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12}';

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
     * @var array<string, string>
     */
    protected static $_namedExpressions = [
        'Action' => Router::ACTION,
        'Year' => Router::YEAR,
        'Month' => Router::MONTH,
        'Day' => Router::DAY,
        'ID' => Router::ID,
        'UUID' => Router::UUID,
    ];

    /**
     * Maintains the request object reference.
     *
     * @var \Cake\Http\ServerRequest
     */
    protected static $_request;

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
     * @var array<callable>
     */
    protected static $_urlFilters = [];

    /**
     * Default extensions defined with Router::extensions()
     *
     * @var array<string>
     */
    protected static $_defaultExtensions = [];

    /**
     * Cache of parsed route paths
     *
     * @var array
     */
    protected static $_routePaths = [];

    /**
     * Get or set default route class.
     *
     * @param string|null $routeClass Class name.
     * @return string|null
     */
    public static function defaultRouteClass(?string $routeClass = null): ?string
    {
        if ($routeClass === null) {
            return static::$_defaultRouteClass;
        }
        static::$_defaultRouteClass = $routeClass;

        return null;
    }

    /**
     * Gets the named route patterns for use in config/routes.php
     *
     * @return array<string, string> Named route elements
     * @see \Cake\Routing\Router::$_namedExpressions
     */
    public static function getNamedExpressions(): array
    {
        return static::$_namedExpressions;
    }

    /**
     * Connects a new Route in the router.
     *
     * Compatibility proxy to \Cake\Routing\RouteBuilder::connect() in the `/` scope.
     *
     * @param \Cake\Routing\Route\Route|string $route A string describing the template of the route
     * @param array|string $defaults An array describing the default route parameters.
     *   These parameters will be used by default and can supply routing parameters that are not dynamic. See above.
     * @param array<string, mixed> $options An array matching the named elements in the route to regular expressions which that
     *   element should match. Also contains additional parameters such as which routed parameters should be
     *   shifted into the passed arguments, supplying patterns for routing parameters and supplying the name of a
     *   custom routing class.
     * @return void
     * @throws \Cake\Core\Exception\CakeException
     * @see \Cake\Routing\RouteBuilder::connect()
     * @see \Cake\Routing\Router::scope()
     * @deprecated 4.3.0 Use the non-static method `RouteBuilder::connect()` instead.
     */
    public static function connect($route, $defaults = [], $options = []): void
    {
        deprecationWarning(
            '`Router::connect()` is deprecated, use the non-static method `RouteBuilder::connect()` instead.'
        );

        static::scope('/', function ($routes) use ($route, $defaults, $options): void {
            /** @var \Cake\Routing\RouteBuilder $routes */
            $routes->connect($route, $defaults, $options);
        });
    }

    /**
     * Get the routing parameters for the request is possible.
     *
     * @param \Cake\Http\ServerRequest $request The request to parse request data from.
     * @return array Parsed elements from URL.
     * @throws \Cake\Routing\Exception\MissingRouteException When a route cannot be handled
     */
    public static function parseRequest(ServerRequest $request): array
    {
        return static::$_collection->parseRequest($request);
    }

    /**
     * Set current request instance.
     *
     * @param \Cake\Http\ServerRequest $request request object.
     * @return void
     */
    public static function setRequest(ServerRequest $request): void
    {
        static::$_request = $request;

        static::$_requestContext['_base'] = $request->getAttribute('base');
        static::$_requestContext['params'] = $request->getAttribute('params', []);

        $uri = $request->getUri();
        static::$_requestContext += [
            '_scheme' => $uri->getScheme(),
            '_host' => $uri->getHost(),
            '_port' => $uri->getPort(),
        ];
    }

    /**
     * Get the current request object.
     *
     * @return \Cake\Http\ServerRequest|null
     */
    public static function getRequest(): ?ServerRequest
    {
        return static::$_request;
    }

    /**
     * Reloads default Router settings. Resets all class variables and
     * removes all connected routes.
     *
     * @return void
     */
    public static function reload(): void
    {
        if (empty(static::$_initialState)) {
            static::$_collection = new RouteCollection();
            static::$_initialState = get_class_vars(static::class);

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
     * Reset routes and related state.
     *
     * Similar to reload() except that this doesn't reset all global state,
     * as that leads to incorrect behavior in some plugin test case scenarios.
     *
     * This method will reset:
     *
     * - routes
     * - URL Filters
     * - the initialized property
     *
     * Extensions and default route classes will not be modified
     *
     * @internal
     * @return void
     */
    public static function resetRoutes(): void
    {
        static::$_collection = new RouteCollection();
        static::$_urlFilters = [];
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
    public static function addUrlFilter(callable $function): void
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
    protected static function _applyUrlFilters(array $url): array
    {
        $request = static::getRequest();
        foreach (static::$_urlFilters as $filter) {
            try {
                $url = $filter($url, $request);
            } catch (Throwable $e) {
                if (is_array($filter)) {
                    $ref = new ReflectionMethod($filter[0], $filter[1]);
                } else {
                    /** @psalm-var \Closure|callable-string $filter */
                    $ref = new ReflectionFunction($filter);
                }
                $message = sprintf(
                    'URL filter defined in %s on line %s could not be applied. The filter failed with: %s',
                    $ref->getFileName(),
                    $ref->getStartLine(),
                    $e->getMessage()
                );
                throw new RuntimeException($message, (int)$e->getCode(), $e);
            }
        }

        return $url;
    }

    /**
     * Finds URL for specified action.
     *
     * Returns a URL pointing to a combination of controller and action.
     *
     * ### Usage
     *
     * - `Router::url('/posts/edit/1');` Returns the string with the base dir prepended.
     *   This usage does not use reverser routing.
     * - `Router::url(['controller' => 'Posts', 'action' => 'edit']);` Returns a URL
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
     * @param \Psr\Http\Message\UriInterface|array|string|null $url An array specifying any of the following:
     *   'controller', 'action', 'plugin' additionally, you can provide routed
     *   elements or query string parameters. If string it can be name any valid url
     *   string or it can be an UriInterface instance.
     * @param bool $full If true, the full base URL will be prepended to the result.
     *   Default is false.
     * @return string Full translated URL with base path.
     * @throws \Cake\Core\Exception\CakeException When the route name is not found
     */
    public static function url($url = null, bool $full = false): string
    {
        $context = static::$_requestContext;
        $request = static::getRequest();

        $context['_base'] = $context['_base'] ?? Configure::read('App.base') ?: '';

        if (empty($url)) {
            $here = $request ? $request->getRequestTarget() : '/';
            $output = $context['_base'] . $here;
            if ($full) {
                $output = static::fullBaseUrl() . $output;
            }

            return $output;
        }

        $params = [
            'plugin' => null,
            'controller' => null,
            'action' => 'index',
            '_ext' => null,
        ];
        if (!empty($context['params'])) {
            $params = $context['params'];
        }

        $frag = '';

        if (is_array($url)) {
            if (isset($url['_path'])) {
                $url = self::unwrapShortString($url);
            }

            if (isset($url['_ssl'])) {
                $url['_scheme'] = $url['_ssl'] === true ? 'https' : 'http';
            }

            if (isset($url['_full']) && $url['_full'] === true) {
                $full = true;
            }
            if (isset($url['#'])) {
                $frag = '#' . $url['#'];
            }
            unset($url['_ssl'], $url['_full'], $url['#']);

            $url = static::_applyUrlFilters($url);

            if (!isset($url['_name'])) {
                // Copy the current action if the controller is the current one.
                if (
                    empty($url['action']) &&
                    (
                        empty($url['controller']) ||
                        $params['controller'] === $url['controller']
                    )
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
                    '_ext' => null,
                ];
            }

            // If a full URL is requested with a scheme the host should default
            // to App.fullBaseUrl to avoid corrupt URLs
            if ($full && isset($url['_scheme']) && !isset($url['_host'])) {
                $url['_host'] = $context['_host'];
            }
            $context['params'] = $params;

            $output = static::$_collection->match($url, $context);
        } else {
            $url = (string)$url;

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
            $output = $context['_base'] . $url;
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
     * Generate URL for route path.
     *
     * Route path examples:
     * - Bookmarks::view
     * - Admin/Bookmarks::view
     * - Cms.Articles::edit
     * - Vendor/Cms.Management/Admin/Articles::view
     *
     * @param string $path Route path specifying controller and action, optionally with plugin and prefix.
     * @param array $params An array specifying any additional parameters.
     *   Can be also any special parameters supported by `Router::url()`.
     * @param bool $full If true, the full base URL will be prepended to the result.
     *   Default is false.
     * @return string Full translated URL with base path.
     */
    public static function pathUrl(string $path, array $params = [], bool $full = false): string
    {
        return static::url(['_path' => $path] + $params, $full);
    }

    /**
     * Finds URL for specified action.
     *
     * Returns a bool if the url exists
     *
     * ### Usage
     *
     * @see Router::url()
     * @param array|string|null $url An array specifying any of the following:
     *   'controller', 'action', 'plugin' additionally, you can provide routed
     *   elements or query string parameters. If string it can be name any valid url
     *   string.
     * @param bool $full If true, the full base URL will be prepended to the result.
     *   Default is false.
     * @return bool
     */
    public static function routeExists($url = null, bool $full = false): bool
    {
        try {
            static::url($url, $full);

            return true;
        } catch (MissingRouteException $e) {
            return false;
        }
    }

    /**
     * Sets the full base URL that will be used as a prefix for generating
     * fully qualified URLs for this application. If no parameters are passed,
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
    public static function fullBaseUrl(?string $base = null): string
    {
        if ($base === null && static::$_fullBaseUrl !== null) {
            return static::$_fullBaseUrl;
        }

        if ($base !== null) {
            static::$_fullBaseUrl = $base;
            Configure::write('App.fullBaseUrl', $base);
        } else {
            $base = (string)Configure::read('App.fullBaseUrl');

            // If App.fullBaseUrl is empty but context is set from request through setRequest()
            if (!$base && !empty(static::$_requestContext['_host'])) {
                $base = sprintf(
                    '%s://%s',
                    static::$_requestContext['_scheme'],
                    static::$_requestContext['_host']
                );
                if (!empty(static::$_requestContext['_port'])) {
                    $base .= ':' . static::$_requestContext['_port'];
                }

                Configure::write('App.fullBaseUrl', $base);

                return static::$_fullBaseUrl = $base;
            }

            static::$_fullBaseUrl = $base;
        }

        $parts = parse_url(static::$_fullBaseUrl);
        static::$_requestContext = [
            '_scheme' => $parts['scheme'] ?? null,
            '_host' => $parts['host'] ?? null,
            '_port' => $parts['port'] ?? null,
        ] + static::$_requestContext;

        return static::$_fullBaseUrl;
    }

    /**
     * Reverses a parsed parameter array into an array.
     *
     * Works similarly to Router::url(), but since parsed URL's contain additional
     * keys like 'pass', '_matchedRoute' etc. those keys need to be specially
     * handled in order to reverse a params array into a string URL.
     *
     * @param \Cake\Http\ServerRequest|array $params The params array or
     *     Cake\Http\ServerRequest object that needs to be reversed.
     * @return array The URL array ready to be used for redirect or HTML link.
     */
    public static function reverseToArray($params): array
    {
        if ($params instanceof ServerRequest) {
            $queryString = $params->getQueryParams();
            $params = $params->getAttribute('params');
            $params['?'] = $queryString;
        }
        $pass = $params['pass'] ?? [];

        $template = $params['_matchedRoute'] ?? null;
        unset(
            $params['pass'],
            $params['_matchedRoute'],
            $params['_name']
        );
        $route = null;
        if ($template) {
            // Locate the route that was used to match this route
            // so we can access the pass parameter configuration.
            foreach (static::getRouteCollection()->routes() as $maybe) {
                if ($maybe->template === $template) {
                    $route = $maybe;
                    break;
                }
            }
        }
        if ($route) {
            // If we found a route, slice off the number of passed args.
            $routePass = $route->options['pass'] ?? [];
            $pass = array_slice($pass, count($routePass));
        }
        $params = array_merge($params, $pass);

        return $params;
    }

    /**
     * Reverses a parsed parameter array into a string.
     *
     * Works similarly to Router::url(), but since parsed URL's contain additional
     * keys like 'pass', '_matchedRoute' etc. those keys need to be specially
     * handled in order to reverse a params array into a string URL.
     *
     * @param \Cake\Http\ServerRequest|array $params The params array or
     *     Cake\Http\ServerRequest object that needs to be reversed.
     * @param bool $full Set to true to include the full URL including the
     *     protocol when reversing the URL.
     * @return string The string that is the reversed result of the array
     */
    public static function reverse($params, $full = false): string
    {
        $params = static::reverseToArray($params);

        return static::url($params, $full);
    }

    /**
     * Normalizes a URL for purposes of comparison.
     *
     * Will strip the base path off and replace any double /'s.
     * It will not unify the casing and underscoring of the input value.
     *
     * @param array|string $url URL to normalize Either an array or a string URL.
     * @return string Normalized URL
     */
    public static function normalize($url = '/'): string
    {
        if (is_array($url)) {
            $url = static::url($url);
        }
        if (preg_match('/^[a-z\-]+:\/\//', $url)) {
            return $url;
        }
        $request = static::getRequest();

        if ($request) {
            $base = $request->getAttribute('base', '');
            if ($base !== '' && stristr($url, $base)) {
                $url = preg_replace('/^' . preg_quote($base, '/') . '/', '', $url, 1);
            }
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
     * @param array<string>|string|null $extensions List of extensions to be added.
     * @param bool $merge Whether to merge with or override existing extensions.
     *   Defaults to `true`.
     * @return array<string> Array of extensions Router is configured to parse.
     */
    public static function extensions($extensions = null, $merge = true): array
    {
        $collection = static::$_collection;
        if ($extensions === null) {
            return array_unique(array_merge(static::$_defaultExtensions, $collection->getExtensions()));
        }

        $extensions = (array)$extensions;
        if ($merge) {
            $extensions = array_unique(array_merge(static::$_defaultExtensions, $extensions));
        }

        return static::$_defaultExtensions = $extensions;
    }

    /**
     * Create a RouteBuilder for the provided path.
     *
     * @param string $path The path to set the builder to.
     * @param array<string, mixed> $options The options for the builder
     * @return \Cake\Routing\RouteBuilder
     */
    public static function createRouteBuilder(string $path, array $options = []): RouteBuilder
    {
        $defaults = [
            'routeClass' => static::defaultRouteClass(),
            'extensions' => static::$_defaultExtensions,
        ];
        $options += $defaults;

        return new RouteBuilder(static::$_collection, $path, [], [
            'routeClass' => $options['routeClass'],
            'extensions' => $options['extensions'],
        ]);
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
     * @param callable|array $params An array of routing defaults to add to each connected route.
     *   If you have no parameters, this argument can be a callable.
     * @param callable|null $callback The callback to invoke with the scoped collection.
     * @throws \InvalidArgumentException When an invalid callable is provided.
     * @return void
     * @deprecated 4.3.0 Use the non-static method `RouteBuilder::scope()` instead.
     */
    public static function scope(string $path, $params = [], $callback = null): void
    {
        deprecationWarning(
            '`Router::scope()` is deprecated, use the non-static method `RouteBuilder::scope()` instead.'
        );

        $options = [];
        if (is_array($params)) {
            $options = $params;
            unset($params['routeClass'], $params['extensions']);
        }
        $builder = static::createRouteBuilder('/', $options);
        $builder->scope($path, $params, $callback);
    }

    /**
     * Create prefixed routes.
     *
     * This method creates a scoped route collection that includes
     * relevant prefix information.
     *
     * The path parameter is used to generate the routing parameter name.
     * For example a path of `admin` would result in `'prefix' => 'Admin'` being
     * applied to all connected routes.
     *
     * The prefix name will be inflected to the dasherized version to create
     * the routing path. If you want a custom path name, use the `path` option.
     *
     * You can re-open a prefix as many times as necessary, as well as nest prefixes.
     * Nested prefixes will result in prefix values like `Admin/Api` which translates
     * to the `Controller\Admin\Api\` namespace.
     *
     * @param string $name The prefix name to use.
     * @param callable|array $params An array of routing defaults to add to each connected route.
     *   If you have no parameters, this argument can be a callable.
     * @param callable|null $callback The callback to invoke that builds the prefixed routes.
     * @return void
     * @deprecated 4.3.0 Use the non-static method `RouteBuilder::prefix()` instead.
     */
    public static function prefix(string $name, $params = [], $callback = null): void
    {
        deprecationWarning(
            '`Router::prefix()` is deprecated, use the non-static method `RouteBuilder::prefix()` instead.'
        );

        if (!is_array($params)) {
            $callback = $params;
            $params = [];
        }

        $path = $params['path'] ?? '/' . Inflector::dasherize($name);
        unset($params['path']);

        $params = array_merge($params, ['prefix' => Inflector::camelize($name)]);
        static::scope($path, $params, $callback);
    }

    /**
     * Add plugin routes.
     *
     * This method creates a scoped route collection that includes
     * relevant plugin information.
     *
     * The plugin name will be inflected to the dasherized version to create
     * the routing path. If you want a custom path name, use the `path` option.
     *
     * Routes connected in the scoped collection will have the correct path segment
     * prepended, and have a matching plugin routing key set.
     *
     * @param string $name The plugin name to build routes for
     * @param callable|array $options Either the options to use, or a callback
     * @param callable|null $callback The callback to invoke that builds the plugin routes.
     *   Only required when $options is defined
     * @return void
     * @deprecated 4.3.0 Use the non-static method `RouteBuilder::plugin()` instead.
     */
    public static function plugin(string $name, $options = [], $callback = null): void
    {
        deprecationWarning(
            '`Router::plugin()` is deprecated, use the non-static method `RouteBuilder::plugin()` instead.'
        );

        if (!is_array($options)) {
            $callback = $options;
            $options = [];
        }
        $params = ['plugin' => $name];
        $path = $options['path'] ?? '/' . Inflector::dasherize($name);
        if (isset($options['_namePrefix'])) {
            $params['_namePrefix'] = $options['_namePrefix'];
        }
        static::scope($path, $params, $callback);
    }

    /**
     * Get the route scopes and their connected routes.
     *
     * @return array<\Cake\Routing\Route\Route>
     */
    public static function routes(): array
    {
        return static::$_collection->routes();
    }

    /**
     * Get the RouteCollection inside the Router
     *
     * @return \Cake\Routing\RouteCollection
     */
    public static function getRouteCollection(): RouteCollection
    {
        return static::$_collection;
    }

    /**
     * Set the RouteCollection inside the Router
     *
     * @param \Cake\Routing\RouteCollection $routeCollection route collection
     * @return void
     */
    public static function setRouteCollection(RouteCollection $routeCollection): void
    {
        static::$_collection = $routeCollection;
    }

    /**
     * Inject route defaults from `_path` key
     *
     * @param array $url Route array with `_path` key
     * @return array
     */
    protected static function unwrapShortString(array $url)
    {
        foreach (['plugin', 'prefix', 'controller', 'action'] as $key) {
            if (array_key_exists($key, $url)) {
                throw new InvalidArgumentException(
                    "`$key` cannot be used when defining route targets with a string route path."
                );
            }
        }
        $url += static::parseRoutePath($url['_path']);
        $url += [
            'plugin' => false,
            'prefix' => false,
        ];
        unset($url['_path']);

        return $url;
    }

    /**
     * Parse a string route path
     *
     * String examples:
     * - Bookmarks::view
     * - Admin/Bookmarks::view
     * - Cms.Articles::edit
     * - Vendor/Cms.Management/Admin/Articles::view
     *
     * @param string $url Route path in [Plugin.][Prefix/]Controller::action format
     * @return array<string, string>
     */
    public static function parseRoutePath(string $url): array
    {
        if (isset(static::$_routePaths[$url])) {
            return static::$_routePaths[$url];
        }

        $regex = '#^
            (?:(?<plugin>[a-z0-9]+(?:/[a-z0-9]+)*)\.)?
            (?:(?<prefix>[a-z0-9]+(?:/[a-z0-9]+)*)/)?
            (?<controller>[a-z0-9]+)
            ::
            (?<action>[a-z0-9_]+)
            $#ix';

        if (!preg_match($regex, $url, $matches)) {
            throw new InvalidArgumentException("Could not parse a string route path `{$url}`.");
        }

        $defaults = [];

        if ($matches['plugin'] !== '') {
            $defaults['plugin'] = $matches['plugin'];
        }
        if ($matches['prefix'] !== '') {
            $defaults['prefix'] = $matches['prefix'];
        }
        $defaults['controller'] = $matches['controller'];
        $defaults['action'] = $matches['action'];

        static::$_routePaths[$url] = $defaults;

        return $defaults;
    }
}
