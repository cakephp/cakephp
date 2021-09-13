<?php
declare(strict_types=1);

/**
 * ErrorHandler class
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error;

use Cake\Core\App;
use Cake\Http\ResponseEmitter;
use Cake\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

/**
 * Error Handler provides basic error and exception handling for your application. It captures and
 * handles all unhandled exceptions and errors. Displays helpful framework errors when debug mode is on.
 *
 * ### Uncaught exceptions
 *
 * When debug mode is off a ExceptionRenderer will render 404 or 500 errors. If an uncaught exception is thrown
 * and it is a type that ExceptionRenderer does not know about it will be treated as a 500 error.
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
 * errors will be logged to Log. You can control which errors are logged by setting
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
 * @see \Cake\Error\ExceptionRenderer for more information on how to customize exception rendering.
 */
class ErrorHandler extends BaseErrorHandler
{
    /**
     * Constructor
     *
     * @param array<string, mixed> $config The options for error handling.
     */
    public function __construct(array $config = [])
    {
        $config += [
            'exceptionRenderer' => ExceptionRenderer::class,
        ];

        $this->setConfig($config);
    }

    /**
     * Display an error.
     *
     * Template method of BaseErrorHandler.
     *
     * @param array $error An array of error data.
     * @param bool $debug Whether the app is in debug mode.
     * @return void
     */
    protected function _displayError(array $error, bool $debug): void
    {
        if (!$debug) {
            return;
        }
        Debugger::getInstance()->outputError($error);
    }

    /**
     * Displays an exception response body.
     *
     * @param \Throwable $exception The exception to display.
     * @return void
     * @throws \Exception When the chosen exception renderer is invalid.
     */
    protected function _displayException(Throwable $exception): void
    {
        try {
            $renderer = $this->getRenderer(
                $exception,
                Router::getRequest()
            );
            $response = $renderer->render();
            $this->_sendResponse($response);
        } catch (Throwable $exception) {
            $this->_logInternalError($exception);
        }
    }

    /**
     * Get a renderer instance.
     *
     * @param \Throwable $exception The exception being rendered.
     * @param \Psr\Http\Message\ServerRequestInterface|null $request The request.
     * @return \Cake\Error\ExceptionRendererInterface The exception renderer.
     * @throws \RuntimeException When the renderer class cannot be found.
     */
    public function getRenderer(
        Throwable $exception,
        ?ServerRequestInterface $request = null
    ): ExceptionRendererInterface {
        $renderer = $this->_config['exceptionRenderer'];

        if (is_string($renderer)) {
            /** @var class-string<\Cake\Error\ExceptionRendererInterface>|null $class */
            $class = App::className($renderer, 'Error');
            if (!$class) {
                throw new RuntimeException(sprintf(
                    "The '%s' renderer class could not be found.",
                    $renderer
                ));
            }

            return new $class($exception, $request);
        }

        /** @var callable $factory */
        $factory = $renderer;

        return $factory($exception, $request);
    }

    /**
     * Log internal errors.
     *
     * @param \Throwable $exception Exception.
     * @return void
     */
    protected function _logInternalError(Throwable $exception): void
    {
        // Disable trace for internal errors.
        $this->_config['trace'] = false;
        $message = sprintf(
            "[%s] %s (%s:%s)\n%s", // Keeping same message format
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        trigger_error($message, E_USER_ERROR);
    }

    /**
     * Method that can be easily stubbed in testing.
     *
     * @param \Cake\Http\Response|string $response Either the message or response object.
     * @return void
     */
    protected function _sendResponse($response): void
    {
        if (is_string($response)) {
            echo $response;

            return;
        }

        $emitter = new ResponseEmitter();
        $emitter->emit($response);
    }
}
