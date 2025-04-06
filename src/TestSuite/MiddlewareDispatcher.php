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

use Cake\Core\HttpApplicationInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\FlashMessage;
use Cake\Http\Server;
use Cake\Http\ServerRequest;
use Cake\Http\ServerRequestFactory;
use Cake\Routing\Router;
use Cake\Routing\RoutingApplicationInterface;
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
     * The application that is being dispatched.
     *
     * @var \Cake\Core\HttpApplicationInterface
     */
    protected HttpApplicationInterface $app;

    /**
     * Constructor
     *
     * @param \Cake\Core\HttpApplicationInterface $app The test case to run.
     */
    public function __construct(HttpApplicationInterface $app)
    {
        $this->app = $app;
    }

    /**
     * Resolve the provided URL into a string.
     *
     * @param array|string $url The URL array/string to resolve.
     * @return string
     * @deprecated 5.1.0 Use IntegrationTestTrait::resolveUrl() instead.
     */
    public function resolveUrl(array|string $url): string
    {
        // If we need to resolve a Route URL but there are no routes, load routes.
        if (is_array($url) && Router::getRouteCollection()->routes() === []) {
            return $this->resolveRoute($url);
        }

        return Router::url($url);
    }

    /**
     * Convert a URL array into a string URL via routing.
     *
     * @param array $url The url to resolve
     * @return string
     * @deprecated 5.1.0 Use IntegrationTestTrait::resolveRouter() instead.
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
     * @param array<string, mixed> $spec The request spec.
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
            $spec['environment'],
        );
        if (str_contains($environment['PHP_SELF'], 'phpunit')) {
            $environment['PHP_SELF'] = '/';
        }
        $request = ServerRequestFactory::fromGlobals(
            $environment,
            $spec['query'],
            $spec['post'],
            $spec['cookies'],
            $spec['files'],
        );

        return $request
            ->withAttribute('session', $spec['session'])
            ->withAttribute('flash', new FlashMessage($spec['session']));
    }

    /**
     * Run a request and get the response.
     *
     * @param array<string, mixed> $requestSpec The request spec to execute.
     * @return \Psr\Http\Message\ResponseInterface The generated response.
     * @throws \LogicException
     */
    public function execute(array $requestSpec): ResponseInterface
    {
        $server = new Server($this->app);

        return $server->run($this->_createRequest($requestSpec));
    }
}
