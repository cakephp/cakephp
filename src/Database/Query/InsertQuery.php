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
 * @since         4.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Query;

use Cake\Database\Exception\DatabaseException;
use Cake\Database\Expression\ValuesExpression;
use Cake\Database\Query;
use InvalidArgumentException;

/**
 * This class is used to generate INSERT queries for the relational database.
 */
class InsertQuery extends Query
{
    /**
     * Type of this query.
     *
     * @var string
     */
    protected string $_type = self::TYPE_INSERT;

    /**
     * List of SQL parts that will be used to build this query.
     *
     * @var array<string, mixed>
     */
    protected array $_parts = [
        'comment' => null,
        'with' => [],
        'insert' => [],
        'modifier' => [],
        'values' => [],
        'epilog' => null,
    ];

    /**
     * Create an insert query.
     *
     * Note calling this method will reset any data previously set
     * with Query::values().
     *
     * @param array $columns The columns to insert into.
     * @param array<int|string, string> $types A map between columns & their datatypes.
     * @return $this
     * @throws \InvalidArgumentException When there are 0 columns.
     */
    public function insert(array $columns, array $types = [])
    {
        if (empty($columns)) {
            throw new InvalidArgumentException('At least 1 column is required to perform an insert.');
        }
        $this->_dirty();
        $this->_parts['insert'][1] = $columns;
        if (!$this->_parts['values']) {
            $this->_parts['values'] = new ValuesExpression($columns, $this->getTypeMap()->setTypes($types));
        } else {
            /** @var \Cake\Database\Expression\ValuesExpression $valuesExpr */
            $valuesExpr = $this->_parts['values'];
            $valuesExpr->setColumns($columns);
        }

        return $this;
    }

    /**
     * Set the table name for insert queries.
     *
     * @param string $table The table name to insert into.
     * @return $this
     */
    public function into(string $table)
    {
        $this->_dirty();
        $this->_parts['insert'][0] = $table;

        return $this;
    }

    /**
     * Set the values for an insert query.
     *
     * Multi inserts can be performed by calling values() more than one time,
     * or by providing an array of value sets. Additionally $data can be a Query
     * instance to insert data from another SELECT statement.
     *
     * @param \Cake\Database\Expression\ValuesExpression|\Cake\Database\Query|array $data The data to insert.
     * @return $this
     * @throws \Cake\Database\Exception\DatabaseException if you try to set values before declaring columns.
     *   Or if you try to set values on non-insert queries.
     */
    public function values(ValuesExpression|Query|array $data)
    {
        if (empty($this->_parts['insert'])) {
            throw new DatabaseException(
                'You cannot add values before defining columns to use.'
            );
        }

        $this->_dirty();
        if ($data instanceof ValuesExpression) {
            $this->_parts['values'] = $data;

            return $this;
        }

        /** @var \Cake\Database\Expression\ValuesExpression $valuesExpr */
        $valuesExpr = $this->_parts['values'];
        $valuesExpr->add($data);

        return $this;
    }
}
