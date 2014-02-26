<?php
/**
 * NotFoundException class
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Error;

/**
 * Represents an HTTP 404 error.
 *
 */
class NotFoundException extends HttpException {

/**
 * Constructor
 *
 * @param string $message If no message is given 'Not Found' will be the message
 * @param integer $code Status code, defaults to 404
 */
	public function __construct($message = null, $code = 404) {
		if (empty($message)) {
			$message = 'Not Found';
		}
		parent::__construct($message, $code);
	}

}
