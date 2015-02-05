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

use BadMethodCallException;
use Cake\Core\App;
use Cake\Routing\Route\Route;
use Cake\Utility\Inflector;
use InvalidArgumentException;

/**
 * Provides features for building routes inside scopes.
 *
 * Gives an easy to use way to build routes and append them
 * into a route collection.
 */
class RouteBuilder
{

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
    protected static $_resourceMap = [
        'index' => ['action' => 'index', 'method' => 'GET', 'path' => ''],
        'create' => ['action' => 'add', 'method' => 'POST', 'path' => ''],
        'view' => ['action' => 'view', 'method' => 'GET', 'path' => ':id'],
        'update' => ['action' => 'edit', 'method' => ['PUT', 'PATCH'], 'path' => ':id'],
        'delete' => ['action' => 'delete', 'method' => 'DELETE', 'path' => ':id'],
    ];

    /**
     * Default route class to use if none is provided in connect() options.
     *
     * @var string
     */
    protected $_routeClass = 'Cake\Routing\Route\Route';

    /**
     * The extensions that should be set into the routes connected.
     *
     * @var array
     */
    protected $_extensions = [];

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
     * The route collection routes should be added to.
     *
     * @var \Cake\Routing\RouteCollection
     */
    protected $_collection;

    /**
     * Constructor
     *
     * @param \Cake\Routing\RouteCollection $collection The route collection to append routes into.
     * @param string $path The path prefix the scope is for.
     * @param array $params The scope's routing parameters.
     * @param array $options Options list. Valid keys are:
     *
     *   - `routeClass` - The default route class to use when adding routes.
     *   - `extensions` - The extensions to connect when adding routes.
     */
    public function __construct($collection, $path, array $params = [], array $options = [])
    {
        $this->_collection = $collection;
        $this->_path = $path;
        $this->_params = $params;
        if (isset($options['routeClass'])) {
            $this->_routeClass = $options['routeClass'];
        }
        if (isset($options['extensions'])) {
            $this->_extensions = $options['extensions'];
        }
    }

    /**
     * Get or set default route class.
     *
     * @param string|null $routeClass Class name.
     * @return string|void
     */
    public function routeClass($routeClass = null)
    {
        if ($routeClass === null) {
            return $this->_routeClass;
        }
        $this->_routeClass = $routeClass;
    }

    /**
     * Get or set the extensions in this route builder's scope.
     *
     * Future routes connected in through this builder will have the connected
     * extensions applied. However, setting extensions does not modify existing routes.
     *
     * @param null|string|array $extensions Either the extensions to use or null.
     * @return array|void
     */
    public function extensions($extensions = null)
    {
        if ($extensions === null) {
            return $this->_extensions;
        }
        $this->_extensions = (array)$extensions;
    }

    /**
     * Get the path this scope is for.
     *
     * @return string
     */
    public function path()
    {
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
    public function params()
    {
        return $this->_params;
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
     * ```
     * $routes->resources('Posts');
     * ```
     *
     * Connect resource routes for the Comments controller in the
     * Comments plugin:
     *
     * ```
     * Router::plugin('Comments', function ($routes) {
     *   $routes->resources('Comments');
     * });
     * ```
     *
     * Plugins will create lower_case underscored resource routes. e.g
     * `/comments/comments`
     *
     * Connect resource routes for the Articles controller in the
     * Admin prefix:
     *
     * ```
     * Router::prefix('admin', function ($routes) {
     *   $routes->resources('Articles');
     * });
     * ```
     *
     * Prefixes will create lower_case underscored resource routes. e.g
     * `/admin/posts`
     *
     * You can create nested resources by passing a callback in:
     *
     * ```
     * $routes->resources('Articles', function ($routes) {
     *   $routes->resources('Comments');
     * });
     * ```
     *
     * The above would generate both resource routes for `/articles`, and `/articles/:article_id/comments`.
     *
     * ### Options:
     *
     * - 'id' - The regular expression fragment to use when matching IDs. By default, matches
     *    integer values and UUIDs.
     * - 'only' - Only connect the specific list of actions.
     * - 'actions' - Override the method names used for connecting actions.
     *
     * @param string $name A controller name to connect resource routes for.
     * @param array|callable $options Options to use when generating REST routes, or a callback.
     * @param callable|null $callback An optional callback to be executed in a nested scope. Nested
     *   scopes inherit the existing path and 'id' parameter.
     * @return array Array of mapped resources
     */
    public function resources($name, $options = [], $callback = null)
    {
        if (is_callable($options) && $callback === null) {
            $callback = $options;
            $options = [];
        }
        $options += [
            'connectOptions' => [],
            'id' => static::ID . '|' . static::UUID,
            'only' => ['index', 'update', 'create', 'view', 'delete'],
            'actions' => [],
        ];
        $options['only'] = (array)$options['only'];
        $connectOptions = $options['connectOptions'];

        $urlName = Inflector::underscore($name);

        $ext = null;
        if (!empty($options['_ext'])) {
            $ext = $options['_ext'];
        }

        foreach (static::$_resourceMap as $method => $params) {
            if (!in_array($method, $options['only'], true)) {
                continue;
            }

            $action = $params['action'];
            if (isset($options['actions'][$method])) {
                $action = $options['actions'][$method];
            }

            $url = '/' . implode('/', array_filter([$urlName, $params['path']]));
            $params = [
                'controller' => $name,
                'action' => $action,
                '_method' => $params['method'],
            ];
            $routeOptions = $connectOptions + [
                'id' => $options['id'],
                'pass' => ['id'],
                '_ext' => $ext,
            ];
            $this->connect($url, $params, $routeOptions);
        }

        if (is_callable($callback)) {
            $idName = Inflector::singularize($urlName) . '_id';
            $path = '/' . $urlName . '/:' . $idName;
            $this->scope($path, [], $callback);
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
     * ```
     * $routes->connect(
     *   '/:lang/:controller/:action/:id',
     *   [],
     *   ['id' => '[0-9]+', 'lang' => '[a-z]{3}']
     * );
     * ```
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
     *   regular route array. Ex. `'pass' => ['slug']`.
     * - `routeClass` is used to extend and change how individual routes parse requests
     *   and handle reverse routing, via a custom routing class.
     *   Ex. `'routeClass' => 'SlugRoute'`
     * - `_name` is used to define a specific name for routes. This can be used to optimize
     *   reverse routing lookups. If undefined a name will be generated for each
     *   connected route.
     * - `_ext` is an array of filename extensions that will be parsed out of the url if present.
     *   See {@link ScopedRouteCollection::extensions()}.
     * - `_method` Only match requests with specific HTTP verbs.
     *
     * Example of using the `_method` condition:
     *
     * `$routes->connect('/tasks', ['controller' => 'Tasks', 'action' => 'index', '_method' => 'GET']);`
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
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function connect($route, array $defaults = [], array $options = [])
    {
        if (empty($options['action'])) {
            $defaults += ['action' => 'index'];
        }

        if (empty($options['_ext'])) {
            $options['_ext'] = $this->_extensions;
        }

        if (empty($options['routeClass'])) {
            $options['routeClass'] = $this->_routeClass;
        }

        $route = $this->_makeRoute($route, $defaults, $options);
        $this->_collection->add($route, $options);
    }

    /**
     * Create a route object, or return the provided object.
     *
     * @param string|\Cake\Routing\Route\Route $route The route template or route object.
     * @param array $defaults Default parameters.
     * @param array $options Additional options parameters.
     * @return \Cake\Routing\Route\Route
     * @throws \InvalidArgumentException when route class or route object is invalid.
     * @throws \BadMethodCallException when the route to make conflicts with the current scope
     */
    protected function _makeRoute($route, $defaults, $options)
    {
        if (is_string($route)) {
            $routeClass = App::className($options['routeClass'], 'Routing/Route');
            if ($routeClass === false) {
                throw new InvalidArgumentException(sprintf(
                    'Cannot find route class %s',
                    $options['routeClass']
                ));
            }

            $route = str_replace('//', '/', $this->_path . $route);
            $route = $route === '/' ? $route : rtrim($route, '/');

            foreach ($this->_params as $param => $val) {
                if (isset($defaults[$param]) && $defaults[$param] !== $val) {
                    $msg = 'You cannot define routes that conflict with the scope. ' .
                        'Scope had %s = %s, while route had %s = %s';
                    throw new BadMethodCallException(sprintf(
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
        throw new InvalidArgumentException(
            'Route class not found, or route class is not a subclass of Cake\Routing\Route\Route'
        );
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
     * `$routes->redirect('/home/*', ['controller' => 'posts', 'action' => 'view']);`
     *
     * Redirects /home/* to /posts/view and passes the parameters to /posts/view. Using an array as the
     * redirect destination allows you to use other routes to define where an URL string should be redirected to.
     *
     * `$routes-redirect('/posts/*', 'http://google.com', ['status' => 302]);`
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
     * @return void
     */
    public function redirect($route, $url, array $options = [])
    {
        $options['routeClass'] = 'Cake\Routing\Route\RedirectRoute';
        if (is_string($url)) {
            $url = ['redirect' => $url];
        }
        $this->connect($route, $url, $options);
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
    public function prefix($name, callable $callback)
    {
        $name = Inflector::underscore($name);
        $path = '/' . $name;
        if (isset($this->_params['prefix'])) {
            $name = $this->_params['prefix'] . '/' . $name;
        }
        $params = ['prefix' => $name];
        $this->scope($path, $params, $callback);
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
     * @param string $name The plugin name to build routes for
     * @param array|callable $options Either the options to use, or a callback
     * @param callable|null $callback The callback to invoke that builds the plugin routes
     *   Only required when $options is defined.
     * @return void
     */
    public function plugin($name, $options = [], $callback = null)
    {
        if ($callback === null) {
            $callback = $options;
            $options = [];
        }
        $params = ['plugin' => $name] + $this->_params;
        if (empty($options['path'])) {
            $options['path'] = '/' . Inflector::underscore($name);
        }
        $this->scope($options['path'], $params, $callback);
    }

    /**
     * Create a new routing scope.
     *
     * Scopes created with this method will inherit the properties of the scope they are
     * added to. This means that both the current path and parameters will be appended
     * to the supplied parameters.
     *
     * @param string $path The path to create a scope for.
     * @param array|callable $params Either the parameters to add to routes, or a callback.
     * @param callable|null $callback The callback to invoke that builds the plugin routes.
     *   Only required when $params is defined.
     * @return void
     * @throws \InvalidArgumentException when there is no callable parameter.
     */
    public function scope($path, $params, $callback = null)
    {
        if ($callback === null) {
            $callback = $params;
            $params = [];
        }
        if (!is_callable($callback)) {
            $msg = 'Need a callable function/object to connect routes.';
            throw new \InvalidArgumentException($msg);
        }

        if ($this->_path !== '/') {
            $path = $this->_path . $path;
        }
        $params = $params + $this->_params;
        $builder = new static($this->_collection, $path, $params, [
            'routeClass' => $this->_routeClass,
            'extensions' => $this->_extensions
        ]);
        $callback($builder);
    }

    /**
     * Connect the `/:controller` and `/:controller/:action/*` fallback routes.
     *
     * This is a shortcut method for connecting fallback routes in a given scope.
     *
     * @param string|null $routeClass the route class to use, uses the default routeClass
     *   if not specified
     * @return void
     */
    public function fallbacks($routeClass = null)
    {
        $routeClass = $routeClass ?: $this->_routeClass;
        $this->connect('/:controller', ['action' => 'index'], compact('routeClass'));
        $this->connect('/:controller/:action/*', [], compact('routeClass'));
    }
}
