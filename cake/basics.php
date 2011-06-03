<?php
/**
 * Basic Cake functionality.
 *
 * Core functions for including other source files, loading models and so forth.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Basic defines for timing functions.
 */
	define('SECOND', 1);
	define('MINUTE', 60);
	define('HOUR', 3600);
	define('DAY', 86400);
	define('WEEK', 604800);
	define('MONTH', 2592000);
	define('YEAR', 31536000);

/**
 * Patch for PHP < 5.0
 */
if (!function_exists('clone')) {
	if (version_compare(PHP_VERSION, '5.0') < 0) {
		eval ('
		function clone($object)
		{
			return $object;
		}');
	}
}

/**
 * Loads configuration files. Receives a set of configuration files
 * to load.
 * Example:
 *
 * `config('config1', 'config2');`
 *
 * @return boolean Success
 * @link http://book.cakephp.org/view/1125/config
 */
	function config() {
		$args = func_get_args();
		foreach ($args as $arg) {
			if ($arg === 'database' && file_exists(CONFIGS . 'database.php')) {
				include_once(CONFIGS . $arg . '.php');
			} elseif (file_exists(CONFIGS . $arg . '.php')) {
				include_once(CONFIGS . $arg . '.php');

				if (count($args) == 1) {
					return true;
				}
			} else {
				if (count($args) == 1) {
					return false;
				}
			}
		}
		return true;
	}

/**
 * Loads component/components from LIBS. Takes optional number of parameters.
 *
 * Example:
 *
 * `uses('flay', 'time');`
 *
 * @param string $name Filename without the .php part
 * @deprecated Will be removed in 2.0
 * @link http://book.cakephp.org/view/1140/uses
 */
	function uses() {
		$args = func_get_args();
		foreach ($args as $file) {
			require_once(LIBS . strtolower($file) . '.php');
		}
	}

/**
 * Prints out debug information about given variable.
 *
 * Only runs if debug level is greater than zero.
 *
 * @param boolean $var Variable to show debug information for.
 * @param boolean $showHtml If set to true, the method prints the debug data in a screen-friendly way.
 * @param boolean $showFrom If set to true, the method prints from where the function was called.
 * @link http://book.cakephp.org/view/1190/Basic-Debugging
 * @link http://book.cakephp.org/view/1128/debug
 */
	function debug($var = false, $showHtml = false, $showFrom = true) {
		if (Configure::read() > 0) {
			if ($showFrom) {
				$calledFrom = debug_backtrace();
				echo '<strong>' . substr(str_replace(ROOT, '', $calledFrom[0]['file']), 1) . '</strong>';
				echo ' (line <strong>' . $calledFrom[0]['line'] . '</strong>)';
			}
			echo "\n<pre class=\"cake-debug\">\n";

			$var = print_r($var, true);
			if ($showHtml) {
				$var = str_replace('<', '&lt;', str_replace('>', '&gt;', $var));
			}
			echo $var . "\n</pre>\n";
		}
	}
if (!function_exists('getMicrotime')) {

/**
 * Returns microtime for execution time checking
 *
 * @return float Microtime
 */
	function getMicrotime() {
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}
}
if (!function_exists('sortByKey')) {

/**
 * Sorts given $array by key $sortby.
 *
 * @param array $array Array to sort
 * @param string $sortby Sort by this key
 * @param string $order  Sort order asc/desc (ascending or descending).
 * @param integer $type Type of sorting to perform
 * @return mixed Sorted array
 */
	function sortByKey(&$array, $sortby, $order = 'asc', $type = SORT_NUMERIC) {
		if (!is_array($array)) {
			return null;
		}

		foreach ($array as $key => $val) {
			$sa[$key] = $val[$sortby];
		}

		if ($order == 'asc') {
			asort($sa, $type);
		} else {
			arsort($sa, $type);
		}

		foreach ($sa as $key => $val) {
			$out[] = $array[$key];
		}
		return $out;
	}
}
if (!function_exists('array_combine')) {

/**
 * Combines given identical arrays by using the first array's values as keys,
 * and the second one's values as values. (Implemented for backwards compatibility with PHP4)
 *
 * @param array $a1 Array to use for keys
 * @param array $a2 Array to use for values
 * @return mixed Outputs either combined array or false.
 * @deprecated Will be removed in 2.0
 */
	function array_combine($a1, $a2) {
		$a1 = array_values($a1);
		$a2 = array_values($a2);
		$c1 = count($a1);
		$c2 = count($a2);

		if ($c1 != $c2) {
			return false;
		}
		if ($c1 <= 0) {
			return false;
		}
		$output = array();

		for ($i = 0; $i < $c1; $i++) {
			$output[$a1[$i]] = $a2[$i];
		}
		return $output;
	}
}

/**
 * Convenience method for htmlspecialchars.
 *
 * @param string $text Text to wrap through htmlspecialchars
 * @param string $charset Character set to use when escaping.  Defaults to config value in 'App.encoding' or 'UTF-8'
 * @return string Wrapped text
 * @link http://book.cakephp.org/view/1132/h
 */
	function h($text, $charset = null) {
		if (is_array($text)) {
			return array_map('h', $text);
		}

		static $defaultCharset = false;
		if ($defaultCharset === false) {
			$defaultCharset = Configure::read('App.encoding');
			if ($defaultCharset === null) {
				$defaultCharset = 'UTF-8';
			}
		}
		if ($charset) {
			return htmlspecialchars($text, ENT_QUOTES, $charset);
		} else {
			return htmlspecialchars($text, ENT_QUOTES, $defaultCharset);
		}
	}

/**
 * Splits a dot syntax plugin name into its plugin and classname.
 * If $name does not have a dot, then index 0 will be null.
 *
 * Commonly used like `list($plugin, $name) = pluginSplit($name);`
 *
 * @param string $name The name you want to plugin split.
 * @param boolean $dotAppend Set to true if you want the plugin to have a '.' appended to it.
 * @param string $plugin Optional default plugin to use if no plugin is found. Defaults to null.
 * @return array Array with 2 indexes.  0 => plugin name, 1 => classname
 */
	function pluginSplit($name, $dotAppend = false, $plugin = null) {
		if (strpos($name, '.') !== false) {
			$parts = explode('.', $name, 2);
			if ($dotAppend) {
				$parts[0] .= '.';
			}
			return $parts;
		}
		return array($plugin, $name);
	}

/**
 * Returns an array of all the given parameters.
 *
 * Example:
 *
 * `a('a', 'b')`
 *
 * Would return:
 *
 * `array('a', 'b')`
 *
 * @return array Array of given parameters
 * @link http://book.cakephp.org/view/1122/a
 * @deprecated Will be removed in 2.0
 */
	function a() {
		$args = func_get_args();
		return $args;
	}

/**
 * Constructs associative array from pairs of arguments.
 *
 * Example:
 *
 * `aa('a','b')`
 *
 * Would return:
 *
 * `array('a'=>'b')`
 *
 * @return array Associative array
 * @link http://book.cakephp.org/view/1123/aa
 * @deprecated Will be removed in 2.0
 */
	function aa() {
		$args = func_get_args();
		$argc = count($args);
		for ($i = 0; $i < $argc; $i++) {
			if ($i + 1 < $argc) {
				$a[$args[$i]] = $args[$i + 1];
			} else {
				$a[$args[$i]] = null;
			}
			$i++;
		}
		return $a;
	}

/**
 * Convenience method for echo().
 *
 * @param string $text String to echo
 * @link http://book.cakephp.org/view/1129/e
 * @deprecated Will be removed in 2.0
 */
	function e($text) {
		echo $text;
	}

/**
 * Convenience method for strtolower().
 *
 * @param string $str String to lowercase
 * @return string Lowercased string
 * @link http://book.cakephp.org/view/1134/low
 * @deprecated Will be removed in 2.0
 */
	function low($str) {
		return strtolower($str);
	}

/**
 * Convenience method for strtoupper().
 *
 * @param string $str String to uppercase
 * @return string Uppercased string
 * @link http://book.cakephp.org/view/1139/up
 * @deprecated Will be removed in 2.0
 */
	function up($str) {
		return strtoupper($str);
	}

/**
 * Convenience method for str_replace().
 *
 * @param string $search String to be replaced
 * @param string $replace String to insert
 * @param string $subject String to search
 * @return string Replaced string
 * @link http://book.cakephp.org/view/1137/r
 * @deprecated Will be removed in 2.0
 */
	function r($search, $replace, $subject) {
		return str_replace($search, $replace, $subject);
	}

/**
 * Print_r convenience function, which prints out <PRE> tags around
 * the output of given array. Similar to debug().
 *
 * @see	debug()
 * @param array $var Variable to print out
 * @link http://book.cakephp.org/view/1136/pr
 */
	function pr($var) {
		if (Configure::read() > 0) {
			echo '<pre>';
			print_r($var);
			echo '</pre>';
		}
	}

/**
 * Display parameters.
 *
 * @param mixed $p Parameter as string or array
 * @return string
 * @deprecated Will be removed in 2.0
 */
	function params($p) {
		if (!is_array($p) || count($p) == 0) {
			return null;
		}
		if (is_array($p[0]) && count($p) == 1) {
			return $p[0];
		}
		return $p;
	}

/**
 * Merge a group of arrays
 *
 * @param array First array
 * @param array Second array
 * @param array Third array
 * @param array Etc...
 * @return array All array parameters merged into one
 * @link http://book.cakephp.org/view/1124/am
 */
	function am() {
		$r = array();
		$args = func_get_args();
		foreach ($args as $a) {
			if (!is_array($a)) {
				$a = array($a);
			}
			$r = array_merge($r, $a);
		}
		return $r;
	}

/**
 * Gets an environment variable from available sources, and provides emulation
 * for unsupported or inconsistent environment variables (i.e. DOCUMENT_ROOT on
 * IIS, or SCRIPT_NAME in CGI mode).  Also exposes some additional custom
 * environment information.
 *
 * @param  string $key Environment variable name.
 * @return string Environment variable setting.
 * @link http://book.cakephp.org/view/1130/env
 */
	function env($key) {
		if ($key == 'HTTPS') {
			if (isset($_SERVER['HTTPS'])) {
				return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
			}
			return (strpos(env('SCRIPT_URI'), 'https://') === 0);
		}

		if ($key == 'SCRIPT_NAME') {
			if (env('CGI_MODE') && isset($_ENV['SCRIPT_URL'])) {
				$key = 'SCRIPT_URL';
			}
		}

		$val = null;
		if (isset($_SERVER[$key])) {
			$val = $_SERVER[$key];
		} elseif (isset($_ENV[$key])) {
			$val = $_ENV[$key];
		} elseif (getenv($key) !== false) {
			$val = getenv($key);
		}

		if ($key === 'REMOTE_ADDR' && $val === env('SERVER_ADDR')) {
			$addr = env('HTTP_PC_REMOTE_ADDR');
			if ($addr !== null) {
				$val = $addr;
			}
		}

		if ($val !== null) {
			return $val;
		}

		switch ($key) {
			case 'SCRIPT_FILENAME':
				if (defined('SERVER_IIS') && SERVER_IIS === true) {
					return str_replace('\\\\', '\\', env('PATH_TRANSLATED'));
				}
				break;
			case 'DOCUMENT_ROOT':
				$name = env('SCRIPT_NAME');
				$filename = env('SCRIPT_FILENAME');
				$offset = 0;
				if (!strpos($name, '.php')) {
					$offset = 4;
				}
				return substr($filename, 0, strlen($filename) - (strlen($name) + $offset));
				break;
			case 'PHP_SELF':
				return str_replace(env('DOCUMENT_ROOT'), '', env('SCRIPT_FILENAME'));
				break;
			case 'CGI_MODE':
				return (PHP_SAPI === 'cgi');
				break;
			case 'HTTP_BASE':
				$host = env('HTTP_HOST');
				$parts = explode('.', $host);
				$count = count($parts);

				if ($count === 1) {
					return '.' . $host;
				} elseif ($count === 2) {
					return '.' . $host;
				} elseif ($count === 3) {
					$gTLD = array('aero', 'asia', 'biz', 'cat', 'com', 'coop', 'edu', 'gov', 'info', 'int', 'jobs', 'mil', 'mobi', 'museum', 'name', 'net', 'org', 'pro', 'tel', 'travel', 'xxx');
					if (in_array($parts[1], $gTLD)) {
						return '.' . $host;
					}
				}
				array_shift($parts);
				return '.' . implode('.', $parts);
				break;
		}
		return null;
	}
if (!function_exists('file_put_contents')) {

/**
 * Writes data into file.
 *
 * If file exists, it will be overwritten. If data is an array, it will be implode()ed with an empty string.
 *
 * @param string $fileName File name.
 * @param mixed  $data String or array.
 * @return boolean Success
 * @deprecated Will be removed in 2.0
 */
	function file_put_contents($fileName, $data) {
		if (is_array($data)) {
			$data = implode('', $data);
		}
		$res = @fopen($fileName, 'w+b');

		if ($res) {
			$write = @fwrite($res, $data);
			if ($write === false) {
				return false;
			} else {
				@fclose($res);
				return $write;
			}
		}
		return false;
	}
}

/**
 * Reads/writes temporary data to cache files or session.
 *
 * @param  string $path	File path within /tmp to save the file.
 * @param  mixed  $data	The data to save to the temporary file.
 * @param  mixed  $expires A valid strtotime string when the data expires.
 * @param  string $target  The target of the cached data; either 'cache' or 'public'.
 * @return mixed  The contents of the temporary file.
 * @deprecated Please use Cache::write() instead
 */
	function cache($path, $data = null, $expires = '+1 day', $target = 'cache') {
		if (Configure::read('Cache.disable')) {
			return null;
		}
		$now = time();

		if (!is_numeric($expires)) {
			$expires = strtotime($expires, $now);
		}

		switch (strtolower($target)) {
			case 'cache':
				$filename = CACHE . $path;
			break;
			case 'public':
				$filename = WWW_ROOT . $path;
			break;
			case 'tmp':
				$filename = TMP . $path;
			break;
		}
		$timediff = $expires - $now;
		$filetime = false;

		if (file_exists($filename)) {
			$filetime = @filemtime($filename);
		}

		if ($data === null) {
			if (file_exists($filename) && $filetime !== false) {
				if ($filetime + $timediff < $now) {
					@unlink($filename);
				} else {
					$data = @file_get_contents($filename);
				}
			}
		} elseif (is_writable(dirname($filename))) {
			@file_put_contents($filename, $data);
		}
		return $data;
	}

/**
 * Used to delete files in the cache directories, or clear contents of cache directories
 *
 * @param mixed $params As String name to be searched for deletion, if name is a directory all files in
 *   directory will be deleted. If array, names to be searched for deletion. If clearCache() without params,
 *   all files in app/tmp/cache/views will be deleted
 * @param string $type Directory in tmp/cache defaults to view directory
 * @param string $ext The file extension you are deleting
 * @return true if files found and deleted false otherwise
 */
	function clearCache($params = null, $type = 'views', $ext = '.php') {
		if (is_string($params) || $params === null) {
			$params = preg_replace('/\/\//', '/', $params);
			$cache = CACHE . $type . DS . $params;

			if (is_file($cache . $ext)) {
				@unlink($cache . $ext);
				return true;
			} elseif (is_dir($cache)) {
				$files = glob($cache . '*');

				if ($files === false) {
					return false;
				}

				foreach ($files as $file) {
					if (is_file($file) && strrpos($file, DS . 'empty') !== strlen($file) - 6) {
						@unlink($file);
					}
				}
				return true;
			} else {
				$cache = array(
					CACHE . $type . DS . '*' . $params . $ext,
					CACHE . $type . DS . '*' . $params . '_*' . $ext
				);
				$files = array();
				while ($search = array_shift($cache)) {
					$results = glob($search);
					if ($results !== false) {
						$files = array_merge($files, $results);
					}
				}
				if (empty($files)) {
					return false;
				}
				foreach ($files as $file) {
					if (is_file($file) && strrpos($file, DS . 'empty') !== strlen($file) - 6) {
						@unlink($file);
					}
				}
				return true;
			}
		} elseif (is_array($params)) {
			foreach ($params as $file) {
				clearCache($file, $type, $ext);
			}
			return true;
		}
		return false;
	}

/**
 * Recursively strips slashes from all values in an array
 *
 * @param array $values Array of values to strip slashes
 * @return mixed What is returned from calling stripslashes
 * @link http://book.cakephp.org/view/1138/stripslashes_deep
 */
	function stripslashes_deep($values) {
		if (is_array($values)) {
			foreach ($values as $key => $value) {
				$values[$key] = stripslashes_deep($value);
			}
		} else {
			$values = stripslashes($values);
		}
		return $values;
	}

/**
 * Returns a translated string if one is found; Otherwise, the submitted message.
 *
 * @param string $singular Text to translate
 * @param boolean $return Set to true to return translated string, or false to echo
 * @return mixed translated string if $return is false string will be echoed
 * @link http://book.cakephp.org/view/1121/__
 */
	function __($singular, $return = false) {
		if (!$singular) {
			return;
		}
		if (!class_exists('I18n')) {
			App::import('Core', 'i18n');
		}

		if ($return === false) {
			echo I18n::translate($singular);
		} else {
			return I18n::translate($singular);
		}
	}

/**
 * Returns correct plural form of message identified by $singular and $plural for count $count.
 * Some languages have more than one form for plural messages dependent on the count.
 *
 * @param string $singular Singular text to translate
 * @param string $plural Plural text
 * @param integer $count Count
 * @param boolean $return true to return, false to echo
 * @return mixed plural form of translated string if $return is false string will be echoed
 */
	function __n($singular, $plural, $count, $return = false) {
		if (!$singular) {
			return;
		}
		if (!class_exists('I18n')) {
			App::import('Core', 'i18n');
		}

		if ($return === false) {
			echo I18n::translate($singular, $plural, null, 6, $count);
		} else {
			return I18n::translate($singular, $plural, null, 6, $count);
		}
	}

/**
 * Allows you to override the current domain for a single message lookup.
 *
 * @param string $domain Domain
 * @param string $msg String to translate
 * @param string $return true to return, false to echo
 * @return translated string if $return is false string will be echoed
 */
	function __d($domain, $msg, $return = false) {
		if (!$msg) {
			return;
		}
		if (!class_exists('I18n')) {
			App::import('Core', 'i18n');
		}

		if ($return === false) {
			echo I18n::translate($msg, null, $domain);
		} else {
			return I18n::translate($msg, null, $domain);
		}
	}

/**
 * Allows you to override the current domain for a single plural message lookup.
 * Returns correct plural form of message identified by $singular and $plural for count $count
 * from domain $domain.
 *
 * @param string $domain Domain
 * @param string $singular Singular string to translate
 * @param string $plural Plural
 * @param integer $count Count
 * @param boolean $return true to return, false to echo
 * @return plural form of translated string if $return is false string will be echoed
 */
	function __dn($domain, $singular, $plural, $count, $return = false) {
		if (!$singular) {
			return;
		}
		if (!class_exists('I18n')) {
			App::import('Core', 'i18n');
		}

		if ($return === false) {
			echo I18n::translate($singular, $plural, $domain, 6, $count);
		} else {
			return I18n::translate($singular, $plural, $domain, 6, $count);
		}
	}

/**
 * Allows you to override the current domain for a single message lookup.
 * It also allows you to specify a category.
 *
 * The category argument allows a specific category of the locale settings to be used for fetching a message.
 * Valid categories are: LC_CTYPE, LC_NUMERIC, LC_TIME, LC_COLLATE, LC_MONETARY, LC_MESSAGES and LC_ALL.
 *
 * Note that the category must be specified with a numeric value, instead of the constant name.  The values are:
 *
 * - LC_ALL       0
 * - LC_COLLATE   1
 * - LC_CTYPE     2
 * - LC_MONETARY  3
 * - LC_NUMERIC   4
 * - LC_TIME      5
 * - LC_MESSAGES  6
 *
 * @param string $domain Domain
 * @param string $msg Message to translate
 * @param integer $category Category
 * @param boolean $return true to return, false to echo
 * @return translated string if $return is false string will be echoed
 */
	function __dc($domain, $msg, $category, $return = false) {
		if (!$msg) {
			return;
		}
		if (!class_exists('I18n')) {
			App::import('Core', 'i18n');
		}

		if ($return === false) {
			echo I18n::translate($msg, null, $domain, $category);
		} else {
			return I18n::translate($msg, null, $domain, $category);
		}
	}

/**
 * Allows you to override the current domain for a single plural message lookup.
 * It also allows you to specify a category.
 * Returns correct plural form of message identified by $singular and $plural for count $count
 * from domain $domain.
 *
 * The category argument allows a specific category of the locale settings to be used for fetching a message.
 * Valid categories are: LC_CTYPE, LC_NUMERIC, LC_TIME, LC_COLLATE, LC_MONETARY, LC_MESSAGES and LC_ALL.
 *
 * Note that the category must be specified with a numeric value, instead of the constant name.  The values are:
 *
 * - LC_ALL       0
 * - LC_COLLATE   1
 * - LC_CTYPE     2
 * - LC_MONETARY  3
 * - LC_NUMERIC   4
 * - LC_TIME      5
 * - LC_MESSAGES  6
 *
 * @param string $domain Domain
 * @param string $singular Singular string to translate
 * @param string $plural Plural
 * @param integer $count Count
 * @param integer $category Category
 * @param boolean $return true to return, false to echo
 * @return plural form of translated string if $return is false string will be echoed
 */
	function __dcn($domain, $singular, $plural, $count, $category, $return = false) {
		if (!$singular) {
			return;
		}
		if (!class_exists('I18n')) {
			App::import('Core', 'i18n');
		}

		if ($return === false) {
			echo I18n::translate($singular, $plural, $domain, $category, $count);
		} else {
			return I18n::translate($singular, $plural, $domain, $category, $count);
		}
	}

/**
 * The category argument allows a specific category of the locale settings to be used for fetching a message.
 * Valid categories are: LC_CTYPE, LC_NUMERIC, LC_TIME, LC_COLLATE, LC_MONETARY, LC_MESSAGES and LC_ALL.
 *
 * Note that the category must be specified with a numeric value, instead of the constant name.  The values are:
 *
 * - LC_ALL       0
 * - LC_COLLATE   1
 * - LC_CTYPE     2
 * - LC_MONETARY  3
 * - LC_NUMERIC   4
 * - LC_TIME      5
 * - LC_MESSAGES  6
 *
 * @param string $msg String to translate
 * @param integer $category Category
 * @param string $return true to return, false to echo
 * @return translated string if $return is false string will be echoed
 */
	function __c($msg, $category, $return = false) {
		if (!$msg) {
			return;
		}
		if (!class_exists('I18n')) {
			App::import('Core', 'i18n');
		}

		if ($return === false) {
			echo I18n::translate($msg, null, null, $category);
		} else {
			return I18n::translate($msg, null, null, $category);
		}
	}

/**
 * Computes the difference of arrays using keys for comparison.
 *
 * @param array First array
 * @param array Second array
 * @return array Array with different keys
 * @deprecated Will be removed in 2.0
 */
	if (!function_exists('array_diff_key')) {
		function array_diff_key() {
			$valuesDiff = array();

			$argc = func_num_args();
			if ($argc < 2) {
				return false;
			}

			$args = func_get_args();
			foreach ($args as $param) {
				if (!is_array($param)) {
					return false;
				}
			}

			foreach ($args[0] as $valueKey => $valueData) {
				for ($i = 1; $i < $argc; $i++) {
					if (array_key_exists($valueKey, $args[$i])) {
						continue 2;
					}
				}
				$valuesDiff[$valueKey] = $valueData;
			}
			return $valuesDiff;
		}
	}

/**
 * Computes the intersection of arrays using keys for comparison
 *
 * @param array First array
 * @param array Second array
 * @return array Array with interesected keys
 * @deprecated Will be removed in 2.0
 */
	if (!function_exists('array_intersect_key')) {
		function array_intersect_key($arr1, $arr2) {
			$res = array();
			foreach ($arr1 as $key => $value) {
				if (array_key_exists($key, $arr2)) {
					$res[$key] = $arr1[$key];
				}
			}
			return $res;
		}
	}

/**
 * Shortcut to Log::write.
 *
 * @param string $message Message to write to log
 */
	function LogError($message) {
		if (!class_exists('CakeLog')) {
			App::import('Core', 'CakeLog');
		}
		$bad = array("\n", "\r", "\t");
		$good = ' ';
		CakeLog::write('error', str_replace($bad, $good, $message));
	}

/**
 * Searches include path for files.
 *
 * @param string $file File to look for
 * @return Full path to file if exists, otherwise false
 * @link http://book.cakephp.org/view/1131/fileExistsInPath
 */
	function fileExistsInPath($file) {
		$paths = explode(PATH_SEPARATOR, ini_get('include_path'));
		foreach ($paths as $path) {
			$fullPath = $path . DS . $file;

			if (file_exists($fullPath)) {
				return $fullPath;
			} elseif (file_exists($file)) {
				return $file;
			}
		}
		return false;
	}

/**
 * Convert forward slashes to underscores and removes first and last underscores in a string
 *
 * @param string String to convert
 * @return string with underscore remove from start and end of string
 * @link http://book.cakephp.org/view/1126/convertSlash
 */
	function convertSlash($string) {
		$string = trim($string, '/');
		$string = preg_replace('/\/\//', '/', $string);
		$string = str_replace('/', '_', $string);
		return $string;
	}

/**
 * Implements http_build_query for PHP4.
 *
 * @param string $data Data to set in query string
 * @param string $prefix If numeric indices, prepend this to index for elements in base array.
 * @param string $argSep String used to separate arguments
 * @param string $baseKey Base key
 * @return string URL encoded query string
 * @see http://php.net/http_build_query
 * @deprecated Will be removed in 2.0
 */
	if (!function_exists('http_build_query')) {
		function http_build_query($data, $prefix = null, $argSep = null, $baseKey = null) {
			if (empty($argSep)) {
				$argSep = ini_get('arg_separator.output');
			}
			if (is_object($data)) {
				$data = get_object_vars($data);
			}
			$out = array();

			foreach ((array)$data as $key => $v) {
				if (is_numeric($key) && !empty($prefix)) {
					$key = $prefix . $key;
				}
				$key = urlencode($key);

				if (!empty($baseKey)) {
					$key = $baseKey . '[' . $key . ']';
				}

				if (is_array($v) || is_object($v)) {
					$out[] = http_build_query($v, $prefix, $argSep, $key);
				} else {
					$out[] = $key . '=' . urlencode($v);
				}
			}
			return implode($argSep, $out);
		}
	}

/**
 * Wraps ternary operations. If $condition is a non-empty value, $val1 is returned, otherwise $val2.
 * Don't use for isset() conditions, or wrap your variable with @ operator:
 * Example:
 *
 * `ife(isset($variable), @$variable, 'default');`
 *
 * @param mixed $condition Conditional expression
 * @param mixed $val1 Value to return in case condition matches
 * @param mixed $val2 Value to return if condition doesn't match
 * @return mixed $val1 or $val2, depending on whether $condition evaluates to a non-empty expression.
 * @link http://book.cakephp.org/view/1133/ife
 * @deprecated Will be removed in 2.0
 */
	function ife($condition, $val1 = null, $val2 = null) {
		if (!empty($condition)) {
			return $val1;
		}
		return $val2;
	}
