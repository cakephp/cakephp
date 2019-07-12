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
use Cake\Database\Retry\ReconnectStrategy;
use Cake\Database\Schema\CachedCollection;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\CollectionInterface as SchemaCollectionInterface;
use Cake\Datasource\ConnectionInterface;
use Cake\Log\Log;
use Psr\Log\LoggerInterface;
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
     * @var array
     */
    protected $_config;

    /**
     * Driver object, responsible for creating the real connection
     * and provide specific SQL dialect.
     *
     * @var \Cake\Database\DriverInterface
     */
    protected $_driver;

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
     * @param array $config configuration for connecting to database
     */
    public function __construct(array $config)
    {
        $this->_config = $config;

        $driver = '';
        if (!empty($config['driver'])) {
            $driver = $config['driver'];
        }
        $this->setDriver($driver, $config);

        if (!empty($config['log'])) {
            $this->enableQueryLogging((bool)$config['log']);
        }
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
        if (empty($this->_config['name'])) {
            return '';
        }

        return $this->_config['name'];
    }

    /**
     * Sets the driver instance. If a string is passed it will be treated
     * as a class name and will be instantiated.
     *
     * @param \Cake\Database\DriverInterface|string $driver The driver instance to use.
     * @param array $config Config for a new driver.
     * @throws \Cake\Database\Exception\MissingDriverException When a driver class is missing.
     * @throws \Cake\Database\Exception\MissingExtensionException When a driver's PHP extension is missing.
     * @return $this
     */
    public function setDriver($driver, $config = [])
    {
        if (is_string($driver)) {
            $className = App::className($driver, 'Database/Driver');
            if ($className === null) {
                throw new MissingDriverException(['driver' => $driver]);
            }
            $driver = new $className($config);
        }
        if (!$driver->enabled()) {
            throw new MissingExtensionException(['driver' => get_class($driver)]);
        }

        $this->_driver = $driver;

        return $this;
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
     * @throws \Cake\Database\Exception\MissingConnectionException if credentials are invalid.
     * @return bool true, if the connection was already established or the attempt was successful.
     */
    public function connect(): bool
    {
        try {
            return $this->_driver->connect();
        } catch (Throwable $e) {
            throw new MissingConnectionException(['reason' => $e->getMessage()], null, $e);
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
     * Prepares a SQL statement to be executed.
     *
     * @param string|\Cake\Database\Query $sql The SQL to convert into a prepared statement.
     * @return \Cake\Database\StatementInterface
     */
    public function prepare($sql): StatementInterface
    {
        return $this->getDisconnectRetry()->run(function () use ($sql) {
            $statement = $this->_driver->prepare($sql);

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
     * @param string $query SQL to be executed and interpolated with $params
     * @param array $params list or associative array of params to be interpolated in $query as values
     * @param array $types list or associative array of types to be used for casting values in query
     * @return \Cake\Database\StatementInterface executed statement
     */
    public function execute(string $query, array $params = [], array $types = []): StatementInterface
    {
        return $this->getDisconnectRetry()->run(function () use ($query, $params, $types) {
            if (!empty($params)) {
                $statement = $this->prepare($query);
                $statement->bind($params, $types);
                $statement->execute();
            } else {
                $statement = $this->query($query);
            }

            return $statement;
        });
    }

    /**
     * Compiles a Query object into a SQL string according to the dialect for this
     * connection's driver
     *
     * @param \Cake\Database\Query $query The query to be compiled
     * @param \Cake\Database\ValueBinder $generator The placeholder generator to use
     * @return string
     */
    public function compileQuery(Query $query, ValueBinder $generator): string
    {
        return $this->getDriver()->compileQuery($query, $generator)[1];
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
     * Executes a SQL statement and returns the Statement object as result.
     *
     * @param string $sql The SQL query to execute.
     * @return \Cake\Database\StatementInterface
     */
    public function query(string $sql): StatementInterface
    {
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
                $this->configName(),
                $this->getCacher()
            );
        }

        return $this->_schemaCollection = new SchemaCollection($this);
    }

    /**
     * Executes an INSERT query on the specified table.
     *
     * @param string $table the table to insert values in
     * @param array $data values to be inserted
     * @param array $types list of associative array containing the types to be used for casting
     * @return \Cake\Database\StatementInterface
     */
    public function insert(string $table, array $data, array $types = []): StatementInterface
    {
        return $this->getDisconnectRetry()->run(function () use ($table, $data, $types) {
            $columns = array_keys($data);

            return $this->newQuery()->insert($columns, $types)
                ->into($table)
                ->values($data)
                ->execute();
        });
    }

    /**
     * Executes an UPDATE statement on the specified table.
     *
     * @param string $table the table to update rows from
     * @param array $data values to be updated
     * @param array $conditions conditions to be set for update statement
     * @param array $types list of associative array containing the types to be used for casting
     * @return \Cake\Database\StatementInterface
     */
    public function update(string $table, array $data, array $conditions = [], array $types = []): StatementInterface
    {
        return $this->getDisconnectRetry()->run(function () use ($table, $data, $conditions, $types) {
            return $this->newQuery()->update($table)
                ->set($data, $types)
                ->where($conditions, $types)
                ->execute();
        });
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
        return $this->getDisconnectRetry()->run(function () use ($table, $conditions, $types) {
            return $this->newQuery()->delete($table)
                ->where($conditions, $types)
                ->execute();
        });
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
            if ($this->_logQueries) {
                $this->log('COMMIT');
            }

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
     * @param bool|null $toBeginning Whether or not the transaction should be rolled back to the
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
     * If you are trying to enable this feature, make sure you check the return value of this
     * function to verify it was enabled successfully.
     *
     * ### Example:
     *
     * `$connection->enableSavePoints(true)` Returns true if drivers supports save points, false otherwise
     * `$connection->enableSavePoints(false)` Disables usage of savepoints and returns false
     *
     * @param bool $enable Whether or not save points should be used.
     * @return $this
     */
    public function enableSavePoints(bool $enable)
    {
        if ($enable === false) {
            $this->_useSavePoints = false;
        } else {
            $this->_useSavePoints = $this->_driver->supportsSavePoints();
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
     * @param string|int $name The save point name.
     * @return void
     */
    public function createSavePoint($name): void
    {
        $this->execute($this->_driver->savePointSQL($name))->closeCursor();
    }

    /**
     * Releases a save point by its name.
     *
     * @param string|int $name The save point name.
     * @return void
     */
    public function releaseSavePoint($name): void
    {
        $this->execute($this->_driver->releaseSavePointSQL($name))->closeCursor();
    }

    /**
     * Rollback a save point by its name.
     *
     * @param string|int $name The save point name.
     * @return void
     */
    public function rollbackSavepoint($name): void
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
     * Returns whether the driver supports adding or dropping constraints
     * to already created tables.
     *
     * @return bool true if driver supports dynamic constraints
     */
    public function supportsDynamicConstraints(): bool
    {
        return $this->_driver->supportsDynamicConstraints();
    }

    /**
     * {@inheritDoc}
     *
     * ### Example:
     *
     * ```
     * $connection->transactional(function ($connection) {
     *   $connection->newQuery()->delete('users')->execute();
     * });
     * ```
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
     * {@inheritDoc}
     *
     * ### Example:
     *
     * ```
     * $connection->disableConstraints(function ($connection) {
     *   $connection->newQuery()->delete('users')->execute();
     * });
     * ```
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
     * @param mixed $value The value to quote.
     * @param string|null $type Type to be used for determining kind of quoting to perform
     * @return string Quoted value
     */
    public function quote($value, $type = null): string
    {
        [$value, $type] = $this->cast($value, $type);

        return $this->_driver->quote($value, $type);
    }

    /**
     * Checks if the driver supports quoting.
     *
     * @return bool
     */
    public function supportsQuoting(): bool
    {
        return $this->_driver->supportsQuoting();
    }

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words.
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
     * @param bool|string $cache Either boolean false to disable metadata caching, or
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
     * @param bool $value Enable/disable query logging
     * @return $this
     */
    public function enableQueryLogging(bool $value)
    {
        $this->_logQueries = $value;

        return $this;
    }

    /**
     * Disable query logging
     *
     * @return $this
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

        if (!class_exists(QueryLogger::class)) {
            throw new RuntimeException(
                'For logging you must either set a logger using Connection::setLogger()' .
                ' or require the cakephp/log package in your composer config.'
            );
        }

        return $this->_logger = new QueryLogger();
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
        $log = new LoggingStatement($statement, $this->_driver);
        $log->setLogger($this->getLogger());

        return $log;
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
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
            'logQueries' => $this->_logQueries,
            'logger' => $this->_logger,
        ];
    }
}
