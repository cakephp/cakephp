<?php
/**
 * ConsoleInput file.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Console
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
 * Constructor
 *
 * @param string $handle The location of the stream to use as input.
 */
	public function __construct($handle = 'php://stdin') {
		$this->_input = fopen($handle, 'r');
	}

/**
 * Read a value from the stream
 *
 * @return mixed The value of the stream
 */
	public function read() {
		return fgets($this->_input);
	}

}
