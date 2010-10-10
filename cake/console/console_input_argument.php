<?php
/**
 * ConsoleArgumentOption file
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
 * An object to represent a single argument used in the command line.
 * ConsoleOptionParser creates these when you use addArgument()
 *
 * @see ConsoleOptionParser::addArgument()
 * @package cake.console
 */
class ConsoleInputArgument {

/**
 * Make a new Input Argument
 *
 * @param mixed $name The long name of the option, or an array with all the properites.
 * @param string $help The help text for this option
 * @param boolean $required Whether this argument is required. Missing required args will trigger exceptions
 * @param arraty $choices Valid choices for this option.
 * @return void
 */
	public function __construct($name, $help = '', $required = false, $choices = array()) {
		if (is_array($name) && isset($name['name'])) {
			foreach ($name as $key => $value) {
				$this->{$key} = $value;
			}
		} else {
			$this->name = $name;
			$this->help = $help;
			$this->required = $required;
			$this->choices = $choices;
		}
	}

/**
 * Get the name of the argument
 *
 * @return string
 */
	public function name() {
		return $this->name;
	}

/**
 * Generate the help for this this argument.
 *
 * @param int $width The width to make the name of the option.
 * @return string 
 */
	public function help($width = 0) {
		$name = $this->name;
		if (strlen($name) < $width) {
			$name = str_pad($name, $width, ' ');
		}
		$optional = '';
		if (!$this->isRequired()) {
			$optional = ' <comment>(optional)</comment>';
		}
		return sprintf('%s%s%s', $name, $this->help, $optional);
	}

/**
 * Get the usage value for this argument
 *
 * @return string
 */
	public function usage() {
		$name = $this->name;
		if (!$this->isRequired()) {
			$name = '[' . $name . ']';
		}
		return $name;
	}

/**
 * Check if this argument is a required argument
 *
 * @return boolean
 */
	public function isRequired() {
		return (bool) $this->required;
	}
}