<?php
declare(strict_types=1);

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
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventManager;
use Cake\Http\Server;
use Cake\Http\ServerRequest;
use Cake\Http\ServerRequestFactory;
use Cake\Routing\Router;
use Cake\Routing\RoutingApplicationInterface;
use LogicException;
use Psr\Http\Message\ResponseInterface;

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
     * @var \Cake\TestSuite\TestCase
     */
    protected $_test;

    /**
     * The application class name
     *
     * @var string
     * @psalm-var class-string<\Cake\Core\HttpApplicationInterface>
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
     * @param \Cake\TestSuite\TestCase $test The test case to run.
     * @param string|null $class The application class name. Defaults to App\Application.
     * @param array|null $constructorArgs The constructor arguments for your application class.
     *   Defaults to `['./config']`
     * @throws \LogicException If it cannot load class for use in integration testing.
     * @psalm-param class-string<\Cake\Core\HttpApplicationInterface>|null $class
     */
    public function __construct(
        TestCase $test,
        ?string $class = null,
        ?array $constructorArgs = null
    ) {
        $this->_test = $test;
        if ($class === null) {
            /** @psalm-var class-string<\Cake\Core\HttpApplicationInterface> */
            $class = Configure::read('App.namespace') . '\Application';
        }
        $this->_class = $class;
        $this->_constructorArgs = $constructorArgs ?: [CONFIG];

        if (!class_exists($this->_class)) {
            throw new LogicException("Cannot load `{$this->_class}` for use in integration testing.", 0);
        }

        $this->app = new $this->_class(...$this->_constructorArgs);
    }

    /**
     * Resolve the provided URL into a string.
     *
     * @param array|string $url The URL array/string to resolve.
     * @return string
     */
    public function resolveUrl($url): string
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
    protected function resolveRoute(array $url): string
    {
        // Simulate application bootstrap and route loading.
        // We need both to ensure plugins are loaded.
        $this->app->bootstrap();
        if ($this->app instanceof PluginApplicationInterface) {
            $this->app->pluginBootstrap();
        }
        $builder = Router::createRouteBuilder('/');

        if ($this->app instanceof RoutingApplicationInterface) {
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
     * @return \Cake\Http\ServerRequest
     */
    protected function _createRequest(array $spec): ServerRequest
    {
        if (isset($spec['input'])) {
            $spec['post'] = [];
            $spec['environment']['CAKEPHP_INPUT'] = $spec['input'];
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

        return $request;
    }

    /**
     * Run a request and get the response.
     *
     * @param array $requestSpec The request spec to execute.
     * @return \Psr\Http\Message\ResponseInterface The generated response.
     * @throws \LogicException
     */
    public function execute(array $requestSpec): ResponseInterface
    {
        // Spy on the controller using the initialize hook instead
        // of the dispatcher hooks as those will be going away one day.
        EventManager::instance()->on(
            'Controller.initialize',
            [$this->_test, 'controllerSpy']
        );

        $server = new Server($this->app);

        return $server->run($this->_createRequest($requestSpec));
    }
}
