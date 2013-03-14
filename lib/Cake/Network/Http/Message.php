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

/**
 * Base class for other HTTP requests/responses
 *
 * Defines some common helper methods, constants
 * and properties.
 */
class Message {

	const STATUS_OK = 200;
	const STATUS_CREATED = 201;
	const STATUS_ACCEPTED = 202;
	const STATUS_MOVED_PERMANENTLY = 301;
	const STATUS_FOUND = 302;
	const STATUS_SEE_OTHER = 303;
	const STATUS_TEMPORARY_REDIRECT = 307;

	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_PATCH = 'PATCH';
	const METHOD_HEAD = 'HEAD';

/**
 * The array of headers in the response.
 *
 * @var array
 */
	protected $_headers = [];

/**
 * The array of cookies in the response.
 *
 * @var array
 */
	protected $_cookies = [];

/**
 * HTTP Version being used.
 *
 * @var string
 */
	protected $_version = '1.1';

/**
 * Normalize header names to Camel-Case form.
 *
 * @param string $name The header name to normalize.
 * @return string Normalized header name.
 */
	protected function _normalizeHeader($name) {
		$parts = explode('-', trim($name));
		$parts = array_map('strtolower', $parts);
		$parts = array_map('ucfirst', $parts);
		return implode('-', $parts);
	}

/**
 * Get all headers
 *
 * @return array
 */
	public function headers() {
		return $this->_headers;
	}

/**
 * Get all cookies
 *
 * @return array
 */
	public function cookies() {
		return $this->_cookies;
	}

/**
 * Get the HTTP version used.
 *
 * @param null|string $version
 * @return string
 */
	public function version() {
		return $this->_version;
	}

/**
 * Get/set the body for the message.
 *
 * @param string|null $body The body for the request. Leave null for get
 * @return mixed Either $this or the body value.
 */
	public function body($body = null) {
		if ($body === null) {
			return $this->_body;
		}
		$this->_body = $body;
		return $this;
	}

}
