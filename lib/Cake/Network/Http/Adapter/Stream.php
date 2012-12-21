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
namespace Cake\Network\Http\Adapter;

use Cake\Network\Http\Request;
use Cake\Network\Http\Response;

/**
 * Implements sending Cake\Network\Http\Request
 * via php's stream API.
 */
class Stream {

	protected $_context;
	protected $_contextOptions;
	protected $_stream;

	public function send(Request $request, $options) {
		$this->_context = array();

		$this->_buildContext($request, $options);
		return $this->_send();
	}

/**
 * Build the stream context out of the request object.
 *
 * @param Request $request The request to build context from.
 * @param array $options Additional request options.
 * @return void
 */
	protected function _buildContext(Request $request, $options) {
		$this->_buildHeaders($request, $options);
		$this->_buildContent($request, $options);
		$this->_buildOptions($request, $options);

		$url = $request->url();
		$scheme = parse_url($url, PHP_URL_SCHEME);
		$this->_context = stream_context_create([
			$scheme => $this->_contextOptions
		]);
	}

/**
 * Build the header context for the request.
 *
 * Creates cookies & headers.
 */
	protected function _buildHeaders(Request $request, $options) {
		$headers = [];
		foreach ($request->headers() as $name => $value) {
			$headers[] = "$name: $value";
		}

		$cookies = [];
		foreach ($request->cookies() as $name => $value) {
			$cookies[] = "$name=$value";
		}
		if ($cookies) {
			$headers[] = 'Cookie: ' . implode('; ', $cookies);
		}
		$this->_contextOptions['header'] = implode("\r\n", $headers);
	}

	protected function _buildContent($request, $options) {
	}

/**
 * Build miscellaneous options for the request.
 *
 * @param Request $request
 * @param array $options
 */
	protected function _buildOptions(Request $request, $options) {
		$this->_contextOptions['method'] = $request->method();
		$this->_contextOptions['protocol_version'] = $request->version();

		if (isset($options['timeout'])) {
			$this->_contextOptions['timeout'] = $options['timeout'];
		}
	}

	protected function _send() {
	}

/**
 * Get the contextOptions.
 *
 * @return array
 */
	public function contextOptions() {
		return $this->_contextOptions;
	}

}
