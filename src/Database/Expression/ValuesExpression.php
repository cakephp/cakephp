<?php
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
     * @var \Cake\Database\Query|null
     */
    protected $_query;

    /**
     * Whether or not values have been casted to expressions
     * already.
     *
     * @var bool
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
        $this->setTypeMap($typeMap);
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
            $this->setQuery($data);

            return;
        }
        $this->_values[] = $data;
        $this->_castedExpressions = false;
    }

    /**
     * Sets the columns to be inserted.
     *
     * @param array $cols Array with columns to be inserted.
     * @return $this
     */
    public function setColumns($cols)
    {
        $this->_columns = $cols;
        $this->_castedExpressions = false;

        return $this;
    }

    /**
     * Gets the columns to be inserted.
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->_columns;
    }

    /**
     * Sets the columns to be inserted. If no params are passed, then it returns
     * the currently stored columns.
     *
     * @deprecated 3.4.0 Use setColumns()/getColumns() instead.
     * @param array|null $cols Array with columns to be inserted.
     * @return array|$this
     */
    public function columns($cols = null)
    {
        deprecationWarning(
            'ValuesExpression::columns() is deprecated. ' .
            'Use ValuesExpression::setColumns()/getColumns() instead.'
        );
        if ($cols !== null) {
            return $this->setColumns($cols);
        }

        return $this->getColumns();
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
     * Sets the values to be inserted.
     *
     * @param array $values Array with values to be inserted.
     * @return $this
     */
    public function setValues($values)
    {
        $this->_values = $values;
        $this->_castedExpressions = false;

        return $this;
    }

    /**
     * Gets the values to be inserted.
     *
     * @return array
     */
    public function getValues()
    {
        if (!$this->_castedExpressions) {
            $this->_processExpressions();
        }

        return $this->_values;
    }

    /**
     * Sets the values to be inserted. If no params are passed, then it returns
     * the currently stored values
     *
     * @deprecated 3.4.0 Use setValues()/getValues() instead.
     * @param array|null $values Array with values to be inserted.
     * @return array|$this
     */
    public function values($values = null)
    {
        deprecationWarning(
            'ValuesExpression::values() is deprecated. ' .
            'Use ValuesExpression::setValues()/getValues() instead.'
        );
        if ($values !== null) {
            return $this->setValues($values);
        }

        return $this->getValues();
    }

    /**
     * Sets the query object to be used as the values expression to be evaluated
     * to insert records in the table.
     *
     * @param \Cake\Database\Query $query The query to set
     * @return $this
     */
    public function setQuery(Query $query)
    {
        $this->_query = $query;

        return $this;
    }

    /**
     * Gets the query object to be used as the values expression to be evaluated
     * to insert records in the table.
     *
     * @return \Cake\Database\Query|null
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Sets the query object to be used as the values expression to be evaluated
     * to insert records in the table. If no params are passed, then it returns
     * the currently stored query
     *
     * @deprecated 3.4.0 Use setQuery()/getQuery() instead.
     * @param \Cake\Database\Query|null $query The query to set
     * @return \Cake\Database\Query|null|$this
     */
    public function query(Query $query = null)
    {
        deprecationWarning(
            'ValuesExpression::query() is deprecated. ' .
            'Use ValuesExpression::setQuery()/getQuery() instead.'
        );
        if ($query !== null) {
            return $this->setQuery($query);
        }

        return $this->getQuery();
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

        $columns = $this->_columnNames();
        $defaults = array_fill_keys($columns, null);
        $placeholders = [];

        $types = [];
        $typeMap = $this->getTypeMap();
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

                $placeholder = $generator->placeholder('c');
                $rowPlaceholders[] = $placeholder;
                $generator->bind($placeholder, $value, $types[$column]);
            }

            $placeholders[] = implode(', ', $rowPlaceholders);
        }

        if ($this->getQuery()) {
            return ' ' . $this->getQuery()->sql($generator);
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
        $typeMap = $this->getTypeMap();

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
                /* @var \Cake\Database\Type\ExpressionTypeInterface $type */
                $this->_values[$row][$col] = $type->toExpression($values[$col]);
            }
        }
        $this->_castedExpressions = true;
    }
}
