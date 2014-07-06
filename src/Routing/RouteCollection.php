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
use Cake\Routing\Error\MissingRouteException;
use Cake\Routing\Router;
use Cake\Routing\Route\Route;
use Cake\Utility\Inflector;

/**
 * Contains a collection of routes.
 *
 * Provides an interface for adding/removing routes
 * and parsing/generating URLs with the routes it contains.
 *
 * @internal
 */
class RouteCollection {

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
 * Add a route to the collection.
 *
 */
	public function add(Route $route, $options) {
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
	}

/**
 * Takes the URL string and iterates the routes until one is able to parse the route.
 *
 * @param string $url Url to parse.
 * @return array An array of request parameters parsed from the url.
 */
	public function parse($url) {
		foreach (array_keys($this->_paths) as $path) {
			if (strpos($url, $path) === 0) {
				break;
			}
		}

		$queryParameters = null;
		if (strpos($url, '?') !== false) {
			list($url, $queryParameters) = explode('?', $url, 2);
			parse_str($queryParameters, $queryParameters);
		}
		foreach ($this->_paths[$path] as $route) {
			$r = $route->parse($url);
			if ($r === false) {
				continue;
			}
			if ($queryParameters) {
				$r['?'] = $queryParameters;
			}
			return $r;
		}
		throw new MissingRouteException(['url' => $url]);
	}

/**
 * Get the set of names from the $url.  Accepts both older style array urls,
 * and newer style urls containing '_name'
 *
 * @param array $url The url to match.
 * @return string The name of the url
 */
	protected function _getNames($url) {
		$plugin = false;
		if (isset($url['plugin'])) {
			$plugin = strtolower($url['plugin']);
		}
		$controller = strtolower($url['controller']);
		$action = strtolower($url['action']);

		$fallbacks = [
			"${controller}:${action}",
			"${controller}:_action",
			"_controller:${action}",
			"_controller:_action"
		];
		if ($plugin) {
			$fallbacks = [
				"${plugin}.${controller}:${action}",
				"${plugin}.${controller}:_action",
				"${plugin}._controller:${action}",
				"${plugin}._controller:_action",
				"_plugin.${controller}:${action}",
				"_plugin._controller:${action}",
				"_plugin._controller:_action",
				"_controller:_action"
			];
		}
		return $fallbacks;
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
		// Named routes support hack.
		if (isset($url['_name'])) {
			$name = $url['_name'];
			unset($url['_name']);
			$out = false;
			if (isset($this->_named[$name])) {
				$route = $this->_named[$name];
				$out = $route->match($url + $route->defaults, $context);
			}
			if ($out) {
				return $out;
			}
			throw new MissingRouteException(['url' => $name]);
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
		throw new MissingRouteException(['url' => var_export($url, true)]);
	}

/**
 * Get all the connected routes as a flat list.
 *
 * @return array
 */
	public function routes() {
		return $this->_routes;
	}

/**
 * Get the connected named routes.
 *
 * @return array
 */
	public function named() {
		return $this->_named;
	}

}
