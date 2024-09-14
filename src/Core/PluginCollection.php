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

use Cake\Core\Exception\CakeException;
use Cake\Core\Exception\MissingPluginException;
use Cake\Utility\Hash;
use Countable;
use Generator;
use InvalidArgumentException;
use Iterator;

/**
 * Plugin Collection
 *
 * Holds onto plugin objects loaded into an application, and
 * provides methods for iterating, and finding plugins based
 * on criteria.
 *
 * This class implements the Iterator interface to allow plugins
 * to be iterated, handling the situation where a plugin's hook
 * method (usually bootstrap) loads another plugin during iteration.
 *
 * While its implementation supported nested iteration it does not
 * support using `continue` or `break` inside loops.
 *
 * @template-implements \Iterator<string, \Cake\Core\PluginInterface>
 */
class PluginCollection implements Iterator, Countable
{
    /**
     * Plugin list
     *
     * @var array<\Cake\Core\PluginInterface>
     */
    protected array $plugins = [];

    /**
     * Names of plugins
     *
     * @var list<string>
     */
    protected array $names = [];

    /**
     * Iterator position stack.
     *
     * @var array<int>
     */
    protected array $positions = [];

    /**
     * Loop depth
     *
     * @var int
     */
    protected int $loopDepth = -1;

    /**
     * Constructor
     *
     * @param array<\Cake\Core\PluginInterface> $plugins The map of plugins to add to the collection.
     */
    public function __construct(array $plugins = [])
    {
        foreach ($plugins as $plugin) {
            $this->add($plugin);
        }
        PluginConfig::loadInstallerConfig();
    }

    /**
     * Add plugins from config array.
     *
     * @param array $config Configuration array. For e.g.:
     *   ```
     *   [
     *       'Company/TestPluginThree',
     *       'TestPlugin' => ['onlyDebug' => true, 'onlyCli' => true],
     *       'Nope' => ['optional' => true],
     *       'Named' => ['routes' => false, 'bootstrap' => false],
     *   ]
     *   ```
     * @return void
     */
    public function addFromConfig(array $config): void
    {
        $debug = Configure::read('debug');
        $cli = PHP_SAPI === 'cli';

        foreach (Hash::normalize($config) as $name => $options) {
            $options = (array)$options;
            $onlyDebug = $options['onlyDebug'] ?? false;
            $onlyCli = $options['onlyCli'] ?? false;
            $optional = $options['optional'] ?? false;

            if (
                ($onlyDebug && !$debug)
                || ($onlyCli && !$cli)
            ) {
                continue;
            }

            try {
                $plugin = $this->create($name, $options);
                $this->add($plugin);
            } catch (MissingPluginException $e) {
                if (!$optional) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Locate a plugin path by looking at configuration data.
     *
     * This will use the `plugins` Configure key, and fallback to enumerating `App::path('plugins')`
     *
     * This method is not part of the official public API as plugins with
     * no plugin class are being phased out.
     *
     * @param string $name The plugin name to locate a path for.
     * @return string
     * @throws \Cake\Core\Exception\MissingPluginException when a plugin path cannot be resolved.
     * @internal
     */
    public function findPath(string $name): string
    {
        // Ensure plugin config is loaded each time. This is necessary primarily
        // for testing because the Configure::clear() call in TestCase::tearDown()
        // wipes out all configuration including plugin paths config.
        PluginConfig::loadInstallerConfig();

        $path = Configure::read('plugins.' . $name);
        if ($path) {
            return $path;
        }

        $pluginPath = str_replace('/', DIRECTORY_SEPARATOR, $name);
        $paths = App::path('plugins');
        foreach ($paths as $path) {
            if (is_dir($path . $pluginPath)) {
                return $path . $pluginPath . DIRECTORY_SEPARATOR;
            }
        }

        throw new MissingPluginException(['plugin' => $name]);
    }

    /**
     * Add a plugin to the collection
     *
     * Plugins will be keyed by their names.
     *
     * @param \Cake\Core\PluginInterface $plugin The plugin to load.
     * @return $this
     */
    public function add(PluginInterface $plugin)
    {
        $name = $plugin->getName();
        if (isset($this->plugins[$name])) {
            throw new CakeException(sprintf('Plugin named `%s` is already loaded', $name));
        }

        $this->plugins[$name] = $plugin;
        $this->names = array_keys($this->plugins);

        return $this;
    }

    /**
     * Remove a plugin from the collection if it exists.
     *
     * @param string $name The named plugin.
     * @return $this
     */
    public function remove(string $name)
    {
        unset($this->plugins[$name]);
        $this->names = array_keys($this->plugins);

        return $this;
    }

    /**
     * Remove all plugins from the collection
     *
     * @return $this
     */
    public function clear()
    {
        $this->plugins = [];
        $this->names = [];
        $this->positions = [];
        $this->loopDepth = -1;

        return $this;
    }

    /**
     * Check whether the named plugin exists in the collection.
     *
     * @param string $name The named plugin.
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->plugins[$name]);
    }

    /**
     * Get the a plugin by name.
     *
     * If a plugin isn't already loaded it will be autoloaded on first access
     * and that plugins loaded this way may miss some hook methods.
     *
     * @param string $name The plugin to get.
     * @return \Cake\Core\PluginInterface The plugin.
     * @throws \Cake\Core\Exception\MissingPluginException when unknown plugins are fetched.
     */
    public function get(string $name): PluginInterface
    {
        if ($this->has($name)) {
            return $this->plugins[$name];
        }

        $plugin = $this->create($name);
        $this->add($plugin);

        return $plugin;
    }

    /**
     * Create a plugin instance from a name/classname and configuration.
     *
     * @param string $name The plugin name or classname
     * @param array<string, mixed> $config Configuration options for the plugin.
     * @return \Cake\Core\PluginInterface
     * @throws \Cake\Core\Exception\MissingPluginException When plugin instance could not be created.
     * @throws \InvalidArgumentException When class name cannot be found.
     * @psalm-param class-string<\Cake\Core\PluginInterface>|string $name
     */
    public function create(string $name, array $config = []): PluginInterface
    {
        if ($name === '') {
            throw new CakeException('Cannot create a plugin with empty name');
        }

        if (str_contains($name, '\\')) {
            if (!class_exists($name)) {
                throw new InvalidArgumentException(sprintf('Class `%s` does not exist.', $name));
            }

            /** @var \Cake\Core\PluginInterface */
            return new $name($config);
        }

        $config += ['name' => $name];
        $namespace = str_replace('/', '\\', $name);

        $className = $namespace . '\\' . 'Plugin';
        // Check for [Vendor/]Foo/Plugin class
        if (!class_exists($className)) {
            $pos = strpos($name, '/');
            if ($pos === false) {
                $className = $namespace . '\\' . $name . 'Plugin';
            } else {
                $className = $namespace . '\\' . substr($name, $pos + 1) . 'Plugin';
            }

            // Check for [Vendor/]Foo/FooPlugin
            if (!class_exists($className)) {
                $className = BasePlugin::class;
                if (empty($config['path'])) {
                    $config['path'] = $this->findPath($name);
                }
            }
        }

        /** @var class-string<\Cake\Core\PluginInterface> $className */
        return new $className($config);
    }

    /**
     * Implementation of Countable.
     *
     * Get the number of plugins in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->plugins);
    }

    /**
     * Part of Iterator Interface
     *
     * @return void
     */
    public function next(): void
    {
        $this->positions[$this->loopDepth]++;
    }

    /**
     * Part of Iterator Interface
     *
     * @return string
     */
    public function key(): string
    {
        return $this->names[$this->positions[$this->loopDepth]];
    }

    /**
     * Part of Iterator Interface
     *
     * @return \Cake\Core\PluginInterface
     */
    public function current(): PluginInterface
    {
        $position = $this->positions[$this->loopDepth];
        $name = $this->names[$position];

        return $this->plugins[$name];
    }

    /**
     * Part of Iterator Interface
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->positions[] = 0;
        $this->loopDepth += 1;
    }

    /**
     * Part of Iterator Interface
     *
     * @return bool
     */
    public function valid(): bool
    {
        $valid = isset($this->names[$this->positions[$this->loopDepth]]);
        if (!$valid) {
            array_pop($this->positions);
            $this->loopDepth -= 1;
        }

        return $valid;
    }

    /**
     * Filter the plugins to those with the named hook enabled.
     *
     * @param string $hook The hook to filter plugins by
     * @return \Generator<\Cake\Core\PluginInterface> A generator containing matching plugins.
     * @throws \InvalidArgumentException on invalid hooks
     */
    public function with(string $hook): Generator
    {
        if (!in_array($hook, PluginInterface::VALID_HOOKS, true)) {
            throw new InvalidArgumentException(sprintf('The `%s` hook is not a known plugin hook.', $hook));
        }
        foreach ($this as $plugin) {
            if ($plugin->isEnabled($hook)) {
                yield $plugin;
            }
        }
    }
}
