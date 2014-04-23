<?php
/**
 * MissingTableClassException class
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2013, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Error;

use Cake\Error\Exception;

/**
 * Exception raised when a Table could not be found.
 *
 */
class MissingTableClassException extends Exception {

	protected $_messageTemplate = 'Table class %s could not be found.';

}
