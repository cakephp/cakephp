<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use Cake\Core\App;
use Cake\Core\Exception\Exception;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\Client\Request;
use Cake\Http\Cookie\CookieCollection;
use Cake\Http\Cookie\CookieInterface;
use Cake\Utility\Hash;
use InvalidArgumentException;
use Zend\Diactoros\Uri;

/**
 * The end user interface for doing HTTP requests.
 *
 * ### Scoped clients
 *
 * If you're doing multiple requests to the same hostname it's often convenient
 * to use the constructor arguments to create a scoped client. This allows you
 * to keep your code DRY and not repeat hostnames, authentication, and other options.
 *
 * ### Doing requests
 *
 * Once you've created an instance of Client you can do requests
 * using several methods. Each corresponds to a different HTTP method.
 *
 * - get()
 * - post()
 * - put()
 * - delete()
 * - patch()
 *
 * ### Cookie management
 *
 * Client will maintain cookies from the responses done with
 * a client instance. These cookies will be automatically added
 * to future requests to matching hosts. Cookies will respect the
 * `Expires`, `Path` and `Domain` attributes. You can get the client's
 * CookieCollection using cookies()
 *
 * You can use the 'cookieJar' constructor option to provide a custom
 * cookie jar instance you've restored from cache/disk. By default
 * an empty instance of Cake\Http\Client\CookieCollection will be created.
 *
 * ### Sending request bodies
 *
 * By default any POST/PUT/PATCH/DELETE request with $data will
 * send their data as `application/x-www-form-urlencoded` unless
 * there are attached files. In that case `multipart/form-data`
 * will be used.
 *
 * When sending request bodies you can use the `type` option to
 * set the Content-Type for the request:
 *
 * ```
 * $http->get('/users', [], ['type' => 'json']);
 * ```
 *
 * The `type` option sets both the `Content-Type` and `Accept` header, to
 * the same mime type. When using `type` you can use either a full mime
 * type or an alias. If you need different types in the Accept and Content-Type
 * headers you should set them manually and not use `type`
 *
 * ### Using authentication
 *
 * By using the `auth` key you can use authentication. The type sub option
 * can be used to specify which authentication strategy you want to use.
 * CakePHP comes with a few built-in strategies:
 *
 * - Basic
 * - Digest
 * - Oauth
 *
 * ### Using proxies
 *
 * By using the `proxy` key you can set authentication credentials for
 * a proxy if you need to use one. The type sub option can be used to
 * specify which authentication strategy you want to use.
 * CakePHP comes with built-in support for basic authentication.
 *
 * @mixin \Cake\Core\InstanceConfigTrait
 */
class Client
{

    use InstanceConfigTrait;

    /**
     * Default configuration for the client.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'adapter' => 'Cake\Http\Client\Adapter\Stream',
        'host' => null,
        'port' => null,
        'scheme' => 'http',
        'timeout' => 30,
        'ssl_verify_peer' => true,
        'ssl_verify_peer_name' => true,
        'ssl_verify_depth' => 5,
        'ssl_verify_host' => true,
        'redirect' => false,
    ];

    /**
     * List of cookies from responses made with this client.
     *
     * Cookies are indexed by the cookie's domain or
     * request host name.
     *
     * @var \Cake\Http\Cookie\CookieCollection
     */
    protected $_cookies;

    /**
     * Adapter for sending requests. Defaults to
     * Cake\Http\Client\Adapter\Stream
     *
     * @var \Cake\Http\Client\Adapter\Stream
     */
    protected $_adapter;

    /**
     * Create a new HTTP Client.
     *
     * ### Config options
     *
     * You can set the following options when creating a client:
     *
     * - host - The hostname to do requests on.
     * - port - The port to use.
     * - scheme - The default scheme/protocol to use. Defaults to http.
     * - timeout - The timeout in seconds. Defaults to 30
     * - ssl_verify_peer - Whether or not SSL certificates should be validated.
     *   Defaults to true.
     * - ssl_verify_peer_name - Whether or not peer names should be validated.
     *   Defaults to true.
     * - ssl_verify_depth - The maximum certificate chain depth to traverse.
     *   Defaults to 5.
     * - ssl_verify_host - Verify that the certificate and hostname match.
     *   Defaults to true.
     * - redirect - Number of redirects to follow. Defaults to false.
     *
     * @param array $config Config options for scoped clients.
     */
    public function __construct($config = [])
    {
        $this->setConfig($config);

        $adapter = $this->_config['adapter'];
        $this->setConfig('adapter', null);
        if (is_string($adapter)) {
            $adapter = new $adapter();
        }
        $this->_adapter = $adapter;

        if (!empty($this->_config['cookieJar'])) {
            $this->_cookies = $this->_config['cookieJar'];
            $this->setConfig('cookieJar', null);
        } else {
            $this->_cookies = new CookieCollection();
        }
    }

    /**
     * Get the cookies stored in the Client.
     *
     * @return \Cake\Http\Client\CookieCollection
     */
    public function cookies()
    {
        return $this->_cookies;
    }

    /**
     * Adds a cookie to the Client collection.
     *
     * @param \Cake\Http\Cookie\CookieInterface $cookie Cookie object.
     * @return $this
     */
    public function addCookie(CookieInterface $cookie)
    {
        if (!$cookie->getDomain() || !$cookie->getPath()) {
            throw new InvalidArgumentException('Cookie must have a domain and a path set.');
        }
        $this->_cookies = $this->_cookies->add($cookie);

        return $this;
    }

    /**
     * Do a GET request.
     *
     * The $data argument supports a special `_content` key
     * for providing a request body in a GET request. This is
     * generally not used, but services like ElasticSearch use
     * this feature.
     *
     * @param string $url The url or path you want to request.
     * @param array $data The query data you want to send.
     * @param array $options Additional options for the request.
     * @return \Cake\Http\Client\Response
     */
    public function get($url, $data = [], array $options = [])
    {
        $options = $this->_mergeOptions($options);
        $body = null;
        if (isset($data['_content'])) {
            $body = $data['_content'];
            unset($data['_content']);
        }
        $url = $this->buildUrl($url, $data, $options);

        return $this->_doRequest(
            Request::METHOD_GET,
            $url,
            $body,
            $options
        );
    }

    /**
     * Do a POST request.
     *
     * @param string $url The url or path you want to request.
     * @param mixed $data The post data you want to send.
     * @param array $options Additional options for the request.
     * @return \Cake\Http\Client\Response
     */
    public function post($url, $data = [], array $options = [])
    {
        $options = $this->_mergeOptions($options);
        $url = $this->buildUrl($url, [], $options);

        return $this->_doRequest(Request::METHOD_POST, $url, $data, $options);
    }

    /**
     * Do a PUT request.
     *
     * @param string $url The url or path you want to request.
     * @param mixed $data The request data you want to send.
     * @param array $options Additional options for the request.
     * @return \Cake\Http\Client\Response
     */
    public function put($url, $data = [], array $options = [])
    {
        $options = $this->_mergeOptions($options);
        $url = $this->buildUrl($url, [], $options);

        return $this->_doRequest(Request::METHOD_PUT, $url, $data, $options);
    }

    /**
     * Do a PATCH request.
     *
     * @param string $url The url or path you want to request.
     * @param mixed $data The request data you want to send.
     * @param array $options Additional options for the request.
     * @return \Cake\Http\Client\Response
     */
    public function patch($url, $data = [], array $options = [])
    {
        $options = $this->_mergeOptions($options);
        $url = $this->buildUrl($url, [], $options);

        return $this->_doRequest(Request::METHOD_PATCH, $url, $data, $options);
    }

    /**
     * Do an OPTIONS request.
     *
     * @param string $url The url or path you want to request.
     * @param mixed $data The request data you want to send.
     * @param array $options Additional options for the request.
     * @return \Cake\Http\Client\Response
     */
    public function options($url, $data = [], array $options = [])
    {
        $options = $this->_mergeOptions($options);
        $url = $this->buildUrl($url, [], $options);

        return $this->_doRequest(Request::METHOD_OPTIONS, $url, $data, $options);
    }

    /**
     * Do a TRACE request.
     *
     * @param string $url The url or path you want to request.
     * @param mixed $data The request data you want to send.
     * @param array $options Additional options for the request.
     * @return \Cake\Http\Client\Response
     */
    public function trace($url, $data = [], array $options = [])
    {
        $options = $this->_mergeOptions($options);
        $url = $this->buildUrl($url, [], $options);

        return $this->_doRequest(Request::METHOD_TRACE, $url, $data, $options);
    }

    /**
     * Do a DELETE request.
     *
     * @param string $url The url or path you want to request.
     * @param mixed $data The request data you want to send.
     * @param array $options Additional options for the request.
     * @return \Cake\Http\Client\Response
     */
    public function delete($url, $data = [], array $options = [])
    {
        $options = $this->_mergeOptions($options);
        $url = $this->buildUrl($url, [], $options);

        return $this->_doRequest(Request::METHOD_DELETE, $url, $data, $options);
    }

    /**
     * Do a HEAD request.
     *
     * @param string $url The url or path you want to request.
     * @param array $data The query string data you want to send.
     * @param array $options Additional options for the request.
     * @return \Cake\Http\Client\Response
     */
    public function head($url, array $data = [], array $options = [])
    {
        $options = $this->_mergeOptions($options);
        $url = $this->buildUrl($url, $data, $options);

        return $this->_doRequest(Request::METHOD_HEAD, $url, '', $options);
    }

    /**
     * Helper method for doing non-GET requests.
     *
     * @param string $method HTTP method.
     * @param string $url URL to request.
     * @param mixed $data The request body.
     * @param array $options The options to use. Contains auth, proxy, etc.
     * @return \Cake\Http\Client\Response
     */
    protected function _doRequest($method, $url, $data, $options)
    {
        $request = $this->_createRequest(
            $method,
            $url,
            $data,
            $options
        );

        return $this->send($request, $options);
    }

    /**
     * Does a recursive merge of the parameter with the scope config.
     *
     * @param array $options Options to merge.
     * @return array Options merged with set config.
     */
    protected function _mergeOptions($options)
    {
        return Hash::merge($this->_config, $options);
    }

    /**
     * Send a request.
     *
     * Used internally by other methods, but can also be used to send
     * handcrafted Request objects.
     *
     * @param \Cake\Http\Client\Request $request The request to send.
     * @param array $options Additional options to use.
     * @return \Cake\Http\Client\Response
     */
    public function send(Request $request, $options = [])
    {
        $redirects = 0;
        if (isset($options['redirect'])) {
            $redirects = (int)$options['redirect'];
            unset($options['redirect']);
        }

        do {
            $response = $this->_sendRequest($request, $options);

            $handleRedirect = $response->isRedirect() && $redirects-- > 0;
            if ($handleRedirect) {
                $url = $request->getUri();
                $request = $this->_cookies->addToRequest($request, []);

                $location = $response->getHeaderLine('Location');
                $locationUrl = $this->buildUrl($location, [], [
                    'host' => $url->getHost(),
                    'port' => $url->getPort(),
                    'scheme' => $url->getScheme(),
                    'protocolRelative' => true
                ]);

                $request = $request->withUri(new Uri($locationUrl));
            }
        } while ($handleRedirect);

        return $response;
    }

    /**
     * Send a request without redirection.
     *
     * @param \Cake\Http\Client\Request $request The request to send.
     * @param array $options Additional options to use.
     * @return \Cake\Http\Client\Response
     */
    protected function _sendRequest(Request $request, $options)
    {
        $responses = $this->_adapter->send($request, $options);
        $url = $request->getUri();
        foreach ($responses as $response) {
            $this->_cookies = $this->_cookies->addFromResponse($response, $request);
        }

        return array_pop($responses);
    }

    /**
     * Generate a URL based on the scoped client options.
     *
     * @param string $url Either a full URL or just the path.
     * @param string|array $query The query data for the URL.
     * @param array $options The config options stored with Client::config()
     * @return string A complete url with scheme, port, host, and path.
     */
    public function buildUrl($url, $query = [], $options = [])
    {
        if (empty($options) && empty($query)) {
            return $url;
        }
        if ($query) {
            $q = (strpos($url, '?') === false) ? '?' : '&';
            $url .= $q;
            $url .= is_string($query) ? $query : http_build_query($query);
        }
        $defaults = [
            'host' => null,
            'port' => null,
            'scheme' => 'http',
            'protocolRelative' => false
        ];
        $options += $defaults;

        if ($options['protocolRelative'] && preg_match('#^//#', $url)) {
            $url = $options['scheme'] . ':' . $url;
        }
        if (preg_match('#^https?://#', $url)) {
            return $url;
        }

        $defaultPorts = [
            'http' => 80,
            'https' => 443
        ];
        $out = $options['scheme'] . '://' . $options['host'];
        if ($options['port'] && $options['port'] != $defaultPorts[$options['scheme']]) {
            $out .= ':' . $options['port'];
        }
        $out .= '/' . ltrim($url, '/');

        return $out;
    }

    /**
     * Creates a new request object based on the parameters.
     *
     * @param string $method HTTP method name.
     * @param string $url The url including query string.
     * @param mixed $data The request body.
     * @param array $options The options to use. Contains auth, proxy, etc.
     * @return \Cake\Http\Client\Request
     */
    protected function _createRequest($method, $url, $data, $options)
    {
        $headers = isset($options['headers']) ? (array)$options['headers'] : [];
        if (isset($options['type'])) {
            $headers = array_merge($headers, $this->_typeHeaders($options['type']));
        }
        if (is_string($data) && !isset($headers['Content-Type']) && !isset($headers['content-type'])) {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        $request = new Request($url, $method, $headers, $data);
        $cookies = isset($options['cookies']) ? $options['cookies'] : [];
        /** @var \Cake\Http\Client\Request $request */
        $request = $this->_cookies->addToRequest($request, $cookies);
        if (isset($options['auth'])) {
            $request = $this->_addAuthentication($request, $options);
        }
        if (isset($options['proxy'])) {
            $request = $this->_addProxy($request, $options);
        }

        return $request;
    }

    /**
     * Returns headers for Accept/Content-Type based on a short type
     * or full mime-type.
     *
     * @param string $type short type alias or full mimetype.
     * @return array Headers to set on the request.
     * @throws \Cake\Core\Exception\Exception When an unknown type alias is used.
     */
    protected function _typeHeaders($type)
    {
        if (strpos($type, '/') !== false) {
            return [
                'Accept' => $type,
                'Content-Type' => $type
            ];
        }
        $typeMap = [
            'json' => 'application/json',
            'xml' => 'application/xml',
        ];
        if (!isset($typeMap[$type])) {
            throw new Exception("Unknown type alias '$type'.");
        }

        return [
            'Accept' => $typeMap[$type],
            'Content-Type' => $typeMap[$type],
        ];
    }

    /**
     * Add authentication headers to the request.
     *
     * Uses the authentication type to choose the correct strategy
     * and use its methods to add headers.
     *
     * @param \Cake\Http\Client\Request $request The request to modify.
     * @param array $options Array of options containing the 'auth' key.
     * @return \Cake\Http\Client\Request The updated request object.
     */
    protected function _addAuthentication(Request $request, $options)
    {
        $auth = $options['auth'];
        $adapter = $this->_createAuth($auth, $options);
        $result = $adapter->authentication($request, $options['auth']);

        return $result ?: $request;
    }

    /**
     * Add proxy authentication headers.
     *
     * Uses the authentication type to choose the correct strategy
     * and use its methods to add headers.
     *
     * @param \Cake\Http\Client\Request $request The request to modify.
     * @param array $options Array of options containing the 'proxy' key.
     * @return \Cake\Http\Client\Request The updated request object.
     */
    protected function _addProxy(Request $request, $options)
    {
        $auth = $options['proxy'];
        $adapter = $this->_createAuth($auth, $options);
        $result = $adapter->proxyAuthentication($request, $options['proxy']);

        return $result ?: $request;
    }

    /**
     * Create the authentication strategy.
     *
     * Use the configuration options to create the correct
     * authentication strategy handler.
     *
     * @param array $auth The authentication options to use.
     * @param array $options The overall request options to use.
     * @return mixed Authentication strategy instance.
     * @throws \Cake\Core\Exception\Exception when an invalid strategy is chosen.
     */
    protected function _createAuth($auth, $options)
    {
        if (empty($auth['type'])) {
            $auth['type'] = 'basic';
        }
        $name = ucfirst($auth['type']);
        $class = App::className($name, 'Http/Client/Auth');
        if (!$class) {
            throw new Exception(
                sprintf('Invalid authentication type %s', $name)
            );
        }

        return new $class($this, $options);
    }
}
// @deprecated 3.4.0 Backwards compatibility with earler 3.x versions.
class_alias('Cake\Http\Client', 'Cake\Network\Http\Client');
