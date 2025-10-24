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
use Cake\Database\Expression\FunctionExpression;

/**
 * Responsible for compiling a Query object into its SQL representation
 * for SQL Server
 *
 * @internal
 */
class SqlserverCompiler extends QueryCompiler
{
    /**
     * {@inheritDoc}
     *
     * @var array<string, string>
     */
    protected array $_templates = [
        'delete' => 'DELETE',
        'where' => ' WHERE %s',
        'group' => ' GROUP BY %s',
        'order' => ' %s',
        'offset' => ' OFFSET %s ROWS',
        'epilog' => ' %s',
        'comment' => '/* %s */ ',
    ];

    /**
     * {@inheritDoc}
     *
     * @var array<string>
     */
    protected array $_selectParts = [
        'comment', 'with', 'select', 'from', 'join', 'where', 'group', 'having', 'window', 'order',
        'offset', 'limit', 'union', 'epilog', 'intersect',
    ];

    /**
     * Helper function used to build the string representation of a `WITH` clause,
     * it constructs the CTE definitions list without generating the `RECURSIVE`
     * keyword that is neither required nor valid.
     *
     * @param array<\Cake\Database\Expression\CommonTableExpression> $parts List of CTEs to be transformed to string
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $binder Value binder used to generate parameter placeholder
     * @return string
     */
    protected function _buildWithPart(array $parts, Query $query, ValueBinder $binder): string
    {
        $expressions = [];
        foreach ($parts as $cte) {
            $expressions[] = $cte->sql($binder);
        }

        return sprintf('WITH %s ', implode(', ', $expressions));
    }

    /**
     * Generates the INSERT part of a SQL query
     *
     * To better handle concurrency and low transaction isolation levels,
     * we also include an OUTPUT clause so we can ensure we get the inserted
     * row's data back.
     *
     * @param array $parts The parts to build
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $binder Value binder used to generate parameter placeholder
     * @return string
     */
    protected function _buildInsertPart(array $parts, Query $query, ValueBinder $binder): string
    {
        if (!isset($parts[0])) {
            throw new DatabaseException(
                'Could not compile insert query. No table was specified. ' .
                'Use `into()` to define a table.',
            );
        }
        $table = $parts[0];
        $columns = $this->_stringifyExpressions($parts[1], $binder);
        $modifiers = $this->_buildModifierPart($query->clause('modifier'), $query, $binder);

        return sprintf(
            'INSERT%s INTO %s (%s) OUTPUT INSERTED.*',
            $modifiers,
            $table,
            implode(', ', $columns),
        );
    }

    /**
     * Generates the LIMIT part of a SQL query
     *
     * @param int $limit the limit clause
     * @param \Cake\Database\Query $query The query that is being compiled
     * @return string
     */
    protected function _buildLimitPart(int $limit, Query $query): string
    {
        if ($query->clause('offset') === null) {
            return '';
        }

        return sprintf(' FETCH FIRST %d ROWS ONLY', $limit);
    }

    /**
     * Helper function used to build the string representation of a HAVING clause,
     * it constructs the field list taking care of aliasing and
     * converting expression objects to string.
     *
     * @param array $parts list of fields to be transformed to string
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $binder Value binder used to generate parameter placeholder
     * @return string
     */
    protected function _buildHavingPart(array $parts, Query $query, ValueBinder $binder): string
    {
        $selectParts = $query->clause('select');

        foreach ($selectParts as $selectKey => $selectPart) {
            if (!$selectPart instanceof FunctionExpression) {
                continue;
            }
            foreach ($parts as $k => $p) {
                if (!is_string($p)) {
                    continue;
                }
                preg_match_all(
                    '/\b' . trim($selectKey, '[]') . '\b/i',
                    $p,
                    $matches,
                );

                if (empty($matches[0])) {
                    continue;
                }

                $parts[$k] = preg_replace(
                    ['/\[|\]/', '/\b' . trim($selectKey, '[]') . '\b/i'],
                    ['', $selectPart->sql($binder)],
                    $p,
                );
            }
        }

        return sprintf(' HAVING %s', implode(', ', $parts));
    }
}
