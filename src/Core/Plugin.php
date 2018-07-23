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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use DirectoryIterator;

/**
 * Plugin is used to load and locate plugins.
 *
 * It also can retrieve plugin paths and load their bootstrap and routes files.
 *
 * @link https://book.cakephp.org/3.0/en/plugins.html
 */
class Plugin
{
    /**
     * Holds a list of all loaded plugins and their configuration
     *
     * @var \Cake\Core\PluginCollection|null
     */
    protected static $plugins;

    /**
     * Class loader instance
     *
     * @var \Cake\Core\ClassLoader
     */
    protected static $_loader;

    /**
     * Returns the filesystem path for a plugin
     *
     * @param string $name name of the plugin in CamelCase format
     * @return string path to the plugin folder
     * @throws \Cake\Core\Exception\MissingPluginException if the folder for plugin was not found or plugin has not been loaded
     */
    public static function path(string $name): string
    {
        $plugin = static::getCollection()->get($name);

        return $plugin->getPath();
    }

    /**
     * Returns the filesystem path for plugin's folder containing class folders.
     *
     * @param string $name name of the plugin in CamelCase format.
     * @return string Path to the plugin folder container class folders.
     * @throws \Cake\Core\Exception\MissingPluginException If plugin has not been loaded.
     */
    public static function classPath(string $name): string
    {
        $plugin = static::getCollection()->get($name);

        return $plugin->getClassPath();
    }

    /**
     * Returns the filesystem path for plugin's folder containing config files.
     *
     * @param string $name name of the plugin in CamelCase format.
     * @return string Path to the plugin folder container config files.
     * @throws \Cake\Core\Exception\MissingPluginException If plugin has not been loaded.
     */
    public static function configPath(string $name): string
    {
        $plugin = static::getCollection()->get($name);

        return $plugin->getConfigPath();
    }

    /**
     * Loads the bootstrapping files for a plugin, or calls the initialization setup in the configuration
     *
     * @param string $name name of the plugin
     * @return void
     * @see \Cake\Core\Plugin::load() for examples of bootstrap configuration
     * @deprecated 3.7 This method will be removed in 4.0.0.
     */
    public static function bootstrap(string $name): void
    {
        deprecationWarning(
            'Plugin::bootstrap() is deprecated. ' .
            'This method will be removed in 4.0.0.'
        );
        $plugin = static::getCollection()->get($name);
        if (!$plugin->isEnabled('bootstrap')) {
            return;
        }
        // Disable bootstrapping for this plugin as it will have
        // been bootstrapped.
        $plugin->disable('bootstrap');

        static::_includeFile(
            $plugin->getConfigPath() . 'bootstrap.php',
            true
        );
    }

    /**
     * Returns true if the plugin $plugin is already loaded.
     *
     * @param string $plugin Plugin name.
     * @return bool
     */
    public static function isLoaded(string $plugin): bool
    {
        return static::getCollection()->has($plugin);
    }

    /**
     * Returns a list of all loaded plugins.
     *
     * @return array
     */
    public static function loaded(): array
    {
        $names = [];
        foreach (static::getCollection() as $plugin) {
            $names[] = $plugin->getName();
        }
        sort($names);

        return $names;
    }

    /**
     * Forgets a loaded plugin or all of them if first parameter is null
     *
     * @param string|null $plugin name of the plugin to forget
     * @return void
     */
    public static function unload(?string $plugin = null): void
    {
        if ($plugin === null) {
            static::$plugins = null;
        } else {
            static::getCollection()->remove($plugin);
        }
    }

    /**
     * Include file, ignoring include error if needed if file is missing
     *
     * @param string $file File to include
     * @param bool $ignoreMissing Whether to ignore include error for missing files
     * @return mixed
     */
    protected static function _includeFile(string $file, bool $ignoreMissing = false)
    {
        if ($ignoreMissing && !is_file($file)) {
            return false;
        }

        return include $file;
    }

    /**
     * Get the shared plugin collection.
     *
     * @internal
     * @return \Cake\Core\PluginCollection
     */
    public static function getCollection()
    {
        if (!isset(static::$plugins)) {
            static::$plugins = new PluginCollection();
        }

        return static::$plugins;
    }
}
