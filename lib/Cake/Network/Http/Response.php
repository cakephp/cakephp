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
 * Implements methods for HTTP responses
 */
class Response {

	protected $_headers;
	protected $_content;

	public function headers($headers = null) {
		if ($headers === null) {
			return $this->_headers;
		}
		$this->_headers = $headers;
		return $this;
	}

	public function content($content) {
		if ($content === null) {
			return $this->_content;
		}
		$this->_content = $content;
		return $this;
	}

}
