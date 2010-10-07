<?php
/**
 * ConsoleOptionParser file
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

/**
 * Handles parsing the ARGV in the command line and provides support 
 * for GetOpt compatible option definition.  Provides a builder pattern implementation
 * for creating shell option parsers.
 *
 * @package       cake
 * @subpackage    cake.cake.console
 */
class ConsoleOptionParser {
/**
 * Construct an OptionParser for a given ARGV array.
 *
 * ### Positional arguments
 *
 * ### Switches
 *
 * Named arguments come in two forms, long and short. Long arguments are preceeded 
 * by two - and give a more verbose option name. i.e. `--version`. Short arguments are 
 * preceeded by one - and are only one character long.  They usually match with a long option, 
 * and provide a more terse alternative.
 *
 * ### Providing Help text
 *
 * By providing help text for your positional arguments and named arguments, the ConsoleOptionParser
 * can generate a help display for you.  You can view the help for shells by using the `--help` or `-h` switch.
 *
 * @param array $args The array of arguments with the Shell/Task stripped off.
 */
	public function __construct($args) {
		
	}
}