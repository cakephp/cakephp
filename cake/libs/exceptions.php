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

class Error404Exception extends RuntimeException { }
class Error500Exception extends RuntimeException { }

/*
 * Exceptions used by Dispatcher
 */
class MissingControllerException extends RuntimeException { }
class MissingActionException extends RuntimeException { }
class PrivateActionException extends RuntimeException { }

/**
 * Exceptions used by the ComponentCollection.
 */
class MissingComponentFileException extends RuntimeException { }
class MissingComponentClassException extends RuntimeException { }

/**
 * Runtime Exceptions for behaviors
 */
class MissingBehaviorFileException extends RuntimeException { }
class MissingBehaviorClassException extends RuntimeException { }

/**
 * Runtime Exceptions for Views
 */
class MissingViewException extends RuntimeException { }
class MissingLayoutException extends RuntimeException { }

/**
 * Runtime Exceptions for ConnectionManager
 */
class MissingDatabaseException extends RuntimeException {}
class MissingConnectionException extends RuntimeException {}

/**
 * Exceptions used by the TaskCollection.
 */
class MissingTaskFileException extends RuntimeException { }
class MissingTaskClassException extends RuntimeException { }

/**
 * Exception class to be thrown when a database table is not found in the datasource
 *
 */
class MissingTableException extends RuntimeException {
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