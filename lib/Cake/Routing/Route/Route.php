<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Routing\Route;

use Cake\Routing\Router;

/**
 * A single Route used by the Router to connect requests to
 * parameter maps.
 *
 * Not normally created as a standalone.  Use Router::connect() to create
 * Routes for your application.
 *
 * @package Cake.Routing.Route
 */
class Route {

/**
 * An array of named segments in a Route.
 * `/:controller/:action/:id` has 3 key elements
 *
 * @var array
 */
	public $keys = array();

/**
 * An array of additional parameters for the Route.
 *
 * @var array
 */
	public $options = array();

/**
 * Default parameters for a Route
 *
 * @var array
 */
	public $defaults = array();

/**
 * The routes template string.
 *
 * @var string
 */
	public $template = null;

/**
 * Is this route a greedy route?  Greedy routes have a `/*` in their
 * template
 *
 * @var string
 */
	protected $_greedy = false;

/**
 * The compiled route regular expression
 *
 * @var string
 */
	protected $_compiledRoute = null;

/**
 * The name for a route.  Fetch with Route::getName();
 *
 * @var string
 */
	protected $_name = null;

/**
 * HTTP header shortcut map.  Used for evaluating header-based route expressions.
 *
 * @var array
 */
	protected $_headerMap = array(
		'type' => 'content_type',
		'method' => 'request_method',
		'server' => 'server_name'
	);

/**
 * Constructor for a Route
 *
 * Using $options['_name'] a specific name can be given to a route.
 * Otherwise a route name will be generated.
 *
 * @param string $template Template string with parameter placeholders
 * @param array $defaults Array of defaults for the route.
 * @param array $options Array of additional options for the Route
 */
	public function __construct($template, $defaults = array(), $options = array()) {
		$this->template = $template;
		$this->defaults = (array)$defaults;
		$this->options = (array)$options;
		if (isset($this->options['_name'])) {
			$this->_name = $this->options['_name'];
		}
	}

/**
 * Check if a Route has been compiled into a regular expression.
 *
 * @return boolean
 */
	public function compiled() {
		return !empty($this->_compiledRoute);
	}

/**
 * Compiles the route's regular expression.  Modifies defaults property so all necessary keys are set
 * and populates $this->names with the named routing elements.
 *
 * @return array Returns a string regular expression of the compiled route.
 */
	public function compile() {
		if ($this->compiled()) {
			return $this->_compiledRoute;
		}
		$this->_writeRoute();
		return $this->_compiledRoute;
	}

/**
 * Builds a route regular expression.  Uses the template, defaults and options
 * properties to compile a regular expression that can be used to parse request strings.
 *
 * @return void
 */
	protected function _writeRoute() {
		if (empty($this->template) || ($this->template === '/')) {
			$this->_compiledRoute = '#^/*$#';
			$this->keys = array();
			return;
		}
		$route = $this->template;
		$names = $routeParams = array();
		$parsed = preg_quote($this->template, '#');

		preg_match_all('#:([A-Za-z0-9_-]+[A-Z0-9a-z])#', $route, $namedElements);
		foreach ($namedElements[1] as $i => $name) {
			$search = '\\' . $namedElements[0][$i];
			if (isset($this->options[$name])) {
				$option = null;
				if ($name !== 'plugin' && array_key_exists($name, $this->defaults)) {
					$option = '?';
				}
				$slashParam = '/\\' . $namedElements[0][$i];
				if (strpos($parsed, $slashParam) !== false) {
					$routeParams[$slashParam] = '(?:/(?P<' . $name . '>' . $this->options[$name] . ')' . $option . ')' . $option;
				} else {
					$routeParams[$search] = '(?:(?P<' . $name . '>' . $this->options[$name] . ')' . $option . ')' . $option;
				}
			} else {
				$routeParams[$search] = '(?:(?P<' . $name . '>[^/]+))';
			}
			$names[] = $name;
		}
		if (preg_match('#\/\*\*$#', $route)) {
			$parsed = preg_replace('#/\\\\\*\\\\\*$#', '(?:/(?P<_trailing_>.*))?', $parsed);
			$this->_greedy = true;
		} elseif (preg_match('#\/\*$#', $route)) {
			$parsed = preg_replace('#/\\\\\*$#', '(?:/(?P<_args_>.*))?', $parsed);
			$this->_greedy = true;
		}
		krsort($routeParams);
		$parsed = str_replace(array_keys($routeParams), array_values($routeParams), $parsed);
		$this->_compiledRoute = '#^' . $parsed . '[/]*$#';
		$this->keys = $names;

		// remove defaults that are also keys. They can cause match failures
		foreach ($this->keys as $key) {
			unset($this->defaults[$key]);
		}
	}

/**
 * Get the standardized plugin.controller:action name
 * for a route. This will compile a route if it has not
 * already been compiled.
 *
 * @return string.
 */
	public function getName() {
		if (!empty($this->_name)) {
			return $this->_name;
		}
		$name = '';
		if (isset($this->defaults['plugin'])) {
			$name = strtolower($this->defaults['plugin']) . '.';
		}
		foreach (array('controller', 'action') as $key) {
			if ($key === 'action') {
				$name .= ':';
			}
			if (isset($this->defaults[$key])) {
				$name .= strtolower($this->defaults[$key]);
			}
			$var = ':' . $key;
			if (strpos($this->template, $var) !== false) {
				$name .= '_' . $key;
			}
		}
		return $this->_name = $name;
	}

/**
 * Checks to see if the given URL can be parsed by this route.
 * If the route can be parsed an array of parameters will be returned; if not
 * false will be returned. String urls are parsed if they match a routes regular expression.
 *
 * @param string $url The url to attempt to parse.
 * @return mixed Boolean false on failure, otherwise an array or parameters
 */
	public function parse($url) {
		if (!$this->compiled()) {
			$this->compile();
		}
		if (!preg_match($this->_compiledRoute, $url, $route)) {
			return false;
		}
		foreach ($this->defaults as $key => $val) {
			$key = (string)$key;
			if ($key[0] === '[' && preg_match('/^\[(\w+)\]$/', $key, $header)) {
				if (isset($this->_headerMap[$header[1]])) {
					$header = $this->_headerMap[$header[1]];
				} else {
					$header = 'http_' . $header[1];
				}
				$header = strtoupper($header);

				$val = (array)$val;
				$h = false;

				foreach ($val as $v) {
					if (env($header) === $v) {
						$h = true;
					}
				}
				if (!$h) {
					return false;
				}
			}
		}
		array_shift($route);
		$count = count($this->keys);
		for ($i = 0; $i <= $count; $i++) {
			unset($route[$i]);
		}
		$route['pass'] = array();

		// Assign defaults, set passed args to pass
		foreach ($this->defaults as $key => $value) {
			if (isset($route[$key])) {
				continue;
			}
			if (is_integer($key)) {
				$route['pass'][] = $value;
				continue;
			}
			$route[$key] = $value;
		}

		foreach ($this->keys as $key) {
			if (isset($route[$key])) {
				$route[$key] = rawurldecode($route[$key]);
			}
		}

		if (isset($route['_args_'])) {
			$pass = $this->_parseArgs($route['_args_'], $route);
			$route['pass'] = array_merge($route['pass'], $pass);
			unset($route['_args_']);
		}

		if (isset($route['_trailing_'])) {
			$route['pass'][] = rawurldecode($route['_trailing_']);
			unset($route['_trailing_']);
		}

		// restructure 'pass' key route params
		if (isset($this->options['pass'])) {
			$j = count($this->options['pass']);
			while ($j--) {
				if (isset($route[$this->options['pass'][$j]])) {
					array_unshift($route['pass'], $route[$this->options['pass'][$j]]);
				}
			}
		}
		return $route;
	}

/**
 * Parse passed parameters into a list of passed args.
 *
 * @param string $args A string with the passed params.  eg. /1/foo
 * @param string $context The current route context, which should contain controller/action keys.
 * @return array Array of passed args.
 */
	protected function _parseArgs($args, $context) {
		$pass = array();
		$args = explode('/', $args);

		foreach ($args as $param) {
			if (empty($param) && $param !== '0' && $param !== 0) {
				continue;
			}
			$pass[] = rawurldecode($param);
		}
		return $pass;
	}

/**
 * Apply persistent parameters to a url array. Persistent parameters are a special
 * key used during route creation to force route parameters to persist when omitted from
 * a url array.
 *
 * @param array $url The array to apply persistent parameters to.
 * @param array $params An array of persistent values to replace persistent ones.
 * @return array An array with persistent parameters applied.
 */
	public function persistParams($url, $params) {
		foreach ($this->options['persist'] as $persistKey) {
			if (array_key_exists($persistKey, $params) && !isset($url[$persistKey])) {
				$url[$persistKey] = $params[$persistKey];
			}
		}
		return $url;
	}

/**
 * Attempt to match a url array.  If the url matches the route parameters and settings, then
 * return a generated string url.  If the url doesn't match the route parameters, false will be returned.
 * This method handles the reverse routing or conversion of url arrays into string urls.
 *
 * @param array $url An array of parameters to check matching with.
 * @return mixed Either a string url for the parameters if they match or false.
 */
	public function match($url) {
		if (!$this->compiled()) {
			$this->compile();
		}
		$defaults = $this->defaults;

		if (isset($defaults['prefix'])) {
			$url['prefix'] = $defaults['prefix'];
		}

		//check that all the key names are in the url
		$keyNames = array_flip($this->keys);
		if (array_intersect_key($keyNames, $url) !== $keyNames) {
			return false;
		}

		// Missing defaults is a fail.
		if (array_diff_key($defaults, $url) !== array()) {
			return false;
		}
		$prefixes = Router::prefixes();
		$pass = array();

		foreach ($url as $key => $value) {
			// keys that exist in the defaults and have different values is a match failure.
			$defaultExists = array_key_exists($key, $defaults);
			if ($defaultExists && $defaults[$key] != $value) {
				return false;
			} elseif ($defaultExists) {
				continue;
			}

			// If the key is a routed key, its not different yet.
			if (array_key_exists($key, $keyNames)) {
				continue;
			}

			// pull out passed args
			$numeric = is_numeric($key);
			if ($numeric && isset($defaults[$key]) && $defaults[$key] == $value) {
				continue;
			} elseif ($numeric) {
				$pass[] = $value;
				unset($url[$key]);
				continue;
			}

			// keys that don't exist are different.
			if (!$defaultExists && !empty($value)) {
				return false;
			}
		}

		//if a not a greedy route, no extra params are allowed.
		if (!$this->_greedy && !empty($pass)) {
			return false;
		}

		//check patterns for routed params
		if (!empty($this->options)) {
			foreach ($this->options as $key => $pattern) {
				if (array_key_exists($key, $url) && !preg_match('#^' . $pattern . '$#', $url[$key])) {
					return false;
				}
			}
		}
		return $this->_writeUrl($url, $pass);
	}

/**
 * Converts a matching route array into a url string. Composes the string url using the template
 * used to create the route.
 *
 * @param array $params The params to convert to a string url.
 * @param array $pass The additional passed arguments.
 * @return string Composed route string.
 */
	protected function _writeUrl($params, $pass = array()) {
		if (isset($params['prefix'], $params['action'])) {
			$params['action'] = str_replace($params['prefix'] . '_', '', $params['action']);
			unset($params['prefix']);
		}

		$pass = implode('/', array_map('rawurlencode', $pass));
		$out = $this->template;

		$search = $replace = array();
		foreach ($this->keys as $key) {
			$string = null;
			if (isset($params[$key])) {
				$string = $params[$key];
			} elseif (strpos($out, $key) != strlen($out) - strlen($key)) {
				$key .= '/';
			}
			$search[] = ':' . $key;
			$replace[] = $string;
		}
		$out = str_replace($search, $replace, $out);

		if (strpos($this->template, '*')) {
			$out = str_replace('*', $pass, $out);
		}
		$out = str_replace('//', '/', $out);
		return $out;
	}

}
