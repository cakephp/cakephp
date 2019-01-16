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
use Cake\Core\BasePlugin;
use Cake\Core\ConsoleApplicationInterface;
use Cake\Core\HttpApplicationInterface;
use Cake\Core\Plugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Core\PluginCollection;
use Cake\Core\PluginInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventManager;
use Cake\Event\EventManagerInterface;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Base class for application classes.
 *
 * The application class is responsible for bootstrapping the application,
 * and ensuring that middleware is attached. It is also invoked as the last piece
 * of middleware, and delegates request/response handling to the correct controller.
 */
abstract class BaseApplication implements
    ConsoleApplicationInterface,
    HttpApplicationInterface,
    PluginApplicationInterface
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
     * Constructor
     *
     * @param string $configDir The directory the bootstrap configuration is held in.
     * @param \Cake\Event\EventManagerInterface $eventManager Application event manager instance.
     */
    public function __construct(string $configDir, ?EventManagerInterface $eventManager = null)
    {
        $this->configDir = $configDir;
        $this->plugins = Plugin::getCollection();
        $this->_eventManager = $eventManager ?: EventManager::instance();
    }

    /**
     * @param \Cake\Http\MiddlewareQueue $middleware The middleware queue to set in your App Class
     * @return \Cake\Http\MiddlewareQueue
     */
    abstract public function middleware(MiddlewareQueue $middleware): MiddlewareQueue;

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
            $plugin = $this->makePlugin($name, $config);
        } else {
            $plugin = $name;
        }
        if (!$plugin instanceof PluginInterface) {
            throw new InvalidArgumentException(sprintf(
                "The `%s` plugin does not implement Cake\Core\PluginInterface.",
                get_class($plugin)
            ));
        }
        $this->plugins->add($plugin);

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
     * Create a plugin instance from a classname and configuration
     *
     * @param string $name The plugin classname
     * @param array $config Configuration options for the plugin
     * @return \Cake\Core\PluginInterface
     */
    protected function makePlugin(string $name, array $config): PluginInterface
    {
        $className = $name;
        if (strpos($className, '\\') === false) {
            $className = str_replace('/', '\\', $className) . '\\' . 'Plugin';
        }
        if (class_exists($className)) {
            $plugin = new $className($config);
            if (!$plugin instanceof PluginInterface) {
                throw new InvalidArgumentException(sprintf(
                    'The `%s` plugin does not implement Cake\Core\PluginInterface.',
                    get_class($plugin)
                ));
            }

            return $plugin;
        }

        if (!isset($config['path'])) {
            $config['path'] = $this->plugins->findPath($name);
        }
        $config['name'] = $name;

        return new BasePlugin($config);
    }

    /**
     * @inheritDoc
     */
    public function bootstrap(): void
    {
        require_once $this->configDir . '/bootstrap.php';
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
     * By default this will load `config/routes.php` for ease of use and backwards compatibility.
     *
     * @param \Cake\Routing\RouteBuilder $routes A route builder to add routes into.
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        // Only load routes if the router is empty
        if (!Router::routes()) {
            require $this->configDir . '/routes.php';
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
     * By default all commands in CakePHP, plugins and the application will be
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
     * Invoke the application.
     *
     * - Convert the PSR response into CakePHP equivalents.
     * - Create the controller that will handle this request.
     * - Invoke the controller.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @param \Psr\Http\Message\RequestHandlerInterface $handler The response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $this->getDispatcher()->dispatch($request);
    }

    /**
     * Get the ActionDispatcher.
     *
     * @return \Cake\Http\ActionDispatcher
     */
    protected function getDispatcher(): ActionDispatcher
    {
        return new ActionDispatcher(null, $this->getEventManager());
    }
}
