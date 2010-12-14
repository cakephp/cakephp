<?php
/**
 * HTTP Response from HttpSocket.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class HttpResponse implements ArrayAccess {

/**
 * Body content
 *
 * @var string
 */
	public $body = '';

/**
 * Headers
 *
 * @var array
 */
	public $headers = array();

/**
 * Cookies
 *
 * @var array
 */
	public $cookies = array();

/**
 * HTTP version
 *
 * @var string
 */
	public $httpVersion = 'HTTP/1.1';

/**
 * Response code
 *
 * @var integer
 */
	public $code = 0;

/**
 * Reason phrase
 *
 * @var string
 */
	public $reasonPhrase = '';

/**
 * Pure raw content
 *
 * @var string
 */
	public $raw = '';

/**
 * Body content
 *
 * @return string
 */
	public function body() {
		return (string)$this->body;
	}

/**
 * Get header in case insensitive
 *
 * @param string $name Header name
 * @return mixed String if header exists or null
 */
	public function getHeader($name) {
		if (isset($this->headers[$name])) {
			return $this->headers[$name];
		}
		foreach ($this->headers as $key => $value) {
			if (strcasecmp($key, $name) == 0) {
				return $value;
			}
		}
		return null;
	}

/**
 * If return is 200 (OK)
 *
 * @return boolean
 */
	public function isOk() {
		return $this->code == 200;
	}

/**
 * ArrayAccess - Offset Exists
 *
 * @param mixed $offset
 * @return boolean
 */
	public function offsetExists($offset) {
		return in_array($offset, array('raw', 'status', 'header', 'body', 'cookies'));
	}

/**
 * ArrayAccess - Offset Get
 *
 * @param mixed $offset
 * @return mixed
 */
	public function offsetGet($offset) {
		switch ($offset) {
			case 'raw':
				$firstLineLength = strpos($this->raw, "\r\n") + 2;
				if ($this->raw[$firstLineLength] === "\r") {
					$header = null;
				} else {
					$header = substr($this->raw, $firstLineLength, strpos($this->raw, "\r\n\r\n") - $firstLineLength) . "\r\n";
				}
				return array(
					'status-line' => $this->httpVersion . ' ' . $this->code . ' ' . $this->reasonPhrase . "\r\n",
					'header' => $header,
					'body' => $this->body,
					'response' => $this->raw
				);
			case 'status':
				return array(
					'http-version' => $this->httpVersion,
					'code' => $this->code,
					'reason-phrase' => $this->reasonPhrase
				);
			case 'header':
				return $this->headers;
			case 'body':
				return $this->body;
			case 'cookies':
				return $this->cookies;
		}
		return null;
	}

/**
 * ArrayAccess - 0ffset Set
 *
 * @param mixed $offset
 * @param mixed $value
 * @return void
 */
	public function offsetSet($offset, $value) {
		return;
	}

/**
 * ArrayAccess - Offset Unset
 *
 * @param mixed @offset
 * @return void
 */
	public function offsetUnset($offset) {
		return;
	}

/**
 * Instance as string
 *
 * @return string
 */
	public function __toString() {
		return $this->body();
	}

}
