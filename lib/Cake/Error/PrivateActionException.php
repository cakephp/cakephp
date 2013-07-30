<?php
/**
 * PrivateActionException class
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
 * @package       Cake.Error
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Error;

/**
 * Private Action exception - used when a controller action
 * starts with a  `_`.
 *
 * @package       Cake.Error
 */
class PrivateActionException extends Exception {

	protected $_messageTemplate = 'Private Action %s::%s() is not directly accessible.';

	public function __construct($message, $code = 404, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}

}
