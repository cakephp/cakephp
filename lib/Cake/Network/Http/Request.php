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
 * Implements methods for HTTP requests.
 */
class Request {

	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_PATCH = 'PATCH';
/**
 * HTTP Version being used.
 *
 * @var string
 */
	protected $_version = '1.1';

	protected $_method;
	protected $_content;
	protected $_url;

/**
 * Headers to be sent.
 *
 * @var array
 */
	protected $_headers = [
		'Connection' => 'close',
		'User-Agent' => 'CakePHP'
	];

	public function method($method = null) {
		if ($method === null) {
			return $this->_method;
		}
		$this->_method = $method;
		return $this;
	}

	public function url($url = null) {
		if ($url === null) {
			return $this->_url;
		}
		$this->_url = $url;
		return $this;
	}

	public function header($name = null, $value = null) {

	}

	public function content($content = null) {
		if ($content === null) {
			return $this->_content;
		}
		$this->_content = $content;
		return $this;
	}

}
