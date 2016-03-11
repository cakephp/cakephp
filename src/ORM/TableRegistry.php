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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use Cake\ORM\Locator\LocatorInterface;

/**
 * Provides a registry/factory for Table objects.
 *
 * This registry allows you to centralize the configuration for tables
 * their connections and other meta-data.
 *
 * ### Configuring instances
 *
 * You may need to configure your table objects, using TableRegistry you can
 * centralize configuration. Any configuration set before instances are created
 * will be used when creating instances. If you modify configuration after
 * an instance is made, the instances *will not* be updated.
 *
 * ```
 * TableRegistry::config('Users', ['table' => 'my_users']);
 * ```
 *
 * Configuration data is stored *per alias* if you use the same table with
 * multiple aliases you will need to set configuration multiple times.
 *
 * ### Getting instances
 *
 * You can fetch instances out of the registry using get(). One instance is stored
 * per alias. Once an alias is populated the same instance will always be returned.
 * This is used to make the ORM use less memory and help make cyclic references easier
 * to solve.
 *
 * ```
 * $table = TableRegistry::get('Users', $config);
 * ```
 *
 */
class TableRegistry
{

    /**
     * LocatorInterface implementation instance.
     *
     * @var \Cake\ORM\Locator\LocatorInterface
     */
    protected static $_locator;

    /**
     * Default LocatorInterface implementation class.
     *
     * @var string
     */
    protected static $_defaultLocatorClass = 'Cake\ORM\Locator\TableLocator';

    /**
     * Sets and returns a singleton instance of LocatorInterface implementation.
     *
     * @param \Cake\ORM\Locator\LocatorInterface|null $locator Instance of a locator to use.
     * @return \Cake\ORM\Locator\LocatorInterface
     */
    public static function locator(LocatorInterface $locator = null)
    {
        if ($locator) {
            static::$_locator = $locator;
        }

        if (!static::$_locator) {
            static::$_locator = new static::$_defaultLocatorClass;
        }

        return static::$_locator;
    }

    /**
     * Stores a list of options to be used when instantiating an object
     * with a matching alias.
     *
     * @param string|null $alias Name of the alias
     * @param array|null $options list of options for the alias
     * @return array The config data.
     */
    public static function config($alias = null, $options = null)
    {
        return static::locator()->config($alias, $options);
    }

    /**
     * Get a table instance from the registry.
     *
     * See options specification in {@link TableLocator::get()}.
     *
     * @param string $alias The alias name you want to get.
     * @param array $options The options you want to build the table with.
     * @return \Cake\ORM\Table
     */
    public static function get($alias, array $options = [])
    {
        return static::locator()->get($alias, $options);
    }

    /**
     * Check to see if an instance exists in the registry.
     *
     * @param string $alias The alias to check for.
     * @return bool
     */
    public static function exists($alias)
    {
        return static::locator()->exists($alias);
    }

    /**
     * Set an instance.
     *
     * @param string $alias The alias to set.
     * @param \Cake\ORM\Table $object The table to set.
     * @return \Cake\ORM\Table
     */
    public static function set($alias, Table $object)
    {
        return static::locator()->set($alias, $object);
    }

    /**
     * Removes an instance from the registry.
     *
     * @param string $alias The alias to remove.
     * @return void
     */
    public static function remove($alias)
    {
        static::locator()->remove($alias);
    }

    /**
     * Clears the registry of configuration and instances.
     *
     * @return void
     */
    public static function clear()
    {
        static::locator()->clear();
    }

    /**
     * Proxy for static calls on a locator.
     *
     * @param string $name Method name.
     * @param array $arguments Method arguments.
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([static::locator(), $name], $arguments);
    }
}
