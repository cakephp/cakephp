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

use Cake\Database\Query;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Statement\PDOStatement;
use InvalidArgumentException;
use PDO;
use PDOException;

/**
 * Represents a database driver containing all specificities for
 * a database engine including its SQL dialect.
 */
abstract class Driver implements DriverInterface
{
    /**
     * Instance of PDO.
     *
     * @var \PDO|null
     */
    protected $_connection;

    /**
     * Configuration data.
     *
     * @var array
     */
    protected $_config;

    /**
     * Base configuration that is merged into the user
     * supplied configuration data.
     *
     * @var array
     */
    protected $_baseConfig = [];

    /**
     * Indicates whether or not the driver is doing automatic identifier quoting
     * for all queries
     *
     * @var bool
     */
    protected $_autoQuoting = false;

    /**
     * Constructor
     *
     * @param array $config The configuration for the driver.
     * @throws \InvalidArgumentException
     */
    public function __construct($config = [])
    {
        if (empty($config['username']) && !empty($config['login'])) {
            throw new InvalidArgumentException(
                'Please pass "username" instead of "login" for connecting to the database'
            );
        }
        $config += $this->_baseConfig;
        $this->_config = $config;
        if (!empty($config['quoteIdentifiers'])) {
            $this->enableAutoQuoting();
        }
    }

    /**
     * Establishes a connection to the database server
     *
     * @param string $dsn A Driver-specific PDO-DSN
     * @param array $config configuration to be used for creating connection
     * @return bool true on success
     */
    protected function _connect($dsn, array $config)
    {
        $connection = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['flags']
        );
        $this->setConnection($connection);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function connect();

    /**
     * {@inheritDoc}
     */
    public function disconnect()
    {
        $this->_connection = null;
    }

    /**
     * Returns correct connection resource or object that is internally used
     * If first argument is passed, it will set internal connection object or
     * result to the value passed.
     *
     * @param mixed $connection The PDO connection instance.
     * @return mixed Connection object used internally.
     * @deprecated 3.6.0 Use getConnection()/setConnection() instead.
     */
    public function connection($connection = null)
    {
        deprecationWarning(
            get_called_class() . '::connection() is deprecated. ' .
            'Use setConnection()/getConnection() instead.'
        );
        if ($connection !== null) {
            $this->_connection = $connection;
        }

        return $this->_connection;
    }

    /**
     * Get the internal PDO connection instance.
     *
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * Set the internal PDO connection instance.
     *
     * @param \PDO $connection PDO instance.
     * @return $this
     */
    public function setConnection($connection)
    {
        $this->_connection = $connection;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function enabled();

    /**
     * {@inheritDoc}
     */
    public function prepare($query)
    {
        $this->connect();
        $isObject = $query instanceof Query;
        $statement = $this->_connection->prepare($isObject ? $query->sql() : $query);

        return new PDOStatement($statement, $this);
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        $this->connect();
        if ($this->_connection->inTransaction()) {
            return true;
        }

        return $this->_connection->beginTransaction();
    }

    /**
     * {@inheritDoc}
     */
    public function commitTransaction()
    {
        $this->connect();
        if (!$this->_connection->inTransaction()) {
            return false;
        }

        return $this->_connection->commit();
    }

    /**
     * {@inheritDoc}
     */
    public function rollbackTransaction()
    {
        $this->connect();
        if (!$this->_connection->inTransaction()) {
            return false;
        }

        return $this->_connection->rollBack();
    }

    /**
     * {@inheritDoc}
     */
    abstract public function releaseSavePointSQL($name);

    /**
     * {@inheritDoc}
     */
    abstract public function savePointSQL($name);

    /**
     * {@inheritDoc}
     */
    abstract public function rollbackSavePointSQL($name);

    /**
     * {@inheritDoc}
     */
    abstract public function disableForeignKeySQL();

    /**
     * {@inheritDoc}
     */
    abstract public function enableForeignKeySQL();

    /**
     * {@inheritDoc}
     */
    abstract public function supportsDynamicConstraints();

    /**
     * {@inheritDoc}
     */
    public function supportsSavePoints()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function quote($value, $type)
    {
        $this->connect();

        return $this->_connection->quote($value, $type);
    }

    /**
     * Checks if the driver supports quoting, as PDO_ODBC does not support it.
     *
     * @return bool
     */
    public function supportsQuoting()
    {
        $this->connect();

        return $this->_connection->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'odbc';
    }

    /**
     * {@inheritDoc}
     */
    abstract public function queryTranslator($type);

    /**
     * {@inheritDoc}
     */
    abstract public function schemaDialect();

    /**
     * {@inheritDoc}
     */
    abstract public function quoteIdentifier($identifier);

    /**
     * {@inheritDoc}
     */
    public function schemaValue($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        if ($value === false) {
            return 'FALSE';
        }
        if ($value === true) {
            return 'TRUE';
        }
        if (is_float($value)) {
            return str_replace(',', '.', (string)$value);
        }
        if (
            (is_int($value) || $value === '0') || (
            is_numeric($value) && strpos($value, ',') === false &&
            $value[0] !== '0' && strpos($value, 'e') === false)
        ) {
            return (string)$value;
        }

        return $this->_connection->quote($value, PDO::PARAM_STR);
    }

    /**
     * {@inheritDoc}
     */
    public function schema()
    {
        return $this->_config['schema'];
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($table = null, $column = null)
    {
        $this->connect();

        if ($this->_connection instanceof PDO) {
            return $this->_connection->lastInsertId($table);
        }

        return $this->_connection->lastInsertId($table, $column);
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected()
    {
        if ($this->_connection === null) {
            $connected = false;
        } else {
            try {
                $connected = $this->_connection->query('SELECT 1');
            } catch (PDOException $e) {
                $connected = false;
            }
        }

        return (bool)$connected;
    }

    /**
     * {@inheritDoc}
     */
    public function enableAutoQuoting($enable = true)
    {
        $this->_autoQuoting = (bool)$enable;

        return $this;
    }

    /**
     * Disable auto quoting of identifiers in queries.
     *
     * @return $this
     */
    public function disableAutoQuoting()
    {
        $this->_autoQuoting = false;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isAutoQuotingEnabled()
    {
        return $this->_autoQuoting;
    }

    /**
     * Returns whether or not this driver should automatically quote identifiers
     * in queries
     *
     * If called with a boolean argument, it will toggle the auto quoting setting
     * to the passed value
     *
     * @deprecated 3.4.0 use enableAutoQuoting()/isAutoQuotingEnabled() instead.
     * @param bool|null $enable Whether to enable auto quoting
     * @return bool
     */
    public function autoQuoting($enable = null)
    {
        deprecationWarning(
            'Driver::autoQuoting() is deprecated. ' .
            'Use Driver::enableAutoQuoting()/isAutoQuotingEnabled() instead.'
        );
        if ($enable !== null) {
            $this->enableAutoQuoting($enable);
        }

        return $this->isAutoQuotingEnabled();
    }

    /**
     * {@inheritDoc}
     */
    public function compileQuery(Query $query, ValueBinder $generator)
    {
        $processor = $this->newCompiler();
        $translator = $this->queryTranslator($query->type());
        $query = $translator($query);

        return [$query, $processor->compile($query, $generator)];
    }

    /**
     * {@inheritDoc}
     */
    public function newCompiler()
    {
        return new QueryCompiler();
    }

    /**
     * Constructs new TableSchema.
     *
     * @param string $table The table name.
     * @param array $columns The list of columns for the schema.
     * @return \Cake\Database\Schema\TableSchemaInterface
     */
    public function newTableSchema($table, array $columns = [])
    {
        $className = TableSchema::class;
        if (isset($this->_config['tableSchema'])) {
            $className = $this->_config['tableSchema'];
        }

        return new $className($table, $columns);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->_connection = null;
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'connected' => $this->_connection !== null,
        ];
    }
}
