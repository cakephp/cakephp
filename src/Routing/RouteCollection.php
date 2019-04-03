<?php
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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing;

use Cake\Routing\Exception\DuplicateNamedRouteException;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Route\Route;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Contains a collection of routes.
 *
 * Provides an interface for adding/removing routes
 * and parsing/generating URLs with the routes it contains.
 *
 * @internal
 */
class RouteCollection
{

    /**
     * The routes connected to this collection.
     *
     * @var array
     */
    protected $_routeTable = [];

    /**
     * The routes connected to this collection.
     *
     * @var \Cake\Routing\Route\Route[]
     */
    protected $_routes = [];

    /**
     * The hash map of named routes that are in this collection.
     *
     * @var \Cake\Routing\Route\Route[]
     */
    protected $_named = [];

    /**
     * Routes indexed by path prefix.
     *
     * @var array
     */
    protected $_paths = [];

    /**
     * A map of middleware names and the related objects.
     *
     * @var array
     */
    protected $_middleware = [];

    /**
     * A map of middleware group names and the related middleware names.
     *
     * @var array
     */
    protected $_middlewareGroups = [];

    /**
     * A map of paths and the list of applicable middleware.
     *
     * @var array
     */
    protected $_middlewarePaths = [];

    /**
     * Route extensions
     *
     * @var array
     */
    protected $_extensions = [];

    /**
     * Add a route to the collection.
     *
     * @param \Cake\Routing\Route\Route $route The route object to add.
     * @param array $options Additional options for the route. Primarily for the
     *   `_name` option, which enables named routes.
     * @return void
     */
    public function add(Route $route, array $options = [])
    {
        $this->_routes[] = $route;

        // Explicit names
        if (isset($options['_name'])) {
            if (isset($this->_named[$options['_name']])) {
                $matched = $this->_named[$options['_name']];
                throw new DuplicateNamedRouteException([
                    'name' => $options['_name'],
                    'url' => $matched->template,
                    'duplicate' => $matched,
                ]);
            }
            $this->_named[$options['_name']] = $route;
        }

        // Generated names.
        $name = $route->getName();
        if (!isset($this->_routeTable[$name])) {
            $this->_routeTable[$name] = [];
        }
        $this->_routeTable[$name][] = $route;

        // Index path prefixes (for parsing)
        $path = $route->staticPath();
        if (empty($this->_paths[$path])) {
            $this->_paths[$path] = [];
            krsort($this->_paths);
        }
        $this->_paths[$path][] = $route;

        $extensions = $route->getExtensions();
        if (count($extensions) > 0) {
            $this->setExtensions($extensions);
        }
    }

    /**
     * Takes the URL string and iterates the routes until one is able to parse the route.
     *
     * @param string $url URL to parse.
     * @param string $method The HTTP method to use.
     * @return array An array of request parameters parsed from the URL.
     * @throws \Cake\Routing\Exception\MissingRouteException When a URL has no matching route.
     */
    public function parse($url, $method = '')
    {
        $decoded = urldecode($url);
        foreach (array_keys($this->_paths) as $path) {
            if (strpos($decoded, $path) !== 0) {
                continue;
            }

            $queryParameters = null;
            if (strpos($url, '?') !== false) {
                list($url, $queryParameters) = explode('?', $url, 2);
                parse_str($queryParameters, $queryParameters);
            }
            /* @var \Cake\Routing\Route\Route $route */
            foreach ($this->_paths[$path] as $route) {
                $r = $route->parse($url, $method);
                if ($r === false) {
                    continue;
                }
                if ($queryParameters) {
                    $r['?'] = $queryParameters;
                }

                return $r;
            }
        }

        $exceptionProperties = ['url' => $url];
        if ($method !== '') {
            // Ensure that if the method is included, it is the first element of
            // the array, to match the order that the strings are printed in the
            // MissingRouteException error message, $_messageTemplateWithMethod.
            $exceptionProperties = array_merge(['method' => $method], $exceptionProperties);
        }
        throw new MissingRouteException($exceptionProperties);
    }

    /**
     * Takes the ServerRequestInterface, iterates the routes until one is able to parse the route.
     *
     * @param \Psr\Http\Messages\ServerRequestInterface $request The request to parse route data from.
     * @return array An array of request parameters parsed from the URL.
     * @throws \Cake\Routing\Exception\MissingRouteException When a URL has no matching route.
     */
    public function parseRequest(ServerRequestInterface $request)
    {
        $uri = $request->getUri();
        $urlPath = urldecode($uri->getPath());
        foreach (array_keys($this->_paths) as $path) {
            if (strpos($urlPath, $path) !== 0) {
                continue;
            }

            /* @var \Cake\Routing\Route\Route $route */
            foreach ($this->_paths[$path] as $route) {
                $r = $route->parseRequest($request);
                if ($r === false) {
                    continue;
                }
                if ($uri->getQuery()) {
                    parse_str($uri->getQuery(), $queryParameters);
                    $r['?'] = $queryParameters;
                }

                return $r;
            }
        }
        throw new MissingRouteException(['url' => $urlPath]);
    }

    /**
     * Get the set of names from the $url. Accepts both older style array urls,
     * and newer style urls containing '_name'
     *
     * @param array $url The url to match.
     * @return array The set of names of the url
     */
    protected function _getNames($url)
    {
        $plugin = false;
        if (isset($url['plugin']) && $url['plugin'] !== false) {
            $plugin = strtolower($url['plugin']);
        }
        $prefix = false;
        if (isset($url['prefix']) && $url['prefix'] !== false) {
            $prefix = strtolower($url['prefix']);
        }
        $controller = strtolower($url['controller']);
        $action = strtolower($url['action']);

        $names = [
            "${controller}:${action}",
            "${controller}:_action",
            "_controller:${action}",
            '_controller:_action',
        ];

        // No prefix, no plugin
        if ($prefix === false && $plugin === false) {
            return $names;
        }

        // Only a plugin
        if ($prefix === false) {
            return [
                "${plugin}.${controller}:${action}",
                "${plugin}.${controller}:_action",
                "${plugin}._controller:${action}",
                "${plugin}._controller:_action",
                "_plugin.${controller}:${action}",
                "_plugin.${controller}:_action",
                "_plugin._controller:${action}",
                '_plugin._controller:_action',
            ];
        }

        // Only a prefix
        if ($plugin === false) {
            return [
                "${prefix}:${controller}:${action}",
                "${prefix}:${controller}:_action",
                "${prefix}:_controller:${action}",
                "${prefix}:_controller:_action",
                "_prefix:${controller}:${action}",
                "_prefix:${controller}:_action",
                "_prefix:_controller:${action}",
                '_prefix:_controller:_action',
            ];
        }

        // Prefix and plugin has the most options
        // as there are 4 factors.
        return [
            "${prefix}:${plugin}.${controller}:${action}",
            "${prefix}:${plugin}.${controller}:_action",
            "${prefix}:${plugin}._controller:${action}",
            "${prefix}:${plugin}._controller:_action",
            "${prefix}:_plugin.${controller}:${action}",
            "${prefix}:_plugin.${controller}:_action",
            "${prefix}:_plugin._controller:${action}",
            "${prefix}:_plugin._controller:_action",
            "_prefix:${plugin}.${controller}:${action}",
            "_prefix:${plugin}.${controller}:_action",
            "_prefix:${plugin}._controller:${action}",
            "_prefix:${plugin}._controller:_action",
            "_prefix:_plugin.${controller}:${action}",
            "_prefix:_plugin.${controller}:_action",
            "_prefix:_plugin._controller:${action}",
            '_prefix:_plugin._controller:_action',
        ];
    }

    /**
     * Reverse route or match a $url array with the connected routes.
     *
     * Returns either the URL string generated by the route,
     * or throws an exception on failure.
     *
     * @param array $url The URL to match.
     * @param array $context The request context to use. Contains _base, _port,
     *    _host, _scheme and params keys.
     * @return string The URL string on match.
     * @throws \Cake\Routing\Exception\MissingRouteException When no route could be matched.
     */
    public function match($url, $context)
    {
        // Named routes support optimization.
        if (isset($url['_name'])) {
            $name = $url['_name'];
            unset($url['_name']);
            if (isset($this->_named[$name])) {
                $route = $this->_named[$name];
                $out = $route->match($url + $route->defaults, $context);
                if ($out) {
                    return $out;
                }
                throw new MissingRouteException([
                    'url' => $name,
                    'context' => $context,
                    'message' => 'A named route was found for "%s", but matching failed.',
                ]);
            }
            throw new MissingRouteException(['url' => $name, 'context' => $context]);
        }

        foreach ($this->_getNames($url) as $name) {
            if (empty($this->_routeTable[$name])) {
                continue;
            }
            /* @var \Cake\Routing\Route\Route $route */
            foreach ($this->_routeTable[$name] as $route) {
                $match = $route->match($url, $context);
                if ($match) {
                    return strlen($match) > 1 ? trim($match, '/') : $match;
                }
            }
        }
        throw new MissingRouteException(['url' => var_export($url, true), 'context' => $context]);
    }

    /**
     * Get all the connected routes as a flat list.
     *
     * @return \Cake\Routing\Route\Route[]
     */
    public function routes()
    {
        return $this->_routes;
    }

    /**
     * Get the connected named routes.
     *
     * @return \Cake\Routing\Route\Route[]
     */
    public function named()
    {
        return $this->_named;
    }

    /**
     * Get/set the extensions that the route collection could handle.
     *
     * @param null|string|array $extensions Either the list of extensions to set,
     *   or null to get.
     * @param bool $merge Whether to merge with or override existing extensions.
     *   Defaults to `true`.
     * @return array The valid extensions.
     * @deprecated 3.5.0 Use getExtensions()/setExtensions() instead.
     */
    public function extensions($extensions = null, $merge = true)
    {
        if ($extensions !== null) {
            $this->setExtensions((array)$extensions, $merge);
        }

        return $this->getExtensions();
    }

    /**
     * Get the extensions that can be handled.
     *
     * @return array The valid extensions.
     */
    public function getExtensions()
    {
        return $this->_extensions;
    }

    /**
     * Set the extensions that the route collection can handle.
     *
     * @param array $extensions The list of extensions to set.
     * @param bool $merge Whether to merge with or override existing extensions.
     *   Defaults to `true`.
     * @return $this
     */
    public function setExtensions(array $extensions, $merge = true)
    {
        if ($merge) {
            $extensions = array_unique(array_merge(
                $this->_extensions,
                $extensions
            ));
        }
        $this->_extensions = $extensions;

        return $this;
    }

    /**
     * Register a middleware with the RouteCollection.
     *
     * Once middleware has been registered, it can be applied to the current routing
     * scope or any child scopes that share the same RouteCollection.
     *
     * @param string $name The name of the middleware. Used when applying middleware to a scope.
     * @param callable $middleware The middleware object to register.
     * @return $this
     */
    public function registerMiddleware($name, callable $middleware)
    {
        if (is_string($middleware)) {
            throw new RuntimeException("The '$name' middleware is not a callable object.");
        }
        $this->_middleware[$name] = $middleware;

        return $this;
    }

    /**
     * Add middleware to a middleware group
     *
     * @param string $name Name of the middleware group
     * @param array $middlewareNames Names of the middleware
     * @return $this
     */
    public function middlewareGroup($name, array $middlewareNames)
    {
        if ($this->hasMiddleware($name)) {
            $message = "Cannot add middleware group '$name'. A middleware by this name has already been registered.";
            throw new RuntimeException($message);
        }

        foreach ($middlewareNames as $middlewareName) {
            if (!$this->hasMiddleware($middlewareName)) {
                $message = "Cannot add '$middlewareName' middleware to group '$name'. It has not been registered.";
                throw new RuntimeException($message);
            }
        }

        $this->_middlewareGroups[$name] = $middlewareNames;

        return $this;
    }

    /**
     * Check if the named middleware group has been created.
     *
     * @param string $name The name of the middleware group to check.
     * @return bool
     */
    public function hasMiddlewareGroup($name)
    {
        return array_key_exists($name, $this->_middlewareGroups);
    }

    /**
     * Check if the named middleware has been registered.
     *
     * @param string $name The name of the middleware to check.
     * @return bool
     */
    public function hasMiddleware($name)
    {
        return isset($this->_middleware[$name]);
    }

    /**
     * Check if the named middleware or middleware group has been registered.
     *
     * @param string $name The name of the middleware to check.
     * @return bool
     */
    public function middlewareExists($name)
    {
        return $this->hasMiddleware($name) || $this->hasMiddlewareGroup($name);
    }

    /**
     * Apply a registered middleware(s) for the provided path
     *
     * @param string $path The URL path to register middleware for.
     * @param string[] $middleware The middleware names to add for the path.
     * @return $this
     */
    public function applyMiddleware($path, array $middleware)
    {
        foreach ($middleware as $name) {
            if (!$this->hasMiddleware($name) && !$this->hasMiddlewareGroup($name)) {
                $message = "Cannot apply '$name' middleware or middleware group to path '$path'. It has not been registered.";
                throw new RuntimeException($message);
            }
        }
        // Matches route element pattern in Cake\Routing\Route
        $path = '#^' . preg_quote($path, '#') . '#';
        $path = preg_replace('/\\\\:([a-z0-9-_]+(?<![-_]))/i', '[^/]+', $path);

        if (!isset($this->_middlewarePaths[$path])) {
            $this->_middlewarePaths[$path] = [];
        }
        $this->_middlewarePaths[$path] = array_merge($this->_middlewarePaths[$path], $middleware);

        return $this;
    }

    /**
     * Get an array of middleware given a list of names
     *
     * @param array $names The names of the middleware or groups to fetch
     * @return array An array of middleware. If any of the passed names are groups,
     *   the groups middleware will be flattened into the returned list.
     * @throws \RuntimeException when a requested middleware does not exist.
     */
    public function getMiddleware(array $names)
    {
        $out = [];
        foreach ($names as $name) {
            if ($this->hasMiddlewareGroup($name)) {
                $out = array_merge($out, $this->getMiddleware($this->_middlewareGroups[$name]));
                continue;
            }
            if (!$this->hasMiddleware($name)) {
                $message = "The middleware named '$name' has not been registered. Use registerMiddleware() to define it.";
                throw new RuntimeException($message);
            }
            $out[] = $this->_middleware[$name];
        }

        return $out;
    }
}
