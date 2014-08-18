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
namespace Cake\Controller\Error;

use Cake\Error\Exception;

/**
 * Missing Controller exception - used when a controller
 * cannot be found.
 *
 */
class MissingControllerException extends Exception {

/**
 * {@inheritDoc}
 */
	protected $_messageTemplate = 'Controller class %s could not be found.';

/**
 * {@inheritDoc}
 */
	public function __construct($message, $code = 404) {
		parent::__construct($message, $code);
	}

}
