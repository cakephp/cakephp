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
namespace Cake\Routing\Middleware;

use Cake\Cache\Cache;
use Cake\Cache\InvalidArgumentException;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Runner;
use Cake\Routing\Exception\FailedRouteCacheException;
use Cake\Routing\Exception\RedirectException as DeprecatedRedirectException;
use Cake\Routing\RouteCollection;
use Cake\Routing\Router;
use Cake\Routing\RoutingApplicationInterface;
use Exception;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Applies routing rules to the request and creates the controller
 * instance if possible.
 */
class RoutingMiddleware implements MiddlewareInterface
{
    /**
     * Key used to store the route collection in the cache engine
     *
     * @var string
     */
    public const ROUTE_COLLECTION_CACHE_KEY = 'routeCollection';

    /**
     * The application that will have its routing hook invoked.
     *
     * @var \Cake\Routing\RoutingApplicationInterface
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
     * @param \Cake\Routing\RoutingApplicationInterface $app The application instance that routes are defined on.
     * @param string|null $cacheConfig The cache config name to use or null to disable routes cache
     */
    public function __construct(RoutingApplicationInterface $app, ?string $cacheConfig = null)
    {
        if ($cacheConfig !== null) {
            deprecationWarning(
                'Use of routing cache is deprecated and will be removed in 5.0. ' .
                'Upgrade to the new `CakeDC/CachedRouting` plugin. ' .
                'See https://github.com/CakeDC/cakephp-cached-routing'
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
    protected function loadRoutes(): void
    {
        $routeCollection = $this->buildRouteCollection();
        Router::setRouteCollection($routeCollection);
    }

    /**
     * Check if route cache is enabled and use the configured Cache to 'remember' the route collection
     *
     * @return \Cake\Routing\RouteCollection
     */
    protected function buildRouteCollection(): RouteCollection
    {
        if (Cache::enabled() && $this->cacheConfig !== null) {
            try {
                return Cache::remember(static::ROUTE_COLLECTION_CACHE_KEY, function () {
                    return $this->prepareRouteCollection();
                }, $this->cacheConfig);
            } catch (InvalidArgumentException $exception) {
                throw $exception;
            } catch (Exception $exception) {
                throw new FailedRouteCacheException(
                    'Unable to cache route collection. Cached routes must be serializable. Check for route-specific
                    middleware or other unserializable settings in your routes. The original exception message can
                    show what type of object failed to serialize.',
                    null,
                    $exception
                );
            }
        }

        return $this->prepareRouteCollection();
    }

    /**
     * Generate the route collection using the builder
     *
     * @return \Cake\Routing\RouteCollection
     */
    protected function prepareRouteCollection(): RouteCollection
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
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->loadRoutes();
        try {
            Router::setRequest($request);
            $params = (array)$request->getAttribute('params', []);
            $middleware = [];
            if (empty($params['controller'])) {
                $params = Router::parseRequest($request) + $params;
                if (isset($params['_middleware'])) {
                    $middleware = $params['_middleware'];
                }
                $route = $params['_route'];
                unset($params['_middleware'], $params['_route']);

                $request = $request->withAttribute('route', $route);
                /** @var \Cake\Http\ServerRequest $request */
                $request = $request->withAttribute('params', $params);
                Router::setRequest($request);
            }
        } catch (RedirectException $exception) {
            return new RedirectResponse(
                $exception->getMessage(),
                $exception->getCode()
            );
        } catch (DeprecatedRedirectException $exception) {
            return new RedirectResponse(
                $exception->getMessage(),
                $exception->getCode()
            );
        }
        $matching = Router::getRouteCollection()->getMiddleware($middleware);
        if (!$matching) {
            return $handler->handle($request);
        }

        $middleware = new MiddlewareQueue($matching);
        $runner = new Runner();

        return $runner->run($middleware, $request, $handler);
    }
}
