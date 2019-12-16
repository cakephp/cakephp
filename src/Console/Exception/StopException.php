<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/3/en/development/errors.html#error-exception-configuration
 * @since         3.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Exception;

/**
 * Exception class for halting errors in console tasks
 *
 * @see \Cake\Console\Shell::_stop()
 * @see \Cake\Console\Shell::error()
 * @see \Cake\Console\Command::abort()
 */
class StopException extends ConsoleException
{
}
