<?php
/* SVN FILE: $Id$ */
/**
 * String handling methods.
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *			1785 E. Sahara Avenue, Suite 490-204
 *			Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 1.2.0.5551
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * String handling methods.
 *
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class String extends Object {
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
 * Gets a reference to the String object instance
 *
 * @return object String instance
 * @access public
 * @static
 */
	function &getInstance() {
		static $instance = array();

		if (!isset($instance[0]) || !$instance[0]) {
			$instance[0] =& new String();
		}
		return $instance[0];
	}
/**
 * Generate a random UUID
 *
 * @see http://www.ietf.org/rfc/rfc4122.txt
 * @return RFC 4122 UUID
 * @static
 */
	function uuid() {
		$node = env('SERVER_ADDR');

		if(empty($node)) {
			$host = env('HOSTNAME');

			if (empty($host)) {
				$host = env('HOST');
			}

			if (empty($host)) {
				$node = ip2long('127.0.0.1');
			} else {
				$ip = gethostbyname($host);
				if ($ip === $host) {
					$node = crc32($host);
				} else {
					$node = ip2long($ip);
				}
			}
		} else {
			$node = ip2long($node);
		}

		if (function_exists('zend_thread_id')) {
			$pid = zend_thread_id();
		} else {
			$pid = getmypid();
		}

		list($timeMid, $timeLow) = explode(' ', microtime());
		$uuid = sprintf("%08x-%04x-%04x-%02x%02x-%04x%08x", (int)$timeLow, (int)substr($timeMid, 2) & 0xffff,
					mt_rand(0, 0xfff) | 0x4000, mt_rand(0, 0x3f) | 0x80, mt_rand(0, 0xff), $pid, $node);
		return $uuid;
	}
/**
 * Converts a multi-byte character string
 * to the decimal value of the character
 *
 * @param multi-byte string $string
 * @return array
 * @access public
 * @static
 */
	function utf8($string) {
		$_this =& String::getInstance();
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
 * Converts the decimal value of a multi-byte character string
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
 * Find position of first occurrence of a string
 *
 * Returns the numeric position of the first occurrence of needle in the haystack  string.
 *
 * @param multi-byte string $haystack
 * @param multi-byte string $needle
 * @param integer $offset
 * @return int|boolean
 * @access public
 * @static
 */
	function strpos($haystack, $needle, $offset = 0) {
		$_this =& String::getInstance();

		$haystack = $_this->utf8($haystack);
		$needle = $_this->utf8($needle);
		$position = $offset;
		$found = false;
		$haystackCount = count($haystack);
		$needleCount = count($needle);

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
		$return = ife($found, $position, false);
		return $return;
	}
/**
 * Find position of first occurrence of a case-insensitive string
 *
 * Returns the numeric position of the first occurrence of needle in the haystack
 *
 * @param multi-byte string $haystack
 * @param multi-byte string $needle
 * @param integer $offset
 * @return int|boolean
 * @access public
 * @static
 */
	function stripos($haystack, $needle, $offset = 0) {
		$_this =& String::getInstance();

		$haystack = $_this->strtoupper($haystack);
		$needle = $_this->strtoupper($needle);
		return $_this->strpos($haystack, $needle, $offset);
	}
/**
 * Make a multi-byte string uppercase
 *
 * Returns string with all alphabetic characters converted to uppercase.
 *
 * @param multi-byte string $string
 * @return string
 * @access public
 * @static
 */
	function strtoupper($string) {
		$_this =& String::getInstance();
		$_this->utf8($string);
		$length = count($_this->__utf8Map);
		$upperCase = array();
		$replaced = array();
		$matched = false;

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

							for ($ii = 0 ; $ii < count($keys[$key]['lower']) ; $ii++) {
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

									for ($jj = 0 ; $jj < count($keys[$ii]['lower']) ; $jj++) {
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
		if (empty($upperCase)) {
			return $string;
		}
		return $_this->ascii($upperCase);
	}
/**
 * Return the Code points range for Unicode characters
 *
 * @param interger $decimal
 * @return string
 * @access private
 */
	function __codepoint ($decimal) {
		$_this =& String::getInstance();

		if ($decimal < 128) {
				$return = '0000_007f'; // Basic Latin
		} elseif ($decimal < 256)  {
				$return = '0080_00ff'; // Latin-1 Supplement
		} elseif ($decimal < 384) {
				$return = '0100_017f'; // Latin Extended-A
		} elseif ($decimal < 592) {
				$return = '0180_024F'; // Latin Extended-B
		} elseif ($decimal < 688) {
				$return = '0250_02af'; // IPA Extensions
		} elseif ($decimal < 768) {
				$return = '02b0_02ff'; // Spacing Modifier Letters
		} elseif ($decimal < 880) {
				$return = '0300_036f'; // Combining Diacritical Marks
		} elseif ($decimal < 1024) {
				$return = '0370_03ff'; // Greek and Coptic
		} elseif ($decimal < 1280) {
				$return = '0400_04ff'; // Cyrillic
		} elseif ($decimal < 1328) {
				$return = '0500_052f'; // Cyrillic Supplement
		} elseif ($decimal < 1424) {
				$return = '0530_058f'; // Armenian
		} elseif ($decimal < 1536) {
				$return = '0590_05ff'; // Hebrew
		} elseif ($decimal < 1792) {
				$return = '0600_06ff'; // Arabic
		} elseif ($decimal < 1872) {
				$return = '0700_074f'; // Syriac
		} elseif ($decimal < 1920) {
				$return = '0750_077f'; // Arabic Supplement
		} elseif ($decimal < 1984) {
				$return = '0780_07bf'; // Thaana
		} elseif ($decimal < 2304) {
				$return = '07c0_07ff'; // NKo
		} elseif ($decimal < 2432) {
				$return = '0900_097f'; // Devanagari
		} elseif ($decimal < 2560) {
				$return = '0980_09ff'; // Bengali
		} elseif ($decimal < 2688) {
				$return = '0a00_0a7f'; // Gurmukhi
		} elseif ($decimal < 2816) {
				$return = '0a80_0aff'; // Gujarati
		} elseif ($decimal < 2944) {
				$return = '0b00_0b7f'; // Oriya
		} elseif ($decimal < 3072) {
				$return = '0b80_0bff'; // Tamil
		} elseif ($decimal < 3200) {
				$return = '0c00_0c7f'; // Telugu
		} elseif ($decimal < 3328) {
				$return = '0c80_0cff'; // Kannada
		} elseif ($decimal < 3456) {
				$return = '0d00_0d7f'; // Malayalam
		} elseif ($decimal < 3584) {
				$return = '0d80_0dff'; // Sinhala
		} elseif ($decimal < 3712) {
				$return = '0e00_0e7f'; // Thai
		} elseif ($decimal < 3840) {
				$return = '0e80_0eff'; // Lao
		} elseif ($decimal < 4096) {
				$return = '0f00_0fff'; // Tibetan
		} elseif ($decimal < 4256) {
				$return = '1000_109f'; // Myanmar
		} elseif ($decimal < 4352) {
				$return = '10a0_10ff'; // Georgian
		} elseif ($decimal < 4608) {
				$return = '1100_11ff'; // Hangul Jamo
		} elseif ($decimal < 4992) {
				$return = '1200_137f'; // Ethiopic
		} elseif ($decimal < 5024) {
				$return = '1380_139f'; // Ethiopic Supplement
		} elseif ($decimal < 5120) {
				$return = '13a0_13ff'; // Cherokee
		} elseif ($decimal < 5760) {
				$return = '1400_167f'; // Unified Canadian Aboriginal Syllabics
		} elseif ($decimal < 5792) {
				$return = '1680_169f'; // Ogham
		} elseif ($decimal < 5888) {
				$return = '16a0_16ff'; // Runic
		} elseif ($decimal < 5920) {
				$return = '1700_171f'; // Tagalog
		} elseif ($decimal < 5952) {
				$return = '1720_173f'; // Hanunoo
		} elseif ($decimal < 5984) {
				$return = '1740_175f'; // Buhid
		} elseif ($decimal < 6016) {
				$return = '1760_177f'; // Tagbanwa
		} elseif ($decimal < 6144) {
				$return = '1780_17ff'; // Khmer
		} elseif ($decimal < 6400) {
				$return = '1800_18af'; // Mongolian
		} elseif ($decimal < 6480) {
				$return = '1900_194f'; // Limbu
		} elseif ($decimal < 6528) {
				$return = '1950_197f'; // Tai Le
		} elseif ($decimal < 6624) {
				$return = '1980_19df'; // New Tai Lue
		} elseif ($decimal < 6656) {
				$return = '19e0_19ff'; // Khmer Symbols
		} elseif ($decimal < 6912) {
				$return = '1a00_1a1f'; // Buginese
		} elseif ($decimal < 7424) {
				$return = '1b00_1b7f'; // Balinese
		} elseif ($decimal < 7552) {
				$return = '1d00_1d7f'; // Phonetic Extensions
		} elseif ($decimal < 7616) {
				$return = '1d80_1dbf'; // Phonetic Extensions Supplement
		} elseif ($decimal < 7680) {
				$return = '1dc0_1dff'; // Combining Diacritical Marks Supplement
		} elseif ($decimal < 7936) {
				$return = '1e00_1eff'; // Latin Extended Additional
		} elseif ($decimal < 8192) {
				$return = '1f00_1fff'; // Greek Extended
		} elseif ($decimal < 8304) {
				$return = '2000_206f'; // General Punctuation
		} elseif ($decimal < 8352) {
				$return = '2070_209f'; // Superscripts and Subscripts
		} elseif ($decimal < 8400) {
				$return = '20a0_20cf'; // Currency Symbols
		} elseif ($decimal < 8448) {
				$return = '20d0_20ff'; // Combining Diacritical Marks for Symbols
		} elseif ($decimal < 8528) {
				$return = '2100_214f'; // Letterlike Symbols
		} elseif ($decimal < 8592) {
				$return = '2150_218f'; // Number Forms
		} elseif ($decimal < 8704) {
				$return = '2190_21ff'; // Arrows
		} elseif ($decimal < 8960) {
				$return = '2200_22ff'; // Mathematical Operators
		} elseif ($decimal < 9216) {
				$return = '2300_23ff'; // Miscellaneous Technical
		} elseif ($decimal < 9280) {
				$return = '2400_243f'; // Control Pictures
		} elseif ($decimal < 9312) {
				$return = '2440_245f'; // Optical Character Recognition
		} elseif ($decimal < 9472) {
				$return = '2460_24ff'; // Enclosed Alphanumerics
		} elseif ($decimal < 9600) {
				$return = '2500_257f'; // Box Drawing
		} elseif ($decimal < 9632) {
				$return = '2580_259f'; // Block Elements
		} elseif ($decimal < 9728) {
				$return = '25a0_25ff'; // Geometric Shapes
		} elseif ($decimal < 9984) {
				$return = '2600_26ff'; // Miscellaneous Symbols
		} elseif ($decimal < 10176) {
				$return = '2700_27bf'; // Dingbats
		} elseif ($decimal < 10224) {
				$return = '27c0_27ef'; // Miscellaneous Mathematical Symbols-A
		} elseif ($decimal < 10240) {
				$return = '27f0_27ff'; // Supplemental Arrows-A
		} elseif ($decimal < 10496) {
				$return = '2800_28ff'; // Braille Patterns
		} elseif ($decimal < 10624) {
				$return = '2900_297f'; // Supplemental Arrows-B
		} elseif ($decimal < 10752) {
				$return = '2980_29ff'; // Miscellaneous Mathematical Symbols-B
		} elseif ($decimal < 11008) {
				$return = '2a00_2aff'; // Supplemental Mathematical Operators
		} elseif ($decimal < 11264) {
				$return = '2b00_2bff'; // Miscellaneous Symbols and Arrows
		} elseif ($decimal < 11360) {
				$return = '2c00_2c5f'; // Glagolitic
		} elseif ($decimal < 11392) {
				$return = '2c60_2c7f'; // Latin Extended-C
		} elseif ($decimal < 11520) {
				$return = '2c80_2cff'; // Coptic
		} elseif ($decimal < 11568) {
				$return = '2d00_2d2f'; // Georgian Supplement
		} elseif ($decimal < 11648) {
				$return = '2d30_2d7f'; // Tifinagh
		} elseif ($decimal < 11776) {
				$return = '2d80_2ddf'; // Ethiopic Extended
		} elseif ($decimal < 11904) {
				$return = '2e00_2e7f'; // Supplemental Punctuation
		} elseif ($decimal < 12032) {
				$return = '2e80_2eff'; // CJK Radicals Supplement
		} elseif ($decimal < 12272) {
				$return = '2f00_2fdf'; // Kangxi Radicals
		} elseif ($decimal < 12288) {
				$return = '2ff0_2fff'; // Ideographic Description Characters
		} elseif ($decimal < 12352) {
				$return = '3000_303f'; // CJK Symbols and Punctuation
		} elseif ($decimal < 12448) {
				$return = '3040_309f'; // Hiragana
		} elseif ($decimal < 12544) {
				$return = '30a0_30ff'; // Katakana
		} elseif ($decimal < 12592) {
				$return = '3100_312f'; // Bopomofo
		} elseif ($decimal < 12688) {
				$return = '3130_318f'; // Hangul Compatibility Jamo
		} elseif ($decimal < 12704) {
				$return = '3190_319f'; // Kanbun
		} elseif ($decimal < 12736) {
				$return = '31a0_31bf'; // Bopomofo Extended
		} elseif ($decimal < 12784) {
				$return = '31c0_31ef'; // CJK Strokes
		} elseif ($decimal < 12800) {
				$return = '31f0_31ff'; // Katakana Phonetic Extensions
		} elseif ($decimal < 13056) {
				$return = '3200_32ff'; // Enclosed CJK Letters and Months
		} elseif ($decimal < 13312) {
				$return = '3300_33ff'; // CJK Compatibility
		} elseif ($decimal < 19904) {
				$return = '3400_4dbf'; // CJK Unified Ideographs Extension A
		} elseif ($decimal < 19968) {
				$return = '4dc0_4dff'; // Yijing Hexagram Symbols
		} elseif ($decimal < 40960) {
				$return = '4e00_9fff'; // CJK Unified Ideographs
		} elseif ($decimal < 42128) {
				$return = 'a000_a48f'; // Yi Syllables
		} elseif ($decimal < 42752) {
				$return = 'a490_a4cf'; // Yi Radicals
		} elseif ($decimal < 42784) {
				$return = 'a700_a71f'; // Modifier Tone Letters
		} elseif ($decimal < 43008) {
				$return = 'a720_a7ff'; // Latin Extended-D
		} elseif ($decimal < 43072) {
				$return = 'a800_a82f'; // Syloti Nagri
		} elseif ($decimal < 44032) {
				$return = 'a840_a87f'; // Phags-pa
		} elseif ($decimal < 55296) {
				$return = 'ac00_d7af'; // Hangul Syllables
		} elseif ($decimal < 56192) {
				$return = 'd800_db7f'; // High Surrogates
		} elseif ($decimal < 56320) {
				$return = 'db80_dbff'; // High Private Use Surrogates
		} elseif ($decimal < 57344) {
				$return = 'dc00_dfff'; // Low Surrogates
		} elseif ($decimal < 63744) {
				$return = 'e000_f8ff'; // Private Use Area
		} elseif ($decimal < 64256) {
				$return = 'f900_faff'; // CJK Compatibility Ideographs
		} elseif ($decimal < 64336) {
				$return = 'fb00_fb4f'; // Alphabetic Presentation Forms
		} elseif ($decimal < 65024) {
				$return = 'fb50_fdff'; // Arabic Presentation Forms-A
		} elseif ($decimal < 65040) {
				$return = 'fe00_fe0f'; // Variation Selectors
		} elseif ($decimal < 65056) {
				$return = 'fe10_fe1f'; // Vertical Forms
		} elseif ($decimal < 65072) {
				$return = 'fe20_fe2f'; // Combining Half Marks
		} elseif ($decimal < 65104) {
				$return = 'fe30_fe4f'; // CJK Compatibility Forms
		} elseif ($decimal < 65136) {
				$return = 'fe50_fe6f'; // Small Form Variants
		} elseif ($decimal < 65280) {
				$return = 'fe70_feff'; // Arabic Presentation Forms-B
		} elseif ($decimal < 65520) {
				$return = 'ff00_ffef'; // Halfwidth and Fullwidth Forms
		} elseif ($decimal < 65536) {
				$return = 'fff0_ffff'; // Specials
		} elseif ($decimal < 65664) {
				$return = '10000_1007f'; // Linear B Syllabary
		} elseif ($decimal < 65792) {
				$return = '10080_100ff'; // Linear B Ideograms
		} elseif ($decimal < 65856) {
				$return = '10100_1013f'; // Aegean Numbers
		} elseif ($decimal < 66304) {
				$return = '10140_1018f'; // Ancient Greek Numbers
		} elseif ($decimal < 66352) {
				$return = '10300_1032f'; // Old Italic
		} elseif ($decimal < 66432) {
				$return = '10330_1034f'; // Gothic
		} elseif ($decimal < 66464) {
				$return = '10380_1039f'; // Ugaritic
		} elseif ($decimal < 66560) {
				$return = '103a0_103df'; // Old Persian
		} elseif ($decimal < 66640) {
				$return = '10400_1044f'; // Deseret
		} elseif ($decimal < 66688) {
				$return = '10450_1047f'; // Shavian
		} elseif ($decimal < 67584) {
				$return = '10480_104af'; // Osmanya
		} elseif ($decimal < 67840) {
				$return = '10800_1083f'; // Cypriot Syllabary
		} elseif ($decimal < 68096) {
				$return = '10900_1091f'; // Phoenician
		} elseif ($decimal < 73728) {
				$return = '10a00_10a5f'; // Kharoshthi
		} elseif ($decimal < 74752) {
				$return = '12000_123ff'; // Cuneiform
		} elseif ($decimal < 118784) {
				$return = '12400_1247f'; // Cuneiform Numbers and Punctuation
		} elseif ($decimal < 119040) {
				$return = '1d000_1d0ff'; // Byzantine Musical Symbols
		} elseif ($decimal < 119296) {
				$return = '1d100_1d1ff'; // Musical Symbols
		} elseif ($decimal < 119552) {
				$return = '1d200_1d24f'; // Ancient Greek Musical Notation
		} elseif ($decimal < 119648) {
				$return = '1d300_1d35f'; // Tai Xuan Jing Symbols
		} elseif ($decimal < 119808) {
				$return = '1d360_1d37f'; // Counting Rod Numerals
		} elseif ($decimal < 131072) {
				$return = '1d400_1d7ff'; // Mathematical Alphanumeric Symbols
		} elseif ($decimal < 194560) {
				$return = '20000_2a6df'; // CJK Unified Ideographs Extension B
		} elseif ($decimal < 917504) {
				$return = '2f800_2fa1f'; // CJK Compatibility Ideographs Supplement
		} elseif ($decimal < 917760) {
				$return = 'e0000_e007f'; // Tags
		} elseif ($decimal < 983040) {
				$return = 'e0100_e01ef'; // Variation Selectors Supplement
		} elseif ($decimal < 1048576) {
				$return = 'f0000_fffff'; // Supplementary Private Use Area-A
		} elseif ($decimal > 1048575) {
			$return = '100000_10ffff'; // Supplementary Private Use Area-B
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
		$_this =& String::getInstance();
		$value = false;
		$found = array();
		if(!isset($_this->__codeRange[$char])) {
			$range = $_this->__codepoint($char);
			if ($range === false) {

			}
			Configure::load('unicode' . DS . 'casefolding' . DS . $range);
			$_this->__caseFold[$range] = Configure::read($range);
			Configure::delete($range);
		}
		$_this->__table = $_this->__codeRange[$char];
		$count = count($_this->__caseFold[$_this->__table]);
		for($i = 0; $i < $count; $i++) {
			if ($_this->__caseFold[$_this->__table][$i][$type][0] === $char) {
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
		$_this =& String::getInstance();
		$_this->__utf8Map = array();
	}
}
?>