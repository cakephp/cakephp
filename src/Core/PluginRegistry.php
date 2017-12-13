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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Plugin Registry
 */
class PluginRegistry extends ObjectRegistry implements PluginRegistryInterface
{

    /**
     * Should resolve the classname for a given object type.
     *
     * @param string $class The class to resolve.
     * @return string|bool The resolved name or false for failure.
     */
    protected function _resolveClassName($class)
    {
        if (class_exists($class)) {
            return $class;
        }

        list($plugin, $name) = pluginSplit($class, true);

        if (empty($plugin)) {
            $class = $name . '\\' . 'Plugin';
        } else {
            $class = $plugin . $name;
        }

        return $class;
    }

    /**
     * Throw an exception when the requested object name is missing.
     *
     * @param string $class The class that is missing.
     * @param string $plugin The plugin $class is missing from.
     * @return void
     * @throws \Exception
     */
    protected function _throwMissingClassError($class, $plugin)
    {
        new RuntimeException(sprintf(
            'Plugin class `%s` not found.',
            $class
        ));
    }

    /**
     * Create an instance of a given classname.
     * This method should construct and do any other initialization logic
     * required.
     *
     * @param string $class The class to build.
     * @param string $alias The alias of the object.
     * @param array $config The Configuration settings for construction
     * @return mixed
     */
    protected function _create($class, $alias, $config)
    {
        $instance = new $class($config);

        if ((!$instance instanceof HttpApplicationInterface
            && !$instance instanceof ConsoleApplicationInterface)
            || !$instance instanceof PluginInterface) {
            throw new RuntimeException(sprintf(
                '`%s` is not a valid plugin object. It\'s not implementing `%s` or `%s`',
                get_class($instance)),
                HttpApplicationInterface::class,
                ConsoleApplicationInterface::class
            );
        }

        return $instance;
    }

    /**
     * Load all the application configuration and bootstrap logic.
     * Override this method to add additional bootstrap logic for your application.
     *
     * @return void
     */
    public function bootstrap()
    {
        foreach ($this as $plugin) {
            if ($plugin->isBootstrapEnabled()) {
                $plugin->bootstrap();
            }
        }
    }

    /**
     * Define the console commands for an application.
     *
     * @param \Cake\Console\CommandCollection $commands The CommandCollection to add commands into.
     * @return \Cake\Console\CommandCollection The updated collection.
     */
    public function console($commands)
    {
        foreach ($this as $plugin) {
            $commands = $plugin->console($commands);
        }

        return $commands;
    }

    /**
     * Define the routes for an application.
     * Use the provided RouteBuilder to define an application's routing.
     *
     * @param \Cake\Routing\RouteBuilder $routes A route builder to add routes into.
     * @return void
     */
    public function routes($routes)
    {
        foreach ($this as $plugin) {
            if ($plugin->isRouteLoadingEnabled()) {
                $plugin->routes($routes);
            }
        }
    }

    /**
     * Define the HTTP middleware layers for an application.
     *
     * @param \Cake\Http\MiddlewareQueue $middleware The middleware queue to set in your App Class
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware($middleware)
    {
        foreach ($this as $plugin) {
            $middleware->add($plugin);
        }

        return $middleware;
    }

    /**
     * Invoke the application.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @param \Psr\Http\Message\ResponseInterface $response The response
     * @param callable $next The next middleware
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        foreach ($this as $plugin) {
            $response = $plugin($request, $response, $next);
        }

        return $response;
    }
}
