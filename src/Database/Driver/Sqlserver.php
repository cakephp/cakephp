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

use Cake\Database\Dialect\SqlserverDialectTrait;
use Cake\Database\Driver;
use Cake\Database\Query;
use Cake\Database\Statement\SqlserverStatement;
use PDO;

/**
 * SQLServer driver.
 */
class Sqlserver extends Driver
{

    use PDODriverTrait;
    use SqlserverDialectTrait;

    /**
     * Base configuration settings for Sqlserver driver
     *
     * @var array
     */
    protected $_baseConfig = [
        'host' => 'localhost\SQLEXPRESS',
        'username' => '',
        'password' => '',
        'database' => 'cake',
        'port' => '',
        // PDO::SQLSRV_ENCODING_UTF8
        'encoding' => 65001,
        'flags' => [],
        'init' => [],
        'settings' => [],
        'attributes' => [],
        'app' => null,
        'connectionPooling' => null,
        'failoverPartner' => null,
        'loginTimeout' => null,
        'multiSubnetFailover' => null,
    ];

    /**
     * Establishes a connection to the database server.
     *
     * Please note that the PDO::ATTR_PERSISTENT attribute is not supported by
     * the SQL Server PHP PDO drivers.  As a result you cannot use the
     * persistent config option when connecting to a SQL Server  (for more
     * information see: https://github.com/Microsoft/msphpsql/issues/65).
     *
     * @throws \InvalidArgumentException if an unsupported setting is in the driver config
     * @return bool true on success
     */
    public function connect()
    {
        if ($this->_connection) {
            return true;
        }
        $config = $this->_config;

        if (isset($config['persistent']) && $config['persistent']) {
            throw new \InvalidArgumentException('Config setting "persistent" cannot be set to true, as the Sqlserver PDO driver does not support PDO::ATTR_PERSISTENT');
        }

        $config['flags'] += [
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        if (!empty($config['encoding'])) {
            $config['flags'][PDO::SQLSRV_ATTR_ENCODING] = $config['encoding'];
        }
        $port = '';
        if (strlen($config['port'])) {
            $port = ',' . $config['port'];
        }

        $dsn = "sqlsrv:Server={$config['host']}{$port};Database={$config['database']};MultipleActiveResultSets=false";
        if ($config['app'] !== null) {
            $dsn .= ";APP={$config['app']}";
        }
        if ($config['connectionPooling'] !== null) {
            $dsn .= ";ConnectionPooling={$config['connectionPooling']}";
        }
        if ($config['failoverPartner'] !== null) {
            $dsn .= ";Failover_Partner={$config['failoverPartner']}";
        }
        if ($config['loginTimeout'] !== null) {
            $dsn .= ";LoginTimeout={$config['loginTimeout']}";
        }
        if ($config['multiSubnetFailover'] !== null) {
            $dsn .= ";MultiSubnetFailover={$config['multiSubnetFailover']}";
        }
        $this->_connect($dsn, $config);

        $connection = $this->connection();
        if (!empty($config['init'])) {
            foreach ((array)$config['init'] as $command) {
                $connection->exec($command);
            }
        }
        if (!empty($config['settings']) && is_array($config['settings'])) {
            foreach ($config['settings'] as $key => $value) {
                $connection->exec("SET {$key} {$value}");
            }
        }
        if (!empty($config['attributes']) && is_array($config['attributes'])) {
            foreach ($config['attributes'] as $key => $value) {
                $connection->setAttribute($key, $value);
            }
        }

        return true;
    }

    /**
     * Returns whether PHP is able to use this driver for connecting to database
     *
     * @return bool true if it is valid to use this driver
     */
    public function enabled()
    {
        return in_array('sqlsrv', PDO::getAvailableDrivers());
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
        $options = [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL];
        $isObject = $query instanceof Query;
        if ($isObject && $query->isBufferedResultsEnabled() === false) {
            $options = [];
        }
        $statement = $this->_connection->prepare($isObject ? $query->sql() : $query, $options);

        return new SqlserverStatement($statement, $this);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDynamicConstraints()
    {
        return true;
    }
}
