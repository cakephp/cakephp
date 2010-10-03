<?php
/**
 * ConsoleOutput an object to provide methods for generating console output.
 * Can be connected to any stream resource that can be used with fopen()
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
 * @subpackage    cake.cake.console
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ConsoleOutput {
/**
 * File handle for output.
 *
 * @var resource
 */
	protected $_output;

/**
 * Constant for a newline.
 */
	const LF = PHP_EOL;

/**
 * Construct the output object.
 *
 * @return void
 */
	public function __construct($stream = 'php://stdout') {
		$this->_output = fopen($stream, 'w');
	}

/**
 * Outputs a single or multiple messages to stdout. If no parameters
 * are passed outputs just a newline.
 *
 * @param mixed $message A string or a an array of strings to output
 * @param integer $newlines Number of newlines to append
 * @return integer Returns the number of bytes returned from writing to stdout.
 */
	public function write($message, $newlines = 1) {
		if (is_array($message)) {
			$message = implode(self::LF, $message);
		}
		return $this->_write($message . str_repeat(self::LF, $newlines));
	}

/**
 * Writes a message to the output stream
 *
 * @param string $message Message to write.
 * @return boolean success
 */
	protected function _write($message) {
		return fwrite($this->_output, $message);
	}

/**
 * clean up and close handles
 *
 * @return void
 */
	public function __destruct() {
		fclose($this->_output);
	}
}