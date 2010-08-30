<?php
/**
 * Exceptions file.  Contains the various exceptions CakePHP will throw until they are
 * moved into their permanent location.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */


class Error404Exception extends RuntimeException {
	public function __construct($message, $code = 404) {
		if (empty($message)) {
			$message = __('Not Found');
		}
		parent::__construct($message, $code);
	}
}
class Error500Exception extends CakeException { 
	public function __construct($message, $code = 500) {
		if (empty($message)) {
			$message = __('Internal Server Error');
		}
		parent::__construct($message, $code);
	}
}

/**
 * CakeException is used a base class for CakePHP's internal exceptions.
 * In general framework errors are interpreted as 500 code errors.
 *
 * @package cake.libs
 */
class CakeException extends RuntimeException {

	protected $_attributes = array();

	protected $_messageTemplate = '';

	public function __construct($message, $code = 500) {
		if (is_array($message)) {
			$this->_attributes = $message;
			$message = vsprintf(__($this->_messageTemplate), $message);
		}
		parent::__construct($message, $code);
	}
	
	public function getAttributes() {
		return $this->_attributes;
	}
}

/*
 * Exceptions used by Dispatcher
 */
class MissingControllerException extends CakeException { 
	protected $_messageTemplate = 'Controller class %s could not be found.';

	public function __construct($message, $code = 404) {
		parent::__construct($message, $code);
	}
}
class MissingActionException extends CakeException { 
	protected $_messageTemplate = 'Action %s::%s() could not be found.';

	public function __construct($message, $code = 404) {
		parent::__construct($message, $code);
	}
}
class PrivateActionException extends CakeException { 
	protected $_messageTemplate = 'Private Action %s::%s() is not directly accessible.';

	public function __construct($message, $code = 404, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}

/**
 * Exceptions used by the ComponentCollection.
 */
class MissingComponentFileException extends CakeException { 
	protected $_messageTemplate = 'Component File  "%s" is missing.';
}
class MissingComponentClassException extends CakeException { 
	protected $_messageTemplate = 'Component class "%s" is missing.';
}

/**
 * Runtime Exceptions for behaviors
 */
class MissingBehaviorFileException extends CakeException { }
class MissingBehaviorClassException extends CakeException { }

/**
 * Runtime Exceptions for Views
 */
class MissingViewException extends CakeException { 
	protected $_messageTemplate = 'View file "%s" is missing.';
}
class MissingLayoutException extends CakeException { 
	protected $_messageTemplate = 'Layout file "%s" is missing.';
}

/**
 * Exceptions used by the HelperCollection.
 */
class MissingHelperFileException extends CakeException { 
	protected $_messageTemplate = 'Helper File "%s" is missing.';
}
class MissingHelperClassException extends CakeException { 
	protected $_messageTemplate = 'Helper class "%s" is missing.';
}


/**
 * Runtime Exceptions for ConnectionManager
 */
class MissingDatabaseException extends CakeException {
	protected $_messageTemplate = 'Database connection "%s" could not be found.';
}
class MissingConnectionException extends CakeException {
	protected $_messageTemplate = 'Database connection "%s" is missing.';
}

/**
 * Exceptions used by the TaskCollection.
 */
class MissingTaskFileException extends CakeException { 
	protected $_messageTemplate = 'Task file "%s" is missing.';
}
class MissingTaskClassException extends CakeException { 
	protected $_messageTemplate = 'Task class "%s" is missing.';
}

/**
 * Exception class to be thrown when a database table is not found in the datasource
 *
 */
class MissingTableException extends CakeException {
	protected $_messageTemplate = 'Database table %s for model %s was not found.';
}
