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

/**
 * MySQL specific Query Compiler.
 */
class MysqlCompiler extends QueryCompiler
{
    /**
     * Compiles a JsonTableExpression for MySQL.
     *
     * MySQL syntax:
     * JSON_TABLE(
     *   expr,
     *   path COLUMNS (column_list)
     * ) [AS] alias
     *
     * column_list:
     *   name type PATH string_path [on_empty] [on_error]
     *   | name type FOR ORDINALITY
     *   | NESTED [PATH] path COLUMNS (column_list)
     *
     * @param \Cake\Database\Expression\JsonTableExpression $expression The expression to compile.
     * @param \Cake\Database\Query $query The query.
     * @param \Cake\Database\ValueBinder $binder The value binder.
     * @return string
     */
    protected function _compileJsonTableExpression(
        JsonTableExpression $expression,
        Query $query,
        ValueBinder $binder
    ): string {
        $source = $expression->getSource();
        if ($source instanceof ExpressionInterface) {
            $sourceSql = $source->sql($binder);
        } else {
            // Assume $source is a string like 'table.column'
            // It might need quoting if it's not a simple identifier or already quoted.
            // For now, let's assume it's directly usable or pre-quoted if complex.
            // Alternatively, treat as identifier: $sourceSql = $this->_driver->quoteIdentifier($source);
            $sourceSql = $source;
        }

        $rootPath = $expression->getRootPath();
        $columnsSql = $this->_compileJsonTableColumns($expression->getColumns(), $query, $binder);

        $alias = $this->_driver->quoteIdentifier($expression->getAlias());

        return sprintf(
            "JSON_TABLE(%s, %s COLUMNS (%s)) AS %s",
            $sourceSql,
            $binder->placeholder($this->_driver->quote($rootPath)), // Path should be a string literal
            $columnsSql,
            $alias
        );
    }

    /**
     * Compiles the COLUMNS part of a JSON_TABLE expression for MySQL.
     *
     * @param array $columns The column definitions.
     * @param \Cake\Database\Query $query The query.
     * @param \Cake\Database\ValueBinder $binder The value binder.
     * @return string
     */
    protected function _compileJsonTableColumns(array $columns, Query $query, ValueBinder $binder): string
    {
        $compiledColumns = [];
        foreach ($columns as $name => $def) {
            $columnName = $this->_driver->quoteIdentifier($name);

            if (isset($def['ordinality']) && $def['ordinality'] === true) {
                $compiledColumns[] = sprintf('%s FOR ORDINALITY', $columnName);
                continue;
            }

            if (isset($def['nested'])) {
                $nestedPath = $def['nestedPath'] ?? '$.*'; // Default path for nested if not specified
                if ($def['nested'] instanceof JsonTableExpression) {
                    // This is a simplification. MySQL's NESTED PATH is part of the same JSON_TABLE,
                    // not a completely new JSON_TABLE call. We need to adapt.
                    // For now, let's assume $def['nested'] is an array of columns for the NESTED PATH.
                    throw new \LogicException('Direct JsonTableExpression in "nested" for MySQL is not yet supported in this simplified compiler.');
                } elseif (is_array($def['nested'])) {
                    $nestedColumnsSql = $this->_compileJsonTableColumns($def['nested'], $query, $binder);
                    $compiledColumns[] = sprintf(
                        'NESTED PATH %s COLUMNS (%s)',
                        $binder->placeholder($this->_driver->quote($nestedPath)),
                        $nestedColumnsSql
                    );
                }
                continue;
            }

            $type = $def['type'] ?? 'VARCHAR(255)'; // Default type if not specified
            $path = $def['path'];
            $pathSql = $binder->placeholder($this->_driver->quote($path));

            $columnSql = sprintf('%s %s PATH %s', $columnName, $type, $pathSql);

            if (isset($def['onEmpty'])) {
                $columnSql .= ' ' . $def['onEmpty']; // e.g., 'NULL ON EMPTY'
            } elseif (isset($def['default'])) {
                 // MySQL uses 'DEFAULT JSON_QUOTE(?) ON EMPTY' or 'DEFAULT ? ON EMPTY'
                 // For simplicity, we'll require onEmpty to be set if default is used this way.
                 // Or, the type system should handle default values if the path doesn't exist.
                 // This part might need refinement based on how defaults are best handled.
            }

            if (isset($def['onError'])) {
                $columnSql .= ' ' . $def['onError']; // e.g., 'NULL ON ERROR'
            }

            $compiledColumns[] = $columnSql;
        }

        return implode(', ', $compiledColumns);
    }
}
