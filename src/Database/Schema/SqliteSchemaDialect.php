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
namespace Cake\Database\Schema;

use Cake\Core\Configure;
use Cake\Database\Exception\DatabaseException;

/**
 * Schema management/reflection features for Sqlite
 *
 * @internal
 */
class SqliteSchemaDialect extends SchemaDialect
{
    /**
     * Whether there is any table in this connection to SQLite containing sequences.
     *
     * @var bool
     */
    protected bool $_hasSequences;

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
            compact('length', 'precision', 'scale')
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
        $pragma = 'table_xinfo';
        if (version_compare($this->_driver->version(), '3.26.0', '<')) {
            $pragma = 'table_info';
        }

        $sql = sprintf(
            'PRAGMA %s(%s)',
            $pragma,
            $this->_driver->quoteIdentifier($tableName)
        );

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
            /** @psalm-suppress PossiblyNullOperand */
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
        $sql = sprintf(
            'PRAGMA index_list(%s)',
            $this->_driver->quoteIdentifier($tableName)
        );

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
     * {@inheritDoc}
     *
     * Since SQLite does not have a way to get metadata about all indexes at once,
     * additional queries are done here. Sqlite constraint names are not
     * stable, and the names for constraints will not match those used to create
     * the table. This is a limitation in Sqlite's metadata features.
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table object to append
     *    an index or constraint to.
     * @param array $row The row data from `describeIndexSql`.
     * @return void
     */
    public function convertIndexDescription(TableSchema $schema, array $row): void
    {
        // Skip auto-indexes created for non-ROWID primary keys.
        if ($row['origin'] === 'pk') {
            return;
        }

        $sql = sprintf(
            'PRAGMA index_info(%s)',
            $this->_driver->quoteIdentifier($row['name'])
        );
        $statement = $this->_driver->prepare($sql);
        $statement->execute();
        $columns = [];
        foreach ($statement->fetchAll('assoc') as $column) {
            $columns[] = $column['name'];
        }
        if ($row['unique']) {
            if ($row['origin'] === 'u') {
                // Try to obtain the actual constraint name for indexes that are
                // created automatically for unique constraints.

                $sql = sprintf(
                    'SELECT sql FROM sqlite_master WHERE type = "table" AND tbl_name = %s',
                    $this->_driver->quoteIdentifier($schema->name())
                );
                $statement = $this->_driver->prepare($sql);
                $statement->execute();

                $tableRow = $statement->fetchAssoc();
                $tableSql = $tableRow['sql'] ??= null;

                if ($tableSql) {
                    $columnsPattern = implode(
                        '\s*,\s*',
                        array_map(
                            fn ($column) => '(?:' . $this->possiblyQuotedIdentifierRegex($column) . ')',
                            $columns
                        )
                    );

                    $regex = "/CONSTRAINT\s*(['\"`\[ ].+?['\"`\] ])\s*UNIQUE\s*\(\s*(?:{$columnsPattern})\s*\)/i";
                    if (preg_match($regex, $tableSql, $matches)) {
                        $row['name'] = $this->normalizePossiblyQuotedIdentifier($matches[1]);
                    }
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
     * @inheritDoc
     */
    public function describeForeignKeySql(string $tableName, array $config): array
    {
        $sql = sprintf(
            'SELECT id FROM pragma_foreign_key_list(%s) GROUP BY id',
            $this->_driver->quoteIdentifier($tableName)
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
            $row['id']
        );
        $statement = $this->_driver->prepare($sql);
        $statement->execute();

        $data = [
            'type' => TableSchema::CONSTRAINT_FOREIGN,
            'columns' => [],
            'references' => [],
        ];

        $foreignKey = null;
        foreach ($statement->fetchAll('assoc') as $foreignKey) {
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
        $data = $schema->getColumn($name);
        assert($data !== null);

        $sql = $this->_getTypeSpecificColumnSql($data['type'], $schema, $name);
        if ($sql !== null) {
            return $sql;
        }

        $typeMap = [
            TableSchemaInterface::TYPE_BINARY_UUID => ' BINARY(16)',
            TableSchemaInterface::TYPE_UUID => ' CHAR(36)',
            TableSchemaInterface::TYPE_CHAR => ' CHAR',
            TableSchemaInterface::TYPE_TINYINTEGER => ' TINYINT',
            TableSchemaInterface::TYPE_SMALLINTEGER => ' SMALLINT',
            TableSchemaInterface::TYPE_INTEGER => ' INTEGER',
            TableSchemaInterface::TYPE_BIGINTEGER => ' BIGINT',
            TableSchemaInterface::TYPE_BOOLEAN => ' BOOLEAN',
            TableSchemaInterface::TYPE_FLOAT => ' FLOAT',
            TableSchemaInterface::TYPE_DECIMAL => ' DECIMAL',
            TableSchemaInterface::TYPE_DATE => ' DATE',
            TableSchemaInterface::TYPE_TIME => ' TIME',
            TableSchemaInterface::TYPE_DATETIME => ' DATETIME',
            TableSchemaInterface::TYPE_DATETIME_FRACTIONAL => ' DATETIMEFRACTIONAL',
            TableSchemaInterface::TYPE_TIMESTAMP => ' TIMESTAMP',
            TableSchemaInterface::TYPE_TIMESTAMP_FRACTIONAL => ' TIMESTAMPFRACTIONAL',
            TableSchemaInterface::TYPE_TIMESTAMP_TIMEZONE => ' TIMESTAMPTIMEZONE',
            TableSchemaInterface::TYPE_JSON => ' TEXT',
            TableSchemaInterface::TYPE_GEOMETRY => ' GEOMETRY_TEXT',
            TableSchemaInterface::TYPE_POINT => ' POINT_TEXT',
            TableSchemaInterface::TYPE_LINESTRING => ' LINESTRING_TEXT',
            TableSchemaInterface::TYPE_POLYGON => ' POLYGON_TEXT',
        ];

        $out = $this->_driver->quoteIdentifier($name);
        $hasUnsigned = [
            TableSchemaInterface::TYPE_TINYINTEGER,
            TableSchemaInterface::TYPE_SMALLINTEGER,
            TableSchemaInterface::TYPE_INTEGER,
            TableSchemaInterface::TYPE_BIGINTEGER,
            TableSchemaInterface::TYPE_FLOAT,
            TableSchemaInterface::TYPE_DECIMAL,
        ];

        if (
            in_array($data['type'], $hasUnsigned, true) &&
            isset($data['unsigned']) &&
            $data['unsigned'] === true &&
            ($data['type'] !== TableSchemaInterface::TYPE_INTEGER || $schema->getPrimaryKey() !== [$name])
        ) {
            $out .= ' UNSIGNED';
        }

        if (isset($typeMap[$data['type']])) {
            $out .= $typeMap[$data['type']];
        }

        if ($data['type'] === TableSchemaInterface::TYPE_TEXT && $data['length'] !== TableSchema::LENGTH_TINY) {
            $out .= ' TEXT';
        }

        if ($data['type'] === TableSchemaInterface::TYPE_CHAR) {
            $out .= '(' . $data['length'] . ')';
        }

        if (
            $data['type'] === TableSchemaInterface::TYPE_STRING ||
            (
                $data['type'] === TableSchemaInterface::TYPE_TEXT &&
                $data['length'] === TableSchema::LENGTH_TINY
            )
        ) {
            $out .= ' VARCHAR';

            if (isset($data['length'])) {
                $out .= '(' . $data['length'] . ')';
            }
        }

        if ($data['type'] === TableSchemaInterface::TYPE_BINARY) {
            if (isset($data['length'])) {
                $out .= ' BLOB(' . $data['length'] . ')';
            } else {
                $out .= ' BLOB';
            }
        }

        $integerTypes = [
            TableSchemaInterface::TYPE_TINYINTEGER,
            TableSchemaInterface::TYPE_SMALLINTEGER,
            TableSchemaInterface::TYPE_INTEGER,
        ];
        if (
            in_array($data['type'], $integerTypes, true) &&
            isset($data['length']) &&
            $schema->getPrimaryKey() !== [$name]
        ) {
            $out .= '(' . (int)$data['length'] . ')';
        }

        $hasPrecision = [TableSchemaInterface::TYPE_FLOAT, TableSchemaInterface::TYPE_DECIMAL];
        if (
            in_array($data['type'], $hasPrecision, true) &&
            (
                isset($data['length']) ||
                isset($data['precision'])
            )
        ) {
            $out .= '(' . (int)$data['length'] . ',' . (int)$data['precision'] . ')';
        }

        if (isset($data['null']) && $data['null'] === false) {
            $out .= ' NOT NULL';
        }

        if ($data['type'] === TableSchemaInterface::TYPE_INTEGER && $schema->getPrimaryKey() === [$name]) {
            $out .= ' PRIMARY KEY';
            if (($name === 'id' || $data['autoIncrement']) && $data['autoIncrement'] !== false) {
                $out .= ' AUTOINCREMENT';
                unset($data['default']);
            }
        }

        $timestampTypes = [
            TableSchemaInterface::TYPE_DATETIME,
            TableSchemaInterface::TYPE_DATETIME_FRACTIONAL,
            TableSchemaInterface::TYPE_TIMESTAMP,
            TableSchemaInterface::TYPE_TIMESTAMP_FRACTIONAL,
            TableSchemaInterface::TYPE_TIMESTAMP_TIMEZONE,
        ];
        if (isset($data['null']) && $data['null'] === true && in_array($data['type'], $timestampTypes, true)) {
            $out .= ' DEFAULT NULL';
        }
        if (isset($data['default'])) {
            $out .= ' DEFAULT ' . $this->_driver->schemaValue($data['default']);
        }

        return $out;
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
        $data = $schema->getConstraint($name);
        assert($data !== null, 'Data does not exist');

        $column = $schema->getColumn($data['columns'][0]);
        assert($column !== null, 'Data does not exist');

        if (
            $data['type'] === TableSchema::CONSTRAINT_PRIMARY &&
            count($data['columns']) === 1 &&
            $column['type'] === TableSchemaInterface::TYPE_INTEGER
        ) {
            return '';
        }
        $clause = '';
        $type = '';
        if ($data['type'] === TableSchema::CONSTRAINT_PRIMARY) {
            $type = 'PRIMARY KEY';
        }
        if ($data['type'] === TableSchema::CONSTRAINT_UNIQUE) {
            $type = 'UNIQUE';
        }
        if ($data['type'] === TableSchema::CONSTRAINT_FOREIGN) {
            $type = 'FOREIGN KEY';

            $clause = sprintf(
                ' REFERENCES %s (%s) ON UPDATE %s ON DELETE %s',
                $this->_driver->quoteIdentifier($data['references'][0]),
                $this->_convertConstraintColumns($data['references'][1]),
                $this->_foreignOnClause($data['update']),
                $this->_foreignOnClause($data['delete'])
            );
        }
        $columns = array_map(
            $this->_driver->quoteIdentifier(...),
            $data['columns']
        );

        return sprintf(
            'CONSTRAINT %s %s (%s)%s',
            $this->_driver->quoteIdentifier($name),
            $type,
            implode(', ', $columns),
            $clause
        );
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
        $data = $schema->getIndex($name);
        assert($data !== null);
        $columns = array_map(
            $this->_driver->quoteIdentifier(...),
            $data['columns']
        );

        return sprintf(
            'CREATE INDEX %s ON %s (%s)',
            $this->_driver->quoteIdentifier($name),
            $this->_driver->quoteIdentifier($schema->name()),
            implode(', ', $columns)
        );
    }

    /**
     * @inheritDoc
     */
    public function createTableSql(TableSchema $schema, array $columns, array $constraints, array $indexes): array
    {
        $lines = array_merge($columns, $constraints);
        $content = implode(",\n", array_filter($lines));
        $temporary = $schema->isTemporary() ? ' TEMPORARY ' : ' ';
        $table = sprintf("CREATE%sTABLE \"%s\" (\n%s\n)", $temporary, $schema->name(), $content);
        $out = [$table];
        foreach ($indexes as $index) {
            $out[] = $index;
        }

        return $out;
    }

    /**
     * @inheritDoc
     */
    public function truncateTableSql(TableSchema $schema): array
    {
        $name = $schema->name();
        $sql = [];
        if ($this->hasSequences()) {
            $sql[] = sprintf('DELETE FROM sqlite_sequence WHERE name="%s"', $name);
        }

        $sql[] = sprintf('DELETE FROM "%s"', $name);

        return $sql;
    }

    /**
     * Returns whether there is any table in this connection to SQLite containing
     * sequences
     *
     * @return bool
     */
    public function hasSequences(): bool
    {
        $result = $this->_driver->prepare(
            'SELECT 1 FROM sqlite_master WHERE name = "sqlite_sequence"'
        );
        $result->execute();
        $this->_hasSequences = (bool)$result->fetch();

        return $this->_hasSequences;
    }
}
