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
/**
 * CakeException is used a base class for CakePHP's internal exceptions.
 * In general framework errors are interpreted as 500 code errors.
 *
 * @package cake.libs
 */
class CakeException extends RuntimeException { 
	public function __construct($message, $code = 500, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}


class Error404Exception extends RuntimeException {
	public function __construct($message, $code = 404, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}
class Error500Exception extends CakeException { }

/*
 * Exceptions used by Dispatcher
 */
class MissingControllerException extends Error404Exception { }
class MissingActionException extends Error404Exception { }
class PrivateActionException extends Error404Exception { }

/**
 * Exceptions used by the ComponentCollection.
 */
class MissingComponentFileException extends CakeException { }
class MissingComponentClassException extends CakeException { }

/**
 * Runtime Exceptions for behaviors
 */
class MissingBehaviorFileException extends CakeException { }
class MissingBehaviorClassException extends CakeException { }

/**
 * Runtime Exceptions for Views
 */
class MissingViewException extends CakeException { }
class MissingLayoutException extends CakeException { }

/**
 * Runtime Exceptions for ConnectionManager
 */
class MissingDatabaseException extends CakeException {}
class MissingConnectionException extends CakeException {}

/**
 * Exceptions used by the TaskCollection.
 */
class MissingTaskFileException extends CakeException { }
class MissingTaskClassException extends CakeException { }

/**
 * Exception class to be thrown when a database table is not found in the datasource
 *
 */
class MissingTableException extends CakeException {
/**
 * The name of the model wanting to load the database table
 *
 * @var string
 */
	protected $model;
/**
 * The name of the missing table
 *
 * @var string
 */
	protected $table;

/**
 * Exception costructor
 *
 * @param string $model The name of the model wanting to load the database table
 * @param string $table The name of the missing table
 * @return void
 */
	public function __construct($model, $table) {
		$this->model = $model;
		$this->$table = $table;
		$message = sprintf(__('Database table %s for model %s was not found.'), $table, $model);
		parent::__construct($message);
	}

/**
 * Returns the name of the model wanting to load the database table
 *
 * @return string
 */
	public function getModel() {
		return $this->model;
	}

/**
 * Returns the name of the missing table
 *
 * @return string
 */
	public function getTable() {
		return $this->table;
	}
}