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
namespace Cake\Database\Driver;

use Cake\Database\Query;
use Cake\Database\Statement\PDOStatement;
use PDO;
use PDOException;

/**
 * PDO driver trait
 *
 * @deprecated 3.6.0 The methods of this trait have been added to `Driver` class.
 */
trait PDODriverTrait
{

    /**
     * Instance of PDO.
     *
     * @var \PDO|null
     */
    protected $_connection;

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
        $this->connection($connection);

        return true;
    }

    /**
     * Returns correct connection resource or object that is internally used
     * If first argument is passed, it will set internal connection object or
     * result to the value passed
     *
     * @param null|\PDO $connection The PDO connection instance.
     * @return \PDO connection object used internally
     */
    public function connection($connection = null)
    {
        if ($connection !== null) {
            $this->_connection = $connection;
        }

        return $this->_connection;
    }

    /**
     * Disconnects from database server
     *
     * @return void
     */
    public function disconnect()
    {
        $this->_connection = null;
    }

    /**
     * Checks whether or not the driver is connected.
     *
     * @return bool
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
     * Prepares a sql statement to be executed
     *
     * @param string|\Cake\Database\Query $query The query to turn into a prepared statement.
     * @return \Cake\Database\StatementInterface
     */
    public function prepare($query)
    {
        $this->connect();
        $isObject = $query instanceof Query;
        $statement = $this->_connection->prepare($isObject ? $query->sql() : $query);

        return new PDOStatement($statement, $this);
    }

    /**
     * Starts a transaction
     *
     * @return bool true on success, false otherwise
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
     * Commits a transaction
     *
     * @return bool true on success, false otherwise
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
     * Rollback a transaction
     *
     * @return bool true on success, false otherwise
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
     * Returns a value in a safe representation to be used in a query string
     *
     * @param mixed $value The value to quote.
     * @param string $type Type to be used for determining kind of quoting to perform
     * @return string
     */
    public function quote($value, $type)
    {
        $this->connect();

        return $this->_connection->quote($value, $type);
    }

    /**
     * Returns last id generated for a table or sequence in database
     *
     * @param string|null $table table name or sequence to get last insert value from
     * @param string|null $column the name of the column representing the primary key
     * @return string|int
     */
    public function lastInsertId($table = null, $column = null)
    {
        $this->connect();

        return $this->_connection->lastInsertId($table);
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
}
