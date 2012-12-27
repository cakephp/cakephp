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

use Cake\Network\Http\Request;
use Cake\Network\Http\Response;
use Cake\Utility\Hash;

/**
 * The end user interface for doing HTTP requests.
 *
 * ### Scoped clients
 *
 * ### Doing requests
 *
 * ### Using authentication
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
 *
 * @param array $config Config options for scoped clients.
 */
	public function __construct($config = []) {
		$adapter = 'Cake\Network\Http\Adapter\Stream';
		if (isset($config['adapter'])) {
			$adapter = $config['adapter'];
			unset($config['adapter']);
		}
		$this->_config = $config;

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
		$request = $this->_createRequest(
			Request::METHOD_GET,
			$url,
			$body,
			$options
		);
		return $this->send($request, $options);
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
	}

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
 * @param Cake\Network\Http\Request $request The request to send.
 * @param array $options Additional options to use.
 * @return Cake\Network\Http\Response
 */
	public function send(Request $request, $options = []) {
		return $this->_adapter->send($request, $options);
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
 * @param mixed $data The request body content.
 * @param array $options The options to use. Contains auth, proxy etc.
 * @return Cake\Network\Http\Request
 */
	protected function _createRequest($method, $url, $data, $options) {
		$request = new Request();
		$request->method($method)
			->url($url)
			->content($data);
		if (isset($options['headers'])) {
			$request->header($options['headers']);
		}
		if (isset($options['cookies'])) {
			$request->cookie($options['cookies']);
		}
		// TODO auth + proxy config.
		return $request;
	}

}
