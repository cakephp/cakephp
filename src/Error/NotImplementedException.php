<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error;

/**
 * Not Implemented Exception - used when an API method is not implemented
 *
 */
class NotImplementedException extends Exception {

	protected $_messageTemplate = '%s is not implemented.';

	public function __construct($message, $code = 501) {
		parent::__construct($message, $code);
	}

}
