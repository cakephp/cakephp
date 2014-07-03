<?php
/**
 * Basic CakePHP functionality.
 *
 * Handles loading of core files needed on every request
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

define('TIME_START', microtime(true));

if (!defined('E_DEPRECATED')) {
	define('E_DEPRECATED', 8192);
}

if (!defined('E_USER_DEPRECATED')) {
	define('E_USER_DEPRECATED', E_USER_NOTICE);
}
error_reporting(E_ALL & ~E_DEPRECATED);

if (!defined('CAKE_CORE_INCLUDE_PATH')) {
	define('CAKE_CORE_INCLUDE_PATH', dirname(dirname(__FILE__)));
}

if (!defined('CORE_PATH')) {
	define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
}

if (!defined('WEBROOT_DIR')) {
	define('WEBROOT_DIR', 'webroot');
}

/**
 * Path to the cake directory.
 */
	define('CAKE', CORE_PATH . 'Cake' . DS);

/**
 * Path to the application's directory.
 */
if (!defined('APP')) {
	define('APP', ROOT . DS . APP_DIR . DS);
}

/**
 * Path to the application's libs directory.
 */
	define('APPLIBS', APP . 'Lib' . DS);

/**
 * Path to the public CSS directory.
 */
if (!defined('CSS')) {
	define('CSS', WWW_ROOT . 'css' . DS);
}

/**
 * Path to the public JavaScript directory.
 */
if (!defined('JS')) {
	define('JS', WWW_ROOT . 'js' . DS);
}

/**
 * Path to the public images directory.
 */
if (!defined('IMAGES')) {
	define('IMAGES', WWW_ROOT . 'img' . DS);
}

/**
 * Path to the tests directory.
 */
if (!defined('TESTS')) {
	define('TESTS', APP . 'Test' . DS);
}

/**
 * Path to the temporary files directory.
 */
if (!defined('TMP')) {
	define('TMP', APP . 'tmp' . DS);
}

/**
 * Path to the logs directory.
 */
if (!defined('LOGS')) {
	define('LOGS', TMP . 'logs' . DS);
}

/**
 * Path to the cache files directory. It can be shared between hosts in a multi-server setup.
 */
if (!defined('CACHE')) {
	define('CACHE', TMP . 'cache' . DS);
}

/**
 * Path to the vendors directory.
 */
if (!defined('VENDORS')) {
	define('VENDORS', ROOT . DS . 'vendors' . DS);
}

/**
 * Web path to the public images directory.
 */
if (!defined('IMAGES_URL')) {
	define('IMAGES_URL', 'img/');
}

/**
 * Web path to the CSS files directory.
 */
if (!defined('CSS_URL')) {
	define('CSS_URL', 'css/');
}

/**
 * Web path to the js files directory.
 */
if (!defined('JS_URL')) {
	define('JS_URL', 'js/');
}

require CAKE . 'basics.php';
require CAKE . 'Core' . DS . 'App.php';
require CAKE . 'Error' . DS . 'exceptions.php';

spl_autoload_register(array('App', 'load'));

App::uses('ErrorHandler', 'Error');
App::uses('Configure', 'Core');
App::uses('CakePlugin', 'Core');
App::uses('Cache', 'Cache');
App::uses('Object', 'Core');
App::uses('Multibyte', 'I18n');

/**
 * Full URL prefix
 */
if (!defined('FULL_BASE_URL')) {
	$s = null;
	if (env('HTTPS')) {
		$s = 's';
	}

	$httpHost = env('HTTP_HOST');

	if (isset($httpHost)) {
		define('FULL_BASE_URL', 'http' . $s . '://' . $httpHost);
		Configure::write('App.fullBaseUrl', FULL_BASE_URL);
	}
	unset($httpHost, $s);
}

Configure::write('App.imageBaseUrl', IMAGES_URL);
Configure::write('App.cssBaseUrl', CSS_URL);
Configure::write('App.jsBaseUrl', JS_URL);

App::$bootstrapping = true;

Configure::bootstrap(isset($boot) ? $boot : true);

if (function_exists('mb_internal_encoding')) {
	$encoding = Configure::read('App.encoding');
	if (!empty($encoding)) {
		mb_internal_encoding($encoding);
	}
	if (!empty($encoding) && function_exists('mb_regex_encoding')) {
		mb_regex_encoding($encoding);
	}
}

if (!function_exists('mb_stripos')) {

/**
 * Find position of first occurrence of a case-insensitive string.
 *
 * @param string $haystack The string from which to get the position of the first occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param int $offset The position in $haystack to start searching.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return int|bool The numeric position of the first occurrence of $needle in the $haystack string, or false
 *    if $needle is not found.
 */
	function mb_stripos($haystack, $needle, $offset = 0, $encoding = null) {
		return Multibyte::stripos($haystack, $needle, $offset);
	}

}

if (!function_exists('mb_stristr')) {

/**
 * Finds first occurrence of a string within another, case insensitive.
 *
 * @param string $haystack The string from which to get the first occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param bool $part Determines which portion of $haystack this function returns.
 *    If set to true, it returns all of $haystack from the beginning to the first occurrence of $needle.
 *    If set to false, it returns all of $haystack from the first occurrence of $needle to the end,
 *    Default value is false.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return string|bool The portion of $haystack, or false if $needle is not found.
 */
	function mb_stristr($haystack, $needle, $part = false, $encoding = null) {
		return Multibyte::stristr($haystack, $needle, $part);
	}

}

if (!function_exists('mb_strlen')) {

/**
 * Get string length.
 *
 * @param string $string The string being checked for length.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return int The number of characters in string $string having character encoding encoding.
 *    A multi-byte character is counted as 1.
 */
	function mb_strlen($string, $encoding = null) {
		return Multibyte::strlen($string);
	}

}

if (!function_exists('mb_strpos')) {

/**
 * Find position of first occurrence of a string.
 *
 * @param string $haystack The string being checked.
 * @param string $needle The position counted from the beginning of haystack.
 * @param int $offset The search offset. If it is not specified, 0 is used.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return int|bool The numeric position of the first occurrence of $needle in the $haystack string.
 *    If $needle is not found, it returns false.
 */
	function mb_strpos($haystack, $needle, $offset = 0, $encoding = null) {
		return Multibyte::strpos($haystack, $needle, $offset);
	}

}

if (!function_exists('mb_strrchr')) {

/**
 * Finds the last occurrence of a character in a string within another.
 *
 * @param string $haystack The string from which to get the last occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param bool $part Determines which portion of $haystack this function returns.
 *    If set to true, it returns all of $haystack from the beginning to the last occurrence of $needle.
 *    If set to false, it returns all of $haystack from the last occurrence of $needle to the end,
 *    Default value is false.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return string|bool The portion of $haystack. or false if $needle is not found.
 */
	function mb_strrchr($haystack, $needle, $part = false, $encoding = null) {
		return Multibyte::strrchr($haystack, $needle, $part);
	}

}

if (!function_exists('mb_strrichr')) {

/**
 * Finds the last occurrence of a character in a string within another, case insensitive.
 *
 * @param string $haystack The string from which to get the last occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param bool $part Determines which portion of $haystack this function returns.
 *    If set to true, it returns all of $haystack from the beginning to the last occurrence of $needle.
 *    If set to false, it returns all of $haystack from the last occurrence of $needle to the end,
 *    Default value is false.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return string|bool The portion of $haystack. or false if $needle is not found.
 */
	function mb_strrichr($haystack, $needle, $part = false, $encoding = null) {
		return Multibyte::strrichr($haystack, $needle, $part);
	}

}

if (!function_exists('mb_strripos')) {

/**
 * Finds position of last occurrence of a string within another, case insensitive
 *
 * @param string $haystack The string from which to get the position of the last occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param int $offset The position in $haystack to start searching.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return int|bool The numeric position of the last occurrence of $needle in the $haystack string,
 *    or false if $needle is not found.
 */
	function mb_strripos($haystack, $needle, $offset = 0, $encoding = null) {
		return Multibyte::strripos($haystack, $needle, $offset);
	}

}

if (!function_exists('mb_strrpos')) {

/**
 * Find position of last occurrence of a string in a string.
 *
 * @param string $haystack The string being checked, for the last occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param int $offset May be specified to begin searching an arbitrary number of characters into the string.
 *    Negative values will stop searching at an arbitrary point prior to the end of the string.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return int|bool The numeric position of the last occurrence of $needle in the $haystack string.
 *    If $needle is not found, it returns false.
 */
	function mb_strrpos($haystack, $needle, $offset = 0, $encoding = null) {
		return Multibyte::strrpos($haystack, $needle, $offset);
	}

}

if (!function_exists('mb_strstr')) {

/**
 * Finds first occurrence of a string within another
 *
 * @param string $haystack The string from which to get the first occurrence of $needle.
 * @param string $needle The string to find in $haystack
 * @param bool $part Determines which portion of $haystack this function returns.
 *    If set to true, it returns all of $haystack from the beginning to the first occurrence of $needle.
 *    If set to false, it returns all of $haystack from the first occurrence of $needle to the end,
 *    Default value is FALSE.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return string|bool The portion of $haystack, or true if $needle is not found.
 */
	function mb_strstr($haystack, $needle, $part = false, $encoding = null) {
		return Multibyte::strstr($haystack, $needle, $part);
	}

}

if (!function_exists('mb_strtolower')) {

/**
 * Make a string lowercase
 *
 * @param string $string The string being lowercased.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return string with all alphabetic characters converted to lowercase.
 */
	function mb_strtolower($string, $encoding = null) {
		return Multibyte::strtolower($string);
	}

}

if (!function_exists('mb_strtoupper')) {

/**
 * Make a string uppercase
 *
 * @param string $string The string being uppercased.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return string with all alphabetic characters converted to uppercase.
 */
	function mb_strtoupper($string, $encoding = null) {
		return Multibyte::strtoupper($string);
	}

}

if (!function_exists('mb_substr_count')) {

/**
 * Count the number of substring occurrences
 *
 * @param string $haystack The string being checked.
 * @param string $needle The string being found.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return int The number of times the $needle substring occurs in the $haystack string.
 */
	function mb_substr_count($haystack, $needle, $encoding = null) {
		return Multibyte::substrCount($haystack, $needle);
	}

}

if (!function_exists('mb_substr')) {

/**
 * Get part of string
 *
 * @param string $string The string being checked.
 * @param int $start The first position used in $string.
 * @param int $length The maximum length of the returned string.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return string The portion of $string specified by the $string and $length parameters.
 */
	function mb_substr($string, $start, $length = null, $encoding = null) {
		return Multibyte::substr($string, $start, $length);
	}

}

if (!function_exists('mb_encode_mimeheader')) {

/**
 * Encode string for MIME header
 *
 * @param string $str The string being encoded
 * @param string $charset specifies the name of the character set in which str is represented in.
 *    The default value is determined by the current NLS setting (mbstring.language).
 * @param string $transferEncoding specifies the scheme of MIME encoding.
 *    It should be either "B" (Base64) or "Q" (Quoted-Printable). Falls back to "B" if not given.
 * @param string $linefeed specifies the EOL (end-of-line) marker with which
 *    mb_encode_mimeheader() performs line-folding
 *    (a » RFC term, the act of breaking a line longer than a certain length into multiple lines.
 *    The length is currently hard-coded to 74 characters). Falls back to "\r\n" (CRLF) if not given.
 * @param int $indent [definition unknown and appears to have no affect]
 * @return string A converted version of the string represented in ASCII.
 */
	function mb_encode_mimeheader($str, $charset = 'UTF-8', $transferEncoding = 'B', $linefeed = "\r\n", $indent = 1) {
		return Multibyte::mimeEncode($str, $charset, $linefeed);
	}

}
