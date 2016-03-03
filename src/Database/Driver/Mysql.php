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
namespace Cake\Database\Driver;

use Cake\Database\Dialect\MysqlDialectTrait;
use Cake\Database\Driver;
use Cake\Database\Query;
use Cake\Database\Statement\MysqlStatement;
use PDO;

class Mysql extends Driver
{

    use MysqlDialectTrait;
    use PDODriverTrait;

    /**
     * Base configuration settings for MySQL driver
     *
     * @var array
     */
    protected $_baseConfig = [
        'persistent' => true,
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'cake',
        'port' => '3306',
        'flags' => [],
        'encoding' => 'utf8',
        'timezone' => null,
        'init' => [],
    ];

    /**
     * Establishes a connection to the database server
     *
     * @return bool true on success
     */
    public function connect()
    {
        if ($this->_connection) {
            return true;
        }
        $config = $this->_config;

        if ($config['timezone'] === 'UTC') {
            $config['timezone'] = '+0:00';
        }

        if (!empty($config['timezone'])) {
            $config['init'][] = sprintf("SET time_zone = '%s'", $config['timezone']);
        }
        if (!empty($config['encoding'])) {
            $config['init'][] = sprintf("SET NAMES %s", $config['encoding']);
        }

        $config['flags'] += [
            PDO::ATTR_PERSISTENT => $config['persistent'],
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        if (!empty($config['ssl_key']) && !empty($config['ssl_cert'])) {
            $config['flags'][PDO::MYSQL_ATTR_SSL_KEY] = $config['ssl_key'];
            $config['flags'][PDO::MYSQL_ATTR_SSL_CERT] = $config['ssl_cert'];
        }
        if (!empty($config['ssl_ca'])) {
            $config['flags'][PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_ca'];
        }

        if (empty($config['unix_socket'])) {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['encoding']}";
        } else {
            $dsn = "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
        }

        $this->_connect($dsn, $config);

        if (!empty($config['init'])) {
            $connection = $this->connection();
            foreach ((array)$config['init'] as $command) {
                $connection->exec($command);
            }
        }
        return true;
    }

    /**
     * Returns whether php is able to use this driver for connecting to database
     *
     * @return bool true if it is valid to use this driver
     */
    public function enabled()
    {
        return in_array('mysql', PDO::getAvailableDrivers());
    }

    /**
     * Prepares a sql statement to be executed
     *
     * @param string|\Cake\Database\Query $query The query to prepare.
     * @return \Cake\Database\StatementInterface
     */
    public function prepare($query)
    {
        $this->connect();
        $isObject = $query instanceof Query;
        $statement = $this->_connection->prepare($isObject ? $query->sql() : $query);
        $result = new MysqlStatement($statement, $this);
        if ($isObject && $query->bufferResults() === false) {
            $result->bufferResults(false);
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function schema()
    {
        return $this->_config['database'];
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDynamicConstraints()
    {
        return true;
    }
}
