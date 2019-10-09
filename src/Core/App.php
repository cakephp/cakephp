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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
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
 * @link https://book.cakephp.org/3.0/en/core-libraries/app.html
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
     * @return string|null Namespaced class name, null if the class is not found.
     */
    public static function className(string $class, string $type = '', string $suffix = ''): ?string
    {
        if (strpos($class, '\\') !== false) {
            return class_exists($class) ? $class : null;
        }

        [$plugin, $name] = pluginSplit($class);
        $base = $plugin ?: Configure::read('App.namespace');
        $base = str_replace('/', '\\', rtrim($base, '\\'));
        $fullname = '\\' . str_replace('/', '\\', $type . '\\' . $name) . $suffix;

        if (static::_classExistsInBase($fullname, $base)) {
            return $base . $fullname;
        }

        if ($plugin || !static::_classExistsInBase($fullname, 'Cake')) {
            return null;
        }

        return 'Cake' . $fullname;
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
    public static function shortName(string $class, string $type, string $suffix = ''): string
    {
        $class = str_replace('\\', '/', $class);
        $type = '/' . $type . '/';

        $pos = strrpos($class, $type);
        if ($pos === false) {
            return $class;
        }

        $pluginName = substr($class, 0, $pos);
        $name = substr($class, $pos + strlen($type));

        if ($suffix) {
            $name = substr($name, 0, -strlen($suffix));
        }

        $nonPluginNamespaces = [
            'Cake',
            str_replace('\\', '/', Configure::read('App.namespace')),
        ];
        if (in_array($pluginName, $nonPluginNamespaces, true)) {
            return $name;
        }

        return $pluginName . '.' . $name;
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
    protected static function _classExistsInBase(string $name, string $namespace): bool
    {
        return class_exists($namespace . $name);
    }

    /**
     * Used to read information stored path.
     *
     * If 1st character of $type argument is lower cased it will return the
     * value of `App.paths.$type` config.
     *
     * Example:
     *
     * ```
     * App::path('plugins');
     * ```
     *
     * Will return the value of `App.paths.plugins` config.
     *
     * ```
     * App::path('Model/Table');
     * ```
     *
     * Will return the path for tables `src/Model/Table`.
     *
     * @param string $type type of path
     * @param string|null $plugin This argument is deprecated.
     *   Use \Cake\Core\Plugin::classPath()/templatePath() instead for plugin paths.
     * @return array
     * @link https://book.cakephp.org/3.0/en/core-libraries/app.html#finding-paths-to-namespaces
     */
    public static function path(string $type, ?string $plugin = null): array
    {
        if (empty($plugin)) {
            if ($type[0] === strtolower($type[0])) {
                return (array)Configure::read('App.paths.' . $type);
            }

            return [APP . $type . DIRECTORY_SEPARATOR];
        }

        deprecationWarning(
            'Using App::path() with 2nd argument $plugin is deprecated.'
            . ' Use \Cake\Core\Plugin::classPath()/templatePath() instead.'
        );

        if ($type === 'templates') {
            return [Plugin::templatePath($plugin)];
        }

        return [Plugin::classPath($plugin) . $type . DIRECTORY_SEPARATOR];
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
     * @return string[] Full path to package
     */
    public static function core(string $type): array
    {
        if ($type === 'templates') {
            return [CORE_PATH . 'templates' . DIRECTORY_SEPARATOR];
        }

        return [CAKE . str_replace('/', DIRECTORY_SEPARATOR, $type) . DIRECTORY_SEPARATOR];
    }
}
