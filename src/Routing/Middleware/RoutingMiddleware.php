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
namespace Cake\Routing\Middleware;

use Cake\Cache\Cache;
use Cake\Core\HttpApplicationInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Runner;
use Cake\Routing\Exception\RedirectException;
use Cake\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;

/**
 * Applies routing rules to the request and creates the controller
 * instance if possible.
 */
class RoutingMiddleware
{
    /**
     * Key used to store the route collection in the cache engine
     */
    const ROUTE_COLLECTION_CACHE_KEY = 'routeCollection';

    /**
     * The application that will have its routing hook invoked.
     *
     * @var \Cake\Core\HttpApplicationInterface|null
     */
    protected $app;

    /**
     * The cache configuration name to use for route collection caching,
     * null to disable caching
     *
     * @var string|null
     */
    protected $cacheConfig;

    /**
     * Constructor
     *
     * @param \Cake\Core\HttpApplicationInterface|null $app The application instance that routes are defined on.
     * @param string|null $cacheConfig The cache config name to use or null to disable routes cache
     */
    public function __construct(HttpApplicationInterface $app = null, $cacheConfig = null)
    {
        if ($app === null) {
            deprecationWarning(
                'RoutingMiddleware should be passed an application instance. ' .
                'Failing to do so can cause plugin routes to not behave correctly.'
            );
        }
        $this->app = $app;
        $this->cacheConfig = $cacheConfig;
    }

    /**
     * Trigger the application's routes() hook if the application exists and Router isn't initialized.
     * Uses the routes cache if enabled via configuration param "Router.cache"
     *
     * If the middleware is created without an Application, routes will be
     * loaded via the automatic route loading that pre-dates the routes() hook.
     *
     * @return void
     */
    protected function loadRoutes()
    {
        if (!$this->app) {
            return;
        }

        $routeCollection = $this->buildRouteCollection();
        Router::setRouteCollection($routeCollection);
    }

    /**
     * Check if route cache is enabled and use the configured Cache to 'remember' the route collection
     *
     * @return \Cake\Routing\RouteCollection
     */
    protected function buildRouteCollection()
    {
        if (Cache::enabled() && $this->cacheConfig !== null) {
            return Cache::remember(static::ROUTE_COLLECTION_CACHE_KEY, function () {
                return $this->prepareRouteCollection();
            }, $this->cacheConfig);
        }

        return $this->prepareRouteCollection();
    }

    /**
     * Generate the route collection using the builder
     *
     * @return \Cake\Routing\RouteCollection
     */
    protected function prepareRouteCollection()
    {
        $builder = Router::createRouteBuilder('/');
        $this->app->routes($builder);
        if ($this->app instanceof PluginApplicationInterface) {
            $this->app->pluginRoutes($builder);
        }

        return Router::getRouteCollection();
    }

    /**
     * Apply routing and update the request.
     *
     * Any route/path specific middleware will be wrapped around $next and then the new middleware stack will be
     * invoked.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next The next middleware to call.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $this->loadRoutes();
        try {
            Router::setRequestContext($request);
            $params = (array)$request->getAttribute('params', []);
            $middleware = [];
            if (empty($params['controller'])) {
                $parsedBody = $request->getParsedBody();
                if (is_array($parsedBody) && isset($parsedBody['_method'])) {
                    $request = $request->withMethod($parsedBody['_method']);
                }
                $params = Router::parseRequest($request) + $params;
                if (isset($params['_middleware'])) {
                    $middleware = $params['_middleware'];
                    unset($params['_middleware']);
                }
                $request = $request->withAttribute('params', $params);
            }
        } catch (RedirectException $e) {
            return new RedirectResponse(
                $e->getMessage(),
                (int)$e->getCode(),
                $response->getHeaders()
            );
        }
        $matching = Router::getRouteCollection()->getMiddleware($middleware);
        if (!$matching) {
            return $next($request, $response);
        }
        $matching[] = $next;
        $middleware = new MiddlewareQueue($matching);
        $runner = new Runner();

        return $runner->run($middleware, $request, $response);
    }
}
