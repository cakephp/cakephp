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
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing\Route;

use Cake\Network\Request;
use Cake\Routing\Router;

/**
 * A single Route used by the Router to connect requests to
 * parameter maps.
 *
 * Not normally created as a standalone. Use Router::connect() to create
 * Routes for your application.
 *
 */
class Route {

/**
 * An array of named segments in a Route.
 * `/:controller/:action/:id` has 3 key elements
 *
 * @var array
 */
	public $keys = [];

/**
 * An array of additional parameters for the Route.
 *
 * @var array
 */
	public $options = [];

/**
 * Default parameters for a Route
 *
 * @var array
 */
	public $defaults = [];

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
 * HTTP header shortcut map. Used for evaluating header-based route expressions.
 *
 * @var array
 */
	protected $_headerMap = [
		'type' => 'content_type',
		'method' => 'request_method',
		'server' => 'server_name'
	];

/**
 * List of connected extensions for this route.
 *
 * @var array
 */
	protected $_extensions = [];

/**
 * Constructor for a Route
 *
 * ### Options
 *
 * - `_name` - By using $options['_name'] a specific name can be
 *   given to a route. Otherwise a route name will be generated.
 * - `_ext` - Defines the extensions used for this route.
 * - `pass` - Copies the listed parameters into params['pass'].
 *
 * @param string $template Template string with parameter placeholders
 * @param array|string $defaults Defaults for the route.
 * @param array $options Array of additional options for the Route
 */
	public function __construct($template, $defaults = [], array $options = []) {
		$this->template = $template;
		$this->defaults = (array)$defaults;
		$this->options = $options;
		if (isset($this->options['_name'])) {
			$this->_name = $this->options['_name'];
		}
		if (isset($this->options['_ext'])) {
			$this->_extensions = $this->options['_ext'];
		}
	}

/**
 * Sets the supported extensions for this route.
 *
 * @param array $extensions The extensions to set.
 * @return void
 */
	public function parseExtensions(array $extensions) {
		$this->_extensions = $extensions;
	}

/**
 * Check if a Route has been compiled into a regular expression.
 *
 * @return bool
 */
	public function compiled() {
		return !empty($this->_compiledRoute);
	}

/**
 * Compiles the route's regular expression.
 *
 * Modifies defaults property so all necessary keys are set
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
 * Builds a route regular expression.
 *
 * Uses the template, defaults and options properties to compile a
 * regular expression that can be used to parse request strings.
 *
 * @return void
 */
	protected function _writeRoute() {
		if (empty($this->template) || ($this->template === '/')) {
			$this->_compiledRoute = '#^/*$#';
			$this->keys = [];
			return;
		}
		$route = $this->template;
		$names = $routeParams = [];
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
		}
		if (preg_match('#\/\*$#', $route)) {
			$parsed = preg_replace('#/\\\\\*$#', '(?:/(?P<_args_>.*))?', $parsed);
			$this->_greedy = true;
		}
		krsort($routeParams);
		$parsed = str_replace(array_keys($routeParams), array_values($routeParams), $parsed);
		$this->_compiledRoute = '#^' . $parsed . '[/]*$#';
		$this->keys = $names;

		// Remove defaults that are also keys. They can cause match failures
		foreach ($this->keys as $key) {
			unset($this->defaults[$key]);
		}

		$keys = $this->keys;
		sort($keys);
		$this->keys = array_reverse($keys);
	}

/**
 * Get the standardized plugin.controller:action name for a route.
 *
 * @return string
 */
	public function getName() {
		if (!empty($this->_name)) {
			return $this->_name;
		}
		$name = '';
		if (isset($this->defaults['plugin'])) {
			$name = $this->defaults['plugin'] . '.';
		}
		if (strpos($this->template, ':plugin') !== false) {
			$name = '_plugin.';
		}
		foreach (array('controller', 'action') as $key) {
			if ($key === 'action') {
				$name .= ':';
			}
			$var = ':' . $key;
			if (strpos($this->template, $var) !== false) {
				$name .= '_' . $key;
			} elseif (isset($this->defaults[$key])) {
				$name .= $this->defaults[$key];
			}
		}
		return $this->_name = strtolower($name);
	}

/**
 * Checks to see if the given URL can be parsed by this route.
 *
 * If the route can be parsed an array of parameters will be returned; if not
 * false will be returned. String URLs are parsed if they match a routes regular expression.
 *
 * @param string $url The URL to attempt to parse.
 * @return mixed Boolean false on failure, otherwise an array or parameters
 */
	public function parse($url) {
		$request = Router::getRequest(true) ?: Request::createFromGlobals();

		if (!$this->compiled()) {
			$this->compile();
		}
		list($url, $ext) = $this->_parseExtension($url);

		if (!preg_match($this->_compiledRoute, urldecode($url), $route)) {
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
					if ($request->env($header) === $v) {
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
		$route['pass'] = [];

		// Assign defaults, set passed args to pass
		foreach ($this->defaults as $key => $value) {
			if (isset($route[$key])) {
				continue;
			}
			if (is_int($key)) {
				$route['pass'][] = $value;
				continue;
			}
			$route[$key] = $value;
		}

		if (isset($route['_args_'])) {
			$pass = $this->_parseArgs($route['_args_'], $route);
			$route['pass'] = array_merge($route['pass'], $pass);
			unset($route['_args_']);
		}

		if (isset($route['_trailing_'])) {
			$route['pass'][] = $route['_trailing_'];
			unset($route['_trailing_']);
		}

		if (!empty($ext)) {
			$route['_ext'] = $ext;
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
 * Removes the extension from $url if it contains a registered extension.
 * If no registered extension is found, no extension is returned and the URL is returned unmodified.
 *
 * @param string $url The url to parse.
 * @return array containing url, extension
 */
	protected function _parseExtension($url) {
		if (empty($this->_extensions)) {
			return array($url, null);
		}
		preg_match('/\.([0-9a-z]*)$/', $url, $match);
		if (empty($match[1])) {
			return array($url, null);
		}
		$ext = strtolower($match[1]);
		$len = strlen($match[1]);
		foreach ($this->_extensions as $name) {
			if (strtolower($name) === $ext) {
				$url = substr($url, 0, ($len + 1) * -1);
				return array($url, $ext);
			}
		}
		return array($url, null);
	}

/**
 * Parse passed parameters into a list of passed args.
 *
 * Return true if a given named $param's $val matches a given $rule depending on $context.
 * Currently implemented rule types are controller, action and match that can be combined with each other.
 *
 * @param string $args A string with the passed params.  eg. /1/foo
 * @param string $context The current route context, which should contain controller/action keys.
 * @return array Array of passed args.
 */
	protected function _parseArgs($args, $context) {
		$pass = [];
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
 * Check if a URL array matches this route instance.
 *
 * If the URL matches the route parameters and settings, then
 * return a generated string URL. If the URL doesn't match the route parameters, false will be returned.
 * This method handles the reverse routing or conversion of URL arrays into string URLs.
 *
 * @param array $url An array of parameters to check matching with.
 * @param array $context An array of the current request context.
 *   Contains information such as the current host, scheme, port, and base
 *   directory.
 * @return mixed Either a string url for the parameters if they match or false.
 */
	public function match(array $url, array $context = []) {
		if (!$this->compiled()) {
			$this->compile();
		}
		$defaults = $this->defaults;

		$hostOptions = array_intersect_key($url, $context);

		// Check for properties that will cause and
		// absoulte url. Copy the other properties over.
		if (
			isset($hostOptions['_scheme']) ||
			isset($hostOptions['_port']) ||
			isset($hostOptions['_host'])
		) {
			$hostOptions += $context;

			if ($hostOptions['_port'] == $context['_port']) {
				unset($hostOptions['_port']);
			}
		}

		// If no base is set, copy one in.
		if (!isset($hostOptions['_base']) && isset($context['_base'])) {
			$hostOptions['_base'] = $context['_base'];
		}
		unset($url['_host'], $url['_scheme'], $url['_port'], $url['_base']);

		// Move extension into the hostOptions so its not part of
		// reverse matches.
		if (isset($url['_ext'])) {
			$hostOptions['_ext'] = $url['_ext'];
			unset($url['_ext']);
		}

		// Missing defaults is a fail.
		if (array_diff_key($defaults, $url) !== []) {
			return false;
		}

		// Defaults with different values are a fail.
		if (array_intersect_key($url, $defaults) != $defaults) {
			return false;
		}

		// check that all the key names are in the url
		$keyNames = array_flip($this->keys);
		if (array_intersect_key($keyNames, $url) !== $keyNames) {
			return false;
		}

		$pass = [];
		$query = [];

		foreach ($url as $key => $value) {
			// keys that exist in the defaults and have different values is a match failure.
			$defaultExists = array_key_exists($key, $defaults);

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
			if (!$defaultExists && ($value !== null && $value !== false && $value !== '')) {
				$query[$key] = $value;
				unset($url[$key]);
			}
		}

		// if not a greedy route, no extra params are allowed.
		if (!$this->_greedy && !empty($pass)) {
			return false;
		}

		//check patterns for routed params
		if (!empty($this->options)) {
			foreach ($this->options as $key => $pattern) {
				if (isset($url[$key]) && !preg_match('#^' . $pattern . '$#', $url[$key])) {
					return false;
				}
			}
		}
		$url += $hostOptions;
		return $this->_writeUrl($url, $pass, $query);
	}

/**
 * Converts a matching route array into a URL string.
 *
 * Composes the string URL using the template
 * used to create the route.
 *
 * @param array $params The params to convert to a string url
 * @param array $pass The additional passed arguments
 * @param array $query An array of parameters
 * @return string Composed route string.
 */
	protected function _writeUrl($params, $pass = [], $query = []) {
		$pass = implode('/', array_map('rawurlencode', $pass));
		$out = $this->template;

		if (!empty($this->keys)) {
			$search = $replace = [];

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
		}

		if (strpos($this->template, '*')) {
			$out = str_replace('*', $pass, $out);
		}

		// add base url if applicable.
		if (isset($params['_base'])) {
			$out = $params['_base'] . $out;
			unset($params['_base']);
		}

		$out = str_replace('//', '/', $out);

		if (
			isset($params['_scheme']) ||
			isset($params['_host']) ||
			isset($params['_port'])
		) {
			$host = $params['_host'];

			// append the port if it exists.
			if (isset($params['_port'])) {
				$host .= ':' . $params['_port'];
			}
			$out = sprintf(
				'%s://%s%s',
				$params['_scheme'],
				$host,
				$out
			);
		}
		if (!empty($params['_ext']) || !empty($query)) {
			$out = rtrim($out, '/');
		}
		if (!empty($params['_ext'])) {
			$out .= '.' . $params['_ext'];
		}
		if (!empty($query)) {
			$out .= '?' . http_build_query($query);
		}
		return $out;
	}

}
