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

	protected $_description = null;
	
	protected $_epilog = null;
	
	protected $_options = array();
	
	protected $_args = array();

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
 */
	public function __construct() {

	}

/**
 * Get or set the description text for shell/task
 *
 * @param string $text The text to set, or null if you want to read
 * @return mixed If reading, the value of the description. If setting $this will be returned
 */
	public function description($text = null) {
		if ($text !== null) {
			$this->_description = $text;
			return $this;
		}
		return $this->_description;
	}

/**
 * Get or set an epilog to the parser.  The epilog is added to the end of
 * the options and arguments listing when help is generated.
 *
 * @param string $text Text when setting or null when reading.
 * @return mixed If reading, the value of the epilog. If setting $this will be returned.
 */
	public function epilog($text = null) {
		if ($text !== null) {
			$this->_epilog = $text;
			return $this;
		}
		return $this->_epilog;
	}

/**
 * Add an option to the option parser. Options allow you to define optional or required
 * parameters for your console application. Options are defined by the parameters they use.
 *
 * ### Params
 *
 * - `shortcut` - The single letter variant for this option, leave undefined for none.
 * - `required` - Set to true to force this option to be required.  An exception will be thrown
 *    when this option is not present.
 * - `description` - Description for this option.
 * - `type` - Require a certain type. Available types are `int` and `string`.  If the options
 *   value is the wrong type an exception will be raised. Leave undefined to accept anything.
 * - `default` - The default value for this option.  If not defined the default will be null.
 * 
 * @param string $name The long name you want to the value to be parsed out as when options are parsed.
 * @param array $params An array of parameters that define the behavior of the option
 * @return returns $this.
 */
	public function addOption($name, $params = array()) {
		$defaults = array(
			'shortcut' => null,
			'required' => false,
			'description' => '',
			'type' => null,
			'default' => null
		);
		$this->_options[$name] = array_merge($defaults, $params);
		return $this;
	}

/**
 * Parse the argv array into a set of params and args.
 *
 * @param array $argv Array of args (argv) to parse
 * @return Array array($params, $args)
 */
	public function parse($argv) {
		$params = $args = array();
		
		return array($params, $args);
	}
}