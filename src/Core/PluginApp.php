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

/**
 * Base Plugin Class
 *
 * Every plugin should extends from this class or implement the interfaces and
 * include a plugin class in it's src root folder.
 */
class PluginApp implements ConsoleApplicationInterface, HttpApplicationInterface, PluginInterface
{

    /**
     * Do bootstrapping or not
     *
     * @var bool
     */
    protected $doBootstrap = true;

    /**
     * Load routes or not
     *
     * @var bool
     */
    protected $loadRoutes = true;

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
     * {@inheritdoc}
     */
    public function disableRoutes()
    {
        $this->loadRoutes = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enableRoutes()
    {
        $this->loadRoutes = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function disableBootstrap()
    {
        $this->doBootstrap = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enableBootstrap()
    {
        $this->doBootstrap = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isRouteLoadingEnabled()
    {
        return $this->loadRoutes;
    }

    /**
     * {@inheritdoc}
     */
    public function isBootstrapEnabled()
    {
        return $this->doBootstrap;
    }

    /**
     * {@inheritdoc}
     */
    public function routes($routes)
    {
        $bootstrap = __DIR__ . 'config' . DS . 'routes.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
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

    /**
     * {@inheritdoc}
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        return $next($request, $response);
    }
}
