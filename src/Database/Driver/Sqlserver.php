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
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\OrderClauseExpression;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\Expression\UnaryExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Cake\Database\Query\SelectQuery;
use Cake\Database\QueryCompiler;
use Cake\Database\Schema\SchemaDialect;
use Cake\Database\Schema\SqlserverSchemaDialect;
use Cake\Database\SqlserverCompiler;
use Cake\Database\Statement\SqlserverStatement;
use Cake\Database\StatementInterface;
use InvalidArgumentException;
use PDO;

/**
 * SQLServer driver.
 */
class Sqlserver extends Driver
{
    use TupleComparisonTranslatorTrait;

    /**
     * @inheritDoc
     */
    protected const MAX_ALIAS_LENGTH = 128;

    /**
     * @inheritDoc
     */
    protected const RETRY_ERROR_CODES = [
        40613, // Azure Sql Database paused
    ];

    /**
     * @inheritDoc
     */
    protected const STATEMENT_CLASS = SqlserverStatement::class;

    /**
     * Base configuration settings for Sqlserver driver
     *
     * @var array<string, mixed>
     */
    protected array $_baseConfig = [
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
        'encrypt' => null,
        'trustServerCertificate' => null,
    ];

    /**
     * String used to start a database identifier quoting to make it safe
     *
     * @var string
     */
    protected string $_startQuote = '[';

    /**
     * String used to end a database identifier quoting to make it safe
     *
     * @var string
     */
    protected string $_endQuote = ']';

    /**
     * Establishes a connection to the database server.
     *
     * Please note that the PDO::ATTR_PERSISTENT attribute is not supported by
     * the SQL Server PHP PDO drivers.  As a result you cannot use the
     * persistent config option when connecting to a SQL Server  (for more
     * information see: https://github.com/Microsoft/msphpsql/issues/65).
     *
     * @throws \InvalidArgumentException if an unsupported setting is in the driver config
     * @return void
     */
    public function connect(): void
    {
        if (isset($this->pdo)) {
            return;
        }
        $config = $this->_config;

        if (isset($config['persistent']) && $config['persistent']) {
            throw new InvalidArgumentException(
                'Config setting "persistent" cannot be set to true, '
                . 'as the Sqlserver PDO driver does not support PDO::ATTR_PERSISTENT'
            );
        }

        $config['flags'] += [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        if (!empty($config['encoding'])) {
            $config['flags'][PDO::SQLSRV_ATTR_ENCODING] = $config['encoding'];
        }
        $port = '';
        if ($config['port']) {
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
        if ($config['encrypt'] !== null) {
            $dsn .= ";Encrypt={$config['encrypt']}";
        }
        if ($config['trustServerCertificate'] !== null) {
            $dsn .= ";TrustServerCertificate={$config['trustServerCertificate']}";
        }

        $this->pdo = $this->createPdo($dsn, $config);
        if (!empty($config['init'])) {
            foreach ((array)$config['init'] as $command) {
                $this->pdo->exec($command);
            }
        }
        if (!empty($config['settings']) && is_array($config['settings'])) {
            foreach ($config['settings'] as $key => $value) {
                $this->pdo->exec("SET {$key} {$value}");
            }
        }
        if (!empty($config['attributes']) && is_array($config['attributes'])) {
            foreach ($config['attributes'] as $key => $value) {
                $this->pdo->setAttribute($key, $value);
            }
        }
    }

    /**
     * Returns whether PHP is able to use this driver for connecting to database
     *
     * @return bool true if it is valid to use this driver
     */
    public function enabled(): bool
    {
        return in_array('sqlsrv', PDO::getAvailableDrivers(), true);
    }

    /**
     * @inheritDoc
     */
    public function prepare(Query|string $query): StatementInterface
    {
        $sql = $query;
        if ($query instanceof Query) {
            $sql = $query->sql();
            if (count($query->getValueBinder()->bindings()) > 2100) {
                throw new InvalidArgumentException(
                    'Exceeded maximum number of parameters (2100) for prepared statements in Sql Server. ' .
                    'This is probably due to a very large WHERE IN () clause which generates a parameter ' .
                    'for each value in the array. ' .
                    'If using an Association, try changing the `strategy` from select to subquery.'
                );
            }
        }

        /** @var string $sql */
        $statement = $this->getPdo()->prepare(
            $sql,
            [
                PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL,
                PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED,
            ]
        );

        $typeMap = null;
        if ($query instanceof SelectQuery && $query->isResultsCastingEnabled()) {
            $typeMap = $query->getSelectTypeMap();
        }

        /** @var \Cake\Database\StatementInterface */
        return new (static::STATEMENT_CLASS)($statement, $this, $typeMap);
    }

    /**
     * @inheritDoc
     */
    public function savePointSQL($name): string
    {
        return 'SAVE TRANSACTION t' . $name;
    }

    /**
     * @inheritDoc
     */
    public function releaseSavePointSQL($name): string
    {
        // SQLServer has no release save point operation.
        return '';
    }

    /**
     * @inheritDoc
     */
    public function rollbackSavePointSQL($name): string
    {
        return 'ROLLBACK TRANSACTION t' . $name;
    }

    /**
     * @inheritDoc
     */
    public function disableForeignKeySQL(): string
    {
        return 'EXEC sp_MSforeachtable "ALTER TABLE ? NOCHECK CONSTRAINT all"';
    }

    /**
     * @inheritDoc
     */
    public function enableForeignKeySQL(): string
    {
        return 'EXEC sp_MSforeachtable "ALTER TABLE ? WITH CHECK CHECK CONSTRAINT all"';
    }

    /**
     * @inheritDoc
     */
    public function supports(DriverFeatureEnum $feature): bool
    {
        return match ($feature) {
            DriverFeatureEnum::CTE,
            DriverFeatureEnum::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION,
            DriverFeatureEnum::SAVEPOINT,
            DriverFeatureEnum::TRUNCATE_WITH_CONSTRAINTS,
            DriverFeatureEnum::WINDOW => true,

            DriverFeatureEnum::JSON => false,
        };
    }

    /**
     * @inheritDoc
     */
    public function schemaDialect(): SchemaDialect
    {
        return $this->_schemaDialect ??= new SqlserverSchemaDialect($this);
    }

    /**
     * {@inheritDoc}
     *
     * @return \Cake\Database\SqlserverCompiler
     */
    public function newCompiler(): QueryCompiler
    {
        return new SqlserverCompiler();
    }

    /**
     * @inheritDoc
     */
    protected function _selectQueryTranslator(SelectQuery $query): SelectQuery
    {
        $limit = $query->clause('limit');
        $offset = $query->clause('offset');

        if ($limit && $offset === null) {
            $query->modifier(['_auto_top_' => sprintf('TOP %d', $limit)]);
        }

        if ($offset !== null && !$query->clause('order')) {
            $query->orderBy($query->newExpr()->add('(SELECT NULL)'));
        }

        if ($this->version() < 11 && $offset !== null) {
            return $this->_pagingSubquery($query, $limit, $offset);
        }

        return $this->_transformDistinct($query);
    }

    /**
     * Generate a paging subquery for older versions of SQLserver.
     *
     * Prior to SQLServer 2012 there was no equivalent to LIMIT OFFSET, so a subquery must
     * be used.
     *
     * @param \Cake\Database\Query\SelectQuery<mixed> $original The query to wrap in a subquery.
     * @param int|null $limit The number of rows to fetch.
     * @param int|null $offset The number of rows to offset.
     * @return \Cake\Database\Query\SelectQuery<mixed> Modified query object.
     */
    protected function _pagingSubquery(SelectQuery $original, ?int $limit, ?int $offset): SelectQuery
    {
        $field = '_cake_paging_._cake_page_rownum_';

        /** @var \Cake\Database\Expression\OrderByExpression $originalOrder */
        $originalOrder = $original->clause('order');
        if ($originalOrder) {
            // SQL server does not support column aliases in OVER clauses.  But
            // the only practical way to specify the use of calculated columns
            // is with their alias.  So substitute the select SQL in place of
            // any column aliases for those entries in the order clause.
            $select = $original->clause('select');
            $order = new OrderByExpression();
            $originalOrder
                ->iterateParts(function ($direction, $orderBy) use ($select, $order) {
                    $key = $orderBy;
                    if (
                        isset($select[$orderBy]) &&
                        $select[$orderBy] instanceof ExpressionInterface
                    ) {
                        $order->add(new OrderClauseExpression($select[$orderBy], $direction));
                    } else {
                        $order->add([$key => $direction]);
                    }

                    // Leave original order clause unchanged.
                    return $orderBy;
                });
        } else {
            $order = new OrderByExpression('(SELECT NULL)');
        }

        $query = clone $original;
        $query->select([
                '_cake_page_rownum_' => new UnaryExpression('ROW_NUMBER() OVER', $order),
            ])->limit(null)
            ->offset(null)
            ->orderBy([], true);

        $outer = $query->getConnection()->selectQuery();
        $outer->select('*')
            ->from(['_cake_paging_' => $query]);

        if ($offset) {
            $outer->where(["$field > " . $offset]);
        }
        if ($limit) {
            $value = (int)$offset + $limit;
            $outer->where(["$field <= $value"]);
        }

        // Decorate the original query as that is what the
        // end developer will be calling execute() on originally.
        $original->decorateResults(function ($row) {
            if (isset($row['_cake_page_rownum_'])) {
                unset($row['_cake_page_rownum_']);
            }

            return $row;
        });

        return $outer;
    }

    /**
     * @inheritDoc
     */
    protected function _transformDistinct(SelectQuery $query): SelectQuery
    {
        if (!is_array($query->clause('distinct'))) {
            return $query;
        }

        $original = $query;
        $query = clone $original;

        $distinct = $query->clause('distinct');
        $query->distinct(false);

        $order = new OrderByExpression($distinct);
        $query
            ->select(function (Query $q) use ($distinct, $order) {
                $over = $q->newExpr('ROW_NUMBER() OVER')
                    ->add('(PARTITION BY')
                    ->add($q->newExpr()->add($distinct)->setConjunction(','))
                    ->add($order)
                    ->add(')')
                    ->setConjunction(' ');

                return [
                    '_cake_distinct_pivot_' => $over,
                ];
            })
            ->limit(null)
            ->offset(null)
            ->orderBy([], true);

        $outer = new SelectQuery($query->getConnection());
        $outer->select('*')
            ->from(['_cake_distinct_' => $query])
            ->where(['_cake_distinct_pivot_' => 1]);

        // Decorate the original query as that is what the
        // end developer will be calling execute() on originally.
        $original->decorateResults(function ($row) {
            if (isset($row['_cake_distinct_pivot_'])) {
                unset($row['_cake_distinct_pivot_']);
            }

            return $row;
        });

        return $outer;
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
     * Receives a FunctionExpression and changes it so that it conforms to this
     * SQL dialect.
     *
     * @param \Cake\Database\Expression\FunctionExpression $expression The function expression to convert to TSQL.
     * @return void
     */
    protected function _transformFunctionExpression(FunctionExpression $expression): void
    {
        switch ($expression->getName()) {
            case 'CONCAT':
                // CONCAT function is expressed as exp1 + exp2
                $expression->setName('')->setConjunction(' +');
                break;
            case 'DATEDIFF':
                $hasDay = false;
                $visitor = function ($value) use (&$hasDay) {
                    if ($value === 'day') {
                        $hasDay = true;
                    }

                    return $value;
                };
                $expression->iterateParts($visitor);

                if (!$hasDay) {
                    $expression->add(['day' => 'literal'], [], true);
                }
                break;
            case 'CURRENT_DATE':
                $time = new FunctionExpression('GETUTCDATE');
                $expression->setName('CONVERT')->add(['date' => 'literal', $time]);
                break;
            case 'CURRENT_TIME':
                $time = new FunctionExpression('GETUTCDATE');
                $expression->setName('CONVERT')->add(['time' => 'literal', $time]);
                break;
            case 'NOW':
                $expression->setName('GETUTCDATE');
                break;
            case 'EXTRACT':
                $expression->setName('DATEPART')->setConjunction(' ,');
                break;
            case 'DATE_ADD':
                $params = [];
                $visitor = function ($p, $key) use (&$params) {
                    if ($key === 0) {
                        $params[2] = $p;
                    } else {
                        $valueUnit = explode(' ', $p);
                        $params[0] = rtrim($valueUnit[1], 's');
                        $params[1] = $valueUnit[0];
                    }

                    return $p;
                };
                $manipulator = function ($p, $key) use (&$params) {
                    return $params[$key];
                };

                $expression
                    ->setName('DATEADD')
                    ->setConjunction(',')
                    ->iterateParts($visitor)
                    ->iterateParts($manipulator)
                    ->add([$params[2] => 'literal']);
                break;
            case 'DAYOFWEEK':
                $expression
                    ->setName('DATEPART')
                    ->setConjunction(' ')
                    ->add(['weekday, ' => 'literal'], [], true);
                break;
            case 'SUBSTR':
                $expression->setName('SUBSTRING');
                if (count($expression) < 4) {
                    $params = [];
                    $expression
                        ->iterateParts(function ($p) use (&$params) {
                            return $params[] = $p;
                        })
                        ->add([new FunctionExpression('LEN', [$params[0]]), ['string']]);
                }

                break;
        }
    }
}
