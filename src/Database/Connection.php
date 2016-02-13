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
namespace Cake\Database;

use Cake\Database\ConnectionInterface;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Exception\MissingDriverException;
use Cake\Database\Exception\MissingExtensionException;
use Cake\Database\Log\LoggedQuery;
use Cake\Database\Log\LoggingStatement;
use Cake\Database\Log\QueryLogger;
use Cake\Database\Schema\CachedCollection;
use Cake\Database\Schema\Collection as SchemaCollection;
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
     * @var QueryLogger
     */
    protected $_logger = null;

    /**
     * The schema collection object
     *
     * @var \Cake\Database\Schema\Collection
     */
    protected $_schemaCollection;

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
        $this->driver($driver, $config);

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
        unset($this->_driver);
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
     * {@inheritDoc}
     */
    public function driver($driver = null, $config = [])
    {
        if ($driver === null) {
            return $this->_driver;
        }
        if (is_string($driver)) {
            if (!class_exists($driver)) {
                throw new MissingDriverException(['driver' => $driver]);
            }
            $driver = new $driver($config);
        }
        if (!$driver->enabled()) {
            throw new MissingExtensionException(['driver' => get_class($driver)]);
        }
        return $this->_driver = $driver;
    }

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        try {
            $this->_driver->connect();
            return true;
        } catch (Exception $e) {
            throw new MissingConnectionException(['reason' => $e->getMessage()]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function disconnect()
    {
        $this->_driver->disconnect();
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected()
    {
        return $this->_driver->isConnected();
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function compileQuery(Query $query, ValueBinder $generator)
    {
        return $this->driver()->compileQuery($query, $generator)[1];
    }

    /**
     * {@inheritDoc}
     */
    public function run(Query $query)
    {
        $statement = $this->prepare($query);
        $query->valueBinder()->attachTo($statement);
        $statement->execute();

        return $statement;
    }

    /**
     * {@inheritDoc}
     */
    public function query($sql)
    {
        $statement = $this->prepare($sql);
        $statement->execute();
        return $statement;
    }

    /**
     * {@inheritDoc}
     */
    public function newQuery()
    {
        return new Query($this);
    }

    /**
     * {@inheritDoc}
     */
    public function schemaCollection(SchemaCollection $collection = null)
    {
        if ($collection !== null) {
            return $this->_schemaCollection = $collection;
        }

        if ($this->_schemaCollection !== null) {
            return $this->_schemaCollection;
        }

        if (!empty($this->_config['cacheMetadata'])) {
            return $this->_schemaCollection = new CachedCollection($this, $this->_config['cacheMetadata']);
        }

        return $this->_schemaCollection = new SchemaCollection($this);
    }

    /**
     * Executes an INSERT query on the specified table.
     *
     * @param string $table The table to insert values in
     * @param array $data Values to be inserted
     * @param array $types List of associative array containing the types to be used for casting
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
     * @param string $table The table to update rows from
     * @param array $data Values to be updated
     * @param array $conditions Conditions to be set for update statement
     * @param array $types List of associative array containing the types to be used for casting
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
     * @param string $table The table to delete rows from
     * @param array $conditions Conditions to be set for delete statement
     * @param array $types List of associative array containing the types to be used for casting
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
            return;
        }

        $this->_transactionLevel++;
        if ($this->useSavePoints()) {
            $this->createSavePoint($this->_transactionLevel);
        }
    }

    /**
     * Commits current transaction.
     *
     * @return bool `true` on success, `false` otherwise
     */
    public function commit()
    {
        if (!$this->_transactionStarted) {
            return false;
        }

        if ($this->_transactionLevel === 0) {
            $this->_transactionStarted = false;
            if ($this->_logQueries) {
                $this->log('COMMIT');
            }
            return $this->_driver->commitTransaction();
        }
        if ($this->useSavePoints()) {
            $this->releaseSavePoint($this->_transactionLevel);
        }

        $this->_transactionLevel--;
        return true;
    }

    /**
     * Rollback current transaction.
     *
     * @return bool
     */
    public function rollback()
    {
        if (!$this->_transactionStarted) {
            return false;
        }

        $useSavePoint = $this->useSavePoints();
        if ($this->_transactionLevel === 0 || !$useSavePoint) {
            $this->_transactionLevel = 0;
            $this->_transactionStarted = false;
            if ($this->_logQueries) {
                $this->log('ROLLBACK');
            }
            $this->_driver->rollbackTransaction();
            return true;
        }

        if ($useSavePoint) {
            $this->rollbackSavepoint($this->_transactionLevel--);
        }
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * ### Example:
     *
     * `$connection->useSavePoints(true)` Returns true if drivers supports save points, false otherwise
     * `$connection->useSavePoints(false)` Disables usage of savepoints and returns false
     * `$connection->useSavePoints()` Returns current status
     */
    public function useSavePoints($enable = null)
    {
        if ($enable === null) {
            return $this->_useSavePoints;
        }

        if ($enable === false) {
            return $this->_useSavePoints = false;
        }

        return $this->_useSavePoints = $this->_driver->supportsSavePoints();
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
        $this->execute($this->_driver->disableForeignKeySql())->closeCursor();
    }

    /**
     * Run driver specific SQL to enable foreign key checks.
     *
     * @return void
     */
    public function enableForeignKeys()
    {
        $this->execute($this->_driver->enableForeignKeySql())->closeCursor();
    }

    /**
     * Returns whether the driver supports adding or dropping constraints
     * to already created tables.
     *
     * @return bool `true` if driver supports dynamic constraints
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
            $this->rollback();
            throw $e;
        }

        if ($result === false) {
            $this->rollback();
            return false;
        }

        $this->commit();
        return $result;
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
     * {@inheritDoc}
     */
    public function inTransaction()
    {
        return $this->_transactionStarted;
    }

    /**
     * {@inheritDoc}
     */
    public function quote($value, $type = null)
    {
        list($value, $type) = $this->cast($value, $type);
        return $this->_driver->quote($value, $type);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsQuoting()
    {
        return $this->_driver->supportsQuoting();
    }

    /**
     * {@inheritDoc}
     */
    public function quoteIdentifier($identifier)
    {
        return $this->_driver->quoteIdentifier($identifier);
    }

    /**
     * {@inheritDoc}
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
     */
    public function logger($instance = null)
    {
        if ($instance === null) {
            if ($this->_logger === null) {
                $this->_logger = new QueryLogger;
            }
            return $this->_logger;
        }
        $this->_logger = $instance;
    }

    /**
     * Logs a Query string using the configured logger object.
     *
     * @param string $sql String to be logged
     * @return void
     */
    public function log($sql)
    {
        $query = new LoggedQuery;
        $query->query = $sql;
        $this->logger()->log($query);
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
        $log = new LoggingStatement($statement, $this->driver());
        $log->logger($this->logger());
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
