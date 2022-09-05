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
 * @since         1.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing\Route;

use Cake\Http\Exception\BadRequestException;
use InvalidArgumentException;
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
     * `/{controller}/{action}/{id}` has 3 key elements
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
    public $template;

    /**
     * Is this route a greedy route? Greedy routes have a `/*` in their
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
    protected $_compiledRoute;

    /**
     * The name for a route. Fetch with Route::getName();
     *
     * @var string|null
     */
    protected $_name;

    /**
     * List of connected extensions for this route.
     *
     * @var array<string>
     */
    protected $_extensions = [];

    /**
     * List of middleware that should be applied.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Track whether brace keys `{var}` were used.
     *
     * @var bool
     */
    protected $braceKeys = true;

    /**
     * Valid HTTP methods.
     *
     * @var array<string>
     */
    public const VALID_METHODS = ['GET', 'PUT', 'POST', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];

    /**
     * Regex for matching braced placholders in route template.
     *
     * @var string
     */
    protected const PLACEHOLDER_REGEX = '#\{([a-z][a-z0-9-_]*)\}#i';

    /**
     * Constructor for a Route
     *
     * ### Options
     *
     * - `_ext` - Defines the extensions used for this route.
     * - `_middleware` - Define the middleware names for this route.
     * - `pass` - Copies the listed parameters into params['pass'].
     * - `_method` - Defines the HTTP method(s) the route applies to. It can be
     *   a string or array of valid HTTP method name.
     * - `_host` - Define the host name pattern if you want this route to only match
     *   specific host names. You can use `.*` and to create wildcard subdomains/hosts
     *   e.g. `*.example.com` matches all subdomains on `example.com`.
     * - '_port` - Define the port if you want this route to only match specific port number.
     * - '_urldecode' - Set to `false` to disable URL decoding before route parsing.
     *
     * @param string $template Template string with parameter placeholders
     * @param array $defaults Defaults for the route.
     * @param array<string, mixed> $options Array of additional options for the Route
     * @throws \InvalidArgumentException When `$options['_method']` are not in `VALID_METHODS` list.
     */
    public function __construct(string $template, array $defaults = [], array $options = [])
    {
        $this->template = $template;
        $this->defaults = $defaults;
        $this->options = $options + ['_ext' => [], '_middleware' => []];
        $this->setExtensions((array)$this->options['_ext']);
        $this->setMiddleware((array)$this->options['_middleware']);
        unset($this->options['_middleware']);

        if (isset($this->defaults['_method'])) {
            $this->defaults['_method'] = $this->normalizeAndValidateMethods($this->defaults['_method']);
        }
    }

    /**
     * Set the supported extensions for this route.
     *
     * @param array<string> $extensions The extensions to set.
     * @return $this
     */
    public function setExtensions(array $extensions)
    {
        $this->_extensions = array_map('strtolower', $extensions);

        return $this;
    }

    /**
     * Get the supported extensions for this route.
     *
     * @return array<string>
     */
    public function getExtensions(): array
    {
        return $this->_extensions;
    }

    /**
     * Set the accepted HTTP methods for this route.
     *
     * @param array<string> $methods The HTTP methods to accept.
     * @return $this
     * @throws \InvalidArgumentException When methods are not in `VALID_METHODS` list.
     */
    public function setMethods(array $methods)
    {
        $this->defaults['_method'] = $this->normalizeAndValidateMethods($methods);

        return $this;
    }

    /**
     * Normalize method names to upper case and validate that they are valid HTTP methods.
     *
     * @param array<string>|string $methods Methods.
     * @return array<string>|string
     * @throws \InvalidArgumentException When methods are not in `VALID_METHODS` list.
     */
    protected function normalizeAndValidateMethods($methods)
    {
        $methods = is_array($methods)
            ? array_map('strtoupper', $methods)
            : strtoupper($methods);

        $diff = array_diff((array)$methods, static::VALID_METHODS);
        if ($diff !== []) {
            throw new InvalidArgumentException(
                sprintf('Invalid HTTP method received. `%s` is invalid.', implode(', ', $diff))
            );
        }

        return $methods;
    }

    /**
     * Set regexp patterns for routing parameters
     *
     * If any of your patterns contain multibyte values, the `multibytePattern`
     * mode will be enabled.
     *
     * @param array<string> $patterns The patterns to apply to routing elements
     * @return $this
     */
    public function setPatterns(array $patterns)
    {
        $patternValues = implode('', $patterns);
        if (mb_strlen($patternValues) < strlen($patternValues)) {
            $this->options['multibytePattern'] = true;
        }
        $this->options = $patterns + $this->options;

        return $this;
    }

    /**
     * Set host requirement
     *
     * @param string $host The host name this route is bound to
     * @return $this
     */
    public function setHost(string $host)
    {
        $this->options['_host'] = $host;

        return $this;
    }

    /**
     * Set the names of parameters that will be converted into passed parameters
     *
     * @param array<string> $names The names of the parameters that should be passed.
     * @return $this
     */
    public function setPass(array $names)
    {
        $this->options['pass'] = $names;

        return $this;
    }

    /**
     * Set the names of parameters that will persisted automatically
     *
     * Persistent parameters allow you to define which route parameters should be automatically
     * included when generating new URLs. You can override persistent parameters
     * by redefining them in a URL or remove them by setting the persistent parameter to `false`.
     *
     * ```
     * // remove a persistent 'date' parameter
     * Router::url(['date' => false', ...]);
     * ```
     *
     * @param array $names The names of the parameters that should be passed.
     * @return $this
     */
    public function setPersist(array $names)
    {
        $this->options['persist'] = $names;

        return $this;
    }

    /**
     * Check if a Route has been compiled into a regular expression.
     *
     * @return bool
     */
    public function compiled(): bool
    {
        return $this->_compiledRoute !== null;
    }

    /**
     * Compiles the route's regular expression.
     *
     * Modifies defaults property so all necessary keys are set
     * and populates $this->names with the named routing elements.
     *
     * @return string Returns a string regular expression of the compiled route.
     */
    public function compile(): string
    {
        if ($this->_compiledRoute === null) {
            $this->_writeRoute();
        }

        /** @var string */
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
    protected function _writeRoute(): void
    {
        if (empty($this->template) || ($this->template === '/')) {
            $this->_compiledRoute = '#^/*$#';
            $this->keys = [];

            return;
        }
        $route = $this->template;
        $names = $routeParams = [];
        $parsed = preg_quote($this->template, '#');

        if (strpos($route, '{') !== false && strpos($route, '}') !== false) {
            preg_match_all(static::PLACEHOLDER_REGEX, $route, $namedElements, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        } else {
            $hasMatches = preg_match_all(
                '/:([a-z0-9-_]+(?<![-_]))/i',
                $route,
                $namedElements,
                PREG_OFFSET_CAPTURE | PREG_SET_ORDER
            );
            $this->braceKeys = false;
            if ($hasMatches) {
                deprecationWarning(
                    'Colon prefixed route placeholders like `:foo` are deprecated.'
                    . ' Use braced placeholders like `{foo}` instead.'
                );
            }
        }
        foreach ($namedElements as $matchArray) {
            // Placeholder name, e.g. "foo"
            $name = $matchArray[1][0];
            // Placeholder with colon/braces, e.g. "{foo}"
            $search = preg_quote($matchArray[0][0]);
            if (isset($this->options[$name])) {
                $option = '';
                if ($name !== 'plugin' && array_key_exists($name, $this->defaults)) {
                    $option = '?';
                }
                // phpcs:disable Generic.Files.LineLength
                // Offset of the colon/braced placeholder in the full template string
                if ($parsed[$matchArray[0][1] - 1] === '/') {
                    $routeParams['/' . $search] = '(?:/(?P<' . $name . '>' . $this->options[$name] . ')' . $option . ')' . $option;
                } else {
                    $routeParams[$search] = '(?:(?P<' . $name . '>' . $this->options[$name] . ')' . $option . ')' . $option;
                }
                // phpcs:disable Generic.Files.LineLength
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
        $mode = empty($this->options['multibytePattern']) ? '' : 'u';
        krsort($routeParams);
        $parsed = str_replace(array_keys($routeParams), $routeParams, $parsed);
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
    public function getName(): string
    {
        if (!empty($this->_name)) {
            return $this->_name;
        }
        $name = '';
        $keys = [
            'prefix' => ':',
            'plugin' => '.',
            'controller' => ':',
            'action' => '',
        ];
        foreach ($keys as $key => $glue) {
            $value = null;
            if (
                strpos($this->template, '{' . $key . '}') !== false
                || strpos($this->template, ':' . $key) !== false
            ) {
                $value = '_' . $key;
            } elseif (isset($this->defaults[$key])) {
                $value = $this->defaults[$key];
            }

            if ($value === null) {
                continue;
            }
            if ($value === true || $value === false) {
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
     * `null` will be returned.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The URL to attempt to parse.
     * @return array|null An array of request parameters, or `null` on failure.
     */
    public function parseRequest(ServerRequestInterface $request): ?array
    {
        $uri = $request->getUri();
        if (isset($this->options['_host']) && !$this->hostMatches($uri->getHost())) {
            return null;
        }

        return $this->parse($uri->getPath(), $request->getMethod());
    }

    /**
     * Checks to see if the given URL can be parsed by this route.
     *
     * If the route can be parsed an array of parameters will be returned; if not
     * `null` will be returned. String URLs are parsed if they match a routes regular expression.
     *
     * @param string $url The URL to attempt to parse.
     * @param string $method The HTTP method of the request being parsed.
     * @return array|null An array of request parameters, or `null` on failure.
     * @throws \Cake\Http\Exception\BadRequestException When method is not an empty string and not in `VALID_METHODS` list.
     */
    public function parse(string $url, string $method): ?array
    {
        try {
            if ($method !== '') {
                $method = $this->normalizeAndValidateMethods($method);
            }
        } catch (InvalidArgumentException $e) {
            throw new BadRequestException($e->getMessage());
        }

        $compiledRoute = $this->compile();
        [$url, $ext] = $this->_parseExtension($url);

        $urldecode = $this->options['_urldecode'] ?? true;
        if ($urldecode) {
            $url = urldecode($url);
        }

        if (!preg_match($compiledRoute, $url, $route)) {
            return null;
        }

        if (
            isset($this->defaults['_method']) &&
            !in_array($method, (array)$this->defaults['_method'], true)
        ) {
            return null;
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
            /** @psalm-suppress PossiblyInvalidArgument */
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

        // pass the name if set
        if (isset($this->options['_name'])) {
            $route['_name'] = $this->options['_name'];
        }

        // restructure 'pass' key route params
        if (isset($this->options['pass'])) {
            $j = count($this->options['pass']);
            while ($j--) {
                /** @psalm-suppress PossiblyInvalidArgument */
                if (isset($route[$this->options['pass'][$j]])) {
                    array_unshift($route['pass'], $route[$this->options['pass'][$j]]);
                }
            }
        }

        $route['_route'] = $this;
        $route['_matchedRoute'] = $this->template;
        if (count($this->middleware) > 0) {
            $route['_middleware'] = $this->middleware;
        }

        return $route;
    }

    /**
     * Check to see if the host matches the route requirements
     *
     * @param string $host The request's host name
     * @return bool Whether the host matches any conditions set in for this route.
     */
    public function hostMatches(string $host): bool
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
    protected function _parseExtension(string $url): array
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
     * @param string $args A string with the passed params. eg. /1/foo
     * @param array $context The current route context, which should contain controller/action keys.
     * @return array<string> Array of passed args.
     */
    protected function _parseArgs(string $args, array $context): array
    {
        $pass = [];
        $args = explode('/', $args);
        $urldecode = $this->options['_urldecode'] ?? true;

        foreach ($args as $param) {
            if (empty($param) && $param !== '0') {
                continue;
            }
            $pass[] = $urldecode ? rawurldecode($param) : $param;
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
    protected function _persistParams(array $url, array $params): array
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
     * @return string|null Either a string URL for the parameters if they match or null.
     */
    public function match(array $url, array $context = []): ?string
    {
        if (empty($this->_compiledRoute)) {
            $this->compile();
        }
        $defaults = $this->defaults;
        $context += ['params' => [], '_port' => null, '_scheme' => null, '_host' => null];

        if (
            !empty($this->options['persist']) &&
            is_array($this->options['persist'])
        ) {
            $url = $this->_persistParams($url, $context['params']);
        }
        unset($context['params']);
        $hostOptions = array_intersect_key($url, $context);

        // Apply the _host option if possible
        if (isset($this->options['_host'])) {
            if (!isset($hostOptions['_host']) && strpos($this->options['_host'], '*') === false) {
                $hostOptions['_host'] = $this->options['_host'];
            }
            $hostOptions['_host'] = $hostOptions['_host'] ?? $context['_host'];

            // The host did not match the route preferences
            if (!$this->hostMatches((string)$hostOptions['_host'])) {
                return null;
            }
        }

        // Check for properties that will cause an
        // absolute url. Copy the other properties over.
        if (
            isset($hostOptions['_scheme']) ||
            isset($hostOptions['_port']) ||
            isset($hostOptions['_host'])
        ) {
            $hostOptions += $context;

            if (
                $hostOptions['_scheme'] &&
                getservbyname($hostOptions['_scheme'], 'tcp') === $hostOptions['_port']
            ) {
                unset($hostOptions['_port']);
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
            return null;
        }
        unset($url['_method'], $url['[method]'], $defaults['_method']);

        // Defaults with different values are a fail.
        if (array_intersect_key($url, $defaults) != $defaults) {
            return null;
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
            return null;
        }

        $pass = [];
        foreach ($url as $key => $value) {
            // If the key is a routed key, it's not different yet.
            if (array_key_exists($key, $keyNames)) {
                continue;
            }

            // pull out passed args
            $numeric = is_numeric($key);
            if ($numeric && isset($defaults[$key]) && $defaults[$key] === $value) {
                continue;
            }
            if ($numeric) {
                $pass[] = $value;
                unset($url[$key]);
                continue;
            }
        }

        // if not a greedy route, no extra params are allowed.
        if (!$this->_greedy && !empty($pass)) {
            return null;
        }

        // check patterns for routed params
        if (!empty($this->options)) {
            foreach ($this->options as $key => $pattern) {
                if (isset($url[$key]) && !preg_match('#^' . $pattern . '$#u', (string)$url[$key])) {
                    return null;
                }
            }
        }
        $url += $hostOptions;

        // Ensure controller/action keys are not null.
        if (
            (isset($keyNames['controller']) && !isset($url['controller'])) ||
            (isset($keyNames['action']) && !isset($url['action']))
        ) {
            return null;
        }

        return $this->_writeUrl($url, $pass, $query);
    }

    /**
     * Check whether the URL's HTTP method matches.
     *
     * @param array $url The array for the URL being generated.
     * @return bool
     */
    protected function _matchMethod(array $url): bool
    {
        if (empty($this->defaults['_method'])) {
            return true;
        }
        if (empty($url['_method'])) {
            $url['_method'] = 'GET';
        }
        $defaults = (array)$this->defaults['_method'];
        $methods = (array)$this->normalizeAndValidateMethods($url['_method']);
        foreach ($methods as $value) {
            if (in_array($value, $defaults, true)) {
                return true;
            }
        }

        return false;
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
    protected function _writeUrl(array $params, array $pass = [], array $query = []): string
    {
        $pass = array_map(function ($value) {
            return rawurlencode((string)$value);
        }, $pass);
        $pass = implode('/', $pass);
        $out = $this->template;

        $search = $replace = [];
        foreach ($this->keys as $key) {
            if (!array_key_exists($key, $params)) {
                throw new InvalidArgumentException("Missing required route key `{$key}`");
            }
            $string = $params[$key];
            if ($this->braceKeys) {
                $search[] = "{{$key}}";
            } else {
                $search[] = ':' . $key;
            }
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
        if (
            isset($params['_scheme']) ||
            isset($params['_host']) ||
            isset($params['_port'])
        ) {
            $host = $params['_host'];

            // append the port & scheme if they exists.
            if (isset($params['_port'])) {
                $host .= ':' . $params['_port'];
            }
            $scheme = $params['_scheme'] ?? 'http';
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
    public function staticPath(): string
    {
        $matched = preg_match(
            static::PLACEHOLDER_REGEX,
            $this->template,
            $namedElements,
            PREG_OFFSET_CAPTURE
        );

        if ($matched) {
            return substr($this->template, 0, $namedElements[0][1]);
        }

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
     * Set the names of the middleware that should be applied to this route.
     *
     * @param array $middleware The list of middleware names to apply to this route.
     *   Middleware names will not be checked until the route is matched.
     * @return $this
     */
    public function setMiddleware(array $middleware)
    {
        $this->middleware = $middleware;

        return $this;
    }

    /**
     * Get the names of the middleware that should be applied to this route.
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Set state magic method to support var_export
     *
     * This method helps for applications that want to implement
     * router caching.
     *
     * @param array<string, mixed> $fields Key/Value of object attributes
     * @return static A new instance of the route
     */
    public static function __set_state(array $fields)
    {
        $class = static::class;
        $obj = new $class('');
        foreach ($fields as $field => $value) {
            $obj->$field = $value;
        }

        return $obj;
    }
}
