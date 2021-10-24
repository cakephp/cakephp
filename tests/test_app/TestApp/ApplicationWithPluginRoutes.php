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
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.6.6
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;

class ApplicationWithPluginRoutes extends BaseApplication
{
    public function bootstrap(): void
    {
        parent::bootstrap();
        $this->addPlugin('TestPlugin');
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue->add(new RoutingMiddleware($this));

        return $middlewareQueue;
    }

    /**
     * Routes hook, used for testing with RoutingMiddleware.
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->scope('/app', function (RouteBuilder $routes): void {
            $routes->connect('/articles', ['controller' => 'Articles']);
        });
        $routes->loadPlugin('TestPlugin');
    }
}
