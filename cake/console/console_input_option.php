<?php
/**
 * ConsoleInputOption file
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
 * An object to represent a single option used in the command line.
 * ConsoleOptionParser creates these when you use addOption()
 *
 * @see ConsoleOptionParser::addOption()
 * @package cake.console
 */
class ConsoleInputOption {

/**
 * Make a new Input Option
 *
 * @param mixed $name The long name of the option, or an array with all the properites.
 * @param string $short The short alias for this option
 * @param string $help The help text for this option
 * @param boolean $boolean Whether this option is a boolean option.  Boolean options don't consume extra tokens
 * @param string $default The default value for this option.
 * @param arraty $choices Valid choices for this option.
 * @return void
 */
	public function __construct($name, $short = null, $help = '', $boolean = false, $default = '', $choices = array()) {
		if (is_array($name) && isset($name['name'])) {
			foreach ($name as $key => $value) {
				$this->{$key} = $value;
			}
		} else {
			$this->name = $name;
			$this->short = $short;
			$this->help = $help;
			$this->boolean = $boolean;
			$this->default = $default;
			$this->choices = $choices;
		}
	}

/**
 * Generate the help for this this option.
 *
 * @param int $width The width to make the name of the option.
 * @return string 
 */
	public function help($width = 0) {
		$default = $short = '';
		if (!empty($this->default) && $this->default !== true) {
			$default = sprintf(__(' <comment>(default: %s)</comment>'), $this->default);
		}
		if (!empty($this->short)) {
			$short = ', -' . $this->short;
		}
		$name = sprintf('--%s%s', $this->name, $short);
		if (strlen($name) < $width) {
			$name = str_pad($name, $width, ' ');
		}
		return sprintf('%s%s%s', $name, $this->help, $default);
	}

/**
 * Get the usage value for this option
 *
 * @return string
 */
	public function usage() {
		$name = empty($this->short) ? '--' . $this->name : '-' . $this->short;
		$default = '';
		if (!empty($this->default) && $this->default !== true) {
			$default = ' ' . $this->default;
		}
		return sprintf('[%s%s]', $name, $default);
	}

/**
 * Get the default value for this option
 *
 * @return void
 */
	public function defaultValue() {
		return $this->default;
	}

/**
 * Check if this option is a boolean option
 *
 * @return boolean
 */
	public function isBoolean() {
		return (bool) $this->boolean;
	}
}
