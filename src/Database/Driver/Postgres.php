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
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\StringExpression;
use Cake\Database\PostgresCompiler;
use Cake\Database\Query;
use Cake\Database\QueryCompiler;
use Cake\Database\Schema\PostgresSchemaDialect;
use Cake\Database\Schema\SchemaDialect;
use PDO;

/**
 * Class Postgres
 */
class Postgres extends Driver
{
    use SqlDialectTrait;

    /**
     * @inheritDoc
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
     * The schema dialect class for this driver
     *
     * @var \Cake\Database\Schema\PostgresSchemaDialect|null
     */
    protected $_schemaDialect;

    /**
     * String used to start a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $_startQuote = '"';

    /**
     * String used to end a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $_endQuote = '"';

    /**
     * @inheritDoc
     */
    protected $supportsCTEs = true;

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
     * @inheritDoc
     */
    public function schemaDialect(): SchemaDialect
    {
        if ($this->_schemaDialect === null) {
            $this->_schemaDialect = new PostgresSchemaDialect($this);
        }

        return $this->_schemaDialect;
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
    public function supportsDynamicConstraints(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function _transformDistinct(Query $query): Query
    {
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function _insertQueryTranslator(Query $query): Query
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
     * @param \Cake\Database\Expression\IdentifierExpression $expression The expression to tranform.
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
                            $p = sprintf("'%s'", $p);
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
        }
    }

    /**
     * Changes string expression into postgresql format.
     *
     * @param \Cake\Database\Expression\StringExpression $expression The string expression to tranform.
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
