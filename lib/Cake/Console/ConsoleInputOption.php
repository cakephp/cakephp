<?php
/**
 * ConsoleInputOption file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * An object to represent a single option used in the command line.
 * ConsoleOptionParser creates these when you use addOption()
 *
 * @see ConsoleOptionParser::addOption()
 * @package       Cake.Console
 */
class ConsoleInputOption {

/**
 * Name of the option
 *
 * @var string
 */
	protected $_name;

/**
 * Short (1 character) alias for the option.
 *
 * @var string
 */
	protected $_short;

/**
 * Help text for the option.
 *
 * @var string
 */
	protected $_help;

/**
 * Is the option a boolean option.  Boolean options do not consume a parameter.
 *
 * @var boolean
 */
	protected $_boolean;

/**
 * Default value for the option
 *
 * @var mixed
 */
	protected $_default;

/**
 * An array of choices for the option.
 *
 * @var array
 */
	protected $_choices;

/**
 * Make a new Input Option
 *
 * @param mixed $name The long name of the option, or an array with all the properties.
 * @param string $short The short alias for this option
 * @param string $help The help text for this option
 * @param boolean $boolean Whether this option is a boolean option.  Boolean options don't consume extra tokens
 * @param string $default The default value for this option.
 * @param array $choices Valid choices for this option.
 * @throws ConsoleException
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
		if (strlen($this->_short) > 1) {
			throw new ConsoleException(
				__d('cake_console', 'Short options must be one letter.')
			);
		}
	}

/**
 * Get the value of the name attribute.
 *
 * @return string Value of this->_name.
 */
	public function name() {
		return $this->_name;
	}

/**
 * Get the value of the short attribute.
 *
 * @return string Value of this->_short.
 */
	public function short() {
		return $this->_short;
	}

/**
 * Generate the help for this this option.
 *
 * @param integer $width The width to make the name of the option.
 * @return string
 */
	public function help($width = 0) {
		$default = $short = '';
		if (!empty($this->_default) && $this->_default !== true) {
			$default = __d('cake_console', ' <comment>(default: %s)</comment>', $this->_default);
		}
		if (!empty($this->_choices)) {
			$default .= __d('cake_console', ' <comment>(choices: %s)</comment>', implode('|', $this->_choices));
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
		if (!empty($this->_choices)) {
			$default = ' ' . implode('|', $this->_choices);
		}
		return sprintf('[%s%s]', $name, $default);
	}

/**
 * Get the default value for this option
 *
 * @return mixed
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
		return (bool)$this->_boolean;
	}

/**
 * Check that a value is a valid choice for this option.
 *
 * @param string $value
 * @return boolean
 * @throws ConsoleException
 */
	public function validChoice($value) {
		if (empty($this->_choices)) {
			return true;
		}
		if (!in_array($value, $this->_choices)) {
			throw new ConsoleException(
				__d('cake_console', '"%s" is not a valid value for --%s. Please use one of "%s"',
				$value, $this->_name, implode(', ', $this->_choices)
			));
		}
		return true;
	}

/**
 * Append the option's xml into the parent.
 *
 * @param SimpleXmlElement $parent The parent element.
 * @return SimpleXmlElement The parent with this option appended.
 */
	public function xml(SimpleXmlElement $parent) {
		$option = $parent->addChild('option');
		$option->addAttribute('name', '--' . $this->_name);
		$short = '';
		if (strlen($this->_short)) {
			$short = $this->_short;
		}
		$option->addAttribute('short', '-' . $short);
		$option->addAttribute('boolean', $this->_boolean);
		$option->addChild('default', $this->_default);
		$choices = $option->addChild('choices');
		foreach ($this->_choices as $valid) {
			$choices->addChild('choice', $valid);
		}
		return $parent;
	}

}
