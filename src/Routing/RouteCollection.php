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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing;

use Cake\Routing\Exception\DuplicateNamedRouteException;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Route\Route;
use Closure;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

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
     * @var array<string, array<\Cake\Routing\Route\Route>>
     */
    protected array $_routeTable = [];

    /**
     * The hash map of named routes that are in this collection.
     *
     * @var array<\Cake\Routing\Route\Route>
     */
    protected array $_named = [];

    /**
     * Routes indexed by static path.
     *
     * @var array<string, array<\Cake\Routing\Route\Route>>
     */
    protected array $staticPaths = [];

    /**
     * Routes indexed by path prefix.
     *
     * @var array<string, array<\Cake\Routing\Route\Route>>
     */
    protected array $_paths = [];

    /**
     * A map of middleware names and the related objects.
     *
     * @var array
     */
    protected array $_middleware = [];

    /**
     * A map of middleware group names and the related middleware names.
     *
     * @var array
     */
    protected array $_middlewareGroups = [];

    /**
     * Route extensions
     *
     * @var array<string>
     */
    protected array $_extensions = [];

    /**
     * Add a route to the collection.
     *
     * @param \Cake\Routing\Route\Route $route The route object to add.
     * @param array<string, mixed> $options Additional options for the route. Primarily for the
     *   `_name` option, which enables named routes.
     * @return void
     */
    public function add(Route $route, array $options = []): void
    {
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
        $this->_routeTable[$name] ??= [];
        $this->_routeTable[$name][] = $route;

        // Index path prefixes (for parsing)
        $path = $route->staticPath();

        $extensions = $route->getExtensions();
        if ($extensions !== []) {
            $this->setExtensions($extensions);
        }

        if ($path === $route->template) {
            $this->staticPaths[$path][] = $route;
        }

        $this->_paths[$path][] = $route;
    }

    /**
     * Takes the ServerRequestInterface, iterates the routes until one is able to parse the route.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to parse route data from.
     * @return array An array of request parameters parsed from the URL.
     * @throws \Cake\Routing\Exception\MissingRouteException When a URL has no matching route.
     */
    public function parseRequest(ServerRequestInterface $request): array
    {
        $uri = $request->getUri();
        $urlPath = $uri->getPath();
        if (strpos($urlPath, '%') !== false) {
            // decode urlencoded segments, but don't decode %2f aka /
            $parts = explode('/', $urlPath);
            $parts = array_map(
                fn(string $part) => str_replace('/', '%2f', urldecode($part)),
                $parts,
            );
            $urlPath = implode('/', $parts);
        }
        if ($urlPath !== '/') {
            $urlPath = rtrim($urlPath, '/');
        }
        if (isset($this->staticPaths[$urlPath])) {
            foreach ($this->staticPaths[$urlPath] as $route) {
                $r = $route->parseRequest($request);
                if ($r === null) {
                    continue;
                }
                if ($uri->getQuery()) {
                    parse_str($uri->getQuery(), $queryParameters);
                    $r['?'] = array_merge($r['?'] ?? [], $queryParameters);
                }

                return $r;
            }
        }

        // Sort path segments matching longest paths first.
        krsort($this->_paths);

        foreach ($this->_paths as $path => $routes) {
            if (!str_starts_with($urlPath, $path)) {
                continue;
            }

            foreach ($routes as $route) {
                $r = $route->parseRequest($request);
                if ($r === null) {
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
     * @return array<string> The set of names of the url
     */
    protected function _getNames(array $url): array
    {
        $plugin = false;
        if (isset($url['plugin']) && $url['plugin'] !== false) {
            $plugin = strtolower($url['plugin']);
        }
        $prefix = false;
        if (isset($url['prefix']) && $url['prefix'] !== false) {
            $prefix = strtolower($url['prefix']);
        }
        $controller = isset($url['controller']) ? strtolower($url['controller']) : null;
        $action = strtolower($url['action']);

        $names = [
            "{$controller}:{$action}",
            "{$controller}:_action",
            "_controller:{$action}",
            '_controller:_action',
        ];

        // No prefix, no plugin
        if ($prefix === false && $plugin === false) {
            return $names;
        }

        // Only a plugin
        if ($prefix === false) {
            return [
                "{$plugin}.{$controller}:{$action}",
                "{$plugin}.{$controller}:_action",
                "{$plugin}._controller:{$action}",
                "{$plugin}._controller:_action",
                "_plugin.{$controller}:{$action}",
                "_plugin.{$controller}:_action",
                "_plugin._controller:{$action}",
                '_plugin._controller:_action',
            ];
        }

        // Only a prefix
        if ($plugin === false) {
            return [
                "{$prefix}:{$controller}:{$action}",
                "{$prefix}:{$controller}:_action",
                "{$prefix}:_controller:{$action}",
                "{$prefix}:_controller:_action",
                "_prefix:{$controller}:{$action}",
                "_prefix:{$controller}:_action",
                "_prefix:_controller:{$action}",
                '_prefix:_controller:_action',
            ];
        }

        // Prefix and plugin has the most options
        // as there are 4 factors.
        return [
            "{$prefix}:{$plugin}.{$controller}:{$action}",
            "{$prefix}:{$plugin}.{$controller}:_action",
            "{$prefix}:{$plugin}._controller:{$action}",
            "{$prefix}:{$plugin}._controller:_action",
            "{$prefix}:_plugin.{$controller}:{$action}",
            "{$prefix}:_plugin.{$controller}:_action",
            "{$prefix}:_plugin._controller:{$action}",
            "{$prefix}:_plugin._controller:_action",
            "_prefix:{$plugin}.{$controller}:{$action}",
            "_prefix:{$plugin}.{$controller}:_action",
            "_prefix:{$plugin}._controller:{$action}",
            "_prefix:{$plugin}._controller:_action",
            "_prefix:_plugin.{$controller}:{$action}",
            "_prefix:_plugin.{$controller}:_action",
            "_prefix:_plugin._controller:{$action}",
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
    public function match(array $url, array $context): string
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
                    'message' => "A named route was found for `{$name}`, but matching failed.",
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
                    return $match === '/' ? $match : trim($match, '/');
                }
            }
        }
        throw new MissingRouteException(['url' => var_export($url, true), 'context' => $context]);
    }

    /**
     * Get all the connected routes as a flat list.
     *
     * Routes will not be returned in the order they were added.
     *
     * @return array<\Cake\Routing\Route\Route>
     */
    public function routes(): array
    {
        krsort($this->_paths);

        return array_reduce(
            $this->_paths,
            'array_merge',
            [],
        );
    }

    /**
     * Get the connected named routes.
     *
     * @return array<\Cake\Routing\Route\Route>
     */
    public function named(): array
    {
        return $this->_named;
    }

    /**
     * Get the extensions that can be handled.
     *
     * @return array<string> The valid extensions.
     */
    public function getExtensions(): array
    {
        return $this->_extensions;
    }

    /**
     * Set the extensions that the route collection can handle.
     *
     * @param array<string> $extensions The list of extensions to set.
     * @param bool $merge Whether to merge with or override existing extensions.
     *   Defaults to `true`.
     * @return $this
     */
    public function setExtensions(array $extensions, bool $merge = true)
    {
        if ($merge) {
            $extensions = array_unique(array_merge(
                $this->_extensions,
                $extensions,
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
     * @param \Psr\Http\Server\MiddlewareInterface|\Closure|string $middleware The middleware to register.
     * @return $this
     */
    public function registerMiddleware(string $name, MiddlewareInterface|Closure|string $middleware)
    {
        $this->_middleware[$name] = $middleware;

        return $this;
    }

    /**
     * Add middleware to a middleware group
     *
     * @param string $name Name of the middleware group
     * @param array<string> $middlewareNames Names of the middleware
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function middlewareGroup(string $name, array $middlewareNames)
    {
        if ($this->hasMiddleware($name)) {
            $message = "Cannot add middleware group '{$name}'. A middleware by this name has already been registered.";
            throw new InvalidArgumentException($message);
        }

        foreach ($middlewareNames as $middlewareName) {
            if (!$this->hasMiddleware($middlewareName)) {
                $message = "Cannot add '{$middlewareName}' middleware to group '{$name}'. It has not been registered.";
                throw new InvalidArgumentException($message);
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
    public function hasMiddlewareGroup(string $name): bool
    {
        return array_key_exists($name, $this->_middlewareGroups);
    }

    /**
     * Check if the named middleware has been registered.
     *
     * @param string $name The name of the middleware to check.
     * @return bool
     */
    public function hasMiddleware(string $name): bool
    {
        return isset($this->_middleware[$name]);
    }

    /**
     * Check if the named middleware or middleware group has been registered.
     *
     * @param string $name The name of the middleware to check.
     * @return bool
     */
    public function middlewareExists(string $name): bool
    {
        return $this->hasMiddleware($name) || $this->hasMiddlewareGroup($name);
    }

    /**
     * Get an array of middleware given a list of names
     *
     * @param array<string> $names The names of the middleware or groups to fetch
     * @return array An array of middleware. If any of the passed names are groups,
     *   the groups middleware will be flattened into the returned list.
     * @throws \InvalidArgumentException when a requested middleware does not exist.
     */
    public function getMiddleware(array $names): array
    {
        $out = [];
        foreach ($names as $name) {
            if ($this->hasMiddlewareGroup($name)) {
                $out = array_merge($out, $this->getMiddleware($this->_middlewareGroups[$name]));
                continue;
            }
            if (!$this->hasMiddleware($name)) {
                throw new InvalidArgumentException(sprintf(
                    'The middleware named `%s` has not been registered. Use registerMiddleware() to define it.',
                    $name,
                ));
            }
            $out[] = $this->_middleware[$name];
        }

        return $out;
    }
}
