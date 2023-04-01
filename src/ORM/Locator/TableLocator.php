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
use Cake\Database\Exception\DatabaseException;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Locator\AbstractLocator;
use Cake\Datasource\RepositoryInterface;
use Cake\ORM\AssociationCollection;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Query\QueryFactory;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use function Cake\Core\pluginSplit;

/**
 * Provides a default registry/factory for Table objects.
 */
class TableLocator extends AbstractLocator implements LocatorInterface
{
    /**
     * Contains a list of locations where table classes should be looked for.
     *
     * @var array<string>
     */
    protected array $locations = [];

    /**
     * Configuration for aliases.
     *
     * @var array<string, array|null>
     */
    protected array $_config = [];

    /**
     * Instances that belong to the registry.
     *
     * @var array<string, \Cake\ORM\Table>
     * @psalm-suppress NonInvariantDocblockPropertyType
     */
    protected array $instances = [];

    /**
     * Contains a list of Table objects that were created out of the
     * built-in Table class. The list is indexed by table alias
     *
     * @var array<\Cake\ORM\Table>
     */
    protected array $_fallbacked = [];

    /**
     * Fallback class to use
     *
     * @var string
     * @psalm-var class-string<\Cake\ORM\Table>
     */
    protected string $fallbackClassName = Table::class;

    /**
     * Whether fallback class should be used if a table class could not be found.
     *
     * @var bool
     */
    protected bool $allowFallbackClass = true;

    protected QueryFactory $queryFactory;

    /**
     * Constructor.
     *
     * @param array<string>|null $locations Locations where tables should be looked for.
     *   If none provided, the default `Model\Table` under your app's namespace is used.
     */
    public function __construct(?array $locations = null, ?QueryFactory $queryFactory = null)
    {
        if ($locations === null) {
            $locations = [
                'Model/Table',
            ];
        }

        foreach ($locations as $location) {
            $this->addLocation($location);
        }

        $this->queryFactory = $queryFactory ?: new QueryFactory();
    }

    /**
     * Set if fallback class should be used.
     *
     * Controls whether a fallback class should be used to create a table
     * instance if a concrete class for alias used in `get()` could not be found.
     *
     * @param bool $allow Flag to enable or disable fallback
     * @return $this
     */
    public function allowFallbackClass(bool $allow)
    {
        $this->allowFallbackClass = $allow;

        return $this;
    }

    /**
     * Set fallback class name.
     *
     * The class that should be used to create a table instance if a concrete
     * class for alias used in `get()` could not be found. Defaults to
     * `Cake\ORM\Table`.
     *
     * @param string $className Fallback class name
     * @return $this
     * @psalm-param class-string<\Cake\ORM\Table> $className
     */
    public function setFallbackClassName(string $className)
    {
        $this->fallbackClassName = $className;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setConfig(array|string $alias, ?array $options = null)
    {
        if (!is_string($alias)) {
            $this->_config = $alias;

            return $this;
        }

        if (isset($this->instances[$alias])) {
            throw new DatabaseException(sprintf(
                'You cannot configure `%s`, it has already been constructed.',
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
     * The options that can be passed are the same as in {@link \Cake\ORM\Table::__construct()}, but the
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
     * @param array<string, mixed> $options The options you want to build the table with.
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
    protected function createInstance(string $alias, array $options): Table
    {
        if (!str_contains($alias, '\\')) {
            [, $classAlias] = pluginSplit($alias);
            $options = ['alias' => $classAlias] + $options;
        } elseif (!isset($options['alias'])) {
            $options['className'] = $alias;
        }

        if (isset($this->_config[$alias])) {
            $options += $this->_config[$alias];
        }

        $allowFallbackClass = $options['allowFallbackClass'] ?? $this->allowFallbackClass;
        $className = $this->_getClassName($alias, $options);
        if ($className) {
            $options['className'] = $className;
        } elseif ($allowFallbackClass) {
            if (empty($options['className'])) {
                $options['className'] = $alias;
            }
            if (!isset($options['table']) && !str_contains($options['className'], '\\')) {
                [, $table] = pluginSplit($options['className']);
                $options['table'] = Inflector::underscore($table);
            }
            $options['className'] = $this->fallbackClassName;
        } else {
            $message = $options['className'] ?? $alias;
            $message = '`' . $message . '`';
            if (!str_contains($message, '\\')) {
                $message = 'for alias ' . $message;
            }
            throw new MissingTableClassException([$message]);
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
        if (empty($options['queryFactory'])) {
            $options['queryFactory'] = $this->queryFactory;
        }

        $options['registryAlias'] = $alias;
        $instance = $this->_create($options);

        if ($options['className'] === $this->fallbackClassName) {
            $this->_fallbacked[$alias] = $instance;
        }

        return $instance;
    }

    /**
     * Gets the table class name.
     *
     * @param string $alias The alias name you want to get. Should be in CamelCase format.
     * @param array<string, mixed> $options Table options array.
     * @return string|null
     */
    protected function _getClassName(string $alias, array $options = []): ?string
    {
        if (empty($options['className'])) {
            $options['className'] = $alias;
        }

        if (str_contains($options['className'], '\\') && class_exists($options['className'])) {
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
     * @param array<string, mixed> $options The alias to check for.
     * @return \Cake\ORM\Table
     */
    protected function _create(array $options): Table
    {
        /** @var class-string<\Cake\ORM\Table> $class */
        $class = $options['className'];

        return new $class($options);
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
     * @return array<\Cake\ORM\Table>
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
