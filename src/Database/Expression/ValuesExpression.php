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
namespace Cake\Database\Expression;

use Cake\Database\Exception;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Cake\Database\TypeMapTrait;
use Cake\Database\Type\ExpressionTypeCasterTrait;
use Cake\Database\ValueBinder;

/**
 * An expression object to contain values being inserted.
 *
 * Helps generate SQL with the correct number of placeholders and bind
 * values correctly into the statement.
 *
 * @internal
 */
class ValuesExpression implements ExpressionInterface
{

    use ExpressionTypeCasterTrait;
    use TypeMapTrait;

    /**
     * Array of values to insert.
     *
     * @var array
     */
    protected $_values = [];

    /**
     * List of columns to ensure are part of the insert.
     *
     * @var array
     */
    protected $_columns = [];

    /**
     * The Query object to use as a values expression
     *
     * @var \Cake\Database\Query
     */
    protected $_query = false;

    /**
     * Whether or not values have been casted to expressions
     * already.
     *
     * @var string
     */
    protected $_castedExpressions = false;

    /**
     * Constructor
     *
     * @param array $columns The list of columns that are going to be part of the values.
     * @param \Cake\Database\TypeMap $typeMap A dictionary of column -> type names
     */
    public function __construct(array $columns, $typeMap)
    {
        $this->_columns = $columns;
        $this->typeMap($typeMap);
    }

    /**
     * Add a row of data to be inserted.
     *
     * @param array|\Cake\Database\Query $data Array of data to append into the insert, or
     *   a query for doing INSERT INTO .. SELECT style commands
     * @return void
     * @throws \Cake\Database\Exception When mixing array + Query data types.
     */
    public function add($data)
    {
        if ((count($this->_values) && $data instanceof Query) ||
            ($this->_query && is_array($data))
        ) {
            throw new Exception(
                'You cannot mix subqueries and array data in inserts.'
            );
        }
        if ($data instanceof Query) {
            $this->query($data);

            return;
        }
        $this->_values[] = $data;
        $this->_castedExpressions = false;
    }

    /**
     * Sets the columns to be inserted. If no params are passed, then it returns
     * the currently stored columns
     *
     * @param array|null $cols arrays with columns to be inserted
     * @return array|$this
     */
    public function columns($cols = null)
    {
        if ($cols === null) {
            return $this->_columns;
        }
        $this->_columns = $cols;
        $this->_castedExpressions = false;

        return $this;
    }

    /**
     * Get the bare column names.
     *
     * Because column names could be identifier quoted, we
     * need to strip the identifiers off of the columns.
     *
     * @return array
     */
    protected function _columnNames()
    {
        $columns = [];
        foreach ($this->_columns as $col) {
            if (is_string($col)) {
                $col = trim($col, '`[]"');
            }
            $columns[] = $col;
        }

        return $columns;
    }

    /**
     * Sets the values to be inserted. If no params are passed, then it returns
     * the currently stored values
     *
     * @param array|null $values arrays with values to be inserted
     * @return array|$this
     */
    public function values($values = null)
    {
        if ($values === null) {
            if (!$this->_castedExpressions) {
                $this->_processExpressions();
            }

            return $this->_values;
        }
        $this->_values = $values;
        $this->_castedExpressions = false;

        return $this;
    }

    /**
     * Sets the query object to be used as the values expression to be evaluated
     * to insert records in the table. If no params are passed, then it returns
     * the currently stored query
     *
     * @param \Cake\Database\Query|null $query The query to set/get
     * @return \Cake\Database\Query
     */
    public function query(Query $query = null)
    {
        if ($query === null) {
            return $this->_query;
        }
        $this->_query = $query;
    }

    /**
     * Convert the values into a SQL string with placeholders.
     *
     * @param \Cake\Database\ValueBinder $generator Placeholder generator object
     * @return string
     */
    public function sql(ValueBinder $generator)
    {
        if (empty($this->_values) && empty($this->_query)) {
            return '';
        }

        if (!$this->_castedExpressions) {
            $this->_processExpressions();
        }

        $i = 0;
        $columns = [];

        $columns = $this->_columnNames();
        $defaults = array_fill_keys($columns, null);
        $placeholders = [];

        $types = [];
        $typeMap = $this->typeMap();
        foreach ($defaults as $col => $v) {
            $types[$col] = $typeMap->type($col);
        }

        foreach ($this->_values as $row) {
            $row += $defaults;
            $rowPlaceholders = [];

            foreach ($columns as $column) {
                $value = $row[$column];

                if ($value instanceof ExpressionInterface) {
                    $rowPlaceholders[] = '(' . $value->sql($generator) . ')';
                    continue;
                }

                $placeholder = $generator->placeholder($i);
                $rowPlaceholders[] = $placeholder;
                $generator->bind($placeholder, $value, $types[$column]);
            }

            $placeholders[] = implode(', ', $rowPlaceholders);
        }

        if ($this->query()) {
            return ' ' . $this->query()->sql($generator);
        }

        return sprintf(' VALUES (%s)', implode('), (', $placeholders));
    }

    /**
     * Traverse the values expression.
     *
     * This method will also traverse any queries that are to be used in the INSERT
     * values.
     *
     * @param callable $visitor The visitor to traverse the expression with.
     * @return void
     */
    public function traverse(callable $visitor)
    {
        if ($this->_query) {
            return;
        }

        if (!$this->_castedExpressions) {
            $this->_processExpressions();
        }

        foreach ($this->_values as $v) {
            if ($v instanceof ExpressionInterface) {
                $v->traverse($visitor);
            }
            if (!is_array($v)) {
                continue;
            }
            foreach ($v as $column => $field) {
                if ($field instanceof ExpressionInterface) {
                    $visitor($field);
                    $field->traverse($visitor);
                }
            }
        }
    }

    /**
     * Converts values that need to be casted to expressions
     *
     * @return void
     */
    protected function _processExpressions()
    {
        $types = [];
        $typeMap = $this->typeMap();

        $columns = $this->_columnNames();
        foreach ($columns as $c) {
            if (!is_scalar($c)) {
                continue;
            }
            $types[$c] = $typeMap->type($c);
        }

        $types = $this->_requiresToExpressionCasting($types);

        if (empty($types)) {
            return;
        }

        foreach ($this->_values as $row => $values) {
            foreach ($types as $col => $type) {
                $this->_values[$row][$col] = $type->toExpression($values[$col]);
            }
        }
        $this->_castedExpressions = true;
    }
}
