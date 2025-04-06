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
namespace Cake\Database;

use Cake\Database\Exception\DatabaseException;
use Cake\Database\Expression\FieldInterface;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Query\DeleteQuery;
use Cake\Database\Query\InsertQuery;
use Cake\Database\Query\SelectQuery;
use Cake\Database\Query\UpdateQuery;

/**
 * Contains all the logic related to quoting identifiers in a Query object
 *
 * @internal
 */
class IdentifierQuoter
{
    /**
     * Constructor
     *
     * @param string $startQuote String used to start a database identifier quoting to make it safe.
     * @param string $endQuote String used to end a database identifier quoting to make it safe.
     */
    public function __construct(
        protected string $startQuote,
        protected string $endQuote,
    ) {
    }

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words
     *
     * @param string $identifier The identifier to quote.
     * @return string
     */
    public function quoteIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);

        if ($identifier === '*' || $identifier === '') {
            return $identifier;
        }

        // string
        if (preg_match('/^[\w-]+$/u', $identifier)) {
            return $this->startQuote . $identifier . $this->endQuote;
        }

        // string.string
        if (preg_match('/^[\w-]+\.[^ \*]*$/u', $identifier)) {
            $items = explode('.', $identifier);

            return $this->startQuote . implode($this->endQuote . '.' . $this->startQuote, $items) . $this->endQuote;
        }

        // string.*
        if (preg_match('/^[\w-]+\.\*$/u', $identifier)) {
            return $this->startQuote . str_replace('.*', $this->endQuote . '.*', $identifier);
        }

        // Functions
        if (preg_match('/^([\w-]+)\((.*)\)$/', $identifier, $matches)) {
            return $matches[1] . '(' . $this->quoteIdentifier($matches[2]) . ')';
        }

        // Alias.field AS thing
        if (preg_match('/^([\w-]+(\.[\w\s-]+|\(.*\))*)\s+AS\s*([\w-]+)$/ui', $identifier, $matches)) {
            return $this->quoteIdentifier($matches[1]) . ' AS ' . $this->quoteIdentifier($matches[3]);
        }

        // string.string with spaces
        if (preg_match('/^([\w-]+\.[\w][\w\s-]*[\w])(.*)/u', $identifier, $matches)) {
            $items = explode('.', $matches[1]);
            $field = implode($this->endQuote . '.' . $this->startQuote, $items);

            return $this->startQuote . $field . $this->endQuote . $matches[2];
        }

        if (preg_match('/^[\w\s-]*[\w-]+/u', $identifier)) {
            return $this->startQuote . $identifier . $this->endQuote;
        }

        return $identifier;
    }

    /**
     * Iterates over each of the clauses in a query looking for identifiers and
     * quotes them
     *
     * @param \Cake\Database\Query $query The query to have its identifiers quoted
     * @return \Cake\Database\Query
     */
    public function quote(Query $query): Query
    {
        $binder = $query->getValueBinder();
        $query->setValueBinder(null);

        match (true) {
            $query instanceof InsertQuery => $this->_quoteInsert($query),
            $query instanceof SelectQuery => $this->_quoteSelect($query),
            $query instanceof UpdateQuery => $this->_quoteUpdate($query),
            $query instanceof DeleteQuery => $this->_quoteDelete($query),
            default =>
                throw new DatabaseException(sprintf(
                    'Instance of SelectQuery, UpdateQuery, InsertQuery, DeleteQuery expected. Found `%s` instead.',
                    get_debug_type($query),
                ))
        };

        $query->traverseExpressions($this->quoteExpression(...));

        return $query->setValueBinder($binder);
    }

    /**
     * Quotes identifiers inside expression objects
     *
     * @param \Cake\Database\ExpressionInterface $expression The expression object to walk and quote.
     * @return void
     */
    public function quoteExpression(ExpressionInterface $expression): void
    {
        match (true) {
            $expression instanceof FieldInterface => $this->_quoteComparison($expression),
            $expression instanceof OrderByExpression => $this->_quoteOrderBy($expression),
            $expression instanceof IdentifierExpression => $this->_quoteIdentifierExpression($expression),
            default => null // Nothing to do if there is no match
        };
    }

    /**
     * Quotes all identifiers in each of the clauses/parts of a query
     *
     * @param \Cake\Database\Query $query The query to quote.
     * @param array $parts Query clauses.
     * @return void
     */
    protected function _quoteParts(Query $query, array $parts): void
    {
        foreach ($parts as $part) {
            $contents = $query->clause($part);

            if (!is_array($contents)) {
                continue;
            }

            $result = $this->_basicQuoter($contents);
            if ($result) {
                $part = match ($part) {
                    'group' => 'groupBy',
                    'order' => 'orderBy',
                    default => $part,
                };

                $query->{$part}($result, true);
            }
        }
    }

    /**
     * A generic identifier quoting function used for various parts of the query
     *
     * @param array<string, mixed> $part the part of the query to quote
     * @return array<string, mixed>
     */
    protected function _basicQuoter(array $part): array
    {
        $result = [];
        foreach ($part as $alias => $value) {
            $value = !is_string($value) ? $value : $this->quoteIdentifier($value);
            $alias = is_numeric($alias) ? $alias : $this->quoteIdentifier($alias);
            $result[$alias] = $value;
        }

        return $result;
    }

    /**
     * Quotes both the table and alias for an array of joins as stored in a Query
     * object
     *
     * @param array $joins The joins to quote.
     * @return array<string, array>
     */
    protected function _quoteJoins(array $joins): array
    {
        $result = [];
        foreach ($joins as $value) {
            $alias = '';
            if (!empty($value['alias'])) {
                $alias = $this->quoteIdentifier($value['alias']);
                $value['alias'] = $alias;
            }

            if (is_string($value['table'])) {
                $value['table'] = $this->quoteIdentifier($value['table']);
            }

            $result[$alias] = $value;
        }

        return $result;
    }

    /**
     * Quotes all identifiers in each of the clauses of a SELECT query
     *
     * @param \Cake\Database\Query\SelectQuery<mixed> $query The query to quote.
     * @return void
     */
    protected function _quoteSelect(SelectQuery $query): void
    {
        $this->_quoteParts($query, ['select', 'distinct', 'from', 'group']);

        $joins = $query->clause('join');
        if ($joins) {
            $joins = $this->_quoteJoins($joins);
            $query->join($joins, [], true);
        }
    }

    /**
     * Quotes all identifiers in each of the clauses of a DELETE query
     *
     * @param \Cake\Database\Query\DeleteQuery $query The query to quote.
     * @return void
     */
    protected function _quoteDelete(DeleteQuery $query): void
    {
        $this->_quoteParts($query, ['from']);

        $joins = $query->clause('join');
        if ($joins) {
            $joins = $this->_quoteJoins($joins);
            $query->join($joins, [], true);
        }
    }

    /**
     * Quotes the table name and columns for an insert query
     *
     * @param \Cake\Database\Query\InsertQuery $query The insert query to quote.
     * @return void
     */
    protected function _quoteInsert(InsertQuery $query): void
    {
        $insert = $query->clause('insert');
        if (!isset($insert[0]) || !isset($insert[1])) {
            return;
        }
        [$table, $columns] = $insert;
        $table = $this->quoteIdentifier($table);
        foreach ($columns as &$column) {
            if (is_scalar($column)) {
                $column = $this->quoteIdentifier((string)$column);
            }
        }
        $query->insert($columns)->into($table);
    }

    /**
     * Quotes the table name for an update query
     *
     * @param \Cake\Database\Query\UpdateQuery $query The update query to quote.
     * @return void
     */
    protected function _quoteUpdate(UpdateQuery $query): void
    {
        $table = $query->clause('update')[0];

        if (is_string($table)) {
            $query->update($this->quoteIdentifier($table));
        }
    }

    /**
     * Quotes identifiers in expression objects implementing the field interface
     *
     * @param \Cake\Database\Expression\FieldInterface $expression The expression to quote.
     * @return void
     */
    protected function _quoteComparison(FieldInterface $expression): void
    {
        $field = $expression->getField();
        if (is_string($field)) {
            $expression->setField($this->quoteIdentifier($field));
        } elseif (is_array($field)) {
            $quoted = [];
            foreach ($field as $f) {
                $quoted[] = $this->quoteIdentifier($f);
            }
            $expression->setField($quoted);
        } else {
            $this->quoteExpression($field);
        }
    }

    /**
     * Quotes identifiers in "order by" expression objects
     *
     * Strings with spaces are treated as literal expressions
     * and will not have identifiers quoted.
     *
     * @param \Cake\Database\Expression\OrderByExpression $expression The expression to quote.
     * @return void
     */
    protected function _quoteOrderBy(OrderByExpression $expression): void
    {
        $expression->iterateParts(function ($part, &$field) {
            if (is_string($field)) {
                $field = $this->quoteIdentifier($field);

                return $part;
            }
            if (is_string($part) && !str_contains($part, ' ')) {
                return $this->quoteIdentifier($part);
            }

            return $part;
        });
    }

    /**
     * Quotes identifiers in "order by" expression objects
     *
     * @param \Cake\Database\Expression\IdentifierExpression $expression The identifiers to quote.
     * @return void
     */
    protected function _quoteIdentifierExpression(IdentifierExpression $expression): void
    {
        $expression->setIdentifier(
            $this->quoteIdentifier($expression->getIdentifier()),
        );
    }
}
