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

use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Route\Route;

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
     * Routes indexed by path prefix.
     *
     * @var array
     */
    protected $_paths = [];

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

        $extensions = $route->extensions();
        if ($extensions) {
            $this->extensions($extensions);
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
        throw new MissingRouteException(['url' => $url]);
    }

    /**
     * Get the set of names from the $url.  Accepts both older style array urls,
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
            "_controller:_action"
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
                "_plugin._controller:_action",
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
                "_prefix:_controller:_action"
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
            "_prefix:_plugin._controller:_action",
        ];
    }

    /**
     * Reverse route or match a $url array with the defined routes.
     * Returns either the string URL generate by the route, or false on failure.
     *
     * @param array $url The url to match.
     * @param array $context The request context to use. Contains _base, _port,
     *    _host, _scheme and params keys.
     * @return string|false Either a string on match, or false on failure.
     * @throws \Cake\Routing\Exception\MissingRouteException when a route cannot be matched.
     */
    public function match($url, $context)
    {
        // Named routes support optimization.
        if (isset($url['_name'])) {
            $name = $url['_name'];
            unset($url['_name']);
            $out = false;
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
     * @return array
     */
    public function routes()
    {
        return $this->_routes;
    }

    /**
     * Get the connected named routes.
     *
     * @return array
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
     */
    public function extensions($extensions = null, $merge = true)
    {
        if ($extensions === null) {
            return $this->_extensions;
        }

        $extensions = (array)$extensions;
        if ($merge) {
            $extensions = array_unique(array_merge(
                $this->_extensions,
                $extensions
            ));
        }

        return $this->_extensions = $extensions;
    }
}
