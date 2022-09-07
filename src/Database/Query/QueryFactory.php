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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Query;

use Cake\Database\Connection;
use Cake\Database\ExpressionInterface;
use Closure;

/**
 * Factory class for generating instances of Select, Insert, Update, Delete queries.
 */
class QueryFactory
{
    /**
     * Constructor/
     *
     * @param \Cake\Database\Connection $connection Connection instance.
     */
    public function __construct(
        protected Connection $connection,
    ) {
    }

    /**
     * Create a new SelectQuery instance.
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array|string|float|int $fields Fields/columns list for the query.
     * @param array|string $table List of tables to query.
     * @param array<string, string> $types Associative array containing the types to be used for casting.
     * @return \Cake\Database\Query\SelectQuery
     */
    public function select(
        ExpressionInterface|Closure|array|string|float|int $fields = [],
        array|string $table = [],
        array $types = []
    ): SelectQuery {
        $query = new SelectQuery($this->connection);

        $query
            ->select($fields)
            ->from($table)
            ->setDefaultTypes($types);

        return $query;
    }

    /**
     * Create a new InsertQuery instance.
     *
     * @param string|null $table The table to insert rows into.
     * @param array $values Associative array of column => value to be inserted.
     * @param array<int|string, string> $types Associative array containing the types to be used for casting.
     * @return \Cake\Database\Query\InsertQuery
     */
    public function insert(?string $table = null, array $values = [], array $types = []): InsertQuery
    {
        $query = new InsertQuery($this->connection);

        if ($table) {
            $query->into($table);
        }

        if ($values) {
            $columns = array_keys($values);
            $query
                ->insert($columns, $types)
                ->values($values);
        }

        return $query;
    }

    /**
     * Create a new UpdateQuery instance.
     *
     * @param \Cake\Database\ExpressionInterface|string|null $table The table to update rows of.
     * @param array $values Values to be updated.
     * @param array $conditions Conditions to be set for the update statement.
     * @param array<string, string> $types Associative array containing the types to be used for casting.
     * @return \Cake\Database\Query\UpdateQuery
     */
    public function update(
        ExpressionInterface|string|null $table = null,
        array $values = [],
        array $conditions = [],
        array $types = []
    ): UpdateQuery {
        $query = new UpdateQuery($this->connection);

        if ($table) {
            $query->update($table);
        }
        if ($values) {
            $query->set($values, $types);
        }
        if ($conditions) {
            $query->where($conditions, $types);
        }

        return $query;
    }

    /**
     * Create a new DeleteQuery instance.
     *
     * @param string|null $table The table to delete rows from.
     * @param array $conditions Conditions to be set for the delete statement.
     * @param array<string, string> $types Associative array containing the types to be used for casting.
     * @return \Cake\Database\Query\DeleteQuery
     */
    public function delete(?string $table = null, array $conditions = [], array $types = []): DeleteQuery
    {
        $query = (new DeleteQuery($this->connection))
            ->delete($table);

        if ($conditions) {
            $query->where($conditions, $types);
        }

        return $query;
    }
}
