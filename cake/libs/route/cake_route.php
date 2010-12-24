<?php
/**
 * A single Route used by the Router to connect requests to
 * parameter maps.
 *
 * Not normally created as a standalone.  Use Router::connect() to create
 * Routes for your application.
 *
 * PHP5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.route
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class CakeRoute {

/**
 * An array of named segments in a Route.
 * `/:controller/:action/:id` has 3 key elements
 *
 * @var array
 * @access public
 */
	public $keys = array();

/**
 * An array of additional parameters for the Route.
 *
 * @var array
 * @access public
 */
	public $options = array();

/**
 * Default parameters for a Route
 *
 * @var array
 * @access public
 */
	public $defaults = array();

/**
 * The routes template string.
 *
 * @var string
 * @access public
 */
	public $template = null;

/**
 * Is this route a greedy route?  Greedy routes have a `/*` in their
 * template
 *
 * @var string
 * @access protected
 */
	protected $_greedy = false;

/**
 * The compiled route regular expresssion
 *
 * @var string
 * @access protected
 */
	protected $_compiledRoute = null;

/**
 * HTTP header shortcut map.  Used for evaluating header-based route expressions.
 *
 * @var array
 * @access private
 */
	private $__headerMap = array(
		'type' => 'content_type',
		'method' => 'request_method',
		'server' => 'server_name'
	);

/**
 * Constructor for a Route
 *
 * @param string $template Template string with parameter placeholders
 * @param array $defaults Array of defaults for the route.
 * @param string $params Array of parameters and additional options for the Route
 * @return void
 */
	public function __construct($template, $defaults = array(), $options = array()) {
		$this->template = $template;
		$this->defaults = (array)$defaults;
		$this->options = (array)$options;
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
		if (preg_match('#\/\*$#', $route, $m)) {
			$parsed = preg_replace('#/\\\\\*$#', '(?:/(?P<_args_>.*))?', $parsed);
			$this->_greedy = true;
		}
		krsort($routeParams);
		$parsed = str_replace(array_keys($routeParams), array_values($routeParams), $parsed);
		$this->_compiledRoute = '#^' . $parsed . '[/]*$#';
		$this->keys = $names;
	}

/**
 * Checks to see if the given URL can be parsed by this route.
 * If the route can be parsed an array of parameters will be returned if not
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
		} else {
			foreach ($this->defaults as $key => $val) {
				if ($key[0] === '[' && preg_match('/^\[(\w+)\]$/', $key, $header)) {
					if (isset($this->__headerMap[$header[1]])) {
						$header = $this->__headerMap[$header[1]];
					} else {
						$header = 'http_' . $header[1];
					}

					$val = (array)$val;
					$h = false;

					foreach ($val as $v) {
						if (env(strtoupper($header)) === $v) {
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
			$route['pass'] = $route['named'] = array();
			$route += $this->defaults;

			//move numerically indexed elements from the defaults into pass.
			foreach ($route as $key => $value) {
				if (is_integer($key)) {
					$route['pass'][] = $value;
					unset($route[$key]);
				}
			}
			return $route;
		}
	}

/**
 * Apply persistent parameters to a url array. Persistant parameters are a special 
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
 * Attempt to match a url array.  If the url matches the route parameters + settings, then
 * return a generated string url.  If the url doesn't match the route parameters false will be returned.
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
		if (array_intersect_key($keyNames, $url) != $keyNames) {
			return false;
		}

		$diffUnfiltered = Set::diff($url, $defaults);
		$diff = array();

		foreach ($diffUnfiltered as $key => $var) {
			if ($var === 0 || $var === '0' || !empty($var)) {
				$diff[$key] = $var;
			}
		}

		//if a not a greedy route, no extra params are allowed.
		if (!$this->_greedy && array_diff_key($diff, $keyNames) != array()) {
			return false;
		}

		//remove defaults that are also keys. They can cause match failures
		foreach ($this->keys as $key) {
			unset($defaults[$key]);
		}
		$filteredDefaults = array_filter($defaults);

		//if the difference between the url diff and defaults contains keys from defaults its not a match
		if (array_intersect_key($filteredDefaults, $diffUnfiltered) !== array()) {
			return false;
		}

		$passedArgsAndParams = array_diff_key($diff, $filteredDefaults, $keyNames);
		list($named, $params) = Router::getNamedElements($passedArgsAndParams, $url['controller'], $url['action']);

		//remove any pass params, they have numeric indexes, skip any params that are in the defaults
		$pass = array();
		$i = 0;
		while (isset($url[$i])) {
			if (!isset($diff[$i])) {
				$i++;
				continue;
			}
			$pass[] = $url[$i];
			unset($url[$i], $params[$i]);
			$i++;
		}

		//still some left over parameters that weren't named or passed args, bail.
		if (!empty($params)) {
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
		return $this->_writeUrl(array_merge($url, compact('pass', 'named')));
	}

/**
 * Converts a matching route array into a url string. Composes the string url using the template
 * used to create the route.
 *
 * @param array $params The params to convert to a string url.
 * @return string Composed route string.
 */
	protected function _writeUrl($params) {
		if (isset($params['prefix'], $params['action'])) {
			$params['action'] = str_replace($params['prefix'] . '_', '', $params['action']);
			unset($params['prefix']);
		}

		if (is_array($params['pass'])) {
			$params['pass'] = implode('/', $params['pass']);
		}

		$separator = Router::$named['separator'];

		if (!empty($params['named']) && is_array($params['named'])) {
			$named = array();
			foreach ($params['named'] as $key => $value) {
				if (is_array($value)) {
					foreach ($value as $namedKey => $namedValue) {
						$named[] = $key . "[$namedKey]" . $separator . $namedValue;
					}
				} else {
					$named[] = $key . $separator . $value;
				}
			}
			$params['pass'] = $params['pass'] . '/' . implode('/', $named);
		}
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
			$out = str_replace('*', $params['pass'], $out);
		}
		$out = str_replace('//', '/', $out);
		return $out;
	}
}