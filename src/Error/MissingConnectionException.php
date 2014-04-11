<?php
/**
 * MissingConnectionException class
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
 * @since         3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Error;

/**
 * Used when no connections can be found.
 *
 */
class MissingConnectionException extends Exception {

	protected $_messageTemplate = 'Database connection "%s" is missing, or could not be created.';

	public function __construct($message, $code = 500) {
		if (is_array($message)) {
			$message += array('enabled' => true);
		}
		parent::__construct($message, $code);
	}

}
