<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.2.0.3830
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Multibyte', 'I18n');
App::uses('File', 'Utility');
App::uses('CakeNumber', 'Utility');

// Load multibyte if the extension is missing.
if (!function_exists('mb_strlen')) {
	class_exists('Multibyte');
}

/**
 * Validation Class. Used for validation of model data
 *
 * Offers different validation methods.
 *
 * @package       Cake.Utility
 */
class Validation {

/**
 * Some complex patterns needed in multiple places
 *
 * @var array
 */
	protected static $_pattern = array(
		'hostname' => '(?:[_\p{L}0-9][-_\p{L}0-9]*\.)*(?:[\p{L}0-9][-\p{L}0-9]{0,62})\.(?:(?:[a-z]{2}\.)?[a-z]{2,})'
	);

/**
 * Holds an array of errors messages set in this class.
 * These are used for debugging purposes
 *
 * @var array
 */
	public static $errors = array();

/**
 * Backwards compatibility wrapper for Validation::notBlank().
 *
 * @param string|array $check Value to check.
 * @return bool Success.
 * @deprecated 2.7.0 Use Validation::notBlank() instead.
 * @see Validation::notBlank()
 */
	public static function notEmpty($check) {
		trigger_error('Validation::notEmpty() is deprecated. Use Validation::notBlank() instead.', E_USER_DEPRECATED);
		return static::notBlank($check);
	}

/**
 * Checks that a string contains something other than whitespace
 *
 * Returns true if string contains something other than whitespace
 *
 * $check can be passed as an array:
 * array('check' => 'valueToCheck');
 *
 * @param string|array $check Value to check
 * @return bool Success
 */
	public static function notBlank($check) {
		if (!is_scalar($check)) {
			return false;
		}
		if (empty($check) && (string)$check !== '0') {
			return false;
		}
		return static::_check($check, '/[^\s]+/m');
	}

/**
 * Checks that a string contains only integer or letters
 *
 * Returns true if string contains only integer or letters
 *
 * $check can be passed as an array:
 * array('check' => 'valueToCheck');
 *
 * @param string|array $check Value to check
 * @return bool Success
 */
	public static function alphaNumeric($check) {
		if (empty($check) && $check != '0') {
			return false;
		}
		return static::_check($check, '/^[\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]+$/Du');
	}

/**
 * Checks that a string length is within s specified range.
 * Spaces are included in the character count.
 * Returns true is string matches value min, max, or between min and max,
 *
 * @param string $check Value to check for length
 * @param int $min Minimum value in range (inclusive)
 * @param int $max Maximum value in range (inclusive)
 * @return bool Success
 */
	public static function lengthBetween($check, $min, $max) {
		$length = mb_strlen($check);
		return ($length >= $min && $length <= $max);
	}

/**
 * Alias of Validator::lengthBetween() for backwards compatibility.
 *
 * @param string $check Value to check for length
 * @param int $min Minimum value in range (inclusive)
 * @param int $max Maximum value in range (inclusive)
 * @return bool Success
 * @see Validator::lengthBetween()
 * @deprecated Deprecated 2.6. Use Validator::lengthBetween() instead.
 */
	public static function between($check, $min, $max) {
		return static::lengthBetween($check, $min, $max);
	}

/**
 * Returns true if field is left blank -OR- only whitespace characters are present in its value
 * Whitespace characters include Space, Tab, Carriage Return, Newline
 *
 * $check can be passed as an array:
 * array('check' => 'valueToCheck');
 *
 * @param string|array $check Value to check
 * @return bool Success
 */
	public static function blank($check) {
		return !static::_check($check, '/[^\\s]/');
	}

/**
 * Validation of credit card numbers.
 * Returns true if $check is in the proper credit card format.
 *
 * @param string|array $check credit card number to validate
 * @param string|array $type 'all' may be passed as a sting, defaults to fast which checks format of most major credit
 * cards
 *    if an array is used only the values of the array are checked.
 *    Example: array('amex', 'bankcard', 'maestro')
 * @param bool $deep set to true this will check the Luhn algorithm of the credit card.
 * @param string $regex A custom regex can also be passed, this will be used instead of the defined regex values
 * @return bool Success
 * @see Validation::luhn()
 */
	public static function cc($check, $type = 'fast', $deep = false, $regex = null) {
		if (!is_scalar($check)) {
			return false;
		}

		$check = str_replace(array('-', ' '), '', $check);
		if (mb_strlen($check) < 13) {
			return false;
		}

		if ($regex !== null) {
			if (static::_check($check, $regex)) {
				return static::luhn($check, $deep);
			}
		}
		$cards = array(
			'all' => array(
				'amex'		=> '/^3[4|7]\\d{13}$/',
				'bankcard'	=> '/^56(10\\d\\d|022[1-5])\\d{10}$/',
				'diners'	=> '/^(?:3(0[0-5]|[68]\\d)\\d{11})|(?:5[1-5]\\d{14})$/',
				'disc'		=> '/^(?:6011|650\\d)\\d{12}$/',
				'electron'	=> '/^(?:417500|4917\\d{2}|4913\\d{2})\\d{10}$/',
				'enroute'	=> '/^2(?:014|149)\\d{11}$/',
				'jcb'		=> '/^(3\\d{4}|2100|1800)\\d{11}$/',
				'maestro'	=> '/^(?:5020|6\\d{3})\\d{12}$/',
				'mc'		=> '/^5[1-5]\\d{14}$/',
				'solo'		=> '/^(6334[5-9][0-9]|6767[0-9]{2})\\d{10}(\\d{2,3})?$/',
				'switch'	=>
				'/^(?:49(03(0[2-9]|3[5-9])|11(0[1-2]|7[4-9]|8[1-2])|36[0-9]{2})\\d{10}(\\d{2,3})?)|(?:564182\\d{10}(\\d{2,3})?)|(6(3(33[0-4][0-9])|759[0-9]{2})\\d{10}(\\d{2,3})?)$/',
				'visa'		=> '/^4\\d{12}(\\d{3})?$/',
				'voyager'	=> '/^8699[0-9]{11}$/'
			),
			'fast' =>
			'/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6011[0-9]{12}|3(?:0[0-5]|[68][0-9])[0-9]{11}|3[47][0-9]{13})$/'
		);

		if (is_array($type)) {
			foreach ($type as $value) {
				$regex = $cards['all'][strtolower($value)];

				if (static::_check($check, $regex)) {
					return static::luhn($check, $deep);
				}
			}
		} elseif ($type === 'all') {
			foreach ($cards['all'] as $value) {
				$regex = $value;

				if (static::_check($check, $regex)) {
					return static::luhn($check, $deep);
				}
			}
		} else {
			$regex = $cards['fast'];

			if (static::_check($check, $regex)) {
				return static::luhn($check, $deep);
			}
		}
		return false;
	}

/**
 * Used to compare 2 numeric values.
 *
 * @param string|array $check1 if string is passed for a string must also be passed for $check2
 *    used as an array it must be passed as array('check1' => value, 'operator' => 'value', 'check2' -> value)
 * @param string $operator Can be either a word or operand
 *    is greater >, is less <, greater or equal >=
 *    less or equal <=, is less <, equal to ==, not equal !=
 * @param int $check2 only needed if $check1 is a string
 * @return bool Success
 */
	public static function comparison($check1, $operator = null, $check2 = null) {
		if ((float)$check1 != $check1) {
			return false;
		}
		$operator = str_replace(array(' ', "\t", "\n", "\r", "\0", "\x0B"), '', strtolower($operator));

		switch ($operator) {
			case 'isgreater':
			case '>':
				if ($check1 > $check2) {
					return true;
				}
				break;
			case 'isless':
			case '<':
				if ($check1 < $check2) {
					return true;
				}
				break;
			case 'greaterorequal':
			case '>=':
				if ($check1 >= $check2) {
					return true;
				}
				break;
			case 'lessorequal':
			case '<=':
				if ($check1 <= $check2) {
					return true;
				}
				break;
			case 'equalto':
			case '==':
				if ($check1 == $check2) {
					return true;
				}
				break;
			case 'notequal':
			case '!=':
				if ($check1 != $check2) {
					return true;
				}
				break;
			default:
				static::$errors[] = __d('cake_dev', 'You must define the $operator parameter for %s', 'Validation::comparison()');
		}
		return false;
	}

/**
 * Used when a custom regular expression is needed.
 *
 * @param string|array $check When used as a string, $regex must also be a valid regular expression.
 *    As and array: array('check' => value, 'regex' => 'valid regular expression')
 * @param string $regex If $check is passed as a string, $regex must also be set to valid regular expression
 * @return bool Success
 */
	public static function custom($check, $regex = null) {
		if (!is_scalar($check)) {
			return false;
		}
		if ($regex === null) {
			static::$errors[] = __d('cake_dev', 'You must define a regular expression for %s', 'Validation::custom()');
			return false;
		}
		return static::_check($check, $regex);
	}

/**
 * Date validation, determines if the string passed is a valid date.
 * keys that expect full month, day and year will validate leap years.
 *
 * Years are valid from 1800 to 2999.
 *
 * ### Formats:
 *
 * - `dmy` 27-12-2006 or 27-12-06 separators can be a space, period, dash, forward slash
 * - `mdy` 12-27-2006 or 12-27-06 separators can be a space, period, dash, forward slash
 * - `ymd` 2006-12-27 or 06-12-27 separators can be a space, period, dash, forward slash
 * - `dMy` 27 December 2006 or 27 Dec 2006
 * - `Mdy` December 27, 2006 or Dec 27, 2006 comma is optional
 * - `My` December 2006 or Dec 2006
 * - `my` 12/2006 or 12/06 separators can be a space, period, dash, forward slash
 * - `ym` 2006/12 or 06/12 separators can be a space, period, dash, forward slash
 * - `y` 2006 just the year without any separators
 *
 * @param string $check a valid date string
 * @param string|array $format Use a string or an array of the keys above.
 *    Arrays should be passed as array('dmy', 'mdy', etc)
 * @param string $regex If a custom regular expression is used this is the only validation that will occur.
 * @return bool Success
 */
	public static function date($check, $format = 'ymd', $regex = null) {
		if ($regex !== null) {
			return static::_check($check, $regex);
		}
		$month = '(0[123456789]|10|11|12)';
		$separator = '([- /.])';
		$fourDigitYear = '(([1][8-9][0-9][0-9])|([2][0-9][0-9][0-9]))';
		$twoDigitYear = '([0-9]{2})';
		$year = '(?:' . $fourDigitYear . '|' . $twoDigitYear . ')';

		$regex['dmy'] = '%^(?:(?:31(\\/|-|\\.|\\x20)(?:0?[13578]|1[02]))\\1|(?:(?:29|30)' .
			$separator . '(?:0?[1,3-9]|1[0-2])\\2))(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$|^(?:29' .
			$separator . '0?2\\3(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\\d|2[0-8])' .
			$separator . '(?:(?:0?[1-9])|(?:1[0-2]))\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%';

		$regex['mdy'] = '%^(?:(?:(?:0?[13578]|1[02])(\\/|-|\\.|\\x20)31)\\1|(?:(?:0?[13-9]|1[0-2])' .
			$separator . '(?:29|30)\\2))(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$|^(?:0?2' . $separator . '29\\3(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:(?:0?[1-9])|(?:1[0-2]))' .
			$separator . '(?:0?[1-9]|1\\d|2[0-8])\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%';

		$regex['ymd'] = '%^(?:(?:(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00)))' .
			$separator . '(?:0?2\\1(?:29)))|(?:(?:(?:1[6-9]|[2-9]\\d)?\\d{2})' .
			$separator . '(?:(?:(?:0?[13578]|1[02])\\2(?:31))|(?:(?:0?[1,3-9]|1[0-2])\\2(29|30))|(?:(?:0?[1-9])|(?:1[0-2]))\\2(?:0?[1-9]|1\\d|2[0-8]))))$%';

		$regex['dMy'] = '/^((31(?!\\ (Feb(ruary)?|Apr(il)?|June?|(Sep(?=\\b|t)t?|Nov)(ember)?)))|((30|29)(?!\\ Feb(ruary)?))|(29(?=\\ Feb(ruary)?\\ (((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)))))|(0?[1-9])|1\\d|2[0-8])\\ (Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)\\ ((1[6-9]|[2-9]\\d)\\d{2})$/';

		$regex['Mdy'] = '/^(?:(((Jan(uary)?|Ma(r(ch)?|y)|Jul(y)?|Aug(ust)?|Oct(ober)?|Dec(ember)?)\\ 31)|((Jan(uary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep)(tember)?|(Nov|Dec)(ember)?)\\ (0?[1-9]|([12]\\d)|30))|(Feb(ruary)?\\ (0?[1-9]|1\\d|2[0-8]|(29(?=,?\\ ((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)))))))\\,?\\ ((1[6-9]|[2-9]\\d)\\d{2}))$/';

		$regex['My'] = '%^(Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)' .
			$separator . '((1[6-9]|[2-9]\\d)\\d{2})$%';

		$regex['my'] = '%^(' . $month . $separator . $year . ')$%';
		$regex['ym'] = '%^(' . $year . $separator . $month . ')$%';
		$regex['y'] = '%^(' . $fourDigitYear . ')$%';

		$format = (is_array($format)) ? array_values($format) : array($format);
		foreach ($format as $key) {
			if (static::_check($check, $regex[$key]) === true) {
				return true;
			}
		}
		return false;
	}

/**
 * Validates a datetime value
 *
 * All values matching the "date" core validation rule, and the "time" one will be valid
 *
 * @param string $check Value to check
 * @param string|array $dateFormat Format of the date part. See Validation::date for more information.
 * @param string $regex Regex for the date part. If a custom regular expression is used this is the only validation that will occur.
 * @return bool True if the value is valid, false otherwise
 * @see Validation::date
 * @see Validation::time
 */
	public static function datetime($check, $dateFormat = 'ymd', $regex = null) {
		$valid = false;
		$parts = explode(' ', $check);
		if (!empty($parts) && count($parts) > 1) {
			$time = array_pop($parts);
			$date = implode(' ', $parts);
			$valid = static::date($date, $dateFormat, $regex) && static::time($time);
		}
		return $valid;
	}

/**
 * Time validation, determines if the string passed is a valid time.
 * Validates time as 24hr (HH:MM) or am/pm ([H]H:MM[a|p]m)
 * Does not allow/validate seconds.
 *
 * @param string $check a valid time string
 * @return bool Success
 */
	public static function time($check) {
		return static::_check($check, '%^((0?[1-9]|1[012])(:[0-5]\d){0,2} ?([AP]M|[ap]m))$|^([01]\d|2[0-3])(:[0-5]\d){0,2}$%');
	}

/**
 * Boolean validation, determines if value passed is a boolean integer or true/false.
 *
 * @param string $check a valid boolean
 * @return bool Success
 */
	public static function boolean($check) {
		$booleanList = array(0, 1, '0', '1', true, false);
		return in_array($check, $booleanList, true);
	}

/**
 * Checks that a value is a valid decimal. Both the sign and exponent are optional.
 *
 * Valid Places:
 *
 * - null => Any number of decimal places, including none. The '.' is not required.
 * - true => Any number of decimal places greater than 0, or a float|double. The '.' is required.
 * - 1..N => Exactly that many number of decimal places. The '.' is required.
 *
 * @param float $check The value the test for decimal.
 * @param int $places Decimal places.
 * @param string $regex If a custom regular expression is used, this is the only validation that will occur.
 * @return bool Success
 */
	public static function decimal($check, $places = null, $regex = null) {
		if ($regex === null) {
			$lnum = '[0-9]+';
			$dnum = "[0-9]*[\.]{$lnum}";
			$sign = '[+-]?';
			$exp = "(?:[eE]{$sign}{$lnum})?";

			if ($places === null) {
				$regex = "/^{$sign}(?:{$lnum}|{$dnum}){$exp}$/";

			} elseif ($places === true) {
				if (is_float($check) && floor($check) === $check) {
					$check = sprintf("%.1f", $check);
				}
				$regex = "/^{$sign}{$dnum}{$exp}$/";

			} elseif (is_numeric($places)) {
				$places = '[0-9]{' . $places . '}';
				$dnum = "(?:[0-9]*[\.]{$places}|{$lnum}[\.]{$places})";
				$regex = "/^{$sign}{$dnum}{$exp}$/";
			}
		}

		// account for localized floats.
		$data = localeconv();
		$check = str_replace($data['thousands_sep'], '', $check);
		$check = str_replace($data['decimal_point'], '.', $check);

		return static::_check($check, $regex);
	}

/**
 * Validates for an email address.
 *
 * Only uses getmxrr() checking for deep validation if PHP 5.3.0+ is used, or
 * any PHP version on a non-Windows distribution
 *
 * @param string $check Value to check
 * @param bool $deep Perform a deeper validation (if true), by also checking availability of host
 * @param string $regex Regex to use (if none it will use built in regex)
 * @return bool Success
 */
	public static function email($check, $deep = false, $regex = null) {
		if ($regex === null) {
			$regex = '/^[\p{L}0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[\p{L}0-9!#$%&\'*+\/=?^_`{|}~-]+)*@' . static::$_pattern['hostname'] . '$/ui';
		}
		$return = static::_check($check, $regex);
		if ($deep === false || $deep === null) {
			return $return;
		}

		if ($return === true && preg_match('/@(' . static::$_pattern['hostname'] . ')$/i', $check, $regs)) {
			if (function_exists('getmxrr') && getmxrr($regs[1], $mxhosts)) {
				return true;
			}
			if (function_exists('checkdnsrr') && checkdnsrr($regs[1], 'MX')) {
				return true;
			}
			return is_array(gethostbynamel($regs[1]));
		}
		return false;
	}

/**
 * Check that value is exactly $comparedTo.
 *
 * @param mixed $check Value to check
 * @param mixed $comparedTo Value to compare
 * @return bool Success
 */
	public static function equalTo($check, $comparedTo) {
		return ($check === $comparedTo);
	}

/**
 * Check that value has a valid file extension.
 *
 * @param string|array $check Value to check
 * @param array $extensions file extensions to allow. By default extensions are 'gif', 'jpeg', 'png', 'jpg'
 * @return bool Success
 */
	public static function extension($check, $extensions = array('gif', 'jpeg', 'png', 'jpg')) {
		if (is_array($check)) {
			return static::extension(array_shift($check), $extensions);
		}
		$extension = strtolower(pathinfo($check, PATHINFO_EXTENSION));
		foreach ($extensions as $value) {
			if ($extension === strtolower($value)) {
				return true;
			}
		}
		return false;
	}

/**
 * Validation of an IP address.
 *
 * @param string $check The string to test.
 * @param string $type The IP Protocol version to validate against
 * @return bool Success
 */
	public static function ip($check, $type = 'both') {
		$type = strtolower($type);
		$flags = 0;
		if ($type === 'ipv4') {
			$flags = FILTER_FLAG_IPV4;
		}
		if ($type === 'ipv6') {
			$flags = FILTER_FLAG_IPV6;
		}
		return (bool)filter_var($check, FILTER_VALIDATE_IP, array('flags' => $flags));
	}

/**
 * Checks whether the length of a string is greater or equal to a minimal length.
 *
 * @param string $check The string to test
 * @param int $min The minimal string length
 * @return bool Success
 */
	public static function minLength($check, $min) {
		return mb_strlen($check) >= $min;
	}

/**
 * Checks whether the length of a string is smaller or equal to a maximal length..
 *
 * @param string $check The string to test
 * @param int $max The maximal string length
 * @return bool Success
 */
	public static function maxLength($check, $max) {
		return mb_strlen($check) <= $max;
	}

/**
 * Checks that a value is a monetary amount.
 *
 * @param string $check Value to check
 * @param string $symbolPosition Where symbol is located (left/right)
 * @return bool Success
 */
	public static function money($check, $symbolPosition = 'left') {
		$money = '(?!0,?\d)(?:\d{1,3}(?:([, .])\d{3})?(?:\1\d{3})*|(?:\d+))((?!\1)[,.]\d{1,2})?';
		if ($symbolPosition === 'right') {
			$regex = '/^' . $money . '(?<!\x{00a2})\p{Sc}?$/u';
		} else {
			$regex = '/^(?!\x{00a2})\p{Sc}?' . $money . '$/u';
		}
		return static::_check($check, $regex);
	}

/**
 * Validate a multiple select. Comparison is case sensitive by default.
 *
 * Valid Options
 *
 * - in => provide a list of choices that selections must be made from
 * - max => maximum number of non-zero choices that can be made
 * - min => minimum number of non-zero choices that can be made
 *
 * @param array $check Value to check
 * @param array $options Options for the check.
 * @param bool $caseInsensitive Set to true for case insensitive comparison.
 * @return bool Success
 */
	public static function multiple($check, $options = array(), $caseInsensitive = false) {
		$defaults = array('in' => null, 'max' => null, 'min' => null);
		$options += $defaults;

		$check = array_filter((array)$check, 'strlen');
		if (empty($check)) {
			return false;
		}
		if ($options['max'] && count($check) > $options['max']) {
			return false;
		}
		if ($options['min'] && count($check) < $options['min']) {
			return false;
		}
		if ($options['in'] && is_array($options['in'])) {
			if ($caseInsensitive) {
				$options['in'] = array_map('mb_strtolower', $options['in']);
			}
			foreach ($check as $val) {
				$strict = !is_numeric($val);
				if ($caseInsensitive) {
					$val = mb_strtolower($val);
				}
				if (!in_array((string)$val, $options['in'], $strict)) {
					return false;
				}
			}
		}
		return true;
	}

/**
 * Checks if a value is numeric.
 *
 * @param string $check Value to check
 * @return bool Success
 */
	public static function numeric($check) {
		return is_numeric($check);
	}

/**
 * Checks if a value is a natural number.
 *
 * @param string $check Value to check
 * @param bool $allowZero Set true to allow zero, defaults to false
 * @return bool Success
 * @see http://en.wikipedia.org/wiki/Natural_number
 */
	public static function naturalNumber($check, $allowZero = false) {
		$regex = $allowZero ? '/^(?:0|[1-9][0-9]*)$/' : '/^[1-9][0-9]*$/';
		return static::_check($check, $regex);
	}

/**
 * Check that a value is a valid phone number.
 *
 * @param string|array $check Value to check (string or array)
 * @param string $regex Regular expression to use
 * @param string $country Country code (defaults to 'all')
 * @return bool Success
 */
	public static function phone($check, $regex = null, $country = 'all') {
		if ($regex === null) {
			switch ($country) {
				case 'us':
				case 'ca':
				case 'can': // deprecated three-letter-code
				case 'all':
					// includes all NANPA members.
					// see http://en.wikipedia.org/wiki/North_American_Numbering_Plan#List_of_NANPA_countries_and_territories
					$regex = '/^(?:(?:\+?1\s*(?:[.-]\s*)?)?';

					// Area code 555, X11 is not allowed.
					$areaCode = '(?![2-9]11)(?!555)([2-9][0-8][0-9])';
					$regex .= '(?:\(\s*' . $areaCode . '\s*\)|' . $areaCode . ')';
					$regex .= '\s*(?:[.-]\s*)?)';

					// Exchange and 555-XXXX numbers
					$regex .= '(?!(555(?:\s*(?:[.\-\s]\s*))(01([0-9][0-9])|1212)))';
					$regex .= '(?!(555(01([0-9][0-9])|1212)))';
					$regex .= '([2-9]1[02-9]|[2-9]1[02-9]|[2-9][02-9]1|[2-9][02-9]{2})\s*(?:[.-]\s*)';

					// Local number and extension
					$regex .= '?([0-9]{4})';
					$regex .= '(?:\s*(?:#|x\.?|ext\.?|extension)\s*(\d+))?$/';
				break;
			}
		}
		if (empty($regex)) {
			return static::_pass('phone', $check, $country);
		}
		return static::_check($check, $regex);
	}

/**
 * Checks that a given value is a valid postal code.
 *
 * @param string|array $check Value to check
 * @param string $regex Regular expression to use
 * @param string $country Country to use for formatting
 * @return bool Success
 */
	public static function postal($check, $regex = null, $country = 'us') {
		if ($regex === null) {
			switch ($country) {
				case 'uk':
					$regex = '/\\A\\b[A-Z]{1,2}[0-9][A-Z0-9]? [0-9][ABD-HJLNP-UW-Z]{2}\\b\\z/i';
					break;
				case 'ca':
					$district = '[ABCEGHJKLMNPRSTVYX]';
					$letters = '[ABCEGHJKLMNPRSTVWXYZ]';
					$regex = "/\\A\\b{$district}[0-9]{$letters} [0-9]{$letters}[0-9]\\b\\z/i";
					break;
				case 'it':
				case 'de':
					$regex = '/^[0-9]{5}$/i';
					break;
				case 'be':
					$regex = '/^[1-9]{1}[0-9]{3}$/i';
					break;
				case 'us':
					$regex = '/\\A\\b[0-9]{5}(?:-[0-9]{4})?\\b\\z/i';
					break;
			}
		}
		if (empty($regex)) {
			return static::_pass('postal', $check, $country);
		}
		return static::_check($check, $regex);
	}

/**
 * Validate that a number is in specified range.
 * if $lower and $upper are not set, will return true if
 * $check is a legal finite on this platform
 *
 * @param string $check Value to check
 * @param int|float $lower Lower limit
 * @param int|float $upper Upper limit
 * @return bool Success
 */
	public static function range($check, $lower = null, $upper = null) {
		if (!is_numeric($check)) {
			return false;
		}
		if ((float)$check != $check) {
			return false;
		}
		if (isset($lower) && isset($upper)) {
			return ($check > $lower && $check < $upper);
		}
		return is_finite($check);
	}

/**
 * Checks that a value is a valid Social Security Number.
 *
 * @param string|array $check Value to check
 * @param string $regex Regular expression to use
 * @param string $country Country
 * @return bool Success
 * @deprecated Deprecated 2.6. Will be removed in 3.0.
 */
	public static function ssn($check, $regex = null, $country = null) {
		if ($regex === null) {
			switch ($country) {
				case 'dk':
					$regex = '/\\A\\b[0-9]{6}-[0-9]{4}\\b\\z/i';
					break;
				case 'nl':
					$regex = '/\\A\\b[0-9]{9}\\b\\z/i';
					break;
				case 'us':
					$regex = '/\\A\\b[0-9]{3}-[0-9]{2}-[0-9]{4}\\b\\z/i';
					break;
			}
		}
		if (empty($regex)) {
			return static::_pass('ssn', $check, $country);
		}
		return static::_check($check, $regex);
	}

/**
 * Checks that a value is a valid URL according to http://www.w3.org/Addressing/URL/url-spec.txt
 *
 * The regex checks for the following component parts:
 *
 * - a valid, optional, scheme
 * - a valid ip address OR
 *   a valid domain name as defined by section 2.3.1 of http://www.ietf.org/rfc/rfc1035.txt
 *   with an optional port number
 * - an optional valid path
 * - an optional query string (get parameters)
 * - an optional fragment (anchor tag)
 *
 * @param string $check Value to check
 * @param bool $strict Require URL to be prefixed by a valid scheme (one of http(s)/ftp(s)/file/news/gopher)
 * @return bool Success
 */
	public static function url($check, $strict = false) {
		static::_populateIp();
		$validChars = '([' . preg_quote('!"$&\'()*+,-.@_:;=~[]') . '\/0-9\p{L}\p{N}]|(%[0-9a-f]{2}))';
		$regex = '/^(?:(?:https?|ftps?|sftp|file|news|gopher):\/\/)' . (!empty($strict) ? '' : '?') .
			'(?:' . static::$_pattern['IPv4'] . '|\[' . static::$_pattern['IPv6'] . '\]|' . static::$_pattern['hostname'] . ')(?::[1-9][0-9]{0,4})?' .
			'(?:\/?|\/' . $validChars . '*)?' .
			'(?:\?' . $validChars . '*)?' .
			'(?:#' . $validChars . '*)?$/iu';
		return static::_check($check, $regex);
	}

/**
 * Checks if a value is in a given list. Comparison is case sensitive by default.
 *
 * @param string $check Value to check.
 * @param array $list List to check against.
 * @param bool $caseInsensitive Set to true for case insensitive comparison.
 * @return bool Success.
 */
	public static function inList($check, $list, $caseInsensitive = false) {
		if ($caseInsensitive) {
			$list = array_map('mb_strtolower', $list);
			$check = mb_strtolower($check);
		} else {
			$list = array_map('strval', $list);
		}
		return in_array((string)$check, $list, true);
	}

/**
 * Runs an user-defined validation.
 *
 * @param string|array $check value that will be validated in user-defined methods.
 * @param object $object class that holds validation method
 * @param string $method class method name for validation to run
 * @param array $args arguments to send to method
 * @return mixed user-defined class class method returns
 */
	public static function userDefined($check, $object, $method, $args = null) {
		return call_user_func_array(array($object, $method), array($check, $args));
	}

/**
 * Checks that a value is a valid UUID - http://tools.ietf.org/html/rfc4122
 *
 * @param string $check Value to check
 * @return bool Success
 */
	public static function uuid($check) {
		$regex = '/^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[0-5][a-fA-F0-9]{3}-[089aAbB][a-fA-F0-9]{3}-[a-fA-F0-9]{12}$/';
		return static::_check($check, $regex);
	}

/**
 * Attempts to pass unhandled Validation locales to a class starting with $classPrefix
 * and ending with Validation. For example $classPrefix = 'nl', the class would be
 * `NlValidation`.
 *
 * @param string $method The method to call on the other class.
 * @param mixed $check The value to check or an array of parameters for the method to be called.
 * @param string $classPrefix The prefix for the class to do the validation.
 * @return mixed Return of Passed method, false on failure
 */
	protected static function _pass($method, $check, $classPrefix) {
		$className = ucwords($classPrefix) . 'Validation';
		if (!class_exists($className)) {
			trigger_error(__d('cake_dev', 'Could not find %s class, unable to complete validation.', $className), E_USER_WARNING);
			return false;
		}
		if (!method_exists($className, $method)) {
			trigger_error(__d('cake_dev', 'Method %s does not exist on %s unable to complete validation.', $method, $className), E_USER_WARNING);
			return false;
		}
		$check = (array)$check;
		return call_user_func_array(array($className, $method), $check);
	}

/**
 * Runs a regular expression match.
 *
 * @param string $check Value to check against the $regex expression
 * @param string $regex Regular expression
 * @return bool Success of match
 */
	protected static function _check($check, $regex) {
		if (is_string($regex) && is_scalar($check) && preg_match($regex, $check)) {
			return true;
		}
		return false;
	}

/**
 * Luhn algorithm
 *
 * @param string|array $check Value to check.
 * @param bool $deep If true performs deep check.
 * @return bool Success
 * @see http://en.wikipedia.org/wiki/Luhn_algorithm
 */
	public static function luhn($check, $deep = false) {
		if (!is_scalar($check)) {
			return false;
		}
		if ($deep !== true) {
			return true;
		}
		if ((int)$check === 0) {
			return false;
		}
		$sum = 0;
		$length = strlen($check);

		for ($position = 1 - ($length % 2); $position < $length; $position += 2) {
			$sum += $check[$position];
		}

		for ($position = ($length % 2); $position < $length; $position += 2) {
			$number = $check[$position] * 2;
			$sum += ($number < 10) ? $number : $number - 9;
		}

		return ($sum % 10 === 0);
	}

/**
 * Checks the mime type of a file.
 *
 * @param string|array $check Value to check.
 * @param array|string $mimeTypes Array of mime types or regex pattern to check.
 * @return bool Success
 * @throws CakeException when mime type can not be determined.
 */
	public static function mimeType($check, $mimeTypes = array()) {
		if (is_array($check) && isset($check['tmp_name'])) {
			$check = $check['tmp_name'];
		}

		$File = new File($check);
		$mime = $File->mime();

		if ($mime === false) {
			throw new CakeException(__d('cake_dev', 'Can not determine the mimetype.'));
		}

		if (is_string($mimeTypes)) {
			return static::_check($mime, $mimeTypes);
		}

		foreach ($mimeTypes as $key => $val) {
			$mimeTypes[$key] = strtolower($val);
		}
		return in_array($mime, $mimeTypes);
	}

/**
 * Checks the filesize
 *
 * @param string|array $check Value to check.
 * @param string $operator See `Validation::comparison()`.
 * @param int|string $size Size in bytes or human readable string like '5MB'.
 * @return bool Success
 */
	public static function fileSize($check, $operator = null, $size = null) {
		if (is_array($check) && isset($check['tmp_name'])) {
			$check = $check['tmp_name'];
		}

		if (is_string($size)) {
			$size = CakeNumber::fromReadableSize($size);
		}
		$filesize = filesize($check);

		return static::comparison($filesize, $operator, $size);
	}

/**
 * Checking for upload errors
 *
 * @param string|array $check Value to check.
 * @param bool $allowNoFile Set to true to allow UPLOAD_ERR_NO_FILE as a pass.
 * @return bool
 * @see http://www.php.net/manual/en/features.file-upload.errors.php
 */
	public static function uploadError($check, $allowNoFile = false) {
		if (is_array($check) && isset($check['error'])) {
			$check = $check['error'];
		}
		if ($allowNoFile) {
			return in_array((int)$check, array(UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE), true);
		}

		return (int)$check === UPLOAD_ERR_OK;
	}

/**
 * Validate an uploaded file.
 *
 * Helps join `uploadError`, `fileSize` and `mimeType` into
 * one higher level validation method.
 *
 * ### Options
 *
 * - `types` - A list of valid mime types. If empty all types
 *   will be accepted. The `type` will not be looked at, instead
 *   the file type will be checked with ext/finfo.
 * - `minSize` - The minimum file size. Defaults to not checking.
 * - `maxSize` - The maximum file size. Defaults to not checking.
 * - `optional` - Whether or not this file is optional. Defaults to false.
 *   If true a missing file will pass the validator regardless of other constraints.
 *
 * @param array $file The uploaded file data from PHP.
 * @param array $options An array of options for the validation.
 * @return bool
 */
	public static function uploadedFile($file, $options = array()) {
		$options += array(
			'minSize' => null,
			'maxSize' => null,
			'types' => null,
			'optional' => false,
		);
		if (!is_array($file)) {
			return false;
		}
		$keys = array('error', 'name', 'size', 'tmp_name', 'type');
		ksort($file);
		if (array_keys($file) != $keys) {
			return false;
		}
		if (!static::uploadError($file, $options['optional'])) {
			return false;
		}
		if ($options['optional'] && (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
			return true;
		}
		if (isset($options['minSize']) && !static::fileSize($file, '>=', $options['minSize'])) {
			return false;
		}
		if (isset($options['maxSize']) && !static::fileSize($file, '<=', $options['maxSize'])) {
			return false;
		}
		if (isset($options['types']) && !static::mimeType($file, $options['types'])) {
			return false;
		}
		return true;
	}

/**
 * Lazily populate the IP address patterns used for validations
 *
 * @return void
 */
	protected static function _populateIp() {
		if (!isset(static::$_pattern['IPv6'])) {
			$pattern = '((([0-9A-Fa-f]{1,4}:){7}(([0-9A-Fa-f]{1,4})|:))|(([0-9A-Fa-f]{1,4}:){6}';
			$pattern .= '(:|((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})';
			$pattern .= '|(:[0-9A-Fa-f]{1,4})))|(([0-9A-Fa-f]{1,4}:){5}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})';
			$pattern .= '(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:)';
			$pattern .= '{4}(:[0-9A-Fa-f]{1,4}){0,1}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2}))';
			$pattern .= '{3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:){3}(:[0-9A-Fa-f]{1,4}){0,2}';
			$pattern .= '((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|';
			$pattern .= '((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:){2}(:[0-9A-Fa-f]{1,4}){0,3}';
			$pattern .= '((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2}))';
			$pattern .= '{3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:)(:[0-9A-Fa-f]{1,4})';
			$pattern .= '{0,4}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)';
			$pattern .= '|((:[0-9A-Fa-f]{1,4}){1,2})))|(:(:[0-9A-Fa-f]{1,4}){0,5}((:((25[0-5]|2[0-4]';
			$pattern .= '\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4})';
			$pattern .= '{1,2})))|(((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})))(%.+)?';

			static::$_pattern['IPv6'] = $pattern;
		}
		if (!isset(static::$_pattern['IPv4'])) {
			$pattern = '(?:(?:25[0-5]|2[0-4][0-9]|(?:(?:1[0-9])?|[1-9]?)[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|(?:(?:1[0-9])?|[1-9]?)[0-9])';
			static::$_pattern['IPv4'] = $pattern;
		}
	}

/**
 * Reset internal variables for another validation run.
 *
 * @return void
 */
	protected static function _reset() {
		static::$errors = array();
	}

}
