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
use Cake\Core\Exception\CakeException;
use Cake\Core\Retry\CommandRetry;
use Cake\Database\Exception\MissingDriverException;
use Cake\Database\Exception\MissingExtensionException;
use Cake\Database\Exception\NestedTransactionRollbackException;
use Cake\Database\Query\DeleteQuery;
use Cake\Database\Query\InsertQuery;
use Cake\Database\Query\QueryFactory;
use Cake\Database\Query\SelectQuery;
use Cake\Database\Query\UpdateQuery;
use Cake\Database\Retry\ReconnectStrategy;
use Cake\Database\Schema\CachedCollection;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\CollectionInterface as SchemaCollectionInterface;
use Cake\Datasource\ConnectionInterface;
use Cake\Log\Log;
use Closure;
use Psr\SimpleCache\CacheInterface;
use Throwable;
use function Cake\Core\env;

/**
 * Represents a connection with a database server.
 */
class Connection implements ConnectionInterface
{
    /**
     * Contains the configuration params for this connection.
     *
     * @var array<string, mixed>
     */
    protected array $_config;

    /**
     * @var \Cake\Database\Driver
     */
    protected Driver $readDriver;

    /**
     * @var \Cake\Database\Driver
     */
    protected Driver $writeDriver;

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

    protected QueryFactory $queryFactory;

    /**
     * Constructor.
     *
     * ### Available options:
     *
     * - `driver` Sort name or FQCN for driver.
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
    }

    /**
     * Creates read and write drivers.
     *
     * @param array<string, mixed> $config Connection config
     * @return array<string, \Cake\Database\Driver>
     * @phpstan-return array{read: \Cake\Database\Driver, write: \Cake\Database\Driver}
     */
    protected function createDrivers(array $config): array
    {
        $driver = $config['driver'] ?? '';
        if (!is_string($driver)) {
            assert($driver instanceof Driver);
            if (!$driver->enabled()) {
                throw new MissingExtensionException(['driver' => $driver::class, 'name' => $this->configName()]);
            }

            // Legacy support for setting instance instead of driver class
            return [self::ROLE_READ => $driver, self::ROLE_WRITE => $driver];
        }

        /** @var class-string<\Cake\Database\Driver>|null $driverClass */
        $driverClass = App::className($driver, 'Database/Driver');
        if ($driverClass === null) {
            throw new MissingDriverException(['driver' => $driver, 'connection' => $this->configName()]);
        }

        $sharedConfig = array_diff_key($config, array_flip([
            'name',
            'className',
            'driver',
            'cacheMetaData',
            'cacheKeyPrefix',
            'read',
            'write',
        ]));

        $writeConfig = ($config['write'] ?? []) + $sharedConfig;
        $readConfig = ($config['read'] ?? []) + $sharedConfig;
        if (array_key_exists('write', $config) || array_key_exists('read', $config)) {
            $readDriver = new $driverClass(['_role' => self::ROLE_READ] + $readConfig);
            $writeDriver = new $driverClass(['_role' => self::ROLE_WRITE] + $writeConfig);
        } else {
            $readDriver = new $driverClass(['_role' => self::ROLE_WRITE] + $writeConfig);
            $writeDriver = $readDriver;
        }

        if (!$writeDriver->enabled()) {
            throw new MissingExtensionException(['driver' => $writeDriver::class, 'name' => $this->configName()]);
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
            $message = 'The connection is going to be closed but there is an active transaction.';

            $requestUrl = env('REQUEST_URI');
            if ($requestUrl) {
                $message .= "\nRequest URL: " . $requestUrl;
            }

            $clientIp = env('REMOTE_ADDR');
            if ($clientIp) {
                $message .= "\nClient IP: " . $clientIp;
            }

            Log::warning($message);
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
     * @return \Cake\Database\Driver
     */
    public function getDriver(string $role = self::ROLE_WRITE): Driver
    {
        assert($role === self::ROLE_READ || $role === self::ROLE_WRITE);

        return $role === self::ROLE_READ ? $this->readDriver : $this->writeDriver;
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
        return $this->getDisconnectRetry()->run(fn() => $this->getDriver()->execute($sql, $params, $types));
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
        return $this->getDisconnectRetry()->run(fn() => $this->getDriver($query->getConnectionRole())->run($query));
    }

    /**
     * Get query factory instance.
     *
     * @return \Cake\Database\Query\QueryFactory
     */
    public function queryFactory(): QueryFactory
    {
        return $this->queryFactory ??= new QueryFactory($this);
    }

    /**
     * Create a new SelectQuery instance for this connection.
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array|string|float|int $fields Fields/columns list for the query.
     * @param array|string $table The table or list of tables to query.
     * @param array<string, string> $types Associative array containing the types to be used for casting.
     * @return \Cake\Database\Query\SelectQuery<mixed>
     */
    public function selectQuery(
        ExpressionInterface|Closure|array|string|float|int $fields = [],
        array|string $table = [],
        array $types = [],
    ): SelectQuery {
        return $this->queryFactory()->select($fields, $table, $types);
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
        return $this->queryFactory()->insert($table, $values, $types);
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
        ExpressionInterface|string|null $table = null,
        array $values = [],
        array $conditions = [],
        array $types = [],
    ): UpdateQuery {
        return $this->queryFactory()->update($table, $values, $conditions, $types);
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
        return $this->queryFactory()->delete($table, $conditions, $types);
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
                $this->getCacher(),
            );
        }

        return $this->_schemaCollection = new SchemaCollection($this);
    }

    /**
     * Executes an INSERT query on the specified table.
     *
     * @param string $table the table to insert values in
     * @param array $values values to be inserted
     * @param array<string, string> $types Array containing the types to be used for casting
     * @return \Cake\Database\StatementInterface
     */
    public function insert(string $table, array $values, array $types = []): StatementInterface
    {
        return $this->insertQuery($table, $values, $types)->execute();
    }

    /**
     * Executes an UPDATE statement on the specified table.
     *
     * @param string $table the table to update rows from
     * @param array $values values to be updated
     * @param array $conditions conditions to be set for update statement
     * @param array<string, string> $types list of associative array containing the types to be used for casting
     * @return \Cake\Database\StatementInterface
     */
    public function update(string $table, array $values, array $conditions = [], array $types = []): StatementInterface
    {
        return $this->updateQuery($table, $values, $conditions, $types)->execute();
    }

    /**
     * Executes a DELETE statement on the specified table.
     *
     * @param string $table the table to delete rows from
     * @param array $conditions conditions to be set for delete statement
     * @param array<string, string> $types list of associative array containing the types to be used for casting
     * @return \Cake\Database\StatementInterface
     */
    public function delete(string $table, array $conditions = [], array $types = []): StatementInterface
    {
        return $this->deleteQuery($table, $conditions, $types)->execute();
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
                $e = $this->nestedTransactionRollbackException;
                assert($e !== null);
                $this->nestedTransactionRollbackException = null;
                throw $e;
            }

            $this->_transactionStarted = false;
            $this->nestedTransactionRollbackException = null;

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
        $toBeginning ??= !$useSavePoint;
        if ($this->_transactionLevel === 0 || $toBeginning) {
            $this->_transactionLevel = 0;
            $this->_transactionStarted = false;
            $this->nestedTransactionRollbackException = null;
            $this->getDriver()->rollbackTransaction();

            return true;
        }

        $savePoint = $this->_transactionLevel--;
        if ($useSavePoint) {
            $this->rollbackSavepoint($savePoint);
        } else {
            $this->nestedTransactionRollbackException ??= new NestedTransactionRollbackException();
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
            $this->_useSavePoints = $this->getDriver()->supports(DriverFeatureEnum::SAVEPOINT);
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
        $this->execute($this->getDriver()->savePointSQL($name));
    }

    /**
     * Releases a save point by its name.
     *
     * @param string|int $name Save point name or id
     * @return void
     */
    public function releaseSavePoint(string|int $name): void
    {
        $sql = $this->getDriver()->releaseSavePointSQL($name);
        if ($sql) {
            $this->execute($sql);
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
        $this->execute($this->getDriver()->rollbackSavePointSQL($name));
    }

    /**
     * Run driver specific SQL to disable foreign key checks.
     *
     * @return void
     */
    public function disableForeignKeys(): void
    {
        $this->getDisconnectRetry()->run(function (): void {
            $this->execute($this->getDriver()->disableForeignKeySQL());
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
            $this->execute($this->getDriver()->enableForeignKeySQL());
        });
    }

    /**
     * Executes a callback inside a transaction, if any exception occurs
     * while executing the passed callback, the transaction will be rolled back
     * If the result of the callback is `false`, the transaction will
     * also be rolled back. Otherwise the transaction is committed after executing
     * the callback.
     *
     * The callback will receive the connection instance as its first argument.
     *
     * ### Example:
     *
     * ```
     * $connection->transactional(function ($connection) {
     *   $connection->deleteQuery('users')->execute();
     * });
     * ```
     *
     * @param \Closure $callback The callback to execute within a transaction.
     * @return mixed The return value of the callback.
     * @throws \Exception Will re-throw any exception raised in $callback after
     *   rolling back the transaction.
     */
    public function transactional(Closure $callback): mixed
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
     * Run an operation with constraints disabled.
     *
     * Constraints should be re-enabled after the callback succeeds/fails.
     *
     * ### Example:
     *
     * ```
     * $connection->disableConstraints(function ($connection) {
     *   $connection->insertQuery('users')->execute();
     * });
     * ```
     *
     * @param \Closure $callback Callback to run with constraints disabled
     * @return mixed The return value of the callback.
     * @throws \Exception Will re-throw any exception raised in $callback after
     *   rolling back the transaction.
     */
    public function disableConstraints(Closure $callback): mixed
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
            throw new CakeException(
                'To use caching you must either set a cacher using Connection::setCacher()' .
                ' or require the cakephp/cache package in your composer config.',
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

        if (isset($config['read'])) {
            $config['read'] = array_intersect_key($secrets, $config['read']) + $config['read'];
        }
        if (isset($config['write'])) {
            $config['write'] = array_intersect_key($secrets, $config['write']) + $config['write'];
        }

        return [
            'config' => $config,
            'readDriver' => $this->readDriver,
            'writeDriver' => $this->writeDriver,
            'transactionLevel' => $this->_transactionLevel,
            'transactionStarted' => $this->_transactionStarted,
            'useSavePoints' => $this->_useSavePoints,
        ];
    }
}
