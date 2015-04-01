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

use Cake\ORM\Registry\RegistryInterface;

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
     * Singleton for static calls.
     *
     * @var \Cake\ORM\Registry\RegistryInterface
     */
    protected static $_instance;

    /**
     * Default RegistryInterface implementation class.
     *
     * @var string
     */
    protected static $_defaultRegistryClass = 'Cake\ORM\Registry\DefaultRegistry';

    /**
     * Sets and returns singleton instance of Registry.
     *
     * @param \Cake\ORM\Registry\RegistryInterface $instance Instance of registry to set.
     * @return \Cake\ORM\Registry\RegistryInterface
     */
    public static function instance(RegistryInterface $instance = null)
    {
        if ($instance) {
            static::$_instance = $instance;
        }

        if (!static::$_instance) {
            static::$_instance = new static::$_defaultRegistryClass;
        }

        return static::$_instance;
    }

    /**
     * Proxy for static calls on a singleton.
     *
     * @param string $name Method name.
     * @param array $arguments Method arguments.
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([static::instance(), $name], $arguments);
    }
}
