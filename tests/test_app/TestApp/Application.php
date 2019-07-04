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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp;

use Cake\Console\CommandCollection;
use Cake\Core\Configure;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;
use TestApp\Command\AbortCommand;

class Application extends BaseApplication
{
    /**
     * @return void
     */
    public function bootstrap(): void
    {
        parent::bootstrap();

        // Load plugins defined in Configure.
        if (Configure::check('Plugins.autoload')) {
            foreach (Configure::read('Plugins.autoload') as $value) {
                $this->addPlugin($value);
            }
        }
    }

    /**
     * @param \Cake\Console\CommandCollection $commands
     *
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        return $commands
            ->add('abort_command', new AbortCommand())
            ->addMany($commands->autoDiscover());
    }

    /**
     * @param \Cake\Http\MiddlewareQueue $middleware
     *
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middleware): MiddlewareQueue
    {
        $middleware->add(function ($req, $res, $next) {
            /** @var \Cake\Http\ServerRequest $res */
            $res = $next($req, $res);

            return $res->withHeader('X-Middleware', 'true');
        });
        $middleware->add(new RoutingMiddleware($this));

        return $middleware;
    }

    /**
     * Routes hook, used for testing with RoutingMiddleware.
     *
     * @param \Cake\Routing\RouteBuilder $routes
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->scope('/app', function (RouteBuilder $routes) {
            $routes->connect('/articles', ['controller' => 'Articles']);
            $routes->connect('/articles/:action/*', ['controller' => 'Articles']);

            $routes->connect('/tests/:action/*', ['controller' => 'Tests'], ['_name' => 'testName']);
            $routes->fallbacks();
        });
        $routes->connect('/posts', ['controller' => 'Posts', 'action' => 'index']);
        $routes->connect('/bake/:controller/:action', ['plugin' => 'Bake']);
    }
}
