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

use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A single Route used by the Router to connect requests to
 * parameter maps.
 *
 * Not normally created as a standalone. Use Router::connect() to create
 * Routes for your application.
 */
class Route
{

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
     * @var string|null
     */
    public $template = null;

    /**
     * Is this route a greedy route?  Greedy routes have a `/*` in their
     * template
     *
     * @var bool
     */
    protected $_greedy = false;

    /**
     * The compiled route regular expression
     *
     * @var string|null
     */
    protected $_compiledRoute = null;

    /**
     * The name for a route.  Fetch with Route::getName();
     *
     * @var string|null
     */
    protected $_name = null;

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
     * - `_ext` - Defines the extensions used for this route.
     * - `pass` - Copies the listed parameters into params['pass'].
     * - `_host` - Define the host name pattern if you want this route to only match
     *   specific host names. You can use `.*` and to create wildcard subdomains/hosts
     *   e.g. `*.example.com` matches all subdomains on `example.com`.
     *
     * @param string $template Template string with parameter placeholders
     * @param array|string $defaults Defaults for the route.
     * @param array $options Array of additional options for the Route
     */
    public function __construct($template, $defaults = [], array $options = [])
    {
        $this->template = $template;
        $this->defaults = (array)$defaults;
        if (isset($this->defaults['[method]'])) {
            $this->defaults['_method'] = $this->defaults['[method]'];
            unset($this->defaults['[method]']);
        }
        $this->options = $options + ['_ext' => []];
        $this->setExtensions((array)$this->options['_ext']);
    }

    /**
     * Get/Set the supported extensions for this route.
     *
     * @deprecated 3.3.9 Use getExtensions/setExtensions instead.
     * @param null|string|array $extensions The extensions to set. Use null to get.
     * @return array|null The extensions or null.
     */
    public function extensions($extensions = null)
    {
        if ($extensions === null) {
            return $this->_extensions;
        }
        $this->_extensions = (array)$extensions;
    }

    /**
     * Set the supported extensions for this route.
     *
     * @param array $extensions The extensions to set.
     * @return self
     */
    public function setExtensions(array $extensions)
    {
        $this->_extensions = array_map('strtolower', $extensions);

        return $this;
    }

    /**
     * Get the supported extensions for this route.
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->_extensions;
    }

    /**
     * Check if a Route has been compiled into a regular expression.
     *
     * @return bool
     */
    public function compiled()
    {
        return !empty($this->_compiledRoute);
    }

    /**
     * Compiles the route's regular expression.
     *
     * Modifies defaults property so all necessary keys are set
     * and populates $this->names with the named routing elements.
     *
     * @return string Returns a string regular expression of the compiled route.
     */
    public function compile()
    {
        if ($this->_compiledRoute) {
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
    protected function _writeRoute()
    {
        if (empty($this->template) || ($this->template === '/')) {
            $this->_compiledRoute = '#^/*$#';
            $this->keys = [];

            return;
        }
        $route = $this->template;
        $names = $routeParams = [];
        $parsed = preg_quote($this->template, '#');

        preg_match_all('/:([a-z0-9-_]+(?<![-_]))/i', $route, $namedElements);
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
        $mode = '';
        if (!empty($this->options['multibytePattern'])) {
            $mode = 'u';
        }
        krsort($routeParams);
        $parsed = str_replace(array_keys($routeParams), array_values($routeParams), $parsed);
        $this->_compiledRoute = '#^' . $parsed . '[/]*$#' . $mode;
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
    public function getName()
    {
        if (!empty($this->_name)) {
            return $this->_name;
        }
        $name = '';
        $keys = [
            'prefix' => ':',
            'plugin' => '.',
            'controller' => ':',
            'action' => ''
        ];
        foreach ($keys as $key => $glue) {
            $value = null;
            if (strpos($this->template, ':' . $key) !== false) {
                $value = '_' . $key;
            } elseif (isset($this->defaults[$key])) {
                $value = $this->defaults[$key];
            }

            if ($value === null) {
                continue;
            }
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            $name .= $value . $glue;
        }

        return $this->_name = strtolower($name);
    }

    /**
     * Checks to see if the given URL can be parsed by this route.
     *
     * If the route can be parsed an array of parameters will be returned; if not
     * false will be returned.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The URL to attempt to parse.
     * @return array|false An array of request parameters, or false on failure.
     */
    public function parseRequest(ServerRequestInterface $request)
    {
        $uri = $request->getUri();
        if (isset($this->options['_host']) && !$this->hostMatches($uri->getHost())) {
            return false;
        }

        return $this->parse($uri->getPath(), $request->getMethod());
    }

    /**
     * Checks to see if the given URL can be parsed by this route.
     *
     * If the route can be parsed an array of parameters will be returned; if not
     * false will be returned. String URLs are parsed if they match a routes regular expression.
     *
     * @param string $url The URL to attempt to parse.
     * @param string $method The HTTP method of the request being parsed.
     * @return array|false An array of request parameters, or false on failure.
     * @deprecated 3.4.0 Use/implement parseRequest() instead as it provides more flexibility/control.
     */
    public function parse($url, $method = '')
    {
        if (empty($this->_compiledRoute)) {
            $this->compile();
        }
        list($url, $ext) = $this->_parseExtension($url);

        if (!preg_match($this->_compiledRoute, urldecode($url), $route)) {
            return false;
        }

        if (isset($this->defaults['_method'])) {
            if (empty($method)) {
                // Deprecated reading the global state is deprecated and will be removed in 4.x
                $request = Router::getRequest(true) ?: ServerRequest::createFromGlobals();
                $method = $request->env('REQUEST_METHOD');
            }
            if (!in_array($method, (array)$this->defaults['_method'], true)) {
                return false;
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
        $route['_matchedRoute'] = $this->template;

        return $route;
    }

    /**
     * Check to see if the host matches the route requirements
     *
     * @param string $host The request's host name
     * @return bool Whether or not the host matches any conditions set in for this route.
     */
    public function hostMatches($host)
    {
        $pattern = '@^' . str_replace('\*', '.*', preg_quote($this->options['_host'], '@')) . '$@';

        return preg_match($pattern, $host) !== 0;
    }

    /**
     * Removes the extension from $url if it contains a registered extension.
     * If no registered extension is found, no extension is returned and the URL is returned unmodified.
     *
     * @param string $url The url to parse.
     * @return array containing url, extension
     */
    protected function _parseExtension($url)
    {
        if (count($this->_extensions) && strpos($url, '.') !== false) {
            foreach ($this->_extensions as $ext) {
                $len = strlen($ext) + 1;
                if (substr($url, -$len) === '.' . $ext) {
                    return [substr($url, 0, $len * -1), $ext];
                }
            }
        }

        return [$url, null];
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
    protected function _parseArgs($args, $context)
    {
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
     * Apply persistent parameters to a URL array. Persistent parameters are a
     * special key used during route creation to force route parameters to
     * persist when omitted from a URL array.
     *
     * @param array $url The array to apply persistent parameters to.
     * @param array $params An array of persistent values to replace persistent ones.
     * @return array An array with persistent parameters applied.
     */
    protected function _persistParams(array $url, array $params)
    {
        foreach ($this->options['persist'] as $persistKey) {
            if (array_key_exists($persistKey, $params) && !isset($url[$persistKey])) {
                $url[$persistKey] = $params[$persistKey];
            }
        }

        return $url;
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
     *   Contains information such as the current host, scheme, port, base
     *   directory and other url params.
     * @return string|false Either a string URL for the parameters if they match or false.
     */
    public function match(array $url, array $context = [])
    {
        if (empty($this->_compiledRoute)) {
            $this->compile();
        }
        $defaults = $this->defaults;
        $context += ['params' => [], '_port' => null, '_scheme' => null, '_host' => null];

        if (!empty($this->options['persist']) &&
            is_array($this->options['persist'])
        ) {
            $url = $this->_persistParams($url, $context['params']);
        }
        unset($context['params']);
        $hostOptions = array_intersect_key($url, $context);

        // Check for properties that will cause an
        // absolute url. Copy the other properties over.
        if (isset($hostOptions['_scheme']) ||
            isset($hostOptions['_port']) ||
            isset($hostOptions['_host'])
        ) {
            $hostOptions += $context;

            if ($hostOptions['_port'] == $context['_port']) {
                unset($hostOptions['_port']);
            }
        }

        // Apply the _host option if possible
        if (isset($this->options['_host'])) {
            if (!isset($hostOptions['_host']) && strpos($this->options['_host'], '*') === false) {
                $hostOptions['_host'] = $this->options['_host'];
            }
            if (!isset($hostOptions['_host'])) {
                $hostOptions['_host'] = $context['_host'];
            }

            // The host did not match the route preferences
            if (!$this->hostMatches($hostOptions['_host'])) {
                return false;
            }
        }

        // If no base is set, copy one in.
        if (!isset($hostOptions['_base']) && isset($context['_base'])) {
            $hostOptions['_base'] = $context['_base'];
        }

        $query = !empty($url['?']) ? (array)$url['?'] : [];
        unset($url['_host'], $url['_scheme'], $url['_port'], $url['_base'], $url['?']);

        // Move extension into the hostOptions so its not part of
        // reverse matches.
        if (isset($url['_ext'])) {
            $hostOptions['_ext'] = $url['_ext'];
            unset($url['_ext']);
        }

        // Check the method first as it is special.
        if (!$this->_matchMethod($url)) {
            return false;
        }
        unset($url['_method'], $url['[method]'], $defaults['_method']);

        // Missing defaults is a fail.
        if (array_diff_key($defaults, $url) !== []) {
            return false;
        }

        // Defaults with different values are a fail.
        if (array_intersect_key($url, $defaults) != $defaults) {
            return false;
        }

        // If this route uses pass option, and the passed elements are
        // not set, rekey elements.
        if (isset($this->options['pass'])) {
            foreach ($this->options['pass'] as $i => $name) {
                if (isset($url[$i]) && !isset($url[$name])) {
                    $url[$name] = $url[$i];
                    unset($url[$i]);
                }
            }
        }

        // check that all the key names are in the url
        $keyNames = array_flip($this->keys);
        if (array_intersect_key($keyNames, $url) !== $keyNames) {
            return false;
        }

        $pass = [];
        foreach ($url as $key => $value) {
            // keys that exist in the defaults and have different values is a match failure.
            $defaultExists = array_key_exists($key, $defaults);

            // If the key is a routed key, it's not different yet.
            if (array_key_exists($key, $keyNames)) {
                continue;
            }

            // pull out passed args
            $numeric = is_numeric($key);
            if ($numeric && isset($defaults[$key]) && $defaults[$key] == $value) {
                continue;
            }
            if ($numeric) {
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

        // check patterns for routed params
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
     * Check whether or not the URL's HTTP method matches.
     *
     * @param array $url The array for the URL being generated.
     * @return bool
     */
    protected function _matchMethod($url)
    {
        if (empty($this->defaults['_method'])) {
            return true;
        }
        if (isset($url['[method]'])) {
            $url['_method'] = $url['[method]'];
        }
        if (empty($url['_method'])) {
            return false;
        }
        if (!in_array(strtoupper($url['_method']), (array)$this->defaults['_method'])) {
            return false;
        }

        return true;
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
    protected function _writeUrl($params, $pass = [], $query = [])
    {
        $pass = implode('/', array_map('rawurlencode', $pass));
        $out = $this->template;

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

        if (strpos($this->template, '**') !== false) {
            array_push($search, '**', '%2F');
            array_push($replace, $pass, '/');
        } elseif (strpos($this->template, '*') !== false) {
            $search[] = '*';
            $replace[] = $pass;
        }
        $out = str_replace($search, $replace, $out);

        // add base url if applicable.
        if (isset($params['_base'])) {
            $out = $params['_base'] . $out;
            unset($params['_base']);
        }

        $out = str_replace('//', '/', $out);
        if (isset($params['_scheme']) ||
            isset($params['_host']) ||
            isset($params['_port'])
        ) {
            $host = $params['_host'];

            // append the port & scheme if they exists.
            if (isset($params['_port'])) {
                $host .= ':' . $params['_port'];
            }
            $scheme = isset($params['_scheme']) ? $params['_scheme'] : 'http';
            $out = "{$scheme}://{$host}{$out}";
        }
        if (!empty($params['_ext']) || !empty($query)) {
            $out = rtrim($out, '/');
        }
        if (!empty($params['_ext'])) {
            $out .= '.' . $params['_ext'];
        }
        if (!empty($query)) {
            $out .= rtrim('?' . http_build_query($query), '?');
        }

        return $out;
    }

    /**
     * Get the static path portion for this route.
     *
     * @return string
     */
    public function staticPath()
    {
        $routeKey = strpos($this->template, ':');
        if ($routeKey !== false) {
            return substr($this->template, 0, $routeKey);
        }
        $star = strpos($this->template, '*');
        if ($star !== false) {
            $path = rtrim(substr($this->template, 0, $star), '/');

            return $path === '' ? '/' : $path;
        }

        return $this->template;
    }

    /**
     * Set state magic method to support var_export
     *
     * This method helps for applications that want to implement
     * router caching.
     *
     * @param array $fields Key/Value of object attributes
     * @return \Cake\Routing\Route\Route A new instance of the route
     */
    public static function __set_state($fields)
    {
        $class = get_called_class();
        $obj = new $class('');
        foreach ($fields as $field => $value) {
            $obj->$field = $value;
        }

        return $obj;
    }
}
