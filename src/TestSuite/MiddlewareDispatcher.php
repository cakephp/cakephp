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
     * @var \Cake\Core\HttpApplicationInterface|\Cake\Core\ConsoleApplicationInterface
     */
    protected $app;

    /**
     * Constructor
     *
     * @param \Cake\TestSuite\IntegrationTestCase $test The test case to run.
     * @param string|null $class The application class name. Defaults to App\Application.
     * @param array|null $constructorArgs The constructor arguments for your application class.
     *   Defaults to `['./config']`
     * @param bool $disableRouterReload Disable Router::reload() call when resolving URLs. This
     *   flag may be necessary if you are using Router methods in your test case setup, and using array URLs
     *   when doing requests in your tests.
     * @throws \LogicException If it cannot load class for use in integration testing.
     */
    public function __construct($test, $class = null, $constructorArgs = null, $disableRouterReload = false)
    {
        $this->_test = $test;
        $this->_class = $class ?: Configure::read('App.namespace') . '\Application';
        $this->_constructorArgs = $constructorArgs ?: [CONFIG];

        try {
            $reflect = new ReflectionClass($this->_class);
            /** @var \Cake\Core\HttpApplicationInterface $app */
            $app = $reflect->newInstanceArgs($this->_constructorArgs);
            $this->app = $app;
        } catch (ReflectionException $e) {
            throw new LogicException(sprintf('Cannot load "%s" for use in integration testing.', $this->_class));
        }
    }

    /**
     * Resolve the provided URL into a string.
     *
     * @param array|string $url The URL array/string to resolve.
     * @return string
     */
    public function resolveUrl($url)
    {
        // If we need to resolve a Route URL but there are no routes, load routes.
        if (is_array($url) && count(Router::getRouteCollection()->routes()) === 0) {
            return $this->resolveRoute($url);
        }

        return Router::url($url);
    }

    /**
     * Convert a URL array into a string URL via routing.
     *
     * @param array $url The url to resolve
     * @return string
     */
    protected function resolveRoute(array $url)
    {
        // Simulate application bootstrap and route loading.
        // We need both to ensure plugins are loaded.
        $this->app->bootstrap();
        if ($this->app instanceof PluginApplicationInterface) {
            $this->app->pluginBootstrap();
        }
        $builder = Router::createRouteBuilder('/');

        if ($this->app instanceof HttpApplicationInterface) {
            $this->app->routes($builder);
        }
        if ($this->app instanceof PluginApplicationInterface) {
            $this->app->pluginRoutes($builder);
        }

        $out = Router::url($url);
        Router::resetRoutes();

        return $out;
    }

    /**
     * Create a PSR7 request from the request spec.
     *
     * @param array $spec The request spec.
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function _createRequest($spec)
    {
        if (isset($spec['input'])) {
            $spec['post'] = [];
        }
        $environment = array_merge(
            array_merge($_SERVER, ['REQUEST_URI' => $spec['url']]),
            $spec['environment']
        );
        if (strpos($environment['PHP_SELF'], 'phpunit') !== false) {
            $environment['PHP_SELF'] = '/';
        }
        $request = ServerRequestFactory::fromGlobals(
            $environment,
            $spec['query'],
            $spec['post'],
            $spec['cookies'],
            $spec['files']
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

    /**
     * Run a request and get the response.
     *
     * @param array $requestSpec The request spec to execute.
     * @return \Psr\Http\Message\ResponseInterface The generated response.
     */
    public function execute($requestSpec)
    {
        try {
            $reflect = new ReflectionClass($this->_class);
            $app = $reflect->newInstanceArgs($this->_constructorArgs);
        } catch (ReflectionException $e) {
            throw new LogicException(sprintf(
                'Cannot load "%s" for use in integration testing.',
                $this->_class
            ));
        }

        // Spy on the controller using the initialize hook instead
        // of the dispatcher hooks as those will be going away one day.
        EventManager::instance()->on(
            'Controller.initialize',
            [$this->_test, 'controllerSpy']
        );

        $server = new Server($app);

        return $server->run($this->_createRequest($requestSpec));
    }
}
