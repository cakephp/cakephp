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
namespace Cake\Database\Dialect;

use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\UnaryExpression;
use Cake\Database\Query;
use Cake\Database\Schema\SqlserverSchema;
use Cake\Database\SqlDialectTrait;
use Cake\Database\SqlserverCompiler;
use PDO;

/**
 * Contains functions that encapsulates the SQL dialect used by SQLServer,
 * including query translators and schema introspection.
 *
 * @internal
 */
trait SqlserverDialectTrait
{

    use SqlDialectTrait;
    use TupleComparisonTranslatorTrait;

    /**
     * String used to start a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $_startQuote = '[';

    /**
     * String used to end a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $_endQuote = ']';

    /**
     * Modify the limit/offset to TSQL
     *
     * @param \Cake\Database\Query $query The query to translate
     * @return \Cake\Database\Query The modified query
     */
    protected function _selectQueryTranslator($query)
    {
        $limit = $query->clause('limit');
        $offset = $query->clause('offset');

        if ($limit && $offset === null) {
            $query->modifier(['_auto_top_' => sprintf('TOP %d', $limit)]);
        }

        if ($offset !== null && !$query->clause('order')) {
            $query->order($query->newExpr()->add('(SELECT NULL)'));
        }

        if ($this->_version() < 11 && $offset !== null) {
            return $this->_pagingSubquery($query, $limit, $offset);
        }

        return $this->_transformDistinct($query);
    }

    /**
     * Get the version of SQLserver we are connected to.
     *
     * @return int
     */
    public function _version()
    {
        $this->connect();

        return $this->_connection->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Generate a paging subquery for older versions of SQLserver.
     *
     * Prior to SQLServer 2012 there was no equivalent to LIMIT OFFSET, so a subquery must
     * be used.
     *
     * @param \Cake\Database\Query $original The query to wrap in a subquery.
     * @param int $limit The number of rows to fetch.
     * @param int $offset The number of rows to offset.
     * @return \Cake\Database\Query Modified query object.
     */
    protected function _pagingSubquery($original, $limit, $offset)
    {
        $field = '_cake_paging_._cake_page_rownum_';
        $order = $original->clause('order') ?: new OrderByExpression('(SELECT NULL)');

        $query = clone $original;
        $query->select([
                '_cake_page_rownum_' => new UnaryExpression('ROW_NUMBER() OVER', $order)
            ])->limit(null)
            ->offset(null)
            ->order([], true);

        $outer = new Query($query->connection());
        $outer->select('*')
            ->from(['_cake_paging_' => $query]);

        if ($offset) {
            $outer->where(["$field > " . (int)$offset]);
        }
        if ($limit) {
            $value = (int)$offset + (int)$limit;
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
     * Returns the passed query after rewriting the DISTINCT clause, so that drivers
     * that do not support the "ON" part can provide the actual way it should be done
     *
     * @param \Cake\Database\Query $original The query to be transformed
     * @return \Cake\Database\Query
     */
    protected function _transformDistinct($original)
    {
        if (!is_array($original->clause('distinct'))) {
            return $original;
        }

        $query = clone $original;
        $distinct = $query->clause('distinct');
        $query->distinct(false);

        $order = new OrderByExpression($distinct);
        $query
            ->select(function ($q) use ($distinct, $order) {
                $over = $q->newExpr('ROW_NUMBER() OVER')
                    ->add('(PARTITION BY')
                    ->add($q->newExpr()->add($distinct)->tieWith(','))
                    ->add($order)
                    ->add(')')
                    ->tieWith(' ');

                return [
                    '_cake_distinct_pivot_' => $over
                ];
            })
            ->limit(null)
            ->offset(null)
            ->order([], true);

        $outer = new Query($query->connection());
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
     * Returns a dictionary of expressions to be transformed when compiling a Query
     * to SQL. Array keys are method names to be called in this class
     *
     * @return array
     */
    protected function _expressionTranslators()
    {
        $namespace = 'Cake\Database\Expression';

        return [
            $namespace . '\FunctionExpression' => '_transformFunctionExpression',
            $namespace . '\TupleComparison' => '_transformTupleComparison'
        ];
    }

    /**
     * Receives a FunctionExpression and changes it so that it conforms to this
     * SQL dialect.
     *
     * @param \Cake\Database\Expression\FunctionExpression $expression The function expression to convert to TSQL.
     * @return void
     */
    protected function _transformFunctionExpression(FunctionExpression $expression)
    {
        switch ($expression->name()) {
            case 'CONCAT':
                // CONCAT function is expressed as exp1 + exp2
                $expression->name('')->tieWith(' +');
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
                $expression->name('CONVERT')->add(['date' => 'literal', $time]);
                break;
            case 'CURRENT_TIME':
                $time = new FunctionExpression('GETUTCDATE');
                $expression->name('CONVERT')->add(['time' => 'literal', $time]);
                break;
            case 'NOW':
                $expression->name('GETUTCDATE');
                break;
            case 'EXTRACT':
                $expression->name('DATEPART')->tieWith(' ,');
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
                    ->name('DATEADD')
                    ->tieWith(',')
                    ->iterateParts($visitor)
                    ->iterateParts($manipulator)
                    ->add([$params[2] => 'literal']);
                break;
            case 'DAYOFWEEK':
                $expression
                    ->name('DATEPART')
                    ->tieWith(' ')
                    ->add(['weekday, ' => 'literal'], [], true);
                break;
            case 'SUBSTR':
                $expression->name('SUBSTRING');
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

    /**
     * Get the schema dialect.
     *
     * Used by Cake\Schema package to reflect schema and
     * generate schema.
     *
     * @return \Cake\Database\Schema\MysqlSchema
     */
    public function schemaDialect()
    {
        return new SqlserverSchema($this);
    }

    /**
     * Returns a SQL snippet for creating a new transaction savepoint
     *
     * @param string $name save point name
     * @return string
     */
    public function savePointSQL($name)
    {
        return 'SAVE TRANSACTION t' . $name;
    }

    /**
     * Returns a SQL snippet for releasing a previously created save point
     *
     * @param string $name save point name
     * @return string
     */
    public function releaseSavePointSQL($name)
    {
        return 'COMMIT TRANSACTION t' . $name;
    }

    /**
     * Returns a SQL snippet for rollbacking a previously created save point
     *
     * @param string $name save point name
     * @return string
     */
    public function rollbackSavePointSQL($name)
    {
        return 'ROLLBACK TRANSACTION t' . $name;
    }

    /**
     * {@inheritDoc}
     *
     * @return \Cake\Database\SqlserverCompiler
     */
    public function newCompiler()
    {
        return new SqlserverCompiler();
    }

    /**
     * {@inheritDoc}
     */
    public function disableForeignKeySQL()
    {
        return 'EXEC sp_msforeachtable "ALTER TABLE ? NOCHECK CONSTRAINT all"';
    }

    /**
     * {@inheritDoc}
     */
    public function enableForeignKeySQL()
    {
        return 'EXEC sp_msforeachtable "ALTER TABLE ? WITH CHECK CHECK CONSTRAINT all"';
    }
}
