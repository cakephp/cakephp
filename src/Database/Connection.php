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
namespace Cake\Database;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Retry\CommandRetry;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Exception\MissingDriverException;
use Cake\Database\Exception\MissingExtensionException;
use Cake\Database\Exception\NestedTransactionRollbackException;
use Cake\Database\Log\LoggedQuery;
use Cake\Database\Log\LoggingStatement;
use Cake\Database\Log\QueryLogger;
use Cake\Database\Query\DeleteQuery;
use Cake\Database\Query\InsertQuery;
use Cake\Database\Query\SelectQuery;
use Cake\Database\Query\UpdateQuery;
use Cake\Database\Retry\ReconnectStrategy;
use Cake\Database\Schema\CachedCollection;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\CollectionInterface as SchemaCollectionInterface;
use Cake\Datasource\ConnectionInterface;
use Cake\Log\Engine\BaseLog;
use Cake\Log\Log;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Throwable;
use function Cake\Core\deprecationWarning;

/**
 * Represents a connection with a database server.
 */
class Connection implements ConnectionInterface
{
    use TypeConverterTrait;

    /**
     * Contains the configuration params for this connection.
     *
     * @var array<string, mixed>
     */
    protected $_config;

    /**
     * @var \Cake\Database\DriverInterface
     */
    protected DriverInterface $readDriver;

    /**
     * @var \Cake\Database\DriverInterface
     */
    protected DriverInterface $writeDriver;

    /**
     * Contains how many nested transactions have been started.
     *
     * @var int
     */
    protected $_transactionLevel = 0;

    /**
     * Whether a transaction is active in this connection.
     *
     * @var bool
     */
    protected $_transactionStarted = false;

    /**
     * Whether this connection can and should use savepoints for nested
     * transactions.
     *
     * @var bool
     */
    protected $_useSavePoints = false;

    /**
     * Whether to log queries generated during this connection.
     *
     * @var bool
     */
    protected $_logQueries = false;

    /**
     * Logger object instance.
     *
     * @var \Psr\Log\LoggerInterface|null
     */
    protected $_logger;

    /**
     * Cacher object instance.
     *
     * @var \Psr\SimpleCache\CacheInterface|null
     */
    protected $cacher;

    /**
     * The schema collection object
     *
     * @var \Cake\Database\Schema\CollectionInterface|null
     */
    protected $_schemaCollection;

    /**
     * NestedTransactionRollbackException object instance, will be stored if
     * the rollback method is called in some nested transaction.
     *
     * @var \Cake\Database\Exception\NestedTransactionRollbackException|null
     */
    protected $nestedTransactionRollbackException;

    /**
     * Constructor.
     *
     * ### Available options:
     *
     * - `driver` Sort name or FCQN for driver.
     * - `log` Boolean indicating whether to use query logging.
     * - `name` Connection name.
     * - `cacheMetaData` Boolean indicating whether metadata (datasource schemas) should be cached.
     *    If set to a string it will be used as the name of cache config to use.
     * - `cacheKeyPrefix` Custom prefix to use when generation cache keys. Defaults to connection name.
     *
     * @param array<string, mixed> $config Configuration array.
     */
    public function __construct(array $config)
    {
        $this->_config = $config;
        [self::ROLE_READ => $this->readDriver, self::ROLE_WRITE => $this->writeDriver] = $this->createDrivers($config);

        if (!empty($config['log'])) {
            $this->enableQueryLogging((bool)$config['log']);
        }
    }

    /**
     * Creates read and write drivers.
     *
     * @param array $config Connection config
     * @return array<string, \Cake\Database\DriverInterface>
     * @psalm-return array{read: \Cake\Database\DriverInterface, write: \Cake\Database\DriverInterface}
     */
    protected function createDrivers(array $config): array
    {
        $driver = $config['driver'] ?? '';
        if (!is_string($driver)) {
            /** @var \Cake\Database\DriverInterface $driver */
            if (!$driver->enabled()) {
                throw new MissingExtensionException(['driver' => get_class($driver), 'name' => $this->configName()]);
            }

            // Legacy support for setting instance instead of driver class
            return [self::ROLE_READ => $driver, self::ROLE_WRITE => $driver];
        }

        /** @var class-string<\Cake\Database\DriverInterface>|null $driverClass */
        $driverClass = App::className($driver, 'Database/Driver');
        if ($driverClass === null) {
            throw new MissingDriverException(['driver' => $driver, 'connection' => $this->configName()]);
        }

        $sharedConfig = array_diff_key($config, array_flip([
            'name',
            'driver',
            'log',
            'cacheMetaData',
            'cacheKeyPrefix',
        ]));

        $writeConfig = $config['write'] ?? [] + $sharedConfig;
        $readConfig = $config['read'] ?? [] + $sharedConfig;
        if ($readConfig == $writeConfig) {
            $readDriver = $writeDriver = new $driverClass(['_role' => self::ROLE_WRITE] + $writeConfig);
        } else {
            $readDriver = new $driverClass(['_role' => self::ROLE_READ] + $readConfig);
            $writeDriver = new $driverClass(['_role' => self::ROLE_WRITE] + $writeConfig);
        }

        if (!$writeDriver->enabled()) {
            throw new MissingExtensionException(['driver' => get_class($writeDriver), 'name' => $this->configName()]);
        }

        return [self::ROLE_READ => $readDriver, self::ROLE_WRITE => $writeDriver];
    }

    /**
     * Destructor
     *
     * Disconnects the driver to release the connection.
     */
    public function __destruct()
    {
        if ($this->_transactionStarted && class_exists(Log::class)) {
            Log::warning('The connection is going to be closed but there is an active transaction.');
        }
    }

    /**
     * @inheritDoc
     */
    public function config(): array
    {
        return $this->_config;
    }

    /**
     * @inheritDoc
     */
    public function configName(): string
    {
        return $this->_config['name'] ?? '';
    }

    /**
     * Returns the connection role: read or write.
     *
     * @return string
     */
    public function role(): string
    {
        return preg_match('/:read$/', $this->configName()) === 1 ? static::ROLE_READ : static::ROLE_WRITE;
    }

    /**
     * Sets the driver instance. If a string is passed it will be treated
     * as a class name and will be instantiated.
     *
     * @param \Cake\Database\DriverInterface|string $driver The driver instance to use.
     * @param array<string, mixed> $config Config for a new driver.
     * @throws \Cake\Database\Exception\MissingDriverException When a driver class is missing.
     * @throws \Cake\Database\Exception\MissingExtensionException When a driver's PHP extension is missing.
     * @return $this
     * @deprecated 4.4.0 Setting the driver is deprecated. Use the connection config instead.
     */
    public function setDriver($driver, $config = [])
    {
        deprecationWarning('Setting the driver is deprecated. Use the connection config instead.');

        $driver = $this->createDriver($driver, $config);
        $this->readDriver = $this->writeDriver = $driver;

        return $this;
    }

    /**
     * Creates driver from name, class name or instance.
     *
     * @param \Cake\Database\DriverInterface|string $name Driver name, class name or instance.
     * @param array $config Driver config if $name is not an instance.
     * @return \Cake\Database\DriverInterface
     * @throws \Cake\Database\Exception\MissingDriverException When a driver class is missing.
     * @throws \Cake\Database\Exception\MissingExtensionException When a driver's PHP extension is missing.
     */
    protected function createDriver($name, array $config): DriverInterface
    {
        $driver = $name;
        if (is_string($driver)) {
            /** @psalm-var class-string<\Cake\Database\DriverInterface>|null $className */
            $className = App::className($driver, 'Database/Driver');
            if ($className === null) {
                throw new MissingDriverException(['driver' => $driver, 'connection' => $this->configName()]);
            }
            $driver = new $className(['_role' => self::ROLE_WRITE] + $config);
        }

        if (!$driver->enabled()) {
            throw new MissingExtensionException(['driver' => get_class($driver), 'name' => $this->configName()]);
        }

        return $driver;
    }

    /**
     * Get the retry wrapper object that is allows recovery from server disconnects
     * while performing certain database actions, such as executing a query.
     *
     * @return \Cake\Core\Retry\CommandRetry The retry wrapper
     */
    public function getDisconnectRetry(): CommandRetry
    {
        return new CommandRetry(new ReconnectStrategy($this));
    }

    /**
     * Gets the driver instance.
     *
     * @param string $role Connection role ('read' or 'write')
     * @return \Cake\Database\DriverInterface
     */
    public function getDriver(string $role = self::ROLE_WRITE): DriverInterface
    {
        assert($role === self::ROLE_READ || $role === self::ROLE_WRITE);

        return $role === self::ROLE_READ ? $this->readDriver : $this->writeDriver;
    }

    /**
     * Connects to the configured database.
     *
     * @throws \Cake\Database\Exception\MissingConnectionException If database connection could not be established.
     * @return bool true, if the connection was already established or the attempt was successful.
     * @deprecated 4.5.0 Use getDriver()->connect() instead.
     */
    public function connect(): bool
    {
        deprecationWarning(
            'If you cannot use automatic connection management, use $connection->getDriver()->connect() instead.'
        );

        $connected = true;
        foreach ([self::ROLE_READ, self::ROLE_WRITE] as $role) {
            try {
                $connected = $connected && $this->getDriver($role)->connect();
            } catch (MissingConnectionException $e) {
                throw $e;
            } catch (Throwable $e) {
                throw new MissingConnectionException(
                    [
                        'driver' => App::shortName(get_class($this->getDriver($role)), 'Database/Driver'),
                        'reason' => $e->getMessage(),
                    ],
                    null,
                    $e
                );
            }
        }

        return $connected;
    }

    /**
     * Disconnects from database server.
     *
     * @return void
     * @deprecated 4.5.0 Use getDriver()->disconnect() instead.
     */
    public function disconnect(): void
    {
        deprecationWarning(
            'If you cannot use automatic connection management, use $connection->getDriver()->disconnect() instead.'
        );

        $this->getDriver(self::ROLE_READ)->disconnect();
        $this->getDriver(self::ROLE_WRITE)->disconnect();
    }

    /**
     * Returns whether connection to database server was already established.
     *
     * @return bool
     * @deprecated 4.5.0 Use getDriver()->isConnected() instead.
     */
    public function isConnected(): bool
    {
        deprecationWarning('Use $connection->getDriver()->isConnected() instead.');

        return $this->getDriver(self::ROLE_READ)->isConnected() && $this->getDriver(self::ROLE_WRITE)->isConnected();
    }

    /**
     * Prepares a SQL statement to be executed.
     *
     * @param \Cake\Database\Query|string $query The SQL to convert into a prepared statement.
     * @return \Cake\Database\StatementInterface
     * @deprecated 4.5.0 Use getDriver()->prepare() instead.
     */
    public function prepare($query): StatementInterface
    {
        $role = $query instanceof Query ? $query->getConnectionRole() : self::ROLE_WRITE;

        return $this->getDisconnectRetry()->run(function () use ($query, $role) {
            $statement = $this->getDriver($role)->prepare($query);

            if ($this->_logQueries) {
                $statement = $this->_newLogger($statement);
            }

            return $statement;
        });
    }

    /**
     * Executes a query using $params for interpolating values and $types as a hint for each
     * those params.
     *
     * @param string $sql SQL to be executed and interpolated with $params
     * @param array $params list or associative array of params to be interpolated in $sql as values
     * @param array $types list or associative array of types to be used for casting values in query
     * @return \Cake\Database\StatementInterface executed statement
     */
    public function execute(string $sql, array $params = [], array $types = []): StatementInterface
    {
        return $this->getDisconnectRetry()->run(function () use ($sql, $params, $types) {
            $statement = $this->prepare($sql);
            if (!empty($params)) {
                $statement->bind($params, $types);
            }
            $statement->execute();

            return $statement;
        });
    }

    /**
     * Compiles a Query object into a SQL string according to the dialect for this
     * connection's driver
     *
     * @param \Cake\Database\Query $query The query to be compiled
     * @param \Cake\Database\ValueBinder $binder Value binder
     * @return string
     * @deprecated 4.5.0 Use getDriver()->compileQuery() instead.
     */
    public function compileQuery(Query $query, ValueBinder $binder): string
    {
        deprecationWarning('Use getDriver()->compileQuery() instead.');

        return $this->getDriver($query->getConnectionRole())->compileQuery($query, $binder)[1];
    }

    /**
     * Executes the provided query after compiling it for the specific driver
     * dialect and returns the executed Statement object.
     *
     * @param \Cake\Database\Query $query The query to be executed
     * @return \Cake\Database\StatementInterface executed statement
     */
    public function run(Query $query): StatementInterface
    {
        return $this->getDisconnectRetry()->run(function () use ($query) {
            $statement = $this->prepare($query);
            $query->getValueBinder()->attachTo($statement);
            $statement->execute();

            return $statement;
        });
    }

    /**
     * Create a new SelectQuery instance for this connection.
     *
     * @param \Cake\Database\ExpressionInterface|callable|array|string $fields fields to be added to the list.
     * @param array|string $table The table or list of tables to query.
     * @param array<string, string> $types Associative array containing the types to be used for casting.
     * @return \Cake\Database\Query\SelectQuery
     */
    public function selectQuery(
        $fields = [],
        $table = [],
        array $types = []
    ): SelectQuery {
        $query = new SelectQuery($this);
        if ($table) {
            $query->from($table);
        }
        if ($fields) {
            $query->select($fields, false);
        }
        $query->setDefaultTypes($types);

        return $query;
    }

    /**
     * Executes a SQL statement and returns the Statement object as result.
     *
     * @param string $sql The SQL query to execute.
     * @return \Cake\Database\StatementInterface
     * @deprecated 4.5.0 Use either `selectQuery`, `insertQuery`, `deleteQuery`, `updateQuery` instead.
     */
    public function query(string $sql): StatementInterface
    {
        deprecationWarning('Use either `selectQuery`, `insertQuery`, `deleteQuery`, `updateQuery` instead.');

        return $this->getDisconnectRetry()->run(function () use ($sql) {
            $statement = $this->prepare($sql);
            $statement->execute();

            return $statement;
        });
    }

    /**
     * Create a new Query instance for this connection.
     *
     * @return \Cake\Database\Query
     * @deprecated 4.5.0 Use `insertQuery()`, `deleteQuery()`, `selectQuery()` or `updateQuery()` instead.
     */
    public function newQuery(): Query
    {
        deprecationWarning(
            'As of 4.5.0, using newQuery() is deprecated. Instead, use `insertQuery()`, ' .
            '`deleteQuery()`, `selectQuery()` or `updateQuery()`. The query objects ' .
            'returned by these methods will emit deprecations that will become fatal errors in 5.0.' .
            'See https://book.cakephp.org/4/en/appendices/4-5-migration-guide.html for more information.'
        );

        return new Query($this);
    }

    /**
     * Sets a Schema\Collection object for this connection.
     *
     * @param \Cake\Database\Schema\CollectionInterface $collection The schema collection object
     * @return $this
     */
    public function setSchemaCollection(SchemaCollectionInterface $collection)
    {
        $this->_schemaCollection = $collection;

        return $this;
    }

    /**
     * Gets a Schema\Collection object for this connection.
     *
     * @return \Cake\Database\Schema\CollectionInterface
     */
    public function getSchemaCollection(): SchemaCollectionInterface
    {
        if ($this->_schemaCollection !== null) {
            return $this->_schemaCollection;
        }

        if (!empty($this->_config['cacheMetadata'])) {
            return $this->_schemaCollection = new CachedCollection(
                new SchemaCollection($this),
                empty($this->_config['cacheKeyPrefix']) ? $this->configName() : $this->_config['cacheKeyPrefix'],
                $this->getCacher()
            );
        }

        return $this->_schemaCollection = new SchemaCollection($this);
    }

    /**
     * Executes an INSERT query on the specified table.
     *
     * @param string $table the table to insert values in
     * @param array $values values to be inserted
     * @param array<int|string, string> $types Array containing the types to be used for casting
     * @return \Cake\Database\StatementInterface
     */
    public function insert(string $table, array $values, array $types = []): StatementInterface
    {
        return $this->getDisconnectRetry()->run(function () use ($table, $values, $types) {
            return $this->insertQuery($table, $values, $types)->execute();
        });
    }

    /**
     * Create a new InsertQuery instance for this connection.
     *
     * @param string|null $table The table to insert rows into.
     * @param array $values Associative array of column => value to be inserted.
     * @param array<int|string, string> $types Associative array containing the types to be used for casting.
     * @return \Cake\Database\Query\InsertQuery
     */
    public function insertQuery(?string $table = null, array $values = [], array $types = []): InsertQuery
    {
        $query = new InsertQuery($this);
        if ($table) {
            $query->into($table);
        }
        if ($values) {
            $columns = array_keys($values);
            $query->insert($columns, $types)
                ->values($values);
        }

        return $query;
    }

    /**
     * Executes an UPDATE statement on the specified table.
     *
     * @param string $table the table to update rows from
     * @param array $values values to be updated
     * @param array $conditions conditions to be set for update statement
     * @param array<string> $types list of associative array containing the types to be used for casting
     * @return \Cake\Database\StatementInterface
     */
    public function update(string $table, array $values, array $conditions = [], array $types = []): StatementInterface
    {
        return $this->getDisconnectRetry()->run(function () use ($table, $values, $conditions, $types) {
            return $this->updateQuery($table, $values, $conditions, $types)->execute();
        });
    }

    /**
     * Create a new UpdateQuery instance for this connection.
     *
     * @param \Cake\Database\ExpressionInterface|string|null $table The table to update rows of.
     * @param array $values Values to be updated.
     * @param array $conditions Conditions to be set for the update statement.
     * @param array<string, string> $types Associative array containing the types to be used for casting.
     * @return \Cake\Database\Query\UpdateQuery
     */
    public function updateQuery(
        $table = null,
        array $values = [],
        array $conditions = [],
        array $types = []
    ): UpdateQuery {
        $query = new UpdateQuery($this);
        if ($table) {
            $query->update($table);
        }
        if ($values) {
            $query->set($values, $types);
        }
        if ($conditions) {
            $query->where($conditions, $types);
        }

        return $query;
    }

    /**
     * Executes a DELETE statement on the specified table.
     *
     * @param string $table the table to delete rows from
     * @param array $conditions conditions to be set for delete statement
     * @param array<string> $types list of associative array containing the types to be used for casting
     * @return \Cake\Database\StatementInterface
     */
    public function delete(string $table, array $conditions = [], array $types = []): StatementInterface
    {
        return $this->getDisconnectRetry()->run(function () use ($table, $conditions, $types) {
            return $this->deleteQuery($table, $conditions, $types)->execute();
        });
    }

    /**
     * Create a new DeleteQuery instance for this connection.
     *
     * @param string|null $table The table to delete rows from.
     * @param array $conditions Conditions to be set for the delete statement.
     * @param array<string, string> $types Associative array containing the types to be used for casting.
     * @return \Cake\Database\Query\DeleteQuery
     */
    public function deleteQuery(?string $table = null, array $conditions = [], array $types = []): DeleteQuery
    {
        $query = new DeleteQuery($this);
        if ($table) {
            $query->from($table);
        }
        if ($conditions) {
            $query->where($conditions, $types);
        }

        return $query;
    }

    /**
     * Starts a new transaction.
     *
     * @return void
     */
    public function begin(): void
    {
        if (!$this->_transactionStarted) {
            if ($this->_logQueries) {
                $this->log('BEGIN');
            }

            $this->getDisconnectRetry()->run(function (): void {
                $this->getDriver()->beginTransaction();
            });

            $this->_transactionLevel = 0;
            $this->_transactionStarted = true;
            $this->nestedTransactionRollbackException = null;

            return;
        }

        $this->_transactionLevel++;
        if ($this->isSavePointsEnabled()) {
            $this->createSavePoint((string)$this->_transactionLevel);
        }
    }

    /**
     * Commits current transaction.
     *
     * @return bool true on success, false otherwise
     */
    public function commit(): bool
    {
        if (!$this->_transactionStarted) {
            return false;
        }

        if ($this->_transactionLevel === 0) {
            if ($this->wasNestedTransactionRolledback()) {
                /** @var \Cake\Database\Exception\NestedTransactionRollbackException $e */
                $e = $this->nestedTransactionRollbackException;
                $this->nestedTransactionRollbackException = null;
                throw $e;
            }

            $this->_transactionStarted = false;
            $this->nestedTransactionRollbackException = null;
            if ($this->_logQueries) {
                $this->log('COMMIT');
            }

            return $this->getDriver()->commitTransaction();
        }
        if ($this->isSavePointsEnabled()) {
            $this->releaseSavePoint((string)$this->_transactionLevel);
        }

        $this->_transactionLevel--;

        return true;
    }

    /**
     * Rollback current transaction.
     *
     * @param bool|null $toBeginning Whether the transaction should be rolled back to the
     * beginning of it. Defaults to false if using savepoints, or true if not.
     * @return bool
     */
    public function rollback(?bool $toBeginning = null): bool
    {
        if (!$this->_transactionStarted) {
            return false;
        }

        $useSavePoint = $this->isSavePointsEnabled();
        if ($toBeginning === null) {
            $toBeginning = !$useSavePoint;
        }
        if ($this->_transactionLevel === 0 || $toBeginning) {
            $this->_transactionLevel = 0;
            $this->_transactionStarted = false;
            $this->nestedTransactionRollbackException = null;
            if ($this->_logQueries) {
                $this->log('ROLLBACK');
            }
            $this->getDriver()->rollbackTransaction();

            return true;
        }

        $savePoint = $this->_transactionLevel--;
        if ($useSavePoint) {
            $this->rollbackSavepoint($savePoint);
        } elseif ($this->nestedTransactionRollbackException === null) {
            $this->nestedTransactionRollbackException = new NestedTransactionRollbackException();
        }

        return true;
    }

    /**
     * Enables/disables the usage of savepoints, enables only if driver the allows it.
     *
     * If you are trying to enable this feature, make sure you check
     * `isSavePointsEnabled()` to verify that savepoints were enabled successfully.
     *
     * @param bool $enable Whether save points should be used.
     * @return $this
     */
    public function enableSavePoints(bool $enable = true)
    {
        if ($enable === false) {
            $this->_useSavePoints = false;
        } else {
            $this->_useSavePoints = $this->getDriver()->supports(DriverInterface::FEATURE_SAVEPOINT);
        }

        return $this;
    }

    /**
     * Disables the usage of savepoints.
     *
     * @return $this
     */
    public function disableSavePoints()
    {
        $this->_useSavePoints = false;

        return $this;
    }

    /**
     * Returns whether this connection is using savepoints for nested transactions
     *
     * @return bool true if enabled, false otherwise
     */
    public function isSavePointsEnabled(): bool
    {
        return $this->_useSavePoints;
    }

    /**
     * Creates a new save point for nested transactions.
     *
     * @param string|int $name Save point name or id
     * @return void
     */
    public function createSavePoint($name): void
    {
        $this->execute($this->getDriver()->savePointSQL($name))->closeCursor();
    }

    /**
     * Releases a save point by its name.
     *
     * @param string|int $name Save point name or id
     * @return void
     */
    public function releaseSavePoint($name): void
    {
        $sql = $this->getDriver()->releaseSavePointSQL($name);
        if ($sql) {
            $this->execute($sql)->closeCursor();
        }
    }

    /**
     * Rollback a save point by its name.
     *
     * @param string|int $name Save point name or id
     * @return void
     */
    public function rollbackSavepoint($name): void
    {
        $this->execute($this->getDriver()->rollbackSavePointSQL($name))->closeCursor();
    }

    /**
     * Run driver specific SQL to disable foreign key checks.
     *
     * @return void
     */
    public function disableForeignKeys(): void
    {
        $this->getDisconnectRetry()->run(function (): void {
            $this->execute($this->getDriver()->disableForeignKeySQL())->closeCursor();
        });
    }

    /**
     * Run driver specific SQL to enable foreign key checks.
     *
     * @return void
     */
    public function enableForeignKeys(): void
    {
        $this->getDisconnectRetry()->run(function (): void {
            $this->execute($this->getDriver()->enableForeignKeySQL())->closeCursor();
        });
    }

    /**
     * Returns whether the driver supports adding or dropping constraints
     * to already created tables.
     *
     * @return bool true if driver supports dynamic constraints
     * @deprecated 4.3.0 Fixtures no longer dynamically drop and create constraints.
     */
    public function supportsDynamicConstraints(): bool
    {
        return $this->getDriver()->supportsDynamicConstraints();
    }

    /**
     * @inheritDoc
     */
    public function transactional(callable $callback)
    {
        $this->begin();

        try {
            $result = $callback($this);
        } catch (Throwable $e) {
            $this->rollback(false);
            throw $e;
        }

        if ($result === false) {
            $this->rollback(false);

            return false;
        }

        try {
            $this->commit();
        } catch (NestedTransactionRollbackException $e) {
            $this->rollback(false);
            throw $e;
        }

        return $result;
    }

    /**
     * Returns whether some nested transaction has been already rolled back.
     *
     * @return bool
     */
    protected function wasNestedTransactionRolledback(): bool
    {
        return $this->nestedTransactionRollbackException instanceof NestedTransactionRollbackException;
    }

    /**
     * @inheritDoc
     */
    public function disableConstraints(callable $callback)
    {
        return $this->getDisconnectRetry()->run(function () use ($callback) {
            $this->disableForeignKeys();

            try {
                $result = $callback($this);
            } finally {
                $this->enableForeignKeys();
            }

            return $result;
        });
    }

    /**
     * Checks if a transaction is running.
     *
     * @return bool True if a transaction is running else false.
     */
    public function inTransaction(): bool
    {
        return $this->_transactionStarted;
    }

    /**
     * Quotes value to be used safely in database query.
     *
     * This uses `PDO::quote()` and requires `supportsQuoting()` to work.
     *
     * @param mixed $value The value to quote.
     * @param \Cake\Database\TypeInterface|string|int $type Type to be used for determining kind of quoting to perform
     * @return string Quoted value
     * @deprecated 4.5.0 Use getDriver()->quote() instead.
     */
    public function quote($value, $type = 'string'): string
    {
        deprecationWarning('Use getDriver()->quote() instead.');
        [$value, $type] = $this->cast($value, $type);

        return $this->getDriver()->quote($value, $type);
    }

    /**
     * Checks if using `quote()` is supported.
     *
     * This is not required to use `quoteIdentifier()`.
     *
     * @return bool
     * @deprecated 4.5.0 Use getDriver()->supportsQuoting() instead.
     */
    public function supportsQuoting(): bool
    {
        deprecationWarning('Use getDriver()->supportsQuoting() instead.');

        return $this->getDriver()->supports(DriverInterface::FEATURE_QUOTE);
    }

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words.
     *
     * This does not require `supportsQuoting()` to work.
     *
     * @param string $identifier The identifier to quote.
     * @return string
     * @deprecated 4.5.0 Use getDriver()->quoteIdentifier() instead.
     */
    public function quoteIdentifier(string $identifier): string
    {
        deprecationWarning('Use getDriver()->quoteIdentifier() instead.');

        return $this->getDriver()->quoteIdentifier($identifier);
    }

    /**
     * Enables or disables metadata caching for this connection
     *
     * Changing this setting will not modify existing schema collections objects.
     *
     * @param string|bool $cache Either boolean false to disable metadata caching, or
     *   true to use `_cake_model_` or the name of the cache config to use.
     * @return void
     */
    public function cacheMetadata($cache): void
    {
        $this->_schemaCollection = null;
        $this->_config['cacheMetadata'] = $cache;
        if (is_string($cache)) {
            $this->cacher = null;
        }
    }

    /**
     * @inheritDoc
     */
    public function setCacher(CacheInterface $cacher)
    {
        $this->cacher = $cacher;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCacher(): CacheInterface
    {
        if ($this->cacher !== null) {
            return $this->cacher;
        }

        $configName = $this->_config['cacheMetadata'] ?? '_cake_model_';
        if (!is_string($configName)) {
            $configName = '_cake_model_';
        }

        if (!class_exists(Cache::class)) {
            throw new RuntimeException(
                'To use caching you must either set a cacher using Connection::setCacher()' .
                ' or require the cakephp/cache package in your composer config.'
            );
        }

        return $this->cacher = Cache::pool($configName);
    }

    /**
     * Enable/disable query logging
     *
     * @param bool $enable Enable/disable query logging
     * @return $this
     * @deprecated 4.5.0 Connection logging is moving to the driver in 5.x
     */
    public function enableQueryLogging(bool $enable = true)
    {
        $this->_logQueries = $enable;

        return $this;
    }

    /**
     * Disable query logging
     *
     * @return $this
     * @deprecated 4.5.0 Connection logging is moving to the driver in 5.x
     */
    public function disableQueryLogging()
    {
        $this->_logQueries = false;

        return $this;
    }

    /**
     * Check if query logging is enabled.
     *
     * @return bool
     * @deprecated 4.5.0 Connection logging is moving to the driver in 5.x
     */
    public function isQueryLoggingEnabled(): bool
    {
        return $this->_logQueries;
    }

    /**
     * Sets a logger
     *
     * @param \Psr\Log\LoggerInterface $logger Logger object
     * @return $this
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->_logger = $logger;

        return $this;
    }

    /**
     * Gets the logger object
     *
     * @return \Psr\Log\LoggerInterface logger instance
     */
    public function getLogger(): LoggerInterface
    {
        if ($this->_logger !== null) {
            return $this->_logger;
        }

        if (!class_exists(BaseLog::class)) {
            throw new RuntimeException(
                'For logging you must either set a logger using Connection::setLogger()' .
                ' or require the cakephp/log package in your composer config.'
            );
        }

        return $this->_logger = new QueryLogger(['connection' => $this->configName()]);
    }

    /**
     * Logs a Query string using the configured logger object.
     *
     * @param string $sql string to be logged
     * @return void
     */
    public function log(string $sql): void
    {
        $query = new LoggedQuery();
        $query->query = $sql;
        $this->getLogger()->debug((string)$query, ['query' => $query]);
    }

    /**
     * Returns a new statement object that will log the activity
     * for the passed original statement instance.
     *
     * @param \Cake\Database\StatementInterface $statement the instance to be decorated
     * @return \Cake\Database\Log\LoggingStatement
     */
    protected function _newLogger(StatementInterface $statement): LoggingStatement
    {
        $log = new LoggingStatement($statement, $this->getDriver());
        $log->setLogger($this->getLogger());

        return $log;
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $secrets = [
            'password' => '*****',
            'username' => '*****',
            'host' => '*****',
            'database' => '*****',
            'port' => '*****',
        ];
        $replace = array_intersect_key($secrets, $this->_config);
        $config = $replace + $this->_config;

        if (isset($config['read'])) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $config['read'] = array_intersect_key($secrets, $config['read']) + $config['read'];
        }
        if (isset($config['write'])) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $config['write'] = array_intersect_key($secrets, $config['write']) + $config['write'];
        }

        return [
            'config' => $config,
            'readDriver' => $this->readDriver,
            'writeDriver' => $this->writeDriver,
            'transactionLevel' => $this->_transactionLevel,
            'transactionStarted' => $this->_transactionStarted,
            'useSavePoints' => $this->_useSavePoints,
            'logQueries' => $this->_logQueries,
            'logger' => $this->_logger,
        ];
    }
}
