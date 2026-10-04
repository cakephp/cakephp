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
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Driver;

use Cake\Database\Driver;
use Cake\Database\DriverFeatureEnum;
use Cake\Database\Expression\DistinctComparisonExpression;
use Cake\Database\Expression\StringAggExpression;
use Cake\Database\Query;
use Cake\Database\Query\SelectQuery;
use Cake\Database\Schema\MariadbSchemaDialect;
use Cake\Database\Schema\SchemaDialect;
use Cake\Database\StatementInterface;
use PDO;
use Pdo\Mysql as PdoMysql;

/**
 * Mariadb Driver
 *
 * While MariaDb shares the same PDO driver, the SQL dialect,
 * and schema reflection have enough minor differences to justify
 * a separate driver.
 */
class Mariadb extends Driver
{
    /**
     * @inheritDoc
     */
    protected function _expressionTranslators(): array
    {
        return [
            StringAggExpression::class => 'transformStringAggExpression',
            DistinctComparisonExpression::class => 'transformDistinctComparisonExpression',
        ];
    }

    /**
     * Translates IS [NOT] DISTINCT FROM into Mariadb-specific syntax.
     *
     * @param \Cake\Database\Expression\DistinctComparisonExpression $expression The expression to translate.
     * @return void
     */
    protected function transformDistinctComparisonExpression(DistinctComparisonExpression $expression): void
    {
        $operator = strtoupper($expression->getOperator());
        if ($operator === 'IS NOT DISTINCT FROM') {
            $expression->setOperator('<=>');
        } elseif ($operator === 'IS DISTINCT FROM') {
            $expression->setOperator('<=>');
            $expression->setNot(true);
        }
    }

    /**
     * Translates portable string aggregation to MariaDB specific syntax.
     *
     * @param \Cake\Database\Expression\StringAggExpression $expression The expression to translate.
     * @return void
     */
    protected function transformStringAggExpression(StringAggExpression $expression): void
    {
        if ($this->supports(DriverFeatureEnum::STRING_AGG)) {
            $expression
                ->setName('STRING_AGG')
                ->setSyntax(StringAggExpression::SYNTAX_STANDARD);

            return;
        }

        $expression
            ->setName('GROUP_CONCAT')
            ->setSyntax(StringAggExpression::SYNTAX_GROUP_CONCAT);
    }

    /**
     * @inheritDoc
     */
    protected const MAX_ALIAS_LENGTH = 256;

    /**
     * Base configuration settings for Mariadb driver
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
     * Mapping of feature to db server version for feature availability checks.
     *
     * @var array<string, array<string, string>>
     */
    protected array $featureVersions = [
        'json' => '10.2.7',
        'cte' => '10.2.1',
        'window' => '10.2.0',
        'string-agg' => '10.5.0',
        'intersect' => '10.3.0',
        'intersect-all' => '10.5.0',
        'except' => '10.3.0',
        'except-all' => '10.5.0',
        'check-constraints' => '10.2.1',
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
            $this->attrUseBufferedQueryId() => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        if (!empty($config['ssl_key']) && !empty($config['ssl_cert'])) {
            $config['flags'][$this->attrSslKeyId()] = $config['ssl_key'];
            $config['flags'][$this->attrSslCertId()] = $config['ssl_cert'];
        }
        if (!empty($config['ssl_ca'])) {
            $config['flags'][$this->attrSslCaId()] = $config['ssl_ca'];
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
     * @inheritDoc
     */
    public function run(Query $query): StatementInterface
    {
        $statement = $this->prepare($query);
        $query->getValueBinder()->attachTo($statement);

        if ($query instanceof SelectQuery) {
            try {
                $this->getPdo()->setAttribute($this->attrUseBufferedQueryId(), $query->isBufferedResultsEnabled());
                $this->executeStatement($statement);
            } finally {
                $this->getPdo()->setAttribute($this->attrUseBufferedQueryId(), true);
            }
        } else {
            $this->executeStatement($statement);
        }

        return $statement;
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
        return $this->_schemaDialect ?? ($this->_schemaDialect = new MariadbSchemaDialect($this));
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
        $versionCompare = function () use ($feature) {
            return version_compare(
                $this->version(),
                $this->featureVersions[$feature->value],
                '>=',
            );
        };

        return match ($feature) {
            DriverFeatureEnum::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION,
            DriverFeatureEnum::SAVEPOINT => true,

            DriverFeatureEnum::TRUNCATE_WITH_CONSTRAINTS => false,

            DriverFeatureEnum::CTE,
            DriverFeatureEnum::JSON,
            DriverFeatureEnum::WINDOW => $versionCompare(),
            DriverFeatureEnum::STRING_AGG => $versionCompare(),
            DriverFeatureEnum::GROUP_CONCAT => true,
            DriverFeatureEnum::INTERSECT => $versionCompare(),
            DriverFeatureEnum::INTERSECT_ALL => $versionCompare(),
            DriverFeatureEnum::EXCEPT => $versionCompare(),
            DriverFeatureEnum::EXCEPT_ALL => $versionCompare(),
            DriverFeatureEnum::CHECK_CONSTRAINTS => $versionCompare(),
            DriverFeatureEnum::SET_OPERATIONS_ORDER_BY => true,
            DriverFeatureEnum::OPTIMIZER_HINT_COMMENT => true,
            DriverFeatureEnum::CASE_SENSITIVE_QUOTED_IDENTIFIERS => false,
            DriverFeatureEnum::SUBQUERY_FILTER_DISTINCT => true,
        };
    }

    /**
     * Returns connected server version.
     *
     * @return string
     */
    public function version(): string
    {
        if ($this->_version === null) {
            $version = (string)$this->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);

            if (preg_match('/^(?:5\.5\.5-)?(\d+\.\d+\.\d+.*-MariaDB[^:]*)/', $version, $matches)) {
                $version = $matches[1];
            }
            $this->_version = $version;
        }

        return $this->_version;
    }

    /**
     * Get PDO ATTR_SSL_KEY id.
     *
     * @return int
     */
    private function attrSslKeyId(): int
    {
        return PHP_VERSION_ID < 80400 ? PDO::MYSQL_ATTR_SSL_KEY : PdoMysql::ATTR_SSL_KEY;
    }

    /**
     * Get PDO ATTR_SSL_CERT id.
     *
     * @return int
     */
    private function attrSslCertId(): int
    {
        return PHP_VERSION_ID < 80400 ? PDO::MYSQL_ATTR_SSL_CERT : PdoMysql::ATTR_SSL_CERT;
    }

    /**
     * Get PDO ATTR_SSL_CA id.
     *
     * @return int
     */
    private function attrSslCaId(): int
    {
        return PHP_VERSION_ID < 80400 ? PDO::MYSQL_ATTR_SSL_CA : PdoMysql::ATTR_SSL_CA;
    }

    /**
     * Get PDO ATTR_USE_BUFFERED_QUERY id.
     *
     * @return int
     */
    private function attrUseBufferedQueryId(): int
    {
        return PHP_VERSION_ID < 80400 ? PDO::MYSQL_ATTR_USE_BUFFERED_QUERY : PdoMysql::ATTR_USE_BUFFERED_QUERY;
    }
}
