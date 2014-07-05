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

		/*
		// Index scopes by key params (for reverse routing).
		$plugin = isset($params['plugin']) ? $params['plugin'] : '';
		$prefix = isset($params['prefix']) ? $params['prefix'] : '';
		if (!isset(static::$_paramScopes[$plugin][$prefix])) {
			static::$_paramScopes[$plugin][$prefix] = $collection;
		} else {
			static::$_paramScopes[$plugin][$prefix]->merge($collection);
		}
		*/
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
			$route = false;
			if (isset($this->_named[$url['_name']])) {
				$route = $this->_named[$url['_name']];
			}
			if ($route) {
				unset($url['_name']);
				return $route->match($url + $route->defaults, $context);
			}
		}

		/*
		// Check the scope that matches key params.
		$plugin = isset($url['plugin']) ? $url['plugin'] : '';
		$prefix = isset($url['prefix']) ? $url['prefix'] : '';

		$collection = null;
		$attempts = [[$plugin, $prefix], ['', '']];
		foreach ($attempts as $attempt) {
			if (isset($this->_byParams[$attempt[0]][$attempt[1]])) {
				$collection = $this->_byParams[$attempt[0]][$attempt[1]];
				break;
			}
		}

		if ($collection) {
			$match = $collection->match($url, static::$_requestContext);
			if ($match !== false) {
				return $match;
			}
		}
		 */

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

	public function named() {
		return $this->_named;
	}

}
