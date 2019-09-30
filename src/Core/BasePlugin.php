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

use InvalidArgumentException;
use ReflectionClass;

/**
 * Base Plugin Class
 *
 * Every plugin should extends from this class or implement the interfaces and
 * include a plugin class in it's src root folder.
 */
class BasePlugin implements PluginInterface
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
     * The class path for this plugin.
     *
     * @var string
     */
    protected $classPath;

    /**
     * The config path for this plugin.
     *
     * @var string
     */
    protected $configPath;

    /**
     * The name of this plugin
     *
     * @var string
     */
    protected $name;

    /**
     * Constructor
     *
     * @param array $options Options
     */
    public function __construct(array $options = [])
    {
        foreach (static::VALID_HOOKS as $key) {
            if (isset($options[$key])) {
                $this->{"{$key}Enabled"} = (bool)$options[$key];
            }
        }
        foreach (['name', 'path', 'classPath', 'configPath'] as $path) {
            if (isset($options[$path])) {
                $this->{$path} = $options[$path];
            }
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
        if ($this->name) {
            return $this->name;
        }
        $parts = explode('\\', get_class($this));
        array_pop($parts);
        $this->name = implode('/', $parts);

        return $this->name;
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
        $this->path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return $this->path;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigPath()
    {
        if ($this->configPath) {
            return $this->configPath;
        }
        $path = $this->getPath();

        return $path . 'config' . DIRECTORY_SEPARATOR;
    }

    /**
     * {@inheritDoc}
     */
    public function getClassPath()
    {
        if ($this->classPath) {
            return $this->classPath;
        }
        $path = $this->getPath();

        return $path . 'src' . DIRECTORY_SEPARATOR;
    }

    /**
     * {@inheritdoc}
     */
    public function enable($hook)
    {
        $this->checkHook($hook);
        $this->{"{$hook}Enabled}"} = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function disable($hook)
    {
        $this->checkHook($hook);
        $this->{"{$hook}Enabled"} = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled($hook)
    {
        $this->checkHook($hook);

        return $this->{"{$hook}Enabled"} === true;
    }

    /**
     * Check if a hook name is valid
     *
     * @param string $hook The hook name to check
     * @throws \InvalidArgumentException on invalid hooks
     * @return void
     */
    protected function checkHook($hook)
    {
        if (!in_array($hook, static::VALID_HOOKS)) {
            throw new InvalidArgumentException(
                "`$hook` is not a valid hook name. Must be one of " . implode(', ', static::VALID_HOOKS)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function routes($routes)
    {
        $path = $this->getConfigPath() . 'routes.php';
        if (file_exists($path)) {
            require $path;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function bootstrap(PluginApplicationInterface $app)
    {
        $bootstrap = $this->getConfigPath() . 'bootstrap.php';
        if (file_exists($bootstrap)) {
            require $bootstrap;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function console($commands)
    {
        return $commands->addMany($commands->discoverPlugin($this->getName()));
    }

    /**
     * {@inheritdoc}
     */
    public function middleware($middleware)
    {
        return $middleware;
    }
}
