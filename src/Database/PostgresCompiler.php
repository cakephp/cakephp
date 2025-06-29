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
 * Postgres specific Query Compiler.
 */
class PostgresCompiler extends QueryCompiler
{
    /**
     * Compiles a JsonTableExpression for PostgreSQL.
     *
     * PostgreSQL uses `jsonb_to_recordset()` for objects within an array,
     * or `jsonb_populate_record()` for a single object.
     * For unnesting arrays to rows, `jsonb_array_elements()` or `jsonb_array_elements_text()`
     * combined with LATERAL joins are common.
     *
     * The core idea is to transform the JSON structure into a rowset.
     *
     * Example of what we're trying to achieve for a structure like MySQL's JSON_TABLE:
     *
     * If source is `orders.order_data` and rootPath is `'$'`, and columns are defined for orderId, orderDate:
     * `... FROM orders o, LATERAL jsonb_populate_record(null::MyOrderRecordType, o.order_data) AS ot ...`
     * (Requires a predefined composite type `MyOrderRecordType` or using `jsonb_to_record` with explicit casting)
     *
     * A more general approach for a single object without predefined types:
     * `... FROM orders o, LATERAL (
     *     SELECT
     *         (o.order_data->>'orderId')::VARCHAR(50) AS orderId,
     *         (o.order_data->>'orderDate')::TIMESTAMP AS orderDate
     * ) AS ot ...`
     *
     * If rootPath is `'$.items[*]'` (an array to be unnested):
     * `... FROM orders o, LATERAL jsonb_array_elements(o.order_data->'items') WITH ORDINALITY AS items_src(item_json, item_ordinality)
     *     LEFT JOIN LATERAL (
     *         SELECT
     *             (items_src.item_json->>'itemId')::VARCHAR(50) as itemId,
     *             (items_src.item_json->>'quantity')::INT as quantity
     *     ) AS items ON true ...`
     *
     * This is quite complex to generalize perfectly like MySQL's JSON_TABLE.
     * This implementation will take a simplified approach, focusing on common use cases.
     * It will primarily use `jsonb_array_elements` for array unnesting and direct extraction for single objects.
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
        $alias = $this->_driver->quoteIdentifier($expression->getAlias());
        $rootPath = $expression->getRootPath();

        // Determine if the root path targets an array for unnesting
        $isUnnestingArray = str_ends_with($rootPath, '[*]');
        $pathToUnnest = $sourceSql;

        if ($rootPath !== '$' && $rootPath !== '$[*]') {
            $pathParts = explode('.', trim($rootPath, '$.[]*'));
            foreach ($pathParts as $part) {
                if (!empty($part)) {
                     // Use #>> for text extraction if it's the final part for a non-array,
                     // otherwise use #> for object/array navigation.
                     // This logic gets tricky with json_array_elements later.
                     // For now, always navigate to the array/object.
                    $pathToUnnest .= '->' . $this->_driver->quote($part);
                }
            }
        } elseif ($rootPath === '$[*]') { // Root is an array
            // $pathToUnnest remains $sourceSql
        } elseif ($rootPath === '$') { // Root is an object, not unnesting an array here
            $isUnnestingArray = false;
        }


        if ($isUnnestingArray) {
            // Unnesting an array (e.g., '$.items[*]')
            $columns = $expression->getColumns();
            $selects = [];
            $ordinalityColName = null;

            foreach ($columns as $colName => $def) {
                if (isset($def['ordinality']) && $def['ordinality'] === true) {
                    $ordinalityColName = $this->_driver->quoteIdentifier($colName);
                    continue; // Handled by WITH ORDINALITY
                }
                if (isset($def['nested'])) {
                     // Proper NESTED PATH for pg requires further LATERAL joins, complex.
                     // For now, we'd treat it as a jsonb column to be extracted from.
                    $selects[] = sprintf(
                        "(%s->%s)::JSONB AS %s",
                        $this->_driver->quoteIdentifier('element_data'), // from jsonb_array_elements
                        $this->_driver->quote($def['path']),
                        $this->_driver->quoteIdentifier($colName)
                    );
                    continue;
                }

                $pgType = $this->_mapJsonTableTypeToPostgres($def['type'] ?? 'TEXT');
                $colPath = $this->_preparePostgresJsonPath($def['path']);

                // Use ->> for text extraction, then cast.
                $selects[] = sprintf(
                    "(%s->>%s)::%s AS %s",
                    $this->_driver->quoteIdentifier('element_data'), // from jsonb_array_elements
                    $colPath,
                    $pgType,
                    $this->_driver->quoteIdentifier($colName)
                );
            }

            $ordinalitySql = '';
            $finalOrdinalitySelect = '';
            if ($ordinalityColName) {
                $ordinalitySql = ' WITH ORDINALITY';
                // Alias for ordinality column from jsonb_array_elements is fixed to 'ordinality'
                // We then re-alias it to the user's desired name.
                $finalOrdinalitySelect = ', ' . $this->_driver->quoteIdentifier('_jt_ordinality_') . ' AS ' . $ordinalityColName;
            }

            // jsonb_array_elements returns a single jsonb column named 'value' by default.
            // We alias it to 'element_data' for clarity inside the sub-query.
            // The sub-query (LATERAL) is needed to apply individual column extractions.
            return sprintf(
                "LATERAL jsonb_array_elements(%s) %s AS %s (%s, %s) LEFT JOIN LATERAL (SELECT %s %s) %s ON true",
                $pathToUnnest, // This should point to the JSONB array
                $ordinalitySql,
                $this->_driver->quoteIdentifier($expression->getAlias() . '_src'), // temp alias for the source of elements
                $this->_driver->quoteIdentifier('element_data'), // column name for each element
                $ordinalityColName ? $this->_driver->quoteIdentifier('_jt_ordinality_') : $this->_driver->quoteIdentifier('_jt_dummy_ord_'), // dummy if no ord
                implode(', ', $selects),
                $finalOrdinalitySelect,
                $alias // final alias for the derived table
            );

        } else {
            // Dealing with a single JSON object (rootPath was '$' or a path to an object)
            // We use a LATERAL subquery to extract fields.
            $columns = $expression->getColumns();
            $selects = [];
            foreach ($columns as $colName => $def) {
                 if (isset($def['ordinality']) && $def['ordinality'] === true) {
                    // Ordinality makes less sense for a single object. Default to 1 or NULL.
                    $selects[] = '1::INTEGER AS ' . $this->_driver->quoteIdentifier($colName);
                    continue;
                }
                if (isset($def['nested'])) {
                    $selects[] = sprintf(
                        "(%s->%s)::JSONB AS %s",
                        $sourceSql, // Source of the main object
                        $this->_driver->quote($def['path']),
                        $this->_driver->quoteIdentifier($colName)
                    );
                    continue;
                }

                $pgType = $this->_mapJsonTableTypeToPostgres($def['type'] ?? 'TEXT');
                $colPath = $this->_preparePostgresJsonPath($def['path']);
                $selects[] = sprintf(
                    "(%s->>%s)::%s AS %s",
                    $sourceSql, // Source of the main object
                    $colPath,
                    $pgType,
                    $this->_driver->quoteIdentifier($colName)
                );
            }
            if (empty($selects)) {
                 // An empty select list in a subquery is invalid.
                 // If only an ordinality column was requested for a single object, this could happen.
                 // Or if the JSON object itself is meant to be the "table".
                 // This case might need a different structure or represent an invalid use.
                 // For now, ensure there's at least one selectable expression or make it valid.
                 // A common fallback if nothing is selected might be to select the source JSON itself.
                 // However, JSON_TABLE implies creating columns.
                 throw new \InvalidArgumentException('No columns defined for JSON object extraction.');
            }
            return sprintf("LATERAL (SELECT %s) AS %s", implode(', ', $selects), $alias);
        }
    }

    /**
     * Prepares a JSON path for PostgreSQL from a JSONPath-like string.
     * PostgreSQL's `->>` operator expects text keys for objects or integer indices for arrays.
     * This is a simplified conversion.
     * e.g., '$.customer.name' -> 'customer' then 'name' (used in chained ->> calls)
     *       '$.items[0].id' -> 'items' then 0 then 'id'
     * For `->>` the final part of the path is used.
     *
     * @param string $jsonPath The input JSONPath string.
     * @return string The prepared path segment for PostgreSQL.
     */
    protected function _preparePostgresJsonPath(string $jsonPath): string
    {
        // Remove '$.' prefix
        $path = preg_replace('/^\$\.?/', '', $jsonPath);
        // For ->> we usually want the last segment of the path.
        // More complex paths like arrays are handled by jsonb_array_elements.
        $segments = explode('.', $path);
        $lastSegment = end($segments);
        // Remove array indexing like [0] for direct text extraction, as that's a navigation step.
        $lastSegment = preg_replace('/\[\d+\]$/', '', $lastSegment);

        return $this->_driver->quote($lastSegment); // Quote the key name
    }


    /**
     * Maps generic JSON_TABLE column types to PostgreSQL specific types.
     *
     * @param string $type Generic type.
     * @return string PostgreSQL type.
     */
    protected function _mapJsonTableTypeToPostgres(string $type): string
    {
        $typeUpper = strtoupper($type);
        if (str_starts_with($typeUpper, 'VARCHAR')) {
            return 'TEXT';
        }
        if (str_starts_with($typeUpper, 'INT')) {
            return 'INTEGER';
        }
        if (str_starts_with($typeUpper, 'NUMERIC') || str_starts_with($typeUpper, 'DECIMAL')) {
            // Extract precision and scale if present, e.g., DECIMAL(10,2) -> NUMERIC(10,2)
            if (preg_match('/(?:NUMERIC|DECIMAL)\s*\((\d+)(?:,\s*(\d+))?\)/i', $type, $matches)) {
                $precision = $matches[1];
                $scale = $matches[2] ?? null;
                return 'NUMERIC(' . $precision . ($scale !== null ? ',' . $scale : '') . ')';
            }
            return 'NUMERIC';
        }
        if ($typeUpper === 'BOOLEAN') {
            return 'BOOLEAN';
        }
        if ($typeUpper === 'DATETIME' || str_starts_with($typeUpper, 'TIMESTAMP')) {
            return 'TIMESTAMP WITHOUT TIME ZONE';
        }
        if ($typeUpper === 'DATE') {
            return 'DATE';
        }
        if ($typeUpper === 'JSON' || $typeUpper === 'JSONB') {
            return 'JSONB';
        }
        // Default to TEXT if no specific mapping or if it's a complex type string
        return $type; // Allow user to specify full PG type like "VARCHAR(20)" directly
    }
}
