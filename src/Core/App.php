<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

/**
 * App is responsible for resource location, and path management.
 *
 * ### Adding paths
 *
 * Additional paths for Templates and Plugins are configured with Configure now. See config/app.php for an
 * example. The `App.paths.plugins` and `App.paths.templates` variables are used to configure paths for plugins
 * and templates respectively. All class based resources should be mapped using your application's autoloader.
 *
 * ### Inspecting loaded paths
 *
 * You can inspect the currently loaded paths using `App::path('Controller')` for example to see loaded
 * controller paths.
 *
 * It is also possible to inspect paths for plugin classes, for instance, to get
 * the path to a plugin's helpers you would call `App::path('View/Helper', 'MyPlugin')`
 *
 * ### Locating plugins
 *
 * Plugins can be located with App as well. Using Plugin::path('DebugKit') for example, will
 * give you the full path to the DebugKit plugin.
 *
 * @link http://book.cakephp.org/3.0/en/core-libraries/app.html
 */
class App
{

    /**
     * Return the class name namespaced. This method checks if the class is defined on the
     * application/plugin, otherwise try to load from the CakePHP core
     *
     * @param string $class Class name
     * @param string $type Type of class
     * @param string $suffix Class name suffix
     * @return bool|string False if the class is not found or namespaced class name
     */
    public static function className($class, $type = '', $suffix = '')
    {
        if (strpos($class, '\\') !== false) {
            return $class;
        }

        list($plugin, $name) = pluginSplit($class);
        $base = $plugin ?: Configure::read('App.namespace');
        $base = str_replace('/', '\\', rtrim($base, '\\'));
        $fullname = '\\' . str_replace('/', '\\', $type . '\\' . $name) . $suffix;

        if (static::_classExistsInBase($fullname, $base)) {
            return $base . $fullname;
        }
        if ($plugin) {
            return false;
        }
        if (static::_classExistsInBase($fullname, 'Cake')) {
            return 'Cake' . $fullname;
        }
        return false;
    }

    /**
     * Returns the plugin split name of a class
     *
     * Examples:
     *
     * ```
     * App::shortName(
     *     'SomeVendor\SomePlugin\Controller\Component\TestComponent',
     *     'Controller/Component',
     *     'Component'
     * )
     * ```
     *
     * Returns: SomeVendor/SomePlugin.Test
     *
     * ```
     * App::shortName(
     *     'SomeVendor\SomePlugin\Controller\Component\Subfolder\TestComponent',
     *     'Controller/Component',
     *     'Component'
     * )
     * ```
     *
     * Returns: SomeVendor/SomePlugin.Subfolder/Test
     *
     * ```
     * App::shortName(
     *     'Cake\Controller\Component\AuthComponent',
     *     'Controller/Component',
     *     'Component'
     * )
     * ```
     *
     * Returns: Auth
     *
     * @param string $class Class name
     * @param string $type Type of class
     * @param string $suffix Class name suffix
     * @return string Plugin split name of class
     */
    public static function shortName($class, $type = '', $suffix = '')
    {
        // Split class in namespace parts
        $namespaceParts = explode('\\', $class);
        $typeParts = explode('/', $type);
        $typeOffset = null;
        $namespacePartCount = count($namespaceParts);
        $typePartCount = count($typeParts);

        // Determine offset of the type
        for ($index = 0; $index < $namespacePartCount; $index++) {
            // Continue if the current slice is not of the type
            if (array_slice($namespaceParts, $index, $typePartCount) !== $typeParts) {
                continue;
            }

            $typeOffset = $index;
        }

        // Get plugin namespace from the namespace part before the type
        $pluginNamespace = implode('\\', array_slice($namespaceParts, 0, $typeOffset));

        // Get name from the namespace part after the type
        $name = implode('/', array_slice($namespaceParts, $typeOffset + $typePartCount));
        // Remove the suffix if it has been set
        if ($suffix) {
            $name = substr($name, 0, 0 - strlen($suffix));
        }

        // Ignore CakePHP and app namespaces
        $ignoredNamespaces = [
            'Cake',
            Configure::read('App.namespace')
        ];
        if (in_array($pluginNamespace, $ignoredNamespaces)) {
            $pluginNamespace = '';
        }

        // Convert plugin namespace to plugin name
        $pluginName = implode('/', explode('\\', $pluginNamespace));

        // Return plugin split formatted name with plugin name and class name
        return implode('.', array_filter(pluginSplit($name, true, $pluginName)));
    }

    /**
     * _classExistsInBase
     *
     * Test isolation wrapper
     *
     * @param string $name Class name.
     * @param string $namespace Namespace.
     * @return bool
     */
    protected static function _classExistsInBase($name, $namespace)
    {
        return class_exists($namespace . $name);
    }

    /**
     * Used to read information stored path
     *
     * Usage:
     *
     * ```
     * App::path('Plugin');
     * ```
     *
     * Will return the configured paths for plugins. This is a simpler way to access
     * the `App.paths.plugins` configure variable.
     *
     * ```
     * App::path('Model/Datasource', 'MyPlugin');
     * ```
     *
     * Will return the path for datasources under the 'MyPlugin' plugin.
     *
     * @param string $type type of path
     * @param string $plugin name of plugin
     * @return array
     * @link http://book.cakephp.org/3.0/en/core-libraries/app.html#finding-paths-to-namespaces
     */
    public static function path($type, $plugin = null)
    {
        if ($type === 'Plugin') {
            return (array)Configure::read('App.paths.plugins');
        }
        if (empty($plugin) && $type === 'Locale') {
            return (array)Configure::read('App.paths.locales');
        }
        if (empty($plugin) && $type === 'Template') {
            return (array)Configure::read('App.paths.templates');
        }
        if (!empty($plugin)) {
            return [Plugin::classPath($plugin) . $type . DS];
        }
        return [APP . $type . DS];
    }

    /**
     * Returns the full path to a package inside the CakePHP core
     *
     * Usage:
     *
     * ```
     * App::core('Cache/Engine');
     * ```
     *
     * Will return the full path to the cache engines package.
     *
     * @param string $type Package type.
     * @return array Full path to package
     */
    public static function core($type)
    {
        return [CAKE . str_replace('/', DS, $type) . DS];
    }
}
