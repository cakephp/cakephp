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
use Cake\Database\SqlDialectTrait;

/**
 * Contains functions that encapsulates the SQL dialect used by Postgres,
 * including query translators and schema introspection.
 *
 * @internal
 */
trait PostgresDialectTrait
{

    use SqlDialectTrait;

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
     * @var \Cake\Database\Schema\PostgresSchema
     */
    protected $_schemaDialect;

    /**
     * Distinct clause needs no transformation
     *
     * @param \Cake\Database\Query $query The query to be transformed
     * @return \Cake\Database\Query
     */
    protected function _transformDistinct($query)
    {
        return $query;
    }

    /**
     * Modifies the original insert query to append a "RETURNING *" epilogue
     * so that the latest insert id can be retrieved
     *
     * @param \Cake\Database\Query $query The query to translate.
     * @return \Cake\Database\Query
     */
    protected function _insertQueryTranslator($query)
    {
        if (!$query->clause('epilog')) {
            $query->epilog('RETURNING *');
        }
        return $query;
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
            $namespace . '\FunctionExpression' => '_transformFunctionExpression'
        ];
    }

    /**
     * Receives a FunctionExpression and changes it so that it conforms to this
     * SQL dialect.
     *
     * @param \Cake\Database\Expression\FunctionExpression $expression The function expression to convert
     *   to postgres SQL.
     * @return void
     */
    protected function _transformFunctionExpression(FunctionExpression $expression)
    {
        switch ($expression->name()) {
            case 'CONCAT':
                // CONCAT function is expressed as exp1 || exp2
                $expression->name('')->type(' ||');
                break;
            case 'DATEDIFF':
                $expression
                    ->name('')
                    ->type('-')
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
                $expression->name('CAST')->type(' AS ')->add([$time, 'date' => 'literal']);
                break;
            case 'CURRENT_TIME':
                $time = new FunctionExpression('LOCALTIMESTAMP', [' 0 ' => 'literal']);
                $expression->name('CAST')->type(' AS ')->add([$time, 'time' => 'literal']);
                break;
            case 'NOW':
                $expression->name('LOCALTIMESTAMP')->add([' 0 ' => 'literal']);
                break;
            case 'DATE_ADD':
                $expression
                    ->name('')
                    ->type(' + INTERVAL')
                    ->iterateParts(function ($p, $key) {
                        if ($key === 1) {
                            $p = sprintf("'%s'", $p);
                        }
                        return $p;
                    });
                break;
            case 'DAYOFWEEK':
                $expression
                    ->name('EXTRACT')
                    ->type(' ')
                    ->add(['DOW FROM' => 'literal'], [], true)
                    ->add([') + (1' => 'literal']); // Postgres starts on index 0 but Sunday should be 1
                break;
        }
    }

    /**
     * Get the schema dialect.
     *
     * Used by Cake\Database\Schema package to reflect schema and
     * generate schema.
     *
     * @return \Cake\Database\Schema\PostgresSchema
     */
    public function schemaDialect()
    {
        if (!$this->_schemaDialect) {
            $this->_schemaDialect = new \Cake\Database\Schema\PostgresSchema($this);
        }
        return $this->_schemaDialect;
    }

    /**
     * {@inheritDoc}
     */
    public function disableForeignKeySQL()
    {
        return 'SET CONSTRAINTS ALL DEFERRED';
    }

    /**
     * {@inheritDoc}
     */
    public function enableForeignKeySQL()
    {
        return 'SET CONSTRAINTS ALL IMMEDIATE';
    }
}
