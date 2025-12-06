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
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\StringExpression;
use Cake\Database\PostgresCompiler;
use Cake\Database\Query\InsertQuery;
use Cake\Database\Query\SelectQuery;
use Cake\Database\QueryCompiler;
use Cake\Database\Schema\PostgresSchemaDialect;
use Cake\Database\Schema\SchemaDialect;
use PDO;

/**
 * Class Postgres
 */
class Postgres extends Driver
{
    /**
     * @inheritDoc
     */
    protected const MAX_ALIAS_LENGTH = 63;

    /**
     * Base configuration settings for Postgres driver
     *
     * @var array<string, mixed>
     */
    protected array $_baseConfig = [
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
        'ssl_key' => null,
        'ssl_cert' => null,
        'ssl_ca' => null,
        'ssl' => false,
        'ssl_mode' => null,
    ];

    /**
     * String used to start a database identifier quoting to make it safe
     *
     * @var string
     */
    protected string $_startQuote = '"';

    /**
     * String used to end a database identifier quoting to make it safe
     *
     * @var string
     */
    protected string $_endQuote = '"';

    /**
     * @inheritDoc
     */
    public function connect(): void
    {
        if ($this->pdo !== null) {
            return;
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

        if ($this->_config['ssl']) {
            if ($this->_config['ssl_mode']) {
                $dsn .= ';sslmode=' . $this->_config['ssl_mode'];
            } else {
                $dsn .= ';sslmode=allow';
            }

            if ($this->_config['ssl_key']) {
                $dsn .= ';sslkey=' . $this->_config['ssl_key'];
            }
            if ($this->_config['ssl_cert']) {
                $dsn .= ';sslcert=' . $this->_config['ssl_cert'];
            }
            if ($this->_config['ssl_ca']) {
                $dsn .= ';sslrootcert=' . $this->_config['ssl_ca'];
            }
        }

        $this->pdo = $this->createPdo($dsn, $config);
        if (!empty($config['encoding'])) {
            $this->setEncoding($config['encoding']);
        }

        if (!empty($config['schema'])) {
            $this->setSchema($config['schema']);
        }

        if (!empty($config['timezone'])) {
            $config['init'][] = sprintf('SET timezone = %s', $this->getPdo()->quote($config['timezone']));
        }

        foreach ($config['init'] as $command) {
            /** @phpstan-ignore-next-line */
            $this->pdo->exec($command);
        }
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
     * @inheritDoc
     */
    public function schemaDialect(): SchemaDialect
    {
        return $this->_schemaDialect ?? ($this->_schemaDialect = new PostgresSchemaDialect($this));
    }

    /**
     * Sets connection encoding
     *
     * @param string $encoding The encoding to use.
     * @return void
     */
    public function setEncoding(string $encoding): void
    {
        $pdo = $this->getPdo();
        $pdo->exec('SET NAMES ' . $pdo->quote($encoding));
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
        $pdo = $this->getPdo();
        $pdo->exec('SET search_path TO ' . $pdo->quote($schema));
    }

    /**
     * Get the SQL for disabling foreign keys.
     *
     * @return string
     */
    public function disableForeignKeySQL(): string
    {
        return 'SET CONSTRAINTS ALL DEFERRED';
    }

    /**
     * @inheritDoc
     */
    public function enableForeignKeySQL(): string
    {
        return 'SET CONSTRAINTS ALL IMMEDIATE';
    }

    /**
     * @inheritDoc
     */
    public function supports(DriverFeatureEnum $feature): bool
    {
        return match ($feature) {
            DriverFeatureEnum::CTE,
            DriverFeatureEnum::JSON,
            DriverFeatureEnum::SAVEPOINT,
            DriverFeatureEnum::TRUNCATE_WITH_CONSTRAINTS,
            DriverFeatureEnum::WINDOW => true,
            DriverFeatureEnum::INTERSECT => true,
            DriverFeatureEnum::INTERSECT_ALL => true,
            DriverFeatureEnum::SET_OPERATIONS_ORDER_BY => true,
            DriverFeatureEnum::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION => false,
            DriverFeatureEnum::OPTIMIZER_HINT_COMMENT => true,
            DriverFeatureEnum::CHECK_CONSTRAINTS => true,
        };
    }

    /**
     * @inheritDoc
     */
    protected function _transformDistinct(SelectQuery $query): SelectQuery
    {
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function _insertQueryTranslator(InsertQuery $query): InsertQuery
    {
        if (!$query->clause('epilog')) {
            $query->epilog('RETURNING *');
        }

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function _expressionTranslators(): array
    {
        return [
            IdentifierExpression::class => '_transformIdentifierExpression',
            FunctionExpression::class => '_transformFunctionExpression',
            StringExpression::class => '_transformStringExpression',
        ];
    }

    /**
     * Changes identifer expression into postgresql format.
     *
     * @param \Cake\Database\Expression\IdentifierExpression $expression The expression to transform.
     * @return void
     */
    protected function _transformIdentifierExpression(IdentifierExpression $expression): void
    {
        $collation = $expression->getCollation();
        if ($collation) {
            // use trim() to work around expression being transformed multiple times
            $expression->setCollation('"' . trim($collation, '"') . '"');
        }
    }

    /**
     * Receives a FunctionExpression and changes it so that it conforms to this
     * SQL dialect.
     *
     * @param \Cake\Database\Expression\FunctionExpression $expression The function expression to convert
     *   to postgres SQL.
     * @return void
     */
    protected function _transformFunctionExpression(FunctionExpression $expression): void
    {
        switch ($expression->getName()) {
            case 'CONCAT':
                // CONCAT function is expressed as exp1 || exp2
                $expression->setName('')->setConjunction(' ||');
                break;
            case 'DATEDIFF':
                $expression
                    ->setName('')
                    ->setConjunction('-')
                    ->iterateParts(function ($p) {
                        if (is_string($p)) {
                            $p = ['value' => [$p => 'literal'], 'type' => null];
                        } else {
                            $p['value'] = [$p['value']];
                        }

                        return new FunctionExpression('DATE', $p['value'], [$p['type']]);
                    });
                break;
            case 'CURRENT_DATE':
                $time = new FunctionExpression('LOCALTIMESTAMP', [' 0 ' => 'literal']);
                $expression->setName('CAST')->setConjunction(' AS ')->add([$time, 'date' => 'literal']);
                break;
            case 'CURRENT_TIME':
                $time = new FunctionExpression('LOCALTIMESTAMP', [' 0 ' => 'literal']);
                $expression->setName('CAST')->setConjunction(' AS ')->add([$time, 'time' => 'literal']);
                break;
            case 'NOW':
                $expression->setName('LOCALTIMESTAMP')->add([' 0 ' => 'literal']);
                break;
            case 'RAND':
                $expression->setName('RANDOM');
                break;
            case 'DATE_ADD':
                $expression
                    ->setName('')
                    ->setConjunction(' + INTERVAL')
                    ->iterateParts(function ($p, $key) {
                        if ($key === 1) {
                            return sprintf("'%s'", $p);
                        }

                        return $p;
                    });
                break;
            case 'DAYOFWEEK':
                $expression
                    ->setName('EXTRACT')
                    ->setConjunction(' ')
                    ->add(['DOW FROM' => 'literal'], [], true)
                    ->add([') + (1' => 'literal']); // Postgres starts on index 0 but Sunday should be 1
                break;
            case 'JSON_VALUE':
                $expression->setName('JSONB_PATH_QUERY')
                    ->iterateParts(function ($p, $key) {
                        if ($key === 0) {
                            $p = sprintf('%s::jsonb', $p);
                        } elseif ($key === 1) {
                            $p = sprintf("'%s'::jsonpath", $this->quoteIdentifier($p['value']));
                        }

                        return $p;
                    });
                break;
        }
    }

    /**
     * Changes string expression into postgresql format.
     *
     * @param \Cake\Database\Expression\StringExpression $expression The string expression to transform.
     * @return void
     */
    protected function _transformStringExpression(StringExpression $expression): void
    {
        // use trim() to work around expression being transformed multiple times
        $expression->setCollation('"' . trim($expression->getCollation(), '"') . '"');
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
