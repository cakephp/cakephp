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
namespace Cake\Database\Driver;

use Cake\Database\Dialect\PostgresDialectTrait;
use Cake\Database\Driver;
use Cake\Database\PostgresCompiler;
use Cake\Database\QueryCompiler;
use PDO;

/**
 * Class Postgres
 */
class Postgres extends Driver
{
    use PostgresDialectTrait;

    /**
     * @var int|null Maximum alias length or null if no limit
     */
    protected const MAX_ALIAS_LENGTH = 63;

    /**
     * Base configuration settings for Postgres driver
     *
     * @var array
     */
    protected $_baseConfig = [
        'persistent' => true,
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'cake',
        'schema' => 'public',
        'port' => 5432,
        'encoding' => 'utf8',
        'timezone' => null,
        'flags' => [],
        'init' => [],
    ];

    /**
     * Establishes a connection to the database server
     *
     * @return bool true on success
     */
    public function connect(): bool
    {
        if ($this->_connection) {
            return true;
        }
        $config = $this->_config;
        $config['flags'] += [
            PDO::ATTR_PERSISTENT => $config['persistent'],
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        if (empty($config['unix_socket'])) {
            $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        } else {
            $dsn = "pgsql:dbname={$config['database']}";
        }

        $this->_connect($dsn, $config);
        $this->_connection = $connection = $this->getConnection();
        if (!empty($config['encoding'])) {
            $this->setEncoding($config['encoding']);
        }

        if (!empty($config['schema'])) {
            $this->setSchema($config['schema']);
        }

        if (!empty($config['timezone'])) {
            $config['init'][] = sprintf('SET timezone = %s', $connection->quote($config['timezone']));
        }

        foreach ($config['init'] as $command) {
            $connection->exec($command);
        }

        return true;
    }

    /**
     * Returns whether php is able to use this driver for connecting to database
     *
     * @return bool true if it is valid to use this driver
     */
    public function enabled(): bool
    {
        return in_array('pgsql', PDO::getAvailableDrivers(), true);
    }

    /**
     * Sets connection encoding
     *
     * @param string $encoding The encoding to use.
     * @return void
     */
    public function setEncoding(string $encoding): void
    {
        $this->connect();
        $this->_connection->exec('SET NAMES ' . $this->_connection->quote($encoding));
    }

    /**
     * Sets connection default schema, if any relation defined in a query is not fully qualified
     * postgres will fallback to looking the relation into defined default schema
     *
     * @param string $schema The schema names to set `search_path` to.
     * @return void
     */
    public function setSchema(string $schema): void
    {
        $this->connect();
        $this->_connection->exec('SET search_path TO ' . $this->_connection->quote($schema));
    }

    /**
     * @inheritDoc
     */
    public function supportsDynamicConstraints(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @return \Cake\Database\PostgresCompiler
     */
    public function newCompiler(): QueryCompiler
    {
        return new PostgresCompiler();
    }
}
