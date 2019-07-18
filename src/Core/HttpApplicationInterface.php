<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * An interface defining the methods that the
 * http server depend on.
 */
interface HttpApplicationInterface
{
    /**
     * Load all the application configuration and bootstrap logic.
     *
     * Override this method to add additional bootstrap logic for your application.
     *
     * @return void
     */
    public function bootstrap();

    /**
     * Define the routes for an application.
     *
     * Use the provided RouteBuilder to define an application's routing.
     *
     * @param \Cake\Routing\RouteBuilder $routes A route builder to add routes into.
     * @return void
     */
    public function routes($routes);

    /**
     * Define the HTTP middleware layers for an application.
     *
     * @param \Cake\Http\MiddlewareQueue $middleware The middleware queue to set in your App Class
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware($middleware);

    /**
     * Invoke the application.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @param \Psr\Http\Message\ResponseInterface $response The response
     * @param callable $next The next middleware
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next);
}
