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
 * @package       cake.libs.error
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Parent class for all of the HTTP related exceptions in CakePHP.
 * All HTTP status/error related exceptions should extend this class so 
 * catch blocks can be specifically typed.
 *
 * @package cake.libs
 */
class HttpException extends RuntimeException { }

/**
 * Represents an HTTP 400 error.
 *
 * @package cake.libs
 */
class BadRequestException extends HttpException {
/**
 * Constructor
 *
 * @param string $message If no message is given 'Bad Request' will be the message
 * @param string $code Status code, defaults to 401
 */
	public function __construct($message = null, $code = 400) {
		if (empty($message)) {
			$message = 'Bad Request';
		}
		parent::__construct($message, $code);
	}
}

/**
 * Represents an HTTP 401 error.
 *
 * @package cake.libs
 */
class UnauthorizedException extends HttpException {
/**
 * Constructor
 *
 * @param string $message If no message is given 'Unauthorized' will be the message
 * @param string $code Status code, defaults to 401
 */
	public function __construct($message = null, $code = 401) {
		if (empty($message)) {
			$message = 'Unauthorized';
		}
		parent::__construct($message, $code);
	}
}

/**
 * Represents an HTTP 403 error.
 *
 * @package cake.libs
 */
class ForbiddenException extends HttpException {
/**
 * Constructor
 *
 * @param string $message If no message is given 'Forbidden' will be the message
 * @param string $code Status code, defaults to 401
 */
	public function __construct($message = null, $code = 403) {
		if (empty($message)) {
			$message = 'Forbidden';
		}
		parent::__construct($message, $code);
	}
}

/**
 * Represents an HTTP 404 error.
 *
 * @package cake.libs
 */
class NotFoundException extends HttpException {
/**
 * Constructor
 *
 * @param string $message If no message is given 'Not Found' will be the message
 * @param string $code Status code, defaults to 404
 */
	public function __construct($message = null, $code = 404) {
		if (empty($message)) {
			$message = 'Not Found';
		}
		parent::__construct($message, $code);
	}
}

/**
 * Represents an HTTP 405 error.
 *
 * @package cake.libs
 */
class MethodNotAllowedException extends HttpException {
/**
 * Constructor
 *
 * @param string $message If no message is given 'Method Not Allowed' will be the message
 * @param string $code Status code, defaults to 401
 */
	public function __construct($message = null, $code = 405) {
		if (empty($message)) {
			$message = 'Method Not Allowed';
		}
		parent::__construct($message, $code);
	}
}

/**
 * Represents an HTTP 500 error.
 *
 * @package cake.libs
 */
class InternalErrorException extends HttpException {
/**
 * Constructor
 *
 * @param string $message If no message is given 'Not Found' will be the message
 * @param string $code Status code, defaults to 404
 */
	public function __construct($message = null, $code = 500) {
		if (empty($message)) {
			$message = 'Internal Server Error';
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
 * @param mixed $message Either the string of the error message, or an array of attributes
 *   that are made available in the view, and sprintf()'d into CakeException::$_messageTemplate
 * @param string $code The code of the error, is also the HTTP status code for the error.
 */
	public function __construct($message, $code = 500) {
		if (is_array($message)) {
			$this->_attributes = $message;
			$message = __($this->_messageTemplate, $message);
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

/**
 * Missing Controller exception - used when a controller 
 * cannot be found.
 *
 * @package cake.libs
 */
class MissingControllerException extends CakeException { 
	protected $_messageTemplate = 'Controller class %s could not be found.';

	public function __construct($message, $code = 404) {
		parent::__construct($message, $code);
	}
}

/**
 * Missing Action exception - used when a controller action 
 * cannot be found.
 *
 * @package cake.libs
 */
class MissingActionException extends CakeException { 
	protected $_messageTemplate = 'Action %s::%s() could not be found.';

	public function __construct($message, $code = 404) {
		parent::__construct($message, $code);
	}
}
/**
 * Private Action exception - used when a controller action 
 * is protected, or starts with a `_`.
 *
 * @package cake.libs
 */
class PrivateActionException extends CakeException { 
	protected $_messageTemplate = 'Private Action %s::%s() is not directly accessible.';

	public function __construct($message, $code = 404, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}

/**
 * Used when a Component file cannot be found.
 *
 * @package cake.libs
 */
class MissingComponentFileException extends CakeException { 
	protected $_messageTemplate = 'Component File  "%s" is missing.';
}

/**
 * Used when a Component class cannot be found.
 *
 * @package cake.libs
 */
class MissingComponentClassException extends CakeException { 
	protected $_messageTemplate = 'Component class "%s" is missing.';
}

/**
 * Used when a Behavior file cannot be found.
 *
 * @package cake.libs
 */
class MissingBehaviorFileException extends CakeException { }

/**
 * Used when a Behavior class cannot be found.
 *
 * @package cake.libs
 */
class MissingBehaviorClassException extends CakeException { }

/**
 * Used when a view file cannot be found.
 *
 * @package cake.libs
 */
class MissingViewException extends CakeException { 
	protected $_messageTemplate = 'View file "%s" is missing.';
}

/**
 * Used when a layout file cannot be found.
 *
 * @package cake.libs
 */
class MissingLayoutException extends CakeException { 
	protected $_messageTemplate = 'Layout file "%s" is missing.';
}

/**
 * Used when a helper file cannot be found.
 *
 * @package cake.libs
 */
class MissingHelperFileException extends CakeException { 
	protected $_messageTemplate = 'Helper File "%s" is missing.';
}

/**
 * Used when a helper class cannot be found.
 *
 * @package cake.libs
 */
class MissingHelperClassException extends CakeException { 
	protected $_messageTemplate = 'Helper class "%s" is missing.';
}


/**
 * Runtime Exceptions for ConnectionManager
 *
 * @package cake.libs
 */
class MissingDatabaseException extends CakeException {
	protected $_messageTemplate = 'Database connection "%s" could not be found.';
}

/**
 * Used when no connections can be found.
 *
 * @package cake.libs
 */
class MissingConnectionException extends CakeException {
	protected $_messageTemplate = 'Database connection "%s" is missing.';
}

/**
 * Used when a Task file cannot be found.
 *
 * @package cake.libs
 */
class MissingTaskFileException extends CakeException { 
	protected $_messageTemplate = 'Task file "%s" is missing.';
}

/**
 * Used when a Task class cannot be found.
 *
 * @package cake.libs
 */
class MissingTaskClassException extends CakeException { 
	protected $_messageTemplate = 'Task class "%s" is missing.';
}

/**
 * Used when a shell method cannot be found.
 *
 * @package cake.libs
 */
class MissingShellMethodException extends CakeException { 
	protected $_messageTemplate = "Unknown command %1\$s %2\$s.\nFor usage try `cake %1\$s --help`";
}

/**
 * Used when a shell class cannot be found.
 *
 * @package cake.libs
 */
class MissingShellClassException extends CakeException { 
	protected $_messageTemplate = "Shell class %s could not be loaded.";
}

/**
 * Used when a shell class cannot be found.
 *
 * @package cake.libs
 */
class MissingShellFileException extends CakeException { 
	protected $_messageTemplate = "Shell file %s could not be loaded.";
}

/**
 * Exception class to be thrown when a database table is not found in the datasource
 *
 * @package cake.libs
 */
class MissingTableException extends CakeException {
	protected $_messageTemplate = 'Database table %s for model %s was not found.';
}

/**
 * Exception Raised when a Model could not be found.
 *
 * @package cake.libs
 */
class MissingModelException extends CakeException {
	protected $_messageTemplate = 'Model %s could not be found.';
}


/**
 * Exception class for Cache.  This exception will be thrown from Cache when it
 * encounters an error.
 *
 * @package cake.libs
 */
class CacheException extends CakeException { }

/**
 * Exception class for Router.  This exception will be thrown from Router when it
 * encounters an error.
 *
 * @package cake.libs
 */
class RouterException extends CakeException { }

/**
 * Exception class for CakeLog.  This exception will be thrown from CakeLog when it
 * encounters an error.
 *
 * @package cake.libs
 */
class CakeLogException extends CakeException { }

/**
 * Exception class for CakeSession.  This exception will be thrown from CakeSession when it
 * encounters an error.
 *
 * @package cake.libs
 */
class CakeSessionException extends CakeException { }

/**
 * Exception class for Configure.  This exception will be thrown from Configure when it
 * encounters an error.
 *
 * @package cake.libs
 */
class ConfigureException extends CakeException { }

/**
 * Exception class for Socket. This exception will be thrown from CakeSocket, HttpSocket and HttpResponse when it
 * encounters an error.
 *
 * @package cake.libs
 */
class SocketException extends CakeException { }

/**
 * Exception class for Xml.  This exception will be thrown from Xml when it
 * encounters an error.
 *
 * @package cake.libs
 */
class XmlException extends CakeException { }

/**
 * Exception class for Console libraries.  This exception will be thrown from Console library
 * classes when they encounter an error.
 *
 * @package cake.libs
 */
class ConsoleException extends CakeException { }

