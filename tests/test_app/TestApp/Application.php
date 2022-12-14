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
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Exception\DuplicateNamedRouteException;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;
use League\Container\ReflectionContainer;
use stdClass;
use TestApp\Command\AbortCommand;
use TestApp\Command\DependencyCommand;
use TestApp\Command\FormatSpecifierCommand;

class Application extends BaseApplication
{
    public function bootstrap(): void
    {
        parent::bootstrap();

        // Load plugins defined in Configure.
        if (Configure::check('Plugins.autoload')) {
            foreach (Configure::read('Plugins.autoload') as $value) {
                $this->addPlugin($value);
            }
        }

        // Check plugins added here
    }

    public function console(CommandCollection $commands): CommandCollection
    {
        return $commands
            ->add('abort_command', new AbortCommand())
            ->add('format_specifier_command', new FormatSpecifierCommand())
            ->addMany($commands->autoDiscover());
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue->add(function ($request, $handler) {
            return $handler->handle($request)->withHeader('X-Middleware', 'true');
        });
        $middlewareQueue->add(new ErrorHandlerMiddleware(Configure::read('Error', [])));
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
            $routes->connect('/articles/{action}/*', ['controller' => 'Articles']);

            try {
                $routes->connect('/tests/{action}/*', ['controller' => 'Tests'], ['_name' => 'testName']);
            } catch (DuplicateNamedRouteException $e) {
                // do nothing. This happens when one test does multiple requests.
            }
            $routes->redirect('/redirect', 'http://example.com/test.html');
            $routes->fallbacks();
        });
        $routes->connect('/posts', ['controller' => 'Posts', 'action' => 'index']);
        $routes->connect('/bake/{controller}/{action}', ['plugin' => 'Bake']);
    }

    /**
     * Container register hook
     *
     * @param \Cake\Core\ContainerInterface $container The container to update
     */
    public function services(ContainerInterface $container): void
    {
        $container->add(stdClass::class, json_decode('{"key":"value"}'));
        $container->add(DependencyCommand::class)
            ->addArgument(stdClass::class);
        $container->add(ComponentRegistry::class);
        $container->delegate(new ReflectionContainer());
    }
}
