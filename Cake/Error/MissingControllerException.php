<?php
/**
 * MissingControllerException class
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Error;

/**
 * Missing Controller exception - used when a controller
 * cannot be found.
 *
 */
class MissingControllerException extends Exception {

	protected $_messageTemplate = 'Controller class %s could not be found.';

	public function __construct($message, $code = 404) {
		parent::__construct($message, $code);
	}

}
