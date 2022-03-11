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
use Cake\Database\Retry\ReconnectStrategy;
use Cake\Database\Schema\CachedCollection;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\CollectionInterface as SchemaCollectionInterface;
use Cake\Datasource\ConnectionInterface;
use Cake\Log\Log;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Throwable;

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
    protected array $_config;

    /**
     * Driver object, responsible for creating the real connection
     * and provide specific SQL dialect.
     *
     * @var \Cake\Database\DriverInterface
     */
    protected DriverInterface $_driver;

    /**
     * Contains how many nested transactions have been started.
     *
     * @var int
     */
    protected int $_transactionLevel = 0;

    /**
     * Whether a transaction is active in this connection.
     *
     * @var bool
     */
    protected bool $_transactionStarted = false;

    /**
     * Whether this connection can and should use savepoints for nested
     * transactions.
     *
     * @var bool
     */
    protected bool $_useSavePoints = false;

    /**
     * Cacher object instance.
     *
     * @var \Psr\SimpleCache\CacheInterface|null
     */
    protected ?CacheInterface $cacher = null;

    /**
     * The schema collection object
     *
     * @var \Cake\Database\Schema\CollectionInterface|null
     */
    protected ?SchemaCollectionInterface $_schemaCollection = null;

    /**
     * NestedTransactionRollbackException object instance, will be stored if
     * the rollback method is called in some nested transaction.
     *
     * @var \Cake\Database\Exception\NestedTransactionRollbackException|null
     */
    protected ?NestedTransactionRollbackException $nestedTransactionRollbackException = null;

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

        $driverConfig = array_diff_key($config, array_flip([
            'name',
            'driver',
            'cacheMetaData',
            'cacheKeyPrefix',
        ]));
        $this->_driver = $this->createDriver($config['driver'] ?? '', $driverConfig);
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
     * Creates driver from name, class name or instance.
     *
     * @param \Cake\Database\DriverInterface|string $name Driver name, class name or instance.
     * @param array $config Driver config if $name is not an instance.
     * @return \Cake\Database\DriverInterface
     * @throws \Cake\Database\Exception\MissingDriverException When a driver class is missing.
     * @throws \Cake\Database\Exception\MissingExtensionException When a driver's PHP extension is missing.
     */
    protected function createDriver(DriverInterface|string $name, array $config): DriverInterface
    {
        $driver = $name;
        if (is_string($driver)) {
            /** @psalm-var class-string<\Cake\Database\DriverInterface>|null $className */
            $className = App::className($driver, 'Database/Driver');
            if ($className === null) {
                throw new MissingDriverException(['driver' => $driver, 'connection' => $this->configName()]);
            }
            $driver = new $className($config);
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
     * @return \Cake\Database\DriverInterface
     */
    public function getDriver(): DriverInterface
    {
        return $this->_driver;
    }

    /**
     * Connects to the configured database.
     *
     * @throws \Cake\Database\Exception\MissingConnectionException If database connection could not be established.
     * @return void
     */
    public function connect(): void
    {
        try {
            $this->_driver->connect();
        } catch (MissingConnectionException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new MissingConnectionException(
                [
                    'driver' => App::shortName(get_class($this->_driver), 'Database/Driver'),
                    'reason' => $e->getMessage(),
                ],
                null,
                $e
            );
        }
    }

    /**
     * Disconnects from database server.
     *
     * @return void
     */
    public function disconnect(): void
    {
        $this->_driver->disconnect();
    }

    /**
     * Returns whether connection to database server was already established.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->_driver->isConnected();
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
        return $this->getDisconnectRetry()->run(fn () => $this->_driver->execute($sql, $params, $types));
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
        return $this->getDisconnectRetry()->run(fn () => $this->_driver->run($query));
    }

    /**
     * Create a new Query instance for this connection.
     *
     * @return \Cake\Database\Query
     */
    public function newQuery(): Query
    {
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
     * @param array<string, string> $types list of associative array containing the types to be used for casting
     * @return \Cake\Database\StatementInterface
     */
    public function insert(string $table, array $values, array $types = []): StatementInterface
    {
        $columns = array_keys($values);

        return $this->newQuery()->insert($columns, $types)
            ->into($table)
            ->values($values)
            ->execute();
    }

    /**
     * Executes an UPDATE statement on the specified table.
     *
     * @param string $table the table to update rows from
     * @param array $values values to be updated
     * @param array $conditions conditions to be set for update statement
     * @param array $types list of associative array containing the types to be used for casting
     * @return \Cake\Database\StatementInterface
     */
    public function update(string $table, array $values, array $conditions = [], array $types = []): StatementInterface
    {
        return $this->newQuery()->update($table)
            ->set($values, $types)
            ->where($conditions, $types)
            ->execute();
    }

    /**
     * Executes a DELETE statement on the specified table.
     *
     * @param string $table the table to delete rows from
     * @param array $conditions conditions to be set for delete statement
     * @param array $types list of associative array containing the types to be used for casting
     * @return \Cake\Database\StatementInterface
     */
    public function delete(string $table, array $conditions = [], array $types = []): StatementInterface
    {
        return $this->newQuery()->delete($table)
            ->where($conditions, $types)
            ->execute();
    }

    /**
     * Starts a new transaction.
     *
     * @return void
     */
    public function begin(): void
    {
        if (!$this->_transactionStarted) {
            $this->getDisconnectRetry()->run(function (): void {
                $this->_driver->beginTransaction();
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

            return $this->_driver->commitTransaction();
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
            $this->_driver->rollbackTransaction();

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
            $this->_useSavePoints = $this->_driver->supports(DriverInterface::FEATURE_SAVEPOINT);
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
    public function createSavePoint(string|int $name): void
    {
        $this->execute($this->_driver->savePointSQL($name))->closeCursor();
    }

    /**
     * Releases a save point by its name.
     *
     * @param string|int $name Save point name or id
     * @return void
     */
    public function releaseSavePoint(string|int $name): void
    {
        $sql = $this->_driver->releaseSavePointSQL($name);
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
    public function rollbackSavepoint(string|int $name): void
    {
        $this->execute($this->_driver->rollbackSavePointSQL($name))->closeCursor();
    }

    /**
     * Run driver specific SQL to disable foreign key checks.
     *
     * @return void
     */
    public function disableForeignKeys(): void
    {
        $this->getDisconnectRetry()->run(function (): void {
            $this->execute($this->_driver->disableForeignKeySQL())->closeCursor();
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
            $this->execute($this->_driver->enableForeignKeySQL())->closeCursor();
        });
    }

    /**
     * @inheritDoc
     */
    public function transactional(callable $callback): mixed
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
    public function disableConstraints(callable $callback): mixed
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
     */
    public function quote(mixed $value, TypeInterface|string|int $type = 'string'): string
    {
        [$value, $type] = $this->cast($value, $type);

        return $this->_driver->quote($value, $type);
    }

    /**
     * Checks if using `quote()` is supported.
     *
     * This is not required to use `quoteIdentifier()`.
     *
     * @return bool
     */
    public function supportsQuoting(): bool
    {
        return $this->_driver->supports(DriverInterface::FEATURE_QUOTE);
    }

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words.
     *
     * This does not require `supportsQuoting()` to work.
     *
     * @param string $identifier The identifier to quote.
     * @return string
     */
    public function quoteIdentifier(string $identifier): string
    {
        return $this->_driver->quoteIdentifier($identifier);
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
    public function cacheMetadata(string|bool $cache): void
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

        return [
            'config' => $config,
            'driver' => $this->_driver,
            'transactionLevel' => $this->_transactionLevel,
            'transactionStarted' => $this->_transactionStarted,
            'useSavePoints' => $this->_useSavePoints,
        ];
    }
}
