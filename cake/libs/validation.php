<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP v 1.2.0.3830
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Short description for file.
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
 */
	var $check = null;
/**
 * Set to a valid regular expression in the class methods.
 * Can be set from $regex param also
 *
 * @var string
 */
	var $regex = null;
/**
 * Some class methods use a country to determine proper validation.
 * This can be passed to methods in the $country param
 *
 * @var string
 */
	var $country = null;
/**
 * Some class methods use a deeper validation when set to true
 *
 * @var string
 */
 	var $deep = null;
/**
 * Some class methods use the $type param to determine which validation to perfom in the method
 *
 * @var string
 */
	var $type = null;

/**
 * Holds an array of errors messages set in this class.
 * These are used for debugging purposes
 *
 * @var array
 */
	var $errors = array();

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
 * @param mixed $check
 * @return boolean
 * @access public
 */
	function alphaNumeric($check) {
		$this->__reset();
		$this->check = $check;

		if (is_array($check)) {
			$this->_extract($check);
		}

		$this->regex = '/[^\\dA-Z]/i';
		if($this->_check() === true){
			return false;
		} else {
			return true;
		}
	}

/**
 * Checks that a string is within s specified range.

 * Spaces are included in the character count
 * Returns true is string matches value min, max, or between min and max,
 *
 * @param string $check
 * @param int $min
 * @param int $max
 * @return boolean
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
 * Returns false if field is left blank -OR- only whitespace characters are present in it's value
 * Whitespace characters include Space, Tab, Carriage Return, Newline
 *
 * $check can be passed as an array:
 * array('check' => 'valueToCheck');
 *
 * @param mixed $check
 * @return boolean
 * @access public
 */
	function blank($check) {
		$this->__reset();
		$this->check = $check;

		if (is_array($check)) {
			$this->_extract($check);
		}

		$this->regex = '/[^\\s]/';
		if($this->_check() === true){
			return false;
		} else {
			return true;
		}
	}

/**
 * Validation of credit card numbers
 *
 * Returns true if $check is in the proper credit card format
 *
 *
 * @param mixed $check credit card number to validate
 * @param mixed $type 'all' may be passed as a sting, defaults to fast which checks format of most major credit cards
 * 							if an array is used only the values of the array are checked.
 * 							Example: array('amex', 'bankcard', 'maestro')
 * @param boolean $deep set to true this will check the Luhn algorithm of the credit card.
 * @see Validation::_luhn()
 * @param string $regex A custom regex can also be passed, this will be used instead of the defined regex values
 * @return boolean
 * @access public
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

		if(strlen($this->check) < 13){
			return false;
		}

		if(!is_null($this->regex)) {
			if($this->_check()) {
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

				if($this->_check()) {
					return $this->_luhn();
				}
			}
		} else {
			if($this->type == 'all') {
				foreach ($cards['all'] as $key => $value) {
					$this->regex = $value;

					if($this->_check()) {
						return $this->_luhn();
					}
				}
			} else {
				$this->regex = $cards['fast'];

				if($this->_check()) {
					return $this->_luhn();
				}
			}
		}
	}

/**
 * Used to compare 2 numeric values
 *
 * @param mixed $check1 if string is passed for a string must also be passed for $check2
 * 							used as an array it must be passed as array('check1' => value, 'operator' => 'value', 'check2' -> value)
 * @param string $operator Can be either a word or operand
 * 								is greater >, is less <, greater or equal >=
 * 								less or equal <=, is less <, equal to ==, not equal !=
 *
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
				if($check1 > $check2) {
					$return = true;
				}
			break;
			case 'isless':
			case '<':
				if($check1 < $check2) {
					$return = true;
				}
			break;
			case 'greaterorequal':
			case '>=':
				if($check1 >= $check2) {
					$return = true;
				}
			break;
			case 'lessorequal':
			case '<=':
				if($check1 <= $check2) {
					$return = true;
				}
			break;
			case 'equalto':
			case '==':
				if($check1 == $check2) {
					$return = true;
				}
			break;
			case 'notequal':
			case '!=':
				if($check1 != $check2) {
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
 * @return boolean
 * @access public
 */
	function custom($check, $regex = null) {
		$this->check = $check;
		$this->regex = $regex;
		if (is_array($check)) {
			$this->_extract($check);
		}
		if($this->regex === null){
			$this->errors[] = __('You must define a regular expression for Validation::custom()', true);
			return false;
		}
		return $this->_check();
	}

	function date($check, $format = 'ymd', $regex = null) {
		$this->__reset();
		$this->check = $check;
		$this->regex = $regex;
		$search = array();

		if(is_array($format)){
			foreach($format as $key => $value){
				$search[$value] = $value;
			}
		} else {
			$search[$format] = $format;
		}
		$regex['mdy'] = '%^(?:(?:(?:0?[13578]|1[02])(\\/|-|\\.|\\x20)31)\\1|(?:(?:0?[13-9]|1[0-2])(\\/|-|\\.|\\x20)(?:29|30)\\2))(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$|^(?:0?2(\\/|-|\\.|\\x20)29\\3(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:(?:0?[1-9])|(?:1[0-2]))(\\/|-|\\.|\\x20)(?:0?[1-9]|1\\d|2[0-8])\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%';
		$regex['dmy'] = '%^(?:(?:31(\\/|-|\\.|\\x20)(?:0?[13578]|1[02]))\\1|(?:(?:29|30)(\\/|-|\\.|\\x20)(?:0?[1,3-9]|1[0-2])\\2))(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$|^(?:29(\\/|-|\\.|\\x20)0?2\\3(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\\d|2[0-8])(\\/|-|\\.|\\x20)(?:(?:0?[1-9])|(?:1[0-2]))\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%';
		$regex['ymd'] = '%^(?:(?:(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00)))(\\/|-|\\.|\\x20)(?:0?2\\1(?:29)))|(?:(?:(?:1[6-9]|[2-9]\\d)?\\d{2})(\\/|-|\\.|\\x20)(?:(?:(?:0?[13578]|1[02])\\2(?:31))|(?:(?:0?[1,3-9]|1[0-2])\\2(29|30))|(?:(?:0?[1-9])|(?:1[0-2]))\\2(?:0?[1-9]|1\\d|2[0-8]))))$%';
		$regex['dMy'] = '/^((31(?!\\ (Feb(ruary)?|Apr(il)?|June?|(Sep(?=\\b|t)t?|Nov)(ember)?)))|((30|29)(?!\\ Feb(ruary)?))|(29(?=\\ Feb(ruary)?\\ (((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)))))|(0?[1-9])|1\\d|2[0-8])\\ (Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)\\ ((1[6-9]|[2-9]\\d)\\d{2})$/';

		foreach ($search as $key){
			$this->regex = $regex[$key];

			if($this->_check() === true){
				return true;
			}
		}
		return false;
	}

	function decimal($check, $type, $regex= null) {
		//Validates a simple decimal format
		//Validate a complex decimal format.
	}

	function email($check, $regex= null, $deep = false) {
		if (is_array($check)) {
			$this->_extract($check);
		} else {
			$this->check = $check;
			$this->regex = $regex;
			$this->deep = $deep;
		}

		if(is_null($this->regex)) {
			$this->regex = '/\\A(?:^([a-z0-9][a-z0-9_\\-\\.\\+]*)@([a-z0-9][a-z0-9\\.\\-]{0,63}\\.(com|org|net|biz|info|name|net|pro|aero|coop|museum|[a-z]{2,4}))$)\\z/i';
		}

		if($this->_check() && $this->deep) {
			if (preg_match('/@([a-z0-9][a-z0-9\\.\\-]{0,63}\\.([a-z]*))/', $check, $regs)) {
				$host = gethostbynamel($regs[1]);
				if (is_array($host)) {
					$this->error[] = false;
					return true;
				} else {
					$this->error[] = true;
					return false;
				}
			}
		}
	}

	function equalTo($check, $comparedTo) {

	}

	function file($check) {

	}

	function ip($check) {

	}

	function minLength($check, $min) {
		$length = strlen($check);
	}

	function maxLength($check, $max) {
		$length = strlen($check);
	}

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

    function multiple($check, $type, $regex= null) {
    	//Validate a select object for a selected index past 0.
    	//Validate a select against a list of restriced indexes.
    	//Validate a multiple-select for the quantity selected.
	}

	function number($check, $lower = null, $upper = null ) {
		if (isset($lower) && isset($upper) && $lower > $upper) {
			//error
		}
		if(is_float($check)) {

		}
	}

	function numeric($check) {
		return is_numeric($check);
	}

	function phone($check, $regex= null, $country = 'all') {
		if (is_array($check)) {
			$this->_extract($check);
		} else {
			$this->check = $check;
			$this->regex = $regex;
			$this->country = $country;
		}

		if(is_null($this->regex)) {
			switch ($this->country) {
				case 'us':
					$this->regex  = '/1?[-. ]?\\(?([0-9]{3})\\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})/';
					break;
			}
		}
		return $this->_check();
	}

	function postal($check, $regex= null, $country = null) {
		if (is_array($check)) {
			$this->_extract($check);
		} else {
			$this->check = $check;
			$this->regex = $regex;
			$this->country = $country;
		}

		if(is_null($this->regex)) {
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

	function ssn($check, $regex= null, $country = null) {
		if (is_array($check)) {
			$this->_extract($check);
		} else {
			$this->check = $check;
			$this->regex = $regex;
			$this->country = $country;
		}

		if(is_null($this->regex)) {
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

	function url($check) {
		$this->check = $check;
		$this->regex = '/\\A(?:(https?|ftps?|file|news|gopher):\\/\\/[\\w\\-_]+(\\.[\\w\\-_]+)+([\\w\\-\\.,@?^=%&:\/~\\+#]*[\\w\\-\\@?^=%&\/~\\+#])?)\\z/i';
		return $this->_check();
	}

	function userDefined($object, $method, $args) {
		return call_user_func_array(array(&$object, $method), $args);
	}

	function _check() {
		if (preg_match($this->regex, $this->check)) {
			$this->error[] = false;
			return true;
		} else {
			$this->error[] = true;
			return false;
		}
	}

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
 * @return boolean
 * @access protected
 */
	function _luhn() {
		if($this->deep === true){
			if($this->check == 0) {
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

	function __reset(){
		$this->check = null;
		$this->regex = null;
		$this->country = null;
		$this->deep = null;
		$this->type = null;
	}
}
?>