<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.8
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses("ConsoleOutput", "Console");

/**
 * StubOutput makes testing shell commands/shell helpers easier.
 *
 * You can use this class by injecting it into a Helper instance:
 *
 * ```
 * App::uses("ConsoleOutputStub", "TestSuite/Stub");
 *
 * $output = new ConsoleOutputStub();
 * $helper = new ProgressHelper($output);
 * ```
 */
class ConsoleOutputStub extends ConsoleOutput {

/**
 * Buffered messages.
 *
 * @var array
 */
	protected $_out = array();

/**
 * The number of bytes written by last call to write
 *
 * @var int
 */
	protected $_lastWritten = 0;

/**
 * Write output to the buffer.
 *
 * @param string|array $message A string or an array of strings to output
 * @param int $newlines Number of newlines to append
 * @return void
 */
	public function write($message, $newlines = 1) {
		foreach ((array)$message as $line) {
			$this->_out[] = $line;
			$this->_lastWritten = strlen($line);
		}
		$newlines--;
		while ($newlines > 0) {
			$this->_out[] = '';
			$this->_lastWritten = 0;
			$newlines--;
		}
	}

/**
 * Overwrite output already written to the buffer.
 *
 * @param array|string $message The message to output.
 * @param int $newlines Number of newlines to append.
 * @param int $size The number of bytes to overwrite. Defaults to the
 *    length of the last message output.
 * @return void
 */
	public function overwrite($message, $newlines = 1, $size = null) {
		//insert an empty array to mock deletion of existing output
		$this->_out[] = "";
		//append new message to output
		$this->write($message, $newlines);
	}

/**
 * Get the buffered output.
 *
 * @return array
 */
	public function messages() {
		return $this->_out;
	}
}