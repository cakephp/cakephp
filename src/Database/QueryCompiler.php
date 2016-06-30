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
namespace Cake\Database;

use Cake\Database\Expression\QueryExpression;

/**
 * Responsible for compiling a Query object into its SQL representation
 *
 * @internal
 */
class QueryCompiler
{

    /**
     * List of sprintf templates that will be used for compiling the SQL for
     * this query. There are some clauses that can be built as just as the
     * direct concatenation of the internal parts, those are listed here.
     *
     * @var array
     */
    protected $_templates = [
        'delete' => 'DELETE',
        'update' => 'UPDATE %s',
        'where' => ' WHERE %s',
        'group' => ' GROUP BY %s ',
        'having' => ' HAVING %s ',
        'order' => ' %s',
        'limit' => ' LIMIT %s',
        'offset' => ' OFFSET %s',
        'epilog' => ' %s'
    ];

    /**
     * The list of query clauses to traverse for generating a SELECT statement
     *
     * @var array
     */
    protected $_selectParts = [
        'select', 'from', 'join', 'where', 'group', 'having', 'order', 'limit',
        'offset', 'union', 'epilog'
    ];

    /**
     * The list of query clauses to traverse for generating an UPDATE statement
     *
     * @var array
     */
    protected $_updateParts = ['update', 'set', 'where', 'epilog'];

    /**
     * The list of query clauses to traverse for generating a DELETE statement
     *
     * @var array
     */
    protected $_deleteParts = ['delete', 'from', 'where', 'epilog'];

    /**
     * The list of query clauses to traverse for generating an INSERT statement
     *
     * @var array
     */
    protected $_insertParts = ['insert', 'values', 'epilog'];

    /**
     * Indicate whether or not this query dialect supports ordered unions.
     *
     * Overridden in subclasses.
     *
     * @var bool
     */
    protected $_orderedUnion = true;

    /**
     * Returns the SQL representation of the provided query after generating
     * the placeholders for the bound values using the provided generator
     *
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return \Closure
     */
    public function compile(Query $query, ValueBinder $generator)
    {
        $sql = '';
        $type = $query->type();
        $query->traverse(
            $this->_sqlCompiler($sql, $query, $generator),
            $this->{'_' . $type . 'Parts'}
        );

        // Propagate bound parameters from sub-queries if the
        // placeholders can be found in the SQL statement.
        if ($query->valueBinder() !== $generator) {
            foreach ($query->valueBinder()->bindings() as $binding) {
                $placeholder = ':' . $binding['placeholder'];
                if (preg_match('/' . $placeholder . '(?:\W|$)/', $sql) > 0) {
                    $generator->bind($placeholder, $binding['value'], $binding['type']);
                }
            }
        }
        return $sql;
    }

    /**
     * Returns a callable object that can be used to compile a SQL string representation
     * of this query.
     *
     * @param string $sql initial sql string to append to
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator The placeholder and value binder object
     * @return \Closure
     */
    protected function _sqlCompiler(&$sql, $query, $generator)
    {
        return function ($parts, $name) use (&$sql, $query, $generator) {
            if (!count($parts)) {
                return;
            }
            if ($parts instanceof ExpressionInterface) {
                $parts = [$parts->sql($generator)];
            }
            if (isset($this->_templates[$name])) {
                $parts = $this->_stringifyExpressions((array)$parts, $generator);
                return $sql .= sprintf($this->_templates[$name], implode(', ', $parts));
            }
            return $sql .= $this->{'_build' . ucfirst($name) . 'Part'}($parts, $query, $generator);
        };
    }

    /**
     * Helper function used to build the string representation of a SELECT clause,
     * it constructs the field list taking care of aliasing and
     * converting expression objects to string. This function also constructs the
     * DISTINCT clause for the query.
     *
     * @param array $parts list of fields to be transformed to string
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return string
     */
    protected function _buildSelectPart($parts, $query, $generator)
    {
        $driver = $query->connection()->driver();
        $select = 'SELECT %s%s%s';
        if ($this->_orderedUnion && $query->clause('union')) {
            $select = '(SELECT %s%s%s';
        }
        $distinct = $query->clause('distinct');
        $modifiers = $query->clause('modifier') ?: null;

        $normalized = [];
        $parts = $this->_stringifyExpressions($parts, $generator);
        foreach ($parts as $k => $p) {
            if (!is_numeric($k)) {
                $p = $p . ' AS ' . $driver->quoteIdentifier($k);
            }
            $normalized[] = $p;
        }

        if ($distinct === true) {
            $distinct = 'DISTINCT ';
        }

        if (is_array($distinct)) {
            $distinct = $this->_stringifyExpressions($distinct, $generator);
            $distinct = sprintf('DISTINCT ON (%s) ', implode(', ', $distinct));
        }
        if ($modifiers !== null) {
            $modifiers = $this->_stringifyExpressions($modifiers, $generator);
            $modifiers = implode(' ', $modifiers) . ' ';
        }

        return sprintf($select, $distinct, $modifiers, implode(', ', $normalized));
    }

    /**
     * Helper function used to build the string representation of a FROM clause,
     * it constructs the tables list taking care of aliasing and
     * converting expression objects to string.
     *
     * @param array $parts list of tables to be transformed to string
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return string
     */
    protected function _buildFromPart($parts, $query, $generator)
    {
        $select = ' FROM %s';
        $normalized = [];
        $parts = $this->_stringifyExpressions($parts, $generator);
        foreach ($parts as $k => $p) {
            if (!is_numeric($k)) {
                $p = $p . ' ' . $k;
            }
            $normalized[] = $p;
        }
        return sprintf($select, implode(', ', $normalized));
    }

    /**
     * Helper function used to build the string representation of multiple JOIN clauses,
     * it constructs the joins list taking care of aliasing and converting
     * expression objects to string in both the table to be joined and the conditions
     * to be used.
     *
     * @param array $parts list of joins to be transformed to string
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return string
     */
    protected function _buildJoinPart($parts, $query, $generator)
    {
        $joins = '';
        foreach ($parts as $join) {
            $subquery = $join['table'] instanceof Query || $join['table'] instanceof QueryExpression;
            if ($join['table'] instanceof ExpressionInterface) {
                $join['table'] = $join['table']->sql($generator);
            }

            if ($subquery) {
                $join['table'] = '(' . $join['table'] . ')';
            }

            $joins .= sprintf(' %s JOIN %s %s', $join['type'], $join['table'], $join['alias']);
            if (isset($join['conditions']) && count($join['conditions'])) {
                $joins .= sprintf(' ON %s', $join['conditions']->sql($generator));
            } else {
                $joins .= ' ON 1 = 1';
            }
        }
        return $joins;
    }

    /**
     * Helper function to generate SQL for SET expressions.
     *
     * @param array $parts List of keys & values to set.
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return string
     */
    protected function _buildSetPart($parts, $query, $generator)
    {
        $set = [];
        foreach ($parts as $part) {
            if ($part instanceof ExpressionInterface) {
                $part = $part->sql($generator);
            }
            if ($part[0] === '(') {
                $part = substr($part, 1, -1);
            }
            $set[] = $part;
        }
        return ' SET ' . implode('', $set);
    }

    /**
     * Builds the SQL string for all the UNION clauses in this query, when dealing
     * with query objects it will also transform them using their configured SQL
     * dialect.
     *
     * @param array $parts list of queries to be operated with UNION
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return string
     */
    protected function _buildUnionPart($parts, $query, $generator)
    {
        $parts = array_map(function ($p) use ($generator) {
            $p['query'] = $p['query']->sql($generator);
            $p['query'] = $p['query'][0] === '(' ? trim($p['query'], '()') : $p['query'];
            $prefix = $p['all'] ? 'ALL ' : '';
            if ($this->_orderedUnion) {
                return "{$prefix}({$p['query']})";
            }
            return $prefix . $p['query'];
        }, $parts);

        if ($this->_orderedUnion) {
            return sprintf(")\nUNION %s", implode("\nUNION ", $parts));
        }
        return sprintf("\nUNION %s", implode("\nUNION ", $parts));
    }

    /**
     * Builds the SQL fragment for INSERT INTO.
     *
     * @param array $parts The insert parts.
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return string SQL fragment.
     */
    protected function _buildInsertPart($parts, $query, $generator)
    {
        $table = $parts[0];
        $columns = $this->_stringifyExpressions($parts[1], $generator);
        return sprintf('INSERT INTO %s (%s)', $table, implode(', ', $columns));
    }

    /**
     * Builds the SQL fragment for INSERT INTO.
     *
     * @param array $parts The values parts.
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return string SQL fragment.
     */
    protected function _buildValuesPart($parts, $query, $generator)
    {
        return implode('', $this->_stringifyExpressions($parts, $generator));
    }

    /**
     * Helper function used to covert ExpressionInterface objects inside an array
     * into their string representation.
     *
     * @param array $expressions list of strings and ExpressionInterface objects
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return array
     */
    protected function _stringifyExpressions($expressions, $generator)
    {
        $result = [];
        foreach ($expressions as $k => $expression) {
            if ($expression instanceof ExpressionInterface) {
                $value = $expression->sql($generator);
                $expression = '(' . $value . ')';
            }
            $result[$k] = $expression;
        }
        return $result;
    }
}
