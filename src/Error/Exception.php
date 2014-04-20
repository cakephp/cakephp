<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html
 * @since         3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Error;

/**
 * Exception is used a base class for CakePHP's internal exceptions.
 * In general framework errors are interpreted as 500 code errors.
 *
 */
class Exception extends BaseException {

/**
 * Array of attributes that are passed in from the constructor, and
 * made available in the view when a development error is displayed.
 *
 * @var array
 */
	protected $_attributes = array();

/**
 * Template string that has attributes sprintf()'ed into it.
 *
 * @var string
 */
	protected $_messageTemplate = '';

/**
 * Constructor.
 *
 * Allows you to create exceptions that are treated as framework errors and disabled
 * when debug = 0.
 *
 * @param string|array $message Either the string of the error message, or an array of attributes
 *   that are made available in the view, and sprintf()'d into Exception::$_messageTemplate
 * @param int $code The code of the error, is also the HTTP status code for the error.
 */
	public function __construct($message, $code = 500) {
		if (is_array($message)) {
			$this->_attributes = $message;
			$message = vsprintf($this->_messageTemplate, $message);
		}
		parent::__construct($message, $code);
	}

/**
 * Get the passed in attributes
 *
 * @return array
 */
	public function getAttributes() {
		return $this->_attributes;
	}

}
