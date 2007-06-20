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
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 1.2.0.3830
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Deprecated
 */
/**
 * Not empty.
 */
	define('VALID_NOT_EMPTY', '/.+/');
/**
 * Numbers [0-9] only.
 */
	define('VALID_NUMBER', '/^[-+]?\\b[0-9]*\\.?[0-9]+\\b$/');
/**
 * A valid email address.
 */
	define('VALID_EMAIL', '/\\A(?:^([a-z0-9][a-z0-9_\\-\\.\\+]*)@([a-z0-9][a-z0-9\\.\\-]{0,63}\\.(com|org|net|biz|info|name|net|pro|aero|coop|museum|[a-z]{2,4}))$)\\z/i');
/**
 * A valid year (1000-2999).
 */
	define('VALID_YEAR', '/^[12][0-9]{3}$/');
/**
 * Offers different validation methods.
 *
 * Long description for file
 *
 * @package    cake
 * @subpackage cake.cake.libs
 * @since      CakePHP v 1.2.0.3830
 */
class Validation extends Object {
/**
 * Set the the value of methods $check param.
 *
 * @var string
 * @access public
 */
	var $check = null;
/**
 * Set to a valid regular expression in the class methods.
 * Can be set from $regex param also
 *
 * @var string
 * @access public
 */
	var $regex = null;
/**
 * Some class methods use a country to determine proper validation.
 * This can be passed to methods in the $country param
 *
 * @var string
 * @access public
 */
	var $country = null;
/**
 * Some class methods use a deeper validation when set to true
 *
 * @var string
 * @access public
 */
 	var $deep = null;
/**
 * Some class methods use the $type param to determine which validation to perfom in the method
 *
 * @var string
 * @access public
 */
	var $type = null;
/**
 * Holds an array of errors messages set in this class.
 * These are used for debugging purposes
 *
 * @var array
 * @access public
 */
	var $errors = array();
/**
 * Constructor.
 */
	function __construct() {
		parent::__construct();
	}
/**
 * Checks that a string contains only integer or letters
 *
 * Returns true if string contains only integer or letters
 *
 * $check can be passed as an array:
 * array('check' => 'valueToCheck');
 *
 * @param mixed $check Value to check
 * @return boolean Success
 * @access public
 */
	function alphaNumeric($check) {
		$this->__reset();
		$this->check = $check;

		if (is_array($check)) {
			$this->_extract($check);
		}

		if (empty($this->check)) {
			return false;
		}

		$this->regex = '/[^\\dA-Z]/i';
		if ($this->_check() === true){
			return false;
		} else {
			return true;
		}
	}
/**
 * Checks that a string length is within s specified range.
 * Spaces are included in the character count.
 * Returns true is string matches value min, max, or between min and max,
 *
 * @param string $check Value to check for length
 * @param int $min Minimum value in range (inclusive)
 * @param int $max Maximum value in range (inclusive)
 * @return boolean Success
 * @access public
 */
	function between($check, $min, $max) {
		$length = strlen($check);

		if ($length >= $min && $length <= $max) {
			return true;
		} else {
			return false;
		}
	}
/**
 * Returns true if field is left blank -OR- only whitespace characters are present in it's value
 * Whitespace characters include Space, Tab, Carriage Return, Newline
 *
 * $check can be passed as an array:
 * array('check' => 'valueToCheck');
 *
 * @param mixed $check Value to check
 * @return boolean Success
 * @access public
 */
	function blank($check) {
		$this->__reset();
		$this->check = $check;

		if (is_array($check)) {
			$this->_extract($check);
		}

		$this->regex = '/[^\\s]/';
		if ($this->_check() === true){
			return false;
		} else {
			return true;
		}
	}
/**
 * Validation of credit card numbers.
 * Returns true if $check is in the proper credit card format.
 *
 * @param mixed $check credit card number to validate
 * @param mixed $type 'all' may be passed as a sting, defaults to fast which checks format of most major credit cards
 * 							if an array is used only the values of the array are checked.
 * 							Example: array('amex', 'bankcard', 'maestro')
 * @param boolean $deep set to true this will check the Luhn algorithm of the credit card.
 * @param string $regex A custom regex can also be passed, this will be used instead of the defined regex values
 * @return boolean Success
 * @access public
 * @see Validation::_luhn()
 */
	function cc($check, $type = 'fast', $deep = false, $regex = null) {
		$this->__reset();
		$this->check = $check;
		$this->type = $type;
		$this->deep = $deep;
		$this->regex = $regex;

		if (is_array($check)) {
			$this->_extract($check);
		}

		$this->check = str_replace(array('-', ' '), '', $this->check);

		if (strlen($this->check) < 13){
			return false;
		}

		if (!is_null($this->regex)) {
			if ($this->_check()) {
				return $this->_luhn();
			}
		}

		$cards = array('all' => array('amex'     => '/^3[4|7]\\d{13}$/',
												'bankcard' => '/^56(10\\d\\d|022[1-5])\\d{10}$/',
												'diners'   => '/^(?:3(0[0-5]|[68]\\d)\\d{11})|(?:5[1-5]\\d{14})$/',
												'disc'     => '/^(?:6011|650\\d)\\d{12}$/',
												'electron' => '/^(?:417500|4917\\d{2}|4913\\d{2})\\d{10}$/',
												'enroute'  => '/^2(?:014|149)\\d{11}$/',
												'jcb'      => '/^(3\\d{4}|2100|1800)\\d{11}$/',
												'maestro'  => '/^(?:5020|6\\d{3})\\d{12}$/',
												'mc'       => '/^5[1-5]\\d{14}$/',
												'solo'     => '/^(6334[5-9][0-9]|6767[0-9]{2})\\d{10}(\\d{2,3})?$/',
												'switch'   => '/^(?:49(03(0[2-9]|3[5-9])|11(0[1-2]|7[4-9]|8[1-2])|36[0-9]{2})\\d{10}(\\d{2,3})?)|(?:564182\\d{10}(\\d{2,3})?)|(6(3(33[0-4][0-9])|759[0-9]{2})\\d{10}(\\d{2,3})?)$/',
												'visa'     => '/^4\\d{12}(\\d{3})?$/',
												'voyager'  => '/^8699[0-9]{11}$/'),
							'fast'   => '/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6011[0-9]{12}|3(?:0[0-5]|[68][0-9])[0-9]{11}|3[47][0-9]{13})$/');

		if (is_array($this->type)) {
			foreach ($this->type as $key => $value) {
				$card = low($value);
				$this->regex = $cards['all'][$card];

				if ($this->_check()) {
					return $this->_luhn();
				}
			}
		} else {
			if ($this->type == 'all') {
				foreach ($cards['all'] as $key => $value) {
					$this->regex = $value;

					if ($this->_check()) {
						return $this->_luhn();
					}
				}
			} else {
				$this->regex = $cards['fast'];

				if ($this->_check()) {
					return $this->_luhn();
				}
			}
		}
	}
/**
 * Used to compare 2 numeric values.
 *
 * @param mixed $check1 if string is passed for a string must also be passed for $check2
 * 							used as an array it must be passed as array('check1' => value, 'operator' => 'value', 'check2' -> value)
 * @param string $operator Can be either a word or operand
 * 								is greater >, is less <, greater or equal >=
 * 								less or equal <=, is less <, equal to ==, not equal !=
 * @param int $check2 only needed if $check1 is a string
 * @return boolean
 * @access public
 */
	function comparison($check1, $operator = null, $check2 = null) {
		$return = false;
		if (is_array($check1)) {
			extract($check1, EXTR_OVERWRITE);
		}
		$operator = str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B"), "", low($operator));

		switch($operator) {
			case 'isgreater':
			case '>':
				if ($check1 > $check2) {
					$return = true;
				}
			break;
			case 'isless':
			case '<':
				if ($check1 < $check2) {
					$return = true;
				}
			break;
			case 'greaterorequal':
			case '>=':
				if ($check1 >= $check2) {
					$return = true;
				}
			break;
			case 'lessorequal':
			case '<=':
				if ($check1 <= $check2) {
					$return = true;
				}
			break;
			case 'equalto':
			case '==':
				if ($check1 == $check2) {
					$return = true;
				}
			break;
			case 'notequal':
			case '!=':
				if ($check1 != $check2) {
					$return = true;
				}
			break;
			default:
				$this->errors[] = __('You must define the $operator parameter for Validation::comparison()', true);
				$return = false;
			break;
		}
		return $return;
	}
/**
 * Used when a custom regular expression is needed.
 *
 * @param mixed $check When used as a string, $regex must also be a valid regular expression.
 *								As and array: array('check' => value, 'regex' => 'valid regular expression')
 * @param string $regex If $check is passed as a string, $regex must also be set to valid regular expression
 * @return boolean Success
 * @access public
 */
	function custom($check, $regex = null) {
		$this->__reset();
		$this->check = $check;
		$this->regex = $regex;
		if (is_array($check)) {
			$this->_extract($check);
		}
		if ($this->regex === null){
			$this->errors[] = __('You must define a regular expression for Validation::custom()', true);
			return false;
		}
		return $this->_check();
	}
/**
 * Date validation, determines if the string passed is a valid date.
 * keys that expect full month, day and year will validate leap years
 *
 * @param string $check a valid date string
 * @param mixed $format Use a string or an array of the keys below. Arrays should be passed as array('dmy', 'mdy', etc)
 * 					Keys: dmy 27-12-2006 or 27-12-06 separators can be a space, period, dash, forward slash
 * 							mdy 12-27-2006 or 12-27-06 separators can be a space, period, dash, forward slash
 * 							ymd 2006-12-27 or 06-12-27 separators can be a space, period, dash, forward slash
 * 							dMy 27 December 2006 or 27 Dec 2006
 * 							Mdy December 27, 2006 or Dec 27, 2006 comma is optional
 * 							My December 2006 or Dec 2006
 * 							my 12/2006 or 12/06 separators can be a space, period, dash, forward slash
 * @param string $regex If a custom regular expression is used this is the only validation that will occur.
 * @return boolean Success
 * @access public
 */
	function date($check, $format = 'ymd', $regex = null) {
		$this->__reset();
		$this->check = $check;
		$this->regex = $regex;

		if (!is_null($this->regex)) {
			return $this->_check();
		}

		$search = array();

		if (is_array($format)){
			foreach ($format as $key => $value){
				$search[$value] = $value;
			}
		} else {
			$search[$format] = $format;
		}
		$regex['dmy'] = '%^(?:(?:31(\\/|-|\\.|\\x20)(?:0?[13578]|1[02]))\\1|(?:(?:29|30)(\\/|-|\\.|\\x20)(?:0?[1,3-9]|1[0-2])\\2))(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$|^(?:29(\\/|-|\\.|\\x20)0?2\\3(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\\d|2[0-8])(\\/|-|\\.|\\x20)(?:(?:0?[1-9])|(?:1[0-2]))\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%';
		$regex['mdy'] = '%^(?:(?:(?:0?[13578]|1[02])(\\/|-|\\.|\\x20)31)\\1|(?:(?:0?[13-9]|1[0-2])(\\/|-|\\.|\\x20)(?:29|30)\\2))(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$|^(?:0?2(\\/|-|\\.|\\x20)29\\3(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:(?:0?[1-9])|(?:1[0-2]))(\\/|-|\\.|\\x20)(?:0?[1-9]|1\\d|2[0-8])\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%';
		$regex['ymd'] = '%^(?:(?:(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00)))(\\/|-|\\.|\\x20)(?:0?2\\1(?:29)))|(?:(?:(?:1[6-9]|[2-9]\\d)?\\d{2})(\\/|-|\\.|\\x20)(?:(?:(?:0?[13578]|1[02])\\2(?:31))|(?:(?:0?[1,3-9]|1[0-2])\\2(29|30))|(?:(?:0?[1-9])|(?:1[0-2]))\\2(?:0?[1-9]|1\\d|2[0-8]))))$%';
		$regex['dMy'] = '/^((31(?!\\ (Feb(ruary)?|Apr(il)?|June?|(Sep(?=\\b|t)t?|Nov)(ember)?)))|((30|29)(?!\\ Feb(ruary)?))|(29(?=\\ Feb(ruary)?\\ (((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)))))|(0?[1-9])|1\\d|2[0-8])\\ (Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)\\ ((1[6-9]|[2-9]\\d)\\d{2})$/';
		$regex['Mdy'] = '/^(?:(((Jan(uary)?|Ma(r(ch)?|y)|Jul(y)?|Aug(ust)?|Oct(ober)?|Dec(ember)?)\\ 31)|((Jan(uary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sept|Nov|Dec)(ember)?)\\ (0?[1-9]|([12]\\d)|30))|(Feb(ruary)?\\ (0?[1-9]|1\\d|2[0-8]|(29(?=,?\\ ((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)))))))\\,?\\ ((1[6-9]|[2-9]\\d)\\d{2}))$/';
		$regex['My'] = '%^(Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)[ /]((1[6-9]|[2-9]\\d)\\d{2})$%';
		$regex['my'] = '%^(((0[123456789]|10|11|12)([- /.])(([1][9][0-9][0-9])|([2][0-9][0-9][0-9]))))$%';

		foreach ($search as $key){
			$this->regex = $regex[$key];

			if ($this->_check() === true){
				return true;
			}
		}
		return false;
	}
/**
 * Checks that a value is a valid decimal. If $places is null, the $check is allowed to be a scientific float
 * If no decimal point is found a false will be returned. Both the sign and exponent are optional.
 *
 * @param integer $check The value the test for decimal
 * @param integer $places if set $check value must have exactly $places after the decimal point
 * @param string $regex If a custom regular expression is used this is the only validation that will occur.
 * @return boolean Success
 * @access public
 */
	function decimal($check, $places = null, $regex = null) {
		$this->__reset();
		$this->regex = $regex;
		$this->check = $check;

		if (!is_null($this->regex)) {
			return $this->_check();
		}

		if (is_null($places)) {
			$this->regex = '/^[-+]?[0-9]*\\.{1}[0-9]+(?:[eE][-+]?[0-9]+)?$/';
			return $this->_check();
		}

		$this->regex = '/^[-+]?[0-9]*\\.{1}[0-9]{'.$places.'}$/';
		return $this->_check();
	}
/**
 * Validates for an email address.
 *
 * @param string $check Value to check
 * @param boolean $deep Perform a deeper validation (if true), by also checking availability of host
 * @param string $regex Regex to use (if none it will use built in regex)
 * @return boolean Success
 * @access public
 */
	function email($check, $deep = false, $regex= null) {
		$this->__reset();
		$this->check = $check;
		$this->regex = $regex;
		$this->deep = $deep;

		if (is_array($check)) {
			$this->_extract($check);
		}

		if (is_null($this->regex)) {
			$this->regex = '/\\A(?:^([a-z0-9][a-z0-9_\\-\\.\\+]*)@([a-z0-9][a-z0-9\\.\\-]{0,63}\\.(com|org|net|biz|info|name|net|pro|aero|coop|museum|[a-z]{2,4}))$)\\z/i';
		}
		$return = $this->_check();

		if ($this->deep === false || $this->deep === null) {
			return $return;
		}

		if ($return === true && preg_match('/@([a-z0-9][a-z0-9\\.\\-]{0,63}\\.([a-z]*))/', $this->check, $regs)) {
			$host = gethostbynamel($regs[1]);
			if (is_array($host)) {
				return true;
			}
		}

		return false;
	}

/**
 * Check that value is exactly $comparedTo.
 *
 * @param mixed $check Value to check
 * @param mixed $comparedTo Value to compare
 * @access public
 * @todo Implement
 */
	function equalTo($check, $comparedTo) {

	}

/**
 * Check that value is a file.
 *
 * @param mixed $check Value to check
 * @access public
 * @todo Implement
 */
	function file($check) {

	}
/**
 * Validation of an IPv4 address.
 *
 * @param string $check The string to test.
 * @return boolean Success
 * @access public
 */
	function ip($check) {
		$bytes = explode('.', $check);
		if (count($bytes) == 4) {
			$returnValue = true;
			foreach ($bytes as $byte) {
				if (!(is_numeric($byte) && $byte >= 0 && $byte <= 255)) {
					$returnValue = false;
				}
			}
			return $returnValue;
		}
		return false;
	}
/**
 * Checks whether the length of a string is greater or equal to a minimal length.
 *
 * @param string $check The string to test
 * @param int $min The minimal string length
 * @return boolean Success
 * @access public
 */
	function minLength($check, $min) {
		$length = strlen($check);
		return ($length >= $min);
	}
/**
 * Checks whether the length of a string is smaller or equal to a maximal length..
 *
 * @param string $check The string to test
 * @param int $max The maximal string length
 * @return boolean Success
 * @access public
 */
	function maxLength($check, $max) {
		$length = strlen($check);
		return ($length <= $max);
	}

/**
 * Checks that a value is a monetary amount.
 *
 * @param string $check Value to check
 * @param string $symbolPosition Where symbol is located (left/right)
 * @return boolean Success
 * @access public
 */
    function money($check, $symbolPosition = 'left') {
    	$this->check = $check;
    	switch ($symbolPosition) {
    		case 'left':
    			$this->regex = '/^(?!\\u00a2)\\p{Sc}?(?!0,?\\d)(?:\\d{1,3}(?:([, .])\\d{3})?(?:\\1\\d{3})*|(?:\\d+))((?!\\1)[,.]\\d{2})?$/';
    		break;
    		case 'right':
    			$this->regex = '/^(?!0,?\\d)(?:\\d{1,3}(?:([, .])\\d{3})?(?:\\1\\d{3})*|(?:\\d+))((?!\\1)[,.]\\d{2})?(?<!\\u00a2)\\p{Sc}?$/';
    		break;
    	}
    	return $this->_check();
    }

/**
 * Validate a multiple select.
 *
 * @param mixed $check Value to check
 * @param mixed $type Type of check
 * @param string $regex Use custom regular expression
 * @access public
 * @todo Implement
 */
    function multiple($check, $type, $regex= null) {
    	//Validate a select object for a selected index past 0.
    	//Validate a select against a list of restriced indexes.
    	//Validate a multiple-select for the quantity selected.
	}

/**
 * Validate that a number is in specified range.
 *
 * @param string $check Value to check
 * @param int $lower Lower limit
 * @param int $upper Upper limit
 * @access public
 * @todo Implement
 */
	function number($check, $lower = null, $upper = null ) {
		if (isset($lower) && isset($upper) && $lower > $upper) {
			//error
		}
		if (is_float($check)) {

		}
	}

/**
 * Checks if a value is numeric.
 *
 * @param string $check Value to check
 * @return boolean Succcess
 * @access public
 */
	function numeric($check) {
		return is_numeric($check);
	}

/**
 * Check that a value is a valid phone number.
 *
 * @param mixed $check Value to check (string or array)
 * @param string $regex Regular expression to use
 * @param string $country Country code (defaults to 'all')
 * @return boolean Success
 * @access public
 */
	function phone($check, $regex= null, $country = 'all') {
		if (is_array($check)) {
			$this->_extract($check);
		} else {
			$this->check = $check;
			$this->regex = $regex;
			$this->country = $country;
		}

		if (is_null($this->regex)) {
			switch ($this->country) {
				case 'us':
					$this->regex  = '/1?[-. ]?\\(?([0-9]{3})\\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})/';
					break;
			}
		}
		return $this->_check();
	}

/**
 * Checks that a given value is a valid postal code.
 *
 * @param mixed $check Value to check
 * @param string $regex Regular expression to use
 * @param string $country Country to use for formatting
 * @return boolean Success
 * @access public
 */
	function postal($check, $regex= null, $country = null) {
		if (is_array($check)) {
			$this->_extract($check);
		} else {
			$this->check = $check;
			$this->regex = $regex;
			$this->country = $country;
		}

		if (is_null($this->regex)) {
			switch ($this->country) {
				case 'us':
					$this->regex  = '/\\A\\b[0-9]{5}(?:-[0-9]{4})?\\b\\z/i';
				break;
				case 'uk':
					$this->regex  = '/\\A\\b[A-Z]{1,2}[0-9][A-Z0-9]? [0-9][ABD-HJLNP-UW-Z]{2}\\b\\z/i';
				break;
				case 'ca':
					$this->regex  = '/\\A\\b[ABCEGHJKLMNPRSTVXY][0-9][A-Z] [0-9][A-Z][0-9]\\b\\z/i';
					break;
			}
		}
		return $this->_check();
	}

/**
 * Checks that a value is a valid Social Security Number.
 *
 * @param mixed $check Value to check
 * @param string $regex Regular expression to use
 * @param string $country Country
 * @return boolean Success
 * @access public
 */
	function ssn($check, $regex = null, $country = null) {
		if (is_array($check)) {
			$this->_extract($check);
		} else {
			$this->check = $check;
			$this->regex = $regex;
			$this->country = $country;
		}

		if (is_null($this->regex)) {
			switch ($this->country) {
				case 'us':
					$this->regex  = '/\\A\\b[0-9]{3}-[0-9]{2}-[0-9]{4}\\b\\z/i';
				break;
				case 'dk':
					$this->regex  = '/\\A\\b[0-9]{6}-[0-9]{4}\\b\\z/i';
				break;
				case 'nl':
					$this->regex  = '/\\A\\b[0-9]{9}\\b\\z/i';
				break;
			}
		}
		return $this->_check();
	}

/**
 * Checks that a value is a valid URL.
 *
 * @param string $check Value to check
 * @return boolean Success
 * @access public
 */
	function url($check) {
		$this->check = $check;
		$this->regex = '/\\A(?:(https?|ftps?|file|news|gopher):\\/\\/[\\w\\-_]+(\\.[\\w\\-_]+)+([\\w\\-\\.,\'@?^=%&:;\/~\\+#]*[\\w\\-\\@?^=%&\/~\\+#])?)\\z/i';
		return $this->_check();
	}

/**
 * Runs an user-defined validation.
 *
 * @param object $object Object that holds validation method
 * @param string $method Method name for validation to run
 * @param array $args Arguments to send to method
 * @return mixed Whatever method returns
 * @access public
 */
	function userDefined($object, $method, $args) {
		return call_user_func_array(array(&$object, $method), $args);
	}

/**
 * Runs a regular expression match.
 *
 * @return boolean Success of match
 * @access protected
 */
	function _check() {
		if (preg_match($this->regex, $this->check)) {
			$this->error[] = false;
			return true;
		} else {
			$this->error[] = true;
			return false;
		}
	}

/**
 * Get the values to use when value sent to validation method is
 * an array.
 *
 * @param array $params Parameters sent to validation method
 * @access protected
 */
	function _extract($params) {
		extract($params, EXTR_OVERWRITE);

		if (isset($check)) {
			$this->check = $check;
		}
		if (isset($regex)) {
			$this->regex = $regex;
		}
		if (isset($country)) {
			$this->country = strtolower($country);
		}
		if (isset($deep)) {
			$this->deep = $deep;
		}
		if (isset($type)) {
			$this->type = $type;
		}
	}
/**
 * Luhn algorithm
 *
 * @see http://en.wikipedia.org/wiki/Luhn_algorithm
 * @return boolean Success
 * @access protected
 */
	function _luhn() {
		if ($this->deep === true){
			if ($this->check == 0) {
				return false;
			}
			$sum = 0;
			$length = strlen($this->check);

			for ($position = 1 - ($length % 2); $position < $length; $position += 2) {
				$sum += substr($this->check, $position, 1);
			}

			for ($position = ($length % 2); $position < $length; $position += 2) {
				$number = substr($this->check, $position, 1) * 2;
				if ($number < 10) {
					$sum += $number;
				} else {
					$sum += $number - 9;
				}
			}

			if ($sum % 10 != 0) {
				return false;
			}
		}
		return true;
	}

/**
 * Reset internal variables for another validation run.
 *
 * @access private
 */
	function __reset(){
		$this->check = null;
		$this->regex = null;
		$this->country = null;
		$this->deep = null;
		$this->type = null;
	}
}
?>