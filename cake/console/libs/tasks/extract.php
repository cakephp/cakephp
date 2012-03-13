<?php
/**
 * Language string extractor
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console.libs
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Language string extractor
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 */
class ExtractTask extends Shell {

/**
 * Paths to use when looking for strings
 *
 * @var string
 * @access private
 */
	var $__paths = array();

/**
 * Files from where to extract
 *
 * @var array
 * @access private
 */
	var $__files = array();

/**
 * Merge all domains string into the default.pot file
 *
 * @var boolean
 * @access private
 */
	var $__merge = false;

/**
 * Current file being processed
 *
 * @var string
 * @access private
 */
	var $__file = null;

/**
 * Contains all content waiting to be write
 *
 * @var string
 * @access private
 */
	var $__storage = array();

/**
 * Extracted tokens
 *
 * @var array
 * @access private
 */
	var $__tokens = array();

/**
 * Extracted strings
 *
 * @var array
 * @access private
 */
	var $__strings = array();

/**
 * Destination path
 *
 * @var string
 * @access private
 */
	var $__output = null;

/**
 * Execution method always used for tasks
 *
 * @return void
 * @access private
 */
	function execute() {
		if (isset($this->params['files']) && !is_array($this->params['files'])) {
			$this->__files = explode(',', $this->params['files']);
		}
		if (isset($this->params['paths'])) {
			$this->__paths = explode(',', $this->params['paths']);
		} else {
			$defaultPath = $this->params['working'];
			$message = sprintf(__("What is the full path you would like to extract?\nExample: %s\n[Q]uit [D]one", true), $this->params['root'] . DS . 'myapp');
			while (true) {
				$response = $this->in($message, null, $defaultPath);
				if (strtoupper($response) === 'Q') {
					$this->out(__('Extract Aborted', true));
					$this->_stop();
				} elseif (strtoupper($response) === 'D') {
					$this->out();
					break;
				} elseif (is_dir($response)) {
					$this->__paths[] = $response;
					$defaultPath = 'D';
				} else {
					$this->err(__('The directory path you supplied was not found. Please try again.', true));
				}
				$this->out();
			}
		}

		if (isset($this->params['output'])) {
			$this->__output = $this->params['output'];
		} else {
			$message = sprintf(__("What is the full path you would like to output?\nExample: %s\n[Q]uit", true), $this->__paths[0] . DS . 'locale');
			while (true) {
				$response = $this->in($message, null, $this->__paths[0] . DS . 'locale');
				if (strtoupper($response) === 'Q') {
					$this->out(__('Extract Aborted', true));
					$this->_stop();
				} elseif (is_dir($response)) {
					$this->__output = $response . DS;
					break;
				} else {
					$this->err(__('The directory path you supplied was not found. Please try again.', true));
				}
				$this->out();
			}
		}

		if (isset($this->params['merge'])) {
			$this->__merge = !(strtolower($this->params['merge']) === 'no');
		} else {
			$this->out();
			$response = $this->in(sprintf(__('Would you like to merge all domains strings into the default.pot file?', true)), array('y', 'n'), 'n');
			$this->__merge = strtolower($response) === 'y';
		}

		if (empty($this->__files)) {
			$this->__searchFiles();
		}
		$this->__extract();
	}

/**
 * Extract text
 *
 * @return void
 * @access private
 */
	function __extract() {
		$this->out();
		$this->out();
		$this->out(__('Extracting...', true));
		$this->hr();
		$this->out(__('Paths:', true));
		foreach ($this->__paths as $path) {
			$this->out('   ' . $path);
		}
		$this->out(__('Output Directory: ', true) . $this->__output);
		$this->hr();
		$this->__extractTokens();
		$this->__buildFiles();
		$this->__writeFiles();
		$this->__paths = $this->__files = $this->__storage = array();
		$this->__strings = $this->__tokens = array();
		$this->out();
		$this->out(__('Done.', true));
	}

/**
 * Show help options
 *
 * @return void
 * @access public
 */
	function help() {
		$this->out(__('CakePHP Language String Extraction:', true));
		$this->hr();
		$this->out(__('The Extract script generates .pot file(s) with translations', true));
		$this->out(__('By default the .pot file(s) will be place in the locale directory of -app', true));
		$this->out(__('By default -app is ROOT/app', true));
		$this->hr();
		$this->out(__('Usage: cake i18n extract <command> <param1> <param2>...', true));
		$this->out();
		$this->out(__('Params:', true));
		$this->out(__('   -app [path...]: directory where your application is located', true));
		$this->out(__('   -root [path...]: path to install', true));
		$this->out(__('   -core [path...]: path to cake directory', true));
		$this->out(__('   -paths [comma separated list of paths, full path is needed]', true));
		$this->out(__('   -merge [yes|no]: Merge all domains strings into the default.pot file', true));
		$this->out(__('   -output [path...]: Full path to output directory', true));
		$this->out(__('   -files: [comma separated list of files, full path to file is needed]', true));
		$this->out();
		$this->out(__('Commands:', true));
		$this->out(__('   cake i18n extract help: Shows this help message.', true));
		$this->out();
	}

/**
 * Extract tokens out of all files to be processed
 *
 * @return void
 * @access private
 */
	function __extractTokens() {
		foreach ($this->__files as $file) {
			$this->__file = $file;
			$this->out(sprintf(__('Processing %s...', true), $file));

			$code = file_get_contents($file);
			$allTokens = token_get_all($code);
			$this->__tokens = array();
			$lineNumber = 1;

			foreach ($allTokens as $token) {
				if ((!is_array($token)) || (($token[0] != T_WHITESPACE) && ($token[0] != T_INLINE_HTML))) {
					if (is_array($token)) {
						$token[] = $lineNumber;
					}
					$this->__tokens[] = $token;
				}

				if (is_array($token)) {
					$lineNumber += count(explode("\n", $token[1])) - 1;
				} else {
					$lineNumber += count(explode("\n", $token)) - 1;
				}
			}
			unset($allTokens);
			$this->__parse('__', array('singular'));
			$this->__parse('__n', array('singular', 'plural'));
			$this->__parse('__d', array('domain', 'singular'));
			$this->__parse('__c', array('singular'));
			$this->__parse('__dc', array('domain', 'singular'));
			$this->__parse('__dn', array('domain', 'singular', 'plural'));
			$this->__parse('__dcn', array('domain', 'singular', 'plural'));
		}
	}

/**
 * Parse tokens
 *
 * @param string $functionName Function name that indicates translatable string (e.g: '__')
 * @param array $map Array containing what variables it will find (e.g: domain, singular, plural)
 * @return void
 * @access private
 */
	function __parse($functionName, $map) {
		$count = 0;
		$tokenCount = count($this->__tokens);

		while (($tokenCount - $count) > 1) {
			list($countToken, $firstParenthesis) = array($this->__tokens[$count], $this->__tokens[$count + 1]);
			if (!is_array($countToken)) {
				$count++;
				continue;
			}

			list($type, $string, $line) = $countToken;
			if (($type == T_STRING) && ($string == $functionName) && ($firstParenthesis == '(')) {
				$position = $count;
				$depth = 0;

				while ($depth == 0) {
					if ($this->__tokens[$position] == '(') {
						$depth++;
					} elseif ($this->__tokens[$position] == ')') {
						$depth--;
					}
					$position++;
				}

				$mapCount = count($map);
				$strings = $this->__getStrings($position, $mapCount);

				if ($mapCount == count($strings)) {
					extract(array_combine($map, $strings));
					$domain = isset($domain) ? $domain : 'default';
					$string = isset($plural) ? $singular . "\0" . $plural : $singular;
					$this->__strings[$domain][$string][$this->__file][] = $line;
				} else {
					$this->__markerError($this->__file, $line, $functionName, $count);
				}
			}
			$count++;
		}
	}

/**
* Get the strings from the position forward
*
* @param integer $position Actual position on tokens array
* @param integer $target Number of strings to extract
* @return array Strings extracted
*/
	function __getStrings($position, $target) {
		$strings = array();
		while (count($strings) < $target && ($this->__tokens[$position] == ',' || $this->__tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING)) {
			$condition1 = ($this->__tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING && $this->__tokens[$position+1] == '.');
			$condition2 = ($this->__tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING && $this->__tokens[$position+1][0] == T_COMMENT);
			if ($condition1	|| $condition2) {
				$string = '';
				while ($this->__tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING || $this->__tokens[$position][0] == T_COMMENT || $this->__tokens[$position] == '.') {
					if ($this->__tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING) {
						$string .= $this->__formatString($this->__tokens[$position][1]);
					}
					$position++;
				}
				if ($this->__tokens[$position][0] == T_COMMENT || $this->__tokens[$position] == ',' || $this->__tokens[$position] == ')') {
					$strings[] = $string;
				}
			} else if ($this->__tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING) {
				$strings[] = $this->__formatString($this->__tokens[$position][1]);
			}
			$position++;
		}
		return $strings;
	}
	
/**
 * Build the translate template file contents out of obtained strings
 *
 * @return void
 * @access private
 */
	function __buildFiles() {
		foreach ($this->__strings as $domain => $strings) {
			foreach ($strings as $string => $files) {
				$occurrences = array();
				foreach ($files as $file => $lines) {
					$occurrences[] = $file . ':' . implode(';', $lines);
				}
				$occurrences = implode("\n#: ", $occurrences);
				$header = '#: ' . str_replace($this->__paths, '', $occurrences) . "\n";

				if (strpos($string, "\0") === false) {
					$sentence = "msgid \"{$string}\"\n";
					$sentence .= "msgstr \"\"\n\n";
				} else {
					list($singular, $plural) = explode("\0", $string);
					$sentence = "msgid \"{$singular}\"\n";
					$sentence .= "msgid_plural \"{$plural}\"\n";
					$sentence .= "msgstr[0] \"\"\n";
					$sentence .= "msgstr[1] \"\"\n\n";
				}

				$this->__store($domain, $header, $sentence);
				if ($domain != 'default' && $this->__merge) {
					$this->__store('default', $header, $sentence);
				}
			}
		}
	}

/**
 * Prepare a file to be stored
 *
 * @return void
 * @access private
 */
	function __store($domain, $header, $sentence) {
		if (!isset($this->__storage[$domain])) {
			$this->__storage[$domain] = array();
		}
		if (!isset($this->__storage[$domain][$sentence])) {
			$this->__storage[$domain][$sentence] = $header;
		} else {
			$this->__storage[$domain][$sentence] .= $header;
		}
	}

/**
 * Write the files that need to be stored
 *
 * @return void
 * @access private
 */
	function __writeFiles() {
		$overwriteAll = false;
		foreach ($this->__storage as $domain => $sentences) {
			$output = $this->__writeHeader();
			foreach ($sentences as $sentence => $header) {
				$output .= $header . $sentence;
			}

			$filename = $domain . '.pot';
			$File = new File($this->__output . $filename);
			$response = '';
			while ($overwriteAll === false && $File->exists() && strtoupper($response) !== 'Y') {
				$this->out();
				$response = $this->in(sprintf(__('Error: %s already exists in this location. Overwrite? [Y]es, [N]o, [A]ll', true), $filename), array('y', 'n', 'a'), 'y');
				if (strtoupper($response) === 'N') {
					$response = '';
					while ($response == '') {
						$response = $this->in(sprintf(__("What would you like to name this file?\nExample: %s", true), 'new_' . $filename), null, 'new_' . $filename);
						$File = new File($this->__output . $response);
						$filename = $response;
					}
				} elseif (strtoupper($response) === 'A') {
					$overwriteAll = true;
				}
			}
			$File->write($output);
			$File->close();
		}
	}

/**
 * Build the translation template header
 *
 * @return string Translation template header
 * @access private
 */
	function __writeHeader() {
		$output  = "# LANGUAGE translation of CakePHP Application\n";
		$output .= "# Copyright YEAR NAME <EMAIL@ADDRESS>\n";
		$output .= "#\n";
		$output .= "#, fuzzy\n";
		$output .= "msgid \"\"\n";
		$output .= "msgstr \"\"\n";
		$output .= "\"Project-Id-Version: PROJECT VERSION\\n\"\n";
		$output .= "\"POT-Creation-Date: " . date("Y-m-d H:iO") . "\\n\"\n";
		$output .= "\"PO-Revision-Date: YYYY-mm-DD HH:MM+ZZZZ\\n\"\n";
		$output .= "\"Last-Translator: NAME <EMAIL@ADDRESS>\\n\"\n";
		$output .= "\"Language-Team: LANGUAGE <EMAIL@ADDRESS>\\n\"\n";
		$output .= "\"MIME-Version: 1.0\\n\"\n";
		$output .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
		$output .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
		$output .= "\"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n\"\n\n";
		return $output;
	}

/**
 * Format a string to be added as a translateable string
 *
 * @param string $string String to format
 * @return string Formatted string
 * @access private
 */
	function __formatString($string) {
		$quote = substr($string, 0, 1);
		$string = substr($string, 1, -1);
		if ($quote == '"') {
			$string = stripcslashes($string);
		} else {
			$string = strtr($string, array("\\'" => "'", "\\\\" => "\\"));
		}
		$string = str_replace("\r\n", "\n", $string);
		return addcslashes($string, "\0..\37\\\"");
	}

/**
 * Indicate an invalid marker on a processed file
 *
 * @param string $file File where invalid marker resides
 * @param integer $line Line number
 * @param string $marker Marker found
 * @param integer $count Count
 * @return void
 * @access private
 */
	function __markerError($file, $line, $marker, $count) {
		$this->out(sprintf(__("Invalid marker content in %s:%s\n* %s(", true), $file, $line, $marker), true);
		$count += 2;
		$tokenCount = count($this->__tokens);
		$parenthesis = 1;

		while ((($tokenCount - $count) > 0) && $parenthesis) {
			if (is_array($this->__tokens[$count])) {
				$this->out($this->__tokens[$count][1], false);
			} else {
				$this->out($this->__tokens[$count], false);
				if ($this->__tokens[$count] == '(') {
					$parenthesis++;
				}

				if ($this->__tokens[$count] == ')') {
					$parenthesis--;
				}
			}
			$count++;
		}
		$this->out("\n", true);
	}

/**
 * Search files that may contain translateable strings
 *
 * @return void
 * @access private
 */
	function __searchFiles() {
		foreach ($this->__paths as $path) {
			$Folder = new Folder($path);
			$files = $Folder->findRecursive('.*\.(php|ctp|thtml|inc|tpl)', true);
			$this->__files = array_merge($this->__files, $files);
		}
	}
}
