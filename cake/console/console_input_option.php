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

	protected $_name, $_short, $_help, $_boolean, $_default, $_choices;

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
				$this->{'_' . $key} = $value;
			}
		} else {
			$this->_name = $name;
			$this->_short = $short;
			$this->_help = $help;
			$this->_boolean = $boolean;
			$this->_default = $default;
			$this->_choices = $choices;
		}
	}

/**
 * Get the name of the argument
 *
 * @return string
 */
	public function name() {
		return $this->_name;
	}

/**
 * Generate the help for this this option.
 *
 * @param int $width The width to make the name of the option.
 * @return string 
 */
	public function help($width = 0) {
		$default = $short = '';
		if (!empty($this->_default) && $this->_default !== true) {
			$default = sprintf(__(' <comment>(default: %s)</comment>'), $this->_default);
		}
		if (!empty($this->_short)) {
			$short = ', -' . $this->_short;
		}
		$name = sprintf('--%s%s', $this->_name, $short);
		if (strlen($name) < $width) {
			$name = str_pad($name, $width, ' ');
		}
		return sprintf('%s%s%s', $name, $this->_help, $default);
	}

/**
 * Get the usage value for this option
 *
 * @return string
 */
	public function usage() {
		$name = empty($this->_short) ? '--' . $this->_name : '-' . $this->_short;
		$default = '';
		if (!empty($this->_default) && $this->_default !== true) {
			$default = ' ' . $this->_default;
		}
		return sprintf('[%s%s]', $name, $default);
	}

/**
 * Get the default value for this option
 *
 * @return void
 */
	public function defaultValue() {
		return $this->_default;
	}

/**
 * Check if this option is a boolean option
 *
 * @return boolean
 */
	public function isBoolean() {
		return (bool) $this->_boolean;
	}

/**
 * Check that a value is a valid choice for this option.
 *
 * @return boolean
 */
	public function validChoice($value) {
		if (empty($this->_choices)) {
			return true;
		}
		if (!in_array($value, $this->_choices)) {
			throw new InvalidArgumentException(
				sprintf(__('"%s" is not a valid value for --%s'), $value, $this->_name)
			);
		}
		return true;
	}
}
