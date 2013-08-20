<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace App\Config;

use Cake\Error\ErrorHandler;
use Cake\Console\ConsoleErrorHandler;

/**
 * Configure the Error and Exception handlers used by your application.
 *
 * By default errors are displayed using Debugger, when debug > 0 and logged by
 * Cake\Log\Log when debug = 0.
 *
 * In CLI environments exceptions will be printed to stderr with a backtrace.
 * In web environments an HTML page will be displayed for the exception.
 * While debug > 0, framework errors like Missing Controller will be displayed.
 * When debug = 0, framework errors will be coerced into generic HTTP errors.
 *
 * Options:
 *
 * - `errorLevel` - int - The level of errors you are interested in capturing.
 * - `trace` - boolean - Whether or not backtraces should be included in
 *   logged errors/exceptions.
 * - `log` - boolean - Whether or not you want exceptions logged.
 * - `exceptionRenderer` - string - The class responsible for rendering
 *   uncaught exceptions.  If you choose a custom class you should place
 *   the file for that class in app/Lib/Error. This class needs to implement a render method.
 * - `skipLog` - array - List of exceptions to skip for logging. Exceptions that
 *   extend one of the listed exceptions will also be skipped for logging.
 *   Example: `'skipLog' => array('Cake\Error\NotFoundException', 'Cake\Error\UnauthorizedException')`
 *
 * @see ErrorHandler for more information on error handling and configuration.
 */
$options = [
	'errorLevel' => E_ALL & ~E_DEPRECATED,
	'exceptionRenderer' => 'Cake\Error\ExceptionRenderer',
	'skipLog' => [],
	'log' => true,
	'trace' => true,
];

if (php_sapi_name() == 'cli') {
	$errorHandler = new ConsoleErrorHandler($options);
} else {
	$errorHandler = new ErrorHandler($options);
}
$errorHandler->register();
