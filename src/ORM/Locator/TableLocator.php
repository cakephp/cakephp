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
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Locator;

use Cake\Core\App;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use RuntimeException;

/**
 * Provides a default registry/factory for Table objects.
 */
class TableLocator implements LocatorInterface
{

    /**
     * Configuration for aliases.
     *
     * @var array
     */
    protected $_config = [];

    /**
     * Instances that belong to the registry.
     *
     * @var array
     */
    protected $_instances = [];

    /**
     * Contains a list of Table objects that were created out of the
     * built-in Table class. The list is indexed by table alias
     *
     * @var array
     */
    protected $_fallbacked = [];

    /**
     * Contains a list of options that were passed to get() method.
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Stores a list of options to be used when instantiating an object
     * with a matching alias.
     *
     * The options that can be stored are those that are recognized by `get()`
     * If second argument is omitted, it will return the current settings
     * for $alias.
     *
     * If no arguments are passed it will return the full configuration array for
     * all aliases
     *
     * @param string|null $alias Name of the alias
     * @param array|null $options list of options for the alias
     * @return array The config data.
     * @throws \RuntimeException When you attempt to configure an existing table instance.
     */
    public function config($alias = null, $options = null)
    {
        if ($alias === null) {
            return $this->_config;
        }
        if (!is_string($alias)) {
            return $this->_config = $alias;
        }
        if ($options === null) {
            return isset($this->_config[$alias]) ? $this->_config[$alias] : [];
        }
        if (isset($this->_instances[$alias])) {
            throw new RuntimeException(sprintf(
                'You cannot configure "%s", it has already been constructed.',
                $alias
            ));
        }
        return $this->_config[$alias] = $options;
    }

    /**
     * Get a table instance from the registry.
     *
     * Tables are only created once until the registry is flushed.
     * This means that aliases must be unique across your application.
     * This is important because table associations are resolved at runtime
     * and cyclic references need to be handled correctly.
     *
     * The options that can be passed are the same as in `Table::__construct()`, but the
     * key `className` is also recognized.
     *
     * If $options does not contain `className` CakePHP will attempt to construct the
     * class name based on the alias. For example 'Users' would result in
     * `App\Model\Table\UsersTable` being attempted. If this class does not exist,
     * then the default `Cake\ORM\Table` class will be used. By setting the `className`
     * option you can define the specific class to use. This className can
     * use a plugin short class reference.
     *
     * If you use a `$name` that uses plugin syntax only the name part will be used as
     * key in the registry. This means that if two plugins, or a plugin and app provide
     * the same alias, the registry will only store the first instance.
     *
     * If no `table` option is passed, the table name will be the underscored version
     * of the provided $alias.
     *
     * If no `connection` option is passed and passed `connectionName` string then it's used as original name to instantiate connection.
     *
     * If no `connection` option is passed the table's defaultConnectionName() method
     * will be called to get the default connection name to use.
     *
     * @param string $alias The alias name you want to get.
     * @param array $options The options you want to build the table with.
     *   If a table has already been loaded the options will be ignored.
     * @return \Cake\ORM\Table
     * @throws \RuntimeException When you try to configure an alias that already exists.
     */
    public function get($alias, array $options = [])
    {
        if (isset($this->_instances[$alias])) {
            if (!empty($options) && $this->_options[$alias] !== $options) {
                throw new RuntimeException(sprintf(
                    'You cannot configure "%s", it already exists in the registry.',
                    $alias
                ));
            }
            return $this->_instances[$alias];
        }

        $this->_options[$alias] = $options;
        list(, $classAlias) = pluginSplit($alias);
        $options = ['alias' => $classAlias] + $options;

        if (isset($this->_config[$alias])) {
            $options += $this->_config[$alias];
        }

        if (empty($options['className'])) {
            $options['className'] = Inflector::camelize($alias);
        }

        $className = $this->_getClassName($alias, $options);
        if ($className) {
            $options['className'] = $className;
        } else {
            if (!isset($options['table']) && strpos($options['className'], '\\') === false) {
                list(, $table) = pluginSplit($options['className']);
                $options['table'] = Inflector::underscore($table);
            }
            $options['className'] = 'Cake\ORM\Table';
        }

        if (empty($options['connection'])) {
            if (!empty($options['connectionName'])) {
                $connectionName = $options['connectionName'];
            } else {
                $connectionName = $options['className']::defaultConnectionName();
            }
            $options['connection'] = ConnectionManager::get($connectionName);
        }

        $options['registryAlias'] = $alias;
        $this->_instances[$alias] = $this->_create($options);

        if ($options['className'] === 'Cake\ORM\Table') {
            $this->_fallbacked[$alias] = $this->_instances[$alias];
        }

        return $this->_instances[$alias];
    }

    /**
     * Gets the table class name.
     *
     * @param string $alias The alias name you want to get.
     * @param array $options Table options array.
     * @return string
     */
    protected function _getClassName($alias, array $options = [])
    {
        if (empty($options['className'])) {
            $options['className'] = Inflector::camelize($alias);
        }
        return App::className($options['className'], 'Model/Table', 'Table');
    }

    /**
     * Wrapper for creating table instances
     *
     * @param array $options The alias to check for.
     * @return \Cake\ORM\Table
     */
    protected function _create(array $options)
    {
        return new $options['className']($options);
    }

    /**
     * {@inheritDoc}
     */
    public function exists($alias)
    {
        return isset($this->_instances[$alias]);
    }

    /**
     * {@inheritDoc}
     */
    public function set($alias, Table $object)
    {
        return $this->_instances[$alias] = $object;
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        $this->_instances = [];
        $this->_config = [];
        $this->_fallbacked = [];
    }

    /**
     * Returns the list of tables that were created by this registry that could
     * not be instantiated from a specific subclass. This method is useful for
     * debugging common mistakes when setting up associations or created new table
     * classes.
     *
     * @return array
     */
    public function genericInstances()
    {
        return $this->_fallbacked;
    }

    /**
     * {@inheritDoc}
     */
    public function remove($alias)
    {
        unset(
            $this->_instances[$alias],
            $this->_config[$alias],
            $this->_fallbacked[$alias]
        );
    }
}
