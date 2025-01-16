<?php
declare(strict_types=1);

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

use Cake\Console\CommandCollection;
use Cake\Event\EventManagerInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
use Closure;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Base Plugin Class
 *
 * Every plugin should extend from this class or implement the interfaces and
 * include a plugin class in its src root folder.
 */
class BasePlugin implements PluginInterface
{
    /**
     * Do bootstrapping or not
     *
     * @var bool
     */
    protected bool $bootstrapEnabled = true;

    /**
     * Console middleware
     *
     * @var bool
     */
    protected bool $consoleEnabled = true;

    /**
     * Enable middleware
     *
     * @var bool
     */
    protected bool $middlewareEnabled = true;

    /**
     * Register container services
     *
     * @var bool
     */
    protected bool $servicesEnabled = true;

    /**
     * Load routes or not
     *
     * @var bool
     */
    protected bool $routesEnabled = true;

    /**
     * Load events or not
     *
     * @var bool
     */
    protected bool $eventsEnabled = true;

    /**
     * The path to this plugin.
     *
     * @var string|null
     */
    protected ?string $path = null;

    /**
     * The class path for this plugin.
     *
     * @var string|null
     */
    protected ?string $classPath = null;

    /**
     * The config path for this plugin.
     *
     * @var string|null
     */
    protected ?string $configPath = null;

    /**
     * The templates path for this plugin.
     *
     * @var string|null
     */
    protected ?string $templatePath = null;

    /**
     * The name of this plugin
     *
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * Constructor
     *
     * @param array<string, mixed> $options Options
     */
    public function __construct(array $options = [])
    {
        foreach (static::VALID_HOOKS as $key) {
            if (isset($options[$key])) {
                $this->{"{$key}Enabled"} = (bool)$options[$key];
            }
        }
        foreach (['name', 'path', 'classPath', 'configPath', 'templatePath'] as $path) {
            if (isset($options[$path])) {
                $this->{$path} = $options[$path];
            }
        }

        $this->initialize();
    }

    /**
     * Initialization hook called from constructor.
     *
     * @return void
     */
    public function initialize(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        if ($this->name !== null) {
            return $this->name;
        }
        $parts = explode('\\', static::class);
        array_pop($parts);

        return $this->name = implode('/', $parts);
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        if ($this->path !== null) {
            return $this->path;
        }
        $reflection = new ReflectionClass($this);
        $path = dirname((string)$reflection->getFileName());

        // Trim off src
        if (str_ends_with($path, 'src')) {
            $path = substr($path, 0, -3);
        }

        return $this->path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * @inheritDoc
     */
    public function getConfigPath(): string
    {
        if ($this->configPath !== null) {
            return $this->configPath;
        }
        $path = $this->getPath();

        return $path . 'config' . DIRECTORY_SEPARATOR;
    }

    /**
     * @inheritDoc
     */
    public function getClassPath(): string
    {
        if ($this->classPath !== null) {
            return $this->classPath;
        }
        $path = $this->getPath();

        return $path . 'src' . DIRECTORY_SEPARATOR;
    }

    /**
     * @inheritDoc
     */
    public function getTemplatePath(): string
    {
        if ($this->templatePath !== null) {
            return $this->templatePath;
        }
        $path = $this->getPath();

        return $this->templatePath = $path . 'templates' . DIRECTORY_SEPARATOR;
    }

    /**
     * @inheritDoc
     */
    public function enable(string $hook)
    {
        $this->checkHook($hook);
        $this->{"{$hook}Enabled"} = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function disable(string $hook)
    {
        $this->checkHook($hook);
        $this->{"{$hook}Enabled"} = false;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(string $hook): bool
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
    protected function checkHook(string $hook): void
    {
        if (!in_array($hook, static::VALID_HOOKS, true)) {
            throw new InvalidArgumentException(sprintf(
                '`%s` is not a valid hook name. Must be one of `%s.`',
                $hook,
                implode(', ', static::VALID_HOOKS),
            ));
        }
    }

    /**
     * @inheritDoc
     */
    public function routes(RouteBuilder $routes): void
    {
        $path = $this->getConfigPath() . 'routes.php';
        if (is_file($path)) {
            $return = require $path;
            if ($return instanceof Closure) {
                $return($routes);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        $bootstrap = $this->getConfigPath() . 'bootstrap.php';
        if (is_file($bootstrap)) {
            require $bootstrap;
        }
    }

    /**
     * @inheritDoc
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        return $commands->addMany($commands->discoverPlugin($this->getName()));
    }

    /**
     * @inheritDoc
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue;
    }

    /**
     * Register container services for this plugin.
     *
     * @param \Cake\Core\ContainerInterface $container The container to add services to.
     * @return void
     */
    public function services(ContainerInterface $container): void
    {
    }

    /**
     * Register application events.
     *
     * @param \Cake\Event\EventManagerInterface $eventManager The global event manager to register listeners on
     * @return \Cake\Event\EventManagerInterface
     */
    public function events(EventManagerInterface $eventManager): EventManagerInterface
    {
        return $eventManager;
    }
}
