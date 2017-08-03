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

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Runner;
use Cake\Routing\Exception\RedirectException;
use Cake\Routing\RouteBuilder;
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
     * The application that will have its routing hook invoked.
     *
     * @var \Cake\Http\BaseApplication
     */
    protected $app;

    /**
     * Constructor
     *
     * @param \Cake\Http\BaseApplication $app The application instance that routes are defined on.
     */
    public function __construct(BaseApplication $app = null)
    {
        $this->app = $app;
    }

    /**
     * Trigger the application's routes() hook if the application exists.
     *
     * If the middleware is created without an Application, routes will be
     * loaded via the automatic route loading that pre-dates the routes() hook.
     *
     * @return void
     */
    protected function loadRoutes()
    {
        if ($this->app) {
            $builder = Router::createRouteBuilder('/');
            $this->app->routes($builder);
            // Prevent routes from being loaded again
            Router::$initialized = true;
        }
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
                $e->getCode(),
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
