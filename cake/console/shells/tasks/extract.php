<?php
/**
 * Language string extractor
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
 * @package       cake.console.shells.tasks
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'File');
/**
 * Language string extractor
 *
 * @package       cake.console.shells.tasks
 */
class ExtractTask extends Shell {

/**
 * Paths to use when looking for strings
 *
 * @var string
 * @access private
 */
	private $__paths = array();

/**
 * Files from where to extract
 *
 * @var array
 * @access private
 */
	private $__files = array();

/**
 * Merge all domains string into the default.pot file
 *
 * @var boolean
 * @access private
 */
	private $__merge = false;

/**
 * Current file being processed
 *
 * @var string
 * @access private
 */
	private $__file = null;

/**
 * Contains all content waiting to be write
 *
 * @var string
 * @access private
 */
	private $__storage = array();

/**
 * Extracted tokens
 *
 * @var array
 * @access private
 */
	private $__tokens = array();

/**
 * Extracted strings
 *
 * @var array
 * @access private
 */
	private $__strings = array();

/**
 * Destination path
 *
 * @var string
 * @access private
 */
	private $__output = null;

/**
 * An array of directories to exclude.
 *
 * @var array
 */
	protected $_exclude = array();

/**
 * Execution method always used for tasks
 *
 * @return void
 * @access private
 */
	function execute() {
		if (!empty($this->params['exclude'])) {
			$this->_exclude = explode(',', $this->params['exclude']);
		}
		if (isset($this->params['files']) && !is_array($this->params['files'])) {
			$this->__files = explode(',', $this->params['files']);
		}
		if (isset($this->params['paths'])) {
			$this->__paths = explode(',', $this->params['paths']);
		} else {
			$defaultPath = APP_PATH;
			$message = __("What is the full path you would like to extract?\nExample: %s\n[Q]uit [D]one", $this->Dispatch->params['root'] . DS . 'myapp');
			while (true) {
				$response = $this->in($message, null, $defaultPath);
				if (strtoupper($response) === 'Q') {
					$this->out(__('Extract Aborted'));
					$this->_stop();
				} elseif (strtoupper($response) === 'D') {
					$this->out();
					break;
				} elseif (is_dir($response)) {
					$this->__paths[] = $response;
					$defaultPath = 'D';
				} else {
					$this->err(__('The directory path you supplied was not found. Please try again.'));
				}
				$this->out();
			}
		}

		if (isset($this->params['output'])) {
			$this->__output = $this->params['output'];
		} else {
			$message = __("What is the full path you would like to output?\nExample: %s\n[Q]uit", $this->__paths[0] . DS . 'locale');
			while (true) {
				$response = $this->in($message, null, $this->__paths[0] . DS . 'locale');
				if (strtoupper($response) === 'Q') {
					$this->out(__('Extract Aborted'));
					$this->_stop();
				} elseif (is_dir($response)) {
					$this->__output = $response . DS;
					break;
				} else {
					$this->err(__('The directory path you supplied was not found. Please try again.'));
				}
				$this->out();
			}
		}

		if (isset($this->params['merge'])) {
			$this->__merge = !(strtolower($this->params['merge']) === 'no');
		} else {
			$this->out();
			$response = $this->in(__('Would you like to merge all domains strings into the default.pot file?'), array('y', 'n'), 'n');
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
		$this->out(__('Extracting...'));
		$this->hr();
		$this->out(__('Paths:'));
		foreach ($this->__paths as $path) {
			$this->out('   ' . $path);
		}
		$this->out(__('Output Directory: ') . $this->__output);
		$this->hr();
		$this->__extractTokens();
		$this->__buildFiles();
		$this->__writeFiles();
		$this->__paths = $this->__files = $this->__storage = array();
		$this->__strings = $this->__tokens = array();
		$this->out();
		$this->out(__('Done.'));
	}

/**
 * Get & configure the option parser
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(__('CakePHP Language String Extraction:'))
			->addOption('app', array('help' => __('Directory where your application is located.')))
			->addOption('paths', array('help' => __('Comma separted list of paths, full paths are needed.')))
			->addOption('merge', array(
				'help' => __('Merge all domain strings into the default.po file.'),
				'choices' => array('yes', 'no')
			))
			->addOption('output', array('help' => __('Full path to output directory.')))
			->addOption('files', array('help' => __('Comma separated list of files, full paths are needed.')))
			->addOption('exclude', array(
				'help' => __('Comma separated list of directories to exclude. Any path containing a path segment with the provided values will be skipped. E.g. test,vendors')
			));
	}

/**
 * Show help options
 *
 * @return void
 */
	public function help() {
		$this->out(__('CakePHP Language String Extraction:'));
		$this->hr();
		$this->out(__('The Extract script generates .pot file(s) with translations'));
		$this->out(__('By default the .pot file(s) will be place in the locale directory of -app'));
		$this->out(__('By default -app is ROOT/app'));
		$this->hr();
		$this->out(__('Usage: cake i18n extract <command> <param1> <param2>...'));
		$this->out();
		$this->out(__('Params:'));
		$this->out(__('   -app [path...]: directory where your application is located'));
		$this->out(__('   -root [path...]: path to install'));
		$this->out(__('   -core [path...]: path to cake directory'));
		$this->out(__('   -paths [comma separated list of paths, full path is needed]'));
		$this->out(__('   -merge [yes|no]: Merge all domains strings into the default.pot file'));
		$this->out(__('   -output [path...]: Full path to output directory'));
		$this->out(__('   -files: [comma separated list of files, full path to file is needed]'));
		$this->out();
		$this->out(__('Commands:'));
		$this->out(__('   cake i18n extract help: Shows this help message.'));
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
			$this->out(__('Processing %s...', $file));

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
				$strings = array();
				while (count($strings) < $mapCount && ($this->__tokens[$position] == ',' || $this->__tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING)) {
					if ($this->__tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING) {
						$strings[] = $this->__tokens[$position][1];
					}
					$position++;
				}

				if ($mapCount == count($strings)) {
					extract(array_combine($map, $strings));
					if (!isset($domain)) {
						$domain = '\'default\'';
					}
					$string = $this->__formatString($singular);
					if (isset($plural)) {
						$string .= "\0" . $this->__formatString($plural);
					}
					$this->__strings[$this->__formatString($domain)][$string][$this->__file][] = $line;
				} else {
					$this->__markerError($this->__file, $line, $functionName, $count);
				}
			}
			$count++;
		}
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
				$response = $this->in(__('Error: %s already exists in this location. Overwrite? [Y]es, [N]o, [A]ll', $filename), array('y', 'n', 'a'), 'y');
				if (strtoupper($response) === 'N') {
					$response = '';
					while ($response == '') {
						$response = $this->in(__("What would you like to name this file?\nExample: %s", 'new_' . $filename), null, 'new_' . $filename);
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
		$this->out(__("Invalid marker content in %s:%s\n* %s(", $file, $line, $marker), true);
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
		$pattern = false;
		if (!empty($this->_exclude)) {
			$pattern = '/[\/\\\\]' . implode('|', $this->_exclude) . '[\/\\\\]/'; 
		}
		foreach ($this->__paths as $path) {
			$Folder = new Folder($path);
			$files = $Folder->findRecursive('.*\.(php|ctp|thtml|inc|tpl)', true);
			if (!empty($pattern)) {
				foreach ($files as $i => $file) {
					if (preg_match($pattern, $file)) {
						unset($files[$i]);
					}
				}
				$files = array_values($files);
			}
			$this->__files = array_merge($this->__files, $files);
		}
	}
}
