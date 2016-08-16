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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Network;

use ArrayAccess;
use BadMethodCallException;
use Cake\Core\Configure;
use Cake\Network\Exception\MethodNotAllowedException;
use Cake\Utility\Hash;

/**
 * A class that helps wrap Request information and particulars about a single request.
 * Provides methods commonly used to introspect on the request headers and request body.
 *
 * Has both an Array and Object interface. You can access framework parameters using indexes:
 *
 * `$request['controller']` or `$request->controller`.
 */
class Request implements ArrayAccess
{

    /**
     * Array of parameters parsed from the URL.
     *
     * @var array
     */
    public $params = [
        'plugin' => null,
        'controller' => null,
        'action' => null,
        '_ext' => null,
        'pass' => []
    ];

    /**
     * Array of POST data. Will contain form data as well as uploaded files.
     * In PUT/PATCH/DELETE requests this property will contain the form-urlencoded
     * data.
     *
     * @var array
     */
    public $data = [];

    /**
     * Array of querystring arguments
     *
     * @var array
     */
    public $query = [];

    /**
     * Array of cookie data.
     *
     * @var array
     */
    public $cookies = [];

    /**
     * Array of environment data.
     *
     * @var array
     */
    protected $_environment = [];

    /**
     * The URL string used for the request.
     *
     * @var string
     */
    public $url;

    /**
     * Base URL path.
     *
     * @var string
     */
    public $base;

    /**
     * webroot path segment for the request.
     *
     * @var string
     */
    public $webroot = '/';

    /**
     * The full address to the current request
     *
     * @var string
     */
    public $here;

    /**
     * Whether or not to trust HTTP_X headers set by most load balancers.
     * Only set to true if your application runs behind load balancers/proxies
     * that you control.
     *
     * @var bool
     */
    public $trustProxy = false;

    /**
     * The built in detectors used with `is()` can be modified with `addDetector()`.
     *
     * There are several ways to specify a detector, see Cake\Network\Request::addDetector() for the
     * various formats and ways to define detectors.
     *
     * @var array
     */
    protected static $_detectors = [
        'get' => ['env' => 'REQUEST_METHOD', 'value' => 'GET'],
        'post' => ['env' => 'REQUEST_METHOD', 'value' => 'POST'],
        'put' => ['env' => 'REQUEST_METHOD', 'value' => 'PUT'],
        'patch' => ['env' => 'REQUEST_METHOD', 'value' => 'PATCH'],
        'delete' => ['env' => 'REQUEST_METHOD', 'value' => 'DELETE'],
        'head' => ['env' => 'REQUEST_METHOD', 'value' => 'HEAD'],
        'options' => ['env' => 'REQUEST_METHOD', 'value' => 'OPTIONS'],
        'ssl' => ['env' => 'HTTPS', 'options' => [1, 'on']],
        'ajax' => ['env' => 'HTTP_X_REQUESTED_WITH', 'value' => 'XMLHttpRequest'],
        'flash' => ['env' => 'HTTP_USER_AGENT', 'pattern' => '/^(Shockwave|Adobe) Flash/'],
        'requested' => ['param' => 'requested', 'value' => 1],
        'json' => ['accept' => ['application/json'], 'param' => '_ext', 'value' => 'json'],
        'xml' => ['accept' => ['application/xml', 'text/xml'], 'param' => '_ext', 'value' => 'xml'],
    ];
    
    /**
     * Instance cache for results of is(something) calls
     *
     * @var array
     */
    protected $_detectorCache = [];

    /**
     * Copy of php://input. Since this stream can only be read once in most SAPI's
     * keep a copy of it so users don't need to know about that detail.
     *
     * @var string
     */
    protected $_input = '';

    /**
     * Instance of a Session object relative to this request
     *
     * @var \Cake\Network\Session
     */
    protected $_session;

    /**
     * Wrapper method to create a new request from PHP superglobals.
     *
     * Uses the $_GET, $_POST, $_FILES, $_COOKIE, $_SERVER, $_ENV and php://input data to construct
     * the request.
     *
     * @return \Cake\Network\Request
     */
    public static function createFromGlobals()
    {
        list($base, $webroot) = static::_base();
        $sessionConfig = (array)Configure::read('Session') + [
            'defaults' => 'php',
            'cookiePath' => $webroot
        ];

        $config = [
            'query' => $_GET,
            'post' => $_POST,
            'files' => $_FILES,
            'cookies' => $_COOKIE,
            'environment' => $_SERVER + $_ENV,
            'base' => $base,
            'webroot' => $webroot,
            'session' => Session::create($sessionConfig)
        ];
        $config['url'] = static::_url($config);

        return new static($config);
    }

    /**
     * Create a new request object.
     *
     * You can supply the data as either an array or as a string.  If you use
     * a string you can only supply the URL for the request.  Using an array will
     * let you provide the following keys:
     *
     * - `post` POST data or non query string data
     * - `query` Additional data from the query string.
     * - `files` Uploaded file data formatted like $_FILES.
     * - `cookies` Cookies for this request.
     * - `environment` $_SERVER and $_ENV data.
     * - `url` The URL without the base path for the request.
     * - `base` The base URL for the request.
     * - `webroot` The webroot directory for the request.
     * - `input` The data that would come from php://input this is useful for simulating
     * - `session` An instance of a Session object
     *   requests with put, patch or delete data.
     *
     * @param string|array $config An array of request data to create a request with.
     */
    public function __construct($config = [])
    {
        if (is_string($config)) {
            $config = ['url' => $config];
        }
        $config += [
            'params' => $this->params,
            'query' => [],
            'post' => [],
            'files' => [],
            'cookies' => [],
            'environment' => [],
            'url' => '',
            'base' => '',
            'webroot' => '',
            'input' => null,
        ];

        $this->_setConfig($config);
    }

    /**
     * Process the config/settings data into properties.
     *
     * @param array $config The config data to use.
     * @return void
     */
    protected function _setConfig($config)
    {
        if (!empty($config['url']) && $config['url'][0] === '/') {
            $config['url'] = substr($config['url'], 1);
        }

        if (empty($config['session'])) {
            $config['session'] = new Session([
                'cookiePath' => $config['base']
            ]);
        }

        $this->url = $config['url'];
        $this->base = $config['base'];
        $this->cookies = $config['cookies'];
        $this->here = $this->base . '/' . $this->url;
        $this->webroot = $config['webroot'];

        $this->_environment = $config['environment'];
        if (isset($config['input'])) {
            $this->_input = $config['input'];
        }
        $config['post'] = $this->_processPost($config['post']);
        $this->data = $this->_processFiles($config['post'], $config['files']);
        $this->query = $this->_processGet($config['query']);
        $this->params = $config['params'];
        $this->_session = $config['session'];
    }

    /**
     * Sets the REQUEST_METHOD environment variable based on the simulated _method
     * HTTP override value. The 'ORIGINAL_REQUEST_METHOD' is also preserved, if you
     * want the read the non-simulated HTTP method the client used.
     *
     * @param array $data Array of post data.
     * @return array
     */
    protected function _processPost($data)
    {
        $method = $this->env('REQUEST_METHOD');
        $override = false;

        if (in_array($method, ['PUT', 'DELETE', 'PATCH']) &&
            strpos($this->contentType(), 'application/x-www-form-urlencoded') === 0
        ) {
            $data = $this->input();
            parse_str($data, $data);
        }
        if ($this->env('HTTP_X_HTTP_METHOD_OVERRIDE')) {
            $data['_method'] = $this->env('HTTP_X_HTTP_METHOD_OVERRIDE');
            $override = true;
        }
        $this->_environment['ORIGINAL_REQUEST_METHOD'] = $method;
        if (isset($data['_method'])) {
            $this->_environment['REQUEST_METHOD'] = $data['_method'];
            unset($data['_method']);
            $override = true;
        }

        if ($override && !in_array($this->_environment['REQUEST_METHOD'], ['PUT', 'POST', 'DELETE', 'PATCH'])) {
            $data = [];
        }

        return $data;
    }

    /**
     * Process the GET parameters and move things into the object.
     *
     * @param array $query The array to which the parsed keys/values are being added.
     * @return array An array containing the parsed querystring keys/values.
     */
    protected function _processGet($query)
    {
        $unsetUrl = '/' . str_replace(['.', ' '], '_', urldecode($this->url));
        unset($query[$unsetUrl]);
        unset($query[$this->base . $unsetUrl]);
        if (strpos($this->url, '?') !== false) {
            list(, $querystr) = explode('?', $this->url);
            parse_str($querystr, $queryArgs);
            $query += $queryArgs;
        }

        return $query;
    }

    /**
     * Get the request uri. Looks in PATH_INFO first, as this is the exact value we need prepared
     * by PHP. Following that, REQUEST_URI, PHP_SELF, HTTP_X_REWRITE_URL and argv are checked in that order.
     * Each of these server variables have the base path, and query strings stripped off
     *
     * @param array $config Configuration to set.
     * @return string URI The CakePHP request path that is being accessed.
     */
    protected static function _url($config)
    {
        if (!empty($_SERVER['PATH_INFO'])) {
            return $_SERVER['PATH_INFO'];
        }
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '://') === false) {
            $uri = $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            $fullBaseUrl = Configure::read('App.fullBaseUrl');
            if (strpos($uri, $fullBaseUrl) === 0) {
                $uri = substr($_SERVER['REQUEST_URI'], strlen($fullBaseUrl));
            }
        } elseif (isset($_SERVER['PHP_SELF'], $_SERVER['SCRIPT_NAME'])) {
            $uri = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['PHP_SELF']);
        } elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $uri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif ($var = env('argv')) {
            $uri = $var[0];
        }

        $base = $config['base'];

        if (strlen($base) > 0 && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }
        if (strpos($uri, '?') !== false) {
            list($uri) = explode('?', $uri, 2);
        }
        if (empty($uri) || $uri === '/' || $uri === '//' || $uri === '/index.php') {
            $uri = '/';
        }
        $endsWithIndex = '/webroot/index.php';
        $endsWithLength = strlen($endsWithIndex);
        if (strlen($uri) >= $endsWithLength &&
            substr($uri, -$endsWithLength) === $endsWithIndex
        ) {
            $uri = '/';
        }

        return $uri;
    }

    /**
     * Returns a base URL and sets the proper webroot
     *
     * If CakePHP is called with index.php in the URL even though
     * URL Rewriting is activated (and thus not needed) it swallows
     * the unnecessary part from $base to prevent issue #3318.
     *
     * @return array Base URL, webroot dir ending in /
     */
    protected static function _base()
    {
        $base = $webroot = $baseUrl = null;
        $config = Configure::read('App');
        extract($config);

        if ($base !== false && $base !== null) {
            return [$base, $base . '/'];
        }

        if (!$baseUrl) {
            $base = dirname(env('PHP_SELF'));
            // Clean up additional / which cause following code to fail..
            $base = preg_replace('#/+#', '/', $base);

            $indexPos = strpos($base, '/' . $webroot . '/index.php');
            if ($indexPos !== false) {
                $base = substr($base, 0, $indexPos) . '/' . $webroot;
            }
            if ($webroot === basename($base)) {
                $base = dirname($base);
            }

            if ($base === DIRECTORY_SEPARATOR || $base === '.') {
                $base = '';
            }
            $base = implode('/', array_map('rawurlencode', explode('/', $base)));

            return [$base, $base . '/'];
        }

        $file = '/' . basename($baseUrl);
        $base = dirname($baseUrl);

        if ($base === DIRECTORY_SEPARATOR || $base === '.') {
            $base = '';
        }
        $webrootDir = $base . '/';

        $docRoot = env('DOCUMENT_ROOT');
        $docRootContainsWebroot = strpos($docRoot, $webroot);

        if (!empty($base) || !$docRootContainsWebroot) {
            if (strpos($webrootDir, '/' . $webroot . '/') === false) {
                $webrootDir .= $webroot . '/';
            }
        }

        return [$base . $file, $webrootDir];
    }

    /**
     * Process uploaded files and move things onto the post data.
     *
     * @param array $post Post data to merge files onto.
     * @param array $files Uploaded files to merge in.
     * @return array merged post + file data.
     */
    protected function _processFiles($post, $files)
    {
        if (is_array($files)) {
            foreach ($files as $key => $data) {
                if (isset($data['tmp_name']) && is_string($data['tmp_name'])) {
                    $post[$key] = $data;
                } else {
                    $keyData = isset($post[$key]) ? $post[$key] : [];
                    $post[$key] = $this->_processFileData($keyData, $data);
                }
            }
        }

        return $post;
    }

    /**
     * Recursively walks the FILES array restructuring the data
     * into something sane and usable.
     *
     * @param array $data The data being built
     * @param array $post The post data being traversed
     * @param string $path The dot separated path to insert $data into.
     * @param string $field The terminal field in the path. This is one of the
     *   $_FILES properties e.g. name, tmp_name, size, error
     * @return array The restructured FILES data
     */
    protected function _processFileData($data, $post, $path = '', $field = '')
    {
        foreach ($post as $key => $fields) {
            $newField = $field;
            $newPath = $path;
            if ($path === '' && $newField === '') {
                $newField = $key;
            }
            if ($field === $newField) {
                $newPath .= '.' . $key;
            }
            if (is_array($fields)) {
                $data = $this->_processFileData($data, $fields, $newPath, $newField);
            } else {
                $newPath = trim($newPath . '.' . $field, '.');
                $data = Hash::insert($data, $newPath, $fields);
            }
        }

        return $data;
    }

    /**
     * Get the content type used in this request.
     *
     * @return string
     */
    public function contentType()
    {
        $type = $this->env('CONTENT_TYPE');
        if ($type) {
            return $type;
        }

        return $this->env('HTTP_CONTENT_TYPE');
    }

    /**
     * Returns the instance of the Session object for this request
     *
     * If a session object is passed as first argument it will be set as
     * the session to use for this request
     *
     * @param \Cake\Network\Session|null $session the session object to use
     * @return \Cake\Network\Session
     */
    public function session(Session $session = null)
    {
        if ($session === null) {
            return $this->_session;
        }

        return $this->_session = $session;
    }

    /**
     * Get the IP the client is using, or says they are using.
     *
     * @return string The client IP.
     */
    public function clientIp()
    {
        if ($this->trustProxy && $this->env('HTTP_X_FORWARDED_FOR')) {
            $ipaddr = preg_replace('/(?:,.*)/', '', $this->env('HTTP_X_FORWARDED_FOR'));
        } elseif ($this->trustProxy && $this->env('HTTP_CLIENT_IP')) {
            $ipaddr = $this->env('HTTP_CLIENT_IP');
        } else {
            $ipaddr = $this->env('REMOTE_ADDR');
        }

        return trim($ipaddr);
    }

    /**
     * Returns the referer that referred this request.
     *
     * @param bool $local Attempt to return a local address.
     *   Local addresses do not contain hostnames.
     * @return string The referring address for this request.
     */
    public function referer($local = false)
    {
        $ref = $this->env('HTTP_REFERER');

        $base = Configure::read('App.fullBaseUrl') . $this->webroot;
        if (!empty($ref) && !empty($base)) {
            if ($local && strpos($ref, $base) === 0) {
                $ref = substr($ref, strlen($base));
                if (!strlen($ref)) {
                    $ref = '/';
                }
                if ($ref[0] !== '/') {
                    $ref = '/' . $ref;
                }

                return $ref;
            }
            if (!$local) {
                return $ref;
            }
        }

        return '/';
    }

    /**
     * Missing method handler, handles wrapping older style isAjax() type methods
     *
     * @param string $name The method called
     * @param array $params Array of parameters for the method call
     * @return mixed
     * @throws \BadMethodCallException when an invalid method is called.
     */
    public function __call($name, $params)
    {
        if (strpos($name, 'is') === 0) {
            $type = strtolower(substr($name, 2));
            
            array_unshift($params, $type);
            
            return call_user_func_array([$this, 'is'], $params);
        }
        throw new BadMethodCallException(sprintf('Method %s does not exist', $name));
    }

    /**
     * Magic get method allows access to parsed routing parameters directly on the object.
     *
     * Allows access to `$this->params['controller']` via `$this->controller`
     *
     * @param string $name The property being accessed.
     * @return mixed Either the value of the parameter or null.
     */
    public function __get($name)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        return null;
    }

    /**
     * Magic isset method allows isset/empty checks
     * on routing parameters.
     *
     * @param string $name The property being accessed.
     * @return bool Existence
     */
    public function __isset($name)
    {
        return isset($this->params[$name]);
    }

    /**
     * Check whether or not a Request is a certain type.
     *
     * Uses the built in detection rules as well as additional rules
     * defined with Cake\Network\CakeRequest::addDetector(). Any detector can be called
     * as `is($type)` or `is$Type()`.
     *
     * @param string|array $type The type of request you want to check. If an array
     *   this method will return true if the request matches any type.
     * @return bool Whether or not the request is the type you are checking.
     */
    public function is($type)
    {
        if (is_array($type)) {
            $result = array_map([$this, 'is'], $type);

            return count(array_filter($result)) > 0;
        }
        
        $args = func_get_args();
        array_shift($args);

        $type = strtolower($type);
        if (!isset(static::$_detectors[$type])) {
            return false;
        }
        
        if (empty($args)) {
            if (!isset($this->_detectorCache[$type])) {
                $this->_detectorCache[$type] = $this->_is($type, $args);
            }

            return $this->_detectorCache[$type];
        }
        
        return $this->_is($type, $args);
    }
    
    /**
     * Clears the instance detector cache, used by the is() function
     *
     * @return void
     */
    public function clearDetectorCache()
    {
        $this->_detectorCache = [];
    }

    /**
     * Worker for the public is() function
     *
     * @param string|array $type The type of request you want to check. If an array
     *   this method will return true if the request matches any type.
     * @param array $args Array of custom detector arguments.
     * @return bool Whether or not the request is the type you are checking.
     */
    protected function _is($type, $args)
    {
        $detect = static::$_detectors[$type];
        if (is_callable($detect)) {
            array_unshift($args, $this);

            return call_user_func_array($detect, $args);
        }
        if (isset($detect['env']) && $this->_environmentDetector($detect)) {
            return true;
        }
        if (isset($detect['header']) && $this->_headerDetector($detect)) {
            return true;
        }
        if (isset($detect['accept']) && $this->_acceptHeaderDetector($detect)) {
            return true;
        }
        if (isset($detect['param']) && $this->_paramDetector($detect)) {
            return true;
        }

        return false;
    }

    /**
     * Detects if a specific accept header is present.
     *
     * @param array $detect Detector options array.
     * @return bool Whether or not the request is the type you are checking.
     */
    protected function _acceptHeaderDetector($detect)
    {
        $acceptHeaders = explode(',', $this->env('HTTP_ACCEPT'));
        foreach ($detect['accept'] as $header) {
            if (in_array($header, $acceptHeaders)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detects if a specific header is present.
     *
     * @param array $detect Detector options array.
     * @return bool Whether or not the request is the type you are checking.
     */
    protected function _headerDetector($detect)
    {
        foreach ($detect['header'] as $header => $value) {
            $header = $this->env('http_' . $header);
            if ($header !== null) {
                if (!is_string($value) && !is_bool($value) && is_callable($value)) {
                    return call_user_func($value, $header);
                }

                return ($header === $value);
            }
        }

        return false;
    }

    /**
     * Detects if a specific request parameter is present.
     *
     * @param array $detect Detector options array.
     * @return bool Whether or not the request is the type you are checking.
     */
    protected function _paramDetector($detect)
    {
        $key = $detect['param'];
        if (isset($detect['value'])) {
            $value = $detect['value'];

            return isset($this->params[$key]) ? $this->params[$key] == $value : false;
        }
        if (isset($detect['options'])) {
            return isset($this->params[$key]) ? in_array($this->params[$key], $detect['options']) : false;
        }

        return false;
    }

    /**
     * Detects if a specific environment variable is present.
     *
     * @param array $detect Detector options array.
     * @return bool Whether or not the request is the type you are checking.
     */
    protected function _environmentDetector($detect)
    {
        if (isset($detect['env'])) {
            if (isset($detect['value'])) {
                return $this->env($detect['env']) == $detect['value'];
            }
            if (isset($detect['pattern'])) {
                return (bool)preg_match($detect['pattern'], $this->env($detect['env']));
            }
            if (isset($detect['options'])) {
                $pattern = '/' . implode('|', $detect['options']) . '/i';

                return (bool)preg_match($pattern, $this->env($detect['env']));
            }
        }

        return false;
    }

    /**
     * Check that a request matches all the given types.
     *
     * Allows you to test multiple types and union the results.
     * See Request::is() for how to add additional types and the
     * built-in types.
     *
     * @param array $types The types to check.
     * @return bool Success.
     * @see \Cake\Network\Request::is()
     */
    public function isAll(array $types)
    {
        $result = array_filter(array_map([$this, 'is'], $types));

        return count($result) === count($types);
    }

    /**
     * Add a new detector to the list of detectors that a request can use.
     * There are several different formats and types of detectors that can be set.
     *
     * ### Callback detectors
     *
     * Callback detectors allow you to provide a callable to handle the check.
     * The callback will receive the request object as its only parameter.
     *
     * ```
     * addDetector('custom', function ($request) { //Return a boolean });
     * addDetector('custom', ['SomeClass', 'somemethod']);
     * ```
     *
     * ### Environment value comparison
     *
     * An environment value comparison, compares a value fetched from `env()` to a known value
     * the environment value is equality checked against the provided value.
     *
     * e.g `addDetector('post', ['env' => 'REQUEST_METHOD', 'value' => 'POST'])`
     *
     * ### Pattern value comparison
     *
     * Pattern value comparison allows you to compare a value fetched from `env()` to a regular expression.
     *
     * ```
     * addDetector('iphone', ['env' => 'HTTP_USER_AGENT', 'pattern' => '/iPhone/i']);
     * ```
     *
     * ### Option based comparison
     *
     * Option based comparisons use a list of options to create a regular expression. Subsequent calls
     * to add an already defined options detector will merge the options.
     *
     * ```
     * addDetector('mobile', ['env' => 'HTTP_USER_AGENT', 'options' => ['Fennec']]);
     * ```
     *
     * ### Request parameter detectors
     *
     * Allows for custom detectors on the request parameters.
     *
     * e.g `addDetector('requested', ['param' => 'requested', 'value' => 1]`
     *
     * You can also make parameter detectors that accept multiple values
     * using the `options` key. This is useful when you want to check
     * if a request parameter is in a list of options.
     *
     * `addDetector('extension', ['param' => 'ext', 'options' => ['pdf', 'csv']]`
     *
     * @param string $name The name of the detector.
     * @param callable|array $callable A callable or options array for the detector definition.
     * @return void
     */
    public static function addDetector($name, $callable)
    {
        $name = strtolower($name);
        if (is_callable($callable)) {
            static::$_detectors[$name] = $callable;

            return;
        }
        if (isset(static::$_detectors[$name], $callable['options'])) {
            $callable = Hash::merge(static::$_detectors[$name], $callable);
        }
        static::$_detectors[$name] = $callable;
    }

    /**
     * Add parameters to the request's parsed parameter set. This will overwrite any existing parameters.
     * This modifies the parameters available through `$request->params`.
     *
     * @param array $params Array of parameters to merge in
     * @return $this The current object, you can chain this method.
     */
    public function addParams(array $params)
    {
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    /**
     * Add paths to the requests' paths vars. This will overwrite any existing paths.
     * Provides an easy way to modify, here, webroot and base.
     *
     * @param array $paths Array of paths to merge in
     * @return $this The current object, you can chain this method.
     */
    public function addPaths(array $paths)
    {
        foreach (['webroot', 'here', 'base'] as $element) {
            if (isset($paths[$element])) {
                $this->{$element} = $paths[$element];
            }
        }

        return $this;
    }

    /**
     * Get the value of the current requests URL. Will include querystring arguments.
     *
     * @param bool $base Include the base path, set to false to trim the base path off.
     * @return string The current request URL including query string args.
     */
    public function here($base = true)
    {
        $url = $this->here;
        if (!empty($this->query)) {
            $url .= '?' . http_build_query($this->query, null, '&');
        }
        if (!$base) {
            $url = preg_replace('/^' . preg_quote($this->base, '/') . '/', '', $url, 1);
        }

        return $url;
    }

    /**
     * Read an HTTP header from the Request information.
     *
     * @param string $name Name of the header you want.
     * @return string|null Either null on no header being set or the value of the header.
     */
    public function header($name)
    {
        $name = str_replace('-', '_', $name);
        if (!in_array(strtoupper($name), ['CONTENT_LENGTH', 'CONTENT_TYPE'])) {
            $name = 'HTTP_' . $name;
        }

        return $this->env($name);
    }

    /**
     * Get the HTTP method used for this request.
     * There are a few ways to specify a method.
     *
     * - If your client supports it you can use native HTTP methods.
     * - You can set the HTTP-X-Method-Override header.
     * - You can submit an input with the name `_method`
     *
     * Any of these 3 approaches can be used to set the HTTP method used
     * by CakePHP internally, and will effect the result of this method.
     *
     * @return string The name of the HTTP method used.
     */
    public function method()
    {
        return $this->env('REQUEST_METHOD');
    }

    /**
     * Get the host that the request was handled on.
     *
     * @return string
     */
    public function host()
    {
        if ($this->trustProxy && $this->env('HTTP_X_FORWARDED_HOST')) {
            return $this->env('HTTP_X_FORWARDED_HOST');
        }

        return $this->env('HTTP_HOST');
    }

    /**
     * Get the port the request was handled on.
     *
     * @return string
     */
    public function port()
    {
        if ($this->trustProxy && $this->env('HTTP_X_FORWARDED_PORT')) {
            return $this->env('HTTP_X_FORWARDED_PORT');
        }

        return $this->env('SERVER_PORT');
    }

    /**
     * Get the current url scheme used for the request.
     *
     * e.g. 'http', or 'https'
     *
     * @return string The scheme used for the request.
     */
    public function scheme()
    {
        if ($this->trustProxy && $this->env('HTTP_X_FORWARDED_PROTO')) {
            return $this->env('HTTP_X_FORWARDED_PROTO');
        }

        return $this->env('HTTPS') ? 'https' : 'http';
    }

    /**
     * Get the domain name and include $tldLength segments of the tld.
     *
     * @param int $tldLength Number of segments your tld contains. For example: `example.com` contains 1 tld.
     *   While `example.co.uk` contains 2.
     * @return string Domain name without subdomains.
     */
    public function domain($tldLength = 1)
    {
        $segments = explode('.', $this->host());
        $domain = array_slice($segments, -1 * ($tldLength + 1));

        return implode('.', $domain);
    }

    /**
     * Get the subdomains for a host.
     *
     * @param int $tldLength Number of segments your tld contains. For example: `example.com` contains 1 tld.
     *   While `example.co.uk` contains 2.
     * @return array An array of subdomains.
     */
    public function subdomains($tldLength = 1)
    {
        $segments = explode('.', $this->host());

        return array_slice($segments, 0, -1 * ($tldLength + 1));
    }

    /**
     * Find out which content types the client accepts or check if they accept a
     * particular type of content.
     *
     * #### Get all types:
     *
     * ```
     * $this->request->accepts();
     * ```
     *
     * #### Check for a single type:
     *
     * ```
     * $this->request->accepts('application/json');
     * ```
     *
     * This method will order the returned content types by the preference values indicated
     * by the client.
     *
     * @param string|null $type The content type to check for. Leave null to get all types a client accepts.
     * @return array|bool Either an array of all the types the client accepts or a boolean if they accept the
     *   provided type.
     */
    public function accepts($type = null)
    {
        $raw = $this->parseAccept();
        $accept = [];
        foreach ($raw as $types) {
            $accept = array_merge($accept, $types);
        }
        if ($type === null) {
            return $accept;
        }

        return in_array($type, $accept);
    }

    /**
     * Parse the HTTP_ACCEPT header and return a sorted array with content types
     * as the keys, and pref values as the values.
     *
     * Generally you want to use Cake\Network\Request::accept() to get a simple list
     * of the accepted content types.
     *
     * @return array An array of prefValue => [content/types]
     */
    public function parseAccept()
    {
        return $this->_parseAcceptWithQualifier($this->header('accept'));
    }

    /**
     * Get the languages accepted by the client, or check if a specific language is accepted.
     *
     * Get the list of accepted languages:
     *
     * ``` \Cake\Network\Request::acceptLanguage(); ```
     *
     * Check if a specific language is accepted:
     *
     * ``` \Cake\Network\Request::acceptLanguage('es-es'); ```
     *
     * @param string|null $language The language to test.
     * @return array|bool If a $language is provided, a boolean. Otherwise the array of accepted languages.
     */
    public function acceptLanguage($language = null)
    {
        $raw = $this->_parseAcceptWithQualifier($this->header('Accept-Language'));
        $accept = [];
        foreach ($raw as $languages) {
            foreach ($languages as &$lang) {
                if (strpos($lang, '_')) {
                    $lang = str_replace('_', '-', $lang);
                }
                $lang = strtolower($lang);
            }
            $accept = array_merge($accept, $languages);
        }
        if ($language === null) {
            return $accept;
        }

        return in_array(strtolower($language), $accept);
    }

    /**
     * Parse Accept* headers with qualifier options.
     *
     * Only qualifiers will be extracted, any other accept extensions will be
     * discarded as they are not frequently used.
     *
     * @param string $header Header to parse.
     * @return array
     */
    protected function _parseAcceptWithQualifier($header)
    {
        $accept = [];
        $header = explode(',', $header);
        foreach (array_filter($header) as $value) {
            $prefValue = '1.0';
            $value = trim($value);

            $semiPos = strpos($value, ';');
            if ($semiPos !== false) {
                $params = explode(';', $value);
                $value = trim($params[0]);
                foreach ($params as $param) {
                    $qPos = strpos($param, 'q=');
                    if ($qPos !== false) {
                        $prefValue = substr($param, $qPos + 2);
                    }
                }
            }

            if (!isset($accept[$prefValue])) {
                $accept[$prefValue] = [];
            }
            if ($prefValue) {
                $accept[$prefValue][] = $value;
            }
        }
        krsort($accept);

        return $accept;
    }

    /**
     * Provides a read accessor for `$this->query`. Allows you
     * to use a syntax similar to `CakeSession` for reading URL query data.
     *
     * @param string $name Query string variable name
     * @return mixed The value being read
     */
    public function query($name)
    {
        return Hash::get($this->query, $name);
    }

    /**
     * Provides a read/write accessor for `$this->data`. Allows you
     * to use a syntax similar to `Cake\Model\Datasource\Session` for reading post data.
     *
     * ### Reading values.
     *
     * ```
     * $request->data('Post.title');
     * ```
     *
     * When reading values you will get `null` for keys/values that do not exist.
     *
     * ### Writing values
     *
     * ```
     * $request->data('Post.title', 'New post!');
     * ```
     *
     * You can write to any value, even paths/keys that do not exist, and the arrays
     * will be created for you.
     *
     * @param string|null $name Dot separated name of the value to read/write
     * @return mixed|$this Either the value being read, or this so you can chain consecutive writes.
     */
    public function data($name = null)
    {
        $args = func_get_args();
        if (count($args) === 2) {
            $this->data = Hash::insert($this->data, $name, $args[1]);

            return $this;
        }
        if ($name !== null) {
            return Hash::get($this->data, $name);
        }

        return $this->data;
    }

    /**
     * Safely access the values in $this->params.
     *
     * @param string $name The name of the parameter to get.
     * @return mixed|$this The value of the provided parameter. Will
     *   return false if the parameter doesn't exist or is falsey.
     */
    public function param($name)
    {
        $args = func_get_args();
        if (count($args) === 2) {
            $this->params = Hash::insert($this->params, $name, $args[1]);

            return $this;
        }
        if (!isset($this->params[$name])) {
            return Hash::get($this->params, $name, false);
        }

        return $this->params[$name];
    }

    /**
     * Read data from `php://input`. Useful when interacting with XML or JSON
     * request body content.
     *
     * Getting input with a decoding function:
     *
     * ```
     * $this->request->input('json_decode');
     * ```
     *
     * Getting input using a decoding function, and additional params:
     *
     * ```
     * $this->request->input('Xml::build', ['return' => 'DOMDocument']);
     * ```
     *
     * Any additional parameters are applied to the callback in the order they are given.
     *
     * @param string|null $callback A decoding callback that will convert the string data to another
     *     representation. Leave empty to access the raw input data. You can also
     *     supply additional parameters for the decoding callback using var args, see above.
     * @return string The decoded/processed request data.
     */
    public function input($callback = null)
    {
        $input = $this->_readInput();
        $args = func_get_args();
        if (!empty($args)) {
            $callback = array_shift($args);
            array_unshift($args, $input);

            return call_user_func_array($callback, $args);
        }

        return $input;
    }

    /**
     * Read cookie data from the request's cookie data.
     *
     * @param string $key The key you want to read.
     * @return null|string Either the cookie value, or null if the value doesn't exist.
     */
    public function cookie($key)
    {
        if (isset($this->cookies[$key])) {
            return $this->cookies[$key];
        }

        return null;
    }

    /**
     * Get/Set value from the request's environment data.
     * Fallback to using env() if key not set in $environment property.
     *
     * @param string $key The key you want to read/write from/to.
     * @param string|null $value Value to set. Default null.
     * @param string|null $default Default value when trying to retrieve an environment
     *   variable's value that does not exist. The value parameter must be null.
     * @return $this|string|null This instance if used as setter,
     *   if used as getter either the environment value, or null if the value doesn't exist.
     */
    public function env($key, $value = null, $default = null)
    {
        if ($value !== null) {
            $this->_environment[$key] = $value;
            $this->clearDetectorCache();

            return $this;
        }

        $key = strtoupper($key);
        if (!array_key_exists($key, $this->_environment)) {
            $this->_environment[$key] = env($key);
        }

        return $this->_environment[$key] !== null ? $this->_environment[$key] : $default;
    }

    /**
     * Allow only certain HTTP request methods, if the request method does not match
     * a 405 error will be shown and the required "Allow" response header will be set.
     *
     * Example:
     *
     * $this->request->allowMethod('post');
     * or
     * $this->request->allowMethod(['post', 'delete']);
     *
     * If the request would be GET, response header "Allow: POST, DELETE" will be set
     * and a 405 error will be returned.
     *
     * @param string|array $methods Allowed HTTP request methods.
     * @return bool true
     * @throws \Cake\Network\Exception\MethodNotAllowedException
     */
    public function allowMethod($methods)
    {
        $methods = (array)$methods;
        foreach ($methods as $method) {
            if ($this->is($method)) {
                return true;
            }
        }
        $allowed = strtoupper(implode(', ', $methods));
        $e = new MethodNotAllowedException();
        $e->responseHeader('Allow', $allowed);
        throw $e;
    }

    /**
     * Read data from php://input, mocked in tests.
     *
     * @return string contents of php://input
     */
    protected function _readInput()
    {
        if (empty($this->_input)) {
            $fh = fopen('php://input', 'r');
            $content = stream_get_contents($fh);
            fclose($fh);
            $this->_input = $content;
        }

        return $this->_input;
    }

    /**
     * Modify data originally from `php://input`. Useful for altering json/xml data
     * in middleware or DispatcherFilters before it gets to RequestHandlerComponent
     *
     * @param string $input A string to replace original parsed data from input()
     * @return void
     */
    public function setInput($input)
    {
        $this->_input = $input;
    }

    /**
     * Array access read implementation
     *
     * @param string $name Name of the key being accessed.
     * @return mixed
     */
    public function offsetGet($name)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }
        if ($name === 'url') {
            return $this->query;
        }
        if ($name === 'data') {
            return $this->data;
        }

        return null;
    }

    /**
     * Array access write implementation
     *
     * @param string $name Name of the key being written
     * @param mixed $value The value being written.
     * @return void
     */
    public function offsetSet($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * Array access isset() implementation
     *
     * @param string $name thing to check.
     * @return bool
     */
    public function offsetExists($name)
    {
        if ($name === 'url' || $name === 'data') {
            return true;
        }

        return isset($this->params[$name]);
    }

    /**
     * Array access unset() implementation
     *
     * @param string $name Name to unset.
     * @return void
     */
    public function offsetUnset($name)
    {
        unset($this->params[$name]);
    }
}
