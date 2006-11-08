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
 * Enter description here...
 *
 * @var unknown_type
 */
	var $check = null;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $regex = null;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $country = null;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
 	var $deep = null;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $type = null;

/**
 * Enter description here...
 *
 * @var unknown_type
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
 */
	function alphaNumeric($check) {
		if (is_array($check)) {
			$this->_extract($check);
		} else {
			$this->check = $check;
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
 * Whitespace characters include Space, Tab, Carriage Return, Newline, Formfeed
 *
 * @param array or string $check
 * @return boolean
 */
	function blank($check) {
		if (is_array($check)) {
			$this->_extract($check);
			$this->regex = '/\\S*/';
		} else {
			$this->check = $check;
			$this->regex = '/\\S*/';
		}
		return $this->_check();
	}

	function cc($check, $regex = null, $type = 'fast') {
		$this->type = $type;
		if (is_array($check)) {
			$this->_extract($check);
		} else {
			$this->check = $check;
		}

		if(isset($this->regex)) {
			return $this->_check();
		}

		$cards = array('all' => array('visa'    => '/^4\\d{12}(\\d{3})?$/',
												'mc'      => '/^5[1-5]\\d{14}$/',
												'disc'    => '/^6011\\d{12}$/',
												'amex'    => '/^3[4|7]\\d{13}$/',
												'diners'  => '/^3[0|6|8]\\d{12}$/',
												'enroute' => '/^2[014|149]\\d{11}$/',
												'jcb'     => '/^3[088|096|112|158|337|528]\\d{12}$/',
												'switch'  => '/^(49030[2-9]|49033[5-9]|49110[1-2]|4911(7[4-9]|8[1-2])|4936[0-9]{2}|564182|6333[0-4][0-9]|6759[0-9]{2})\\d{10}(\\d{2,3})?$/',
												'delta'   => '/^4(1373[3-7]|462[0-9]{2}|5397[8|9]|54313|5443[2-5]|54742|567(2[5-9]|3[0-9]|4[0-5])|658[3-7][0-9]|659(0[1-9]|[1-4][0-9]|50)|844[09|10]|909[6-7][0-9]|9218[1|2]|98824)\\d{10}$/',
												'solo'    => '/^(6334[5-9][0-9]|6767[0-9]{2})\\d{10}(\\d{2,3})?$/'),
												'fast'   => '/^(6334[5-9][0-9]|6767[0-9]{2})\\d{10}(\\d{2,3})?$/');

		if (is_array($this->type)) {
			foreach ($this->type as $key => $value) {
				$card = low($key);
				$this->regex = $cards['all'][$card];
				if($this->_check()) {
					return true;
				}
			}
		} else {
			if($this->type == 'all') {
				foreach ($cards['all'] as $key => $value) {
					$this->regex = $value;

					if($this->_check()) {
						return true;
					}
				}
			} else {
				$this->regex = $cards['fast'];
				return $this->_check();
			}
		}
	}

	function comparison($check1, $operator = null, $check2 = null) {
		$return = false;
		if (is_array($check1)) {
			extract($params, EXTR_OVERWRITE);
		}

		switch($operator) {
			case 'is greater':
				if($check1 > $check2) {
					$return = true;
				}
			break;
			case 'is less':
				if($check1 < $check2) {
					$return = true;
				}
			break;
			case 'greater or equal':
				if($check1 >= $check2) {
					$return = true;
				}
			break;
			case 'less or equal':
				if($check1 > $check2) {
					$return = true;
				}
			break;
			case 'equal to':
				if($check1 == $check2) {
					$return = true;
				}
			break;
			case 'not equal':
				if($check1 != $check2) {
					$return = true;
				}
				break;
		}
		return $return;
	}

	function custom($check, $regex = null) {
		if (is_array($check)) {
			$this->_extract($check);
		} else {
			$this->check = $check;
			$this->regex = $regex;
		}
		return $this->_check();
	}

	function date($check) {

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
}
?>