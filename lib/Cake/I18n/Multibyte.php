<?php
/**
 * Multibyte handling methods.
 *
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
 * @package       Cake.I18n
 * @since         CakePHP(tm) v 1.2.0.6833
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Cake\I18n;

use Cake\Utility\String;

/**
 * Multibyte handling methods.
 *
 * @package       Cake.I18n
 * @deprecated
 */
class Multibyte {

/**
 * Converts a multibyte character string
 * to the decimal value of the character
 *
 * @param string $string
 * @return array
 * @deprecated
 * @see Cake\Utility\String::utf8
 */
	public static function utf8($string) {
		return String::utf8($string);
	}

/**
 * Converts the decimal value of a multibyte character string
 * to a string
 *
 * @param array $array
 * @return string
 * @deprecated
 * @see Cake\Utility\String::ascii
 */
	public static function ascii($array) {
		return String::ascii($array);
	}

/**
 * Find position of first occurrence of a case-insensitive string.
 *
 * @param string $haystack The string from which to get the position of the first occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param integer $offset The position in $haystack to start searching.
 * @return integer|boolean The numeric position of the first occurrence of $needle in the $haystack string,
 *    or false if $needle is not found.
 * @deprecated
 * @see mb_stripos function
 */
	public static function stripos($haystack, $needle, $offset = 0) {
		return mb_stripos($haystack, $needle, $offset);
	}

/**
 * Finds first occurrence of a string within another, case insensitive.
 *
 * @param string $haystack The string from which to get the first occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param boolean $part Determines which portion of $haystack this function returns.
 *    If set to true, it returns all of $haystack from the beginning to the first occurrence of $needle.
 *    If set to false, it returns all of $haystack from the first occurrence of $needle to the end,
 *    Default value is false.
 * @return integer|boolean The portion of $haystack, or false if $needle is not found.
 * @deprecated
 * @see mb_stristr function
 */
	public static function stristr($haystack, $needle, $part = false) {
		return mb_stristr($haystack, $needle, $part);
	}

/**
 * Get string length.
 *
 * @param string $string The string being checked for length.
 * @return integer The number of characters in string $string
 * @deprecated
 * @see mb_strlen function
 */
	public static function strlen($string) {
		return mb_strlen($string);
	}

/**
 * Find position of first occurrence of a string.
 *
 * @param string $haystack The string being checked.
 * @param string $needle The position counted from the beginning of haystack.
 * @param integer $offset The search offset. If it is not specified, 0 is used.
 * @return integer|boolean The numeric position of the first occurrence of $needle in the $haystack string.
 *    If $needle is not found, it returns false.
 * @deprecated
 * @see mb_strpos function
 */
	public static function strpos($haystack, $needle, $offset = 0) {
		return mb_strpos($haystack, $needle, $offset);
	}

/**
 * Finds the last occurrence of a character in a string within another.
 *
 * @param string $haystack The string from which to get the last occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param boolean $part Determines which portion of $haystack this function returns.
 *    If set to true, it returns all of $haystack from the beginning to the last occurrence of $needle.
 *    If set to false, it returns all of $haystack from the last occurrence of $needle to the end,
 *    Default value is false.
 * @return string|boolean The portion of $haystack. or false if $needle is not found.
 * @deprecated
 * @see mb_strrchr function
 */
	public static function strrchr($haystack, $needle, $part = false) {
		return mb_strrchr($haystack, $needle, $part);
	}

/**
 * Finds the last occurrence of a character in a string within another, case insensitive.
 *
 * @param string $haystack The string from which to get the last occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param boolean $part Determines which portion of $haystack this function returns.
 *    If set to true, it returns all of $haystack from the beginning to the last occurrence of $needle.
 *    If set to false, it returns all of $haystack from the last occurrence of $needle to the end,
 *    Default value is false.
 * @return string|boolean The portion of $haystack. or false if $needle is not found.
 * @deprecated
 * @see mb_strrichr function
 */
	public static function strrichr($haystack, $needle, $part = false) {
		return mb_strrichr($haystack, $needle, $part);
	}

/**
 * Finds position of last occurrence of a string within another, case insensitive
 *
 * @param string $haystack The string from which to get the position of the last occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param integer $offset The position in $haystack to start searching.
 * @return integer|boolean The numeric position of the last occurrence of $needle in the $haystack string,
 *    or false if $needle is not found.
 * @deprecated
 * @see mb_strripos function
 */
	public static function strripos($haystack, $needle, $offset = 0) {
		return mb_strripos($haystack, $needle, $offset);
	}

/**
 * Find position of last occurrence of a string in a string.
 *
 * @param string $haystack The string being checked, for the last occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param integer $offset May be specified to begin searching an arbitrary number of characters into the string.
 *    Negative values will stop searching at an arbitrary point prior to the end of the string.
 * @return integer|boolean The numeric position of the last occurrence of $needle in the $haystack string.
 *    If $needle is not found, it returns false.
 * @deprecated
 * @see strrpos function
 */
	public static function strrpos($haystack, $needle, $offset = 0) {
		return mb_strrpos($haystack, $needle, $offset);
	}

/**
 * Finds first occurrence of a string within another
 *
 * @param string $haystack The string from which to get the first occurrence of $needle.
 * @param string $needle The string to find in $haystack
 * @param boolean $part Determines which portion of $haystack this function returns.
 *    If set to true, it returns all of $haystack from the beginning to the first occurrence of $needle.
 *    If set to false, it returns all of $haystack from the first occurrence of $needle to the end,
 *    Default value is FALSE.
 * @return string|boolean The portion of $haystack, or true if $needle is not found.
 * @deprecated
 * @see mb_strstr function
 */
	public static function strstr($haystack, $needle, $part = false) {
		return mb_strstr($haystack, $needle, $part);
	}

/**
 * Make a string lowercase
 *
 * @param string $string The string being lowercased.
 * @return string with all alphabetic characters converted to lowercase.
 * @deprecated
 * @see mb_strtolower function
 */
	public static function strtolower($string) {
		return mb_strtolower($string);
	}

/**
 * Make a string uppercase
 *
 * @param string $string The string being uppercased.
 * @return string with all alphabetic characters converted to uppercase.
 * @deprecated
 * @see mb_strtoupper function
 */
	public static function strtoupper($string) {
		return mb_strtoupper($string);
	}

/**
 * Count the number of substring occurrences
 *
 * @param string $haystack The string being checked.
 * @param string $needle The string being found.
 * @return integer The number of times the $needle substring occurs in the $haystack string.
 * @deprecated
 * @see mb_substr_count function
 */
	public static function substrCount($haystack, $needle) {
		return mb_substr_count($haystack, $needle);
	}

/**
 * Get part of string
 *
 * @param string $string The string being checked.
 * @param integer $start The first position used in $string.
 * @param integer $length The maximum length of the returned string.
 * @return string The portion of $string specified by the $string and $length parameters.
 * @deprecated
 * @see mb_substr function
 */
	public static function substr($string, $start, $length = null) {
		return mb_substr($string, $start, $length);
	}

/**
 * Prepare a string for mail transport, using the provided encoding
 *
 * @param string $string value to encode
 * @param string $charset charset to use for encoding. defaults to UTF-8
 * @param string $newline
 * @return string
 * @deprecated
 * @see mb_encode_mimeheader function
 */
	public static function mimeEncode($string, $charset = null, $newline = "\r\n") {
		return mb_encode_mimeheader($string, $charset, 'B', $newline);
	}

/**
 * Check the $string for multibyte characters
 *
 * @param string $string value to test
 * @return boolean
 * @deprecated
 * @see Cake\Utility\String::isMultibyte
 */
	public static function checkMultibyte($string) {
		return String::isMultibyte($string);
	}

}
