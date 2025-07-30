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
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Database\Schema;

use Cake\Core\Configure;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\Schema\SchemaDialect;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\TableSchemaInterface;
use PDO;

/**
 * Schema dialect stub to test backwards compat for dialects
 * that lack the new describe API.
 */
class CompatDialect extends SchemaDialect
{
    /**
     * Convert a column definition to the abstract types.
     *
     * The returned type will be a type that
     * Cake\Database\TypeFactory can handle.
     *
     * @param string $column The column type + length
     * @throws \Cake\Database\Exception\DatabaseException when unable to parse column type
     * @return array<string, mixed> Array of column information.
     */
    protected function _convertColumn(string $column): array
    {
        if ($column === '') {
            return ['type' => TableSchemaInterface::TYPE_TEXT, 'length' => null];
        }

        preg_match('/(unsigned)?\s*([a-z]+)(?:\(([0-9,]+)\))?/i', $column, $matches);
        if (!$matches) {
            throw new DatabaseException(sprintf('Unable to parse column type from `%s`', $column));
        }

        $unsigned = false;
        if (strtolower($matches[1]) === 'unsigned') {
            $unsigned = true;
        }

        $col = strtolower($matches[2]);
        $length = null;
        $precision = null;
        $scale = null;
        if (isset($matches[3])) {
            $length = $matches[3];
            if (str_contains($length, ',')) {
                [$length, $precision] = explode(',', $length);
            }
            $length = (int)$length;
            $precision = (int)$precision;
        }

        $type = $this->_applyTypeSpecificColumnConversion(
            $col,
            compact('length', 'precision', 'scale'),
        );
        if ($type !== null) {
            return $type;
        }

        if ($col === 'bigint') {
            return ['type' => TableSchemaInterface::TYPE_BIGINTEGER, 'length' => $length, 'unsigned' => $unsigned];
        }
        if ($col === 'smallint') {
            return ['type' => TableSchemaInterface::TYPE_SMALLINTEGER, 'length' => $length, 'unsigned' => $unsigned];
        }
        if ($col === 'tinyint') {
            return ['type' => TableSchemaInterface::TYPE_TINYINTEGER, 'length' => $length, 'unsigned' => $unsigned];
        }
        if (str_contains($col, 'int') && $col !== 'point') {
            return ['type' => TableSchemaInterface::TYPE_INTEGER, 'length' => $length, 'unsigned' => $unsigned];
        }
        if (str_contains($col, 'decimal')) {
            return [
                'type' => TableSchemaInterface::TYPE_DECIMAL,
                'length' => $length,
                'precision' => $precision,
                'unsigned' => $unsigned,
            ];
        }
        if (in_array($col, ['float', 'real', 'double'])) {
            return [
                'type' => TableSchemaInterface::TYPE_FLOAT,
                'length' => $length,
                'precision' => $precision,
                'unsigned' => $unsigned,
            ];
        }

        if (str_contains($col, 'boolean')) {
            return ['type' => TableSchemaInterface::TYPE_BOOLEAN, 'length' => null];
        }

        if (($col === 'binary' && $length === 16) || strtolower($column) === 'uuid_blob') {
            return ['type' => TableSchemaInterface::TYPE_BINARY_UUID, 'length' => null];
        }
        if (($col === 'char' && $length === 36) || $col === 'uuid') {
            return ['type' => TableSchemaInterface::TYPE_UUID, 'length' => null];
        }
        if ($col === 'char') {
            return ['type' => TableSchemaInterface::TYPE_CHAR, 'length' => $length];
        }
        if (str_contains($col, 'char')) {
            return ['type' => TableSchemaInterface::TYPE_STRING, 'length' => $length];
        }

        if (in_array($col, ['blob', 'clob', 'binary', 'varbinary'])) {
            return ['type' => TableSchemaInterface::TYPE_BINARY, 'length' => $length];
        }

        $datetimeTypes = [
            'date',
            'time',
            'timestamp',
            'timestampfractional',
            'timestamptimezone',
            'datetime',
            'datetimefractional',
        ];
        if (in_array($col, $datetimeTypes)) {
            return ['type' => $col, 'length' => null];
        }

        if (Configure::read('ORM.mapJsonTypeForSqlite') === true) {
            if (str_contains($col, TableSchemaInterface::TYPE_JSON) && !str_contains($col, 'jsonb')) {
                return ['type' => TableSchemaInterface::TYPE_JSON, 'length' => null];
            }
        }

        if (in_array($col, TableSchemaInterface::GEOSPATIAL_TYPES)) {
            // TODO how can srid be preserved? It doesn't come back
            // in the output of show full columns from ...
            return [
                'type' => $col,
                'length' => null,
            ];
        }

        return ['type' => TableSchemaInterface::TYPE_TEXT, 'length' => null];
    }

    /**
     * Generate the SQL to list the tables and views.
     *
     * @param array<string, mixed> $config The connection configuration to use for
     *    getting tables from.
     * @return array An array of (sql, params) to execute.
     */
    public function listTablesSql(array $config): array
    {
        return [
            'SELECT name FROM sqlite_master ' .
            'WHERE (type="table" OR type="view") ' .
            'AND name != "sqlite_sequence" ORDER BY name',
            [],
        ];
    }

    /**
     * Generate the SQL to list the tables, excluding all views.
     *
     * @param array<string, mixed> $config The connection configuration to use for
     *    getting tables from.
     * @return array<mixed> An array of (sql, params) to execute.
     */
    public function listTablesWithoutViewsSql(array $config): array
    {
        return [
            'SELECT name FROM sqlite_master WHERE type="table" ' .
            'AND name != "sqlite_sequence" ORDER BY name',
            [],
        ];
    }

    /**
     * @inheritDoc
     */
    public function describeColumnSql(string $tableName, array $config): array
    {
        $sql = $this->describeColumnQuery($tableName);

        return [$sql, []];
    }

    /**
     * @inheritDoc
     */
    public function convertColumnDescription(TableSchema $schema, array $row): void
    {
        $field = $this->_convertColumn($row['type']);
        $field += [
            'null' => !$row['notnull'],
            'default' => $this->_defaultValue($row['dflt_value']),
        ];
        $primary = $schema->getConstraint('primary');

        if ($row['pk'] && empty($primary)) {
            $field['null'] = false;
            $field['autoIncrement'] = true;
        }

        // SQLite does not support autoincrement on composite keys.
        if ($row['pk'] && !empty($primary)) {
            $existingColumn = $primary['columns'][0];
            $schema->addColumn($existingColumn, ['autoIncrement' => null] + $schema->getColumn($existingColumn));
        }

        $schema->addColumn($row['name'], $field);
        if ($row['pk']) {
            $constraint = (array)$schema->getConstraint('primary') + [
                'type' => TableSchema::CONSTRAINT_PRIMARY,
                'columns' => [],
            ];
            $constraint['columns'] = array_merge($constraint['columns'], [$row['name']]);
            $schema->addConstraint('primary', $constraint);
        }
    }

    /**
     * Helper method for creating SQL to describe columns in a table.
     *
     * @param string $tableName The table to describe.
     * @return string SQL to reflect columns
     */
    private function describeColumnQuery(string $tableName): string
    {
        $pragma = 'table_xinfo';
        if (version_compare($this->_driver->version(), '3.26.0', '<')) {
            $pragma = 'table_info';
        }

        return sprintf(
            'PRAGMA %s(%s)',
            $pragma,
            $this->_driver->quoteIdentifier($tableName),
        );
    }

    /**
     * Manipulate the default value.
     *
     * Sqlite includes quotes and bared NULLs in default values.
     * We need to remove those.
     *
     * @param string|int|null $default The default value.
     * @return string|int|null
     */
    protected function _defaultValue(string|int|null $default): string|int|null
    {
        if ($default === 'NULL' || $default === null) {
            return null;
        }

        // Remove quotes
        if (is_string($default) && preg_match("/^'(.*)'$/", $default, $matches)) {
            return str_replace("''", "'", $matches[1]);
        }

        return $default;
    }

    /**
     * @inheritDoc
     */
    public function describeIndexSql(string $tableName, array $config): array
    {
        $sql = $this->describeIndexQuery($tableName);

        return [$sql, []];
    }

    /**
     * Generates a regular expression to match identifiers that may or
     * may not be quoted with any of the supported quotes.
     *
     * @param string $identifier The identifier to match.
     * @return string
     */
    protected function possiblyQuotedIdentifierRegex(string $identifier): string
    {
        $identifiers = [];
        $identifier = preg_quote($identifier, '/');

        $hasTick = str_contains($identifier, '`');
        $hasDoubleQuote = str_contains($identifier, '"');
        $hasSingleQuote = str_contains($identifier, "'");

        $identifiers[] = '\[' . $identifier . '\]';
        $identifiers[] = '`' . ($hasTick ? str_replace('`', '``', $identifier) : $identifier) . '`';
        $identifiers[] = '"' . ($hasDoubleQuote ? str_replace('"', '""', $identifier) : $identifier) . '"';
        $identifiers[] = "'" . ($hasSingleQuote ? str_replace("'", "''", $identifier) : $identifier) . "'";

        if (!$hasTick && !$hasDoubleQuote && !$hasSingleQuote) {
            $identifiers[] = $identifier;
        }

        return implode('|', $identifiers);
    }

    /**
     * Removes possible escape characters and surrounding quotes from
     * identifiers.
     *
     * @param string $value The identifier to normalize.
     * @return string
     */
    protected function normalizePossiblyQuotedIdentifier(string $value): string
    {
        $value = trim($value);

        if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
            return mb_substr($value, 1, -1);
        }

        foreach (['`', "'", '"'] as $quote) {
            if (str_starts_with($value, $quote) && str_ends_with($value, $quote)) {
                $value = str_replace($quote . $quote, $quote, $value);

                return mb_substr($value, 1, -1);
            }
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function convertIndexDescription(TableSchema $schema, array $row): void
    {
        // Skip auto-indexes created for non-ROWID primary keys.
        if ($row['origin'] === 'pk') {
            return;
        }

        $sql = sprintf(
            'PRAGMA index_info(%s)',
            $this->_driver->quoteIdentifier($row['name']),
        );
        $statement = $this->_driver->execute($sql);
        $columns = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[] = $column['name'];
        }
        if ($row['unique']) {
            if ($row['origin'] === 'u') {
                $name = $this->extractIndexName($schema->name(), $columns);
                if ($name !== null) {
                    $row['name'] = $name;
                }
            }

            $schema->addConstraint($row['name'], [
                'type' => TableSchema::CONSTRAINT_UNIQUE,
                'columns' => $columns,
            ]);
        } else {
            $schema->addIndex($row['name'], [
                'type' => TableSchema::INDEX_INDEX,
                'columns' => $columns,
            ]);
        }
    }

    /**
     * Helper method for creating SQL to reflect indexes in a table.
     *
     * @param string $tableName The table to get indexes from.
     * @return string SQL to reflect indexes
     */
    private function describeIndexQuery(string $tableName): string
    {
        return sprintf(
            'PRAGMA index_list(%s)',
            $this->_driver->quoteIdentifier($tableName),
        );
    }

    /**
     * Try to extract the original unique index name from table sql.
     *
     * @param string $tableName The table name.
     * @param array $columns The columns in the index.
     * @return string|null The name of the unique index if it could be inferred.
     */
    private function extractIndexName(string $tableName, array $columns): ?string
    {
        $sql = 'SELECT sql FROM sqlite_master WHERE type = "table" AND tbl_name = ?';
        $statement = $this->_driver->execute($sql, [$tableName]);
        $statement->execute();

        $tableRow = $statement->fetchAssoc();
        $tableSql = $tableRow['sql'] ??= null;
        if (!$tableSql) {
            return null;
        }

        $columnsPattern = implode(
            '\s*,\s*',
            array_map(
                fn($column) => '(?:' . $this->possiblyQuotedIdentifierRegex($column) . ')',
                $columns,
            ),
        );

        $regex = "/CONSTRAINT\s*(['\"`\[ ].+?['\"`\] ])\s*UNIQUE\s*\(\s*(?:{$columnsPattern})\s*\)/i";
        if (preg_match($regex, $tableSql, $matches)) {
            return $this->normalizePossiblyQuotedIdentifier($matches[1]);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function describeForeignKeySql(string $tableName, array $config): array
    {
        $sql = sprintf(
            'SELECT id FROM pragma_foreign_key_list(%s) GROUP BY id',
            $this->_driver->quoteIdentifier($tableName),
        );

        return [$sql, []];
    }

    /**
     * @inheritDoc
     */
    public function convertForeignKeyDescription(TableSchema $schema, array $row): void
    {
        $sql = sprintf(
            'SELECT * FROM pragma_foreign_key_list(%s) WHERE id = %d ORDER BY seq',
            $this->_driver->quoteIdentifier($schema->name()),
            $row['id'],
        );
        $statement = $this->_driver->prepare($sql);
        $statement->execute();

        $data = [
            'type' => TableSchema::CONSTRAINT_FOREIGN,
            'columns' => [],
            'references' => [],
        ];

        $foreignKey = null;
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $foreignKey) {
            $data['columns'][] = $foreignKey['from'];
            $data['references'][] = $foreignKey['to'];
        }

        if (count($data['references']) === 1) {
            $data['references'] = [$foreignKey['table'], $data['references'][0]];
        } else {
            $data['references'] = [$foreignKey['table'], $data['references']];
        }
        $data['update'] = $this->_convertOnClause($foreignKey['on_update'] ?? '');
        $data['delete'] = $this->_convertOnClause($foreignKey['on_delete'] ?? '');

        $name = implode('_', $data['columns']) . '_' . $row['id'] . '_fk';

        $schema->addConstraint($name, $data);
    }

    /**
     * {@inheritDoc}
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table instance the column is in.
     * @param string $name The name of the column.
     * @return string SQL fragment.
     * @throws \Cake\Database\Exception\DatabaseException when the column type is unknown
     */
    public function columnSql(TableSchema $schema, string $name): string
    {
        return 'not implemented';
    }

    /**
     * {@inheritDoc}
     *
     * Note integer primary keys will return ''. This is intentional as Sqlite requires
     * that integer primary keys be defined in the column definition.
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table instance the column is in.
     * @param string $name The name of the column.
     * @return string SQL fragment.
     */
    public function constraintSql(TableSchema $schema, string $name): string
    {
        return 'not implemented';
    }

    /**
     * {@inheritDoc}
     *
     * SQLite can not properly handle adding a constraint to an existing table.
     * This method is no-op
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table instance the foreign key constraints are.
     * @return array SQL fragment.
     */
    public function addConstraintSql(TableSchema $schema): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     *
     * SQLite can not properly handle dropping a constraint to an existing table.
     * This method is no-op
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table instance the foreign key constraints are.
     * @return array SQL fragment.
     */
    public function dropConstraintSql(TableSchema $schema): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function indexSql(TableSchema $schema, string $name): string
    {
        return 'not implemented';
    }

    /**
     * @inheritDoc
     */
    public function createTableSql(TableSchema $schema, array $columns, array $constraints, array $indexes): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function truncateTableSql(TableSchema $schema): array
    {
        return [];
    }

    /**
     * Returns whether there is any table in this connection to SQLite containing
     * sequences
     *
     * @return bool
     */
    public function hasSequences(): bool
    {
        return true;
    }
}
