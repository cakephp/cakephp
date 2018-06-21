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
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.6.6
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp;

use Cake\Http\BaseApplication;
use Cake\Routing\Middleware\RoutingMiddleware;

class ApplicationWithPluginRoutes extends BaseApplication
{
    public function bootstrap()
    {
        parent::bootstrap();
        $this->addPlugin('TestPlugin');
    }

    public function middleware($middleware)
    {
        $middleware->add(new RoutingMiddleware($this));

        return $middleware;
    }

    /**
     * Routes hook, used for testing with RoutingMiddleware.
     *
     * @param \Cake\Routing\RouteBuilder $routes
     * @return void
     */
    public function routes($routes)
    {
        $routes->scope('/app', function ($routes) {
            $routes->connect('/articles', ['controller' => 'Articles']);
        });
        $routes->loadPlugin('TestPlugin');
    }
}
