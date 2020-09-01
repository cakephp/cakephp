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
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Locator;

use Cake\Core\App;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Locator\AbstractLocator;
use Cake\Datasource\RepositoryInterface;
use Cake\ORM\AssociationCollection;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use RuntimeException;

/**
 * Provides a default registry/factory for Table objects.
 */
class TableLocator extends AbstractLocator implements LocatorInterface
{
    /**
     * Contains a list of locations where table classes should be looked for.
     *
     * @var array
     */
    protected $locations = [];

    /**
     * Configuration for aliases.
     *
     * @var array
     */
    protected $_config = [];

    /**
     * Instances that belong to the registry.
     *
     * @var \Cake\ORM\Table[]
     */
    protected $instances = [];

    /**
     * Contains a list of Table objects that were created out of the
     * built-in Table class. The list is indexed by table alias
     *
     * @var \Cake\ORM\Table[]
     */
    protected $_fallbacked = [];

    /**
     * Constructor.
     *
     * @param array|null $locations Locations where tables should be looked for.
     *   If none provided, the default `Model\Table` under your app's namespace is used.
     */
    public function __construct(?array $locations = null)
    {
        if ($locations === null) {
            $locations = [
                'Model/Table',
            ];
        }

        foreach ($locations as $location) {
            $this->addLocation($location);
        }
    }

    /**
     * @inheritDoc
     */
    public function setConfig($alias, $options = null)
    {
        if (!is_string($alias)) {
            $this->_config = $alias;

            return $this;
        }

        if (isset($this->instances[$alias])) {
            throw new RuntimeException(sprintf(
                'You cannot configure "%s", it has already been constructed.',
                $alias
            ));
        }

        $this->_config[$alias] = $options;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(?string $alias = null): array
    {
        if ($alias === null) {
            return $this->_config;
        }

        return $this->_config[$alias] ?? [];
    }

    /**
     * Get a table instance from the registry.
     *
     * Tables are only created once until the registry is flushed.
     * This means that aliases must be unique across your application.
     * This is important because table associations are resolved at runtime
     * and cyclic references need to be handled correctly.
     *
     * The options that can be passed are the same as in Cake\ORM\Table::__construct(), but the
     * `className` key is also recognized.
     *
     * ### Options
     *
     * - `className` Define the specific class name to use. If undefined, CakePHP will generate the
     *   class name based on the alias. For example 'Users' would result in
     *   `App\Model\Table\UsersTable` being used. If this class does not exist,
     *   then the default `Cake\ORM\Table` class will be used. By setting the `className`
     *   option you can define the specific class to use. The className option supports
     *   plugin short class references {@link \Cake\Core\App::shortName()}.
     * - `table` Define the table name to use. If undefined, this option will default to the underscored
     *   version of the alias name.
     * - `connection` Inject the specific connection object to use. If this option and `connectionName` are undefined,
     *   The table class' `defaultConnectionName()` method will be invoked to fetch the connection name.
     * - `connectionName` Define the connection name to use. The named connection will be fetched from
     *   {@link \Cake\Datasource\ConnectionManager}.
     *
     * *Note* If your `$alias` uses plugin syntax only the name part will be used as
     * key in the registry. This means that if two plugins, or a plugin and app provide
     * the same alias, the registry will only store the first instance.
     *
     * @param string $alias The alias name you want to get. Should be in CamelCase format.
     * @param array $options The options you want to build the table with.
     *   If a table has already been loaded the options will be ignored.
     * @return \Cake\ORM\Table
     * @throws \RuntimeException When you try to configure an alias that already exists.
     */
    public function get(string $alias, array $options = []): Table
    {
        /** @var \Cake\ORM\Table */
        return parent::get($alias, $options);
    }

    /**
     * @inheritDoc
     */
    protected function createInstance(string $alias, array $options)
    {
        if (strpos($alias, '\\') === false) {
            [, $classAlias] = pluginSplit($alias);
            $options = ['alias' => $classAlias] + $options;
        } elseif (!isset($options['alias'])) {
            $options['className'] = $alias;
            /** @psalm-suppress PossiblyFalseOperand */
            $alias = substr($alias, strrpos($alias, '\\') + 1, -5);
        }

        if (isset($this->_config[$alias])) {
            $options += $this->_config[$alias];
        }

        $className = $this->_getClassName($alias, $options);
        if ($className) {
            $options['className'] = $className;
        } else {
            if (empty($options['className'])) {
                $options['className'] = $alias;
            }
            if (!isset($options['table']) && strpos($options['className'], '\\') === false) {
                [, $table] = pluginSplit($options['className']);
                $options['table'] = Inflector::underscore($table);
            }
            $options['className'] = Table::class;
        }

        if (empty($options['connection'])) {
            if (!empty($options['connectionName'])) {
                $connectionName = $options['connectionName'];
            } else {
                /** @var \Cake\ORM\Table $className */
                $className = $options['className'];
                $connectionName = $className::defaultConnectionName();
            }
            $options['connection'] = ConnectionManager::get($connectionName);
        }
        if (empty($options['associations'])) {
            $associations = new AssociationCollection($this);
            $options['associations'] = $associations;
        }

        $options['registryAlias'] = $alias;
        $instance = $this->_create($options);

        if ($options['className'] === Table::class) {
            $this->_fallbacked[$alias] = $instance;
        }

        return $instance;
    }

    /**
     * Gets the table class name.
     *
     * @param string $alias The alias name you want to get. Should be in CamelCase format.
     * @param array $options Table options array.
     * @return string|null
     */
    protected function _getClassName(string $alias, array $options = []): ?string
    {
        if (empty($options['className'])) {
            $options['className'] = $alias;
        }

        if (strpos($options['className'], '\\') !== false && class_exists($options['className'])) {
            return $options['className'];
        }

        foreach ($this->locations as $location) {
            $class = App::className($options['className'], $location, 'Table');
            if ($class !== null) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Wrapper for creating table instances
     *
     * @param array $options The alias to check for.
     * @return \Cake\ORM\Table
     */
    protected function _create(array $options): Table
    {
        /** @var \Cake\ORM\Table */
        return new $options['className']($options);
    }

    /**
     * Set a Table instance.
     *
     * @param string $alias The alias to set.
     * @param \Cake\ORM\Table $repository The Table to set.
     * @return \Cake\ORM\Table
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function set(string $alias, RepositoryInterface $repository): Table
    {
        return $this->instances[$alias] = $repository;
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        parent::clear();

        $this->_fallbacked = [];
        $this->_config = [];
    }

    /**
     * Returns the list of tables that were created by this registry that could
     * not be instantiated from a specific subclass. This method is useful for
     * debugging common mistakes when setting up associations or created new table
     * classes.
     *
     * @return \Cake\ORM\Table[]
     */
    public function genericInstances(): array
    {
        return $this->_fallbacked;
    }

    /**
     * @inheritDoc
     */
    public function remove(string $alias): void
    {
        parent::remove($alias);

        unset($this->_fallbacked[$alias]);
    }

    /**
     * Adds a location where tables should be looked for.
     *
     * @param string $location Location to add.
     * @return $this
     * @since 3.8.0
     */
    public function addLocation(string $location)
    {
        $location = str_replace('\\', '/', $location);
        $this->locations[] = trim($location, '/');

        return $this;
    }
}
