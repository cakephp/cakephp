<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html
 * @since         3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Error;

/**
 * Represents a fatal error
 *
 */
class FatalErrorException extends Exception {

/**
 * Constructor
 *
 * @param string $message
 * @param int $code
 * @param string $file
 * @param int $line
 */
	public function __construct($message, $code = 500, $file = null, $line = null) {
		parent::__construct($message, $code);
		if ($file) {
			$this->file = $file;
		}
		if ($line) {
			$this->line = $line;
		}
	}

}
