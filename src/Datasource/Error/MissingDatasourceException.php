<?php
/**
 * MissingDatasourceException class
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
namespace Cake\Datasource\Error;

use Cake\Error\Exception;

/**
 * Used when a datasource cannot be found.
 *
 */
class MissingDatasourceException extends Exception {

	protected $_messageTemplate = 'Datasource class %s could not be found. %s';

}
