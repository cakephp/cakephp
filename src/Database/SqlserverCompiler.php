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
namespace Cake\Database;

/**
 * Responsible for compiling a Query object into its SQL representation
 * for SQL Server
 *
 * @internal
 */
class SqlserverCompiler extends QueryCompiler
{
    /**
     * SQLserver does not support ORDER BY in UNION queries.
     *
     * @var bool
     */
    protected $_orderedUnion = false;

    /**
     * {@inheritDoc}
     */
    protected $_templates = [
        'delete' => 'DELETE',
        'where' => ' WHERE %s',
        'group' => ' GROUP BY %s ',
        'having' => ' HAVING %s ',
        'order' => ' %s',
        'offset' => ' OFFSET %s ROWS',
        'epilog' => ' %s',
    ];

    /**
     * {@inheritDoc}
     */
    protected $_selectParts = [
        'select', 'from', 'join', 'where', 'group', 'having', 'order', 'offset',
        'limit', 'union', 'epilog',
    ];

    /**
     * Generates the INSERT part of a SQL query
     *
     * To better handle concurrency and low transaction isolation levels,
     * we also include an OUTPUT clause so we can ensure we get the inserted
     * row's data back.
     *
     * @param array $parts The parts to build
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return string
     */
    protected function _buildInsertPart($parts, $query, $generator)
    {
        $table = $parts[0];
        $columns = $this->_stringifyExpressions($parts[1], $generator);
        $modifiers = $this->_buildModifierPart($query->clause('modifier'), $query, $generator);

        return sprintf(
            'INSERT%s INTO %s (%s) OUTPUT INSERTED.*',
            $modifiers,
            $table,
            implode(', ', $columns)
        );
    }

    /**
     * Generates the LIMIT part of a SQL query
     *
     * @param int $limit the limit clause
     * @param \Cake\Database\Query $query The query that is being compiled
     * @return string
     */
    protected function _buildLimitPart($limit, $query)
    {
        if ($limit === null || $query->clause('offset') === null) {
            return '';
        }

        return sprintf(' FETCH FIRST %d ROWS ONLY', $limit);
    }
}
