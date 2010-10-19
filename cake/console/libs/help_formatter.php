<?php
/**
 * A class to format help for console shells.  Can format to either
 * text or XML formats
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
class HelpFormatter {
/**
 * Build the help formatter for a an OptionParser
 *
 * @return void
 */
	public function __construct(ConsoleOptionParser $parser) {
		$this->_parser = $parser;
	}

/**
 * Get the help as text.
 *
 * @return string
 */
	public function text() {
		
	}

/**
 * Get the help as an xml string.
 *
 * @return string
 */
	public function xml() {
		
	}
}