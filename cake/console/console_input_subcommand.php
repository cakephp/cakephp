<?php
/**
 * ConsoleInputSubcommand file
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
 * An object to represent a single subcommand used in the command line.
 * ConsoleOptionParser creates these when you use addSubcommand()
 *
 * @see ConsoleOptionParser::addSubcommand()
 * @package cake.console
 */
class ConsoleInputSubcommand {

/**
 * Make a new Subcommand
 *
 * @param mixed $name The long name of the subcommand, or an array with all the properites.
 * @param string $help The help text for this option
 * @param ConsoleOptionParser $parser A parser for this subcommand.
 * @return void
 */
	public function __construct($name, $help = '', $parser = null) {
		if (is_array($name) && isset($name['name'])) {
			foreach ($name as $key => $value) {
				$this->{$key} = $value;
			}
		} else {
			$this->name = $name;
			$this->help = $help;
			$this->parser = $parser;
		}
	}

/**
 * Get the name of the subcommand
 *
 * @return string
 */
	public function name() {
		return $this->name;
	}

/**
 * Generate the help for this this subcommand.
 *
 * @param int $width The width to make the name of the subcommand.
 * @return string 
 */
	public function help($width = 0) {
		$name = $this->name;
		if (strlen($name) < $width) {
			$name = str_pad($name, $width, ' ');
		}
		return $name . $this->help;
	}

/**
 * Get the usage value for this option
 *
 * @return string
 */
	public function parser() {
		if ($this->parser instanceof ConsoleOptionParser) {
			return $this->parser;
		}
		return false;
	}
}
