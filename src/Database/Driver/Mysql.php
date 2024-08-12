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

use Cake\Database\Driver;
use Cake\Database\DriverFeatureEnum;
use Cake\Database\Schema\MysqlSchemaDialect;
use Cake\Database\Schema\SchemaDialect;
use PDO;

/**
 * MySQL Driver
 */
class Mysql extends Driver
{
    /**
     * @inheritDoc
     */
    protected const MAX_ALIAS_LENGTH = 256;

    /**
     * Server type MySQL
     *
     * @var string
     */
    protected const SERVER_TYPE_MYSQL = 'mysql';

    /**
     * Server type MariaDB
     *
     * @var string
     */
    protected const SERVER_TYPE_MARIADB = 'mariadb';

    /**
     * Base configuration settings for MySQL driver
     *
     * @var array<string, mixed>
     */
    protected array $_baseConfig = [
        'persistent' => true,
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'cake',
        'port' => '3306',
        'flags' => [],
        'encoding' => 'utf8mb4',
        'timezone' => null,
        'init' => [],
    ];

    /**
     * String used to start a database identifier quoting to make it safe
     *
     * @var string
     */
    protected string $_startQuote = '`';

    /**
     * String used to end a database identifier quoting to make it safe
     *
     * @var string
     */
    protected string $_endQuote = '`';

    /**
     * Server type.
     *
     * If the underlying server is MariaDB, its value will get set to `'mariadb'`
     * after `version()` method is called.
     *
     * @var string
     */
    protected string $serverType = self::SERVER_TYPE_MYSQL;

    /**
     * Mapping of feature to db server version for feature availability checks.
     *
     * @var array<string, array<string, string>>
     */
    protected array $featureVersions = [
        'mysql' => [
            'json' => '5.7.0',
            'cte' => '8.0.0',
            'window' => '8.0.0',
        ],
        'mariadb' => [
            'json' => '10.2.7',
            'cte' => '10.2.1',
            'window' => '10.2.0',
        ],
    ];

    /**
     * @inheritDoc
     */
    public function connect(): void
    {
        if ($this->pdo !== null) {
            return;
        }
        $config = $this->_config;

        if ($config['timezone'] === 'UTC') {
            $config['timezone'] = '+0:00';
        }

        if (!empty($config['timezone'])) {
            $config['init'][] = sprintf("SET time_zone = '%s'", $config['timezone']);
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
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        } else {
            $dsn = "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
        }

        if (!empty($config['encoding'])) {
            $dsn .= ";charset={$config['encoding']}";
        }

        $this->pdo = $this->createPdo($dsn, $config);

        if (!empty($config['init'])) {
            foreach ((array)$config['init'] as $command) {
                $this->pdo->exec($command);
            }
        }
    }

    /**
     * Returns whether php is able to use this driver for connecting to database
     *
     * @return bool true if it is valid to use this driver
     */
    public function enabled(): bool
    {
        return in_array('mysql', PDO::getAvailableDrivers(), true);
    }

    /**
     * @inheritDoc
     */
    public function schemaDialect(): SchemaDialect
    {
        if (isset($this->_schemaDialect)) {
            return $this->_schemaDialect;
        }

        return $this->_schemaDialect = new MysqlSchemaDialect($this);
    }

    /**
     * @inheritDoc
     */
    public function schema(): string
    {
        return $this->_config['database'];
    }

    /**
     * Get the SQL for disabling foreign keys.
     *
     * @return string
     */
    public function disableForeignKeySQL(): string
    {
        return 'SET foreign_key_checks = 0';
    }

    /**
     * @inheritDoc
     */
    public function enableForeignKeySQL(): string
    {
        return 'SET foreign_key_checks = 1';
    }

    /**
     * @inheritDoc
     */
    public function supports(DriverFeatureEnum $feature): bool
    {
        return match ($feature) {
            DriverFeatureEnum::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION,
            DriverFeatureEnum::SAVEPOINT => true,

            DriverFeatureEnum::TRUNCATE_WITH_CONSTRAINTS => false,

            DriverFeatureEnum::CTE,
            DriverFeatureEnum::JSON,
            DriverFeatureEnum::WINDOW => version_compare(
                $this->version(),
                $this->featureVersions[$this->serverType][$feature->value],
                '>='
            ),
        };
    }

    /**
     * Returns true if the connected server is MariaDB.
     *
     * @return bool
     */
    public function isMariadb(): bool
    {
        $this->version();

        return $this->serverType === static::SERVER_TYPE_MARIADB;
    }

    /**
     * Returns connected server version.
     *
     * @return string
     */
    public function version(): string
    {
        if ($this->_version === null) {
            $this->_version = (string)$this->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);

            if (str_contains($this->_version, 'MariaDB')) {
                $this->serverType = static::SERVER_TYPE_MARIADB;
                preg_match('/^(?:5\.5\.5-)?(\d+\.\d+\.\d+.*-MariaDB[^:]*)/', $this->_version, $matches);
                $this->_version = $matches[1];
            }
        }

        return $this->_version;
    }
}
