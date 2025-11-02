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
namespace Cake\Http;

use Cake\Core\ContainerApplicationInterface;
use Cake\Core\HttpApplicationInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventManager;
use Cake\Event\EventManagerInterface;
use Cake\Routing\Router;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Runs an application invoking all the PSR7 middleware and the registered application.
 *
 * @implements \Cake\Event\EventDispatcherInterface<\Cake\Core\HttpApplicationInterface>
 */
class Server implements EventDispatcherInterface
{
    /**
     * @use \Cake\Event\EventDispatcherTrait<\Cake\Core\HttpApplicationInterface>
     */
    use EventDispatcherTrait;

    /**
     * @var \Cake\Core\HttpApplicationInterface
     */
    protected HttpApplicationInterface $app;

    /**
     * Constructor
     *
     * @param \Cake\Core\HttpApplicationInterface $app The application to use.
     * @param \Cake\Http\Runner $runner Application runner.
     */
    public function __construct(HttpApplicationInterface $app, protected Runner $runner = new Runner())
    {
        $this->app = $app;
    }

    /**
     * Run the request/response through the Application and its middleware.
     *
     * This will invoke the following methods:
     *
     * - App->bootstrap() - Perform any bootstrapping logic for your application here.
     * - App->middleware() - Attach any application middleware here.
     * - Trigger the 'Server.buildMiddleware' event. You can use this to modify the
     *   from event listeners.
     * - Run the middleware queue including the application.
     *
     * @param \Psr\Http\Message\ServerRequestInterface|null $request The request to use or null.
     * @param \Cake\Http\MiddlewareQueue|null $middlewareQueue MiddlewareQueue or null.
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \RuntimeException When the application does not make a response.
     */
    public function run(
        ?ServerRequestInterface $request = null,
        ?MiddlewareQueue $middlewareQueue = null,
    ): ResponseInterface {
        $this->bootstrap();

        $request = $request ?: ServerRequestFactory::fromGlobals();

        if ($middlewareQueue === null) {
            if ($this->app instanceof ContainerApplicationInterface) {
                $middlewareQueue = new MiddlewareQueue([], $this->app->getContainer());
            } else {
                $middlewareQueue = new MiddlewareQueue();
            }
        }

        $middleware = $this->app->middleware($middlewareQueue);
        if ($this->app instanceof PluginApplicationInterface) {
            $middleware = $this->app->pluginMiddleware($middleware);
        }

        $this->dispatchEvent('Server.buildMiddleware', ['middleware' => $middleware]);

        $response = $this->runner->run($middleware, $request, $this->app);

        if ($request instanceof ServerRequest) {
            $request->getSession()->close();
        }

        return $response;
    }

    /**
     * Application bootstrap wrapper.
     *
     * Calls the application's `bootstrap()` hook. After the application the
     * plugins are bootstrapped.
     *
     * @return void
     */
    protected function bootstrap(): void
    {
        $this->app->bootstrap();
        if ($this->app instanceof PluginApplicationInterface) {
            $this->app->pluginBootstrap();
        }
    }

    /**
     * Emit the response using the PHP SAPI.
     *
     * After the response has been emitted, the `Server.terminate` event will be triggered.
     *
     * The `Server.terminate` event can be used to do potentially heavy tasks after the
     * response is sent to the client. Only the PHP FPM server API is able to send a
     * response to the client while the server's PHP process still performs some tasks.
     * For other environments the event will be triggered before the response is flushed
     * to the client and will have no benefit.
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response to emit
     * @param \Cake\Http\ResponseEmitter|null $emitter The emitter to use.
     *   When null, a SAPI Stream Emitter will be used.
     * @return void
     */
    public function emit(ResponseInterface $response, ?ResponseEmitter $emitter = null): void
    {
        $emitter ??= new ResponseEmitter();
        $emitter->emit($response);

        $request = null;
        if ($this->app instanceof ContainerApplicationInterface) {
            $container = $this->app->getContainer();
            if ($container->has(ServerRequest::class)) {
                $request = $container->get(ServerRequest::class);
            }
        }
        if (!$request) {
            $request = Router::getRequest();
        }
        $this->dispatchEvent('Server.terminate', compact('request', 'response'));
    }

    /**
     * Get the current application.
     *
     * @return \Cake\Core\HttpApplicationInterface The application that will be run.
     */
    public function getApp(): HttpApplicationInterface
    {
        return $this->app;
    }

    /**
     * Get the application's event manager or the global one.
     *
     * @return \Cake\Event\EventManagerInterface
     */
    public function getEventManager(): EventManagerInterface
    {
        if ($this->app instanceof EventDispatcherInterface) {
            return $this->app->getEventManager();
        }

        return EventManager::instance();
    }

    /**
     * Set the application's event manager.
     *
     * If the application does not support events, an exception will be raised.
     *
     * @param \Cake\Event\EventManagerInterface $eventManager The event manager to set.
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setEventManager(EventManagerInterface $eventManager)
    {
        if ($this->app instanceof EventDispatcherInterface) {
            $this->app->setEventManager($eventManager);

            return $this;
        }

        throw new InvalidArgumentException('Cannot set the event manager, the application does not support events.');
    }
}
