<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *                              1785 E. Sahara Avenue, Suite 490-204
 *                              Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright       Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link                http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package         cake
 * @subpackage      cake.cake.console.libs
 * @since           CakePHP(tm) v 1.2.0.5012
 * @version         $Revision$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Only used when -debug option
 */
return null;
	$singularReturn = __('Singular string  return __()', true);
	$singularEcho = __('Singular string  echo __()');

	$pluralReturn = __n('% apple in the bowl (plural string return __n())', '% apples in the blowl (plural string 2 return __n())', 3, true);
	$pluralEcho = __n('% apple in the bowl (plural string 2 echo __n())', '% apples in the blowl (plural string 2 echo __n()', 3);

	$singularDomainReturn = __d('controllers', 'Singular string domain lookup return __d()', true);
	$singularDomainEcho = __d('controllers', 'Singular string domain lookup echo __d()');

	$pluralDomainReturn = __dn('controllers', '% pears in the bowl (plural string domain lookup return __dn())', '% pears in the blowl (plural string domain lookup return __dn())', 3, true);
	$pluralDomainEcho = __dn('controllers', '% pears in the bowl (plural string domain lookup echo __dn())', '% pears in the blowl (plural string domain lookup echo __dn())', 3);

	$singularDomainCategoryReturn = __dc('controllers', 'Singular string domain and category lookup return __dc()', 5, true);
	$singularDomainCategoryEcho = __dc('controllers', 'Singular string domain and category lookup echo __dc()', 5);

	$pluralDomainCategoryReturn = __dcn('controllers', '% apple in the bowl (plural string 1 domain and category lookup return __dcn())', '% apples in the blowl (plural string 2 domain and category lookup return __dcn())', 3, 5, true);
	$pluralDomainCategoryEcho = __dcn('controllers', '% apple in the bowl (plural string 1 domain and category lookup echo __dcn())', '% apples in the blowl (plural string 2 domain and category lookup echo __dcn())', 3, 5);

	$categoryReturn = __c('Category string lookup line return __c()', 5, true);
	$categoryEcho = __c('Category string  lookup line echo __c()', 5);
/**
 * Language string extractor
 *
 * @package     cake
 * @subpackage  cake.cake.console.libs
 */
class ExtractShell extends Shell{
	var $path = null;
	var $files = array();

	var $__filename = 'default';
	var $__oneFile = true;
	var $__file = null;
	var $__tokens = array();
	var $__strings = array();
	var $__fileVersions = array();
	var $__output = null;

	function initialize() {
		if (isset($this->params['files']) && !is_array($this->params['files'])){
			$this->files = explode(',', $this->params['files']);
		}

		if (isset($this->params['path'])) {
			$this->path = $this->params['path'];
		} else {
			$this->path = ROOT . DS . APP_DIR;
		}

		if (isset($this->params['debug'])) {
			$this->path = ROOT;
			$this->files = array(__FILE__);
		}

		if (isset($this->params['output'])) {
			$this->__output = $this->params['output'];
		} else {
			$this->__output = APP . 'locale' . DS;
		}

		if (empty($this->files)){
			$this->files = $this->__searchDirectory();
		}
	}
	function main() {
		$this->out('');
		$this->out('');
		$this->out('Extracting...');
		$this->hr();
		$this->out('Path: '. $this->path);
		$this->out('Output Directory: '. $this->__output);
		$this->hr();

		$response = '';
		$filename = '';
		while ($response == '') {
			$response = $this->in('Would you like to merge all translations into one file?', array('y','n'), 'y');
			if (strtolower($response) == 'n') {
				$this->__oneFile = false;
			} else {
				while ($filename == '') {
					$filename = $this->in('What should we name this file?', null, $this->__filename);
					if ($filename == '') {
						$this->out('The filesname you supplied was empty. Please try again.');
					}
				}
				$this->__filename = $filename;
			}
		}
		$this->__extractTokens();
	}
	function help() {
		$this->out('CakePHP Language String Extraction:');
		$this->hr();
		$this->out('The Extract script generates .pot file(s) with translations');
		$this->out('By default the .pot file(s) will be place in the locale directory of -app');
		$this->out('By default -app is ROOT/app');
		$this->out('');
		$this->hr('');
		$this->out('usage: php extract.php [command] [path...]');
		$this->out('');
		$this->out('commands:');
		$this->out('   -app [path...]: directory where your application is located');
		$this->out('   -root [path...]: path to install');
		$this->out('   -core [path...]: path to cake directory');
		$this->out('   -path [path...]: Full path to directory to extract strings');
		$this->out('   -output [path...]: Full path to output directory');
		$this->out('   -files: [comma separated list of files, full path to file is needed]');
		$this->out('   cake extract help: Shows this help message.');
		$this->out('   -debug: Perform self test.');
		$this->out('');
	}
	function __extractTokens(){
		foreach ($this->files as $file) {
			$this->__file = $file;
			$this->out("Processing $file...");

			$code = file_get_contents($file);

			$this->__findVersion($code, $file);
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
					$lineNumber += count(split("\n", $token[1])) - 1;
				} else {
					$lineNumber += count(split("\n", $token)) - 1;
				}
			}
			unset($allTokens);
			$this->basic();
			$this->basic('__c');
			$this->extended();
			$this->extended('__dc', 2);
			$this->extended('__n', 0, true);
			$this->extended('__dn', 2, true);
			$this->extended('__dcn', 4, true);
		}
		$this->__buildFiles();
		$this->__writeFiles();
		$this->out('Done.');
	}
/**
 * Will parse  __(), __c() functions
 *
 * @param string $functionname
 */
	function basic($functionname = '__') {
		$count = 0;
		$tokenCount = count($this->__tokens);

		while (($tokenCount - $count) > 3) {
			list($countToken, $parenthesis, $middle, $right) = array($this->__tokens[$count], $this->__tokens[$count + 1], $this->__tokens[$count + 2], $this->__tokens[$count + 3]);
			if (!is_array($countToken)) {
				$count++;
				continue;
			}

			list($type, $string, $line) = $countToken;
			if (($type == T_STRING) && ($string == $functionname) && ($parenthesis == "(")) {

				if (in_array($right, array(")", ","))
				&& (is_array($middle) && ($middle[0] == T_CONSTANT_ENCAPSED_STRING))) {

					if ($this->__oneFile === true) {
						$this->__strings[$this->__formatString($middle[1])][$this->__file][] = $line;
					} else {
						$this->__strings[$this->__file][$this->__formatString($middle[1])][] = $line;
					}
				} else {
					$this->__markerError($this->__file, $line, $functionname, $count);
				}
			}
			$count++;
		}
	}
/**
 * Will parse __d(), __dc(), __n(), __dn(), __dcn()
 *
 * @param string $functionname
 * @param integer $shift
 * @param boolean $plural
 */
	function extended($functionname = '__d', $shift = 0, $plural = false) {
		$count = 0;
		$tokenCount = count($this->__tokens);

		while (($tokenCount - $count) > 7) {
			list($countToken, $firstParenthesis) = array($this->__tokens[$count], $this->__tokens[$count + 1]);
			if (!is_array($countToken)) {
				$count++;
				continue;
			}

			list($type, $string, $line) = $countToken;
			if (($type == T_STRING) && ($string == $functionname) && ($firstParenthesis == "(")) {
				$position = $count;
				$depth = 0;

				while ($depth == 0) {
					if ($this->__tokens[$position] == "(") {
						$depth++;
					} elseif ($this->__tokens[$position] == ")") {
						$depth--;
					}
					$position++;
				}

				if ($plural) {
					$end = $position + $shift + 7;

					if ($this->__tokens[$position + $shift + 5] === ')') {
						$end = $position + $shift + 5;
					}

					if (empty($shift)) {
						list($singular, $firstComma, $plural, $seoncdComma, $endParenthesis) = array($this->__tokens[$position], $this->__tokens[$position + 1], $this->__tokens[$position + 2], $this->__tokens[$position + 3], $this->__tokens[$end]);
						$condition = ($seoncdComma == ",");
					} else {
						list($domain, $firstComma, $singular, $seoncdComma, $plural, $comma3, $endParenthesis) = array($this->__tokens[$position], $this->__tokens[$position + 1], $this->__tokens[$position + 2], $this->__tokens[$position + 3], $this->__tokens[$position + 4], $this->__tokens[$position + 5], $this->__tokens[$end]);
						$condition = ($comma3 == ",");
					}
					$condition = $condition &&
						(is_array($singular) && ($singular[0] == T_CONSTANT_ENCAPSED_STRING)) &&
						(is_array($plural) && ($plural[0] == T_CONSTANT_ENCAPSED_STRING));
				} else {
					if ($this->__tokens[$position + $shift + 5] === ')') {
						$comma = $this->__tokens[$position + $shift + 3];
						$end = $position + $shift + 5;
					} else {
						$comma = null;
						$end = $position + $shift + 3;
					}

					list($domain, $firstComma, $text, $seoncdComma, $endParenthesis) = array($this->__tokens[$position], $this->__tokens[$position + 1], $this->__tokens[$position + 2], $comma, $this->__tokens[$end]);
					$condition = ($seoncdComma == "," || $seoncdComma === null) &&
						(is_array($domain) && ($domain[0] == T_CONSTANT_ENCAPSED_STRING)) &&
						(is_array($text) && ($text[0] == T_CONSTANT_ENCAPSED_STRING));
				}

				if (($endParenthesis == ")") && $condition) {
					if ($this->__oneFile === true) {
						if ($plural) {
							$this->__strings[$this->__formatString($singular[1]) . "\0" . $this->__formatString($plural[1])][$this->__file][] = $line;
						} else {
							$this->__strings[$this->__formatString($text[1])][$this->__file][] = $line;
						}
					} else {
						if ($plural) {
							$this->__strings[$this->__file][$this->__formatString($singular[1]) . "\0" . $this->__formatString($plural[1])][] = $line;
						} else {
							$this->__strings[$this->__file][$this->__formatString($text[1])][] = $line;
						}
					}
				} else {
					$this->__markerError($this->__file, $line, $functionname, $count);
				}
			}
			$count++;
		}
	}
	function __buildFiles() {
		$output = '';
		foreach ($this->__strings as $str => $fileInfo) {
			$occured = $fileList = array();

			if ($this->__oneFile === true) {
				foreach ($fileInfo as $file => $lines) {
					$occured[] = "$file:" . join(";", $lines);

					if (isset($this->__fileVersions[$file])) {
						$fileList[] = $this->__fileVersions[$file];
					}
				}
				$occurances = join("\n#: ", $occured);
				$occurances = str_replace($this->path, '', $occurances);
				$output = "#: $occurances\n";
				$filename = $this->__filename;

				if (strpos($str, "\0") === false) {
					$output .= "msgid \"$str\"\n";
					$output .= "msgstr \"\"\n";
				} else {
					list($singular, $plural) = explode("\0", $str);
					$output .= "msgid \"$singular\"\n";
					$output .= "msgid_plural \"$plural\"\n";
					$output .= "msgstr[0] \"\"\n";
					$output .= "msgstr[1] \"\"\n";
				}
				$output .= "\n";
			} else {
				foreach ($fileInfo as $file => $lines) {
					$filename = $str;
					$occured = array("$str:" . join(";", $lines));

					if (isset($this->__fileVersions[$str])) {
						$fileList[] = $this->__fileVersions[$str];
					}
					$occurances = join("\n#: ", $occured);
					$occurances = str_replace($this->path, '', $occurances);
					$output .= "#: $occurances\n";

					if (strpos($file, "\0") === false) {
						$output .= "msgid \"$file\"\n";
						$output .= "msgstr \"\"\n";
					} else {
						list($singular, $plural) = explode("\0", $file);
						$output .= "msgid \"$singular\"\n";
						$output .= "msgid_plural \"$plural\"\n";
						$output .= "msgstr[0] \"\"\n";
						$output .= "msgstr[1] \"\"\n";
					}
					$output .= "\n";
				}
			}
			$this->__store($filename, $output, $fileList);
		}
	}
	function __store($file = 0, $input = 0, $fileList = array(), $get = 0) {
		static $storage = array();

		if (!$get) {
			if (isset($storage[$file])) {
				$storage[$file][1] = array_unique(array_merge($storage[$file][1], $fileList));
				$storage[$file][] = $input;
			} else {
				$storage[$file] = array();
				$storage[$file][0] = $this->__writeHeader();
				$storage[$file][1] = $fileList;
				$storage[$file][2] = $input;
			}
		} else {
			return $storage;
		}
	}
	function __writeFiles() {
		$output = $this->__store(0, 0, array(), 1);
		$output = $this->__mergeFiles($output);

		foreach ($output as $file => $content) {
			$tmp = str_replace(array($this->path, '.php','.ctp','.thtml', '.inc','.tpl' ), '', $file);
			$tmp = str_replace(DS, '.', $tmp);
			$file = str_replace('.', '-', $tmp) .'.pot';
			$fileList = $content[1];

			unset($content[1]);

			$fileList = str_replace(array($this->path), '', $fileList);

			if (count($fileList) > 1) {
				$fileList = "Generated from files:\n#  " . join("\n#  ", $fileList);
			} elseif (count($fileList) == 1) {
				$fileList = "Generated from file: " . join("", $fileList);
			} else {
				$fileList = "No version information was available in the source files.";
			}
			$fp = fopen($this->__output . $file, 'w');
			fwrite($fp, str_replace("--VERSIONS--", $fileList, join("", $content)));
			fclose($fp);
		}
	}
	function __mergeFiles($output){
		foreach ($output as $file => $content) {
			if (count($content) <= 1 && $file != $this->__filename) {
				@$output[$this->__filename][1] = array_unique(array_merge($output[$this->__filename][1], $content[1]));

				if (!isset($output[$this->__filename][0])) {
					$output[$this->__filename][0] = $content[0];
				}
				unset($content[0]);
				unset($content[1]);

				foreach ($content as $msgid) {
					$output[$this->__filename][] = $msgid;
				}
				unset($output[$file]);
			}
		}
		return $output;
	}
	function __writeHeader() {
		$output  = "# LANGUAGE translation of CakePHP Application\n";
		$output .= "# Copyright YEAR NAME <EMAIL@ADDRESS>\n";
		$output .= "# --VERSIONS--\n";
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
	function __findVersion($code, $file) {
		$header = '$Id' . ':';
		if (preg_match('/\\' . $header . ' [\\w.]* ([\\d]*)/', $code, $versionInfo)) {
			$version = str_replace(ROOT, '', 'Revision: ' . $versionInfo[1] . ' ' .$file);
			$this->__fileVersions[$file] = $version;
		}
	}
	function __formatString($string) {
		$quote = substr($string, 0, 1);
		$string = substr($string, 1, -1);
		if ($quote == '"') {
			$string = stripcslashes($string);
		} else {
			$string = strtr($string, array("\\'" => "'", "\\\\" => "\\"));
		}
		return addcslashes($string, "\0..\37\\\"");
	}
	function __markerError($file, $line, $marker, $count) {
		$this->out("Invalid marker content in $file:$line\n* $marker(", true);
		$count += 2;
		$tokenCount = count($this->__tokens);
		$parenthesis = 1;

		while ((($tokenCount - $count) > 0) && $parenthesis) {
			if (is_array($this->__tokens[$count])) {
				$this->out($this->__tokens[$count][1], false);
			} else {
				$this->out($this->__tokens[$count], false);
				if ($this->__tokens[$count] == "(") {
					$parenthesis++;
				}

				if ($this->__tokens[$count] == ")") {
					$parenthesis--;
				}
			}
			$count++;
		}
		$this->out("\n", true);
	}
	function __searchDirectory($path = null) {
		if ($path === null){
			$path = $this->path .DS;
		}
		$files = glob("$path*.{php,ctp,thtml,inc,tpl}", GLOB_BRACE);
		$dirs = glob("$path*", GLOB_ONLYDIR);

		foreach ($dirs as $dir) {
			if (!preg_match("!(^|.+/)(CVS|.svn)$!", $dir)) {
				$files = array_merge($files, $this->__searchDirectory("$dir" . DS));
				if (($id = array_search($dir . DS . 'extract.php', $files)) !== FALSE) {
					unset($files[$id]);
				}
			}
		}
		return $files;
	}
}
?>