#!/usr/bin/php -q
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
 * @subpackage      cake.cake.scripts
 * @since           CakePHP(tm) v 1.2.0.4708
 * @version         $Revision$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 */
	define ('DS', DIRECTORY_SEPARATOR);
	if (function_exists('ini_set')) {
		ini_set('display_errors', '1');
		ini_set('error_reporting', '7');
		ini_set('memory_limit', '16M');
		ini_set('max_execution_time', 0);
	}

	$app = null;
	$root = dirname(dirname(dirname(__FILE__)));
	$core = null;
	$help = null;
	$files = array();
	$path = null;

	for ($i = 1; $i < count($argv); $i += 2) {
		switch ($argv[$i]) {
			case '-a':
			case '-app':
				$app = $argv[$i + 1];
			break;
			case '-c':
			case '-core':
				$core = $argv[$i + 1];
			break;
			case '-r':
			case '-root':
				$root = $argv[$i + 1];
			break;
			case '-h':
			case '-help':
				$help = true;
			break;
			case '-f' :
			case '-files' :
				$files = $argv[$i + 1];
			break;
			case '-p':
			case '-path':
				$path = $argv[$i + 1];
			break;
			case '-debug' :
				$files = array(__FILE__);
			break;
		}
	}

	if(!$app) {
		$app = 'app';
	}

	if(!is_dir($app)) {
		$project = true;
		$projectPath = $app;
	}

	if($project) {
		$app = $projectPath;
	}

	$shortPath = str_replace($root, '', $app);
	$shortPath = str_replace('..'.DS, '', $shortPath);
	$shortPath = str_replace(DS.DS, DS, $shortPath);

	$pathArray = explode(DS, $shortPath);

	if(end($pathArray) != '') {
		$appDir = array_pop($pathArray);
	} else {
		array_pop($pathArray);
		$appDir = array_pop($pathArray);
	}
	$rootDir = implode(DS, $pathArray);
	$rootDir = str_replace(DS.DS, DS, $rootDir);

	if(!$rootDir) {
		$rootDir = $root;
		$projectPath = $root.DS.$appDir;
	}

	define ('ROOT', $rootDir);
	define ('APP_DIR', $appDir);
	define ('DEBUG', 1);;
	define('CAKE_CORE_INCLUDE_PATH', $root);

	if(function_exists('ini_set')) {
		ini_set('include_path', CAKE_CORE_INCLUDE_PATH . PATH_SEPARATOR . ROOT . DS . APP_DIR . DS . PATH_SEPARATOR . ini_get('include_path'));
		define('APP_PATH', null);
		define('CORE_PATH', null);
	} else {
		define('APP_PATH', ROOT . DS . APP_DIR . DS);
		define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
	}
	require_once (CORE_PATH.'cake'.DS.'basics.php');
	require_once (CORE_PATH.'cake'.DS.'config'.DS.'paths.php');
	uses('object', 'configure');

	$extract = new I18nExtractor();

	if(!empty($files) && !is_array($files)){
		$extract->files = explode(',', $files);
	} else {
		$extract->files = $files;
	}

	if($path) {
		$extract->path = $path;
	}

	if($help === true){
		$extract->help();
		exit();
	}
	$extract->main();
	return;
	// Only used when -debug option
	$singluarReturn = __('Singular string  return __()', true);
	$singluarEcho = __('Singular string  echo __()');

	$pluralReturn = __n('% apple in the bowl (plural string return __n())', '% apples in the blowl (plural string 2 return __n())', 3, true);
	$pluralEcho = __n('% apple in the bowl (plural string 2 echo __n())', '% apples in the blowl (plural string 2 echo __n()', 3);

	$singluarDomainReturn = __d('controllers', 'Singular string domain lookup return __d()', true);
	$singluarDomainEcho = __d('controllers', 'Singular string domain lookup echo __d()');

	$pluralDomainReturn = __dn('controllers', '% pears in the bowl (plural string domain lookup return __dn())', '% pears in the blowl (plural string domain lookup return __dn())', 3, true);
	$pluralDomainEcho = __dn('controllers', '% pears in the bowl (plural string domain lookup echo __dn())', '% pears in the blowl (plural string domain lookup echo __dn())', 3);

	$singluarDomainCategoryReturn = __dc('controllers', 'Singular string domain and category lookup return __dc()', 5, true);
	$singluarDomainCategoryEcho = __dc('controllers', 'Singular string domain and category lookup echo __dc()', 5);

	$pluralDomainCategoryReturn = __dcn('controllers', '% apple in the bowl (plural string 1 domain and category lookup return __dcn())', '% apples in the blowl (plural string 2 domain and category lookup return __dcn())', 3, 5, true);
	$pluralDomainCategoryEcho = __dcn('controllers', '% apple in the bowl (plural string 1 domain and category lookup echo __dcn())', '% apples in the blowl (plural string 2 domain and category lookup echo __dcn())', 3, 5);

	$cateogryReturn = __c('Category string lookup line return __c()', 5, true);
	$categoryEcho = __c('Category string  lookup line echo __c()', 5);
/**
 * Language string extractor
 *
 * @package     cake
 * @subpackage  cake.cake.scripts
 */
class I18nExtractor {
	var $stdin;
	var $stdout;
	var $stderr;
	var $path = null;
	var $files = array();

	var $__filename = 'default';
	var $__oneFile = true;
	var $__file = null;
	var $__tokens = array();
	var $__strings = array();
	var $__fileVersions = array();
	var $__output = null;

	function __construct() {
		$this->stdin = fopen('php://stdin', 'r');
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');
		$this->path = APP;
		$this->__output = APP . 'locale' . DS;
		$this->__welcome();
	}
	function I18nExtractor() {
		return $this->__construct();
	}
	function main() {
		$this->__stdout('');
		$this->__stdout('');
		$this->__stdout('Extracting...');
		$this->__hr();
		$this->__stdout('Path: '. $this->path);
		$this->__stdout('Output Directory: '. $this->__output);
		$this->__hr();

		$response = '';
		$filename = '';
		while($response == '') {
			$response = $this->__getInput('Would you like to merge all translations into one file?', array('y','n'), 'y');
			if(strtolower($response) == 'n') {
				$this->__oneFile = false;
			} else {
				while($filename == '') {
					$filename = $this->__getInput('What should we name this file?', null, $this->__filename);
					if ($filename == '') {
						$this->__stdout('The filesname you supplied was empty. Please try again.');
					}
				}
				$this->__filename = $filename;
			}
		}

		if(empty($this->files)){
			$this->files = $this->__searchDirectory();
		}
		$this->__extractTokens();
	}
	function help() {
		$this->__stdout('CakePHP Language String Extraction:');
		$this->__hr();
		$this->__stdout('The Extract script generates .pot file(s) with translations');
		$this->__stdout('The .pot file(s) will be place in the locale directory of -app');
		$this->__stdout('By default -app is ROOT/app');
		$this->__stdout('');
		$this->__hr('');
		$this->__stdout('usage: php extract.php [command] [path...]');
		$this->__stdout('');
		$this->__stdout('commands:');
		$this->__stdout('   -app or -a: directory where your application is located');
		$this->__stdout('   -root or -r: path to install');
		$this->__stdout('   -core or -c: path to cake directory');
		$this->__stdout('   -path or -p: [path...] Full path to directory to extract strings');
		$this->__stdout('   -files or -f: [comma separated list of files]');
		$this->__stdout('   -help or -h: Shows this help message.');
		$this->__stdout('   -debug or -d: Perform self test.');
		$this->__stdout('');
	}
	function __welcome() {
		$this->__stdout('');
		$this->__stdout(' ___  __  _  _  ___  __  _  _  __');
		$this->__stdout('|    |__| |_/  |__  |__] |__| |__]');
		$this->__stdout('|___ |  | | \_ |___ |    |  | |');
		$this->__hr();
		$this->__stdout('');
	}
	function __extractTokens(){
		foreach ($this->files as $file) {
			$this->__file = $file;
			$this->__stdout("Processing $file...");

			$code = file_get_contents($file);

			$this->__findVersion($code, $file);
			$allTokens = token_get_all($code);
			$this->__tokens = array();
			$lineNumber = 1;

			foreach($allTokens as $token) {
				if((!is_array($token)) || (($token[0] != T_WHITESPACE) && ($token[0] != T_INLINE_HTML))) {
					if(is_array($token)) {
						$token[] = $lineNumber;
					}
					$this->__tokens[] = $token;
				}

				if(is_array($token)) {
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
		$this->__stdout('Done.');
	}
/**
 * Will parse  __(), __c() functions
 *
 * @param string $functionname
 */
	function basic($functionname = '__') {
		$count = 0;
		$tokenCount = count($this->__tokens);

		while(($tokenCount - $count) > 3) {
			list($countToken, $parenthesis, $middle, $right) = array($this->__tokens[$count], $this->__tokens[$count + 1], $this->__tokens[$count + 2], $this->__tokens[$count + 3]);
			if (!is_array($countToken)) {
				$count++;
				continue;
			}

			list($type, $string, $line) = $countToken;
			if(($type == T_STRING) && ($string == $functionname) && ($parenthesis == "(")) {

				if(in_array($right, array(")", ","))
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

		while(($tokenCount - $count) > 7) {
			list($countToken, $firstParenthesis) = array($this->__tokens[$count], $this->__tokens[$count + 1]);
			if(!is_array($countToken)) {
				$count++;
				continue;
			}

			list($type, $string, $line) = $countToken;
			if (($type == T_STRING) && ($string == $functionname) && ($firstParenthesis == "(")) {
				$position = $count;
				$depth = 0;

				while($depth == 0) {
					if ($this->__tokens[$position] == "(") {
						$depth++;
					} elseif($this->__tokens[$position] == ")") {
						$depth--;
					}
					$position++;
				}

				if($plural) {
					$end = $position + $shift + 7;

					if($this->__tokens[$position + $shift + 5] === ')') {
						$end = $position + $shift + 5;
					}

					if(empty($shift)) {
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
					if($this->__tokens[$position + $shift + 5] === ')') {
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

				if(($endParenthesis == ")") && $condition) {
					if($this->__oneFile === true) {
						if($plural) {
							$this->__strings[$this->__formatString($singular[1]) . "\0" . $this->__formatString($plural[1])][$this->__file][] = $line;
						} else {
							$this->__strings[$this->__formatString($text[1])][$this->__file][] = $line;
						}
					} else {
						if($plural) {
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
		foreach($this->__strings as $str => $fileInfo) {
			$occured = $fileList = array();

			if($this->__oneFile === true) {
				foreach($fileInfo as $file => $lines) {
					$occured[] = "$file:" . join(";", $lines);

					if(isset($this->__fileVersions[$file])) {
						$fileList[] = $this->__fileVersions[$file];
					}
				}
				$occurances = join("\n#: ", $occured);
				$occurances = str_replace($this->path, '', $occurances);
				$output = "#: $occurances\n";
				$filename = $this->__filename;

				if(strpos($str, "\0") === false) {
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
				foreach($fileInfo as $file => $lines) {
					$filename = $str;
					$occured = array("$str:" . join(";", $lines));

					if(isset($this->__fileVersions[$str])) {
						$fileList[] = $this->__fileVersions[$str];
					}
					$occurances = join("\n#: ", $occured);
					$occurances = str_replace($this->path, '', $occurances);
					$output .= "#: $occurances\n";

					if(strpos($file, "\0") === false) {
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

		if(!$get) {
			if(isset($storage[$file])) {
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

		foreach($output as $file => $content) {
			$tmp = str_replace(array($this->path, '.php','.ctp','.thtml', '.inc','.tpl' ), '', $file);
			$tmp = str_replace(DS, '.', $tmp);
			$file = str_replace('.', '-', $tmp) .'.pot';
			$fileList = $content[1];

			unset($content[1]);

			$fileList = str_replace(array($this->path), '', $fileList);

			if(count($fileList) > 1) {
				$fileList = "Generated from files:\n#  " . join("\n#  ", $fileList);
			} elseif(count($fileList) == 1) {
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
		foreach($output as $file => $content) {
			if(count($content) <= 1 && $file != $this->__filename) {
				@$output[$this->__filename][1] = array_unique(array_merge($output[$this->__filename][1], $content[1]));

				if(!isset($output[$this->__filename][0])) {
					$output[$this->__filename][0] = $content[0];
				}
				unset($content[0]);
				unset($content[1]);

				foreach($content as $msgid) {
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
		if(preg_match('/\\$Id$code, $versionInfo)) {
			$version = str_replace(ROOT, '', 'Revision: ' . $versionInfo[1] . ' ' .$file);
			$this->__fileVersions[$file] = $version;
		}
	}
	function __formatString($string) {
		$quote = substr($str, 0, 1);
		$string = substr($string, 1, -1);
		if($quote == '"') {
			$string = stripcslashes($string);
		} else {
			$string = strtr($string, array("\\'" => "'", "\\\\" => "\\"));
		}
		return addcslashes($string, "\0..\37\\\"");
	}
	function __markerError($file, $line, $marker, $count) {
		$this->__stdout("Invalid marker content in $file:$line\n* $marker(", true);
		$count += 2;
		$tokenCount = count($this->__tokens);
		$parenthesis = 1;

		while((($tokenCount - $count) > 0) && $parenthesis) {
			if(is_array($this->__tokens[$count])) {
				$this->__stdout($this->__tokens[$count][1], false);
			} else {
				$this->__stdout($this->__tokens[$count], false);
				if($this->__tokens[$count] == "(") {
					$parenthesis++;
				}

				if($this->__tokens[$count] == ")") {
					$parenthesis--;
				}
			}
			$count++;
		}
		$this->__stdout("\n", true);
	}
	function __searchDirectory($path = null) {
		if($path === null){
			$path = $this->path;
		}
		$files = glob("$path*.{php,ctp,thtml,inc,tpl}", GLOB_BRACE);
		$dirs = glob("$path*", GLOB_ONLYDIR);

		foreach($dirs as $dir) {
			if(!preg_match("!(^|.+/)(CVS|.svn)$!", $dir)) {
				$files = array_merge($files, $this->__searchDirectory("$dir/"));
				if(($id = array_search($dir .DS . 'extract.php', $files)) !== FALSE) {
					unset($files[$id]);
				}
			}
		}
		return $files;
	}
	function __getInput($prompt, $options = null, $default = null) {
		if(!is_array($options)) {
			$printOptions = '';
		} else {
			$printOptions = '(' . implode('/', $options) . ')';
		}

		if($default == null) {
			$this->__stdout('');
			$this->__stdout($prompt . " $printOptions \n" . '> ', false);
		} else {
			$this->__stdout('');
			$this->__stdout($prompt . " $printOptions \n" . "[$default] > ", false);
		}
		$result = trim(fgets($this->stdin));

		if($default != null && empty($result)) {
			return $default;
		} else {
			return $result;
		}
	}
	function __stdout($string, $newline = true) {
		if($newline) {
			fwrite($this->stdout, $string . "\n");
		} else {
			fwrite($this->stdout, $string);
		}
	}
	function __stderr($string) {
		fwrite($this->stderr, $string, true);
	}
	function __hr() {
		$this->__stdout('---------------------------------------------------------------');
	}
}
?>