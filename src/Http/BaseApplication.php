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

namespace Cake\Http;

use Cake\Console\CommandCollection;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\ControllerFactory;
use Cake\Core\ConsoleApplicationInterface;
use Cake\Core\Container;
use Cake\Core\ContainerApplicationInterface;
use Cake\Core\ContainerInterface;
use Cake\Core\Exception\MissingPluginException;
use Cake\Core\HttpApplicationInterface;
use Cake\Core\Plugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Core\PluginCollection;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventManager;
use Cake\Event\EventManagerInterface;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\RoutingApplicationInterface;
use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Base class for full-stack applications
 *
 * This class serves as a base class for applications that are using
 * CakePHP as a full stack framework. If you are only using the Http or Console libraries
 * you should implement the relevant interfaces directly.
 *
 * The application class is responsible for bootstrapping the application,
 * and ensuring that middleware is attached. It is also invoked as the last piece
 * of middleware, and delegates request/response handling to the correct controller.
 */
abstract class BaseApplication implements
    ConsoleApplicationInterface,
    ContainerApplicationInterface,
    HttpApplicationInterface,
    PluginApplicationInterface,
    RoutingApplicationInterface
{
    use EventDispatcherTrait;

    /**
     * @var string Contains the path of the config directory
     */
    protected $configDir;

    /**
     * Plugin Collection
     *
     * @var \Cake\Core\PluginCollection
     */
    protected $plugins;

    /**
     * Controller factory
     *
     * @var \Cake\Http\ControllerFactoryInterface|null
     */
    protected $controllerFactory;

    /**
     * Container
     *
     * @var \Cake\Core\ContainerInterface|null
     */
    protected $container;

    /**
     * Constructor
     *
     * @param string $configDir The directory the bootstrap configuration is held in.
     * @param \Cake\Event\EventManagerInterface|null $eventManager Application event manager instance.
     * @param \Cake\Http\ControllerFactoryInterface|null $controllerFactory Controller factory.
     */
    public function __construct(
        string $configDir,
        ?EventManagerInterface $eventManager = null,
        ?ControllerFactoryInterface $controllerFactory = null
    ) {
        $this->configDir = rtrim($configDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->plugins = Plugin::getCollection();
        $this->_eventManager = $eventManager ?: EventManager::instance();
        $this->controllerFactory = $controllerFactory;
    }

    /**
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to set in your App Class
     * @return \Cake\Http\MiddlewareQueue
     */
    abstract public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue;

    /**
     * @inheritDoc
     */
    public function pluginMiddleware(MiddlewareQueue $middleware): MiddlewareQueue
    {
        foreach ($this->plugins->with('middleware') as $plugin) {
            $middleware = $plugin->middleware($middleware);
        }

        return $middleware;
    }

    /**
     * @inheritDoc
     */
    public function addPlugin($name, array $config = [])
    {
        if (is_string($name)) {
            $plugin = $this->plugins->create($name, $config);
        } else {
            $plugin = $name;
        }
        $this->plugins->add($plugin);

        return $this;
    }

    /**
     * Add an optional plugin
     *
     * If it isn't available, ignore it.
     *
     * @param \Cake\Core\PluginInterface|string $name The plugin name or plugin object.
     * @param array<string, mixed> $config The configuration data for the plugin if using a string for $name
     * @return $this
     */
    public function addOptionalPlugin($name, array $config = [])
    {
        try {
            $this->addPlugin($name, $config);
        } catch (MissingPluginException $e) {
            // Do not halt if the plugin is missing
        }

        return $this;
    }

    /**
     * Get the plugin collection in use.
     *
     * @return \Cake\Core\PluginCollection
     */
    public function getPlugins(): PluginCollection
    {
        return $this->plugins;
    }

    /**
     * @inheritDoc
     */
    public function bootstrap(): void
    {
        require_once $this->configDir . 'bootstrap.php';
    }

    /**
     * @inheritDoc
     */
    public function pluginBootstrap(): void
    {
        foreach ($this->plugins->with('bootstrap') as $plugin) {
            $plugin->bootstrap($this);
        }
    }

    /**
     * {@inheritDoc}
     *
     * By default, this will load `config/routes.php` for ease of use and backwards compatibility.
     *
     * @param \Cake\Routing\RouteBuilder $routes A route builder to add routes into.
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        // Only load routes if the router is empty
        if (!Router::routes()) {
            $return = require $this->configDir . 'routes.php';
            if ($return instanceof Closure) {
                $return($routes);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function pluginRoutes(RouteBuilder $routes): RouteBuilder
    {
        foreach ($this->plugins->with('routes') as $plugin) {
            $plugin->routes($routes);
        }

        return $routes;
    }

    /**
     * Define the console commands for an application.
     *
     * By default, all commands in CakePHP, plugins and the application will be
     * loaded using conventions based names.
     *
     * @param \Cake\Console\CommandCollection $commands The CommandCollection to add commands into.
     * @return \Cake\Console\CommandCollection The updated collection.
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        return $commands->addMany($commands->autoDiscover());
    }

    /**
     * @inheritDoc
     */
    public function pluginConsole(CommandCollection $commands): CommandCollection
    {
        foreach ($this->plugins->with('console') as $plugin) {
            $commands = $plugin->console($commands);
        }

        return $commands;
    }

    /**
     * Get the dependency injection container for the application.
     *
     * The first time the container is fetched it will be constructed
     * and stored for future calls.
     *
     * @return \Cake\Core\ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        if ($this->container === null) {
            $this->container = $this->buildContainer();
        }

        return $this->container;
    }

    /**
     * Build the service container
     *
     * Override this method if you need to use a custom container or
     * want to change how the container is built.
     *
     * @return \Cake\Core\ContainerInterface
     */
    protected function buildContainer(): ContainerInterface
    {
        $container = new Container();
        $this->services($container);
        foreach ($this->plugins->with('services') as $plugin) {
            $plugin->services($container);
        }

        $event = $this->dispatchEvent('Application.buildContainer', ['container' => $container]);
        if ($event->getResult() instanceof ContainerInterface) {
            return $event->getResult();
        }

        return $container;
    }

    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     */
    public function services(ContainerInterface $container): void
    {
    }

    /**
     * Invoke the application.
     *
     * - Add the request to the container, enabling its injection into other services.
     * - Create the controller that will handle this request.
     * - Invoke the controller.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(
        ServerRequestInterface $request
    ): ResponseInterface {
        $container = $this->getContainer();
        $container->add(ServerRequest::class, $request);
        $container->add(ContainerInterface::class, $container);

        if ($this->controllerFactory === null) {
            $this->controllerFactory = new ControllerFactory($container);
        }

        if (Router::getRequest() !== $request) {
            Router::setRequest($request);
        }

        $controller = $this->controllerFactory->create($request);

        // This is needed for auto-wiring. Should be removed in 5.x
        $container->add(ComponentRegistry::class, $controller->components());

        return $this->controllerFactory->invoke($controller);
    }
}
