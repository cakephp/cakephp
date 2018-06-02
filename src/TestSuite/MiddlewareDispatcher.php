<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Core\Configure;
use Cake\Core\HttpApplicationInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventManager;
use Cake\Http\Server;
use Cake\Http\ServerRequestFactory;
use Cake\Routing\Router;
use LogicException;
use ReflectionClass;
use ReflectionException;
use Zend\Diactoros\Stream;

/**
 * Dispatches a request capturing the response for integration
 * testing purposes into the Cake\Http stack.
 *
 * @internal
 */
class MiddlewareDispatcher
{
    /**
     * The test case being run.
     *
     * @var \Cake\TestSuite\IntegrationTestCase
     */
    protected $_test;

    /**
     * The application class name
     *
     * @var string
     */
    protected $_class;

    /**
     * Constructor arguments for your application class.
     *
     * @var array
     */
    protected $_constructorArgs;

    /**
     * The application that is being dispatched.
     *
     * @var \Cake\Core\HttpApplicationInterface
     */
    protected $app;

    /**
     * Constructor
     *
     * @param \Cake\TestSuite\IntegrationTestCase $test The test case to run.
     * @param string|null $class The application class name. Defaults to App\Application.
     * @param array|null $constructorArgs The constructor arguments for your application class.
     *   Defaults to `['./config']`
     */
    public function __construct($test, $class = null, $constructorArgs = null)
    {
        $this->_test = $test;
        $this->_class = $class ?: Configure::read('App.namespace') . '\Application';
        $this->_constructorArgs = $constructorArgs ?: [CONFIG];

        try {
            $reflect = new ReflectionClass($this->_class);
            $this->app = $reflect->newInstanceArgs($this->_constructorArgs);
        } catch (ReflectionException $e) {
            throw new LogicException(sprintf('Cannot load "%s" for use in integration testing.', $this->_class));
        }

        $this->bootstrap();
        $this->loadRoutes();
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
     * Ensure that the application's routes are loaded.
     *
     * Console commands and shells often need to generate URLs.
     *
     * @return void
     */
    protected function loadRoutes()
    {
        Router::reload();
        $builder = Router::createRouteBuilder('/');

        if ($this->app instanceof HttpApplicationInterface) {
            $this->app->routes($builder);
        }
        if ($this->app instanceof PluginApplicationInterface) {
            $this->app->pluginRoutes($builder);
        }
    }

    /**
     * Run a request and get the response.
     *
     * @param \Cake\Http\ServerRequest $request The request to execute.
     * @return \Psr\Http\Message\ResponseInterface The generated response.
     */
    public function execute($request)
    {
        // Spy on the controller using the initialize hook instead
        // of the dispatcher hooks as those will be going away one day.
        EventManager::instance()->on(
            'Controller.initialize',
            [$this->_test, 'controllerSpy']
        );

        Router::reload();
        $server = new Server($this->app);
        $psrRequest = $this->_createRequest($request);

        return $server->run($psrRequest);
    }

    /**
     * Create a PSR7 request from the request spec.
     *
     * @param array $spec The request spec.
     * @return \Psr\Http\Message\RequestInterface
     */
    protected function _createRequest($spec)
    {
        if (isset($spec['input'])) {
            $spec['post'] = [];
        }
        $environment = array_merge(
            array_merge($_SERVER, ['REQUEST_URI' => $spec['url'], 'PHP_SELF' => '/']),
            $spec['environment']
        );
        $request = ServerRequestFactory::fromGlobals(
            $environment,
            $spec['query'],
            $spec['post'],
            $spec['cookies']
        );
        $request = $request->withAttribute('session', $spec['session']);

        if (isset($spec['input'])) {
            $stream = new Stream('php://memory', 'rw');
            $stream->write($spec['input']);
            $stream->rewind();
            $request = $request->withBody($stream);
        }

        return $request;
    }
}
