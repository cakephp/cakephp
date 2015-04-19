<?php
/**
 * ErrorHandler class
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.10.5
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error;

use Cake\Core\App;
use Cake\Error\Debugger;
use Exception;

/**
 * Error Handler provides basic error and exception handling for your application. It captures and
 * handles all unhandled exceptions and errors. Displays helpful framework errors when debug > 1.
 *
 * ### Uncaught exceptions
 *
 * When debug < 1 a CakeException will render 404 or 500 errors. If an uncaught exception is thrown
 * and it is a type that ErrorHandler does not know about it will be treated as a 500 error.
 *
 * ### Implementing application specific exception handling
 *
 * You can implement application specific exception handling in one of a few ways. Each approach
 * gives you different amounts of control over the exception handling process.
 *
 * - Modify config/error.php and setup custom exception handling.
 * - Use the `exceptionRenderer` option to inject an Exception renderer. This will
 *   let you keep the existing handling logic but override the rendering logic.
 *
 * #### Create your own Exception handler
 *
 * This gives you full control over the exception handling process. The class you choose should be
 * loaded in your config/error.php and registered as the default exception handler.
 *
 * #### Using a custom renderer with `exceptionRenderer`
 *
 * If you don't want to take control of the exception handling, but want to change how exceptions are
 * rendered you can use `exceptionRenderer` option to choose a class to render exception pages. By default
 * `Cake\Error\ExceptionRenderer` is used. Your custom exception renderer class should be placed in src/Error.
 *
 * Your custom renderer should expect an exception in its constructor, and implement a render method.
 * Failing to do so will cause additional errors.
 *
 * #### Logging exceptions
 *
 * Using the built-in exception handling, you can log all the exceptions
 * that are dealt with by ErrorHandler by setting `log` option to true in your config/error.php.
 * Enabling this will log every exception to Log and the configured loggers.
 *
 * ### PHP errors
 *
 * Error handler also provides the built in features for handling php errors (trigger_error).
 * While in debug mode, errors will be output to the screen using debugger. While in production mode,
 * errors will be logged to Log.  You can control which errors are logged by setting
 * `errorLevel` option in config/error.php.
 *
 * #### Logging errors
 *
 * When ErrorHandler is used for handling errors, you can enable error logging by setting the `log`
 * option to true. This will log all errors to the configured log handlers.
 *
 * #### Controlling what errors are logged/displayed
 *
 * You can control which errors are logged / displayed by ErrorHandler by setting `errorLevel`. Setting this
 * to one or a combination of a few of the E_* constants will only enable the specified errors:
 *
 * ```
 * $options['errorLevel'] = E_ALL & ~E_NOTICE;
 * ```
 *
 * Would enable handling for all non Notice errors.
 *
 * @see ExceptionRenderer for more information on how to customize exception rendering.
 */
class ErrorHandler extends BaseErrorHandler
{

    /**
     * Options to use for the Error handling.
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Constructor
     *
     * @param array $options The options for error handling.
     */
    public function __construct($options = [])
    {
        $defaults = [
            'log' => true,
            'trace' => false,
            'exceptionRenderer' => 'Cake\Error\ExceptionRenderer',
        ];
        $this->_options = $options + $defaults;
    }

    /**
     * Display an error.
     *
     * Template method of BaseErrorHandler.
     *
     * Only when debug > 2 will a formatted error be displayed.
     *
     * @param array $error An array of error data.
     * @param bool $debug Whether or not the app is in debug mode.
     * @return void
     */
    protected function _displayError($error, $debug)
    {
        if (!$debug) {
            return;
        }
        Debugger::getInstance()->outputError($error);
    }

    /**
     * Displays an exception response body.
     *
     * @param \Exception $exception The exception to display
     * @return void
     * @throws \Exception When the chosen exception renderer is invalid.
     */
    protected function _displayException($exception)
    {
        $renderer = App::className($this->_options['exceptionRenderer'], 'Error');
        try {
            if (!$renderer) {
                throw new Exception("$renderer is an invalid class.");
            }
            $error = new $renderer($exception);
            $response = $error->render();
            $this->_clearOutput();
            $this->_sendResponse($response);
        } catch (Exception $e) {
            // Disable trace for internal errors.
            $this->_options['trace'] = false;
            $message = sprintf(
                "[%s] %s\n%s", // Keeping same message format
                get_class($e),
                $e->getMessage(),
                $e->getTraceAsString()
            );
            trigger_error($message, E_USER_ERROR);
        }
    }

    /**
     * Clear output buffers so error pages display properly.
     *
     * Easily stubbed in testing.
     *
     * @return void
     */
    protected function _clearOutput()
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }

    /**
     * Method that can be easily stubbed in testing.
     *
     * @param string|\Cake\Network\Response $response Either the message or response object.
     * @return void
     */
    protected function _sendResponse($response)
    {
        if (is_string($response)) {
            echo $response;
            return;
        }
        $response->send();
    }
}
