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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Network\Exception;

/**
 * Represents an HTTP 400 error.
 *
 */
class BadRequestException extends HttpException {

/**
 * Constructor
 *
 * @param string $message If no message is given 'Bad Request' will be the message
 * @param int $code Status code, defaults to 400
 */
	public function __construct($message = null, $code = 400) {
		if (empty($message)) {
			$message = 'Bad Request';
		}
		parent::__construct($message, $code);
	}

}
