<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html
 * @since         3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Error;

/**
 * Base class that all Exceptions extend.
 *
 */
class BaseException extends \RuntimeException {

/**
 * Array of headers to be passed to Cake\Network\Response::header()
 *
 * @var array
 */
	protected $_responseHeaders = null;

/**
 * Get/set the response header to be used
 *
 * See also Cake\Network\Response::header()
 *
 * @param string|array $header. An array of header strings or a single header string
 *	- an associative array of "header name" => "header value"
 *	- an array of string headers is also accepted
 * @param string $value The header value.
 * @return array
 */
	public function responseHeader($header = null, $value = null) {
		if ($header) {
			if (is_array($header)) {
				return $this->_responseHeaders = $header;
			}
			$this->_responseHeaders = array($header => $value);
		}
		return $this->_responseHeaders;
	}

}
