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
use Cake\Core\InstanceConfigTrait;
use Cake\Error\ErrorHandler;
use Cake\Error\ExceptionRenderer;
use Cake\Http\Response;
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
     * - `exceptionRenderer` The renderer instance or class name to use or a callable factory
     *   which returns a \Cake\Error\ExceptionRendererInterface instance.
     *   Defaults to \Cake\Error\ExceptionRenderer
     *
     * @var array
     */
    protected $_defaultConfig = [
        'skipLog' => [],
        'log' => true,
        'trace' => false,
        'exceptionRenderer' => ExceptionRenderer::class,
    ];

    /**
     * Error handler instance.
     *
     * @var \Cake\Error\ErrorHandler|null
     */
    protected $errorHandler;

    /**
     * Constructor
     *
     * @param \Cake\Error\ErrorHandler|null $errorHandler The error handler.
     * @param array $config Configuration options for error handler.
     *   These options will be ignored if an ErrorHandler instance is passed as
     *   first argument.
     */
    public function __construct($errorHandler = null, array $config = [])
    {
        $this->setConfig($config);

        if ($errorHandler !== null) {
            $this->errorHandler = $errorHandler;
        }
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
        $errorHandler = $this->getErrorHandler();

        try {
            $response = $errorHandler->getRenderer($exception, $request)->render();
            $errorHandler->logException($exception, $request);
        } catch (Throwable $internalException) {
            $errorHandler->logException($internalException, $request);
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
     * Get a error handler instance
     *
     * @return \Cake\Error\ErrorHandler The error handler.
     */
    protected function getErrorHandler(): ErrorHandler
    {
        if ($this->errorHandler === null) {
            /** @var string $className */
            $className = App::className('ErrorHandler', 'Error');
            /** @var \Cake\Error\ErrorHandler $this->errorHandler */
            $this->errorHandler = new $className($this->getConfig());
        }

        return $this->errorHandler;
    }
}
