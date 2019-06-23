<?php
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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use ArrayAccess;
use BadMethodCallException;
use Cake\Core\Configure;
use Cake\Http\Cookie\CookieCollection;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Session;
use Cake\Utility\Hash;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Zend\Diactoros\PhpInputStream;
use Zend\Diactoros\Stream;
use Zend\Diactoros\UploadedFile;

/**
 * A class that helps wrap Request information and particulars about a single request.
 * Provides methods commonly used to introspect on the request headers and request body.
 */
class ServerRequest implements ArrayAccess, ServerRequestInterface
{

    /**
     * Array of parameters parsed from the URL.
     *
     * @var array
     * @deprecated 3.4.0 This public property will be removed in 4.0.0. Use getParam() instead.
     */
    protected $params = [
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
     * @var null|array|object
     * @deprecated 3.4.0 This public property will be removed in 4.0.0. Use getData() instead.
     */
    protected $data = [];

    /**
     * Array of query string arguments
     *
     * @var array
     * @deprecated 3.4.0 This public property will be removed in 4.0.0. Use getQuery() or getQueryParams() instead.
     */
    protected $query = [];

    /**
     * Array of cookie data.
     *
     * @var array
     * @deprecated 3.4.0 This public property will be removed in 4.0.0. Use getCookie() instead.
     */
    protected $cookies = [];

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
     * @deprecated 3.6.0 This public property will be removed in 4.0.0. Use getPath() instead.
     */
    protected $url;

    /**
     * Base URL path.
     *
     * @var string
     * @deprecated 3.4.0 This public property will be removed in 4.0.0. Use getAttribute('base') instead.
     */
    protected $base;

    /**
     * webroot path segment for the request.
     *
     * @var string
     * @deprecated 3.4.0 This public property will be removed in 4.0.0. Use getAttribute('webroot') instead.
     */
    protected $webroot = '/';

    /**
     * The full address to the current request
     *
     * @var string
     * @deprecated 3.4.0 This public property will be removed in 4.0.0. Use getAttribute('here') or getUri()->getPath() instead.
     */
    protected $here;

    /**
     * Whether or not to trust HTTP_X headers set by most load balancers.
     * Only set to true if your application runs behind load balancers/proxies
     * that you control.
     *
     * @var bool
     */
    public $trustProxy = false;

    /**
     * trusted proxies list
     *
     * @var array
     */
    protected $trustedProxies = [];

    /**
     * Contents of php://input
     *
     * @var string
     */
    protected $_input;

    /**
     * The built in detectors used with `is()` can be modified with `addDetector()`.
     *
     * There are several ways to specify a detector, see \Cake\Http\ServerRequest::addDetector() for the
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
     * Request body stream. Contains php://input unless `input` constructor option is used.
     *
     * @var \Psr\Http\Message\StreamInterface
     */
    protected $stream;

    /**
     * Uri instance
     *
     * @var \Psr\Http\Message\UriInterface
     */
    protected $uri;

    /**
     * Instance of a Session object relative to this request
     *
     * @var \Cake\Http\Session
     */
    protected $session;

    /**
     * Store the additional attributes attached to the request.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * A list of propertes that emulated by the PSR7 attribute methods.
     *
     * @var array
     */
    protected $emulatedAttributes = ['session', 'webroot', 'base', 'params', 'here'];

    /**
     * Array of Psr\Http\Message\UploadedFileInterface objects.
     *
     * @var array
     */
    protected $uploadedFiles = [];

    /**
     * The HTTP protocol version used.
     *
     * @var string|null
     */
    protected $protocol;

    /**
     * The request target if overridden
     *
     * @var string|null
     */
    protected $requestTarget;

    /**
     * List of deprecated properties that have backwards
     * compatibility offered through magic methods.
     *
     * @var array
     */
    private $deprecatedProperties = [
        'data' => ['get' => 'getData()', 'set' => 'withData()'],
        'query' => ['get' => 'getQuery()', 'set' => 'withQueryParams()'],
        'params' => ['get' => 'getParam()', 'set' => 'withParam()'],
        'cookies' => ['get' => 'getCookie()', 'set' => 'withCookieParams()'],
        'url' => ['get' => 'getPath()', 'set' => 'withRequestTarget()'],
        'base' => ['get' => 'getAttribute("base")', 'set' => 'withAttribute("base")'],
        'webroot' => ['get' => 'getAttribute("webroot")', 'set' => 'withAttribute("webroot")'],
        'here' => ['get' => 'getAttribute("here")', 'set' => 'withAttribute("here")'],
    ];

    /**
     * Wrapper method to create a new request from PHP superglobals.
     *
     * Uses the $_GET, $_POST, $_FILES, $_COOKIE, $_SERVER, $_ENV and php://input data to construct
     * the request.
     *
     * @return self
     * @deprecated 3.4.0 Use `Cake\Http\ServerRequestFactory` instead.
     */
    public static function createFromGlobals()
    {
        deprecationWarning(
            'ServerRequest::createFromGlobals() is deprecated. ' .
            'Use `Cake\Http\ServerRequestFactory` instead.'
        );

        return ServerRequestFactory::fromGlobals();
    }

    /**
     * Create a new request object.
     *
     * You can supply the data as either an array or as a string. If you use
     * a string you can only supply the URL for the request. Using an array will
     * let you provide the following keys:
     *
     * - `post` POST data or non query string data
     * - `query` Additional data from the query string.
     * - `files` Uploaded file data formatted like $_FILES.
     * - `cookies` Cookies for this request.
     * - `environment` $_SERVER and $_ENV data.
     * - `url` The URL without the base path for the request.
     * - `uri` The PSR7 UriInterface object. If null, one will be created.
     * - `base` The base URL for the request.
     * - `webroot` The webroot directory for the request.
     * - `input` The data that would come from php://input this is useful for simulating
     *   requests with put, patch or delete data.
     * - `session` An instance of a Session object
     *
     * @param string|array $config An array of request data to create a request with.
     *   The string version of this argument is *deprecated* and will be removed in 4.0.0
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
            'uri' => null,
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
        if (strlen($config['url']) > 1 && $config['url'][0] === '/') {
            $config['url'] = substr($config['url'], 1);
        }

        if (empty($config['session'])) {
            $config['session'] = new Session([
                'cookiePath' => $config['base']
            ]);
        }

        $this->_environment = $config['environment'];
        $this->cookies = $config['cookies'];

        if (isset($config['uri']) && $config['uri'] instanceof UriInterface) {
            $uri = $config['uri'];
        } else {
            $uri = ServerRequestFactory::createUri($config['environment']);
        }

        // Extract a query string from config[url] if present.
        // This is required for backwards compatibility and keeping
        // UriInterface implementations happy.
        $querystr = '';
        if (strpos($config['url'], '?') !== false) {
            list($config['url'], $querystr) = explode('?', $config['url']);
        }
        if (strlen($config['url'])) {
            $uri = $uri->withPath('/' . $config['url']);
        }
        if (strlen($querystr)) {
            $uri = $uri->withQuery($querystr);
        }

        $this->uri = $uri;
        $this->base = $config['base'];
        $this->webroot = $config['webroot'];

        $this->url = substr($uri->getPath(), 1);
        $this->here = $this->base . '/' . $this->url;

        if (isset($config['input'])) {
            $stream = new Stream('php://memory', 'rw');
            $stream->write($config['input']);
            $stream->rewind();
        } else {
            $stream = new PhpInputStream();
        }
        $this->stream = $stream;

        $config['post'] = $this->_processPost($config['post']);
        $this->data = $this->_processFiles($config['post'], $config['files']);
        $this->query = $this->_processGet($config['query'], $querystr);
        $this->params = $config['params'];
        $this->session = $config['session'];
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
        $method = $this->getEnv('REQUEST_METHOD');
        $override = false;

        if (in_array($method, ['PUT', 'DELETE', 'PATCH']) &&
            strpos($this->contentType(), 'application/x-www-form-urlencoded') === 0
        ) {
            $data = $this->input();
            parse_str($data, $data);
        }
        if ($this->hasHeader('X-Http-Method-Override')) {
            $data['_method'] = $this->getHeaderLine('X-Http-Method-Override');
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
     * @param string $queryString A query string from the URL if provided
     * @return array An array containing the parsed query string as keys/values.
     */
    protected function _processGet($query, $queryString = '')
    {
        $unsetUrl = '/' . str_replace(['.', ' '], '_', urldecode($this->url));
        unset($query[$unsetUrl], $query[$this->base . $unsetUrl]);
        if (strlen($queryString)) {
            parse_str($queryString, $queryArgs);
            $query += $queryArgs;
        }

        return $query;
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
        if (!is_array($files)) {
            return $post;
        }
        $fileData = [];
        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $fileData[$key] = $value;
                continue;
            }

            if (is_array($value) && isset($value['tmp_name'])) {
                $fileData[$key] = $this->_createUploadedFile($value);
                continue;
            }

            throw new InvalidArgumentException(sprintf(
                'Invalid value in FILES "%s"',
                json_encode($value)
            ));
        }
        $this->uploadedFiles = $fileData;

        // Make a flat map that can be inserted into $post for BC.
        $fileMap = Hash::flatten($fileData);
        foreach ($fileMap as $key => $file) {
            $error = $file->getError();
            $tmpName = '';
            if ($error === UPLOAD_ERR_OK) {
                $tmpName = $file->getStream()->getMetadata('uri');
            }
            $post = Hash::insert($post, $key, [
                'tmp_name' => $tmpName,
                'error' => $error,
                'name' => $file->getClientFilename(),
                'type' => $file->getClientMediaType(),
                'size' => $file->getSize(),
            ]);
        }

        return $post;
    }

    /**
     * Create an UploadedFile instance from a $_FILES array.
     *
     * If the value represents an array of values, this method will
     * recursively process the data.
     *
     * @param array $value $_FILES struct
     * @return array|UploadedFileInterface
     */
    protected function _createUploadedFile(array $value)
    {
        if (is_array($value['tmp_name'])) {
            return $this->_normalizeNestedFiles($value);
        }

        return new UploadedFile(
            $value['tmp_name'],
            $value['size'],
            $value['error'],
            $value['name'],
            $value['type']
        );
    }

    /**
     * Normalize an array of file specifications.
     *
     * Loops through all nested files and returns a normalized array of
     * UploadedFileInterface instances.
     *
     * @param array $files The file data to normalize & convert.
     * @return array An array of UploadedFileInterface objects.
     */
    protected function _normalizeNestedFiles(array $files = [])
    {
        $normalizedFiles = [];
        foreach (array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
            ];
            $normalizedFiles[$key] = $this->_createUploadedFile($spec);
        }

        return $normalizedFiles;
    }

    /**
     * Get the content type used in this request.
     *
     * @return string
     */
    public function contentType()
    {
        $type = $this->getEnv('CONTENT_TYPE');
        if ($type) {
            return $type;
        }

        return $this->getEnv('HTTP_CONTENT_TYPE');
    }

    /**
     * Returns the instance of the Session object for this request
     *
     * @return \Cake\Http\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Returns the instance of the Session object for this request
     *
     * If a session object is passed as first argument it will be set as
     * the session to use for this request
     *
     * @deprecated 3.5.0 Use getSession() instead. The setter part will be removed.
     * @param \Cake\Http\Session|null $session the session object to use
     * @return \Cake\Http\Session
     */
    public function session(Session $session = null)
    {
        deprecationWarning(
            'ServerRequest::session() is deprecated. ' .
            'Use getSession() instead. The setter part will be removed.'
        );

        if ($session === null) {
            return $this->session;
        }

        return $this->session = $session;
    }

    /**
     * Get the IP the client is using, or says they are using.
     *
     * @return string The client IP.
     */
    public function clientIp()
    {
        if ($this->trustProxy && $this->getEnv('HTTP_X_FORWARDED_FOR')) {
            $addresses = array_map('trim', explode(',', $this->getEnv('HTTP_X_FORWARDED_FOR')));
            $trusted = (count($this->trustedProxies) > 0);
            $n = count($addresses);

            if ($trusted) {
                $trusted = array_diff($addresses, $this->trustedProxies);
                $trusted = (count($trusted) === 1);
            }

            if ($trusted) {
                return $addresses[0];
            }

            return $addresses[$n - 1];
        }

        if ($this->trustProxy && $this->getEnv('HTTP_X_REAL_IP')) {
            $ipaddr = $this->getEnv('HTTP_X_REAL_IP');
        } elseif ($this->trustProxy && $this->getEnv('HTTP_CLIENT_IP')) {
            $ipaddr = $this->getEnv('HTTP_CLIENT_IP');
        } else {
            $ipaddr = $this->getEnv('REMOTE_ADDR');
        }

        return trim($ipaddr);
    }

    /**
     * register trusted proxies
     *
     * @param array $proxies ips list of trusted proxies
     * @return void
     */
    public function setTrustedProxies(array $proxies)
    {
        $this->trustedProxies = $proxies;
        $this->trustProxy = true;
    }

    /**
     * Get trusted proxies
     *
     * @return array
     */
    public function getTrustedProxies()
    {
        return $this->trustedProxies;
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
        $ref = $this->getEnv('HTTP_REFERER');

        $base = Configure::read('App.fullBaseUrl') . $this->webroot;
        if (!empty($ref) && !empty($base)) {
            if ($local && strpos($ref, $base) === 0) {
                $ref = substr($ref, strlen($base));
                if (!strlen($ref) || strpos($ref, '//') === 0) {
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

            return $this->is(...$params);
        }
        throw new BadMethodCallException(sprintf('Method %s does not exist', $name));
    }

    /**
     * Magic set method allows backward compatibility for former public properties
     *
     *
     * @param string $name The property being accessed.
     * @param mixed $value The property value.
     * @return mixed Either the value of the parameter or null.
     * @deprecated 3.6.0 Public properties will be removed in 4.0.0.
     *   Use appropriate setters instead.
     */
    public function __set($name, $value)
    {
        if (isset($this->deprecatedProperties[$name])) {
            $method = $this->deprecatedProperties[$name]['set'];
            deprecationWarning(
                "Setting {$name} as a property will be removed in 4.0.0. " .
                "Use {$method} instead."
            );

            return $this->{$name} = $value;
        }
        throw new BadMethodCallException("Cannot set {$name} it is not a known property.");
    }

    /**
     * Magic get method allows access to parsed routing parameters directly on the object.
     *
     * Allows access to `$this->params['controller']` via `$this->controller`
     *
     * @param string $name The property being accessed.
     * @return mixed Either the value of the parameter or null.
     * @deprecated 3.4.0 Accessing routing parameters through __get will removed in 4.0.0.
     *   Use getParam() instead.
     */
    public function &__get($name)
    {
        if (isset($this->deprecatedProperties[$name])) {
            $method = $this->deprecatedProperties[$name]['get'];
            deprecationWarning(
                "Accessing `{$name}` as a property will be removed in 4.0.0. " .
                "Use request->{$method} instead."
            );

            return $this->{$name};
        }

        deprecationWarning(sprintf(
            'Accessing routing parameters through `%s` will removed in 4.0.0. ' .
            'Use `getParam()` instead.',
            $name
        ));

        if (isset($this->params[$name])) {
            return $this->params[$name];
        }
        $value = null;

        return $value;
    }

    /**
     * Magic isset method allows isset/empty checks
     * on routing parameters.
     *
     * @param string $name The property being accessed.
     * @return bool Existence
     * @deprecated 3.4.0 Accessing routing parameters through __isset will removed in 4.0.0.
     *   Use getParam() instead.
     */
    public function __isset($name)
    {
        if (isset($this->deprecatedProperties[$name])) {
            $method = $this->deprecatedProperties[$name]['get'];
            deprecationWarning(
                "Accessing {$name} as a property will be removed in 4.0.0. " .
                "Use {$method} instead."
            );

            return isset($this->{$name});
        }

        deprecationWarning(
            'Accessing routing parameters through __isset will removed in 4.0.0. ' .
            'Use getParam() instead.'
        );

        return isset($this->params[$name]);
    }

    /**
     * Check whether or not a Request is a certain type.
     *
     * Uses the built in detection rules as well as additional rules
     * defined with Cake\Http\ServerRequest::addDetector(). Any detector can be called
     * as `is($type)` or `is$Type()`.
     *
     * @param string|array $type The type of request you want to check. If an array
     *   this method will return true if the request matches any type.
     * @param array ...$args List of arguments
     * @return bool Whether or not the request is the type you are checking.
     */
    public function is($type, ...$args)
    {
        if (is_array($type)) {
            $result = array_map([$this, 'is'], $type);

            return count(array_filter($result)) > 0;
        }

        $type = strtolower($type);
        if (!isset(static::$_detectors[$type])) {
            return false;
        }
        if ($args) {
            return $this->_is($type, $args);
        }
        if (!isset($this->_detectorCache[$type])) {
            $this->_detectorCache[$type] = $this->_is($type, $args);
        }

        return $this->_detectorCache[$type];
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
     * @param string $type The type of request you want to check.
     * @param array $args Array of custom detector arguments.
     * @return bool Whether or not the request is the type you are checking.
     */
    protected function _is($type, $args)
    {
        $detect = static::$_detectors[$type];
        if (is_callable($detect)) {
            array_unshift($args, $this);

            return $detect(...$args);
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
        $acceptHeaders = explode(',', $this->getEnv('HTTP_ACCEPT'));
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
            $header = $this->getEnv('http_' . $header);
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
                return $this->getEnv($detect['env']) == $detect['value'];
            }
            if (isset($detect['pattern'])) {
                return (bool)preg_match($detect['pattern'], $this->getEnv($detect['env']));
            }
            if (isset($detect['options'])) {
                $pattern = '/' . implode('|', $detect['options']) . '/i';

                return (bool)preg_match($pattern, $this->getEnv($detect['env']));
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
     * @see \Cake\Http\ServerRequest::is()
     */
    public function isAll(array $types)
    {
        $result = array_filter(array_map([$this, 'is'], $types));

        return count($result) === count($types);
    }

    /**
     * Add a new detector to the list of detectors that a request can use.
     * There are several different types of detectors that can be set.
     *
     * ### Callback comparison
     *
     * Callback detectors allow you to provide a callable to handle the check.
     * The callback will receive the request object as its only parameter.
     *
     * ```
     * addDetector('custom', function ($request) { //Return a boolean });
     * ```
     *
     * ### Environment value comparison
     *
     * An environment value comparison, compares a value fetched from `env()` to a known value
     * the environment value is equality checked against the provided value.
     *
     * ```
     * addDetector('post', ['env' => 'REQUEST_METHOD', 'value' => 'POST']);
     * ```
     *
     * ### Request parameter comparison
     *
     * Allows for custom detectors on the request parameters.
     *
     * ```
     * addDetector('requested', ['param' => 'requested', 'value' => 1]);
     * ```
     *
     * ### Accept comparison
     *
     * Allows for detector to compare against Accept header value.
     *
     * ```
     * addDetector('csv', ['accept' => 'text/csv']);
     * ```
     *
     * ### Header comparison
     *
     * Allows for one or more headers to be compared.
     *
     * ```
     * addDetector('fancy', ['header' => ['X-Fancy' => 1]);
     * ```
     *
     * The `param`, `env` and comparison types allow the following
     * value comparison options:
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
     * You can also make compare against multiple values
     * using the `options` key. This is useful when you want to check
     * if a request value is in a list of options.
     *
     * `addDetector('extension', ['param' => '_ext', 'options' => ['pdf', 'csv']]`
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
     * This modifies the parameters available through `$request->getParam()`.
     *
     * @param array $params Array of parameters to merge in
     * @return $this The current object, you can chain this method.
     * @deprecated 3.6.0 ServerRequest::addParams() is deprecated. Use `withParam()` or
     *   `withAttribute('params')` instead.
     */
    public function addParams(array $params)
    {
        deprecationWarning(
            'ServerRequest::addParams() is deprecated. ' .
            'Use `withParam()` or `withAttribute("params", $params)` instead.'
        );
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    /**
     * Add paths to the requests' paths vars. This will overwrite any existing paths.
     * Provides an easy way to modify, here, webroot and base.
     *
     * @param array $paths Array of paths to merge in
     * @return $this The current object, you can chain this method.
     * @deprecated 3.6.0 Mutating a request in place is deprecated. Use `withAttribute()` to modify paths instead.
     */
    public function addPaths(array $paths)
    {
        deprecationWarning(
            'ServerRequest::addPaths() is deprecated. ' .
            'Use `withAttribute($key, $value)` instead.'
        );
        foreach (['webroot', 'here', 'base'] as $element) {
            if (isset($paths[$element])) {
                $this->{$element} = $paths[$element];
            }
        }

        return $this;
    }

    /**
     * Get the value of the current requests URL. Will include the query string arguments.
     *
     * @param bool $base Include the base path, set to false to trim the base path off.
     * @return string The current request URL including query string args.
     * @deprecated 3.4.0 This method will be removed in 4.0.0. You should use getRequestTarget() instead.
     */
    public function here($base = true)
    {
        deprecationWarning(
            'ServerRequest::here() will be removed in 4.0.0. You should use getRequestTarget() instead.'
        );

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
     * Normalize a header name into the SERVER version.
     *
     * @param string $name The header name.
     * @return string The normalized header name.
     */
    protected function normalizeHeaderName($name)
    {
        $name = str_replace('-', '_', strtoupper($name));
        if (!in_array($name, ['CONTENT_LENGTH', 'CONTENT_TYPE'])) {
            $name = 'HTTP_' . $name;
        }

        return $name;
    }

    /**
     * Read an HTTP header from the Request information.
     *
     * If the header is not defined in the request, this method
     * will fallback to reading data from $_SERVER and $_ENV.
     * This fallback behavior is deprecated, and will be removed in 4.0.0
     *
     * @param string $name Name of the header you want.
     * @return string|null Either null on no header being set or the value of the header.
     * @deprecated 4.0.0 The automatic fallback to env() will be removed in 4.0.0, see getHeader()
     */
    public function header($name)
    {
        deprecationWarning(
            'ServerRequest::header() is deprecated. ' .
            'The automatic fallback to env() will be removed in 4.0.0, see getHeader()'
        );

        $name = $this->normalizeHeaderName($name);

        return $this->getEnv($name);
    }

    /**
     * Get all headers in the request.
     *
     * Returns an associative array where the header names are
     * the keys and the values are a list of header values.
     *
     * While header names are not case-sensitive, getHeaders() will normalize
     * the headers.
     *
     * @return array An associative array of headers and their values.
     * @link http://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function getHeaders()
    {
        $headers = [];
        foreach ($this->_environment as $key => $value) {
            $name = null;
            if (strpos($key, 'HTTP_') === 0) {
                $name = substr($key, 5);
            }
            if (strpos($key, 'CONTENT_') === 0) {
                $name = $key;
            }
            if ($name !== null) {
                $name = str_replace('_', ' ', strtolower($name));
                $name = str_replace(' ', '-', ucwords($name));
                $headers[$name] = (array)$value;
            }
        }

        return $headers;
    }

    /**
     * Check if a header is set in the request.
     *
     * @param string $name The header you want to get (case-insensitive)
     * @return bool Whether or not the header is defined.
     * @link http://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function hasHeader($name)
    {
        $name = $this->normalizeHeaderName($name);

        return isset($this->_environment[$name]);
    }

    /**
     * Get a single header from the request.
     *
     * Return the header value as an array. If the header
     * is not present an empty array will be returned.
     *
     * @param string $name The header you want to get (case-insensitive)
     * @return array An associative array of headers and their values.
     *   If the header doesn't exist, an empty array will be returned.
     * @link http://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function getHeader($name)
    {
        $name = $this->normalizeHeaderName($name);
        if (isset($this->_environment[$name])) {
            return (array)$this->_environment[$name];
        }

        return [];
    }

    /**
     * Get a single header as a string from the request.
     *
     * @param string $name The header you want to get (case-insensitive)
     * @return string Header values collapsed into a comma separated string.
     * @link http://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function getHeaderLine($name)
    {
        $value = $this->getHeader($name);

        return implode(', ', $value);
    }

    /**
     * Get a modified request with the provided header.
     *
     * @param string $name The header name.
     * @param string|array $value The header value
     * @return static
     * @link http://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function withHeader($name, $value)
    {
        $new = clone $this;
        $name = $this->normalizeHeaderName($name);
        $new->_environment[$name] = $value;

        return $new;
    }

    /**
     * Get a modified request with the provided header.
     *
     * Existing header values will be retained. The provided value
     * will be appended into the existing values.
     *
     * @param string $name The header name.
     * @param string|array $value The header value
     * @return static
     * @link http://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function withAddedHeader($name, $value)
    {
        $new = clone $this;
        $name = $this->normalizeHeaderName($name);
        $existing = [];
        if (isset($new->_environment[$name])) {
            $existing = (array)$new->_environment[$name];
        }
        $existing = array_merge($existing, (array)$value);
        $new->_environment[$name] = $existing;

        return $new;
    }

    /**
     * Get a modified request without a provided header.
     *
     * @param string $name The header name to remove.
     * @return static
     * @link http://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function withoutHeader($name)
    {
        $new = clone $this;
        $name = $this->normalizeHeaderName($name);
        unset($new->_environment[$name]);

        return $new;
    }

    /**
     * Get the HTTP method used for this request.
     *
     * @return string The name of the HTTP method used.
     * @deprecated 3.4.0 This method will be removed in 4.0.0. Use getMethod() instead.
     */
    public function method()
    {
        deprecationWarning(
            'ServerRequest::method() is deprecated. ' .
            'This method will be removed in 4.0.0. Use getMethod() instead.'
        );

        return $this->getEnv('REQUEST_METHOD');
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
     * @link http://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function getMethod()
    {
        return $this->getEnv('REQUEST_METHOD');
    }

    /**
     * Update the request method and get a new instance.
     *
     * @param string $method The HTTP method to use.
     * @return static A new instance with the updated method.
     * @link http://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function withMethod($method)
    {
        $new = clone $this;

        if (!is_string($method) ||
            !preg_match('/^[!#$%&\'*+.^_`\|~0-9a-z-]+$/i', $method)
        ) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }
        $new->_environment['REQUEST_METHOD'] = $method;

        return $new;
    }

    /**
     * Get all the server environment parameters.
     *
     * Read all of the 'environment' or 'server' data that was
     * used to create this request.
     *
     * @return array
     * @link http://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function getServerParams()
    {
        return $this->_environment;
    }

    /**
     * Get all the query parameters in accordance to the PSR-7 specifications. To read specific query values
     * use the alternative getQuery() method.
     *
     * @return array
     * @link http://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function getQueryParams()
    {
        return $this->query;
    }

    /**
     * Update the query string data and get a new instance.
     *
     * @param array $query The query string data to use
     * @return static A new instance with the updated query string data.
     * @link http://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function withQueryParams(array $query)
    {
        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    /**
     * Get the host that the request was handled on.
     *
     * @return string
     */
    public function host()
    {
        if ($this->trustProxy && $this->getEnv('HTTP_X_FORWARDED_HOST')) {
            return $this->getEnv('HTTP_X_FORWARDED_HOST');
        }

        return $this->getEnv('HTTP_HOST');
    }

    /**
     * Get the port the request was handled on.
     *
     * @return string
     */
    public function port()
    {
        if ($this->trustProxy && $this->getEnv('HTTP_X_FORWARDED_PORT')) {
            return $this->getEnv('HTTP_X_FORWARDED_PORT');
        }

        return $this->getEnv('SERVER_PORT');
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
        if ($this->trustProxy && $this->getEnv('HTTP_X_FORWARDED_PROTO')) {
            return $this->getEnv('HTTP_X_FORWARDED_PROTO');
        }

        return $this->getEnv('HTTPS') ? 'https' : 'http';
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
     * Generally you want to use Cake\Http\ServerRequest::accept() to get a simple list
     * of the accepted content types.
     *
     * @return array An array of prefValue => [content/types]
     */
    public function parseAccept()
    {
        return $this->_parseAcceptWithQualifier($this->getHeaderLine('Accept'));
    }

    /**
     * Get the languages accepted by the client, or check if a specific language is accepted.
     *
     * Get the list of accepted languages:
     *
     * ``` \Cake\Http\ServerRequest::acceptLanguage(); ```
     *
     * Check if a specific language is accepted:
     *
     * ``` \Cake\Http\ServerRequest::acceptLanguage('es-es'); ```
     *
     * @param string|null $language The language to test.
     * @return array|bool If a $language is provided, a boolean. Otherwise the array of accepted languages.
     */
    public function acceptLanguage($language = null)
    {
        $raw = $this->_parseAcceptWithQualifier($this->getHeaderLine('Accept-Language'));
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
        $headers = explode(',', $header);
        foreach (array_filter($headers) as $value) {
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
     * Provides a read accessor for `$this->query`.
     * Allows you to use a `Hash::get()` compatible syntax for reading post data.
     *
     * @param string|null $name Query string variable name or null to read all.
     * @return string|array|null The value being read
     * @deprecated 3.4.0 Use getQuery() or the PSR-7 getQueryParams() and withQueryParams() methods instead.
     */
    public function query($name = null)
    {
        deprecationWarning(
            'ServerRequest::query() is deprecated. ' .
            'Use getQuery() or the PSR-7 getQueryParams() and withQueryParams() methods instead.'
        );

        if ($name === null) {
            return $this->query;
        }

        return $this->getQuery($name);
    }

    /**
     * Read a specific query value or dotted path.
     *
     * Developers are encouraged to use getQueryParams() when possible as it is PSR-7 compliant, and this method
     * is not.
     *
     * ### PSR-7 Alternative
     *
     * ```
     * $value = Hash::get($request->getQueryParams(), 'Post.id', null);
     * ```
     *
     * @param string|null $name The name or dotted path to the query param or null to read all.
     * @param mixed $default The default value if the named parameter is not set, and $name is not null.
     * @return null|string|array Query data.
     * @see ServerRequest::getQueryParams()
     */
    public function getQuery($name = null, $default = null)
    {
        if ($name === null) {
            return $this->query;
        }

        return Hash::get($this->query, $name, $default);
    }

    /**
     * Provides a read/write accessor for `$this->data`.
     * Allows you to use a `Hash::get()` compatible syntax for reading post data.
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
     * @param mixed ...$args The data to set (deprecated)
     * @return mixed|$this Either the value being read, or this so you can chain consecutive writes.
     * @deprecated 3.4.0 Use withData() and getData() or getParsedBody() instead.
     */
    public function data($name = null, ...$args)
    {
        deprecationWarning(
            'ServerRequest::data() is deprecated. ' .
            'Use withData() and getData() or getParsedBody() instead.'
        );

        if (count($args) === 1) {
            $this->data = Hash::insert($this->data, $name, $args[0]);

            return $this;
        }
        if ($name !== null) {
            return Hash::get($this->data, $name);
        }

        return $this->data;
    }

    /**
     * Provides a safe accessor for request data. Allows
     * you to use Hash::get() compatible paths.
     *
     * ### Reading values.
     *
     * ```
     * // get all data
     * $request->getData();
     *
     * // Read a specific field.
     * $request->getData('Post.title');
     *
     * // With a default value.
     * $request->getData('Post.not there', 'default value');
     * ```
     *
     * When reading values you will get `null` for keys/values that do not exist.
     *
     * @param string|null $name Dot separated name of the value to read. Or null to read all data.
     * @param mixed $default The default data.
     * @return null|string|array The value being read.
     */
    public function getData($name = null, $default = null)
    {
        if ($name === null) {
            return $this->data;
        }
        if (!is_array($this->data) && $name) {
            return $default;
        }

        return Hash::get($this->data, $name, $default);
    }

    /**
     * Safely access the values in $this->params.
     *
     * @param string $name The name of the parameter to get.
     * @param mixed ...$args Value to set (deprecated).
     * @return mixed|$this The value of the provided parameter. Will
     *   return false if the parameter doesn't exist or is falsey.
     * @deprecated 3.4.0 Use getParam() and withParam() instead.
     */
    public function param($name, ...$args)
    {
        deprecationWarning(
            'ServerRequest::param() is deprecated. ' .
            'Use getParam() and withParam() instead.'
        );

        if (count($args) === 1) {
            $this->params = Hash::insert($this->params, $name, $args[0]);

            return $this;
        }

        return $this->getParam($name);
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
     * @param array ...$args The additional arguments
     * @return string The decoded/processed request data.
     */
    public function input($callback = null, ...$args)
    {
        $this->stream->rewind();
        $input = $this->stream->getContents();
        if ($callback) {
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
     * @deprecated 3.4.0 Use getCookie() instead.
     */
    public function cookie($key)
    {
        deprecationWarning(
            'ServerRequest::cookie() is deprecated. ' .
            'Use getCookie() instead.'
        );

        if (isset($this->cookies[$key])) {
            return $this->cookies[$key];
        }

        return null;
    }

    /**
     * Read cookie data from the request's cookie data.
     *
     * @param string $key The key or dotted path you want to read.
     * @param string $default The default value if the cookie is not set.
     * @return null|array|string Either the cookie value, or null if the value doesn't exist.
     */
    public function getCookie($key, $default = null)
    {
        return Hash::get($this->cookies, $key, $default);
    }

    /**
     * Get a cookie collection based on the request's cookies
     *
     * The CookieCollection lets you interact with request cookies using
     * `\Cake\Http\Cookie\Cookie` objects and can make converting request cookies
     * into response cookies easier.
     *
     * This method will create a new cookie collection each time it is called.
     * This is an optimization that allows fewer objects to be allocated until
     * the more complex CookieCollection is needed. In general you should prefer
     * `getCookie()` and `getCookieParams()` over this method. Using a CookieCollection
     * is ideal if your cookies contain complex JSON encoded data.
     *
     * @return \Cake\Http\Cookie\CookieCollection
     */
    public function getCookieCollection()
    {
        return CookieCollection::createFromServerRequest($this);
    }

    /**
     * Replace the cookies in the request with those contained in
     * the provided CookieCollection.
     *
     * @param \Cake\Http\Cookie\CookieCollection $cookies The cookie collection
     * @return static
     */
    public function withCookieCollection(CookieCollection $cookies)
    {
        $new = clone $this;
        $values = [];
        foreach ($cookies as $cookie) {
            $values[$cookie->getName()] = $cookie->getValue();
        }
        $new->cookies = $values;

        return $new;
    }

    /**
     * Get all the cookie data from the request.
     *
     * @return array An array of cookie data.
     */
    public function getCookieParams()
    {
        return $this->cookies;
    }

    /**
     * Replace the cookies and get a new request instance.
     *
     * @param array $cookies The new cookie data to use.
     * @return static
     */
    public function withCookieParams(array $cookies)
    {
        $new = clone $this;
        $new->cookies = $cookies;

        return $new;
    }

    /**
     * Get the parsed request body data.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this will be the
     * post data. For other content types, it may be the deserialized request
     * body.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     */
    public function getParsedBody()
    {
        return $this->data;
    }

    /**
     * Update the parsed body and get a new instance.
     *
     * @param null|array|object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return static
     */
    public function withParsedBody($data)
    {
        $new = clone $this;
        $new->data = $data;

        return $new;
    }

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        if ($this->protocol) {
            return $this->protocol;
        }

        // Lazily populate this data as it is generally not used.
        preg_match('/^HTTP\/([\d.]+)$/', $this->getEnv('SERVER_PROTOCOL'), $match);
        $protocol = '1.1';
        if (isset($match[1])) {
            $protocol = $match[1];
        }
        $this->protocol = $protocol;

        return $this->protocol;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * @param string $version HTTP protocol version
     * @return static
     */
    public function withProtocolVersion($version)
    {
        if (!preg_match('/^(1\.[01]|2)$/', $version)) {
            throw new InvalidArgumentException("Unsupported protocol version '{$version}' provided");
        }
        $new = clone $this;
        $new->protocol = $version;

        return $new;
    }

    /**
     * Get a value from the request's environment data.
     * Fallback to using env() if the key is not set in the $environment property.
     *
     * @param string $key The key you want to read from.
     * @param string|null $default Default value when trying to retrieve an environment
     *   variable's value that does not exist.
     * @return string|null Either the environment value, or null if the value doesn't exist.
     */
    public function getEnv($key, $default = null)
    {
        $key = strtoupper($key);
        if (!array_key_exists($key, $this->_environment)) {
            $this->_environment[$key] = env($key);
        }

        return $this->_environment[$key] !== null ? $this->_environment[$key] : $default;
    }

    /**
     * Update the request with a new environment data element.
     *
     * Returns an updated request object. This method returns
     * a *new* request object and does not mutate the request in-place.
     *
     * @param string $key The key you want to write to.
     * @param string $value Value to set
     * @return static
     */
    public function withEnv($key, $value)
    {
        $new = clone $this;
        $new->_environment[$key] = $value;
        $new->clearDetectorCache();

        return $new;
    }

    /**
     * Get/Set value from the request's environment data.
     * Fallback to using env() if key not set in $environment property.
     *
     * @deprecated 3.5.0 Use getEnv()/withEnv() instead.
     * @param string $key The key you want to read/write from/to.
     * @param string|null $value Value to set. Default null.
     * @param string|null $default Default value when trying to retrieve an environment
     *   variable's value that does not exist. The value parameter must be null.
     * @return $this|string|null This instance if used as setter,
     *   if used as getter either the environment value, or null if the value doesn't exist.
     */
    public function env($key, $value = null, $default = null)
    {
        deprecationWarning(
            'ServerRequest::env() is deprecated. ' .
            'Use getEnv()/withEnv() instead.'
        );

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
     * @throws \Cake\Http\Exception\MethodNotAllowedException
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
            $fh = fopen('php://input', 'rb');
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
     * @deprecated 3.4.0 This method will be removed in 4.0.0. Use withBody() instead.
     */
    public function setInput($input)
    {
        deprecationWarning(
            'This method will be removed in 4.0.0.' .
            'Use withBody() instead.'
        );

        $stream = new Stream('php://memory', 'rw');
        $stream->write($input);
        $stream->rewind();
        $this->stream = $stream;
    }

    /**
     * Update the request with a new request data element.
     *
     * Returns an updated request object. This method returns
     * a *new* request object and does not mutate the request in-place.
     *
     * Use `withParsedBody()` if you need to replace the all request data.
     *
     * @param string $name The dot separated path to insert $value at.
     * @param mixed $value The value to insert into the request data.
     * @return static
     */
    public function withData($name, $value)
    {
        $copy = clone $this;
        $copy->data = Hash::insert($copy->data, $name, $value);

        return $copy;
    }

    /**
     * Update the request removing a data element.
     *
     * Returns an updated request object. This method returns
     * a *new* request object and does not mutate the request in-place.
     *
     * @param string $name The dot separated path to remove.
     * @return static
     */
    public function withoutData($name)
    {
        $copy = clone $this;
        $copy->data = Hash::remove($copy->data, $name);

        return $copy;
    }

    /**
     * Update the request with a new routing parameter
     *
     * Returns an updated request object. This method returns
     * a *new* request object and does not mutate the request in-place.
     *
     * @param string $name The dot separated path to insert $value at.
     * @param mixed $value The value to insert into the the request parameters.
     * @return static
     */
    public function withParam($name, $value)
    {
        $copy = clone $this;
        $copy->params = Hash::insert($copy->params, $name, $value);

        return $copy;
    }

    /**
     * Safely access the values in $this->params.
     *
     * @param string $name The name or dotted path to parameter.
     * @param mixed $default The default value if `$name` is not set. Default `false`.
     * @return mixed
     */
    public function getParam($name, $default = false)
    {
        return Hash::get($this->params, $name, $default);
    }

    /**
     * Return an instance with the specified request attribute.
     *
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return static
     */
    public function withAttribute($name, $value)
    {
        $new = clone $this;
        if (in_array($name, $this->emulatedAttributes, true)) {
            $new->{$name} = $value;
        } else {
            $new->attributes[$name] = $value;
        }

        return $new;
    }

    /**
     * Return an instance without the specified request attribute.
     *
     * @param string $name The attribute name.
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withoutAttribute($name)
    {
        $new = clone $this;
        if (in_array($name, $this->emulatedAttributes, true)) {
            throw new InvalidArgumentException(
                "You cannot unset '$name'. It is a required CakePHP attribute."
            );
        }
        unset($new->attributes[$name]);

        return $new;
    }

    /**
     * Read an attribute from the request, or get the default
     *
     * @param string $name The attribute name.
     * @param mixed|null $default The default value if the attribute has not been set.
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        if (in_array($name, $this->emulatedAttributes, true)) {
            return $this->{$name};
        }
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return $default;
    }

    /**
     * Get all the attributes in the request.
     *
     * This will include the params, webroot, base, and here attributes that CakePHP
     * provides.
     *
     * @return array
     */
    public function getAttributes()
    {
        $emulated = [
            'params' => $this->params,
            'webroot' => $this->webroot,
            'base' => $this->base,
            'here' => $this->here
        ];

        return $this->attributes + $emulated;
    }

    /**
     * Get the uploaded file from a dotted path.
     *
     * @param string $path The dot separated path to the file you want.
     * @return null|\Psr\Http\Message\UploadedFileInterface
     */
    public function getUploadedFile($path)
    {
        $file = Hash::get($this->uploadedFiles, $path);
        if (!$file instanceof UploadedFile) {
            return null;
        }

        return $file;
    }

    /**
     * Get the array of uploaded files from the request.
     *
     * @return array
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * Update the request replacing the files, and creating a new instance.
     *
     * @param array $files An array of uploaded file objects.
     * @return static
     * @throws \InvalidArgumentException when $files contains an invalid object.
     */
    public function withUploadedFiles(array $files)
    {
        $this->validateUploadedFiles($files, '');
        $new = clone $this;
        $new->uploadedFiles = $files;

        return $new;
    }

    /**
     * Recursively validate uploaded file data.
     *
     * @param array $uploadedFiles The new files array to validate.
     * @param string $path The path thus far.
     * @return void
     * @throws \InvalidArgumentException If any leaf elements are not valid files.
     */
    protected function validateUploadedFiles(array $uploadedFiles, $path)
    {
        foreach ($uploadedFiles as $key => $file) {
            if (is_array($file)) {
                $this->validateUploadedFiles($file, $key . '.');
                continue;
            }

            if (!$file instanceof UploadedFileInterface) {
                throw new InvalidArgumentException("Invalid file at '{$path}{$key}'");
            }
        }
    }

    /**
     * Gets the body of the message.
     *
     * @return \Psr\Http\Message\StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        return $this->stream;
    }

    /**
     * Return an instance with the specified message body.
     *
     * @param \Psr\Http\Message\StreamInterface $body The new request body
     * @return static
     */
    public function withBody(StreamInterface $body)
    {
        $new = clone $this;
        $new->stream = $body;

        return $new;
    }

    /**
     * Retrieves the URI instance.
     *
     * @return \Psr\Http\Message\UriInterface Returns a UriInterface instance
     *   representing the URI of the request.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Return an instance with the specified uri
     *
     * *Warning* Replacing the Uri will not update the `base`, `webroot`,
     * and `url` attributes.
     *
     * @param \Psr\Http\Message\UriInterface $uri The new request uri
     * @param bool $preserveHost Whether or not the host should be retained.
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $new = clone $this;
        $new->uri = $uri;

        if ($preserveHost && $this->hasHeader('Host')) {
            return $new;
        }

        $host = $uri->getHost();
        if (!$host) {
            return $new;
        }
        if ($uri->getPort()) {
            $host .= ':' . $uri->getPort();
        }
        $new->_environment['HTTP_HOST'] = $host;

        return $new;
    }

    /**
     * Create a new instance with a specific request-target.
     *
     * You can use this method to overwrite the request target that is
     * inferred from the request's Uri. This also lets you change the request
     * target's form to an absolute-form, authority-form or asterisk-form
     *
     * @link https://tools.ietf.org/html/rfc7230#section-2.7 (for the various
     *   request-target forms allowed in request messages)
     * @param string $target The request target.
     * @return static
     */
    public function withRequestTarget($target)
    {
        $new = clone $this;
        $new->requestTarget = $target;

        return $new;
    }

    /**
     * Retrieves the request's target.
     *
     * Retrieves the message's request-target either as it was requested,
     * or as set with `withRequestTarget()`. By default this will return the
     * application relative path without base directory, and the query string
     * defined in the SERVER environment.
     *
     * @return string
     */
    public function getRequestTarget()
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        if (empty($target)) {
            $target = '/';
        }

        return $target;
    }

    /**
     * Get the path of current request.
     *
     * @return string
     * @since 3.6.1
     */
    public function getPath()
    {
        if ($this->requestTarget === null) {
            return $this->uri->getPath();
        }

        list($path) = explode('?', $this->requestTarget);

        return $path;
    }

    /**
     * Array access read implementation
     *
     * @param string $name Name of the key being accessed.
     * @return mixed
     * @deprecated 3.4.0 The ArrayAccess methods will be removed in 4.0.0. Use getParam(), getData() and getQuery() instead.
     */
    public function offsetGet($name)
    {
        deprecationWarning(
            'The ArrayAccess methods will be removed in 4.0.0.' .
            'Use getParam(), getData() and getQuery() instead.'
        );

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
     * @deprecated 3.4.0 The ArrayAccess methods will be removed in 4.0.0. Use withParam() instead.
     */
    public function offsetSet($name, $value)
    {
        deprecationWarning(
            'The ArrayAccess methods will be removed in 4.0.0.' .
            'Use withParam() instead.'
        );

        $this->params[$name] = $value;
    }

    /**
     * Array access isset() implementation
     *
     * @param string $name thing to check.
     * @return bool
     * @deprecated 3.4.0 The ArrayAccess methods will be removed in 4.0.0. Use getParam() instead.
     */
    public function offsetExists($name)
    {
        deprecationWarning(
            'The ArrayAccess methods will be removed in 4.0.0.' .
            'Use getParam() instead.'
        );

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
     * @deprecated 3.4.0 The ArrayAccess methods will be removed in 4.0.0. Use withParam() instead.
     */
    public function offsetUnset($name)
    {
        deprecationWarning(
            'The ArrayAccess methods will be removed in 4.0.0.' .
            'Use withParam() instead.'
        );

        unset($this->params[$name]);
    }
}

// @deprecated 3.4.0 Add backwards compat alias.
class_alias('Cake\Http\ServerRequest', 'Cake\Network\Request');
