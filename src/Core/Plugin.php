<?php
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

use Cake\Core\Exception\MissingPluginException;
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
     * @var array
     */
    protected static $_plugins = [];

    /**
     * Class loader instance
     *
     * @var \Cake\Core\ClassLoader
     */
    protected static $_loader;

    /**
     * Loads a plugin and optionally loads bootstrapping,
     * routing files or runs an initialization function.
     *
     * Plugins only need to be loaded if you want bootstrapping/routes/cli commands to
     * be exposed. If your plugin does not expose any of these features you do not need
     * to load them.
     *
     * This method does not configure any autoloaders. That must be done separately either
     * through composer, or your own code during config/bootstrap.php.
     *
     * ### Examples:
     *
     * `Plugin::load('DebugKit')`
     *
     * Will load the DebugKit plugin and will not load any bootstrap nor route files.
     * However, the plugin will be part of the framework default routes, and have its
     * CLI tools (if any) available for use.
     *
     * `Plugin::load('DebugKit', ['bootstrap' => true, 'routes' => true])`
     *
     * Will load the bootstrap.php and routes.php files.
     *
     * `Plugin::load('DebugKit', ['bootstrap' => false, 'routes' => true])`
     *
     * Will load routes.php file but not bootstrap.php
     *
     * `Plugin::load('FOC/Authenticate')`
     *
     * Will load plugin from `plugins/FOC/Authenticate`.
     *
     * It is also possible to load multiple plugins at once. Examples:
     *
     * `Plugin::load(['DebugKit', 'ApiGenerator'])`
     *
     * Will load the DebugKit and ApiGenerator plugins.
     *
     * `Plugin::load(['DebugKit', 'ApiGenerator'], ['bootstrap' => true])`
     *
     * Will load bootstrap file for both plugins
     *
     * ```
     *   Plugin::load([
     *     'DebugKit' => ['routes' => true],
     *     'ApiGenerator'
     *     ],
     *     ['bootstrap' => true])
     * ```
     *
     * Will only load the bootstrap for ApiGenerator and only the routes for DebugKit
     *
     * ### Configuration options
     *
     * - `bootstrap` - array - Whether or not you want the $plugin/config/bootstrap.php file loaded.
     * - `routes` - boolean - Whether or not you want to load the $plugin/config/routes.php file.
     * - `ignoreMissing` - boolean - Set to true to ignore missing bootstrap/routes files.
     * - `path` - string - The path the plugin can be found on. If empty the default plugin path (App.pluginPaths) will be used.
     * - `classBase` - The path relative to `path` which contains the folders with class files.
     *    Defaults to "src".
     * - `autoload` - boolean - Whether or not you want an autoloader registered. This defaults to false. The framework
     *   assumes you have configured autoloaders using composer. However, if your application source tree is made up of
     *   plugins, this can be a useful option.
     *
     * @param string|array $plugin name of the plugin to be loaded in CamelCase format or array or plugins to load
     * @param array $config configuration options for the plugin
     * @throws \Cake\Core\Exception\MissingPluginException if the folder for the plugin to be loaded is not found
     * @return void
     */
    public static function load($plugin, array $config = [])
    {
        if (is_array($plugin)) {
            foreach ($plugin as $name => $conf) {
                list($name, $conf) = is_numeric($name) ? [$conf, $config] : [$name, $conf];
                static::load($name, $conf);
            }

            return;
        }

        static::_loadConfig();

        $config += [
            'autoload' => false,
            'bootstrap' => false,
            'routes' => false,
            'classBase' => 'src',
            'ignoreMissing' => false
        ];

        if (!isset($config['path'])) {
            $config['path'] = Configure::read('plugins.' . $plugin);
        }

        if (empty($config['path'])) {
            $paths = App::path('Plugin');
            $pluginPath = str_replace('/', DIRECTORY_SEPARATOR, $plugin);
            foreach ($paths as $path) {
                if (is_dir($path . $pluginPath)) {
                    $config['path'] = $path . $pluginPath . DIRECTORY_SEPARATOR;
                    break;
                }
            }
        }

        if (empty($config['path'])) {
            throw new MissingPluginException(['plugin' => $plugin]);
        }

        $config['classPath'] = $config['path'] . $config['classBase'] . DIRECTORY_SEPARATOR;
        if (!isset($config['configPath'])) {
            $config['configPath'] = $config['path'] . 'config' . DIRECTORY_SEPARATOR;
        }

        static::$_plugins[$plugin] = $config;

        if ($config['autoload'] === true) {
            if (empty(static::$_loader)) {
                static::$_loader = new ClassLoader();
                static::$_loader->register();
            }
            static::$_loader->addNamespace(
                str_replace('/', '\\', $plugin),
                $config['path'] . $config['classBase'] . DIRECTORY_SEPARATOR
            );
            static::$_loader->addNamespace(
                str_replace('/', '\\', $plugin) . '\Test',
                $config['path'] . 'tests' . DIRECTORY_SEPARATOR
            );
        }

        if ($config['bootstrap'] === true) {
            static::bootstrap($plugin);
        }
    }

    /**
     * Load the plugin path configuration file.
     *
     * @return void
     */
    protected static function _loadConfig()
    {
        if (Configure::check('plugins')) {
            return;
        }
        $vendorFile = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'cakephp-plugins.php';
        if (!file_exists($vendorFile)) {
            $vendorFile = dirname(dirname(dirname(dirname(__DIR__)))) . DIRECTORY_SEPARATOR . 'cakephp-plugins.php';
            if (!file_exists($vendorFile)) {
                Configure::write(['plugins' => []]);

                return;
            }
        }

        $config = require $vendorFile;
        Configure::write($config);
    }

    /**
     * Will load all the plugins located in the default plugin folder.
     *
     * If passed an options array, it will be used as a common default for all plugins to be loaded
     * It is possible to set specific defaults for each plugins in the options array. Examples:
     *
     * ```
     *  Plugin::loadAll([
     *      ['bootstrap' => true],
     *      'DebugKit' => ['routes' => true],
     *  ]);
     * ```
     *
     * The above example will load the bootstrap file for all plugins, but for DebugKit it will only load the routes file
     * and will not look for any bootstrap script.
     *
     * If a plugin has been loaded already, it will not be reloaded by loadAll().
     *
     * @param array $options Options.
     * @return void
     * @throws \Cake\Core\Exception\MissingPluginException
     */
    public static function loadAll(array $options = [])
    {
        static::_loadConfig();
        $plugins = [];
        foreach (App::path('Plugin') as $path) {
            if (!is_dir($path)) {
                continue;
            }
            $dir = new DirectoryIterator($path);
            foreach ($dir as $dirPath) {
                if ($dirPath->isDir() && !$dirPath->isDot()) {
                    $plugins[] = $dirPath->getBasename();
                }
            }
        }
        if (Configure::check('plugins')) {
            $plugins = array_merge($plugins, array_keys(Configure::read('plugins')));
            $plugins = array_unique($plugins);
        }

        foreach ($plugins as $p) {
            $opts = isset($options[$p]) ? $options[$p] : null;
            if ($opts === null && isset($options[0])) {
                $opts = $options[0];
            }
            if (isset(static::$_plugins[$p])) {
                continue;
            }
            static::load($p, (array)$opts);
        }
    }

    /**
     * Returns the filesystem path for a plugin
     *
     * @param string $plugin name of the plugin in CamelCase format
     * @return string path to the plugin folder
     * @throws \Cake\Core\Exception\MissingPluginException if the folder for plugin was not found or plugin has not been loaded
     */
    public static function path($plugin)
    {
        if (empty(static::$_plugins[$plugin])) {
            throw new MissingPluginException(['plugin' => $plugin]);
        }

        return static::$_plugins[$plugin]['path'];
    }

    /**
     * Returns the filesystem path for plugin's folder containing class folders.
     *
     * @param string $plugin name of the plugin in CamelCase format.
     * @return string Path to the plugin folder container class folders.
     * @throws \Cake\Core\Exception\MissingPluginException If plugin has not been loaded.
     */
    public static function classPath($plugin)
    {
        if (empty(static::$_plugins[$plugin])) {
            throw new MissingPluginException(['plugin' => $plugin]);
        }

        return static::$_plugins[$plugin]['classPath'];
    }

    /**
     * Returns the filesystem path for plugin's folder containing config files.
     *
     * @param string $plugin name of the plugin in CamelCase format.
     * @return string Path to the plugin folder container config files.
     * @throws \Cake\Core\Exception\MissingPluginException If plugin has not been loaded.
     */
    public static function configPath($plugin)
    {
        if (empty(static::$_plugins[$plugin])) {
            throw new MissingPluginException(['plugin' => $plugin]);
        }

        return static::$_plugins[$plugin]['configPath'];
    }

    /**
     * Loads the bootstrapping files for a plugin, or calls the initialization setup in the configuration
     *
     * @param string $plugin name of the plugin
     * @return mixed
     * @see \Cake\Core\Plugin::load() for examples of bootstrap configuration
     */
    public static function bootstrap($plugin)
    {
        $config = static::$_plugins[$plugin];
        if ($config['bootstrap'] === false) {
            return false;
        }
        if ($config['bootstrap'] === true) {
            return static::_includeFile(
                $config['configPath'] . 'bootstrap.php',
                $config['ignoreMissing']
            );
        }
    }

    /**
     * Loads the routes file for a plugin, or all plugins configured to load their respective routes file.
     *
     * If you need fine grained control over how routes are loaded for plugins, you
     * can use {@see Cake\Routing\RouteBuilder::loadPlugin()}
     *
     * @param string|null $plugin name of the plugin, if null will operate on all
     *   plugins having enabled the loading of routes files.
     * @return bool
     */
    public static function routes($plugin = null)
    {
        if ($plugin === null) {
            foreach (static::loaded() as $p) {
                static::routes($p);
            }

            return true;
        }
        $config = static::$_plugins[$plugin];
        if ($config['routes'] === false) {
            return false;
        }

        return (bool)static::_includeFile(
            $config['configPath'] . 'routes.php',
            $config['ignoreMissing']
        );
    }

    /**
     * Returns true if the plugin $plugin is already loaded
     * If plugin is null, it will return a list of all loaded plugins
     *
     * @param string|null $plugin Plugin name.
     * @return bool|array Boolean true if $plugin is already loaded.
     *   If $plugin is null, returns a list of plugins that have been loaded
     */
    public static function loaded($plugin = null)
    {
        if ($plugin !== null) {
            return isset(static::$_plugins[$plugin]);
        }
        $return = array_keys(static::$_plugins);
        sort($return);

        return $return;
    }

    /**
     * Forgets a loaded plugin or all of them if first parameter is null
     *
     * @param string|null $plugin name of the plugin to forget
     * @return void
     */
    public static function unload($plugin = null)
    {
        if ($plugin === null) {
            static::$_plugins = [];
        } else {
            unset(static::$_plugins[$plugin]);
        }
    }

    /**
     * Include file, ignoring include error if needed if file is missing
     *
     * @param string $file File to include
     * @param bool $ignoreMissing Whether to ignore include error for missing files
     * @return mixed
     */
    protected static function _includeFile($file, $ignoreMissing = false)
    {
        if ($ignoreMissing && !is_file($file)) {
            return false;
        }

        return include $file;
    }
}
