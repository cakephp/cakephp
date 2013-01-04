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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Network\Http;

use Cake\Core\App;
use Cake\Error;
use Cake\Network\Http\Request;
use Cake\Network\Http\Response;
use Cake\Utility\Hash;

/**
 * The end user interface for doing HTTP requests.
 *
 * ### Scoped clients
 *
 * If you're doing multiple requests to the same hostname its often convienent
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
 * `Expires` and `Domain` attributes. You can get the list of
 * currently stored cookies using the cookies() method.
 *
 * ### Sending request bodies
 *
 * By default any POST/PUT/PATCH/DELETE request with $data will
 * send their data as `multipart/form-data`.
 *
 * When sending request bodies you can use the `type` option to
 * set the Content-Type for the request:
 *
 * `$http->get('/users', [], ['type' => 'json']);`
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
 * a proxy if you need to use one.. The type sub option can be used to
 * specify which authentication strategy you want to use.
 * CakePHP comes with built-in support for basic authentication.
 *
 */
class Client {

/**
 * Stored configuration for the client.
 *
 * @var array
 */
	protected $_config = [
		'host' => null,
		'port' => null,
		'scheme' => 'http',
		'timeout' => 30,
		'ssl_verify_peer' => true,
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
 * @var array
 */
	protected $_cookies = [];

/**
 * Adapter for sending requests. Defaults to
 * Cake\Network\Http\Stream
 *
 * @var Cake\Network\Http\Stream
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
 * - ssl_verify_depth - The maximum certificate chain depth to travers.
 *   Defaults to 5.
 * - ssl_verify_host - Verify that the certificate and hostname match.
 *   Defaults to true.
 * - redirect - Number of redirects to follow. Defaults to false.
 *
 * @param array $config Config options for scoped clients.
 */
	public function __construct($config = []) {
		$adapter = 'Cake\Network\Http\Adapter\Stream';
		if (isset($config['adapter'])) {
			$adapter = $config['adapter'];
			unset($config['adapter']);
		}
		$this->config($config);

		if (is_string($adapter)) {
			$adapter = new $adapter();
		}
		$this->_adapter = $adapter;
	}

/**
 * Get or set additional config options.
 *
 * Setting config will use Hash::merge() for appending into
 * the existing configuration.
 *
 * @param array|null $config Configuration options. null to get.
 * @return this|array
 */
	public function config($config = null) {
		if ($config === null) {
			return $this->_config;
		}
		$this->_config = Hash::merge($this->_config, $config);
		return $this;
	}

/**
 * Get the cookies stored in the Client.
 *
 * @return array
 */
	public function cookies() {
		return $this->_cookies;
	}

/**
 * Do a GET request.
 *
 * The $data argument supports a special `_content` key
 * for providing a request body in a GET request. This is
 * generally not used but services like ElasticSearch use 
 * this feature.
 *
 * @param string $url The url or path you want to request.
 * @param array $data The query data you want to send.
 * @param array $options Additional options for the request.
 * @return Cake\Network\Http\Response
 */
	public function get($url, $data = [], $options = []) {
		$options = $this->_mergeOptions($options);
		$body = [];
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
 * @param array $data The post data you want to send.
 * @param array $options Additional options for the request.
 * @return Cake\Network\Http\Response
 */
	public function post($url, $data = [], $options = []) {
		$options = $this->_mergeOptions($options);
		$url = $this->buildUrl($url, [], $options);
		return $this->_doRequest(Request::METHOD_POST, $url, $data, $options);
	}

/**
 * Do a PUT request.
 *
 * @param string $url The url or path you want to request.
 * @param array $data The request data you want to send.
 * @param array $options Additional options for the request.
 * @return Cake\Network\Http\Response
 */
	public function put($url, $data = [], $options = []) {
		$options = $this->_mergeOptions($options);
		$url = $this->buildUrl($url, [], $options);
		return $this->_doRequest(Request::METHOD_PUT, $url, $data, $options);
	}

/**
 * Do a PATCH request.
 *
 * @param string $url The url or path you want to request.
 * @param array $data The request data you want to send.
 * @param array $options Additional options for the request.
 * @return Cake\Network\Http\Response
 */
	public function patch($url, $data = [], $options = []) {
		$options = $this->_mergeOptions($options);
		$url = $this->buildUrl($url, [], $options);
		return $this->_doRequest(Request::METHOD_PATCH, $url, $data, $options);
	}

/**
 * Do a DELETE request.
 *
 * @param string $url The url or path you want to request.
 * @param array $data The request data you want to send.
 * @param array $options Additional options for the request.
 * @return Cake\Network\Http\Response
 */
	public function delete($url, $data = [], $options = []) {
		$options = $this->_mergeOptions($options);
		$url = $this->buildUrl($url, [], $options);
		return $this->_doRequest(Request::METHOD_DELETE, $url, $data, $options);
	}

/**
 * Helper method for doing non-GET requests.
 *
 * @param string $method HTTP method.
 * @param string $url URL to request.
 */
	protected function _doRequest($method, $url, $data, $options) {
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
	protected function _mergeOptions($options) {
		return Hash::merge($this->_config, $options);
	}

/**
 * Send a request.
 *
 * Used internally by other methods, but can also be used to send
 * handcrafted Request objects.
 *
 * @param Cake\Network\Http\Request $request The request to send.
 * @param array $options Additional options to use.
 * @return Cake\Network\Http\Response
 */
	public function send(Request $request, $options = []) {
		$responses = $this->_adapter->send($request, $options);
		$host = parse_url($request->url(), PHP_URL_HOST);
		foreach ($responses as $response) {
			$this->_storeCookies($response, $host);
		}
		return array_pop($responses);
	}

/**
 * Store cookies in a response to be used in future requests.
 *
 * Non-expired cookies will be stored for use in future requests
 * made with the same Client instance. Cookies are not saved
 * between instances.
 *
 * @param Response $response The response to read cookies from
 * @param string $host The request host, used for getting host names
 *   in case the cookies didn't set a domain.
 * @return void
 */
	protected function _storeCookies(Response $response, $host) {
		$cookies = $response->cookies();
		foreach ($cookies as $name => $cookie) {
			$expires = isset($cookie['expires']) ? $cookie['expires'] : false;
			$domain = isset($cookie['domain']) ? $cookie['domain'] : $host;
			$domain = trim($domain, '.');
			if ($expires) {
				$expires = \DateTime::createFromFormat('D, j-M-Y H:i:s e', $expires);
			}
			if ($expires && $expires->getTimestamp() <= time()) {
				continue;
			}
			if (empty($this->_cookies[$domain])) {
				$this->_cookies[$domain] = [];
			}
			$this->_cookies[$domain][$name] = $cookie['value'];
		}
	}

/**
 * Adds cookies stored in the client to the request.
 *
 * Uses the request's host to find matching cookies.
 *
 * @param Request $request
 * @return void
 */
	protected function _addCookies(Request $request) {
		$host = parse_url($request->url(), PHP_URL_HOST);
		if (isset($this->_cookies[$host])) {
			$request->cookie($this->_cookies[$host]);
		}
	}

/**
 * Generate a URL based on the scoped client options.
 *
 * @param string $url Either a full URL or just the path.
 * @param array $query The query data for the URL.
 * @param array $options The config options stored with Client::config()
 * @return string A complete url with scheme, port, host, path.
 */
	public function buildUrl($url, $query = [], $options = []) {
		if (empty($options) && empty($query)) {
			return $url;
		}
		if ($query) {
			$q = (strpos($url, '?') === false) ? '?' : '&';
			$url .= $q . http_build_query($query);
		}
		if (preg_match('#^https?://#', $url)) {
			return $url;
		}
		$defaults = [
			'host' => null,
			'port' => null,
			'scheme' => 'http',
		];
		$options += $defaults;
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
 * @param array $options The options to use. Contains auth, proxy etc.
 * @return Cake\Network\Http\Request
 */
	protected function _createRequest($method, $url, $data, $options) {
		$request = new Request();
		$request->method($method)
			->url($url)
			->body($data);
		if (isset($options['type'])) {
			$request->header($this->_typeHeaders($options['type']));
		}
		if (isset($options['headers'])) {
			$request->header($options['headers']);
		}
		$this->_addCookies($request);
		if (isset($options['cookies'])) {
			$request->cookie($options['cookies']);
		}
		if (isset($options['auth'])) {
			$this->_addAuthentication($request, $options);
		}
		if (isset($options['proxy'])) {
			$this->_addProxy($request, $options);
		}
		return $request;
	}

/**
 * Returns headers for Accept/Content-Type based on a short type
 * or full mime-type.
 *
 * @param string $type short type alias or full mimetype.
 * @return array Headers to set on the request.
 */
	protected function _typeHeaders($type) {
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
			throw new Error\Exception(__d('cake_dev', 'Unknown type alias.'));
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
 * @param Request $request The request to modify.
 * @param array $options Array of options containing the 'auth' key.
 * @return void
 */
	protected function _addAuthentication(Request $request, $options) {
		$auth = $options['auth'];
		$adapter = $this->_createAuth($auth, $options);
		$adapter->authentication($request, $options['auth']);
	}

/**
 * Add proxy authentication headers.
 *
 * Uses the authentication type to choose the correct strategy
 * and use its methods to add headers.
 *
 * @param Request $request The request to modify.
 * @param array $options Array of options containing the 'proxy' key.
 * @return void
 */
	protected function _addProxy(Request $request, $options) {
		$auth = $options['proxy'];
		$adapter = $this->_createAuth($auth, $options);
		$adapter->proxyAuthentication($request, $options['proxy']);
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
 * @throws Cake\Error\Exception when an invalid stratgey is chosen.
 */
	protected function _createAuth($auth, $options) {
		if (empty($auth['type'])) {
			$auth['type'] = 'basic';
		}
		$name = ucfirst($auth['type']);
		$class = App::className($name, 'Network/Http/Auth');
		if (!$class) {
			throw new Error\Exception(
				__d('cake_dev', 'Invalid authentication type %s', $name)
			);
		}
		return new $class($this, $options);
	}

}
