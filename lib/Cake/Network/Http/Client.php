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

	protected $_config = [];

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
 * @param string $url The url or path you want to request.
 * @param array $data The query data you want to send.
 * @param array $options Additional options for the request.
 * @return Cake\Network\Http\Response
 */
	public function get($url, $data = [], $options = []) {
		$options = $this->_mergeOptions($options);
		$request = $this->_createRequest(Request::METHOD_GET, $url, $data, $options);
		return $this->_adapter->send($request, $options);
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
 * Generate a URL based on the scoped client options.
 *
 * @param string $url Either a full URL or just the path.
 * @param array $options The config options stored with Client::config()
 * @return string A complete url with scheme, port, host, path.
 */
	public function buildUrl($url, $options = []) {
		if (empty($options)) {
			return $url;
		}
		$defaults = [
			'host' => null,
			'port' => null,
			'scheme' => 'http',
		];
		$options = array_merge($defaults, (array)$options);
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

	protected function _createRequest($method, $url, $data, $options) {
		$url = $this->buildUrl($url, $options);
		$request = new Request();
		$request->method($method)
			->url($url)
			->content($data);
		if (isset($options['headers'])) {
			$request->header($options['headers']);
		}
		return $request;
	}

}
