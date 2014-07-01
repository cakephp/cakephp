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
namespace Cake\Routing\Error;

use Cake\Error\Exception;

/**
 * Exception raised when a URL cannot be reverse routed
 * or when a URL cannot be parsed.
 */
class MissingRouteException extends Exception {

	protected $_messageTemplate = 'A route matching "%s" could not be found.';

}
