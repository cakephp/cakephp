<?php
namespace Cake\Routing;

use Cake\Routing\Route\Route;

class RouteCollection {
	
	protected $_routes = array();

	protected $_routeTable = array();

	public function add(Route $route) {
		$this->_routes[] = $route;
	}

	public function match($url, $params = null) {
		$output = false;
		for ($i = 0, $len = count($this->_routes); $i < $len; $i++) {
			$originalUrl = $url;
			$route =& $this->_routes[$i];

			if (isset($route->options['persist'], $params)) {
				$url = $route->persistParams($url, $params);
			}

			if ($match = $route->match($url)) {
				$output = trim($match, '/');
				break;
			}
			$url = $originalUrl;
		}
		return $output;
	}

	public function parse($url) {
		$out = array();
		for ($i = 0, $len = count($this->_routes); $i < $len; $i++) {
			$route = $this->_routes[$i];

			if (($r = $route->parse($url)) !== false) {
				$out = $r;
				break;
			}
		}
		return $out;
	}

/**
 * Promote a route (by default, the last one added) to the beginning of the list.
 * Does not modify route ordering stored in the hashtable lookups.
 *
 * @param integer $which A zero-based array index representing
 *    the route to move. For example,
 *    if 3 routes have been added, the last route would be 2.
 * @return boolean Returns false if no route exists at the position
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
		return true;
	}

}
