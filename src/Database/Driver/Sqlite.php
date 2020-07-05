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
use Cake\Database\Expression\TupleComparison;
use Cake\Database\Query;
use Cake\Database\QueryCompiler;
use Cake\Database\Schema\SchemaDialect;
use Cake\Database\Schema\SqliteSchemaDialect;
use Cake\Database\SqliteCompiler;
use Cake\Database\Statement\PDOStatement;
use Cake\Database\Statement\SqliteStatement;
use Cake\Database\StatementInterface;
use InvalidArgumentException;
use PDO;

/**
 * Class Sqlite
 */
class Sqlite extends Driver
{
    use SqlDialectTrait;
    use TupleComparisonTranslatorTrait;

    /**
     * Base configuration settings for Sqlite driver
     *
     * - `mask` The mask used for created database
     *
     * @var array
     */
    protected $_baseConfig = [
        'persistent' => false,
        'username' => null,
        'password' => null,
        'database' => ':memory:',
        'encoding' => 'utf8',
        'mask' => 0644,
        'flags' => [],
        'init' => [],
    ];

    /**
     * The schema dialect class for this driver
     *
     * @var \Cake\Database\Schema\SqliteSchemaDialect
     */
    protected $_schemaDialect;

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
    protected $_startQuote = '"';

    /**
     * String used to end a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $_endQuote = '"';

    /**
     * Mapping of date parts.
     *
     * @var array
     */
    protected $_dateParts = [
        'day' => 'd',
        'hour' => 'H',
        'month' => 'm',
        'minute' => 'M',
        'second' => 'S',
        'week' => 'W',
        'year' => 'Y',
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
        if (!is_string($config['database']) || !strlen($config['database'])) {
            $name = $config['name'] ?? 'unknown';
            throw new InvalidArgumentException(
                "The `database` key for the `{$name}` SQLite connection needs to be a non-empty string."
            );
        }

        $databaseExists = file_exists($config['database']);

        $dsn = "sqlite:{$config['database']}";
        $this->_connect($dsn, $config);

        if (!$databaseExists && $config['database'] !== ':memory:') {
            // phpcs:disable
            @chmod($config['database'], $config['mask']);
            // phpcs:enable
        }

        if (!empty($config['init'])) {
            foreach ((array)$config['init'] as $command) {
                $this->getConnection()->exec($command);
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
        return in_array('sqlite', PDO::getAvailableDrivers(), true);
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
        $result = new SqliteStatement(new PDOStatement($statement, $this), $this);
        /** @psalm-suppress PossiblyInvalidMethodCall */
        if ($isObject && $query->isBufferedResultsEnabled() === false) {
            $result->bufferResults(false);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function disableForeignKeySQL(): string
    {
        return 'PRAGMA foreign_keys = OFF';
    }

    /**
     * @inheritDoc
     */
    public function enableForeignKeySQL(): string
    {
        return 'PRAGMA foreign_keys = ON';
    }

    /**
     * @inheritDoc
     */
    public function supportsDynamicConstraints(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function schemaDialect(): SchemaDialect
    {
        if ($this->_schemaDialect === null) {
            $this->_schemaDialect = new SqliteSchemaDialect($this);
        }

        return $this->_schemaDialect;
    }

    /**
     * @inheritDoc
     */
    public function newCompiler(): QueryCompiler
    {
        return new SqliteCompiler();
    }

    /**
     * @inheritDoc
     */
    protected function _expressionTranslators(): array
    {
        return [
            FunctionExpression::class => '_transformFunctionExpression',
            TupleComparison::class => '_transformTupleComparison',
        ];
    }

    /**
     * @inheritDoc
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
                    ->setName('ROUND')
                    ->setConjunction('-')
                    ->iterateParts(function ($p) {
                        return new FunctionExpression('JULIANDAY', [$p['value']], [$p['type']]);
                    });
                break;
            case 'NOW':
                $expression->setName('DATETIME')->add(["'now'" => 'literal']);
                break;
            case 'RAND':
                $expression
                    ->setName('ABS')
                    ->add(['RANDOM() % 1' => 'literal'], [], true);
                break;
            case 'CURRENT_DATE':
                $expression->setName('DATE')->add(["'now'" => 'literal']);
                break;
            case 'CURRENT_TIME':
                $expression->setName('TIME')->add(["'now'" => 'literal']);
                break;
            case 'EXTRACT':
                $expression
                    ->setName('STRFTIME')
                    ->setConjunction(' ,')
                    ->iterateParts(function ($p, $key) {
                        if ($key === 0) {
                            $value = rtrim(strtolower($p), 's');
                            if (isset($this->_dateParts[$value])) {
                                $p = ['value' => '%' . $this->_dateParts[$value], 'type' => null];
                            }
                        }

                        return $p;
                    });
                break;
            case 'DATE_ADD':
                $expression
                    ->setName('DATE')
                    ->setConjunction(',')
                    ->iterateParts(function ($p, $key) {
                        if ($key === 1) {
                            $p = ['value' => $p, 'type' => null];
                        }

                        return $p;
                    });
                break;
            case 'DAYOFWEEK':
                $expression
                    ->setName('STRFTIME')
                    ->setConjunction(' ')
                    ->add(["'%w', " => 'literal'], [], true)
                    ->add([') + (1' => 'literal']); // Sqlite starts on index 0 but Sunday should be 1
                break;
        }
    }

    /**
     * Returns true if the server supports common table expressions.
     *
     * @return bool
     */
    public function supportsCTEs(): bool
    {
        if ($this->supportsCTEs === null) {
            $this->supportsCTEs = version_compare($this->version(), '3.8.3', '>=');
        }

        return $this->supportsCTEs;
    }

    /**
     * Returns true if the connected server supports window functions.
     *
     * @return bool
     */
    public function supportsWindowFunctions(): bool
    {
        if ($this->_supportsWindowFunctions === null) {
            $this->_supportsWindowFunctions = version_compare($this->version(), '3.25.0', '>=');
        }

        return $this->_supportsWindowFunctions;
    }
}
