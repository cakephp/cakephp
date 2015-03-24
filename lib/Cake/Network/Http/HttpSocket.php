<?php
/**
 * HTTP Socket connection class.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Network.Http
 * @since         CakePHP(tm) v 1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeSocket', 'Network');
App::uses('Router', 'Routing');
App::uses('Hash', 'Utility');

/**
 * CakePHP network socket connection class.
 *
 * Core base class for HTTP network communication. HttpSocket can be used as an
 * Object Oriented replacement for cURL in many places.
 *
 * @package       Cake.Network.Http
 */
class HttpSocket extends CakeSocket {

/**
 * When one activates the $quirksMode by setting it to true, all checks meant to
 * enforce RFC 2616 (HTTP/1.1 specs).
 * will be disabled and additional measures to deal with non-standard responses will be enabled.
 *
 * @var bool
 */
	public $quirksMode = false;

/**
 * Contain information about the last request (read only)
 *
 * @var array
 */
	public $request = array(
		'method' => 'GET',
		'uri' => array(
			'scheme' => 'http',
			'host' => null,
			'port' => 80,
			'user' => null,
			'pass' => null,
			'path' => null,
			'query' => null,
			'fragment' => null
		),
		'version' => '1.1',
		'body' => '',
		'line' => null,
		'header' => array(
			'Connection' => 'close',
			'User-Agent' => 'CakePHP'
		),
		'raw' => null,
		'redirect' => false,
		'cookies' => array(),
	);

/**
 * Contain information about the last response (read only)
 *
 * @var array
 */
	public $response = null;

/**
 * Response class name
 *
 * @var string
 */
	public $responseClass = 'HttpSocketResponse';

/**
 * Configuration settings for the HttpSocket and the requests
 *
 * @var array
 */
	public $config = array(
		'persistent' => false,
		'host' => 'localhost',
		'protocol' => 'tcp',
		'port' => 80,
		'timeout' => 30,
		'ssl_verify_peer' => true,
		'ssl_allow_self_signed' => false,
		'ssl_verify_depth' => 5,
		'ssl_verify_host' => true,
		'request' => array(
			'uri' => array(
				'scheme' => array('http', 'https'),
				'host' => 'localhost',
				'port' => array(80, 443)
			),
			'redirect' => false,
			'cookies' => array(),
		)
	);

/**
 * Authentication settings
 *
 * @var array
 */
	protected $_auth = array();

/**
 * Proxy settings
 *
 * @var array
 */
	protected $_proxy = array();

/**
 * Resource to receive the content of request
 *
 * @var mixed
 */
	protected $_contentResource = null;

/**
 * Build an HTTP Socket using the specified configuration.
 *
 * You can use a URL string to set the URL and use default configurations for
 * all other options:
 *
 * `$http = new HttpSocket('http://cakephp.org/');`
 *
 * Or use an array to configure multiple options:
 *
 * ```
 * $http = new HttpSocket(array(
 *    'host' => 'cakephp.org',
 *    'timeout' => 20
 * ));
 * ```
 *
 * See HttpSocket::$config for options that can be used.
 *
 * @param string|array $config Configuration information, either a string URL or an array of options.
 */
	public function __construct($config = array()) {
		if (is_string($config)) {
			$this->_configUri($config);
		} elseif (is_array($config)) {
			if (isset($config['request']['uri']) && is_string($config['request']['uri'])) {
				$this->_configUri($config['request']['uri']);
				unset($config['request']['uri']);
			}
			$this->config = Hash::merge($this->config, $config);
		}
		parent::__construct($this->config);
	}

/**
 * Set authentication settings.
 *
 * Accepts two forms of parameters. If all you need is a username + password, as with
 * Basic authentication you can do the following:
 *
 * ```
 * $http->configAuth('Basic', 'mark', 'secret');
 * ```
 *
 * If you are using an authentication strategy that requires more inputs, like Digest authentication
 * you can call `configAuth()` with an array of user information.
 *
 * ```
 * $http->configAuth('Digest', array(
 *		'user' => 'mark',
 *		'pass' => 'secret',
 *		'realm' => 'my-realm',
 *		'nonce' => 1235
 * ));
 * ```
 *
 * To remove any set authentication strategy, call `configAuth()` with no parameters:
 *
 * `$http->configAuth();`
 *
 * @param string $method Authentication method (ie. Basic, Digest). If empty, disable authentication
 * @param string|array $user Username for authentication. Can be an array with settings to authentication class
 * @param string $pass Password for authentication
 * @return void
 */
	public function configAuth($method, $user = null, $pass = null) {
		if (empty($method)) {
			$this->_auth = array();
			return;
		}
		if (is_array($user)) {
			$this->_auth = array($method => $user);
			return;
		}
		$this->_auth = array($method => compact('user', 'pass'));
	}

/**
 * Set proxy settings
 *
 * @param string|array $host Proxy host. Can be an array with settings to authentication class
 * @param int $port Port. Default 3128.
 * @param string $method Proxy method (ie, Basic, Digest). If empty, disable proxy authentication
 * @param string $user Username if your proxy need authentication
 * @param string $pass Password to proxy authentication
 * @return void
 */
	public function configProxy($host, $port = 3128, $method = null, $user = null, $pass = null) {
		if (empty($host)) {
			$this->_proxy = array();
			return;
		}
		if (is_array($host)) {
			$this->_proxy = $host + array('host' => null);
			return;
		}
		$this->_proxy = compact('host', 'port', 'method', 'user', 'pass');
	}

/**
 * Set the resource to receive the request content. This resource must support fwrite.
 *
 * @param resource|bool $resource Resource or false to disable the resource use
 * @return void
 * @throws SocketException
 */
	public function setContentResource($resource) {
		if ($resource === false) {
			$this->_contentResource = null;
			return;
		}
		if (!is_resource($resource)) {
			throw new SocketException(__d('cake_dev', 'Invalid resource.'));
		}
		$this->_contentResource = $resource;
	}

/**
 * Issue the specified request. HttpSocket::get() and HttpSocket::post() wrap this
 * method and provide a more granular interface.
 *
 * @param string|array $request Either an URI string, or an array defining host/uri
 * @return mixed false on error, HttpSocketResponse on success
 * @throws SocketException
 */
	public function request($request = array()) {
		$this->reset(false);

		if (is_string($request)) {
			$request = array('uri' => $request);
		} elseif (!is_array($request)) {
			return false;
		}

		if (!isset($request['uri'])) {
			$request['uri'] = null;
		}
		$uri = $this->_parseUri($request['uri']);
		if (!isset($uri['host'])) {
			$host = $this->config['host'];
		}
		if (isset($request['host'])) {
			$host = $request['host'];
			unset($request['host']);
		}
		$request['uri'] = $this->url($request['uri']);
		$request['uri'] = $this->_parseUri($request['uri'], true);
		$this->request = Hash::merge($this->request, array_diff_key($this->config['request'], array('cookies' => true)), $request);

		$this->_configUri($this->request['uri']);

		$Host = $this->request['uri']['host'];
		if (!empty($this->config['request']['cookies'][$Host])) {
			if (!isset($this->request['cookies'])) {
				$this->request['cookies'] = array();
			}
			if (!isset($request['cookies'])) {
				$request['cookies'] = array();
			}
			$this->request['cookies'] = array_merge($this->request['cookies'], $this->config['request']['cookies'][$Host], $request['cookies']);
		}

		if (isset($host)) {
			$this->config['host'] = $host;
		}

		$this->_setProxy();
		$this->request['proxy'] = $this->_proxy;

		$cookies = null;

		if (is_array($this->request['header'])) {
			if (!empty($this->request['cookies'])) {
				$cookies = $this->buildCookies($this->request['cookies']);
			}
			$scheme = '';
			$port = 0;
			if (isset($this->request['uri']['scheme'])) {
				$scheme = $this->request['uri']['scheme'];
			}
			if (isset($this->request['uri']['port'])) {
				$port = $this->request['uri']['port'];
			}
			if (($scheme === 'http' && $port != 80) ||
				($scheme === 'https' && $port != 443) ||
				($port != 80 && $port != 443)
			) {
				$Host .= ':' . $port;
			}
			$this->request['header'] = array_merge(compact('Host'), $this->request['header']);
		}

		if (isset($this->request['uri']['user'], $this->request['uri']['pass'])) {
			$this->configAuth('Basic', $this->request['uri']['user'], $this->request['uri']['pass']);
		} elseif (isset($this->request['auth'], $this->request['auth']['method'], $this->request['auth']['user'], $this->request['auth']['pass'])) {
			$this->configAuth($this->request['auth']['method'], $this->request['auth']['user'], $this->request['auth']['pass']);
		}
		$authHeader = Hash::get($this->request, 'header.Authorization');
		if (empty($authHeader)) {
			$this->_setAuth();
			$this->request['auth'] = $this->_auth;
		}

		if (is_array($this->request['body'])) {
			$this->request['body'] = http_build_query($this->request['body'], '', '&');
		}

		if (!empty($this->request['body']) && !isset($this->request['header']['Content-Type'])) {
			$this->request['header']['Content-Type'] = 'application/x-www-form-urlencoded';
		}

		if (!empty($this->request['body']) && !isset($this->request['header']['Content-Length'])) {
			$this->request['header']['Content-Length'] = strlen($this->request['body']);
		}
		if (isset($this->request['uri']['scheme']) && $this->request['uri']['scheme'] === 'https' && in_array($this->config['protocol'], array(false, 'tcp'))) {
			$this->config['protocol'] = 'ssl';
		}

		$connectionType = null;
		if (isset($this->request['header']['Connection'])) {
			$connectionType = $this->request['header']['Connection'];
		}
		$this->request['header'] = $this->_buildHeader($this->request['header']) . $cookies;

		if (empty($this->request['line'])) {
			$this->request['line'] = $this->_buildRequestLine($this->request);
		}

		if ($this->quirksMode === false && $this->request['line'] === false) {
			return false;
		}

		$this->_configContext($this->request['uri']['host']);

		$this->request['raw'] = '';
		if ($this->request['line'] !== false) {
			$this->request['raw'] = $this->request['line'];
		}

		if ($this->request['header'] !== false) {
			$this->request['raw'] .= $this->request['header'];
		}

		$this->request['raw'] .= "\r\n";
		$this->request['raw'] .= $this->request['body'];
		$this->write($this->request['raw']);

		$response = null;
		$inHeader = true;
		while ($data = $this->read()) {
			if ($this->_contentResource) {
				if ($inHeader) {
					$response .= $data;
					$pos = strpos($response, "\r\n\r\n");
					if ($pos !== false) {
						$pos += 4;
						$data = substr($response, $pos);
						fwrite($this->_contentResource, $data);

						$response = substr($response, 0, $pos);
						$inHeader = false;
					}
				} else {
					fwrite($this->_contentResource, $data);
					fflush($this->_contentResource);
				}
			} else {
				$response .= $data;
			}
		}

		if ($connectionType === 'close') {
			$this->disconnect();
		}

		list($plugin, $responseClass) = pluginSplit($this->responseClass, true);
		App::uses($responseClass, $plugin . 'Network/Http');
		if (!class_exists($responseClass)) {
			throw new SocketException(__d('cake_dev', 'Class %s not found.', $this->responseClass));
		}
		$this->response = new $responseClass($response);

		if (!empty($this->response->cookies)) {
			if (!isset($this->config['request']['cookies'][$Host])) {
				$this->config['request']['cookies'][$Host] = array();
			}
			$this->config['request']['cookies'][$Host] = array_merge($this->config['request']['cookies'][$Host], $this->response->cookies);
		}

		if ($this->request['redirect'] && $this->response->isRedirect()) {
			$location = trim($this->response->getHeader('Location'), '=');
			$request['uri'] = str_replace('%2F', '/', $location);
			$request['redirect'] = is_int($this->request['redirect']) ? $this->request['redirect'] - 1 : $this->request['redirect'];
			$this->response = $this->request($request);
		}

		return $this->response;
	}

/**
 * Issues a GET request to the specified URI, query, and request.
 *
 * Using a string uri and an array of query string parameters:
 *
 * `$response = $http->get('http://google.com/search', array('q' => 'cakephp', 'client' => 'safari'));`
 *
 * Would do a GET request to `http://google.com/search?q=cakephp&client=safari`
 *
 * You could express the same thing using a uri array and query string parameters:
 *
 * ```
 * $response = $http->get(
 *     array('host' => 'google.com', 'path' => '/search'),
 *     array('q' => 'cakephp', 'client' => 'safari')
 * );
 * ```
 *
 * @param string|array $uri URI to request. Either a string uri, or a uri array, see HttpSocket::_parseUri()
 * @param array $query Querystring parameters to append to URI
 * @param array $request An indexed array with indexes such as 'method' or uri
 * @return mixed Result of request, either false on failure or the response to the request.
 */
	public function get($uri = null, $query = array(), $request = array()) {
		if (!empty($query)) {
			$uri = $this->_parseUri($uri, $this->config['request']['uri']);
			if (isset($uri['query'])) {
				$uri['query'] = array_merge($uri['query'], $query);
			} else {
				$uri['query'] = $query;
			}
			$uri = $this->_buildUri($uri);
		}

		$request = Hash::merge(array('method' => 'GET', 'uri' => $uri), $request);
		return $this->request($request);
	}

/**
 * Issues a HEAD request to the specified URI, query, and request.
 *
 * By definition HEAD request are identical to GET request except they return no response body. This means that all
 * information and examples relevant to GET also applys to HEAD.
 *
 * @param string|array $uri URI to request. Either a string URI, or a URI array, see HttpSocket::_parseUri()
 * @param array $query Querystring parameters to append to URI
 * @param array $request An indexed array with indexes such as 'method' or uri
 * @return mixed Result of request, either false on failure or the response to the request.
 */
	public function head($uri = null, $query = array(), $request = array()) {
		if (!empty($query)) {
			$uri = $this->_parseUri($uri, $this->config['request']['uri']);
			if (isset($uri['query'])) {
				$uri['query'] = array_merge($uri['query'], $query);
			} else {
				$uri['query'] = $query;
			}
			$uri = $this->_buildUri($uri);
		}

		$request = Hash::merge(array('method' => 'HEAD', 'uri' => $uri), $request);
		return $this->request($request);
	}

/**
 * Issues a POST request to the specified URI, query, and request.
 *
 * `post()` can be used to post simple data arrays to a URL:
 *
 * ```
 * $response = $http->post('http://example.com', array(
 *     'username' => 'batman',
 *     'password' => 'bruce_w4yne'
 * ));
 * ```
 *
 * @param string|array $uri URI to request. See HttpSocket::_parseUri()
 * @param array $data Array of request body data keys and values.
 * @param array $request An indexed array with indexes such as 'method' or uri
 * @return mixed Result of request, either false on failure or the response to the request.
 */
	public function post($uri = null, $data = array(), $request = array()) {
		$request = Hash::merge(array('method' => 'POST', 'uri' => $uri, 'body' => $data), $request);
		return $this->request($request);
	}

/**
 * Issues a PUT request to the specified URI, query, and request.
 *
 * @param string|array $uri URI to request, See HttpSocket::_parseUri()
 * @param array $data Array of request body data keys and values.
 * @param array $request An indexed array with indexes such as 'method' or uri
 * @return mixed Result of request
 */
	public function put($uri = null, $data = array(), $request = array()) {
		$request = Hash::merge(array('method' => 'PUT', 'uri' => $uri, 'body' => $data), $request);
		return $this->request($request);
	}

/**
 * Issues a PATCH request to the specified URI, query, and request.
 *
 * @param string|array $uri URI to request, See HttpSocket::_parseUri()
 * @param array $data Array of request body data keys and values.
 * @param array $request An indexed array with indexes such as 'method' or uri
 * @return mixed Result of request
 */
	public function patch($uri = null, $data = array(), $request = array()) {
		$request = Hash::merge(array('method' => 'PATCH', 'uri' => $uri, 'body' => $data), $request);
		return $this->request($request);
	}

/**
 * Issues a DELETE request to the specified URI, query, and request.
 *
 * @param string|array $uri URI to request (see {@link _parseUri()})
 * @param array $data Array of request body data keys and values.
 * @param array $request An indexed array with indexes such as 'method' or uri
 * @return mixed Result of request
 */
	public function delete($uri = null, $data = array(), $request = array()) {
		$request = Hash::merge(array('method' => 'DELETE', 'uri' => $uri, 'body' => $data), $request);
		return $this->request($request);
	}

/**
 * Normalizes URLs into a $uriTemplate. If no template is provided
 * a default one will be used. Will generate the URL using the
 * current config information.
 *
 * ### Usage:
 *
 * After configuring part of the request parameters, you can use url() to generate
 * URLs.
 *
 * ```
 * $http = new HttpSocket('http://www.cakephp.org');
 * $url = $http->url('/search?q=bar');
 * ```
 *
 * Would return `http://www.cakephp.org/search?q=bar`
 *
 * url() can also be used with custom templates:
 *
 * `$url = $http->url('http://www.cakephp/search?q=socket', '/%path?%query');`
 *
 * Would return `/search?q=socket`.
 *
 * @param string|array $url Either a string or array of URL options to create a URL with.
 * @param string $uriTemplate A template string to use for URL formatting.
 * @return mixed Either false on failure or a string containing the composed URL.
 */
	public function url($url = null, $uriTemplate = null) {
		if ($url === null) {
			$url = '/';
		}
		if (is_string($url)) {
			$scheme = $this->config['request']['uri']['scheme'];
			if (is_array($scheme)) {
				$scheme = $scheme[0];
			}
			$port = $this->config['request']['uri']['port'];
			if (is_array($port)) {
				$port = $port[0];
			}
			if ($url{0} === '/') {
				$url = $this->config['request']['uri']['host'] . ':' . $port . $url;
			}
			if (!preg_match('/^.+:\/\/|\*|^\//', $url)) {
				$url = $scheme . '://' . $url;
			}
		} elseif (!is_array($url) && !empty($url)) {
			return false;
		}

		$base = array_merge($this->config['request']['uri'], array('scheme' => array('http', 'https'), 'port' => array(80, 443)));
		$url = $this->_parseUri($url, $base);

		if (empty($url)) {
			$url = $this->config['request']['uri'];
		}

		if (!empty($uriTemplate)) {
			return $this->_buildUri($url, $uriTemplate);
		}
		return $this->_buildUri($url);
	}

/**
 * Set authentication in request
 *
 * @return void
 * @throws SocketException
 */
	protected function _setAuth() {
		if (empty($this->_auth)) {
			return;
		}
		$method = key($this->_auth);
		list($plugin, $authClass) = pluginSplit($method, true);
		$authClass = Inflector::camelize($authClass) . 'Authentication';
		App::uses($authClass, $plugin . 'Network/Http');

		if (!class_exists($authClass)) {
			throw new SocketException(__d('cake_dev', 'Unknown authentication method.'));
		}
		if (!method_exists($authClass, 'authentication')) {
			throw new SocketException(__d('cake_dev', 'The %s does not support authentication.', $authClass));
		}
		call_user_func_array("$authClass::authentication", array($this, &$this->_auth[$method]));
	}

/**
 * Set the proxy configuration and authentication
 *
 * @return void
 * @throws SocketException
 */
	protected function _setProxy() {
		if (empty($this->_proxy) || !isset($this->_proxy['host'], $this->_proxy['port'])) {
			return;
		}
		$this->config['host'] = $this->_proxy['host'];
		$this->config['port'] = $this->_proxy['port'];
		$this->config['proxy'] = true;

		if (empty($this->_proxy['method']) || !isset($this->_proxy['user'], $this->_proxy['pass'])) {
			return;
		}
		list($plugin, $authClass) = pluginSplit($this->_proxy['method'], true);
		$authClass = Inflector::camelize($authClass) . 'Authentication';
		App::uses($authClass, $plugin . 'Network/Http');

		if (!class_exists($authClass)) {
			throw new SocketException(__d('cake_dev', 'Unknown authentication method for proxy.'));
		}
		if (!method_exists($authClass, 'proxyAuthentication')) {
			throw new SocketException(__d('cake_dev', 'The %s does not support proxy authentication.', $authClass));
		}
		call_user_func_array("$authClass::proxyAuthentication", array($this, &$this->_proxy));
	}

/**
 * Parses and sets the specified URI into current request configuration.
 *
 * @param string|array $uri URI, See HttpSocket::_parseUri()
 * @return bool If uri has merged in config
 */
	protected function _configUri($uri = null) {
		if (empty($uri)) {
			return false;
		}

		if (is_array($uri)) {
			$uri = $this->_parseUri($uri);
		} else {
			$uri = $this->_parseUri($uri, true);
		}

		if (!isset($uri['host'])) {
			return false;
		}
		$config = array(
			'request' => array(
				'uri' => array_intersect_key($uri, $this->config['request']['uri'])
			)
		);
		$this->config = Hash::merge($this->config, $config);
		$this->config = Hash::merge($this->config, array_intersect_key($this->config['request']['uri'], $this->config));
		return true;
	}

/**
 * Configure the socket's context. Adds in configuration
 * that can not be declared in the class definition.
 *
 * @param string $host The host you're connecting to.
 * @return void
 */
	protected function _configContext($host) {
		foreach ($this->config as $key => $value) {
			if (substr($key, 0, 4) !== 'ssl_') {
				continue;
			}
			$contextKey = substr($key, 4);
			if (empty($this->config['context']['ssl'][$contextKey])) {
				$this->config['context']['ssl'][$contextKey] = $value;
			}
			unset($this->config[$key]);
		}
		if (version_compare(PHP_VERSION, '5.3.2', '>=')) {
			if (empty($this->config['context']['ssl']['SNI_enabled'])) {
				$this->config['context']['ssl']['SNI_enabled'] = true;
			}
			if (version_compare(PHP_VERSION, '5.6.0', '>=')) {
				if (empty($this->config['context']['ssl']['peer_name'])) {
					$this->config['context']['ssl']['peer_name'] = $host;
				}
			} else {
				if (empty($this->config['context']['ssl']['SNI_server_name'])) {
					$this->config['context']['ssl']['SNI_server_name'] = $host;
				}
			}
		}
		if (empty($this->config['context']['ssl']['cafile'])) {
			$this->config['context']['ssl']['cafile'] = CAKE . 'Config' . DS . 'cacert.pem';
		}
		if (!empty($this->config['context']['ssl']['verify_host'])) {
			$this->config['context']['ssl']['CN_match'] = $host;
		}
		unset($this->config['context']['ssl']['verify_host']);
	}

/**
 * Takes a $uri array and turns it into a fully qualified URL string
 *
 * @param string|array $uri Either A $uri array, or a request string. Will use $this->config if left empty.
 * @param string $uriTemplate The Uri template/format to use.
 * @return mixed A fully qualified URL formatted according to $uriTemplate, or false on failure
 */
	protected function _buildUri($uri = array(), $uriTemplate = '%scheme://%user:%pass@%host:%port/%path?%query#%fragment') {
		if (is_string($uri)) {
			$uri = array('host' => $uri);
		}
		$uri = $this->_parseUri($uri, true);

		if (!is_array($uri) || empty($uri)) {
			return false;
		}

		$uri['path'] = preg_replace('/^\//', null, $uri['path']);
		$uri['query'] = http_build_query($uri['query'], '', '&');
		$uri['query'] = rtrim($uri['query'], '=');
		$stripIfEmpty = array(
			'query' => '?%query',
			'fragment' => '#%fragment',
			'user' => '%user:%pass@',
			'host' => '%host:%port/'
		);

		foreach ($stripIfEmpty as $key => $strip) {
			if (empty($uri[$key])) {
				$uriTemplate = str_replace($strip, null, $uriTemplate);
			}
		}

		$defaultPorts = array('http' => 80, 'https' => 443);
		if (array_key_exists($uri['scheme'], $defaultPorts) && $defaultPorts[$uri['scheme']] == $uri['port']) {
			$uriTemplate = str_replace(':%port', null, $uriTemplate);
		}
		foreach ($uri as $property => $value) {
			$uriTemplate = str_replace('%' . $property, $value, $uriTemplate);
		}

		if ($uriTemplate === '/*') {
			$uriTemplate = '*';
		}
		return $uriTemplate;
	}

/**
 * Parses the given URI and breaks it down into pieces as an indexed array with elements
 * such as 'scheme', 'port', 'query'.
 *
 * @param string|array $uri URI to parse
 * @param bool|array $base If true use default URI config, otherwise indexed array to set 'scheme', 'host', 'port', etc.
 * @return array Parsed URI
 */
	protected function _parseUri($uri = null, $base = array()) {
		$uriBase = array(
			'scheme' => array('http', 'https'),
			'host' => null,
			'port' => array(80, 443),
			'user' => null,
			'pass' => null,
			'path' => '/',
			'query' => null,
			'fragment' => null
		);

		if (is_string($uri)) {
			$uri = parse_url($uri);
		}
		if (!is_array($uri) || empty($uri)) {
			return false;
		}
		if ($base === true) {
			$base = $uriBase;
		}

		if (isset($base['port'], $base['scheme']) && is_array($base['port']) && is_array($base['scheme'])) {
			if (isset($uri['scheme']) && !isset($uri['port'])) {
				$base['port'] = $base['port'][array_search($uri['scheme'], $base['scheme'])];
			} elseif (isset($uri['port']) && !isset($uri['scheme'])) {
				$base['scheme'] = $base['scheme'][array_search($uri['port'], $base['port'])];
			}
		}

		if (is_array($base) && !empty($base)) {
			$uri = array_merge($base, $uri);
		}

		if (isset($uri['scheme']) && is_array($uri['scheme'])) {
			$uri['scheme'] = array_shift($uri['scheme']);
		}
		if (isset($uri['port']) && is_array($uri['port'])) {
			$uri['port'] = array_shift($uri['port']);
		}

		if (array_key_exists('query', $uri)) {
			$uri['query'] = $this->_parseQuery($uri['query']);
		}

		if (!array_intersect_key($uriBase, $uri)) {
			return false;
		}
		return $uri;
	}

/**
 * This function can be thought of as a reverse to PHP5's http_build_query(). It takes a given query string and turns it into an array and
 * supports nesting by using the php bracket syntax. So this means you can parse queries like:
 *
 * - ?key[subKey]=value
 * - ?key[]=value1&key[]=value2
 *
 * A leading '?' mark in $query is optional and does not effect the outcome of this function.
 * For the complete capabilities of this implementation take a look at HttpSocketTest::testparseQuery()
 *
 * @param string|array $query A query string to parse into an array or an array to return directly "as is"
 * @return array The $query parsed into a possibly multi-level array. If an empty $query is
 *     given, an empty array is returned.
 */
	protected function _parseQuery($query) {
		if (is_array($query)) {
			return $query;
		}

		$parsedQuery = array();

		if (is_string($query) && !empty($query)) {
			$query = preg_replace('/^\?/', '', $query);
			$items = explode('&', $query);

			foreach ($items as $item) {
				if (strpos($item, '=') !== false) {
					list($key, $value) = explode('=', $item, 2);
				} else {
					$key = $item;
					$value = null;
				}

				$key = urldecode($key);
				$value = urldecode($value);

				if (preg_match_all('/\[([^\[\]]*)\]/iUs', $key, $matches)) {
					$subKeys = $matches[1];
					$rootKey = substr($key, 0, strpos($key, '['));
					if (!empty($rootKey)) {
						array_unshift($subKeys, $rootKey);
					}
					$queryNode =& $parsedQuery;

					foreach ($subKeys as $subKey) {
						if (!is_array($queryNode)) {
							$queryNode = array();
						}

						if ($subKey === '') {
							$queryNode[] = array();
							end($queryNode);
							$subKey = key($queryNode);
						}
						$queryNode =& $queryNode[$subKey];
					}
					$queryNode = $value;
					continue;
				}
				if (!isset($parsedQuery[$key])) {
					$parsedQuery[$key] = $value;
				} else {
					$parsedQuery[$key] = (array)$parsedQuery[$key];
					$parsedQuery[$key][] = $value;
				}
			}
		}
		return $parsedQuery;
	}

/**
 * Builds a request line according to HTTP/1.1 specs. Activate quirks mode to work outside specs.
 *
 * @param array $request Needs to contain a 'uri' key. Should also contain a 'method' key, otherwise defaults to GET.
 * @return string Request line
 * @throws SocketException
 */
	protected function _buildRequestLine($request = array()) {
		$asteriskMethods = array('OPTIONS');

		if (is_string($request)) {
			$isValid = preg_match("/(.+) (.+) (.+)\r\n/U", $request, $match);
			if (!$this->quirksMode && (!$isValid || ($match[2] === '*' && !in_array($match[3], $asteriskMethods)))) {
				throw new SocketException(__d('cake_dev', 'HttpSocket::_buildRequestLine - Passed an invalid request line string. Activate quirks mode to do this.'));
			}
			return $request;
		} elseif (!is_array($request)) {
			return false;
		} elseif (!array_key_exists('uri', $request)) {
			return false;
		}

		$request['uri'] = $this->_parseUri($request['uri']);
		$request += array('method' => 'GET');
		if (!empty($this->_proxy['host']) && $request['uri']['scheme'] !== 'https') {
			$request['uri'] = $this->_buildUri($request['uri'], '%scheme://%host:%port/%path?%query');
		} else {
			$request['uri'] = $this->_buildUri($request['uri'], '/%path?%query');
		}

		if (!$this->quirksMode && $request['uri'] === '*' && !in_array($request['method'], $asteriskMethods)) {
			throw new SocketException(__d('cake_dev', 'HttpSocket::_buildRequestLine - The "*" asterisk character is only allowed for the following methods: %s. Activate quirks mode to work outside of HTTP/1.1 specs.', implode(',', $asteriskMethods)));
		}
		$version = isset($request['version']) ? $request['version'] : '1.1';
		return $request['method'] . ' ' . $request['uri'] . ' HTTP/' . $version . "\r\n";
	}

/**
 * Builds the header.
 *
 * @param array $header Header to build
 * @param string $mode Mode
 * @return string Header built from array
 */
	protected function _buildHeader($header, $mode = 'standard') {
		if (is_string($header)) {
			return $header;
		} elseif (!is_array($header)) {
			return false;
		}

		$fieldsInHeader = array();
		foreach ($header as $key => $value) {
			$lowKey = strtolower($key);
			if (array_key_exists($lowKey, $fieldsInHeader)) {
				$header[$fieldsInHeader[$lowKey]] = $value;
				unset($header[$key]);
			} else {
				$fieldsInHeader[$lowKey] = $key;
			}
		}

		$returnHeader = '';
		foreach ($header as $field => $contents) {
			if (is_array($contents) && $mode === 'standard') {
				$contents = implode(',', $contents);
			}
			foreach ((array)$contents as $content) {
				$contents = preg_replace("/\r\n(?![\t ])/", "\r\n ", $content);
				$field = $this->_escapeToken($field);

				$returnHeader .= $field . ': ' . $contents . "\r\n";
			}
		}
		return $returnHeader;
	}

/**
 * Builds cookie headers for a request.
 *
 * Cookies can either be in the format returned in responses, or
 * a simple key => value pair.
 *
 * @param array $cookies Array of cookies to send with the request.
 * @return string Cookie header string to be sent with the request.
 */
	public function buildCookies($cookies) {
		$header = array();
		foreach ($cookies as $name => $cookie) {
			if (is_array($cookie)) {
				$value = $this->_escapeToken($cookie['value'], array(';'));
			} else {
				$value = $this->_escapeToken($cookie, array(';'));
			}
			$header[] = $name . '=' . $value;
		}
		return $this->_buildHeader(array('Cookie' => implode('; ', $header)), 'pragmatic');
	}

/**
 * Escapes a given $token according to RFC 2616 (HTTP 1.1 specs)
 *
 * @param string $token Token to escape
 * @param array $chars Characters to escape
 * @return string Escaped token
 */
	protected function _escapeToken($token, $chars = null) {
		$regex = '/([' . implode('', $this->_tokenEscapeChars(true, $chars)) . '])/';
		$token = preg_replace($regex, '"\\1"', $token);
		return $token;
	}

/**
 * Gets escape chars according to RFC 2616 (HTTP 1.1 specs).
 *
 * @param bool $hex true to get them as HEX values, false otherwise
 * @param array $chars Characters to escape
 * @return array Escape chars
 */
	protected function _tokenEscapeChars($hex = true, $chars = null) {
		if (!empty($chars)) {
			$escape = $chars;
		} else {
			$escape = array('"', "(", ")", "<", ">", "@", ",", ";", ":", "\\", "/", "[", "]", "?", "=", "{", "}", " ");
			for ($i = 0; $i <= 31; $i++) {
				$escape[] = chr($i);
			}
			$escape[] = chr(127);
		}

		if (!$hex) {
			return $escape;
		}
		foreach ($escape as $key => $char) {
			$escape[$key] = '\\x' . str_pad(dechex(ord($char)), 2, '0', STR_PAD_LEFT);
		}
		return $escape;
	}

/**
 * Resets the state of this HttpSocket instance to it's initial state (before Object::__construct got executed) or does
 * the same thing partially for the request and the response property only.
 *
 * @param bool $full If set to false only HttpSocket::response and HttpSocket::request are reset
 * @return bool True on success
 */
	public function reset($full = true) {
		static $initalState = array();
		if (empty($initalState)) {
			$initalState = get_class_vars(__CLASS__);
		}
		if (!$full) {
			$this->request = $initalState['request'];
			$this->response = $initalState['response'];
			return true;
		}
		parent::reset($initalState);
		return true;
	}

}

