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
 * SQLite specific Query Compiler.
 */
class SqliteCompiler extends QueryCompiler
{
    /**
     * Compiles a JsonTableExpression for SQLite.
     *
     * SQLite uses `json_each()` for iterating over JSON arrays/objects,
     * and `json_extract()` to get specific values.
     *
     * Syntax:
     * ... FROM json_each(json_string, path_to_array) AS alias
     * Then select values using json_extract(alias.value, '$.path_in_element')
     *
     * The alias provided to JsonTableExpression will refer to the output of json_each().
     * json_each() outputs columns like: key, value, type, atom, id, parent, fullkey, path.
     * We are primarily interested in 'value' (for array elements or object values) and 'key' (for ordinality/object keys).
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
        $sourceSql = ($source instanceof ExpressionInterface) ? $source->sql($binder) : (string)$source;

        $rootPath = $expression->getRootPath();
        // If rootPath is '$', json_each will iterate over the top-level keys of an object,
        // or elements of a top-level array. If it's '$.items', it iterates over 'items'.
        // json_each requires the path argument.
        $pathSql = $binder->placeholder($this->_driver->quote($rootPath));

        $alias = $this->_driver->quoteIdentifier($expression->getAlias());

        // The actual column selection (json_extract from alias.value) happens in the SELECT part of the main query.
        // This function provides the table-producing function for the FROM or JOIN clause.
        // e.g., "json_each(orders.data, '$.items') AS order_items_src"
        // A subquery might be needed to present it like a table with predefined columns immediately.

        $columns = $expression->getColumns();
        if (empty($columns)) {
            // json_each itself can be used, but to emulate JSON_TABLE, columns are expected.
            // Return json_each directly, user must select from its 'value' column.
            return sprintf("json_each(%s, %s) AS %s", $sourceSql, $pathSql, $alias);
        }

        // To emulate named columns directly from the JSON_TABLE construct in FROM/JOIN,
        // we need to create a subquery that uses json_extract.
        $selects = [];
        $jsonEachValueColumn = $this->_driver->quoteIdentifier($expression->getAlias()) . '.' . $this->_driver->quoteIdentifier('value');
        $jsonEachKeyColumn = $this->_driver->quoteIdentifier($expression->getAlias()) . '.' . $this->_driver->quoteIdentifier('key');


        foreach ($columns as $colName => $def) {
            $aliasedColName = $this->_driver->quoteIdentifier($colName);

            if (isset($def['ordinality']) && $def['ordinality'] === true) {
                // For arrays, json_each's 'key' is the 0-based index.
                // We add 1 to make it 1-based for ordinality.
                // Type casting to INTEGER is good practice.
                $selects[] = sprintf("CAST(%s AS INTEGER) + 1 AS %s", $jsonEachKeyColumn, $aliasedColName);
                continue;
            }

            if (isset($def['nested'])) {
                // To extract a nested JSON structure, use json_extract and it will return the JSON text.
                // The user would then need another json_each or json_extract on this column.
                $extractPath = isset($def['path']) ? $this->_driver->quote($def['path']) : "'$'"; // Extract the whole sub-object/array
                 $selects[] = sprintf("json_extract(%s, %s) AS %s", $jsonEachValueColumn, $extractPath, $aliasedColName);
                continue;
            }

            if (!isset($def['path'])) {
                throw new DatabaseException("Column '{$colName}' must have a 'path' defined for SQLite json_extract.");
            }
            $extractPath = $this->_driver->quote($def['path']);

            // json_extract returns JSON types (text, number, null). Casting might be needed.
            // SQLite is typeless for columns but casting can influence affinity.
            $sqlValue = sprintf("json_extract(%s, %s)", $jsonEachValueColumn, $extractPath);

            // Basic type hinting for SQLite via CAST, though SQLite is flexible.
            $type = strtoupper($def['type'] ?? 'TEXT');
            if (str_starts_with($type, 'INT')) {
                $sqlValue = sprintf("CAST(%s AS INTEGER)", $sqlValue);
            } elseif (str_starts_with($type, 'NUMERIC') || str_starts_with($type, 'DECIMAL') || $type === 'REAL' || $type === 'FLOAT' || $type === 'DOUBLE') {
                $sqlValue = sprintf("CAST(%s AS REAL)", $sqlValue);
            } elseif ($type === 'BOOLEAN') {
                 // SQLite stores boolean as 0 or 1. json_extract of true/false gives 1/0.
                $sqlValue = sprintf("CAST(%s AS INTEGER)", $sqlValue);
            }
            // TEXT is default, no cast needed unless specified like VARCHAR.
            // DATE/DATETIME are stored as TEXT/NUMERIC, json_extract will give text.

            $selects[] = sprintf("%s AS %s", $sqlValue, $aliasedColName);
        }

        if(empty($selects)){
            // This case should ideally not be hit if columns are defined.
            // If it is, it means only an ordinality column was defined for a non-array source,
            // or some other misconfiguration.
            // Fallback to just json_each if no valid columns could be derived for SELECT.
             return sprintf("json_each(%s, %s) AS %s", $sourceSql, $pathSql, $alias);
        }

        // Create a subquery that projects the desired columns
        // The alias of json_each (e.g., order_items_src) is internal to this subquery.
        // The external alias (e.g., order_items) is for the result of the subquery.
        $internalAlias = $this->_driver->quoteIdentifier($expression->getAlias() . '_core');
        return sprintf(
            "(SELECT %s FROM json_each(%s, %s) AS %s) AS %s",
            implode(', ', $selects),
            $sourceSql,
            $pathSql,
            $internalAlias, // Alias for json_each() inside the subquery
            $alias          // Alias for the subquery itself
        );
    }
}
