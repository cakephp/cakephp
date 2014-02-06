<?php
/**
 * PrivateActionException class
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
 * Private Action exception - used when a controller action
 * starts with a  `_`.
 */
class PrivateActionException extends Exception {

/**
 * Message template.
 *
 * @var string
 */
	protected $_messageTemplate = 'Private Action %s::%s() is not directly accessible.';

/**
 * Constructor
 *
 * @param string $message Excception message
 * @param integer $code Exception code
 * @param \Exception $previous Previous exception
 */
	public function __construct($message, $code = 404, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}

}
