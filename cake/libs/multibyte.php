<?php
/* SVN FILE: $Id$ */
/**
 * Multibyte handling methods.
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *			1785 E. Sahara Avenue, Suite 490-204
 *			Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 1.2.0.6833
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
if (function_exists('mb_internal_encoding')) {
	mb_internal_encoding(Configure::read('App.encoding'));
}
/**
 * Find position of first occurrence of a case-insensitive string.
 *
 * @param string $haystack The string from which to get the position of the first occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param integer $offset The position in $haystack to start searching.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return integer|boolean The numeric position of the first occurrence of $needle in the $haystack string, or false if $needle is not found.
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
 *                If set to true, it returns all of $haystack from the beginning to the first occurrence of $needle.
 *                If set to false, it returns all of $haystack from the first occurrence of $needle to the end, Default value is false.
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
 *                 A multi-byte character is counted as 1.
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
 *                         If $needle is not found, it returns false.
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
 *                      If set to true, it returns all of $haystack from the beginning to the last occurrence of $needle.
 *                      If set to false, it returns all of $haystack from the last occurrence of $needle to the end, Default value is false.
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
 *                      If set to true, it returns all of $haystack from the beginning to the last occurrence of $needle.
 *                      If set to false, it returns all of $haystack from the last occurrence of $needle to the end, Default value is false.
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
 * @return integer|boolean The numeric position of the last occurrence of $needle in the $haystack string, or false if $needle is not found.
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
 *                        Negative values will stop searching at an arbitrary point prior to the end of the string.
 * @param string $encoding Character encoding name to use. If it is omitted, internal character encoding is used.
 * @return integer|boolean The numeric position of the last occurrence of $needle in the $haystack string. If $needle is not found, it returns false.
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
 *                      If set to true, it returns all of $haystack from the beginning to the first occurrence of $needle.
 *                      If set to false, it returns all of $haystack from the first occurrence of $needle to the end, Default value is FALSE.
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
 * @access public
 * @static
 */
if (!function_exists('mb_substr')) {
	function mb_substr($string, $start, $length = null, $encoding = null) {
		return Multibyte::substr($string, $start, $length);
	}
}
/**
 * Multibyte handling methods.
 *
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class Multibyte extends Object {
/**
 * Holds the decimal value of a multi-byte character
 *
 * @var array
 * @access private
 */
	var $__utf8Map = array();
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

		if (!isset($instance[0]) || !$instance[0]) {
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
		$_this =& Multibyte::getInstance();
		$_this->__reset();

		$values = array();
		$find = 1;
		$length = strlen($string);

		for ($i = 0; $i < $length; $i++ ) {
			$value = ord(($string[$i]));

			if ($value < 128) {
				$_this->__utf8Map[] = $value;

			} else {
				if (count($values) == 0) {
					$find = ife($value < 224, 2, 3);
				}
				$values[] = $value;

				if (count($values) === $find) {
						if ($find == 3) {
							$_this->__utf8Map[] = (($values[0] % 16) * 4096) + (($values[1] % 64) * 64) + ($values[2] % 64);

						} else {
							$_this->__utf8Map[] = (($values[0] % 32) * 64) + ($values[1] % 64);

						}
					$values = array();
					$find = 1;
				}
			}
		}
		return $_this->__utf8Map;
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

		foreach($array as $utf8) {
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
 * @return integer|boolean The numeric position of the first occurrence of $needle in the $haystack string, or false if $needle is not found.
 * @access public
 * @static
 */
	function stripos($haystack, $needle, $offset = 0) {
		$_this =& Multibyte::getInstance();

		if (!PHP5 || $_this->__checkMultibyte($haystack)) {
			$haystack = $_this->strtoupper($haystack);
			$needle = $_this->strtoupper($needle);
			return $_this->strpos($haystack, $needle, $offset);
		}
		return stripos($haystack, $needle, $offset);
	}
/**
 * Finds first occurrence of a string within another, case insensitive.
 *
 * @param string $haystack The string from which to get the first occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param boolean $part Determines which portion of $haystack this function returns.
 *                If set to true, it returns all of $haystack from the beginning to the first occurrence of $needle.
 *                If set to false, it returns all of $haystack from the first occurrence of $needle to the end, Default value is false.
 * @return int|boolean The portion of $haystack, or false if $needle is not found.
 * @access public
 * @static
 */
	function stristr($haystack, $needle, $part = false) {
		$_this =& Multibyte::getInstance();
		$php = (phpversion() < 5.3);

		if (($php && $part) || $_this->__checkMultibyte($haystack)) {
			$check = $_this->strtoupper($haystack);
			$check = $_this->utf8($check);
			$found = false;
			$haystack = $_this->utf8($haystack);
			$haystackCount = count($haystack);
			$needle = $_this->strtoupper($needle);
			$needle = $_this->utf8($needle);
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
				return $_this->ascii($parts);
			} elseif ($found && !empty($haystack)) {
				return $_this->ascii($haystack);
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
		$_this =& Multibyte::getInstance();
		if ($_this->__checkMultibyte($string)) {
			$string = $_this->utf8($string);
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
 *                         If $needle is not found, it returns false.
 * @access public
 * @static
 */
	function strpos($haystack, $needle, $offset = 0) {
		$_this =& Multibyte::getInstance();

		if ($_this->__checkMultibyte($haystack)) {
			$found = false;
			$haystack = $_this->utf8($haystack);
			$haystackCount = count($haystack);
			$needle = $_this->utf8($needle);
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
 *                      If set to true, it returns all of $haystack from the beginning to the last occurrence of $needle.
 *                      If set to false, it returns all of $haystack from the last occurrence of $needle to the end, Default value is false.
 * @return string|boolean The portion of $haystack. or false if $needle is not found.
 * @access public
 * @static
 */
	function strrchr($haystack, $needle, $part = false) {
		$_this =& Multibyte::getInstance();

		$check = $_this->utf8($haystack);
		$found = false;
		$haystack = $_this->utf8($haystack);
		$haystackCount = count($haystack);
		$matches = array_count_values($check);
		$needle = $_this->utf8($needle);
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
			return $_this->ascii($parts);
		} elseif ($found && !empty($haystack)) {
			return $_this->ascii($haystack);
		}
		return false;
	}
/**
 * Finds the last occurrence of a character in a string within another, case insensitive.
 *
 * @param string $haystack The string from which to get the last occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param boolean $part Determines which portion of $haystack this function returns.
 *                      If set to true, it returns all of $haystack from the beginning to the last occurrence of $needle.
 *                      If set to false, it returns all of $haystack from the last occurrence of $needle to the end, Default value is false.
 * @return string|boolean The portion of $haystack. or false if $needle is not found.
 * @access public
 * @static
 */
	function strrichr($haystack, $needle, $part = false) {
		$_this =& Multibyte::getInstance();

		$check = $_this->strtoupper($haystack);
		$check = $_this->utf8($check);
		$found = false;
		$haystack = $_this->utf8($haystack);
		$haystackCount = count($haystack);
		$matches = array_count_values($check);
		$needle = $_this->strtoupper($needle);
		$needle = $_this->utf8($needle);
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
			return $_this->ascii($parts);
		} elseif ($found && !empty($haystack)) {
			return $_this->ascii($haystack);
		}
		return false;
	}
/**
 * Finds position of last occurrence of a string within another, case insensitive
 *
 * @param string $haystack The string from which to get the position of the last occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param integer $offset The position in $haystack to start searching.
 * @return integer|boolean The numeric position of the last occurrence of $needle in the $haystack string, or false if $needle is not found.
 * @access public
 * @static
 */
	function strripos($haystack, $needle, $offset = 0) {
		$_this =& Multibyte::getInstance();

		if (!PHP5 || $_this->__checkMultibyte($haystack)) {
			$found = false;
			$haystack = $_this->strtoupper($haystack);
			$haystack = $_this->utf8($haystack);
			$haystackCount = count($haystack);
			$matches = array_count_values($haystack);
			$needle = $_this->strtoupper($needle);
			$needle = $_this->utf8($needle);
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
			$return = ife($found, $position, false);
			return $return;
		}
		return strripos($haystack, $needle, $offset);
	}

/**
 * Find position of last occurrence of a string in a string.
 *
 * @param string $haystack The string being checked, for the last occurrence of $needle.
 * @param string $needle The string to find in $haystack.
 * @param integer $offset May be specified to begin searching an arbitrary number of characters into the string.
 *                        Negative values will stop searching at an arbitrary point prior to the end of the string.
 * @return integer|boolean The numeric position of the last occurrence of $needle in the $haystack string. If $needle is not found, it returns false.
 * @access public
 * @static
 */
	function strrpos($haystack, $needle, $offset = 0) {
		$_this =& Multibyte::getInstance();

		if (!PHP5 || $_this->__checkMultibyte($haystack)) {
			$found = false;
			$haystack = $_this->utf8($haystack);
			$haystackCount = count($haystack);
			$matches = array_count_values($haystack);
			$needle = $_this->utf8($needle);
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
			$return = ife($found, $position, false);
			return $return;
		}
		return strrpos($haystack, $needle, $offset);
	}
/**
 * Finds first occurrence of a string within another
 *
 * @param string $haystack The string from which to get the first occurrence of $needle.
 * @param string $needle The string to find in $haystack
 * @param boolean $part Determines which portion of $haystack this function returns.
 *                      If set to true, it returns all of $haystack from the beginning to the first occurrence of $needle.
 *                      If set to false, it returns all of $haystack from the first occurrence of $needle to the end, Default value is FALSE.
 * @return string|boolean The portion of $haystack, or true if $needle is not found.
 * @access public
 * @static
 */
	function strstr($haystack, $needle, $part = false) {
		$_this =& Multibyte::getInstance();
		$php = (phpversion() < 5.3);

		if (($php && $part) || $_this->__checkMultibyte($haystack)) {
			$check = $_this->utf8($haystack);
			$found = false;
			$haystack = $_this->utf8($haystack);
			$haystackCount = count($haystack);
			$needle = $_this->utf8($needle);
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
				return $_this->ascii($parts);
			} elseif ($found && !empty($haystack)) {
				return $_this->ascii($haystack);
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
		$_this->utf8($string);

		$length = count($_this->__utf8Map);
		$lowerCase = array();
		$matched = false;

		for ($i = 0 ; $i < $length; $i++) {
			$char = $_this->__utf8Map[$i];

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
		return $_this->ascii($lowerCase);
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
		$_this->utf8($string);

		$length = count($_this->__utf8Map);
		$matched = false;
		$replaced = array();
		$upperCase = array();

		for ($i = 0 ; $i < $length; $i++) {
			$char = $_this->__utf8Map[$i];

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

							for ($ii = 0; $ii < count($keys[$key]['lower']); $ii++) {
								$nextChar = $_this->__utf8Map[$i + $ii];

								if (isset($nextChar) && ($nextChar == $keys[$key]['lower'][$j + $ii])) {
									$replace++;
								}
							}
							if ($replace == count($keys[$key]['lower'])) {
								$upperCase[] = $keys[$key]['upper'];
								$replaced = array_merge($replaced, array_values($keys[$key]['lower']));
								$matched = true;
								break 1;
							}
						} elseif ($length > 1 && $keyCount > 1) {
							$j = 0;
							for ($ii = 1; $ii < $keyCount; $ii++) {
								$nextChar = $_this->__utf8Map[$i + $ii - 1];

								if (in_array($nextChar, $keys[$ii]['lower'])) {

									for ($jj = 0; $jj < count($keys[$ii]['lower']); $jj++) {
										$nextChar = $_this->__utf8Map[$i + $jj];

										if (isset($nextChar) && ($nextChar == $keys[$ii]['lower'][$j + $jj])) {
											$replace++;
										}
									}
									if ($replace == count($keys[$ii]['lower'])) {
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
		return $_this->ascii($upperCase);
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
		$_this =& Multibyte::getInstance();

		$count = 0;
		$haystack = $_this->utf8($haystack);
		$haystackCount = count($haystack);
		$matches = array_count_values($haystack);
		$needle = $_this->utf8($needle);
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
		$_this =& Multibyte::getInstance();

		$string = $_this->utf8($string);
		$stringCount = count($string);

		for ($i = 1; $i <= $start; $i++) {
			unset($string[$i - 1]);
		}

		if ($length === null || count($string) < $length) {
			return $_this->ascii($string);
		}
		$string = array_values($string);

		for ($i = 0; $i < $length; $i++) {
			$value[] = $string[$i];
		}
		return $_this->ascii($value);
	}
/**
 * Return the Code points range for Unicode characters
 *
 * @param interger $decimal
 * @return string
 * @access private
 */
	function __codepoint ($decimal) {
		$_this =& Multibyte::getInstance();

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
		$_this->__codeRange[$decimal] = $return;
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
	function __find($char, $type = 'lower'){
		$_this =& Multibyte::getInstance();
		$value = false;
		$found = array();
		if(!isset($_this->__codeRange[$char])) {
			$range = $_this->__codepoint($char);
			if ($range === false) {
				return null;
			}
			Configure::load('unicode' . DS . 'casefolding' . DS . $range);
			$_this->__caseFold[$range] = Configure::read($range);
			Configure::delete($range);
		}

		if (!$_this->__codeRange[$char]) {
			return null;
		}
		$_this->__table = $_this->__codeRange[$char];
		$count = count($_this->__caseFold[$_this->__table]);

		for($i = 0; $i < $count; $i++) {
			if ($type === 'lower' && $_this->__caseFold[$_this->__table][$i][$type][0] === $char) {
				$found[] = $_this->__caseFold[$_this->__table][$i];
			} elseif ($type === 'upper' && $_this->__caseFold[$_this->__table][$i][$type] === $char) {
				$found[] = $_this->__caseFold[$_this->__table][$i];
			}
		}
		return $found;
  }
/**
 * resets the utf8 map array
 *
 * @access private
 */
	function __reset() {
		$_this =& Multibyte::getInstance();
		$_this->__utf8Map = array();
	}
/**
 * Check the $string for multibyte characters
 *
 * @access private
 */
	function __checkMultibyte($string) {
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