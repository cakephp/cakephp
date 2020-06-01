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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use Cake\Datasource\FactoryLocator;
use Cake\ORM\Locator\LocatorInterface;

/**
 * Provides a registry/factory for Table objects.
 *
 * This registry allows you to centralize the configuration for tables
 * their connections and other meta-data.
 *
 * ### Configuring instances
 *
 * You may need to configure your table objects. Using the `TableLocator` you can
 * centralize configuration. Any configuration set before instances are created
 * will be used when creating instances. If you modify configuration after
 * an instance is made, the instances *will not* be updated.
 *
 * ```
 * TableRegistry::getTableLocator()->setConfig('Users', ['table' => 'my_users']);
 *
 * // Prior to 3.6.0
 * TableRegistry::config('Users', ['table' => 'my_users']);
 * ```
 *
 * Configuration data is stored *per alias* if you use the same table with
 * multiple aliases you will need to set configuration multiple times.
 *
 * ### Getting instances
 *
 * You can fetch instances out of the registry through `TableLocator::get()`.
 * One instance is stored per alias. Once an alias is populated the same
 * instance will always be returned. This reduces the ORM memory cost and
 * helps make cyclic references easier to solve.
 *
 * ```
 * $table = TableRegistry::getTableLocator()->get('Users', $config);
 *
 * // Prior to 3.6.0
 * $table = TableRegistry::get('Users', $config);
 * ```
 */
class TableRegistry
{
    /**
     * Returns a singleton instance of LocatorInterface implementation.
     *
     * @return \Cake\ORM\Locator\LocatorInterface
     */
    public static function getTableLocator(): LocatorInterface
    {
        /** @var \Cake\ORM\Locator\LocatorInterface */
        return FactoryLocator::get('Table');
    }

    /**
     * Sets singleton instance of LocatorInterface implementation.
     *
     * @param \Cake\ORM\Locator\LocatorInterface $tableLocator Instance of a locator to use.
     * @return void
     */
    public static function setTableLocator(LocatorInterface $tableLocator): void
    {
        FactoryLocator::add('Table', $tableLocator);
    }

    /**
     * Get a table instance from the registry.
     *
     * See options specification in {@link TableLocator::get()}.
     *
     * @param string $alias The alias name you want to get.
     * @param array $options The options you want to build the table with.
     * @return \Cake\ORM\Table
     * @deprecated 3.6.0 Use {@link \Cake\ORM\Locator\TableLocator::get()} instead. Will be removed in 5.0.
     */
    public static function get(string $alias, array $options = []): Table
    {
        return static::getTableLocator()->get($alias, $options);
    }

    /**
     * Check to see if an instance exists in the registry.
     *
     * @param string $alias The alias to check for.
     * @return bool
     * @deprecated 3.6.0 Use {@link \Cake\ORM\Locator\TableLocator::exists()} instead. Will be removed in 5.0
     */
    public static function exists(string $alias): bool
    {
        return static::getTableLocator()->exists($alias);
    }

    /**
     * Set an instance.
     *
     * @param string $alias The alias to set.
     * @param \Cake\ORM\Table $object The table to set.
     * @return \Cake\ORM\Table
     * @deprecated 3.6.0 Use {@link \Cake\ORM\Locator\TableLocator::set()} instead. Will be removed in 5.0
     */
    public static function set(string $alias, Table $object): Table
    {
        return static::getTableLocator()->set($alias, $object);
    }

    /**
     * Removes an instance from the registry.
     *
     * @param string $alias The alias to remove.
     * @return void
     * @deprecated 3.6.0 Use {@link \Cake\ORM\Locator\TableLocator::remove()} instead. Will be removed in 5.0
     */
    public static function remove(string $alias): void
    {
        static::getTableLocator()->remove($alias);
    }

    /**
     * Clears the registry of configuration and instances.
     *
     * @return void
     * @deprecated 3.6.0 Use {@link \Cake\ORM\Locator\TableLocator::clear()} instead. Will be removed in 5.0
     */
    public static function clear(): void
    {
        static::getTableLocator()->clear();
    }
}
