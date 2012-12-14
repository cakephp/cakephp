<?php
/**
 * ConsoleOutput file.
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
 * Object wrapper for outputting information from a shell application.
 * Can be connected to any stream resource that can be used with fopen()
 *
 * Can generate colorized output on consoles that support it. There are a few
 * built in styles
 *
 * - `error` Error messages.
 * - `warning` Warning messages.
 * - `info` Informational messages.
 * - `comment` Additional text.
 * - `question` Magenta text used for user prompts
 *
 * By defining styles with addStyle() you can create custom console styles.
 *
 * ### Using styles in output
 *
 * You can format console output using tags with the name of the style to apply. From inside a shell object
 *
 * `$this->out('<warning>Overwrite:</warning> foo.php was overwritten.');`
 *
 * This would create orange 'Overwrite:' text, while the rest of the text would remain the normal color.
 * See ConsoleOutput::styles() to learn more about defining your own styles.  Nested styles are not supported
 * at this time.
 *
 * @package       Cake.Console
 */
class ConsoleOutput {

/**
 * Raw output constant - no modification of output text.
 */
	const RAW = 0;

/**
 * Plain output - tags will be stripped.
 */
	const PLAIN = 1;

/**
 * Color output - Convert known tags in to ANSI color escape codes.
 */
	const COLOR = 2;

/**
 * Constant for a newline.
 */
	const LF = PHP_EOL;

/**
 * File handle for output.
 *
 * @var resource
 */
	protected $_output;

/**
 * The current output type. Manipulated with ConsoleOutput::outputAs();
 *
 * @var integer
 */
	protected $_outputAs = self::COLOR;

/**
 * text colors used in colored output.
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
 * background colors used in colored output.
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
 * formatting options for colored output
 *
 * @var string
 */
	protected static $_options = array(
		'bold' => 1,
		'underline' => 4,
		'blink' => 5,
		'reverse' => 7,
	);

/**
 * Styles that are available as tags in console output.
 * You can modify these styles with ConsoleOutput::styles()
 *
 * @var array
 */
	protected static $_styles = array(
		'emergency' => array('text' => 'red', 'underline' => true),
		'alert' => array('text' => 'red', 'underline' => true),
		'critical' => array('text' => 'red', 'underline' => true),
		'error' => array('text' => 'red', 'underline' => true),
		'warning' => array('text' => 'yellow'),
		'info' => array('text' => 'cyan'),
		'debug' => array('text' => 'yellow'),
		'success' => array('text' => 'green'),
		'comment' => array('text' => 'blue'),
		'question' => array('text' => 'magenta'),
	);

/**
 * Construct the output object.
 *
 * Checks for a pretty console environment. Ansicon allows pretty consoles
 * on windows, and is supported.
 *
 * @param string $stream The identifier of the stream to write output to.
 */
	public function __construct($stream = 'php://stdout') {
		$this->_output = fopen($stream, 'w');

		if (DS == '\\' && !(bool)env('ANSICON')) {
			$this->_outputAs = self::PLAIN;
		}
	}

/**
 * Outputs a single or multiple messages to stdout. If no parameters
 * are passed, outputs just a newline.
 *
 * @param string|array $message A string or a an array of strings to output
 * @param integer $newlines Number of newlines to append
 * @return integer Returns the number of bytes returned from writing to stdout.
 */
	public function write($message, $newlines = 1) {
		if (is_array($message)) {
			$message = implode(self::LF, $message);
		}
		return $this->_write($this->styleText($message . str_repeat(self::LF, $newlines)));
	}

/**
 * Apply styling to text.
 *
 * @param string $text Text with styling tags.
 * @return string String with color codes added.
 */
	public function styleText($text) {
		if ($this->_outputAs == self::RAW) {
			return $text;
		}
		if ($this->_outputAs == self::PLAIN) {
			$tags = implode('|', array_keys(self::$_styles));
			return preg_replace('#</?(?:' . $tags . ')>#', '', $text);
		}
		return preg_replace_callback(
			'/<(?P<tag>[a-z0-9-_]+)>(?P<text>.*?)<\/(\1)>/ims', array($this, '_replaceTags'), $text
		);
	}

/**
 * Replace tags with color codes.
 *
 * @param array $matches.
 * @return string
 */
	protected function _replaceTags($matches) {
		$style = $this->styles($matches['tag']);
		if (empty($style)) {
			return '<' . $matches['tag'] . '>' . $matches['text'] . '</' . $matches['tag'] . '>';
		}

		$styleInfo = array();
		if (!empty($style['text']) && isset(self::$_foregroundColors[$style['text']])) {
			$styleInfo[] = self::$_foregroundColors[$style['text']];
		}
		if (!empty($style['background']) && isset(self::$_backgroundColors[$style['background']])) {
			$styleInfo[] = self::$_backgroundColors[$style['background']];
		}
		unset($style['text'], $style['background']);
		foreach ($style as $option => $value) {
			if ($value) {
				$styleInfo[] = self::$_options[$option];
			}
		}
		return "\033[" . implode($styleInfo, ';') . 'm' . $matches['text'] . "\033[0m";
	}

/**
 * Writes a message to the output stream.
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
 * ### Get a style definition
 *
 * `$this->output->styles('error');`
 *
 * ### Get all the style definitions
 *
 * `$this->output->styles();`
 *
 * ### Create or modify an existing style
 *
 * `$this->output->styles('annoy', array('text' => 'purple', 'background' => 'yellow', 'blink' => true));`
 *
 * ### Remove a style
 *
 * `$this->output->styles('annoy', false);`
 *
 * @param string $style The style to get or create.
 * @param array $definition The array definition of the style to change or create a style
 *   or false to remove a style.
 * @return mixed If you are getting styles, the style or null will be returned. If you are creating/modifying
 *   styles true will be returned.
 */
	public function styles($style = null, $definition = null) {
		if ($style === null && $definition === null) {
			return self::$_styles;
		}
		if (is_string($style) && $definition === null) {
			return isset(self::$_styles[$style]) ? self::$_styles[$style] : null;
		}
		if ($definition === false) {
			unset(self::$_styles[$style]);
			return true;
		}
		self::$_styles[$style] = $definition;
		return true;
	}

/**
 * Get/Set the output type to use.  The output type how formatting tags are treated.
 *
 * @param integer $type The output type to use.  Should be one of the class constants.
 * @return mixed Either null or the value if getting.
 */
	public function outputAs($type = null) {
		if ($type === null) {
			return $this->_outputAs;
		}
		$this->_outputAs = $type;
	}

/**
 * clean up and close handles
 *
 */
	public function __destruct() {
		fclose($this->_output);
	}

}
