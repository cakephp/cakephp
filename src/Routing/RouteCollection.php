<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing;

use Cake\Network\Request;
use Cake\Routing\Route\Route;

/**
 * RouteCollection is used to operate on a set of routes.
 * It stores routes both in a linear list in order of connection, as well
 * as storing them in a hash-table indexed by a routes' name.
 *
 */
class RouteCollection implements \Countable {

/**
 * A hash table of routes indexed by route names.
 * Used for reverse routing.
 *
 * @var array
 */
	protected $_routeTable = [];

/**
 * A list of routes connected, in the order they were connected.
 * Used for parsing incoming urls.
 *
 * @var array
 */
	protected $_routes = [];

/**
 * The top most request's context. Updated whenever
 * requests are pushed/popped off the stack in Router.
 *
 * @var array
 */
	protected $_requestContext = [
		'_base' => '',
		'_port' => 80,
		'_scheme' => 'http',
		'_host' => 'localhost',
	];

/**
 * Add a route to the collection.
 *
 * Appends the route to the list of routes, and the route hashtable.
 * @param \Cake\Routing\Route\Route $route The route to add
 * @return void
 */
	public function add(Route $route) {
		$name = $route->getName();
		if (!isset($this->_routeTable[$name])) {
			$this->_routeTable[$name] = [];
		}
		$this->_routeTable[$name][] = $route;
		$this->_routes[] = $route;
	}

/**
 * Reverse route or match a $url array with the defined routes.
 * Returns either the string URL generate by the route, or false on failure.
 *
 * @param array $url The url to match.
 * @return void
 */
	public function match($url) {
		$names = $this->_getNames($url);
		unset($url['_name']);
		foreach ($names as $name) {
			if (isset($this->_routeTable[$name])) {
				$output = $this->_matchRoutes($this->_routeTable[$name], $url);
				if ($output) {
					return $output;
				}
			}
		}
		return $this->_matchRoutes($this->_routes, $url);
	}

/**
 * Matches a set of routes with a given $url and $params
 *
 * @param array $routes An array of routes to match against.
 * @param array $url The url to match.
 * @return mixed Either false on failure, or a string on success.
 */
	protected function _matchRoutes($routes, $url) {
		for ($i = 0, $len = count($routes); $i < $len; $i++) {
			$match = $routes[$i]->match($url, $this->_requestContext);
			if ($match) {
				return strlen($match) > 1 ? trim($match, '/') : $match;
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
			$name = $url['_name'];
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
				'_plugin._controller:%3$s',
				'_plugin._controller:_action',
				'_controller:_action'
			];
		}
		foreach ($fallbacks as $i => $template) {
			$fallbacks[$i] = sprintf($template, $plugin, $url['controller'], $url['action']);
		}
		if ($name) {
			array_unshift($fallbacks, $name);
		}
		return $fallbacks;
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
		$out = array();
		for ($i = 0, $len = count($this); $i < $len; $i++) {
			$r = $this->_routes[$i]->parse($url);
			if ($r !== false && $queryParameters) {
				$r['?'] = $queryParameters;
				return $r;
			}
			if ($r !== false) {
				return $r;
			}
		}
		return $out;
	}

/**
 * Promote a route (by default, the last one added) to the beginning of the list.
 * Also promotes the route to the head of its named slice in the named route
 * table.
 *
 * @param int $which A zero-based array index representing
 *    the route to move. For example,
 *    if 3 routes have been added, the last route would be 2.
 * @return bool Returns false if no route exists at the position
 *    specified by $which.
 */
	public function promote($which) {
		if ($which === null) {
			$which = count($this->_routes) - 1;
		}
		if (!isset($this->_routes[$which])) {
			return false;
		}
		$route =& $this->_routes[$which];
		unset($this->_routes[$which]);
		array_unshift($this->_routes, $route);

		$name = $route->getName();
		$routes = $this->_routeTable[$name];
		$index = array_search($route, $routes, true);
		unset($this->_routeTable[$name][$index]);
		array_unshift($this->_routeTable[$name], $route);
		return true;
	}

/**
 * Get route(s) out of the collection.
 *
 * If a string argument is provided, the first matching
 * route for the provided name will be returned.
 *
 * If an integer argument is provided, the route
 * with that index will be returned.
 *
 * @param mixed $index The index or name of the route you want.
 * @return mixed Either the route object or null.
 */
	public function get($index) {
		if (is_string($index)) {
			$routes = isset($this->_routeTable[$index]) ? $this->_routeTable[$index] : [null];
			return $routes[0];
		}
		return isset($this->_routes[$index]) ? $this->_routes[$index] : null;
	}

/**
 * Get the list of all connected routes.
 *
 * @return array.
 */
	public function all() {
		return $this->_routes;
	}

/**
 * Part of the countable interface.
 *
 * @return int The number of connected routes.
 */
	public function count() {
		return count($this->_routes);
	}

/**
 * Populate the request context used to generate URL's
 * Generally set to the last/most recent request.
 *
 * @param \Cake\Network\Request $request
 * @return void
 */
	public function setContext(Request $request) {
		$this->_requestContext = [
			'_base' => $request->base,
			'_port' => $request->port(),
			'_scheme' => $request->scheme(),
			'_host' => $request->host()
		];
	}

/**
 * Sets which extensions routes will use.
 *
 * @param array $extensions The extensions for routes to use.
 * @return void
 */
	public function parseExtensions(array $extensions) {
		foreach ($this->_routes as $route) {
			$route->parseExtensions($extensions);
		}
	}

}
