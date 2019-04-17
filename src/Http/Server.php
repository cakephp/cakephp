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
namespace Cake\Http;

use Cake\Core\HttpApplicationInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventManager;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Zend\Diactoros\Response\EmitterInterface;

/**
 * Runs an application invoking all the PSR7 middleware and the registered application.
 */
class Server implements EventDispatcherInterface
{

    /**
     * Alias methods away so we can implement proxying methods.
     */
    use EventDispatcherTrait {
        eventManager as private _eventManager;
        getEventManager as private _getEventManager;
        setEventManager as private _setEventManager;
    }

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
     * @param \Psr\Http\Message\ServerRequestInterface|null $request The request to use or null.
     * @param \Psr\Http\Message\ResponseInterface|null $response The response to use or null.
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \RuntimeException When the application does not make a response.
     */
    public function run(ServerRequestInterface $request = null, ResponseInterface $response = null)
    {
        $this->bootstrap();

        $response = $response ?: new Response();
        $request = $request ?: ServerRequestFactory::fromGlobals();

        $middleware = $this->app->middleware(new MiddlewareQueue());
        if ($this->app instanceof PluginApplicationInterface) {
            $middleware = $this->app->pluginMiddleware($middleware);
        }

        if (!($middleware instanceof MiddlewareQueue)) {
            throw new RuntimeException('The application `middleware` method did not return a middleware queue.');
        }
        $this->dispatchEvent('Server.buildMiddleware', ['middleware' => $middleware]);
        $middleware->add($this->app);

        $response = $this->runner->run($middleware, $request, $response);

        if (!($response instanceof ResponseInterface)) {
            throw new RuntimeException(sprintf(
                'Application did not create a response. Got "%s" instead.',
                is_object($response) ? get_class($response) : $response
            ));
        }

        return $response;
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
    protected function bootstrap()
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
     * @param \Zend\Diactoros\Response\EmitterInterface|null $emitter The emitter to use.
     *   When null, a SAPI Stream Emitter will be used.
     * @return void
     */
    public function emit(ResponseInterface $response, EmitterInterface $emitter = null)
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
    public function getApp()
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
     * @return \Cake\Event\EventManager
     */
    public function getEventManager()
    {
        if ($this->app instanceof PluginApplicationInterface) {
            return $this->app->getEventManager();
        }

        return EventManager::instance();
    }

    /**
     * Get/set the application's event manager.
     *
     * If the application does not support events and this method is used as
     * a setter, an exception will be raised.
     *
     * @param \Cake\Event\EventManager|null $events The event manager to set.
     * @return \Cake\Event\EventManager|$this
     * @deprecated 3.6.0 Will be removed in 4.0
     */
    public function eventManager(EventManager $events = null)
    {
        deprecationWarning('eventManager() is deprecated. Use getEventManager()/setEventManager() instead.');
        if ($events === null) {
            return $this->getEventManager();
        }

        return $this->setEventManager($events);
    }

    /**
     * Get/set the application's event manager.
     *
     * If the application does not support events and this method is used as
     * a setter, an exception will be raised.
     *
     * @param \Cake\Event\EventManager $events The event manager to set.
     * @return $this
     */
    public function setEventManager(EventManager $events)
    {
        if ($this->app instanceof PluginApplicationInterface) {
            $this->app->setEventManager($events);

            return $this;
        }

        throw new InvalidArgumentException('Cannot set the event manager, the application does not support events.');
    }
}
