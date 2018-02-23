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
use Cake\Event\EventApplicationInterface;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Zend\Diactoros\Response\EmitterInterface;

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
        $this->setApp($app);
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

            $events = $this->app->events($this->getEventManager());
            $events = $this->app->pluginEvents($events);
            $this->setEventManager($events);
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
     * Set the application.
     *
     * @param \Cake\Core\HttpApplicationInterface $app The application to set.
     * @return $this
     */
    public function setApp(HttpApplicationInterface $app)
    {
        $this->app = $app;

        if ($app instanceof EventDispatcherInterface) {
            $this->setEventManager($app->getEventManager());
        }

        return $this;
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
}
