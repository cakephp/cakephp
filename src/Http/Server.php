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

use Cake\Core\HttpApplicationInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventManager;
use Cake\Event\EventManagerInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;

/**
 * Runs an application invoking all the PSR7 middleware and the registered application.
 */
class Server implements EventDispatcherInterface
{
    use EventDispatcherTrait;

    /**
     * @var \Cake\Core\HttpApplicationInterface
     */
    protected $app;

    /**
     * @var \Cake\Http\Runner
     */
    protected $runner;

    /**
     * Constructor
     *
     * @param \Cake\Core\HttpApplicationInterface $app The application to use.
     */
    public function __construct(HttpApplicationInterface $app)
    {
        $this->app = $app;
        $this->setRunner(new Runner());
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
     * @param \Cake\Http\ServerRequest|null $request The request to use or null.
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \RuntimeException When the application does not make a response.
     */
    public function run(?ServerRequest $request = null): ResponseInterface
    {
        $this->bootstrap();

        $request = $request ?: ServerRequestFactory::fromGlobals();

        $middleware = $this->app->middleware(new MiddlewareQueue());
        if ($this->app instanceof PluginApplicationInterface) {
            $middleware = $this->app->pluginMiddleware($middleware);
        }

        $this->dispatchEvent('Server.buildMiddleware', ['middleware' => $middleware]);

        return $this->runner->run($middleware, $request, $this->app);
    }

    /**
     * Application bootstrap wrapper.
     *
     * Calls `bootstrap()` and `events()` if application implements `EventApplicationInterface`.
     * After the application is bootstrapped and events are attached, plugins are bootstrapped
     * and have their events attached.
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
     * @param \Psr\Http\Message\ResponseInterface $response The response to emit
     * @param \Zend\HttpHandlerRunner\Emitter\EmitterInterface|null $emitter The emitter to use.
     *   When null, a SAPI Stream Emitter will be used.
     * @return void
     */
    public function emit(ResponseInterface $response, ?EmitterInterface $emitter = null): void
    {
        if (!$emitter) {
            $emitter = new ResponseEmitter();
        }
        $emitter->emit($response);
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
     * Set the runner
     *
     * @param \Cake\Http\Runner $runner The runner to use.
     * @return $this
     */
    public function setRunner(Runner $runner)
    {
        $this->runner = $runner;

        return $this;
    }

    /**
     * Get the application's event manager or the global one.
     *
     * @return \Cake\Event\EventManagerInterface
     */
    public function getEventManager(): EventManagerInterface
    {
        if ($this->app instanceof PluginApplicationInterface) {
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
        if ($this->app instanceof PluginApplicationInterface) {
            $this->app->setEventManager($eventManager);

            return $this;
        }

        throw new InvalidArgumentException('Cannot set the event manager, the application does not support events.');
    }
}
