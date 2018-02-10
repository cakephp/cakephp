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

/**
 * Base Plugin Class
 *
 * Every plugin should extends from this class or implement the interfaces and
 * include a plugin class in it's src root folder.
 */
class PluginApp implements PluginInterface
{

    /**
     * Do bootstrapping or not
     *
     * @var bool
     */
    protected $bootstrapEnabled = true;

    /**
     * Load routes or not
     *
     * @var bool
     */
    protected $routesEnabled = true;

    /**
     * Enable middleware
     *
     * @var bool
     */
    protected $middlewareEnabled = true;

    /**
     * Console middleware
     *
     * @var bool
     */
    protected $consoleEnabled = true;

    /**
     * Constructor
     *
     * @param array $options Options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['bootstrap'])) {
            $this->disableBootstrap((bool)$options['bootstrap']);
        }

        if (isset($options['routes'])) {
            (bool)$options['routes'] ? $this->enableRoutes() : $this->disableRoutes();
        }

        $this->initialize();
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        $parts = explode('\\', get_class($this));
        array_pop($parts);

        return implode('/', $parts);
    }

    /**
     * {@inheritdoc}
     */
    public function disableRoutes()
    {
        $this->routesEnabled = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enableRoutes()
    {
        $this->routesEnabled = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function disableBootstrap()
    {
        $this->bootstrapEnabled = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enableBootstrap()
    {
        $this->bootstrapEnabled = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isRoutesEnabled()
    {
        return $this->routesEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function isBootstrapEnabled()
    {
        return $this->bootstrapEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function isConsoleEnabled()
    {
        return $this->consoleEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function isMiddlewareEnabled()
    {
        return $this->middlewareEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function routes($routes)
    {
        $routes = __DIR__ . 'config' . DS . 'routes.php';
        if (file_exists($routes)) {
            require_once $routes;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function bootstrap()
    {
        $bootstrap = __DIR__ . 'config' . DS . 'bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function console($commands)
    {
        return $commands;
    }

    /**
     * {@inheritdoc}
     */
    public function middleware($middleware)
    {
        return $middleware;
    }
}
