<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/3.0/en/development/errors.html#error-exception-configuration
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Exception;

use Cake\Core\Exception\Exception;

/**
 * Exception class for Console libraries. This exception will be thrown from Console library
 * classes when they encounter an error.
 */
class ConsoleException extends Exception
{
}
