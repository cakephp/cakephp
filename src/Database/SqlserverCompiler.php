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
 * @since         5.x.x
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Cake\Database\Expression\JsonTableExpression;
use Cake\Database\Exception\DatabaseException;

/**
 * SQL Server specific Query Compiler.
 */
class SqlserverCompiler extends QueryCompiler
{
    /**
     * Compiles a JsonTableExpression for SQL Server using OPENJSON.
     *
     * SQL Server syntax:
     * OPENJSON ( jsonExpression [ , path ] )
     * [ WITH ( column_name type [ column_path ] [ AS JSON ] [ , ...n ] ) ]
     *
     * This is typically used with CROSS APPLY or OUTER APPLY.
     * The alias for the resulting table is applied by the APPLY clause.
     *
     * @param \Cake\Database\Expression\JsonTableExpression $expression The expression to compile.
     * @param \Cake\Database\Query $query The query.
     * @param \Cake\Database\ValueBinder $binder The value binder.
     * @return string The SQL for the OPENJSON part, to be used within an APPLY clause.
     */
    protected function _compileJsonTableExpression(
        JsonTableExpression $expression,
        Query $query,
        ValueBinder $binder
    ): string {
        $source = $expression->getSource();
        $sourceSql = ($source instanceof ExpressionInterface) ? $source->sql($binder) : (string)$source;

        $rootPath = $expression->getRootPath();
        // For OPENJSON, if path is '$', it can be omitted or be 'strict $'. Using DEFAULT keyword if path is '$' or empty.
        $pathSql = ($rootPath === '$' || empty($rootPath)) ? 'DEFAULT' : $binder->placeholder($this->_driver->quote($rootPath));

        $withClause = '';
        $columns = $expression->getColumns();

        if (!empty($columns)) {
            $columnDefinitions = [];
            foreach ($columns as $name => $def) {
                $colName = $this->_driver->quoteIdentifier($name);

                if (isset($def['ordinality']) && $def['ordinality'] === true) {
                    // SQL Server's OPENJSON provides the index as 'key' in the schema for arrays.
                    // User should define a column like: 'item_index' => ['type' => 'INT', 'path' => '$.key']
                    // This compiler won't auto-add '$.key', it expects it in the path for ordinality.
                    if (!isset($def['path']) || strtolower($def['path']) !== '$.key') {
                         throw new DatabaseException(
                            "For SQL Server ordinality with OPENJSON, column '{$name}' must have path '$.key'."
                         );
                    }
                    $colType = $this->_mapJsonTableTypeToSqlserver($def['type'] ?? 'INT');
                    $columnDefinitions[] = sprintf('%s %s %s', $colName, $colType, $this->_driver->quote($def['path']));
                    continue;
                }

                if (isset($def['nested'])) {
                     // For NESTED PATH, SQL Server expects the column to be defined AS JSON
                     // and then a subsequent OPENJSON call would process it.
                    $colPath = isset($def['path']) ? $this->_driver->quote($def['path']) : "DEFAULT";
                    // Type for AS JSON is usually NVARCHAR(MAX) implicitly, or user can specify.
                    $colType = $this->_mapJsonTableTypeToSqlserver($def['type'] ?? 'NVARCHAR(MAX)');
                    $columnDefinitions[] = sprintf('%s %s %s AS JSON', $colName, $colType, $colPath);
                    continue;
                }

                if (!isset($def['path'])) {
                    throw new DatabaseException("Column '{$name}' must have a 'path' defined for SQL Server OPENJSON.");
                }

                $colType = $this->_mapJsonTableTypeToSqlserver($def['type'] ?? 'NVARCHAR(255)');
                $colPath = $this->_driver->quote($def['path']); // Path is required in WITH clause items
                $columnDefSql = sprintf('%s %s %s', $colName, $colType, $colPath);

                // AS JSON for extracting sub-JSON fragments
                if (isset($def['asJson']) && $def['asJson'] === true) {
                    $columnDefSql .= ' AS JSON';
                }

                $columnDefinitions[] = $columnDefSql;
            }
            if (!empty($columnDefinitions)) {
                $withClause = sprintf(' WITH (%s)', implode(', ', $columnDefinitions));
            }
        }

        return sprintf("OPENJSON(%s, %s)%s", $sourceSql, $pathSql, $withClause);
    }

    /**
     * In SQL Server, OPENJSON is used with APPLY, so it implicitly handles the "join" structure.
     * The ON clause is not part of the APPLY itself.
     */
    protected function _jsonTableExpressionImplicitlyHandlesJoin(JsonTableExpression $expression, Query $query): bool
    {
        return true;
    }

    /**
     * Overridden _buildFromPart for SQL Server to handle APPLY for JsonTableExpression if it's the primary source.
     * This is less common; OPENJSON is usually in a JOIN/APPLY.
     * If JsonTableExpression is used in FROM, it implies a CROSS APPLY against a dummy source or needs careful construction.
     * For simplicity, we'll assume if it's in FROM, it's a standalone OPENJSON call that needs an alias.
     * A more robust solution might involve checking query structure or context.
     */
    protected function _buildFromPart(array $parts, Query $query, ValueBinder $binder): string
    {
        $normalized = [];
        foreach ($parts as $alias => $table) {
            if ($table instanceof JsonTableExpression) {
                // This usage in FROM is tricky for SQL Server's OPENJSON which prefers APPLY.
                // One way to make it work is to treat it as a subquery.
                // `(SELECT * FROM OPENJSON(...) WITH (...)) AS alias`
                // However, OPENJSON needs a source. If $table->getSource() refers to another table in $parts,
                // this simple loop won't work. This needs a more advanced compiler strategy or restrictions on use.
                // For now, assume it's self-contained or source is a variable/literal.
                $compiledJsonTable = $this->_compileJsonTableExpression($table, $query, $binder);
                $normalized[] = sprintf('(%s) AS %s', $compiledJsonTable, $this->_driver->quoteIdentifier($table->getAlias()));

            } elseif ($table instanceof ExpressionInterface) {
                $partSql = '(' . $table->sql($binder) . ')';
                if (is_string($alias) && !is_numeric($alias)) {
                    $partSql .= ' ' . $this->_driver->quoteIdentifier($alias);
                }
                $normalized[] = $partSql;
            } else {
                if (is_string($alias) && !is_numeric($alias)) {
                    $normalized[] = $this->_driver->quoteIdentifier((string)$table) . ' ' . $this->_driver->quoteIdentifier($alias);
                } else {
                    $normalized[] = $this->_driver->quoteIdentifier((string)$table);
                }
            }
        }
        return ' FROM ' . implode(', ', $normalized);
    }


    /**
     * Overridden _buildJoinPart for SQL Server to handle APPLY for JsonTableExpression.
     */
    protected function _buildJoinPart(array $parts, Query $query, ValueBinder $binder): string
    {
        $joins = '';
        foreach ($parts as $join) {
            if (!isset($join['table'])) {
                throw new DatabaseException(sprintf(
                    'Could not compile join clause for alias `%s`. No table was specified. Use the `table` key to define a table.',
                    $join['alias']
                ));
            }

            $tableExpr = $join['table'];
            $joinType = strtoupper((string)$join['type']); // Ensure type is string

            if ($tableExpr instanceof JsonTableExpression) {
                $applyType = match ($joinType) {
                    'LEFT' => 'OUTER APPLY',
                    'RIGHT' => throw new DatabaseException('RIGHT APPLY with OPENJSON is not directly supported. Consider restructuring or using OUTER APPLY.'),
                    default => 'CROSS APPLY', // INNER maps to CROSS APPLY
                };
                $compiledJsonTable = $this->_compileJsonTableExpression($tableExpr, $query, $binder);
                $joins .= sprintf(
                    ' %s %s AS %s',
                    $applyType,
                    $compiledJsonTable,
                    $this->_driver->quoteIdentifier($join['alias']) // Alias for the result of OPENJSON
                );

                // Conditions for APPLY are generally not placed in an ON clause directly attached to the APPLY.
                // They would be part of the main WHERE clause or used if joining the result of APPLY further.
                if (isset($join['conditions']) && $join['conditions'] instanceof ExpressionInterface) {
                    $conditionSql = $join['conditions']->sql($binder);
                    if (!empty(trim($conditionSql))) {
                        // This implies the APPLYed result is then joined TO something else.
                        $joins .= " ON {$conditionSql}";
                    }
                }
            } else {
                // Standard join compilation
                $tableSql = '';
                $joinTableAlias = $this->_driver->quoteIdentifier($join['alias']);
                if ($tableExpr instanceof ExpressionInterface) {
                    $tableSql = '(' . $tableExpr->sql($binder) . ') ' . $joinTableAlias;
                } else {
                    $tableSql = $this->_driver->quoteIdentifier((string)$tableExpr) . ' ' . $joinTableAlias;
                }
                $joins .= sprintf(' %s JOIN %s', $joinType, $tableSql);

                $condition = '';
                if (isset($join['conditions']) && $join['conditions'] instanceof ExpressionInterface) {
                    $condition = $join['conditions']->sql($binder);
                }
                if ($condition === '') {
                    $joins .= ' ON 1 = 1'; // Should not happen with standard joins typically
                } else {
                    $joins .= " ON {$condition}";
                }
            }
        }
        return $joins;
    }

    /**
     * Maps generic JSON_TABLE column types to SQL Server specific types.
     */
    protected function _mapJsonTableTypeToSqlserver(string $type): string
    {
        $typeUpper = strtoupper($type);
        if (str_starts_with($typeUpper, 'VARCHAR')) {
            return 'NVARCHAR' . preg_replace('/VARCHAR/i', '', $typeUpper);
        }
        if (str_starts_with($typeUpper, 'INT')) {
            return 'INT';
        }
        if (str_starts_with($typeUpper, 'NUMERIC') || str_starts_with($typeUpper, 'DECIMAL')) {
            if (preg_match('/(NUMERIC|DECIMAL)\s*\((\d+)(?:,\s*(\d+))?\)/i', $type, $matches)) {
                return $matches[1] . '(' . $matches[2] . (isset($matches[3]) ? ',' . $matches[3] : '') . ')';
            }
            return 'NUMERIC';
        }
        if ($typeUpper === 'BOOLEAN') {
            return 'BIT';
        }
        if ($typeUpper === 'DATETIME' || str_starts_with($typeUpper, 'TIMESTAMP')) {
            // DATETIME2 is generally preferred in modern SQL Server
            if (preg_match('/DATETIME2(?:\s*\((\d)\))?/i', $type, $matches)) {
                 return 'DATETIME2' . (isset($matches[1]) ? '(' . $matches[1] . ')' : '');
            }
            return 'DATETIME2';
        }
        if ($typeUpper === 'DATE') {
            return 'DATE';
        }
        if ($typeUpper === 'JSON') {
            return 'NVARCHAR(MAX)'; // Used when AS JSON is specified
        }
        // Allow user to pass full type like NVARCHAR(100)
        return (str_contains($typeUpper, '(') || $typeUpper === 'NVARCHAR(MAX)') ? $type : 'NVARCHAR(255)';
    }
}
