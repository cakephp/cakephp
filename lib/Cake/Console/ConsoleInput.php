<?php
/**
 * ConsoleInput file.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Console
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Object wrapper for interacting with stdin
 *
 * @package       Cake.Console
 */
class ConsoleInput {

/**
 * Input value.
 *
 * @var resource
 */
	protected $_input;

/**
 * Can this instance use readline?
 * Two conditions must be met:
 * 1. Readline support must be enabled.
 * 2. Handle we are attached to must be stdin.
 * Allows rich editing with arrow keys and history when inputting a string.
 *
 * @var bool
 */
	protected $_canReadline;

/**
 * Constructor
 *
 * @param string $handle The location of the stream to use as input.
 */
	public function __construct($handle = 'php://stdin') {
		$this->_canReadline = extension_loaded('readline') && $handle === 'php://stdin' ? true : false;
		$this->_input = fopen($handle, 'r');
	}

/**
 * Read a value from the stream
 *
 * @return mixed The value of the stream
 */
	public function read() {
		if ($this->_canReadline) {
			$line = readline('');
			if (!empty($line)) {
				readline_add_history($line);
			}
			return $line;
		}
		return fgets($this->_input);
	}

/**
 * Checks if data is available on the stream
 *
 * @param int $timeout An optional time to wait for data
 * @return bool True for data available, false otherwise
 */
	public function dataAvailable($timeout = 0) {
		$readFds = array($this->_input);
		$readyFds = stream_select($readFds, $writeFds, $errorFds, $timeout);
		return ($readyFds > 0);
	}

}
