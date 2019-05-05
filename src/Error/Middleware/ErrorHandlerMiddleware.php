<?php
declare(strict_types=1);
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
use Cake\Error\ExceptionRendererInterface;
use Cake\Http\Response;
use Cake\Log\Log;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Error handling middleware.
 *
 * Traps exceptions and converts them into HTML or content-type appropriate
 * error pages using the CakePHP ExceptionRenderer.
 */
class ErrorHandlerMiddleware implements MiddlewareInterface
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
     * @var \Cake\Error\ExceptionRendererInterface|callable|string
     */
    protected $exceptionRenderer;

    /**
     * Constructor
     *
     * @param string|callable|null $exceptionRenderer The renderer or class name
     *   to use or a callable factory. If null, value of 'exceptionRenderer' key
     *   from $config will be used with fallback to Cake\Error\ExceptionRenderer::class.
     * @param array $config Configuration options to use.
     *   will be used.
     */
    public function __construct($exceptionRenderer = null, array $config = [])
    {
        $this->setConfig($config);

        if ($exceptionRenderer) {
            $this->exceptionRenderer = $exceptionRenderer;

            return;
        }

        $this->exceptionRenderer = $this->getConfig('exceptionRenderer', ExceptionRenderer::class);
    }

    /**
     * Wrap the remaining middleware with error handling.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            return $this->handleException($exception, $request);
        }
    }

    /**
     * Handle an exception and generate an error response
     *
     * @param \Throwable $exception The exception to handle.
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function handleException(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $renderer = $this->getRenderer($exception, $request);
        try {
            $response = $renderer->render();
            $this->logException($request, $exception);
        } catch (Throwable $internalException) {
            $this->logException($request, $internalException);
            $response = $this->handleInternalError();
        }

        return $response;
    }

    /**
     * Handle internal errors.
     *
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    protected function handleInternalError(): ResponseInterface
    {
        $response = new Response(['body' => 'An Internal Server Error Occurred']);

        return $response->withStatus(500);
    }

    /**
     * Get a renderer instance
     *
     * @param \Throwable $exception The exception being rendered.
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return \Cake\Error\ExceptionRendererInterface The exception renderer.
     * @throws \Exception When the renderer class cannot be found.
     */
    protected function getRenderer(Throwable $exception, ServerRequestInterface $request): ExceptionRendererInterface
    {
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

        /** @var callable $factory */
        $factory = $this->exceptionRenderer;

        return $factory($exception, $request);
    }

    /**
     * Log an error for the exception if applicable.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The current request.
     * @param \Throwable $exception The exception to log a message for.
     * @return void
     */
    protected function logException(ServerRequestInterface $request, Throwable $exception): void
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
     * @param \Throwable $exception The exception to log a message for.
     * @return string Error message
     */
    protected function getMessage(ServerRequestInterface $request, Throwable $exception): string
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
     * @param \Throwable $exception The exception to log a message for.
     * @param bool $isPrevious False for original exception, true for previous
     * @return string Error message
     */
    protected function getMessageForException(Throwable $exception, bool $isPrevious = false): string
    {
        $message = sprintf(
            '%s[%s] %s',
            $isPrevious ? "\nCaused by: " : '',
            get_class($exception),
            $exception->getMessage()
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
