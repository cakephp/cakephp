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

use ReflectionClass;

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
     * The path to this plugin.
     *
     * @var string
     */
    protected $path;

    /**
     * Constructor
     *
     * @param array $options Options
     */
    public function __construct(array $options = [])
    {
        foreach (PluginCollection::VALID_HOOKS as $key) {
            if (isset($options[$key])) {
                $this->{"{$key}Enabled"} = (bool)$options[$key];
            }
        }
        if (isset($options['path'])) {
            $this->path = $options['path'];
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
     * {@inheritDoc}
     */
    public function getPath()
    {
        if ($this->path) {
            return $this->path;
        }
        $reflection = new ReflectionClass($this);
        $path = dirname($reflection->getFileName());

        // Trim off src
        if (substr($path, -3) === 'src') {
            $path = substr($path, 0, -3);
        }

        return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
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
    public function disableMiddleware()
    {
        $this->middlewareEnabled = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enableMiddleware()
    {
        $this->middlewareEnabled = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function disableConsole()
    {
        $this->consoleEnabled = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enableConsole()
    {
        $this->consoleEnabled = true;

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
