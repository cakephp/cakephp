<?php
/**
 * ConsoleOutput  file. 
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
 * Object wrapper for outputing information from a shell application.
 * Can be connected to any stream resource that can be used with fopen()
 * 
 * Can generate colourzied output on consoles that support it. There are a few 
 * built in styles
 *
 * - `error` Error messages. Bright red text.
 * - `warning` Warning messages. Bright orange text
 * - `info` Informational messages. Cyan text.
 *
 * By defining styles with addStyle() you can create custom console styles.
 *
 * ### Using styles in output
 *
 * You can format console output using tags with the name of the style to apply. From inside a shell object
 *
 * `$this->out('<warning>Overwrite:</warning> foo.php was overwritten.');`
 *
 * This would create orange 'Overwrite:' text, while the rest of the text would remain the normal colour.
 * See ConsoleOutput::styles() to learn more about defining your own styles.
 *
 * @package cake.console
 */
class ConsoleOutput {
/**
 * File handle for output.
 *
 * @var resource
 */
	protected $_output;

/**
 * Constant for a newline.
 */
	const LF = PHP_EOL;

/**
 * text colors used in coloured output.
 *
 * @var array
 */
	protected static $_foregroundColors = array(
		'black' => 30,
		'red' => 31,
		'green' => 32,
		'yellow' => 33,
		'blue' => 34,
		'magenta' => 35,
		'cyan' => 36,
		'white' => 37
	);

/**
 * background colours used in coloured output.
 *
 * @var array
 */
	protected static $_backgroundColors = array(
		'black' => 40,
		'red' => 41,
		'green' => 42,
		'yellow' => 43,
		'blue' => 44,
		'magenta' => 45,
		'cyan' => 46,
		'white' => 47
	);

/**
 * formatting options for coloured output
 *
 * @var string
 */
	protected static $_options = array(
		'bold' => 1,
		'underscore' => 4,
		'blink' => 5,
		'reverse' => 7,
		'conceal' => 8
	);

/**
 * Styles that are available as tags in console output.
 * You can modify these styles with ConsoleOutput::styles()
 *
 * @var array
 */
	protected static $_styles = array(
		'error' => array('text' => 'red'),
		'warning' => array('text' => 'yellow'),
		'info' => array('text' => 'cyan')
	);

/**
 * Construct the output object.
 *
 * @return void
 */
	public function __construct($stream = 'php://stdout') {
		$this->_output = fopen($stream, 'w');
	}

/**
 * Outputs a single or multiple messages to stdout. If no parameters
 * are passed outputs just a newline.
 *
 * @param mixed $message A string or a an array of strings to output
 * @param integer $newlines Number of newlines to append
 * @return integer Returns the number of bytes returned from writing to stdout.
 */
	public function write($message, $newlines = 1) {
		if (is_array($message)) {
			$message = implode(self::LF, $message);
		}
		return $this->_write($message . str_repeat(self::LF, $newlines));
	}

/**
 * Writes a message to the output stream
 *
 * @param string $message Message to write.
 * @return boolean success
 */
	protected function _write($message) {
		return fwrite($this->_output, $message);
	}

/**
 * Get the current styles offered, or append new ones in.
 *
 * @param string $style The style to get or create.
 * @param mixed $definition The array definition of the style to change or create a style
 *   or false to remove a style.
 * @return mixed
 */
	function styles($style = null, $definition = null) {
		if (is_string($style) && $definition === null) {
			return isset(self::$_styles[$style]) ? self::$_styles[$style] : null;
		}
		if ($definition === false) {
			unset(self::$_styles[$style]);
			return true;
		}
		self::$_styles[$style] = $definition;
	}

/**
 * clean up and close handles
 *
 * @return void
 */
	public function __destruct() {
		fclose($this->_output);
	}
}