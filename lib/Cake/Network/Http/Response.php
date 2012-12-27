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
 * Implements methods for HTTP responses.
 *
 * All of the following examples assume that `$response` is an
 * instance of this class.
 *
 * ### Get header values
 *
 * Header names are case-insensitve, but normalized to Title-Case
 * when the response is parsed.
 *
 * `$val = $response->header('content-type');`
 *
 * Will read the Content-Type header. You can get all set
 * headers using:
 *
 * `$response->header();`
 *
 * You can also get at the headers using array notation. When getting
 * headers with array notation, you have to use case-sensitive header
 * names:
 *
 * `$val = $response['headers']['Content-Type'];`
 *
 * ### Get the response body
 *
 * You can access the response body using:
 *
 * `$content = $response->body();`
 *
 * You can also use array notation:
 *
 * `$content = $response['body'];`
 *
 * ### Check the status code
 *
 * You can access the response status code using:
 *
 * `$content = $response->statusCode();`
 *
 * You can also use array notation:
 *
 * `$content = $response['code'];`
 */
class Response implements \ArrayAccess {

	const STATUS_OK = 200;
	const STATUS_CREATED = 201;
	const STATUS_ACCEPTED = 202;

/**
 * The status code of the response.
 *
 * @var int
 */
	protected $_code;

/**
 * The array of headers in the response.
 *
 * @var array
 */
	protected $_headers;

/**
 * The array of cookies in the response.
 *
 * @var array
 */
	protected $_cookies;

/**
 * The response body
 *
 * @var string
 */
	protected $_body;

/**
 * Map of public => property names for ArrayAccess
 *
 * @var array
 */
	protected $_arrayProperties = [
		'cookies' => '_cookies',
		'headers' => '_headers',
		'body' => '_body',
		'code' => '_code'
	];
/**
 * Constructor
 *
 * @param array $headers Unparsed headers.
 * @param string $body The response body.
 */
	public function __construct($headers = [], $body = '') {
		$this->_parseHeaders($headers);
		$this->_body = $body;
	}

/**
 * Parses headers if necessary.
 *
 * - Decodes the status code.
 * - Parses and normalizes header names + values.
 *
 * @param array $headers
 */
	protected function _parseHeaders($headers) {
		foreach ($headers as $key => $value) {
			if (substr($value, 0, 5) === 'HTTP/') {
				preg_match('/HTTP\/[\d.]+ ([0-9]+)/i', $value, $matches);
				$this->_code = $matches[1];
				continue;
			}
			list($name, $value) = explode(':', $value, 2);
			$value = trim($value);
			$name = $this->_normalizeHeader($name);
			if ($name === 'Set-Cookie') {
				$this->_parseCookie($value);
			}
			if (isset($this->_headers[$name])) {
				$this->_headers[$name] = (array)$this->_headers[$name];
				$this->_headers[$name][] = $value;
			} else {
				$this->_headers[$name] = $value;
			}
		}
	}

/**
 * Parse a cookie header into data.
 *
 * @param string $value The cookie value to parse.
 * @return void
 */
	protected function _parseCookie($value) {
		$nestedSemi = '";"';
		if (strpos($value, $nestedSemi) !== false) {
			$value = str_replace($nestedSemi, "{__cookie_replace__}", $value);
			$parts = explode(';', $value);
			$parts = str_replace("{__cookie_replace__}", $nestedSemi, $parts);
		} else {
			$parts = preg_split('/\;[ \t]*/', $value);
		}

		$name = false;
		$cookie = [];
		foreach ($parts as $i => $part) {
			if (strpos($part, '=') !== false) {
				list($key, $value) = explode('=', $part, 2);
			} else {
				$key = $part;
				$value = true;
			}
			if ($i === 0) {
				$name = $key;
				$cookie['value'] = $value;
				continue;
			}
			$key = strtolower($key);
			if (!isset($cookie[$key])) {
				$cookie[$key] = $value;
			}
		}
		$this->_cookies[$name] = $cookie;
	}

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
 * Check if the response was OK
 *
 * @return boolean
 */
	public function isOk() {
		return in_array(
			$this->_code,
			[static::STATUS_OK, static::STATUS_CREATED, static::STATUS_ACCEPTED]
		);
	}

/**
 * Check if the response had a redirect status code.
 *
 * @return boolean
 */
	public function isRedirect() {
		return (
			in_array($this->_code, array(301, 302, 303, 307)) &&
			$this->header('Location')
		);
	}

/**
 * Get the status code from the response
 *
 * @return int
 */
	public function statusCode() {
		return $this->_code;
	}

/**
 * Get the encoding if it was set.
 *
 * @return string|null
 */
	public function encoding() {
		$content = $this->header('content-type');
		if (!$content) {
			return null;
		}
		preg_match('/charset\s?=\s?[\'"]?([a-z0-9-_]+)[\'"]?/i', $content, $matches);
		if (empty($matches[1])) {
			return null;
		}
		return $matches[1];
	}

/**
 * Read single/multiple header value(s) out.
 *
 * @param string $name The name of the header you want. Leave
 *   null to get all headers.
 * @return mixed Null when the header doesn't exist. An array
 *   will be returned when getting all headers or when getting
 *   a header that had multiple values set. Otherwise a string
 *   will be returned.
 */
	public function header($name = null) {
		if ($name === null) {
			return $this->_headers;
		}
		$name = $this->_normalizeHeader($name);
		if (!isset($this->_headers[$name])) {
			return null;
		}
		return $this->_headers[$name];
	}

/**
 * Read single/multiple cookie values out.
 *
 * @param string $name The name of the cookie you want. Leave
 *   null to get all cookies.
 * @param boolean $all Get all parts of the cookie. When false only
 *   the value will be returned.
 * @return mixed
 */
	public function cookie($name = null, $all = false) {
		if ($name === null) {
			return $this->_cookies;
		}
		if (!isset($this->_cookies[$name])) {
			return null;
		}
		if ($all) {
			return $this->_cookies[$name];
		}
		return $this->_cookies[$name]['value'];
	}

/**
 * Get the response body.
 *
 * By passing in a $parser callable, you can get the decoded
 * response content back.
 *
 * For example to get the json data as an object:
 *
 * `$body = $response->body('json_decode');`
 *
 * @param callable $parser The callback to use to decode
 *   the response body.
 * @return mixed The response body.
 */
	public function body($parser = null) {
		if ($parser) {
			return $parser($this->_body);
		}
		return $this->_body;
	}

/**
 * Read values with array syntax.
 *
 * @param string $name
 * @return mixed
 */
	public function offsetGet($name) {
		if (!isset($this->_arrayProperties[$name])) {
			return false;
		}
		$key = $this->_arrayProperties[$name];
		return $this->{$key};
	}

/**
 * isset/empty test with array syntax.
 *
 * @param string $name
 * @return boolean
 */
	public function offsetExists($name) {
		if (!isset($this->_arrayProperties[$name])) {
			return false;
		}
		$key = $this->_arrayProperties[$name];
		return isset($this->$key);
	}

/**
 * Do nothing ArrayAccess is readonly
 *
 * @param string $name
 * @param mixed $value
 * @return null
 */
	public function offsetSet($name, $value) {
		
	}

/**
 * Do nothing ArrayAccess is readonly
 *
 * @param string $name
 * @return null
 */
	public function offsetUnset($name) {
		
	}

}
