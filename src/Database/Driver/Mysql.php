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
use Cake\Database\Expression\WithExpression;
use Cake\Database\Query;
use Cake\Database\Schema\MysqlSchemaDialect;
use Cake\Database\Schema\SchemaDialect;
use Cake\Database\Statement\MysqlStatement;
use Cake\Database\StatementInterface;
use PDO;

/**
 * Class Mysql
 */
class Mysql extends Driver
{
    use SqlDialectTrait;

    /**
     * @var int|null Maximum alias length or null if no limit
     */
    protected const MAX_ALIAS_LENGTH = 256;

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
        'encoding' => 'utf8mb4',
        'timezone' => null,
        'init' => [],
    ];

    /**
     * The schema dialect for this driver
     *
     * @var \Cake\Database\Schema\MysqlSchemaDialect
     */
    protected $_schemaDialect;

    /**
     * The server version
     *
     * @var string
     */
    protected $_version;

    /**
     * Whether or not the server supports native JSON
     *
     * @var bool|null
     */
    protected $_supportsNativeJson;

    /**
     * Whether or not the connected server supports window functions.
     *
     * @var bool|null
     */
    protected $_supportsWindowFunctions;

    /**
     * String used to start a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $_startQuote = '`';

    /**
     * String used to end a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $_endQuote = '`';

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

        $this->_connect($dsn, $config);

        if (!empty($config['init'])) {
            $connection = $this->getConnection();
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
    public function enabled(): bool
    {
        return in_array('mysql', PDO::getAvailableDrivers(), true);
    }

    /**
     * Prepares a sql statement to be executed
     *
     * @param string|\Cake\Database\Query $query The query to prepare.
     * @return \Cake\Database\StatementInterface
     */
    public function prepare($query): StatementInterface
    {
        $this->connect();
        $isObject = $query instanceof Query;
        /**
         * @psalm-suppress PossiblyInvalidMethodCall
         * @psalm-suppress PossiblyInvalidArgument
         */
        $statement = $this->_connection->prepare($isObject ? $query->sql() : $query);
        $result = new MysqlStatement($statement, $this);
        /** @psalm-suppress PossiblyInvalidMethodCall */
        if ($isObject && $query->isBufferedResultsEnabled() === false) {
            $result->bufferResults(false);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function schemaDialect(): SchemaDialect
    {
        if ($this->_schemaDialect === null) {
            $this->_schemaDialect = new MysqlSchemaDialect($this);
        }

        return $this->_schemaDialect;
    }

    /**
     * @inheritDoc
     */
    public function schema(): string
    {
        return $this->_config['database'];
    }

    /**
     * @inheritDoc
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
    public function supportsDynamicConstraints(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function _expressionTranslators(): array
    {
        $namespace = 'Cake\Database\Expression';

        return [
            $namespace . '\WithExpression' => '_transformWithExpression',
        ];
    }

    /**
     * Receives a WithExpression and changes it so that it conforms to this
     * SQL dialect.
     *
     * @param \Cake\Database\Expression\WithExpression $expression The expression to transform.
     * @return void
     */
    protected function _transformWithExpression(WithExpression $expression): void
    {
        foreach ($expression->getExpressions() as $expression) {
            $expression->setModifiers([]);
        }
    }

    /**
     * Returns connected server version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        if ($this->_version === null) {
            $this->connect();
            $this->_version = (string)$this->_connection->getAttribute(PDO::ATTR_SERVER_VERSION);
        }

        return $this->_version;
    }

    /**
     * Returns true if the server supports common table expressions.
     *
     * @return bool
     */
    public function supportsCommonTableExpressions(): bool
    {
        if ($this->supportsCommonTableExpressions === null) {
            $version = $this->getVersion();
            if (strpos($version, 'MariaDB') !== false) {
                preg_match('/(\d+\.\d+.\d+)-MariaDB/i', $version, $matches);
                $version = $matches[1];

                $this->supportsCommonTableExpressions = version_compare($version, '10.2.2', '>=');
            } else {
                $this->supportsCommonTableExpressions = version_compare($version, '8.0.0', '>=');
            }
        }

        return $this->supportsCommonTableExpressions;
    }

    /**
     * Returns true if the server supports native JSON columns
     *
     * @return bool
     */
    public function supportsNativeJson(): bool
    {
        if ($this->_supportsNativeJson === null) {
            $this->_supportsNativeJson = version_compare($this->getVersion(), '5.7.0', '>=');
        }

        return $this->_supportsNativeJson;
    }

    /**
     * Returns true if the connected server supports window functions.
     *
     * @return bool
     */
    public function supportsWindowFunctions(): bool
    {
        if ($this->_supportsWindowFunctions === null) {
            $this->_supportsWindowFunctions = version_compare($this->getVersion(), '8.0.0', '>=');
        }

        return $this->_supportsWindowFunctions;
    }
}
