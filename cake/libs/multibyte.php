<?php
/* SVN FILE: $Id$ */
/**
 * Multibyte handling methods.
 *
 *
 * PHP versions 4 and 5
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
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 1.2.0.6833
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
if (function_exists('mb_internal_encoding')) {
	$encoding = Configure::read('App.encoding');
	if (!empty($encoding)) {
		mb_internal_encoding($encoding);
	}
}
/**
 * Find position of first occurrence of a case-insensitive string.
 *
 * @param string $haystack The string from which to get the position of the first occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param integer $offset The position in $haystack to start searching.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return integer|boolean The numeric position of the first occurrence of $needle in the $haystack string, or false
 *    if $needle is not found.
 */
if (!function_exists('mb_stripos')) {
	function mb_stripos($haystack, $needle, $offset = 0, $encoding = null) {
		return Multibyte::stripos($haystack, $needle, $offset);
	}
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
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return string|boolean The portion of $haystack, or false if $needle is not found.
 */
if (!function_exists('mb_stristr')) {
	function mb_stristr($haystack, $needle, $part = false, $encoding = null) {
		return Multibyte::stristr($haystack, $needle, $part);
	}
}
/**
 * Get string length.
 *
 * @param string $string The string being checked for length.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return integer The number of characters in string $string having character encoding encoding.
 *    A multi-byte character is counted as 1.
 */
if (!function_exists('mb_strlen')) {
	function mb_strlen($string, $encoding = null) {
		return Multibyte::strlen($string);
	}
}
/**
 * Find position of first occurrence of a string.
 *
 * @param string $haystack The string being checked.
 * @param string $needle The position counted from the beginning of haystack.
 * @param integer $offset The search offset. If it is not specified, 0 is used.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return integer|boolean The numeric position of the first occurrence of $needle in the $haystack string.
 *    If $needle is not found, it returns false.
 */
if (!function_exists('mb_strpos')) {
	function mb_strpos($haystack, $needle, $offset = 0, $encoding = null) {
		return Multibyte::strpos($haystack, $needle, $offset);
	}
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
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return string|boolean The portion of $haystack. or false if $needle is not found.
 */
if (!function_exists('mb_strrchr')) {
	function mb_strrchr($haystack, $needle, $part = false, $encoding = null) {
		return Multibyte::strrchr($haystack, $needle, $part);
	}
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
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return string|boolean The portion of $haystack. or false if $needle is not found.
 */
if (!function_exists('mb_strrichr')) {
	function mb_strrichr($haystack, $needle, $part = false, $encoding = null) {
		return Multibyte::strrichr($haystack, $needle, $part);
	}
}
/**
 * Finds position of last occurrence of a string within another, case insensitive
 *
 * @param string $haystack The string from which to get the position of the last occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param integer $offset The position in $haystack to start searching.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return integer|boolean The numeric position of the last occurrence of $needle in the $haystack string,
 *    or false if $needle is not found.
 */
if (!function_exists('mb_strripos')) {
	function mb_strripos($haystack, $needle, $offset = 0, $encoding = null) {
		return Multibyte::strripos($haystack, $needle, $offset);
	}
}
/**
 * Find position of last occurrence of a string in a string.
 *
 * @param string $haystack The string being checked, for the last occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param integer $offset May be specified to begin searching an arbitrary number of characters into the string.
 *    Negative values will stop searching at an arbitrary point prior to the end of the string.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return integer|boolean The numeric position of the last occurrence of $needle in the $haystack string.
 *    If $needle is not found, it returns false.
 */
if (!function_exists('mb_strrpos')) {
	function mb_strrpos($haystack, $needle, $offset = 0, $encoding = null) {
		return Multibyte::strrpos($haystack, $needle, $offset);
	}
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
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return string|boolean The portion of $haystack, or true if $needle is not found.
 */
if (!function_exists('mb_strstr')) {
	function mb_strstr($haystack, $needle, $part = false, $encoding = null) {
		return Multibyte::strstr($haystack, $needle, $part);
	}
}
/**
 * Make a string lowercase
 *
 * @param string $string The string being lowercased.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return string with all alphabetic characters converted to lowercase.
 */
if (!function_exists('mb_strtolower')) {
	function mb_strtolower($string, $encoding = null) {
		return Multibyte::strtolower($string);
	}
}
/**
 * Make a string uppercase
 *
 * @param string $string The string being uppercased.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return string with all alphabetic characters converted to uppercase.
 */
if (!function_exists('mb_strtoupper')) {
	function mb_strtoupper($string, $encoding = null) {
		return Multibyte::strtoupper($string);
	}
}
/**
 * Count the number of substring occurrences
 *
 * @param string $haystack The string being checked.
 * @param string $needle The string being found.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return integer The number of times the $needle substring occurs in the $haystack string.
 */
if (!function_exists('mb_substr_count')) {
	function mb_substr_count($haystack, $needle, $encoding = null) {
		return Multibyte::substrCount($haystack, $needle);
	}
}
/**
 * Get part of string
 *
 * @param string $string The string being checked.
 * @param integer $start The first position used in $string.
 * @param integer $length The maximum length of the returned string.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return string The portion of $string specified by the $string and $length parameters.
 */
if (!function_exists('mb_substr')) {
	function mb_substr($string, $start, $length = null, $encoding = null) {
		return Multibyte::substr($string, $start, $length);
	}
}
/**
 * Encode string for MIME header
 *
 * @param string $str The string being encoded
 * @param string $charset specifies the name of the character set in which str is represented in.
 *    The default value is determined by the current NLS setting (mbstring.language).
 * @param string $transfer_encoding specifies the scheme of MIME encoding.
 *    It should be either "B" (Base64) or "Q" (Quoted-Printable). Falls back to "B" if not given.
 * @param string $linefeed specifies the EOL (end-of-line) marker with which
 *    mb_encode_mimeheader() performs line-folding
 *    (a » RFC term, the act of breaking a line longer than a certain length into multiple lines.
 *    The length is currently hard-coded to 74 characters). Falls back to "\r\n" (CRLF) if not given.
 * @param integer $indent [definition unknown and appears to have no affect]
 * @return string A converted version of the string represented in ASCII.
 */
if (!function_exists('mb_encode_mimeheader')) {
	function mb_encode_mimeheader($str, $charset = 'UTF-8', $transfer_encoding = 'B', $linefeed = "\r\n", $indent = 1) {
		return Multibyte::mimeEncode($str, $charset, $linefeed);
	}
}
/**
 * Multibyte handling methods.
 *
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class Multibyte extends Object {
/**
 *  Holds the case folding values
 *
 * @var array
 * @access private
 */
	var $__caseFold = array();
/**
 * Holds an array of Unicode code point ranges
 *
 * @var array
 * @access private
 */
	var $__codeRange = array();
/**
 * Holds the current code point range
 *
 * @var string
 * @access private
 */
	var $__table = null;
/**
 * Gets a reference to the Multibyte object instance
 *
 * @return object Multibyte instance
 * @access public
 * @static
 */
	function &getInstance() {
		static $instance = array();

		if (!$instance) {
			$instance[0] =& new Multibyte();
		}
		return $instance[0];
	}
/**
 * Converts a multibyte character string
 * to the decimal value of the character
 *
 * @param multibyte string $string
 * @return array
 * @access public
 * @static
 */
	function utf8($string) {
		$map = array();

		$values = array();
		$find = 1;
		$length = strlen($string);

		for ($i = 0; $i < $length; $i++) {
			$value = ord($string[$i]);

			if ($value < 128) {
				$map[] = $value;
			} else {
				if (empty($values)) {
					$find = ($value < 224) ? 2 : 3;
				}
				$values[] = $value;

				if (count($values) === $find) {
					if ($find == 3) {
						$map[] = (($values[0] % 16) * 4096) + (($values[1] % 64) * 64) + ($values[2] % 64);
					} else {
						$map[] = (($values[0] % 32) * 64) + ($values[1] % 64);
					}
					$values = array();
					$find = 1;
				}
			}
		}
		return $map;
	}
/**
 * Converts the decimal value of a multibyte character string
 * to a string
 *
 * @param array $array
 * @return string
 * @access public
 * @static
 */
	function ascii($array) {
		$ascii = '';

		foreach ($array as $utf8) {
			if ($utf8 < 128) {
				$ascii .= chr($utf8);
			} elseif ($utf8 < 2048) {
				$ascii .= chr(192 + (($utf8 - ($utf8 % 64)) / 64));
				$ascii .= chr(128 + ($utf8 % 64));
			} else {
				$ascii .= chr(224 + (($utf8 - ($utf8 % 4096)) / 4096));
				$ascii .= chr(128 + ((($utf8 % 4096) - ($utf8 % 64)) / 64));
				$ascii .= chr(128 + ($utf8 % 64));
			}
		}
		return $ascii;
	}
/**
 * Find position of first occurrence of a case-insensitive string.
 *
 * @param multi-byte string $haystack The string from which to get the position of the first occurrence of $needle.
 * @param multi-byte string $needle The string to find in $haystack.
 * @param integer $offset The position in $haystack to start searching.
 * @return integer|boolean The numeric position of the first occurrence of $needle in the $haystack string,
 *    or false if $needle is not found.
 * @access public
 * @static
 */
	function stripos($haystack, $needle, $offset = 0) {
		if (!PHP5 || Multibyte::checkMultibyte($haystack)) {
			$haystack = Multibyte::strtoupper($haystack);
			$needle = Multibyte::strtoupper($needle);
			return Multibyte::strpos($haystack, $needle, $offset);
		}
		return stripos($haystack, $needle, $offset);
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
 * @return int|boolean The portion of $haystack, or false if $needle is not found.
 * @access public
 * @static
 */
	function stristr($haystack, $needle, $part = false) {
		$php = (PHP_VERSION < 5.3);

		if (($php && $part) || Multibyte::checkMultibyte($haystack)) {
			$check = Multibyte::strtoupper($haystack);
			$check = Multibyte::utf8($check);
			$found = false;

			$haystack = Multibyte::utf8($haystack);
			$haystackCount = count($haystack);

			$needle = Multibyte::strtoupper($needle);
			$needle = Multibyte::utf8($needle);
			$needleCount = count($needle);

			$parts = array();
			$position = 0;

			while (($found === false) && ($position < $haystackCount)) {
				if (isset($needle[0]) && $needle[0] === $check[$position]) {
					for ($i = 1; $i < $needleCount; $i++) {
						if ($needle[$i] !== $check[$position + $i]) {
							break;
						}
					}
					if ($i === $needleCount) {
						$found = true;
					}
				}
				if (!$found) {
					$parts[] = $haystack[$position];
					unset($haystack[$position]);
				}
				$position++;
			}

			if ($found && $part && !empty($parts)) {
				return Multibyte::ascii($parts);
			} elseif ($found && !empty($haystack)) {
				return Multibyte::ascii($haystack);
			}
			return false;
		}

		if (!$php) {
			return stristr($haystack, $needle, $part);
		}
		return stristr($haystack, $needle);
	}
/**
 * Get string length.
 *
 * @param string $string The string being checked for length.
 * @return integer The number of characters in string $string
 * @access public
 * @static
 */
	function strlen($string) {
		if (Multibyte::checkMultibyte($string)) {
			$string = Multibyte::utf8($string);
			return count($string);
		}
		return strlen($string);
	}
/**
 * Find position of first occurrence of a string.
 *
 * @param string $haystack The string being checked.
 * @param string $needle The position counted from the beginning of haystack.
 * @param integer $offset The search offset. If it is not specified, 0 is used.
 * @return integer|boolean The numeric position of the first occurrence of $needle in the $haystack string.
 *    If $needle is not found, it returns false.
 * @access public
 * @static
 */
	function strpos($haystack, $needle, $offset = 0) {
		if (Multibyte::checkMultibyte($haystack)) {
			$found = false;

			$haystack = Multibyte::utf8($haystack);
			$haystackCount = count($haystack);

			$needle = Multibyte::utf8($needle);
			$needleCount = count($needle);

			$position = $offset;

			while (($found === false) && ($position < $haystackCount)) {
				if (isset($needle[0]) && $needle[0] === $haystack[$position]) {
					for ($i = 1; $i < $needleCount; $i++) {
						if ($needle[$i] !== $haystack[$position + $i]) {
							break;
						}
					}
					if ($i === $needleCount) {
						$found = true;
						$position--;
					}
				}
				$position++;
			}
			if ($found) {
				return $position;
			}
			return false;
		}
		return strpos($haystack, $needle, $offset);
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
 * @access public
 * @static
 */
	function strrchr($haystack, $needle, $part = false) {
		$check = Multibyte::utf8($haystack);
		$found = false;

		$haystack = Multibyte::utf8($haystack);
		$haystackCount = count($haystack);

		$matches = array_count_values($check);

		$needle = Multibyte::utf8($needle);
		$needleCount = count($needle);

		$parts = array();
		$position = 0;

		while (($found === false) && ($position < $haystackCount)) {
			if (isset($needle[0]) && $needle[0] === $check[$position]) {
				for ($i = 1; $i < $needleCount; $i++) {
					if ($needle[$i] !== $check[$position + $i]) {
						if ($needle[$i] === $check[($position + $i) -1]) {
							$found = true;
						}
						unset($parts[$position - 1]);
						$haystack = array_merge(array($haystack[$position]), $haystack);
						break;
					}
				}
				if (isset($matches[$needle[0]]) && $matches[$needle[0]] > 1) {
					$matches[$needle[0]] = $matches[$needle[0]] - 1;
				} elseif ($i === $needleCount) {
					$found = true;
				}
			}

			if (!$found && isset($haystack[$position])) {
				$parts[] = $haystack[$position];
				unset($haystack[$position]);
			}
			$position++;
		}

		if ($found && $part && !empty($parts)) {
			return Multibyte::ascii($parts);
		} elseif ($found && !empty($haystack)) {
			return Multibyte::ascii($haystack);
		}
		return false;
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
 * @access public
 * @static
 */
	function strrichr($haystack, $needle, $part = false) {
		$check = Multibyte::strtoupper($haystack);
		$check = Multibyte::utf8($check);
		$found = false;

		$haystack = Multibyte::utf8($haystack);
		$haystackCount = count($haystack);

		$matches = array_count_values($check);

		$needle = Multibyte::strtoupper($needle);
		$needle = Multibyte::utf8($needle);
		$needleCount = count($needle);

		$parts = array();
		$position = 0;

		while (($found === false) && ($position < $haystackCount)) {
			if (isset($needle[0]) && $needle[0] === $check[$position]) {
				for ($i = 1; $i < $needleCount; $i++) {
					if ($needle[$i] !== $check[$position + $i]) {
						if ($needle[$i] === $check[($position + $i) -1]) {
							$found = true;
						}
						unset($parts[$position - 1]);
						$haystack = array_merge(array($haystack[$position]), $haystack);
						break;
					}
				}
				if (isset($matches[$needle[0]]) && $matches[$needle[0]] > 1) {
					$matches[$needle[0]] = $matches[$needle[0]] - 1;
				} elseif ($i === $needleCount) {
					$found = true;
				}
			}

			if (!$found && isset($haystack[$position])) {
				$parts[] = $haystack[$position];
				unset($haystack[$position]);
			}
			$position++;
		}

		if ($found && $part && !empty($parts)) {
			return Multibyte::ascii($parts);
		} elseif ($found && !empty($haystack)) {
			return Multibyte::ascii($haystack);
		}
		return false;
	}
/**
 * Finds position of last occurrence of a string within another, case insensitive
 *
 * @param string $haystack The string from which to get the position of the last occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param integer $offset The position in $haystack to start searching.
 * @return integer|boolean The numeric position of the last occurrence of $needle in the $haystack string,
 *    or false if $needle is not found.
 * @access public
 * @static
 */
	function strripos($haystack, $needle, $offset = 0) {
		if (!PHP5 || Multibyte::checkMultibyte($haystack)) {
			$found = false;
			$haystack = Multibyte::strtoupper($haystack);
			$haystack = Multibyte::utf8($haystack);
			$haystackCount = count($haystack);

			$matches = array_count_values($haystack);

			$needle = Multibyte::strtoupper($needle);
			$needle = Multibyte::utf8($needle);
			$needleCount = count($needle);

			$position = $offset;

			while (($found === false) && ($position < $haystackCount)) {
				if (isset($needle[0]) && $needle[0] === $haystack[$position]) {
					for ($i = 1; $i < $needleCount; $i++) {
						if ($needle[$i] !== $haystack[$position + $i]) {
							if ($needle[$i] === $haystack[($position + $i) -1]) {
								$position--;
								$found = true;
								continue;
							}
						}
					}

					if (!$offset && isset($matches[$needle[0]]) && $matches[$needle[0]] > 1) {
						$matches[$needle[0]] = $matches[$needle[0]] - 1;
					} elseif ($i === $needleCount) {
						$found = true;
						$position--;
					}
				}
				$position++;
			}
			return ($found) ? $position : false;
		}
		return strripos($haystack, $needle, $offset);
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
 * @access public
 * @static
 */
	function strrpos($haystack, $needle, $offset = 0) {
		if (!PHP5 || Multibyte::checkMultibyte($haystack)) {
			$found = false;

			$haystack = Multibyte::utf8($haystack);
			$haystackCount = count($haystack);

			$matches = array_count_values($haystack);

			$needle = Multibyte::utf8($needle);
			$needleCount = count($needle);

			$position = $offset;

			while (($found === false) && ($position < $haystackCount)) {
				if (isset($needle[0]) && $needle[0] === $haystack[$position]) {
					for ($i = 1; $i < $needleCount; $i++) {
						if ($needle[$i] !== $haystack[$position + $i]) {
							if ($needle[$i] === $haystack[($position + $i) -1]) {
								$position--;
								$found = true;
								continue;
							}
						}
					}

					if (!$offset && isset($matches[$needle[0]]) && $matches[$needle[0]] > 1) {
						$matches[$needle[0]] = $matches[$needle[0]] - 1;
					} elseif ($i === $needleCount) {
						$found = true;
						$position--;
					}
				}
				$position++;
			}
			return ($found) ? $position : false;
		}
		return strrpos($haystack, $needle, $offset);
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
 * @access public
 * @static
 */
	function strstr($haystack, $needle, $part = false) {
		$php = (PHP_VERSION < 5.3);

		if (($php && $part) || Multibyte::checkMultibyte($haystack)) {
			$check = Multibyte::utf8($haystack);
			$found = false;

			$haystack = Multibyte::utf8($haystack);
			$haystackCount = count($haystack);

			$needle = Multibyte::utf8($needle);
			$needleCount = count($needle);

			$parts = array();
			$position = 0;

			while (($found === false) && ($position < $haystackCount)) {
				if (isset($needle[0]) && $needle[0] === $check[$position]) {
					for ($i = 1; $i < $needleCount; $i++) {
						if ($needle[$i] !== $check[$position + $i]) {
							break;
						}
					}
					if ($i === $needleCount) {
						$found = true;
					}
				}
				if (!$found) {
					$parts[] = $haystack[$position];
					unset($haystack[$position]);
				}
				$position++;
			}

			if ($found && $part && !empty($parts)) {
				return Multibyte::ascii($parts);
			} elseif ($found && !empty($haystack)) {
				return Multibyte::ascii($haystack);
			}
			return false;
		}

		if (!$php) {
			return strstr($haystack, $needle, $part);
		}
		return strstr($haystack, $needle);
	}
/**
 * Make a string lowercase
 *
 * @param string $string The string being lowercased.
 * @return string with all alphabetic characters converted to lowercase.
 * @access public
 * @static
 */
	function strtolower($string) {
		$_this =& Multibyte::getInstance();
		$utf8Map = Multibyte::utf8($string);

		$length = count($utf8Map);
		$lowerCase = array();
		$matched = false;

		for ($i = 0 ; $i < $length; $i++) {
			$char = $utf8Map[$i];

			if ($char < 128) {
				$str = strtolower(chr($char));
				$strlen = strlen($str);
				for ($ii = 0 ; $ii < $strlen; $ii++) {
					$lower = ord(substr($str, $ii, 1));
				}
				$lowerCase[] = $lower;
				$matched = true;
			} else {
				$matched = false;
				$keys = $_this->__find($char, 'upper');

				if (!empty($keys)) {
					foreach ($keys as $key => $value) {
						if ($keys[$key]['upper'] == $char && count($keys[$key]['lower'][0]) === 1) {
							$lowerCase[] = $keys[$key]['lower'][0];
							$matched = true;
							break 1;
						}
					}
				}
			}
			if ($matched === false) {
				$lowerCase[] = $char;
			}
		}
		return Multibyte::ascii($lowerCase);
	}
/**
 * Make a string uppercase
 *
 * @param string $string The string being uppercased.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return string with all alphabetic characters converted to uppercase.
 * @access public
 * @static
 */
	function strtoupper($string) {
		$_this =& Multibyte::getInstance();
		$utf8Map = Multibyte::utf8($string);

		$length = count($utf8Map);
		$matched = false;
		$replaced = array();
		$upperCase = array();

		for ($i = 0 ; $i < $length; $i++) {
			$char = $utf8Map[$i];

			if ($char < 128) {
				$str = strtoupper(chr($char));
				$strlen = strlen($str);
				for ($ii = 0 ; $ii < $strlen; $ii++) {
					$upper = ord(substr($str, $ii, 1));
				}
				$upperCase[] = $upper;
				$matched = true;

			} else {
				$matched = false;
				$keys = $_this->__find($char);
				$keyCount = count($keys);

				if (!empty($keys)) {
					foreach ($keys as $key => $value) {
						$matched = false;
						$replace = 0;
						if ($length > 1 && count($keys[$key]['lower']) > 1) {
							$j = 0;

							for ($ii = 0, $count = count($keys[$key]['lower']); $ii < $count; $ii++) {
								$nextChar = $utf8Map[$i + $ii];

								if (isset($nextChar) && ($nextChar == $keys[$key]['lower'][$j + $ii])) {
									$replace++;
								}
							}
							if ($replace == $count) {
								$upperCase[] = $keys[$key]['upper'];
								$replaced = array_merge($replaced, array_values($keys[$key]['lower']));
								$matched = true;
								break 1;
							}
						} elseif ($length > 1 && $keyCount > 1) {
							$j = 0;
							for ($ii = 1; $ii < $keyCount; $ii++) {
								$nextChar = $utf8Map[$i + $ii - 1];

								if (in_array($nextChar, $keys[$ii]['lower'])) {

									for ($jj = 0, $count = count($keys[$ii]['lower']); $jj < $count; $jj++) {
										$nextChar = $utf8Map[$i + $jj];

										if (isset($nextChar) && ($nextChar == $keys[$ii]['lower'][$j + $jj])) {
											$replace++;
										}
									}
									if ($replace == $count) {
										$upperCase[] = $keys[$ii]['upper'];
										$replaced = array_merge($replaced, array_values($keys[$ii]['lower']));
										$matched = true;
										break 2;
									}
								}
							}
						}
						if ($keys[$key]['lower'][0] == $char) {
							$upperCase[] = $keys[$key]['upper'];
							$matched = true;
							break 1;
						}
					}
				}
			}
			if ($matched === false && !in_array($char, $replaced, true)) {
				$upperCase[] = $char;
			}
		}
		return Multibyte::ascii($upperCase);
	}
/**
 * Count the number of substring occurrences
 *
 * @param string $haystack The string being checked.
 * @param string $needle The string being found.
 * @return integer The number of times the $needle substring occurs in the $haystack string.
 * @access public
 * @static
 */
	function substrCount($haystack, $needle) {
		$count = 0;
		$haystack = Multibyte::utf8($haystack);
		$haystackCount = count($haystack);
		$matches = array_count_values($haystack);
		$needle = Multibyte::utf8($needle);
		$needleCount = count($needle);

		if ($needleCount === 1 && isset($matches[$needle[0]])) {
			return $matches[$needle[0]];
		}

		for ($i = 0; $i < $haystackCount; $i++) {
			if (isset($needle[0]) && $needle[0] === $haystack[$i]) {
				for ($ii = 1; $ii < $needleCount; $ii++) {
					if ($needle[$ii] === $haystack[$i + 1]) {
						if ((isset($needle[$ii + 1]) && $haystack[$i + 2]) && $needle[$ii + 1] !== $haystack[$i + 2]) {
							$count--;
						} else {
							$count++;
						}
					}
				}
			}
		}
		return $count;
	}
/**
 * Get part of string
 *
 * @param string $string The string being checked.
 * @param integer $start The first position used in $string.
 * @param integer $length The maximum length of the returned string.
 * @return string The portion of $string specified by the $string and $length parameters.
 * @access public
 * @static
 */
	function substr($string, $start, $length = null) {
		if ($start === 0 && $length === null) {
			return $string;
		}

		$string = Multibyte::utf8($string);
		$stringCount = count($string);

		for ($i = 1; $i <= $start; $i++) {
			unset($string[$i - 1]);
		}

		if ($length === null || count($string) < $length) {
			return Multibyte::ascii($string);
		}
		$string = array_values($string);

		$value = array();
		for ($i = 0; $i < $length; $i++) {
			$value[] = $string[$i];
		}
		return Multibyte::ascii($value);
	}
/**
 * Prepare a string for mail transport, using the provided encoding
 *
 * @param string $string value to encode
 * @param string $charset charset to use for encoding. defaults to UTF-8
 * @param string $newline
 * @return string
 * @access public
 * @static
 * @TODO: add support for 'Q'('Quoted Printable') encoding
 */
	function mimeEncode($string, $charset = null, $newline = "\r\n") {
		if (!Multibyte::checkMultibyte($string) && strlen($string) < 75) {
			return $string;
		}

		if (empty($charset)) {
			$charset = Configure::read('App.encoding');
		}
		$charset = strtoupper($charset);

		$start = '=?' . $charset . '?B?';
		$end = '?=';
		$spacer = $end . $newline . ' ' . $start;

		$length = 75 - strlen($start) - strlen($end);
		$length = $length - ($length % 4);
		if ($charset == 'UTF-8') {
			$parts = array();
			$maxchars = floor(($length * 3) / 4);
			while (strlen($string) > $maxchars) {
				$i = $maxchars;
				$test = ord($string[$i]);
				while ($test >= 128 && $test <= 191) {
					$i--;
					$test = ord($string[$i]);
				}
				$parts[] = base64_encode(substr($string, 0, $i));
				$string = substr($string, $i);
			}
			$parts[] = base64_encode($string);
			$string = implode($spacer, $parts);
		} else {
			$string = chunk_split(base64_encode($string), $length, $spacer);
			$string = preg_replace('/' . preg_quote($spacer) . '$/', '', $string);
		}
		return $start . $string . $end;
	}
/**
 * Return the Code points range for Unicode characters
 *
 * @param interger $decimal
 * @return string
 * @access private
 */
	function __codepoint ($decimal) {
		if ($decimal > 128 && $decimal < 256)  {
			$return = '0080_00ff'; // Latin-1 Supplement
		} elseif ($decimal < 384) {
			$return = '0100_017f'; // Latin Extended-A
		} elseif ($decimal < 592) {
			$return = '0180_024F'; // Latin Extended-B
		} elseif ($decimal < 688) {
			$return = '0250_02af'; // IPA Extensions
		} elseif ($decimal >= 880 && $decimal < 1024) {
			$return = '0370_03ff'; // Greek and Coptic
		} elseif ($decimal < 1280) {
			$return = '0400_04ff'; // Cyrillic
		} elseif ($decimal < 1328) {
			$return = '0500_052f'; // Cyrillic Supplement
		} elseif ($decimal < 1424) {
			$return = '0530_058f'; // Armenian
		} elseif ($decimal >= 7680 && $decimal < 7936) {
			$return = '1e00_1eff'; // Latin Extended Additional
		} elseif ($decimal < 8192) {
			$return = '1f00_1fff'; // Greek Extended
		} elseif ($decimal >= 8448 && $decimal < 8528) {
			$return = '2100_214f'; // Letterlike Symbols
		} elseif ($decimal < 8592) {
			$return = '2150_218f'; // Number Forms
		} elseif ($decimal >= 9312 && $decimal < 9472) {
			$return = '2460_24ff'; // Enclosed Alphanumerics
		} elseif ($decimal >= 11264 && $decimal < 11360) {
			$return = '2c00_2c5f'; // Glagolitic
		} elseif ($decimal < 11392) {
			$return = '2c60_2c7f'; // Latin Extended-C
		} elseif ($decimal < 11520) {
			$return = '2c80_2cff'; // Coptic
		} elseif ($decimal >= 65280 && $decimal < 65520) {
			$return = 'ff00_ffef'; // Halfwidth and Fullwidth Forms
		} else {
			$return = false;
		}
		$this->__codeRange[$decimal] = $return;
		return $return;
	}
/**
 * Find the related code folding values for $char
 *
 * @param integer $char decimal value of character
 * @param string $type
 * @return array
 * @access private
 */
	function __find($char, $type = 'lower') {
		$value = false;
		$found = array();
		if (!isset($this->__codeRange[$char])) {
			$range = $this->__codepoint($char);
			if ($range === false) {
				return null;
			}
			Configure::load('unicode' . DS . 'casefolding' . DS . $range);
			$this->__caseFold[$range] = Configure::read($range);
			Configure::delete($range);
		}

		if (!$this->__codeRange[$char]) {
			return null;
		}
		$this->__table = $this->__codeRange[$char];
		$count = count($this->__caseFold[$this->__table]);

		for ($i = 0; $i < $count; $i++) {
			if ($type === 'lower' && $this->__caseFold[$this->__table][$i][$type][0] === $char) {
				$found[] = $this->__caseFold[$this->__table][$i];
			} elseif ($type === 'upper' && $this->__caseFold[$this->__table][$i][$type] === $char) {
				$found[] = $this->__caseFold[$this->__table][$i];
			}
		}
		return $found;
	}
/**
 * Check the $string for multibyte characters
 * @param string $string value to test
 * @return boolean
 * @access public
 * @static
 */
	function checkMultibyte($string) {
		$length = strlen($string);

		for ($i = 0; $i < $length; $i++ ) {
			$value = ord(($string[$i]));
			if ($value > 128) {
				return true;
			}
		}
		return false;
	}
}
?>