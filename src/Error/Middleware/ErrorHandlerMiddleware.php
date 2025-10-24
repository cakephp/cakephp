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
use Cake\Core\InstanceConfigTrait;
use Cake\Core\PluginApplicationInterface;
use Cake\Error\ExceptionTrap;
use Cake\Error\Renderer\WebExceptionRenderer;
use Cake\Event\EventDispatcherTrait;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\Routing\Router;
use Cake\Routing\RoutingApplicationInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use function Cake\Core\triggerWarning;

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
     * @use \Cake\Event\EventDispatcherTrait<\Cake\Error\ExceptionTrap>
     */
    use EventDispatcherTrait;

    /**
     * Default configuration values.
     *
     * Ignored if contructor is passed an ExceptionTrap instance.
     *
     * Configuration keys and values are shared with `ExceptionTrap`.
     * This class will pass its configuration onto the ExceptionTrap
     * class if you are using the array style constructor.
     *
     * @var array<string, mixed>
     * @see \Cake\Error\ExceptionTrap
     */
    protected array $_defaultConfig = [
        'exceptionRenderer' => WebExceptionRenderer::class,
    ];

    /**
     * ExceptionTrap instance
     *
     * @var \Cake\Error\ExceptionTrap|null
     */
    protected ?ExceptionTrap $exceptionTrap = null;

    /**
     * @var \Cake\Routing\RoutingApplicationInterface|null
     */
    protected ?RoutingApplicationInterface $app = null;

    /**
     * Constructor
     *
     * @param \Cake\Error\ExceptionTrap|array $config The error handler instance
     *  or config array.
     * @param \Cake\Routing\RoutingApplicationInterface|null $app Application instance.
     */
    public function __construct(ExceptionTrap|array $config = [], ?RoutingApplicationInterface $app = null)
    {
        $this->app = $app;

        if (Configure::read('debug')) {
            ini_set('zend.exception_ignore_args', '0');
        }

        if (is_array($config)) {
            $this->setConfig($config);

            return;
        }

        $this->exceptionTrap = $config;
    }

    /**
     * Wrap the remaining middleware with error handling.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (RedirectException $exception) {
            return $this->handleRedirect($exception);
        } catch (Throwable $exception) {
            return $this->handleException($exception, Router::getRequest() ?? $request);
        }
    }

    /**
     * Handle an exception and generate an error response
     *
     * @param \Throwable $exception The exception to handle.
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function handleException(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $this->loadRoutes();

        $trap = $this->getExceptionTrap();
        $trap->logException($exception, $request);

        $event = $this->dispatchEvent(
            'Exception.beforeRender',
            ['exception' => $exception, 'request' => $request],
            $trap,
        );

        $response = $event->getResult();
        if ($response === null) {
            $renderer = $trap->renderer($event->getData('exception'), $request);
        }

        try {
            $response ??= $renderer->render();
            if (is_string($response)) {
                return new Response(['body' => $response, 'status' => 500]);
            }

            return $response;
        } catch (Throwable $internalException) {
            $trap->logException($internalException, $request);

            return $this->handleInternalError();
        }
    }

    /**
     * Convert a redirect exception into a response.
     *
     * @param \Cake\Http\Exception\RedirectException $exception The exception to handle
     * @return \Psr\Http\Message\ResponseInterface Response created from the redirect.
     */
    public function handleRedirect(RedirectException $exception): ResponseInterface
    {
        return new RedirectResponse(
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getHeaders(),
        );
    }

    /**
     * Handle internal errors.
     *
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    protected function handleInternalError(): ResponseInterface
    {
        return new Response([
            'body' => 'An Internal Server Error Occurred',
            'status' => 500,
        ]);
    }

    /**
     * Get a exception trap instance
     *
     * @return \Cake\Error\ExceptionTrap The exception trap.
     */
    protected function getExceptionTrap(): ExceptionTrap
    {
        if ($this->exceptionTrap === null) {
            /** @var class-string<\Cake\Error\ExceptionTrap> $className */
            $className = App::className('ExceptionTrap', 'Error');
            $this->exceptionTrap = new $className($this->getConfig());
        }

        return $this->exceptionTrap;
    }

    /**
     * Ensure that the application's routes are loaded.
     *
     * @return void
     */
    protected function loadRoutes(): void
    {
        if (
            !($this->app instanceof RoutingApplicationInterface)
            || Router::routes()
        ) {
            return;
        }

        try {
            $builder = Router::createRouteBuilder('/');

            $this->app->routes($builder);
            if ($this->app instanceof PluginApplicationInterface) {
                $this->app->pluginRoutes($builder);
            }
        } catch (Throwable $e) {
            triggerWarning(sprintf(
                "Exception loading routes when rendering an error page: \n %s - %s",
                $e::class,
                $e->getMessage(),
            ));
        }
    }
}
