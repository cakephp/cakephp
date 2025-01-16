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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use BadMethodCallException;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Http\Cookie\CookieCollection;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Utility\Hash;
use Closure;
use InvalidArgumentException;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use function Cake\Core\env;

/**
 * A class that helps wrap Request information and particulars about a single request.
 * Provides methods commonly used to introspect on the request headers and request body.
 */
class ServerRequest implements ServerRequestInterface
{
    /**
     * Array of parameters parsed from the URL.
     *
     * @var array
     */
    protected array $params = [
        'plugin' => null,
        'controller' => null,
        'action' => null,
        '_ext' => null,
        'pass' => [],
    ];

    /**
     * Array of POST data. Will contain form data as well as uploaded files.
     * In PUT/PATCH/DELETE requests this property will contain the form-urlencoded
     * data.
     *
     * @var object|array|null
     */
    protected object|array|null $data = [];

    /**
     * Array of query string arguments
     *
     * @var array
     */
    protected array $query = [];

    /**
     * Array of cookie data.
     *
     * @var array<string, mixed>
     */
    protected array $cookies = [];

    /**
     * Array of environment data.
     *
     * @var array<string, mixed>
     */
    protected array $_environment = [];

    /**
     * Base URL path.
     *
     * @var string
     */
    protected string $base;

    /**
     * webroot path segment for the request.
     *
     * @var string
     */
    protected string $webroot = '/';

    /**
     * Whether to trust HTTP_X headers set by most load balancers.
     * Only set to true if your application runs behind load balancers/proxies
     * that you control.
     *
     * @var bool
     */
    public bool $trustProxy = false;

    /**
     * Trusted proxies list
     *
     * @var array<string>
     */
    protected array $trustedProxies = [];

    /**
     * The built in detectors used with `is()` can be modified with `addDetector()`.
     *
     * There are several ways to specify a detector, see \Cake\Http\ServerRequest::addDetector() for the
     * various formats and ways to define detectors.
     *
     * @var array<\Closure|array>
     */
    protected static array $_detectors = [
        'get' => ['env' => 'REQUEST_METHOD', 'value' => 'GET'],
        'post' => ['env' => 'REQUEST_METHOD', 'value' => 'POST'],
        'put' => ['env' => 'REQUEST_METHOD', 'value' => 'PUT'],
        'patch' => ['env' => 'REQUEST_METHOD', 'value' => 'PATCH'],
        'delete' => ['env' => 'REQUEST_METHOD', 'value' => 'DELETE'],
        'head' => ['env' => 'REQUEST_METHOD', 'value' => 'HEAD'],
        'options' => ['env' => 'REQUEST_METHOD', 'value' => 'OPTIONS'],
        'https' => ['env' => 'HTTPS', 'options' => [1, 'on']],
        'ajax' => ['env' => 'HTTP_X_REQUESTED_WITH', 'value' => 'XMLHttpRequest'],
        'json' => ['accept' => ['application/json'], 'param' => '_ext', 'value' => 'json'],
        'xml' => [
            'accept' => ['application/xml', 'text/xml'],
            'exclude' => ['text/html'],
            'param' => '_ext',
            'value' => 'xml',
        ],
    ];

    /**
     * Instance cache for results of is(something) calls
     *
     * @var array<string, bool>
     */
    protected array $_detectorCache = [];

    /**
     * Request body stream. Contains php://input unless `input` constructor option is used.
     *
     * @var \Psr\Http\Message\StreamInterface
     */
    protected StreamInterface $stream;

    /**
     * Uri instance
     *
     * @var \Psr\Http\Message\UriInterface
     */
    protected UriInterface $uri;

    /**
     * Instance of a Session object relative to this request
     *
     * @var \Cake\Http\Session
     */
    protected Session $session;

    /**
     * Instance of a FlashMessage object relative to this request
     *
     * @var \Cake\Http\FlashMessage
     */
    protected FlashMessage $flash;

    /**
     * Store the additional attributes attached to the request.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * A list of properties that emulated by the PSR7 attribute methods.
     *
     * @var array<string>
     */
    protected array $emulatedAttributes = ['session', 'flash', 'webroot', 'base', 'params', 'here'];

    /**
     * Array of Psr\Http\Message\UploadedFileInterface objects.
     *
     * @var array
     */
    protected array $uploadedFiles = [];

    /**
     * The HTTP protocol version used.
     *
     * @var string|null
     */
    protected ?string $protocol = null;

    /**
     * The request target if overridden
     *
     * @var string|null
     */
    protected ?string $requestTarget = null;

    /**
     * Create a new request object.
     *
     * You can supply the data as either an array or as a string. If you use
     * a string you can only supply the URL for the request. Using an array will
     * let you provide the following keys:
     *
     * - `post` POST data or non query string data
     * - `query` Additional data from the query string.
     * - `files` Uploaded files in a normalized structure, with each leaf an instance of UploadedFileInterface.
     * - `cookies` Cookies for this request.
     * - `environment` $_SERVER and $_ENV data.
     * - `url` The URL without the base path for the request.
     * - `uri` The PSR7 UriInterface object. If null, one will be created from `url` or `environment`.
     * - `base` The base URL for the request.
     * - `webroot` The webroot directory for the request.
     * - `input` The data that would come from php://input this is useful for simulating
     *   requests with put, patch or delete data.
     * - `session` An instance of a Session object
     *
     * @param array<string, mixed> $config An array of request data to create a request with.
     */
    public function __construct(array $config = [])
    {
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
     * @param array<string, mixed> $config The config data to use.
     * @return void
     */
    protected function _setConfig(array $config): void
    {
        if (empty($config['session'])) {
            $config['session'] = new Session([
                'cookiePath' => $config['base'],
            ]);
        }

        if (empty($config['environment']['REQUEST_METHOD'])) {
            $config['environment']['REQUEST_METHOD'] = 'GET';
        }

        $this->cookies = $config['cookies'];

        if (isset($config['uri'])) {
            if (!$config['uri'] instanceof UriInterface) {
                throw new CakeException('The `uri` key must be an instance of ' . UriInterface::class);
            }
            $uri = $config['uri'];
        } else {
            if ($config['url'] !== '') {
                $config = $this->processUrlOption($config);
            }
            ['uri' => $uri] = UriFactory::marshalUriAndBaseFromSapi($config['environment']);
        }

        $this->_environment = $config['environment'];

        $this->uri = $uri;
        $this->base = $config['base'];
        $this->webroot = $config['webroot'];

        if (isset($config['input'])) {
            $stream = new Stream('php://memory', 'rw');
            $stream->write($config['input']);
            $stream->rewind();
        } else {
            $stream = new Stream('php://input');
        }
        $this->stream = $stream;

        $post = $config['post'];
        if (!(is_array($post) || is_object($post) || $post === null)) {
            throw new InvalidArgumentException(sprintf(
                '`post` key must be an array, object or null.'
                . ' Got `%s` instead.',
                get_debug_type($post),
            ));
        }
        $this->data = $post;
        $this->uploadedFiles = $config['files'];
        $this->query = $config['query'];
        $this->params = $config['params'];
        $this->session = $config['session'];
        $this->flash = new FlashMessage($this->session);
    }

    /**
     * Set environment vars based on `url` option to facilitate UriInterface instance generation.
     *
     * `query` option is also updated based on URL's querystring.
     *
     * @param array<string, mixed> $config Config array.
     * @return array<string, mixed> Update config.
     */
    protected function processUrlOption(array $config): array
    {
        if (!str_starts_with($config['url'], '/')) {
            $config['url'] = '/' . $config['url'];
        }

        if (str_contains($config['url'], '?')) {
            [$config['url'], $config['environment']['QUERY_STRING']] = explode('?', $config['url']);

            parse_str($config['environment']['QUERY_STRING'], $queryArgs);
            $config['query'] += $queryArgs;
        }

        $config['environment']['REQUEST_URI'] = $config['url'];

        return $config;
    }

    /**
     * Get the content type used in this request.
     *
     * @return string|null
     */
    public function contentType(): ?string
    {
        return $this->getEnv('CONTENT_TYPE') ?: $this->getEnv('HTTP_CONTENT_TYPE');
    }

    /**
     * Returns the instance of the Session object for this request
     *
     * @return \Cake\Http\Session
     */
    public function getSession(): Session
    {
        return $this->session;
    }

    /**
     * Returns the instance of the FlashMessage object for this request
     *
     * @return \Cake\Http\FlashMessage
     */
    public function getFlash(): FlashMessage
    {
        return $this->flash;
    }

    /**
     * Get the IP the client is using, or says they are using.
     *
     * @return string The client IP.
     */
    public function clientIp(): string
    {
        if ($this->trustProxy && $this->getEnv('HTTP_X_FORWARDED_FOR')) {
            $addresses = array_map('trim', explode(',', (string)$this->getEnv('HTTP_X_FORWARDED_FOR')));
            $trusted = $this->trustedProxies !== [];
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

        return trim((string)$ipaddr);
    }

    /**
     * register trusted proxies
     *
     * @param array<string> $proxies ips list of trusted proxies
     * @return void
     */
    public function setTrustedProxies(array $proxies): void
    {
        $this->trustedProxies = $proxies;
        $this->trustProxy = true;
        $this->uri = $this->uri->withScheme($this->scheme());
    }

    /**
     * Get trusted proxies
     *
     * @return array<string>
     */
    public function getTrustedProxies(): array
    {
        return $this->trustedProxies;
    }

    /**
     * Returns the referer that referred this request.
     *
     * @param bool $local Attempt to return a local address.
     *   Local addresses do not contain hostnames.
     * @return string|null The referring address for this request or null.
     */
    public function referer(bool $local = true): ?string
    {
        $ref = $this->getEnv('HTTP_REFERER');

        $base = Configure::read('App.fullBaseUrl') . $this->webroot;
        if (!$ref || !$base) {
            return null;
        }

        if ($local && str_starts_with($ref, $base)) {
            $ref = substr($ref, strlen($base));
            if ($ref === '' || str_starts_with($ref, '//')) {
                $ref = '/';
            }
            if (!str_starts_with($ref, '/')) {
                return '/' . $ref;
            }

            return $ref;
        }

        if ($local) {
            return null;
        }

        return $ref;
    }

    /**
     * Missing method handler, handles wrapping older style isAjax() type methods
     *
     * @param string $name The method called
     * @param array $params Array of parameters for the method call
     * @return bool
     * @throws \BadMethodCallException when an invalid method is called.
     */
    public function __call(string $name, array $params): bool
    {
        if (str_starts_with($name, 'is')) {
            $type = strtolower(substr($name, 2));

            array_unshift($params, $type);

            return $this->is(...$params);
        }
        throw new BadMethodCallException(sprintf('Method `%s()` does not exist.', $name));
    }

    /**
     * Check whether a Request is a certain type.
     *
     * Uses the built-in detection rules as well as additional rules
     * defined with {@link \Cake\Http\ServerRequest::addDetector()}. Any detector can be called
     * as `is($type)` or `is$Type()`.
     *
     * @param array<string>|string $type The type of request you want to check. If an array
     *   this method will return true if the request matches any type.
     * @param mixed ...$args List of arguments
     * @return bool Whether the request is the type you are checking.
     * @throws \InvalidArgumentException If no detector has been set for the provided type.
     */
    public function is(array|string $type, mixed ...$args): bool
    {
        if (is_array($type)) {
            foreach ($type as $_type) {
                if ($this->is($_type)) {
                    return true;
                }
            }

            return false;
        }

        $type = strtolower($type);
        if (!isset(static::$_detectors[$type])) {
            throw new InvalidArgumentException(sprintf('No detector set for type `%s`.', $type));
        }
        if ($args) {
            return $this->_is($type, $args);
        }

        return $this->_detectorCache[$type] ??= $this->_is($type, $args);
    }

    /**
     * Clears the instance detector cache, used by the is() function
     *
     * @return void
     */
    public function clearDetectorCache(): void
    {
        $this->_detectorCache = [];
    }

    /**
     * Worker for the public is() function
     *
     * @param string $type The type of request you want to check.
     * @param array $args Array of custom detector arguments.
     * @return bool Whether the request is the type you are checking.
     */
    protected function _is(string $type, array $args): bool
    {
        $detect = static::$_detectors[$type];
        if ($detect instanceof Closure) {
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
     * @return bool Whether the request is the type you are checking.
     */
    protected function _acceptHeaderDetector(array $detect): bool
    {
        $content = new ContentTypeNegotiation();
        $options = $detect['accept'];

        // Some detectors overlap with the default browser Accept header
        // For these types we use an exclude list to refine our content type
        // detection.
        $exclude = $detect['exclude'] ?? null;
        if ($exclude) {
            $options = array_merge($options, $exclude);
        }

        $accepted = $content->preferredType($this, $options);
        if ($accepted === null) {
            return false;
        }
        if ($exclude && in_array($accepted, $exclude, true)) {
            return false;
        }

        return true;
    }

    /**
     * Detects if a specific header is present.
     *
     * @param array $detect Detector options array.
     * @return bool Whether the request is the type you are checking.
     */
    protected function _headerDetector(array $detect): bool
    {
        foreach ($detect['header'] as $header => $value) {
            $header = $this->getEnv('http_' . $header);
            if ($header !== null) {
                if ($value instanceof Closure) {
                    return $value($header);
                }

                return $header === $value;
            }
        }

        return false;
    }

    /**
     * Detects if a specific request parameter is present.
     *
     * @param array $detect Detector options array.
     * @return bool Whether the request is the type you are checking.
     */
    protected function _paramDetector(array $detect): bool
    {
        $key = $detect['param'];
        if (isset($detect['value'])) {
            $value = $detect['value'];

            return isset($this->params[$key]) && $this->params[$key] == $value;
        }
        if (isset($detect['options'])) {
            return isset($this->params[$key]) && in_array($this->params[$key], $detect['options']);
        }

        return false;
    }

    /**
     * Detects if a specific environment variable is present.
     *
     * @param array $detect Detector options array.
     * @return bool Whether the request is the type you are checking.
     */
    protected function _environmentDetector(array $detect): bool
    {
        if (isset($detect['env'])) {
            if (isset($detect['value'])) {
                return $this->getEnv($detect['env']) == $detect['value'];
            }
            if (isset($detect['pattern'])) {
                return (bool)preg_match($detect['pattern'], (string)$this->getEnv($detect['env']));
            }
            if (isset($detect['options'])) {
                $pattern = '/' . implode('|', $detect['options']) . '/i';

                return (bool)preg_match($pattern, (string)$this->getEnv($detect['env']));
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
     * @param array<string> $types The types to check.
     * @return bool Success.
     * @see \Cake\Http\ServerRequest::is()
     */
    public function isAll(array $types): bool
    {
        foreach ($types as $type) {
            if (!$this->is($type)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add a new detector to the list of detectors that a request can use.
     * There are several different types of detectors that can be set.
     *
     * ### Callback comparison
     *
     * Callback detectors allow you to provide a closure to handle the check.
     * The closure will receive the request object as its only parameter.
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
     * addDetector('admin', ['param' => 'prefix', 'value' => 'admin']);
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
     * @param \Closure|array $detector A Closure or options array for the detector definition.
     * @return void
     */
    public static function addDetector(string $name, Closure|array $detector): void
    {
        $name = strtolower($name);
        if ($detector instanceof Closure) {
            static::$_detectors[$name] = $detector;

            return;
        }

        if (isset(static::$_detectors[$name], $detector['options'])) {
            /** @var array $data */
            $data = static::$_detectors[$name];
            $detector = Hash::merge($data, $detector);
        }
        static::$_detectors[$name] = $detector;
    }

    /**
     * Normalize a header name into the SERVER version.
     *
     * @param string $name The header name.
     * @return string The normalized header name.
     */
    protected function normalizeHeaderName(string $name): string
    {
        $name = str_replace('-', '_', strtoupper($name));
        if (!in_array($name, ['CONTENT_LENGTH', 'CONTENT_TYPE'], true)) {
            return 'HTTP_' . $name;
        }

        return $name;
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
     * @return array<string, array<string>> An associative array of headers and their values.
     * @link https://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function getHeaders(): array
    {
        $headers = [];
        foreach ($this->_environment as $key => $value) {
            $name = null;
            if (str_starts_with($key, 'HTTP_')) {
                $name = substr($key, 5);
            }
            if (str_starts_with($key, 'CONTENT_')) {
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
     * @return bool Whether the header is defined.
     * @link https://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function hasHeader(string $name): bool
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
     * @return array<string, string> An associative array of headers and their values.
     *   If the header doesn't exist, an empty array will be returned.
     * @link https://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function getHeader(string $name): array
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
     * @link https://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function getHeaderLine(string $name): string
    {
        $value = $this->getHeader($name);

        return implode(', ', $value);
    }

    /**
     * Get a modified request with the provided header.
     *
     * @param string $name The header name.
     * @param array|string $value The header value
     * @return static
     * @link https://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function withHeader(string $name, $value): static
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
     * @param array|string $value The header value
     * @return static
     * @link https://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function withAddedHeader(string $name, $value): static
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
     * @link https://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function withoutHeader(string $name): static
    {
        $new = clone $this;
        $name = $this->normalizeHeaderName($name);
        unset($new->_environment[$name]);

        return $new;
    }

    /**
     * Get the HTTP method used for this request.
     * There are a few ways to specify a method.
     *
     * - If your client supports it you can use native HTTP methods.
     * - You can set the X-Http-Method-Override header.
     * - You can submit an input with the name `_method`
     *
     * Any of these 3 approaches can be used to set the HTTP method used
     * by CakePHP internally, and will effect the result of this method.
     *
     * @return string The name of the HTTP method used.
     * @link https://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function getMethod(): string
    {
        return (string)$this->getEnv('REQUEST_METHOD');
    }

    /**
     * Update the request method and get a new instance.
     *
     * @param string $method The HTTP method to use.
     * @return static A new instance with the updated method.
     * @link https://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function withMethod(string $method): static
    {
        $new = clone $this;

        if (!preg_match('/^[!#$%&\'*+.^_`\|~0-9a-z-]+$/i', $method)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method `%s` provided.',
                $method,
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
     * @link https://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function getServerParams(): array
    {
        return $this->_environment;
    }

    /**
     * Get all the query parameters in accordance to the PSR-7 specifications. To read specific query values
     * use the alternative getQuery() method.
     *
     * @return array
     * @link https://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function getQueryParams(): array
    {
        return $this->query;
    }

    /**
     * Update the query string data and get a new instance.
     *
     * @param array $query The query string data to use
     * @return static A new instance with the updated query string data.
     * @link https://www.php-fig.org/psr/psr-7/ This method is part of the PSR-7 server request interface.
     */
    public function withQueryParams(array $query): static
    {
        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    /**
     * Get the host that the request was handled on.
     *
     * @return string|null
     */
    public function host(): ?string
    {
        if ($this->trustProxy && $this->getEnv('HTTP_X_FORWARDED_HOST')) {
            return $this->getEnv('HTTP_X_FORWARDED_HOST');
        }

        return $this->getEnv('HTTP_HOST');
    }

    /**
     * Get the port the request was handled on.
     *
     * @return string|null
     */
    public function port(): ?string
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
    public function scheme(): string
    {
        if ($this->trustProxy && $this->getEnv('HTTP_X_FORWARDED_PROTO')) {
            return (string)$this->getEnv('HTTP_X_FORWARDED_PROTO');
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
    public function domain(int $tldLength = 1): string
    {
        $host = $this->host();
        if (!$host) {
            return '';
        }

        $segments = explode('.', $host);
        $domain = array_slice($segments, -1 * ($tldLength + 1));

        return implode('.', $domain);
    }

    /**
     * Get the subdomains for a host.
     *
     * @param int $tldLength Number of segments your tld contains. For example: `example.com` contains 1 tld.
     *   While `example.co.uk` contains 2.
     * @return array<string> An array of subdomains.
     */
    public function subdomains(int $tldLength = 1): array
    {
        $host = $this->host();
        if (!$host) {
            return [];
        }

        $segments = explode('.', $host);

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
     * @return array<string>|bool Either an array of all the types the client accepts or a boolean if they accept the
     *   provided type.
     */
    public function accepts(?string $type = null): array|bool
    {
        $content = new ContentTypeNegotiation();
        if ($type) {
            return $content->preferredType($this, [$type]) !== null;
        }

        $accept = [];
        foreach ($content->parseAccept($this) as $types) {
            $accept = array_merge($accept, $types);
        }

        return $accept;
    }

    /**
     * Get the languages accepted by the client, or check if a specific language is accepted.
     *
     * Get the list of accepted languages:
     *
     * ```$request->acceptLanguage();```
     *
     * Check if a specific language is accepted:
     *
     * ```$request->acceptLanguage('es-es');```
     *
     * @param string|null $language The language to test.
     * @return array|bool If a $language is provided, a boolean. Otherwise, the array of accepted languages.
     */
    public function acceptLanguage(?string $language = null): array|bool
    {
        $content = new ContentTypeNegotiation();
        if ($language !== null) {
            return $content->acceptLanguage($this, $language);
        }

        return $content->acceptedLanguages($this);
    }

    /**
     * Read a specific query value or dotted path.
     *
     * Developers are encouraged to use getQueryParams() if they need the whole query array,
     * as it is PSR-7 compliant, and this method is not. Using Hash::get() you can also get single params.
     *
     * ### PSR-7 Alternative
     *
     * ```
     * $value = Hash::get($request->getQueryParams(), 'Post.id');
     * ```
     *
     * @param string|null $name The name or dotted path to the query param or null to read all.
     * @param mixed $default The default value if the named parameter is not set, and $name is not null.
     * @return mixed Query data.
     * @see ServerRequest::getQueryParams()
     */
    public function getQuery(?string $name = null, mixed $default = null): mixed
    {
        if ($name === null) {
            return $this->query;
        }

        return Hash::get($this->query, $name, $default);
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
     * Developers are encouraged to use getParsedBody() if they need the whole data array,
     * as it is PSR-7 compliant, and this method is not. Using Hash::get() you can also get single params.
     *
     * ### PSR-7 Alternative
     *
     * ```
     * $value = Hash::get($request->getParsedBody(), 'Post.id');
     * ```
     *
     * @param string|null $name Dot separated name of the value to read. Or null to read all data.
     * @param mixed $default The default data.
     * @return mixed The value being read.
     */
    public function getData(?string $name = null, mixed $default = null): mixed
    {
        if ($name === null) {
            return $this->data;
        }
        if (!is_array($this->data)) {
            return $default;
        }

        return Hash::get($this->data, $name, $default);
    }

    /**
     * Read cookie data from the request's cookie data.
     *
     * @param string $key The key or dotted path you want to read.
     * @param array|string|null $default The default value if the cookie is not set.
     * @return array|string|null Either the cookie value, or null if the value doesn't exist.
     */
    public function getCookie(string $key, array|string|null $default = null): array|string|null
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
    public function getCookieCollection(): CookieCollection
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
    public function withCookieCollection(CookieCollection $cookies): static
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
     * @return array<string, mixed> An array of cookie data.
     */
    public function getCookieParams(): array
    {
        return $this->cookies;
    }

    /**
     * Replace the cookies and get a new request instance.
     *
     * @param array $cookies The new cookie data to use.
     * @return static
     */
    public function withCookieParams(array $cookies): static
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
     * @return object|array|null The deserialized body parameters, if any.
     *     These will typically be an array.
     */
    public function getParsedBody(): object|array|null
    {
        return $this->data;
    }

    /**
     * Update the parsed body and get a new instance.
     *
     * @param object|array|null $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return static
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function withParsedBody($data): static
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
    public function getProtocolVersion(): string
    {
        if ($this->protocol) {
            return $this->protocol;
        }

        // Lazily populate this data as it is generally not used.
        preg_match('/^HTTP\/([\d.]+)$/', (string)$this->getEnv('SERVER_PROTOCOL'), $match);
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
    public function withProtocolVersion(string $version): static
    {
        if (!preg_match('/^(1\.[01]|2)$/', $version)) {
            throw new InvalidArgumentException(sprintf('Unsupported protocol version `%s` provided.', $version));
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
    public function getEnv(string $key, ?string $default = null): ?string
    {
        $key = strtoupper($key);
        if (!array_key_exists($key, $this->_environment)) {
            $this->_environment[$key] = env($key);
        }

        return $this->_environment[$key] !== null ? (string)$this->_environment[$key] : $default;
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
    public function withEnv(string $key, string $value): static
    {
        $new = clone $this;
        $new->_environment[$key] = $value;
        $new->clearDetectorCache();

        return $new;
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
     * @param array<string>|string $methods Allowed HTTP request methods.
     * @return true
     * @throws \Cake\Http\Exception\MethodNotAllowedException
     */
    public function allowMethod(array|string $methods): bool
    {
        $methods = (array)$methods;
        foreach ($methods as $method) {
            if ($this->is($method)) {
                return true;
            }
        }
        $allowed = strtoupper(implode(', ', $methods));
        $e = new MethodNotAllowedException();
        $e->setHeader('Allow', $allowed);
        throw $e;
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
    public function withData(string $name, mixed $value): static
    {
        $copy = clone $this;

        if (is_array($copy->data)) {
            $copy->data = Hash::insert($copy->data, $name, $value);
        }

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
    public function withoutData(string $name): static
    {
        $copy = clone $this;

        if (is_array($copy->data)) {
            $copy->data = Hash::remove($copy->data, $name);
        }

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
    public function withParam(string $name, mixed $value): static
    {
        $copy = clone $this;
        $copy->params = Hash::insert($copy->params, $name, $value);

        return $copy;
    }

    /**
     * Safely access the values in $this->params.
     *
     * @param string $name The name or dotted path to parameter.
     * @param mixed $default The default value if `$name` is not set. Default `null`.
     * @return mixed
     */
    public function getParam(string $name, mixed $default = null): mixed
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
    public function withAttribute(string $name, mixed $value): static
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
    public function withoutAttribute(string $name): static
    {
        $new = clone $this;
        if (in_array($name, $this->emulatedAttributes, true)) {
            throw new InvalidArgumentException(
                "You cannot unset '{$name}'. It is a required CakePHP attribute.",
            );
        }
        unset($new->attributes[$name]);

        return $new;
    }

    /**
     * Read an attribute from the request, or get the default
     *
     * @param string $name The attribute name.
     * @param mixed $default The default value if the attribute has not been set.
     * @return mixed
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        if (in_array($name, $this->emulatedAttributes, true)) {
            if ($name === 'here') {
                return $this->base . $this->uri->getPath();
            }

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
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        $emulated = [
            'params' => $this->params,
            'webroot' => $this->webroot,
            'base' => $this->base,
            'here' => $this->base . $this->uri->getPath(),
        ];

        return $this->attributes + $emulated;
    }

    /**
     * Get the uploaded file from a dotted path.
     *
     * @param string $path The dot separated path to the file you want.
     * @return \Psr\Http\Message\UploadedFileInterface|null
     */
    public function getUploadedFile(string $path): ?UploadedFileInterface
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
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * Update the request replacing the files, and creating a new instance.
     *
     * @param array $uploadedFiles An array of uploaded file objects.
     * @return static
     * @throws \InvalidArgumentException when $files contains an invalid object.
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        $this->validateUploadedFiles($uploadedFiles, '');
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;

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
    protected function validateUploadedFiles(array $uploadedFiles, string $path): void
    {
        foreach ($uploadedFiles as $key => $file) {
            if (is_array($file)) {
                $this->validateUploadedFiles($file, $key . '.');
                continue;
            }

            if (!$file instanceof UploadedFileInterface) {
                throw new InvalidArgumentException(sprintf('Invalid file at `%s%s`.', $path, $key));
            }
        }
    }

    /**
     * Gets the body of the message.
     *
     * @return \Psr\Http\Message\StreamInterface Returns the body as a stream.
     */
    public function getBody(): StreamInterface
    {
        return $this->stream;
    }

    /**
     * Return an instance with the specified message body.
     *
     * @param \Psr\Http\Message\StreamInterface $body The new request body
     * @return static
     */
    public function withBody(StreamInterface $body): static
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
    public function getUri(): UriInterface
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
     * @param bool $preserveHost Whether the host should be retained.
     * @return static
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): static
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
        $port = $uri->getPort();
        if ($port) {
            $host .= ':' . $port;
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
     * @param string $requestTarget The request target.
     * @return static
     */
    public function withRequestTarget(string $requestTarget): static
    {
        $new = clone $this;
        $new->requestTarget = $requestTarget;

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
    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        if (!$target) {
            return '/';
        }

        return $target;
    }

    /**
     * Get the path of current request.
     *
     * @return string
     * @since 3.6.1
     */
    public function getPath(): string
    {
        if ($this->requestTarget === null) {
            return $this->uri->getPath();
        }

        [$path] = explode('?', $this->requestTarget);

        return $path;
    }
}
