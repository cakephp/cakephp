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

/**
 * Schema management/reflection features for SQLServer.
 *
 * @internal
 */
class SqlserverSchemaDialect extends SchemaDialect
{
    /**
     * @var string
     */
    public const DEFAULT_SCHEMA_NAME = 'dbo';

    /**
     * Generate the SQL to list the tables and views.
     *
     * @param array<string, mixed> $config The connection configuration to use for
     *    getting tables from.
     * @return array An array of (sql, params) to execute.
     */
    public function listTablesSql(array $config): array
    {
        $sql = "SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = ?
            AND (TABLE_TYPE = 'BASE TABLE' OR TABLE_TYPE = 'VIEW')
            ORDER BY TABLE_NAME";
        $schema = $config['schema'] ?? static::DEFAULT_SCHEMA_NAME;

        return [$sql, [$schema]];
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
        $sql = "SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = ?
            AND (TABLE_TYPE = 'BASE TABLE')
            ORDER BY TABLE_NAME";
        $schema = $config['schema'] ?? static::DEFAULT_SCHEMA_NAME;

        return [$sql, [$schema]];
    }

    /**
     * @inheritDoc
     */
    public function describeColumnSql(string $tableName, array $config): array
    {
        $sql = $this->describeColumnQuery();
        $schema = $config['schema'] ?? static::DEFAULT_SCHEMA_NAME;

        return [$sql, [$tableName, $schema]];
    }

    /**
     * Helper method for creating SQL to describe columns in a table.
     *
     * @return string SQL to reflect columns
     */
    private function describeColumnQuery(): string
    {
        return 'SELECT DISTINCT
            AC.column_id AS [column_id],
            AC.name AS [name],
            TY.name AS [type],
            AC.max_length AS [char_length],
            AC.precision AS [precision],
            AC.scale AS [scale],
            AC.is_identity AS [autoincrement],
            AC.is_nullable AS [null],
            OBJECT_DEFINITION(AC.default_object_id) AS [default],
            AC.collation_name AS [collation_name],
            EP.[value] AS [comment]
            FROM sys.[objects] T
            INNER JOIN sys.[schemas] S ON S.[schema_id] = T.[schema_id]
            INNER JOIN sys.[all_columns] AC ON T.[object_id] = AC.[object_id]
            INNER JOIN sys.[types] TY ON TY.[user_type_id] = AC.[user_type_id]
            LEFT JOIN sys.[extended_properties] as EP
                ON T.[object_id] = EP.[major_id]
                AND AC.[column_id] = EP.[minor_id]
                AND EP.[name] = \'MS_Description\'
            WHERE T.[name] = ? AND S.[name] = ?
            ORDER BY column_id';
    }

    /**
     * Convert a column definition to the abstract types.
     *
     * The returned type will be a type that
     * Cake\Database\TypeFactory  can handle.
     *
     * @param string $col The column type
     * @param int|null $length the column length
     * @param int|null $precision The column precision
     * @param int|null $scale The column scale
     * @return array<string, mixed> Array of column information.
     * @link https://technet.microsoft.com/en-us/library/ms187752.aspx
     */
    protected function _convertColumn(
        string $col,
        ?int $length = null,
        ?int $precision = null,
        ?int $scale = null,
    ): array {
        $col = strtolower($col);

        $type = $this->_applyTypeSpecificColumnConversion(
            $col,
            compact('length', 'precision', 'scale'),
        );
        if ($type !== null) {
            return $type;
        }

        if (in_array($col, ['date', 'time'])) {
            return ['type' => $col, 'length' => null];
        }

        if ($col === 'datetime') {
            // datetime cannot parse more than 3 digits of precision and isn't accurate
            return ['type' => TableSchemaInterface::TYPE_DATETIME, 'length' => null];
        }
        if (str_contains($col, 'datetime')) {
            $typeName = TableSchemaInterface::TYPE_DATETIME;
            if ($scale > 0) {
                $typeName = TableSchemaInterface::TYPE_DATETIME_FRACTIONAL;
            }

            return ['type' => $typeName, 'length' => null, 'precision' => $scale];
        }

        if ($col === 'char') {
            return ['type' => TableSchemaInterface::TYPE_CHAR, 'length' => $length];
        }

        if ($col === 'tinyint') {
            return ['type' => TableSchemaInterface::TYPE_TINYINTEGER, 'length' => $precision ?: 3];
        }
        if ($col === 'smallint') {
            return ['type' => TableSchemaInterface::TYPE_SMALLINTEGER, 'length' => $precision ?: 5];
        }
        if ($col === 'int' || $col === 'integer') {
            return ['type' => TableSchemaInterface::TYPE_INTEGER, 'length' => $precision ?: 10];
        }
        if ($col === 'bigint') {
            return ['type' => TableSchemaInterface::TYPE_BIGINTEGER, 'length' => $precision ?: 20];
        }
        if ($col === 'bit') {
            return ['type' => TableSchemaInterface::TYPE_BOOLEAN, 'length' => null];
        }
        if (
            str_contains($col, 'numeric') ||
            str_contains($col, 'money') ||
            str_contains($col, 'decimal')
        ) {
            return ['type' => TableSchemaInterface::TYPE_DECIMAL, 'length' => $precision, 'precision' => $scale];
        }

        if ($col === 'real' || $col === 'float') {
            return ['type' => TableSchemaInterface::TYPE_FLOAT, 'length' => null];
        }
        // SqlServer schema reflection returns double length for unicode
        // columns because internally it uses UTF16/UCS2
        if ($col === 'nvarchar' || $col === 'nchar' || $col === 'ntext') {
            $length /= 2;
        }
        if (str_contains($col, 'varchar') && $length < 0) {
            return ['type' => TableSchemaInterface::TYPE_TEXT, 'length' => null];
        }

        if (str_contains($col, 'varchar')) {
            return ['type' => TableSchemaInterface::TYPE_STRING, 'length' => $length ?: 255];
        }

        if (str_contains($col, 'char')) {
            return ['type' => TableSchemaInterface::TYPE_CHAR, 'length' => $length];
        }

        if (str_contains($col, 'text')) {
            return ['type' => TableSchemaInterface::TYPE_TEXT, 'length' => null];
        }

        if ($col === 'image' || str_contains($col, 'binary')) {
            // -1 is the value for MAX which we treat as a 'long' binary
            if ($length == -1) {
                $length = TableSchema::LENGTH_LONG;
            }

            return ['type' => TableSchemaInterface::TYPE_BINARY, 'length' => $length];
        }

        if ($col === 'uniqueidentifier') {
            return ['type' => TableSchemaInterface::TYPE_UUID];
        }
        if ($col === 'geometry') {
            return ['type' => TableSchemaInterface::TYPE_GEOMETRY];
        }
        if ($col === 'geography') {
            // SQLserver only has one generic geometry type that
            // we map to point.
            return ['type' => TableSchemaInterface::TYPE_POINT];
        }

        return ['type' => TableSchemaInterface::TYPE_STRING, 'length' => null];
    }

    /**
     * @inheritDoc
     */
    public function convertColumnDescription(TableSchema $schema, array $row): void
    {
        $field = $this->_convertColumn(
            $row['type'],
            $row['char_length'] !== null ? (int)$row['char_length'] : null,
            $row['precision'] !== null ? (int)$row['precision'] : null,
            $row['scale'] !== null ? (int)$row['scale'] : null,
        );

        if (!empty($row['autoincrement'])) {
            $field['autoIncrement'] = true;
        }

        $field += [
            'null' => $row['null'] === '1',
            'default' => $this->_defaultValue($field['type'], $row['default']),
            'collate' => $row['collation_name'],
        ];
        $schema->addColumn($row['name'], $field);
    }

    /**
     * Split a tablename into a tuple of schema, table
     * If the table does not have a schema name included, the connection
     * schema will be used.
     *
     * @param string $tableName The table name to split
     * @return array A tuple of [schema, tablename]
     */
    private function splitTablename(string $tableName): array
    {
        $config = $this->_driver->config();
        $schema = $config['schema'] ?? static::DEFAULT_SCHEMA_NAME;
        if (str_contains($tableName, '.')) {
            return explode('.', $tableName);
        }

        return [$schema, $tableName];
    }

    /**
     * @inheritDoc
     */
    public function describeColumns(string $tableName): array
    {
        [$schema, $name] = $this->splitTablename($tableName);

        $sql = $this->describeColumnQuery();
        $statement = $this->_driver->execute($sql, [$name, $schema]);
        $columns = [];
        foreach ($statement->fetchAll('assoc') as $row) {
            $field = $this->_convertColumn(
                $row['type'],
                $row['char_length'] !== null ? (int)$row['char_length'] : null,
                $row['precision'] !== null ? (int)$row['precision'] : null,
                $row['scale'] !== null ? (int)$row['scale'] : null,
            );

            if (!empty($row['autoincrement'])) {
                $field['autoIncrement'] = true;
            }

            $field += [
                'name' => $row['name'],
                'null' => $row['null'] === '1',
                'default' => $this->_defaultValue($field['type'], $row['default']),
                'comment' => $row['comment'] ?? null,
                'collate' => $row['collation_name'],
            ];
            $columns[] = $field;
        }

        return $columns;
    }

    /**
     * Manipulate the default value.
     *
     * Removes () wrapping default values, extracts strings from
     * N'' wrappers and collation text and converts NULL strings.
     *
     * @param string $type The schema type
     * @param string|null $default The default value.
     * @return string|int|null
     */
    protected function _defaultValue(string $type, ?string $default): string|int|null
    {
        if ($default === null) {
            return null;
        }

        // remove () surrounding value (NULL) but leave () at the end of functions
        // integers might have two ((0)) wrapping value
        if (preg_match('/^\(+(.*?(\(\))?)\)+$/', $default, $matches)) {
            $default = $matches[1];
        }

        if ($default === 'NULL') {
            return null;
        }

        if ($type === TableSchemaInterface::TYPE_BOOLEAN) {
            return (int)$default;
        }

        // Remove quotes
        if (preg_match("/^\(?N?'(.*)'\)?/", $default, $matches)) {
            return str_replace("''", "'", $matches[1]);
        }

        return $default;
    }

    /**
     * @inheritDoc
     */
    public function describeIndexSql(string $tableName, array $config): array
    {
        $sql = $this->describeIndexQuery();
        $schema = $config['schema'] ?? static::DEFAULT_SCHEMA_NAME;

        return [$sql, [$tableName, $schema]];
    }

    /**
     * Get the query to describe indexes
     *
     * @return string
     */
    private function describeIndexQuery(): string
    {
        return "SELECT
                I.[name] AS [index_name],
                IC.[index_column_id] AS [index_order],
                AC.[name] AS [column_name],
                I.[is_unique], I.[is_primary_key],
                I.[is_unique_constraint]
            FROM sys.[tables] AS T
            INNER JOIN sys.[schemas] S ON S.[schema_id] = T.[schema_id]
            INNER JOIN sys.[indexes] I ON T.[object_id] = I.[object_id]
            INNER JOIN sys.[index_columns] IC ON I.[object_id] = IC.[object_id] AND I.[index_id] = IC.[index_id]
            INNER JOIN sys.[all_columns] AC ON T.[object_id] = AC.[object_id] AND IC.[column_id] = AC.[column_id]
            WHERE T.[is_ms_shipped] = 0 AND I.[type_desc] <> 'HEAP' AND T.[name] = ? AND S.[name] = ?
            ORDER BY I.[index_id], IC.[index_column_id]";
    }

    /**
     * @inheritDoc
     */
    public function convertIndexDescription(TableSchema $schema, array $row): void
    {
        $type = TableSchema::INDEX_INDEX;
        $name = $row['index_name'];
        if ($row['is_primary_key']) {
            $name = TableSchema::CONSTRAINT_PRIMARY;
            $type = TableSchema::CONSTRAINT_PRIMARY;
        }
        if (($row['is_unique'] || $row['is_unique_constraint']) && $type === TableSchema::INDEX_INDEX) {
            $type = TableSchema::CONSTRAINT_UNIQUE;
        }

        if ($type === TableSchema::INDEX_INDEX) {
            $existing = $schema->getIndex($name);
        } else {
            $existing = $schema->getConstraint($name);
        }

        $columns = [$row['column_name']];
        if ($existing) {
            $columns = array_merge($existing['columns'], $columns);
        }

        if ($type === TableSchema::CONSTRAINT_PRIMARY || $type === TableSchema::CONSTRAINT_UNIQUE) {
            $schema->addConstraint($name, [
                'type' => $type,
                'columns' => $columns,
            ]);

            return;
        }
        $schema->addIndex($name, [
            'type' => $type,
            'columns' => $columns,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function describeIndexes(string $tableName): array
    {
        [$schema, $name] = $this->splitTablename($tableName);
        $sql = $this->describeIndexQuery();
        $indexes = [];
        $statement = $this->_driver->execute($sql, [$name, $schema]);
        foreach ($statement->fetchAll('assoc') as $row) {
            $type = TableSchema::INDEX_INDEX;
            $name = $row['index_name'];
            $constraint = null;
            if ($row['is_primary_key']) {
                $constraint = $name;
                $name = TableSchema::CONSTRAINT_PRIMARY;
                $type = TableSchema::CONSTRAINT_PRIMARY;
            }
            if (($row['is_unique'] || $row['is_unique_constraint']) && $type === TableSchema::INDEX_INDEX) {
                $type = TableSchema::CONSTRAINT_UNIQUE;
            }

            if (!isset($indexes[$name])) {
                $indexes[$name] = [
                    'name' => $name,
                    'type' => $type,
                    'columns' => [],
                    'length' => [],
                ];
            }
            $indexes[$name]['columns'][] = $row['column_name'];
            if ($constraint) {
                $indexes[$name]['constraint'] = $constraint;
            }
        }

        return array_values($indexes);
    }

    /**
     * Get the query to describe foreign keys
     *
     * @return string
     */
    private function describeForeignKeyQuery(): string
    {
        // phpcs:disable Generic.Files.LineLength

        return 'SELECT FK.[name] AS [foreign_key_name],
            FK.[delete_referential_action_desc] AS [delete_type],
            FK.[update_referential_action_desc] AS [update_type],
            C.name AS [column],
            RT.name AS [reference_table],
            RC.name AS [reference_column]
            FROM sys.foreign_keys FK
            INNER JOIN sys.foreign_key_columns FKC ON FKC.constraint_object_id = FK.object_id
            INNER JOIN sys.tables T ON T.object_id = FKC.parent_object_id
            INNER JOIN sys.tables RT ON RT.object_id = FKC.referenced_object_id
            INNER JOIN sys.schemas S ON S.schema_id = T.schema_id AND S.schema_id = RT.schema_id
            INNER JOIN sys.columns C ON C.column_id = FKC.parent_column_id AND C.object_id = FKC.parent_object_id
            INNER JOIN sys.columns RC ON RC.column_id = FKC.referenced_column_id AND RC.object_id = FKC.referenced_object_id
            WHERE FK.is_ms_shipped = 0 AND T.name = ? AND S.name = ?
            ORDER BY FKC.constraint_column_id';
        // phpcs:enable Generic.Files.LineLength
    }

    /**
     * @inheritDoc
     */
    public function describeForeignKeys(string $tableName): array
    {
        [$schema, $name] = $this->splitTablename($tableName);
        $sql = $this->describeForeignKeyQuery();
        $keys = [];
        $statement = $this->_driver->execute($sql, [$name, $schema]);
        foreach ($statement->fetchAll('assoc') as $row) {
            $name = $row['foreign_key_name'];
            if (!isset($keys[$name])) {
                $keys[$name] = [
                    'name' => $name,
                    'type' => TableSchema::CONSTRAINT_FOREIGN,
                    'columns' => [],
                    'references' => [$row['reference_table'], []],
                    'update' => $this->_convertOnClause($row['update_type']),
                    'delete' => $this->_convertOnClause($row['delete_type']),
                ];
            }
            $keys[$name]['columns'][] = $row['column'];
            $keys[$name]['references'][1][] = $row['reference_column'];
        }

        foreach ($keys as $id => $key) {
            // references.1 is the referenced columns. Backwards compat
            // requires a single column to be a string, but multiple to be an array.
            if (count($key['references'][1]) === 1) {
                $keys[$id]['references'][1] = $key['references'][1][0];
            }
        }

        return array_values($keys);
    }

    /**
     * @inheritDoc
     */
    public function describeForeignKeySql(string $tableName, array $config): array
    {
        $sql = $this->describeForeignKeyQuery();
        $schema = $config['schema'] ?? static::DEFAULT_SCHEMA_NAME;

        return [$sql, [$tableName, $schema]];
    }

    /**
     * @inheritDoc
     */
    public function convertForeignKeyDescription(TableSchema $schema, array $row): void
    {
        $data = [
            'type' => TableSchema::CONSTRAINT_FOREIGN,
            'columns' => [$row['column']],
            'references' => [$row['reference_table'], $row['reference_column']],
            'update' => $this->_convertOnClause($row['update_type']),
            'delete' => $this->_convertOnClause($row['delete_type']),
        ];
        $name = $row['foreign_key_name'];
        $schema->addConstraint($name, $data);
    }

    /**
     * @inheritDoc
     */
    public function describeOptions(string $tableName): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function _foreignOnClause(string $on): string
    {
        $parent = parent::_foreignOnClause($on);

        return $parent === 'RESTRICT' ? parent::_foreignOnClause(TableSchema::ACTION_NO_ACTION) : $parent;
    }

    /**
     * @inheritDoc
     */
    protected function _convertOnClause(string $clause): string
    {
        return match ($clause) {
            'NO_ACTION' => TableSchema::ACTION_NO_ACTION,
            'CASCADE' => TableSchema::ACTION_CASCADE,
            'SET_NULL' => TableSchema::ACTION_SET_NULL,
            'SET_DEFAULT' => TableSchema::ACTION_SET_DEFAULT,
            default => TableSchema::ACTION_SET_NULL,
        };
    }

    /**
     * @inheritDoc
     */
    public function columnSql(TableSchema $schema, string $name): string
    {
        $data = $schema->getColumn($name);
        assert($data !== null);
        $data['name'] = $name;

        $sql = $this->_getTypeSpecificColumnSql($data['type'], $schema, $name);
        if ($sql !== null) {
            return $sql;
        }
        $autoIncrementTypes = [
            TableSchemaInterface::TYPE_TINYINTEGER,
            TableSchemaInterface::TYPE_SMALLINTEGER,
            TableSchemaInterface::TYPE_INTEGER,
            TableSchemaInterface::TYPE_BIGINTEGER,
        ];
        $primaryKey = $schema->getPrimaryKey();
        if (
            in_array($data['type'], $autoIncrementTypes, true) &&
            $primaryKey === [$name] &&
            $name === 'id'
        ) {
            $data['autoIncrement'] = true;
        }

        return $this->columnDefinitionSql($data);
    }

    /**
     * @inheritDoc
     */
    public function columnDefinitionSql(array $column): string
    {
        $name = $column['name'];
        $column += [
            'length' => null,
            'precision' => null,
        ];
        $out = $this->_driver->quoteIdentifier($name);
        $typeMap = [
            TableSchemaInterface::TYPE_TINYINTEGER => ' TINYINT',
            TableSchemaInterface::TYPE_SMALLINTEGER => ' SMALLINT',
            TableSchemaInterface::TYPE_INTEGER => ' INTEGER',
            TableSchemaInterface::TYPE_BIGINTEGER => ' BIGINT',
            TableSchemaInterface::TYPE_BINARY_UUID => ' UNIQUEIDENTIFIER',
            TableSchemaInterface::TYPE_BOOLEAN => ' BIT',
            TableSchemaInterface::TYPE_CHAR => ' NCHAR',
            TableSchemaInterface::TYPE_FLOAT => ' FLOAT',
            TableSchemaInterface::TYPE_DECIMAL => ' DECIMAL',
            TableSchemaInterface::TYPE_DATE => ' DATE',
            TableSchemaInterface::TYPE_TIME => ' TIME',
            TableSchemaInterface::TYPE_DATETIME => ' DATETIME2',
            TableSchemaInterface::TYPE_DATETIME_FRACTIONAL => ' DATETIME2',
            TableSchemaInterface::TYPE_TIMESTAMP => ' DATETIME2',
            TableSchemaInterface::TYPE_TIMESTAMP_FRACTIONAL => ' DATETIME2',
            TableSchemaInterface::TYPE_TIMESTAMP_TIMEZONE => ' DATETIME2',
            TableSchemaInterface::TYPE_UUID => ' UNIQUEIDENTIFIER',
            TableSchemaInterface::TYPE_NATIVE_UUID => ' UNIQUEIDENTIFIER',
            TableSchemaInterface::TYPE_JSON => ' NVARCHAR(MAX)',
            TableSchemaInterface::TYPE_GEOMETRY => ' GEOMETRY',
            TableSchemaInterface::TYPE_POINT => ' GEOGRAPHY',
            TableSchemaInterface::TYPE_LINESTRING => ' GEOGRAPHY',
            TableSchemaInterface::TYPE_POLYGON => ' GEOGRAPHY',
        ];

        if (isset($typeMap[$column['type']])) {
            $out .= $typeMap[$column['type']];
        }

        $autoIncrementTypes = [
            TableSchemaInterface::TYPE_TINYINTEGER,
            TableSchemaInterface::TYPE_SMALLINTEGER,
            TableSchemaInterface::TYPE_INTEGER,
            TableSchemaInterface::TYPE_BIGINTEGER,
        ];
        $autoIncrement = (bool)($column['autoIncrement'] ?? false);
        if (
            in_array($column['type'], $autoIncrementTypes, true) &&
            $autoIncrement
        ) {
            $out .= ' IDENTITY(1, 1)';
            unset($column['default']);
        }

        if ($column['type'] === TableSchemaInterface::TYPE_TEXT && $column['length'] !== TableSchema::LENGTH_TINY) {
            $out .= ' NVARCHAR(MAX)';
        }

        if ($column['type'] === TableSchemaInterface::TYPE_CHAR) {
            $out .= '(' . $column['length'] . ')';
        }

        if ($column['type'] === TableSchemaInterface::TYPE_BINARY) {
            if (
                !isset($column['length'])
                || in_array($column['length'], [TableSchema::LENGTH_MEDIUM, TableSchema::LENGTH_LONG], true)
            ) {
                $column['length'] = 'MAX';
            }

            if ($column['length'] === 1) {
                $out .= ' BINARY(1)';
            } else {
                $out .= ' VARBINARY';

                $out .= sprintf('(%s)', $column['length']);
            }
        }

        if (
            $column['type'] === TableSchemaInterface::TYPE_STRING ||
            (
                $column['type'] === TableSchemaInterface::TYPE_TEXT &&
                $column['length'] === TableSchema::LENGTH_TINY
            )
        ) {
            $type = ' NVARCHAR';
            $length = $column['length'] ?? TableSchema::LENGTH_TINY;
            $out .= sprintf('%s(%d)', $type, $length);
        }

        $hasCollate = [
            TableSchemaInterface::TYPE_TEXT,
            TableSchemaInterface::TYPE_STRING,
            TableSchemaInterface::TYPE_CHAR,
        ];
        if (in_array($column['type'], $hasCollate, true) && isset($column['collate']) && $column['collate'] !== '') {
            $out .= ' COLLATE ' . $column['collate'];
        }

        $precisionTypes = [
            TableSchemaInterface::TYPE_FLOAT,
            TableSchemaInterface::TYPE_DATETIME,
            TableSchemaInterface::TYPE_DATETIME_FRACTIONAL,
            TableSchemaInterface::TYPE_TIMESTAMP,
            TableSchemaInterface::TYPE_TIMESTAMP_FRACTIONAL,
        ];
        if (in_array($column['type'], $precisionTypes, true) && isset($column['precision'])) {
            $out .= '(' . (int)$column['precision'] . ')';
        }

        if (
            $column['type'] === TableSchemaInterface::TYPE_DECIMAL &&
            (
                isset($column['length']) ||
                isset($column['precision'])
            )
        ) {
            $out .= '(' . (int)$column['length'] . ',' . (int)$column['precision'] . ')';
        }

        if (isset($column['null']) && $column['null'] === false) {
            $out .= ' NOT NULL';
        }

        $dateTimeTypes = [
            TableSchemaInterface::TYPE_DATETIME,
            TableSchemaInterface::TYPE_DATETIME_FRACTIONAL,
            TableSchemaInterface::TYPE_TIMESTAMP,
            TableSchemaInterface::TYPE_TIMESTAMP_FRACTIONAL,
        ];
        $dateTimeDefaults = [
            'current_timestamp',
            'getdate()',
            'getutcdate()',
            'sysdatetime()',
            'sysutcdatetime()',
            'sysdatetimeoffset()',
        ];
        if (
            isset($column['default']) &&
            in_array($column['type'], $dateTimeTypes, true) &&
            is_string($column['default']) &&
            in_array(strtolower($column['default']), $dateTimeDefaults, true)
        ) {
            $out .= ' DEFAULT ' . strtoupper($column['default']);
        } elseif (isset($column['default'])) {
            $default = is_bool($column['default'])
                ? (int)$column['default']
                : $this->_driver->schemaValue($column['default']);
            $out .= ' DEFAULT ' . $default;
        } elseif (isset($column['null']) && $column['null'] !== false) {
            $out .= ' DEFAULT NULL';
        }

        return $out;
    }

    /**
     * @inheritDoc
     */
    public function addConstraintSql(TableSchema $schema): array
    {
        $sqlPattern = 'ALTER TABLE %s ADD %s;';
        $sql = [];

        foreach ($schema->constraints() as $name) {
            $constraint = $schema->getConstraint($name);
            assert($constraint !== null);
            if ($constraint['type'] === TableSchema::CONSTRAINT_FOREIGN) {
                $tableName = $this->_driver->quoteIdentifier($schema->name());
                $sql[] = sprintf($sqlPattern, $tableName, $this->constraintSql($schema, $name));
            }
        }

        return $sql;
    }

    /**
     * @inheritDoc
     */
    public function dropConstraintSql(TableSchema $schema): array
    {
        $sqlPattern = 'ALTER TABLE %s DROP CONSTRAINT %s;';
        $sql = [];

        foreach ($schema->constraints() as $name) {
            $constraint = $schema->getConstraint($name);
            assert($constraint !== null);
            if ($constraint['type'] === TableSchema::CONSTRAINT_FOREIGN) {
                $tableName = $this->_driver->quoteIdentifier($schema->name());
                $constraintName = $this->_driver->quoteIdentifier($name);
                $sql[] = sprintf($sqlPattern, $tableName, $constraintName);
            }
        }

        return $sql;
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
            $data['columns'],
        );

        return sprintf(
            'CREATE INDEX %s ON %s (%s)',
            $this->_driver->quoteIdentifier($name),
            $this->_driver->quoteIdentifier($schema->name()),
            implode(', ', $columns),
        );
    }

    /**
     * @inheritDoc
     */
    public function constraintSql(TableSchema $schema, string $name): string
    {
        $data = $schema->getConstraint($name);
        assert($data !== null);
        $out = 'CONSTRAINT ' . $this->_driver->quoteIdentifier($name);
        if ($data['type'] === TableSchema::CONSTRAINT_PRIMARY) {
            $out = 'PRIMARY KEY';
        }
        if ($data['type'] === TableSchema::CONSTRAINT_UNIQUE) {
            $out .= ' UNIQUE';
        }

        return $this->_keySql($out, $data);
    }

    /**
     * Helper method for generating key SQL snippets.
     *
     * @param string $prefix The key prefix
     * @param array $data Key data.
     * @return string
     */
    protected function _keySql(string $prefix, array $data): string
    {
        $columns = array_map(
            $this->_driver->quoteIdentifier(...),
            $data['columns'],
        );
        if ($data['type'] === TableSchema::CONSTRAINT_FOREIGN) {
            return $prefix . sprintf(
                ' FOREIGN KEY (%s) REFERENCES %s (%s) ON UPDATE %s ON DELETE %s',
                implode(', ', $columns),
                $this->_driver->quoteIdentifier($data['references'][0]),
                $this->_convertConstraintColumns($data['references'][1]),
                $this->_foreignOnClause($data['update']),
                $this->_foreignOnClause($data['delete']),
            );
        }

        return $prefix . ' (' . implode(', ', $columns) . ')';
    }

    /**
     * @inheritDoc
     */
    public function createTableSql(TableSchema $schema, array $columns, array $constraints, array $indexes): array
    {
        $content = array_merge($columns, $constraints);
        $content = implode(",\n", array_filter($content));
        $tableName = $this->_driver->quoteIdentifier($schema->name());
        $out = [];
        $out[] = sprintf("CREATE TABLE %s (\n%s\n)", $tableName, $content);
        foreach ($indexes as $index) {
            $out[] = $index;
        }
        foreach ($schema->columns() as $name) {
            $column = $schema->getColumn($name);
            $comment = $column['comment'] ?? null;
            if ($comment !== null) {
                $out[] = $this->columnCommentSql($schema, $name, $comment);
            }
        }

        return $out;
    }

    /**
     * Generate the SQL to create a column comment.
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table schema.
     * @param string $name The column name.
     * @param string $comment The column comment.
     * @return string
     */
    protected function columnCommentSql(TableSchema $schema, string $name, string $comment): string
    {
        $tableName = $this->_driver->quoteIdentifier($schema->name());
        $columnName = $this->_driver->quoteIdentifier($name);
        $comment = $this->_driver->schemaValue($comment);

        return sprintf(
            "EXEC sp_addextendedproperty N'MS_Description', %s, N'SCHEMA', N'dbo', N'TABLE', %s, N'COLUMN', %s;",
            $comment,
            $tableName,
            $columnName,
        );
    }

    /**
     * @inheritDoc
     */
    public function truncateTableSql(TableSchema $schema): array
    {
        $name = $this->_driver->quoteIdentifier($schema->name());
        $queries = [
            sprintf('DELETE FROM %s', $name),
        ];

        // Restart identity sequences
        $pk = $schema->getPrimaryKey();
        if (count($pk) === 1) {
            $column = $schema->getColumn($pk[0]);
            assert($column !== null);
            if (in_array($column['type'], ['integer', 'biginteger'])) {
                $queries[] = sprintf(
                    "IF EXISTS (SELECT * FROM sys.identity_columns WHERE OBJECT_NAME(OBJECT_ID) = '%s' AND " .
                    "last_value IS NOT NULL) DBCC CHECKIDENT('%s', RESEED, 0)",
                    $schema->name(),
                    $schema->name(),
                );
            }
        }

        return $queries;
    }
}
