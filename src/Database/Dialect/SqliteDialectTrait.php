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

use Cake\Database\ExpressionInterface;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Schema\SqliteSchema;
use Cake\Database\SqlDialectTrait;
use Cake\Database\SqliteCompiler;

/**
 * SQLite dialect trait
 *
 * @internal
 */
trait SqliteDialectTrait
{

    use SqlDialectTrait;
    use TupleComparisonTranslatorTrait;

    /**
     *  String used to start a database identifier quoting to make it safe
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
     * The schema dialect class for this driver
     *
     * @var \Cake\Database\Schema\SqliteSchema
     */
    protected $_schemaDialect;

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
        'year' => 'Y'
    ];

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
     * @param \Cake\Database\Expression\FunctionExpression $expression The function expression
     *   to translate for SQLite.
     * @return void
     */
    protected function _transformFunctionExpression(FunctionExpression $expression)
    {
        switch ($expression->name()) {
            case 'CONCAT':
                // CONCAT function is expressed as exp1 || exp2
                $expression->name('')->tieWith(' ||');
                break;
            case 'DATEDIFF':
                $expression
                    ->name('ROUND')
                    ->tieWith('-')
                    ->iterateParts(function ($p) {
                        return new FunctionExpression('JULIANDAY', [$p['value']], [$p['type']]);
                    });
                break;
            case 'NOW':
                $expression->name('DATETIME')->add(["'now'" => 'literal']);
                break;
            case 'CURRENT_DATE':
                $expression->name('DATE')->add(["'now'" => 'literal']);
                break;
            case 'CURRENT_TIME':
                $expression->name('TIME')->add(["'now'" => 'literal']);
                break;
            case 'EXTRACT':
                $expression
                    ->name('STRFTIME')
                    ->tieWith(' ,')
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
                    ->name('DATE')
                    ->tieWith(',')
                    ->iterateParts(function ($p, $key) {
                        if ($key === 1) {
                            $p = ['value' => $p, 'type' => null];
                        }

                        return $p;
                    });
                break;
            case 'DAYOFWEEK':
                $expression
                    ->name('STRFTIME')
                    ->tieWith(' ')
                    ->add(["'%w', " => 'literal'], [], true)
                    ->add([') + (1' => 'literal']); // Sqlite starts on index 0 but Sunday should be 1
                break;
        }
    }

    /**
     * Transforms an insert query that is meant to insert multiple rows at a time,
     * otherwise it leaves the query untouched.
     *
     * The way SQLite works with multi insert is by having multiple select statements
     * joined with UNION.
     *
     * @param \Cake\Database\Query $query The query to translate
     * @return \Cake\Database\Query
     */
    protected function _insertQueryTranslator($query)
    {
        $v = $query->clause('values');
        if (count($v->values()) === 1 || $v->query()) {
            return $query;
        }

        $newQuery = $query->connection()->newQuery();
        $cols = $v->columns();
        $placeholder = 0;
        $replaceQuery = false;

        foreach ($v->values() as $k => $val) {
            $fillLength = count($cols) - count($val);
            if ($fillLength > 0) {
                $val = array_merge($val, array_fill(0, $fillLength, null));
            }

            foreach ($val as $col => $attr) {
                if (!($attr instanceof ExpressionInterface)) {
                    $val[$col] = sprintf(':c%d', $placeholder);
                    $placeholder++;
                }
            }

            $select = array_combine($cols, $val);
            if ($k === 0) {
                $replaceQuery = true;
                $newQuery->select($select);
                continue;
            }

            $q = $newQuery->connection()->newQuery();
            $newQuery->unionAll($q->select($select));
        }

        if ($replaceQuery) {
            $v->query($newQuery);
        }

        return $query;
    }

    /**
     * Get the schema dialect.
     *
     * Used by Cake\Database\Schema package to reflect schema and
     * generate schema.
     *
     * @return \Cake\Database\Schema\SqliteSchema
     */
    public function schemaDialect()
    {
        if (!$this->_schemaDialect) {
            $this->_schemaDialect = new SqliteSchema($this);
        }

        return $this->_schemaDialect;
    }

    /**
     * {@inheritDoc}
     */
    public function disableForeignKeySQL()
    {
        return 'PRAGMA foreign_keys = OFF';
    }

    /**
     * {@inheritDoc}
     */
    public function enableForeignKeySQL()
    {
        return 'PRAGMA foreign_keys = ON';
    }

    /**
     * {@inheritDoc}
     *
     * @return \Cake\Database\SqliteCompiler
     */
    public function newCompiler()
    {
        return new SqliteCompiler();
    }
}
