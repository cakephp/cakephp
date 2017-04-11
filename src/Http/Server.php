<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use Cake\Event\EventDispatcherTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Zend\Diactoros\Response\EmitterInterface;

/**
 * Runs an application invoking all the PSR7 middleware and the registered application.
 */
class Server
{

    use EventDispatcherTrait;

    /**
     * @var \Cake\Http\BaseApplication
     */
    protected $app;

    /**
     * @var \Cake\Http\Runner
     */
    protected $runner;

    /**
     * Constructor
     *
     * @param \Cake\Http\BaseApplication $app The application to use.
     */
    public function __construct(BaseApplication $app)
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
        $this->app->bootstrap();
        $response = $response ?: new Response();
        $request = $request ?: ServerRequestFactory::fromGlobals();

        $middleware = $this->app->middleware(new MiddlewareQueue());
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
     * Emit the response using the PHP SAPI.
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response to emit
     * @param \Zend\Diactoros\Response\EmitterInterface|null $emitter The emitter to use.
     *   When null, a SAPI Stream Emitter will be used.
     * @return void
     */
    public function emit(ResponseInterface $response, EmitterInterface $emitter = null)
    {
        $stream = $response->getBody();
        if (!$emitter) {
            $emitter = new ResponseEmitter();
        }
        $emitter->emit($response);
    }

    /**
     * Set the application.
     *
     * @param BaseApplication $app The application to set.
     * @return $this
     */
    public function setApp(BaseApplication $app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Get the current application.
     *
     * @return \Cake\Http\BaseApplication The application that will be run.
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
