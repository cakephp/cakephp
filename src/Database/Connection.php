<?php
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

use Cake\Core\App;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Exception\MissingDriverException;
use Cake\Database\Exception\MissingExtensionException;
use Cake\Database\Exception\NestedTransactionRollbackException;
use Cake\Database\Log\LoggedQuery;
use Cake\Database\Log\LoggingStatement;
use Cake\Database\Log\QueryLogger;
use Cake\Database\Schema\CachedCollection;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Datasource\ConnectionInterface;
use Cake\Log\Log;
use Exception;

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
     * @var \Cake\Database\Driver
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
     * @var \Cake\Database\Log\QueryLogger|null
     */
    protected $_logger;

    /**
     * The schema collection object
     *
     * @var \Cake\Database\Schema\Collection|null
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
    public function __construct($config)
    {
        $this->_config = $config;

        $driver = '';
        if (!empty($config['driver'])) {
            $driver = $config['driver'];
        }
        $this->setDriver($driver, $config);

        if (!empty($config['log'])) {
            $this->logQueries($config['log']);
        }
    }

    /**
     * Destructor
     *
     * Disconnects the driver to release the connection.
     */
    public function __destruct()
    {
        if ($this->_transactionStarted && class_exists('Cake\Log\Log')) {
            Log::warning('The connection is going to be closed but there is an active transaction.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function config()
    {
        return $this->_config;
    }

    /**
     * {@inheritDoc}
     */
    public function configName()
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
     * @param \Cake\Database\Driver|string $driver The driver instance to use.
     * @param array $config Config for a new driver.
     * @throws \Cake\Database\Exception\MissingDriverException When a driver class is missing.
     * @throws \Cake\Database\Exception\MissingExtensionException When a driver's PHP extension is missing.
     * @return $this
     */
    public function setDriver($driver, $config = [])
    {
        if (is_string($driver)) {
            $className = App::className($driver, 'Database/Driver');
            if (!$className || !class_exists($className)) {
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
     * Gets the driver instance.
     *
     * @return \Cake\Database\Driver
     */
    public function getDriver()
    {
        return $this->_driver;
    }

    /**
     * Sets the driver instance. If a string is passed it will be treated
     * as a class name and will be instantiated.
     *
     * If no params are passed it will return the current driver instance.
     *
     * @deprecated 3.4.0 Use setDriver()/getDriver() instead.
     * @param \Cake\Database\Driver|string|null $driver The driver instance to use.
     * @param array $config Either config for a new driver or null.
     * @throws \Cake\Database\Exception\MissingDriverException When a driver class is missing.
     * @throws \Cake\Database\Exception\MissingExtensionException When a driver's PHP extension is missing.
     * @return \Cake\Database\Driver
     */
    public function driver($driver = null, $config = [])
    {
        if ($driver !== null) {
            $this->setDriver($driver, $config);
        }

        return $this->getDriver();
    }

    /**
     * Connects to the configured database.
     *
     * @throws \Cake\Database\Exception\MissingConnectionException if credentials are invalid.
     * @return bool true, if the connection was already established or the attempt was successful.
     */
    public function connect()
    {
        try {
            return $this->_driver->connect();
        } catch (Exception $e) {
            throw new MissingConnectionException(['reason' => $e->getMessage()]);
        }
    }

    /**
     * Disconnects from database server.
     *
     * @return void
     */
    public function disconnect()
    {
        $this->_driver->disconnect();
    }

    /**
     * Returns whether connection to database server was already established.
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->_driver->isConnected();
    }

    /**
     * Prepares a SQL statement to be executed.
     *
     * @param string|\Cake\Database\Query $sql The SQL to convert into a prepared statement.
     * @return \Cake\Database\StatementInterface
     */
    public function prepare($sql)
    {
        $statement = $this->_driver->prepare($sql);

        if ($this->_logQueries) {
            $statement = $this->_newLogger($statement);
        }

        return $statement;
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
    public function execute($query, array $params = [], array $types = [])
    {
        if (!empty($params)) {
            $statement = $this->prepare($query);
            $statement->bind($params, $types);
            $statement->execute();
        } else {
            $statement = $this->query($query);
        }

        return $statement;
    }

    /**
     * Compiles a Query object into a SQL string according to the dialect for this
     * connection's driver
     *
     * @param \Cake\Database\Query $query The query to be compiled
     * @param \Cake\Database\ValueBinder $generator The placeholder generator to use
     * @return string
     */
    public function compileQuery(Query $query, ValueBinder $generator)
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
    public function run(Query $query)
    {
        $statement = $this->prepare($query);
        $query->getValueBinder()->attachTo($statement);
        $statement->execute();

        return $statement;
    }

    /**
     * Executes a SQL statement and returns the Statement object as result.
     *
     * @param string $sql The SQL query to execute.
     * @return \Cake\Database\StatementInterface
     */
    public function query($sql)
    {
        $statement = $this->prepare($sql);
        $statement->execute();

        return $statement;
    }

    /**
     * Create a new Query instance for this connection.
     *
     * @return \Cake\Database\Query
     */
    public function newQuery()
    {
        return new Query($this);
    }

    /**
     * Sets a Schema\Collection object for this connection.
     *
     * @param \Cake\Database\Schema\Collection $collection The schema collection object
     * @return $this
     */
    public function setSchemaCollection(SchemaCollection $collection)
    {
        $this->_schemaCollection = $collection;

        return $this;
    }

    /**
     * Gets a Schema\Collection object for this connection.
     *
     * @return \Cake\Database\Schema\Collection
     */
    public function getSchemaCollection()
    {
        if ($this->_schemaCollection !== null) {
            return $this->_schemaCollection;
        }

        if (!empty($this->_config['cacheMetadata'])) {
            return $this->_schemaCollection = new CachedCollection($this, $this->_config['cacheMetadata']);
        }

        return $this->_schemaCollection = new SchemaCollection($this);
    }

    /**
     * Gets or sets a Schema\Collection object for this connection.
     *
     * @deprecated 3.4.0 Use setSchemaCollection()/getSchemaCollection()
     * @param \Cake\Database\Schema\Collection|null $collection The schema collection object
     * @return \Cake\Database\Schema\Collection
     */
    public function schemaCollection(SchemaCollection $collection = null)
    {
        if ($collection !== null) {
            $this->setSchemaCollection($collection);
        }

        return $this->getSchemaCollection();
    }

    /**
     * Executes an INSERT query on the specified table.
     *
     * @param string $table the table to insert values in
     * @param array $data values to be inserted
     * @param array $types list of associative array containing the types to be used for casting
     * @return \Cake\Database\StatementInterface
     */
    public function insert($table, array $data, array $types = [])
    {
        $columns = array_keys($data);

        return $this->newQuery()->insert($columns, $types)
            ->into($table)
            ->values($data)
            ->execute();
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
    public function update($table, array $data, array $conditions = [], $types = [])
    {
        return $this->newQuery()->update($table)
            ->set($data, $types)
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
    public function delete($table, $conditions = [], $types = [])
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
    public function begin()
    {
        if (!$this->_transactionStarted) {
            if ($this->_logQueries) {
                $this->log('BEGIN');
            }
            $this->_driver->beginTransaction();
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
    public function commit()
    {
        if (!$this->_transactionStarted) {
            return false;
        }

        if ($this->_transactionLevel === 0) {
            if ($this->wasNestedTransactionRolledback()) {
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
    public function rollback($toBeginning = null)
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
    public function enableSavePoints($enable)
    {
        if ($enable === false) {
            $this->_useSavePoints = false;
        } else {
            $this->_useSavePoints = $this->_driver->supportsSavePoints();
        }

        return $this;
    }

    /**
     * Returns whether this connection is using savepoints for nested transactions
     *
     * @return bool true if enabled, false otherwise
     */
    public function isSavePointsEnabled()
    {
        return $this->_useSavePoints;
    }

    /**
     * Returns whether this connection is using savepoints for nested transactions
     * If a boolean is passed as argument it will enable/disable the usage of savepoints
     * only if driver the allows it.
     *
     * If you are trying to enable this feature, make sure you check the return value of this
     * function to verify it was enabled successfully.
     *
     * ### Example:
     *
     * `$connection->useSavePoints(true)` Returns true if drivers supports save points, false otherwise
     * `$connection->useSavePoints(false)` Disables usage of savepoints and returns false
     * `$connection->useSavePoints()` Returns current status
     *
     * @deprecated 3.4.0 Use enableSavePoints()/isSavePointsEnabled() instead.
     * @param bool|null $enable Whether or not save points should be used.
     * @return bool true if enabled, false otherwise
     */
    public function useSavePoints($enable = null)
    {
        if ($enable !== null) {
            $this->enableSavePoints($enable);
        }

        return $this->isSavePointsEnabled();
    }

    /**
     * Creates a new save point for nested transactions.
     *
     * @param string $name The save point name.
     * @return void
     */
    public function createSavePoint($name)
    {
        $this->execute($this->_driver->savePointSQL($name))->closeCursor();
    }

    /**
     * Releases a save point by its name.
     *
     * @param string $name The save point name.
     * @return void
     */
    public function releaseSavePoint($name)
    {
        $this->execute($this->_driver->releaseSavePointSQL($name))->closeCursor();
    }

    /**
     * Rollback a save point by its name.
     *
     * @param string $name The save point name.
     * @return void
     */
    public function rollbackSavepoint($name)
    {
        $this->execute($this->_driver->rollbackSavePointSQL($name))->closeCursor();
    }

    /**
     * Run driver specific SQL to disable foreign key checks.
     *
     * @return void
     */
    public function disableForeignKeys()
    {
        $this->execute($this->_driver->disableForeignKeySQL())->closeCursor();
    }

    /**
     * Run driver specific SQL to enable foreign key checks.
     *
     * @return void
     */
    public function enableForeignKeys()
    {
        $this->execute($this->_driver->enableForeignKeySQL())->closeCursor();
    }

    /**
     * Returns whether the driver supports adding or dropping constraints
     * to already created tables.
     *
     * @return bool true if driver supports dynamic constraints
     */
    public function supportsDynamicConstraints()
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
        } catch (Exception $e) {
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
    protected function wasNestedTransactionRolledback()
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
        $this->disableForeignKeys();

        try {
            $result = $callback($this);
        } catch (Exception $e) {
            $this->enableForeignKeys();
            throw $e;
        }

        $this->enableForeignKeys();

        return $result;
    }

    /**
     * Checks if a transaction is running.
     *
     * @return bool True if a transaction is running else false.
     */
    public function inTransaction()
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
    public function quote($value, $type = null)
    {
        list($value, $type) = $this->cast($value, $type);

        return $this->_driver->quote($value, $type);
    }

    /**
     * Checks if the driver supports quoting.
     *
     * @return bool
     */
    public function supportsQuoting()
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
    public function quoteIdentifier($identifier)
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
    public function cacheMetadata($cache)
    {
        $this->_schemaCollection = null;
        $this->_config['cacheMetadata'] = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function logQueries($enable = null)
    {
        if ($enable === null) {
            return $this->_logQueries;
        }
        $this->_logQueries = $enable;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated 3.5.0 Use getLogger() and setLogger() instead.
     */
    public function logger($instance = null)
    {
        if ($instance === null) {
            return $this->getLogger();
        }

        $this->setLogger($instance);
    }

    /**
     * Sets a logger
     *
     * @param object $logger Logger object
     * @return $this
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;

        return $this;
    }

    /**
     * Gets the logger object
     *
     * @return object logger instance
     */
    public function getLogger()
    {
        if ($this->_logger === null) {
            $this->_logger = new QueryLogger();
        }

        return $this->_logger;
    }

    /**
     * Logs a Query string using the configured logger object.
     *
     * @param string $sql string to be logged
     * @return void
     */
    public function log($sql)
    {
        $query = new LoggedQuery();
        $query->query = $sql;
        $this->getLogger()->log($query);
    }

    /**
     * Returns a new statement object that will log the activity
     * for the passed original statement instance.
     *
     * @param \Cake\Database\StatementInterface $statement the instance to be decorated
     * @return \Cake\Database\Log\LoggingStatement
     */
    protected function _newLogger(StatementInterface $statement)
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
    public function __debugInfo()
    {
        $secrets = [
            'password' => '*****',
            'username' => '*****',
            'host' => '*****',
            'database' => '*****',
            'port' => '*****'
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
            'logger' => $this->_logger
        ];
    }
}
