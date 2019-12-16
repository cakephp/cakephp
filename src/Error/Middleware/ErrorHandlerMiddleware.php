<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error\Middleware;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception as CakeException;
use Cake\Core\InstanceConfigTrait;
use Cake\Error\ExceptionRenderer;
use Cake\Error\PHP7ErrorException;
use Cake\Log\Log;
use Error;
use Exception;
use Throwable;

/**
 * Error handling middleware.
 *
 * Traps exceptions and converts them into HTML or content-type appropriate
 * error pages using the CakePHP ExceptionRenderer.
 */
class ErrorHandlerMiddleware
{
    use InstanceConfigTrait;

    /**
     * Default configuration values.
     *
     * - `log` Enable logging of exceptions.
     * - `skipLog` List of exceptions to skip logging. Exceptions that
     *   extend one of the listed exceptions will also not be logged. Example:
     *
     *   ```
     *   'skipLog' => ['Cake\Error\NotFoundException', 'Cake\Error\UnauthorizedException']
     *   ```
     *
     * - `trace` Should error logs include stack traces?
     *
     * @var array
     */
    protected $_defaultConfig = [
        'skipLog' => [],
        'log' => true,
        'trace' => false,
    ];

    /**
     * Exception render.
     *
     * @var \Cake\Error\ExceptionRendererInterface|callable|string|null
     */
    protected $exceptionRenderer;

    /**
     * Constructor
     *
     * @param string|callable|null $exceptionRenderer The renderer or class name
     *   to use or a callable factory. If null, Configure::read('Error.exceptionRenderer')
     *   will be used.
     * @param array $config Configuration options to use. If empty, `Configure::read('Error')`
     *   will be used.
     */
    public function __construct($exceptionRenderer = null, array $config = [])
    {
        if ($exceptionRenderer) {
            $this->exceptionRenderer = $exceptionRenderer;
        }

        $config = $config ?: Configure::read('Error');
        $this->setConfig($config);
    }

    /**
     * Wrap the remaining middleware with error handling.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke($request, $response, $next)
    {
        try {
            return $next($request, $response);
        } catch (Throwable $exception) {
            return $this->handleException($exception, $request, $response);
        } catch (Exception $exception) {
            return $this->handleException($exception, $request, $response);
        }
    }

    /**
     * Handle an exception and generate an error response
     *
     * @param \Exception $exception The exception to handle.
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function handleException($exception, $request, $response)
    {
        $renderer = $this->getRenderer($exception, $request);
        try {
            $res = $renderer->render();
            $this->logException($request, $exception);

            return $res;
        } catch (Throwable $exception) {
            $this->logException($request, $exception);
            $response = $this->handleInternalError($response);
        } catch (Exception $exception) {
            $this->logException($request, $exception);
            $response = $this->handleInternalError($response);
        }

        return $response;
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response The response
     *
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    protected function handleInternalError($response)
    {
        $body = $response->getBody();
        $body->write('An Internal Server Error Occurred');

        return $response->withStatus(500)
            ->withBody($body);
    }

    /**
     * Get a renderer instance
     *
     * @param \Exception $exception The exception being rendered.
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return \Cake\Error\ExceptionRendererInterface The exception renderer.
     * @throws \Exception When the renderer class cannot be found.
     */
    protected function getRenderer($exception, $request)
    {
        if (!$this->exceptionRenderer) {
            $this->exceptionRenderer = $this->getConfig('exceptionRenderer') ?: ExceptionRenderer::class;
        }

        // For PHP5 backwards compatibility
        if ($exception instanceof Error) {
            $exception = new PHP7ErrorException($exception);
        }

        if (is_string($this->exceptionRenderer)) {
            $class = App::className($this->exceptionRenderer, 'Error');
            if (!$class) {
                throw new Exception(sprintf(
                    "The '%s' renderer class could not be found.",
                    $this->exceptionRenderer
                ));
            }

            return new $class($exception, $request);
        }
        $factory = $this->exceptionRenderer;

        return $factory($exception, $request);
    }

    /**
     * Log an error for the exception if applicable.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The current request.
     * @param \Exception $exception The exception to log a message for.
     * @return void
     */
    protected function logException($request, $exception)
    {
        if (!$this->getConfig('log')) {
            return;
        }

        foreach ((array)$this->getConfig('skipLog') as $class) {
            if ($exception instanceof $class) {
                return;
            }
        }

        Log::error($this->getMessage($request, $exception));
    }

    /**
     * Generate the error log message.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The current request.
     * @param \Exception $exception The exception to log a message for.
     * @return string Error message
     */
    protected function getMessage($request, $exception)
    {
        $message = $this->getMessageForException($exception);

        $message .= "\nRequest URL: " . $request->getRequestTarget();
        $referer = $request->getHeaderLine('Referer');
        if ($referer) {
            $message .= "\nReferer URL: " . $referer;
        }
        $message .= "\n\n";

        return $message;
    }

    /**
     * Generate the message for the exception
     *
     * @param \Exception $exception The exception to log a message for.
     * @param bool $isPrevious False for original exception, true for previous
     * @return string Error message
     */
    protected function getMessageForException($exception, $isPrevious = false)
    {
        $message = sprintf(
            '%s[%s] %s (%s:%s)',
            $isPrevious ? "\nCaused by: " : '',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        $debug = Configure::read('debug');

        if ($debug && $exception instanceof CakeException) {
            $attributes = $exception->getAttributes();
            if ($attributes) {
                $message .= "\nException Attributes: " . var_export($exception->getAttributes(), true);
            }
        }

        if ($this->getConfig('trace')) {
            $message .= "\n" . $exception->getTraceAsString();
        }

        $previous = $exception->getPrevious();
        if ($previous) {
            $message .= $this->getMessageForException($previous, true);
        }

        return $message;
    }
}
