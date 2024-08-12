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
namespace Cake\Database\Expression;

use Cake\Database\Exception\DatabaseException;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Cake\Database\Type\ExpressionTypeCasterTrait;
use Cake\Database\TypeMap;
use Cake\Database\TypeMapTrait;
use Cake\Database\ValueBinder;
use Closure;

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
    protected array $_values = [];

    /**
     * List of columns to ensure are part of the insert.
     *
     * @var array
     */
    protected array $_columns = [];

    /**
     * The Query object to use as a values expression
     *
     * @var \Cake\Database\Query|null
     */
    protected ?Query $_query = null;

    /**
     * Whether values have been casted to expressions
     * already.
     *
     * @var bool
     */
    protected bool $_castedExpressions = false;

    /**
     * Constructor
     *
     * @param array $columns The list of columns that are going to be part of the values.
     * @param \Cake\Database\TypeMap $typeMap A dictionary of column -> type names
     */
    public function __construct(array $columns, TypeMap $typeMap)
    {
        $this->_columns = $columns;
        $this->setTypeMap($typeMap);
    }

    /**
     * Add a row of data to be inserted.
     *
     * @param \Cake\Database\Query|array $values Array of data to append into the insert, or
     *   a query for doing INSERT INTO .. SELECT style commands
     * @return void
     * @throws \Cake\Database\Exception\DatabaseException When mixing array + Query data types.
     */
    public function add(Query|array $values): void
    {
        if (
            (
                count($this->_values) &&
                $values instanceof Query
            ) ||
            (
                $this->_query &&
                is_array($values)
            )
        ) {
            throw new DatabaseException(
                'You cannot mix subqueries and array values in inserts.'
            );
        }
        if ($values instanceof Query) {
            $this->setQuery($values);

            return;
        }
        $this->_values[] = $values;
        $this->_castedExpressions = false;
    }

    /**
     * Sets the columns to be inserted.
     *
     * @param array $columns Array with columns to be inserted.
     * @return $this
     */
    public function setColumns(array $columns)
    {
        $this->_columns = $columns;
        $this->_castedExpressions = false;

        return $this;
    }

    /**
     * Gets the columns to be inserted.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->_columns;
    }

    /**
     * Get the bare column names.
     *
     * Because column names could be identifier quoted, we
     * need to strip the identifiers off of the columns.
     *
     * @return array
     */
    protected function _columnNames(): array
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
    public function setValues(array $values)
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
    public function getValues(): array
    {
        if (!$this->_castedExpressions) {
            $this->_processExpressions();
        }

        return $this->_values;
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
    public function getQuery(): ?Query
    {
        return $this->_query;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        if (!$this->_values && $this->_query === null) {
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
                    $rowPlaceholders[] = '(' . $value->sql($binder) . ')';
                    continue;
                }

                $placeholder = $binder->placeholder('c');
                $rowPlaceholders[] = $placeholder;
                $binder->bind($placeholder, $value, $types[$column]);
            }

            $placeholders[] = implode(', ', $rowPlaceholders);
        }

        $query = $this->getQuery();
        if ($query) {
            return ' ' . $query->sql($binder);
        }

        return sprintf(' VALUES (%s)', implode('), (', $placeholders));
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback)
    {
        if ($this->_query) {
            return $this;
        }

        if (!$this->_castedExpressions) {
            $this->_processExpressions();
        }

        foreach ($this->_values as $v) {
            if ($v instanceof ExpressionInterface) {
                $v->traverse($callback);
            }
            if (!is_array($v)) {
                continue;
            }
            foreach ($v as $field) {
                if ($field instanceof ExpressionInterface) {
                    $callback($field);
                    $field->traverse($callback);
                }
            }
        }

        return $this;
    }

    /**
     * Converts values that need to be casted to expressions
     *
     * @return void
     */
    protected function _processExpressions(): void
    {
        $types = [];
        $typeMap = $this->getTypeMap();

        $columns = $this->_columnNames();
        foreach ($columns as $c) {
            if (!is_string($c) && !is_int($c)) {
                continue;
            }
            $types[$c] = $typeMap->type($c);
        }

        $types = $this->_requiresToExpressionCasting($types);

        if (!$types) {
            return;
        }

        foreach ($this->_values as $row => $values) {
            foreach ($types as $col => $type) {
                /** @var \Cake\Database\Type\ExpressionTypeInterface $type */
                $this->_values[$row][$col] = $type->toExpression($values[$col]);
            }
        }
        $this->_castedExpressions = true;
    }
}
