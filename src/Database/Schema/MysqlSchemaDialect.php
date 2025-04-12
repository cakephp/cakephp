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

use Cake\Database\DriverFeatureEnum;
use Cake\Database\Exception\DatabaseException;
use PDOException;

/**
 * Schema generation/reflection features for MySQL
 *
 * @internal
 */
class MysqlSchemaDialect extends SchemaDialect
{
    /**
     * Generate the SQL to list the tables and views.
     *
     * @param array<string, mixed> $config The connection configuration to use for
     *    getting tables from.
     * @return array<mixed> An array of (sql, params) to execute.
     */
    public function listTablesSql(array $config): array
    {
        return ['SHOW FULL TABLES FROM ' . $this->_driver->quoteIdentifier($config['database']), []];
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
            'SHOW FULL TABLES FROM ' . $this->_driver->quoteIdentifier($config['database'])
            . ' WHERE TABLE_TYPE = "BASE TABLE"'
        , []];
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
     * Helper method for creating SQL to describe columns in a table.
     *
     * @param string $tableName The table to describe.
     * @return string SQL to reflect columns
     */
    private function describeColumnQuery(string $tableName): string
    {
        return 'SHOW FULL COLUMNS FROM ' . $this->_driver->quoteIdentifier($tableName);
    }

    /**
     * Split a tablename into a tuple of database, table
     * If the table does not have a database name included, the connection
     * database will be used.
     *
     * @param string $tableName The table name to split
     * @return array A tuple of [database, tablename]
     */
    private function splitTablename(string $tableName): array
    {
        $config = $this->_driver->config();
        $db = $config['database'];
        if (str_contains($tableName, '.')) {
            return explode('.', $tableName);
        }

        return [$db, $tableName];
    }

    /**
     * Get a list of column metadata as a array
     *
     * Each item in the array will contain the following:
     *
     * - name : the name of the column.
     * - type : the abstract type of the column.
     * - length : the length of the column.
     * - default : the default value of the column or null.
     * - null : boolean indicating whether the column can be null.
     * - comment : the column comment or null.
     *
     * The following keys will be set as required:
     *
     * - autoIncrement : set for columns that are an integer primary key.
     * - onUpdate : set for datetime/timestamp columns with `ON UPDATE` clauses.
     *
     * @param string $tableName The name of the table to describe columns on.
     * @return array
     */
    public function describeColumns(string $tableName): array
    {
        $sql = $this->describeColumnQuery($tableName);
        $columns = [];
        try {
            $statement = $this->_driver->execute($sql);
        } catch (PDOException $e) {
            throw new DatabaseException("Could not describe columns on `{$tableName}`", null, $e);
        }
        foreach ($statement->fetchAll('assoc') as $row) {
            $field = $this->_convertColumn($row['Type']);
            $default = $this->parseDefault($field['type'], $row);

            $field += [
                'name' => $row['Field'],
                'null' => $row['Null'] === 'YES',
                'default' => $default,
                'collate' => $row['Collation'],
                'comment' => $row['Comment'],
                'length' => null,
            ];
            if (isset($row['Extra']) && $row['Extra'] === 'auto_increment') {
                $field['autoIncrement'] = true;
            }
            if ($row['Extra'] === 'on update CURRENT_TIMESTAMP') {
                $field['onUpdate'] = 'CURRENT_TIMESTAMP';
            } elseif ($row['Extra'] === 'on update current_timestamp()') {
                $field['onUpdate'] = 'CURRENT_TIMESTAMP';
            }
            $columns[] = $field;
        }

        return $columns;
    }

    /**
     * Parse the default value if required.
     *
     * @param string $type The type of column
     * @param array $row a Row of schema reflection data
     * @return ?string The default value of a column.
     */
    protected function parseDefault(string $type, array $row): ?string
    {
        $default = $row['Default'];
        if (
            is_string($default) &&
            in_array(
                $type,
                array_merge(
                    TableSchema::GEOSPATIAL_TYPES,
                    [TableSchema::TYPE_BINARY, TableSchema::TYPE_JSON, TableSchema::TYPE_TEXT],
                ),
            )
        ) {
            // The default that comes back from MySQL for these types prefixes the collation type and
            // surrounds the value with escaped single quotes, for example "_utf8mbf4\'abc\'", and so
            // this converts that then down to the default value of "abc" to correspond to what the user
            // would have specified in a migration.
            $default = (string)preg_replace("/^_(?:[a-zA-Z0-9]+?)\\\'(.*)\\\'$/", '\1', $default);

            // If the default is wrapped in a function, and has a collation marker on it, strip
            // the collation marker out
            $default = (string)preg_replace(
                "/^(?<prefix>[a-zA-Z0-9_]*\()(?<collation>_[a-zA-Z0-9]+)\\\'(?<args>.*)\\\'\)$/",
                "\\1'\\3')",
                $default,
            );
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
     * Helper method for creating SQL to reflect indexes in a table.
     *
     * @param string $tableName The table to get indexes from.
     * @return string SQL to reflect indexes
     */
    private function describeIndexQuery(string $tableName): string
    {
        return 'SHOW INDEXES FROM ' . $this->_driver->quoteIdentifier($tableName);
    }

    /**
     * @inheritDoc
     */
    public function describeIndexes(string $tableName): array
    {
        $sql = $this->describeIndexQuery($tableName);
        $statement = $this->_driver->execute($sql);
        $indexes = [];

        foreach ($statement->fetchAll('assoc') as $row) {
            $name = $row['Key_name'];
            $type = null;
            if ($name === 'PRIMARY') {
                $name = TableSchema::CONSTRAINT_PRIMARY;
                $type = TableSchema::CONSTRAINT_PRIMARY;
            }
            if ($row['Index_type'] === 'FULLTEXT') {
                $type = TableSchema::INDEX_FULLTEXT;
            } elseif ((int)$row['Non_unique'] === 0 && $type !== TableSchema::CONSTRAINT_PRIMARY) {
                $type = TableSchema::CONSTRAINT_UNIQUE;
            } elseif ($type !== TableSchema::CONSTRAINT_PRIMARY) {
                $type = TableSchema::INDEX_INDEX;
            }
            if (!isset($indexes[$name])) {
                $indexes[$name] = [
                    'name' => $name,
                    'type' => $type,
                    'columns' => [],
                    'length' => [],
                ];
            }
            // conditional indexes can have null columns
            if ($row['Column_name'] !== null) {
                $indexes[$name]['columns'][] = $row['Column_name'];
            }
            if (!empty($row['Sub_part'])) {
                $indexes[$name]['length'][$row['Column_name']] = $row['Sub_part'];
            }
        }

        return array_values($indexes);
    }

    /**
     * @inheritDoc
     */
    public function describeOptionsSql(string $tableName, array $config): array
    {
        return ['SHOW TABLE STATUS WHERE Name = ?', [$tableName]];
    }

    /**
     * @inheritDoc
     */
    public function convertOptionsDescription(TableSchema $schema, array $row): void
    {
        $schema->setOptions([
            'engine' => $row['Engine'],
            'collation' => $row['Collation'],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function describeOptions(string $tableName): array
    {
        [, $name] = $this->splitTablename($tableName);
        $sql = 'SHOW TABLE STATUS WHERE Name = ?';
        $statement = $this->_driver->execute($sql, [$name]);
        $row = $statement->fetch('assoc');

        return [
            'engine' => $row['Engine'],
            'collation' => $row['Collation'],
        ];
    }

    /**
     * Convert a MySQL column type into an abstract type.
     *
     * The returned type will be a type that Cake\Database\TypeFactory can handle.
     *
     * @param string $column The column type + length
     * @return array<string, mixed> Array of column information.
     * @throws \Cake\Database\Exception\DatabaseException When column type cannot be parsed.
     */
    protected function _convertColumn(string $column): array
    {
        preg_match('/([a-z]+)(?:\(([0-9,]+)\))?\s*([a-z]+)?/i', $column, $matches);
        if (!$matches) {
            throw new DatabaseException(sprintf('Unable to parse column type from `%s`', $column));
        }

        $col = strtolower($matches[1]);
        $length = null;
        $precision = null;
        $scale = null;
        if (isset($matches[2]) && strlen($matches[2])) {
            $length = $matches[2];
            if (str_contains($matches[2], ',')) {
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

        if (in_array($col, ['date', 'time'])) {
            return ['type' => $col, 'length' => null];
        }
        if (in_array($col, ['datetime', 'timestamp'])) {
            $typeName = $col;
            if ($length > 0) {
                $typeName = $col . 'fractional';
            }

            return ['type' => $typeName, 'length' => null, 'precision' => $length];
        }

        if (($col === 'tinyint' && $length === 1) || $col === 'boolean') {
            return ['type' => TableSchemaInterface::TYPE_BOOLEAN, 'length' => null];
        }

        $unsigned = (isset($matches[3]) && strtolower($matches[3]) === 'unsigned');
        if (str_contains($col, 'bigint') || $col === 'bigint') {
            return ['type' => TableSchemaInterface::TYPE_BIGINTEGER, 'length' => null, 'unsigned' => $unsigned];
        }
        if ($col === 'tinyint') {
            return ['type' => TableSchemaInterface::TYPE_TINYINTEGER, 'length' => null, 'unsigned' => $unsigned];
        }
        if ($col === 'smallint') {
            return ['type' => TableSchemaInterface::TYPE_SMALLINTEGER, 'length' => null, 'unsigned' => $unsigned];
        }
        if (in_array($col, ['int', 'integer', 'mediumint'])) {
            return ['type' => TableSchemaInterface::TYPE_INTEGER, 'length' => null, 'unsigned' => $unsigned];
        }
        if ($col === 'char' && $length === 36) {
            return ['type' => TableSchemaInterface::TYPE_UUID, 'length' => null];
        }
        if ($col === 'char') {
            return ['type' => TableSchemaInterface::TYPE_CHAR, 'length' => $length];
        }
        if (str_contains($col, 'char')) {
            return ['type' => TableSchemaInterface::TYPE_STRING, 'length' => $length];
        }
        if (str_contains($col, 'text')) {
            $lengthName = substr($col, 0, -4);
            $length = TableSchema::$columnLengths[$lengthName] ?? null;

            return ['type' => TableSchemaInterface::TYPE_TEXT, 'length' => $length];
        }
        if ($col === 'binary' && $length === 16) {
            return ['type' => TableSchemaInterface::TYPE_BINARY_UUID, 'length' => null];
        }
        if ($col === 'uuid') {
            return ['type' => TableSchemaInterface::TYPE_NATIVE_UUID, 'length' => null];
        }
        if (str_contains($col, 'blob') || in_array($col, ['binary', 'varbinary'])) {
            $lengthName = substr($col, 0, -4);
            $length = TableSchema::$columnLengths[$lengthName] ?? $length;

            return ['type' => TableSchemaInterface::TYPE_BINARY, 'length' => $length];
        }
        if (str_contains($col, 'float') || str_contains($col, 'double')) {
            return [
                'type' => TableSchemaInterface::TYPE_FLOAT,
                'length' => $length,
                'precision' => $precision,
                'unsigned' => $unsigned,
            ];
        }
        if (str_contains($col, 'decimal')) {
            return [
                'type' => TableSchemaInterface::TYPE_DECIMAL,
                'length' => $length,
                'precision' => $precision,
                'unsigned' => $unsigned,
            ];
        }

        if (str_contains($col, 'json')) {
            return ['type' => TableSchemaInterface::TYPE_JSON, 'length' => null];
        }
        if (in_array($col, TableSchemaInterface::GEOSPATIAL_TYPES)) {
            // TODO how can srid be preserved? It doesn't come back
            // in the output of show full columns from ...
            return [
                'type' => $col,
                'length' => null,
            ];
        }

        return ['type' => TableSchemaInterface::TYPE_STRING, 'length' => null];
    }

    /**
     * @inheritDoc
     */
    public function convertColumnDescription(TableSchema $schema, array $row): void
    {
        $field = $this->_convertColumn($row['Type']);
        $default = $this->parseDefault($field['type'], $row);
        $field += [
            'null' => $row['Null'] === 'YES',
            'default' => $default,
            'collate' => $row['Collation'],
            'comment' => $row['Comment'],
        ];
        if (isset($row['Extra']) && $row['Extra'] === 'auto_increment') {
            $field['autoIncrement'] = true;
        }
        $schema->addColumn($row['Field'], $field);
    }

    /**
     * @inheritDoc
     */
    public function convertIndexDescription(TableSchema $schema, array $row): void
    {
        $type = null;
        $columns = [];
        $length = [];

        $name = $row['Key_name'];
        if ($name === 'PRIMARY') {
            $name = TableSchema::CONSTRAINT_PRIMARY;
            $type = TableSchema::CONSTRAINT_PRIMARY;
        }

        if (!empty($row['Column_name'])) {
            $columns[] = $row['Column_name'];
        }

        if ($row['Index_type'] === 'FULLTEXT') {
            $type = TableSchema::INDEX_FULLTEXT;
        } elseif ((int)$row['Non_unique'] === 0 && $type !== 'primary') {
            $type = TableSchema::CONSTRAINT_UNIQUE;
        } elseif ($type !== 'primary') {
            $type = TableSchema::INDEX_INDEX;
        }

        if (!empty($row['Sub_part'])) {
            $length[$row['Column_name']] = $row['Sub_part'];
        }
        $isIndex = (
            $type === TableSchema::INDEX_INDEX ||
            $type === TableSchema::INDEX_FULLTEXT
        );
        if ($isIndex) {
            $existing = $schema->getIndex($name);
        } else {
            $existing = $schema->getConstraint($name);
        }

        // MySQL multi column indexes come back as multiple rows.
        if ($existing) {
            $columns = array_merge($existing['columns'], $columns);
            $length = array_merge($existing['length'], $length);
        }
        if ($isIndex) {
            $schema->addIndex($name, [
                'type' => $type,
                'columns' => $columns,
                'length' => $length,
            ]);
        } else {
            $schema->addConstraint($name, [
                'type' => $type,
                'columns' => $columns,
                'length' => $length,
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function describeForeignKeySql(string $tableName, array $config): array
    {
        $sql = 'SELECT * FROM information_schema.key_column_usage AS kcu
            INNER JOIN information_schema.referential_constraints AS rc
            ON (
                kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                AND kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
            )
            WHERE kcu.TABLE_SCHEMA = ? AND kcu.TABLE_NAME = ? AND rc.TABLE_NAME = ?
            ORDER BY kcu.ORDINAL_POSITION ASC';

        return [$sql, [$config['database'], $tableName, $tableName]];
    }

    /**
     * @inheritDoc
     */
    public function convertForeignKeyDescription(TableSchema $schema, array $row): void
    {
        $data = [
            'type' => TableSchema::CONSTRAINT_FOREIGN,
            'columns' => [$row['COLUMN_NAME']],
            'references' => [$row['REFERENCED_TABLE_NAME'], $row['REFERENCED_COLUMN_NAME']],
            'update' => $this->_convertOnClause($row['UPDATE_RULE']),
            'delete' => $this->_convertOnClause($row['DELETE_RULE']),
        ];
        $name = $row['CONSTRAINT_NAME'];
        $schema->addConstraint($name, $data);
    }

    /**
     * @inheritDoc
     */
    public function describeForeignKeys(string $tableName): array
    {
        [$database, $name] = $this->splitTablename($tableName);
        $sql = 'SELECT * FROM information_schema.key_column_usage AS kcu
            INNER JOIN information_schema.referential_constraints AS rc
            ON (
                kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                AND kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
            )
            WHERE kcu.TABLE_SCHEMA = ? AND kcu.TABLE_NAME = ? AND rc.TABLE_NAME = ?
            ORDER BY kcu.ORDINAL_POSITION ASC';
        $statement = $this->_driver->execute($sql, [$database, $name, $name]);
        $keys = [];
        foreach ($statement->fetchAll('assoc') as $row) {
            $name = $row['CONSTRAINT_NAME'];
            if (!isset($keys[$name])) {
                $keys[$name] = [
                    'name' => $name,
                    'type' => TableSchema::CONSTRAINT_FOREIGN,
                    'columns' => [],
                    'references' => [$row['REFERENCED_TABLE_NAME'], []],
                    'update' => $this->_convertOnClause($row['UPDATE_RULE'] ?? ''),
                    'delete' => $this->_convertOnClause($row['DELETE_RULE'] ?? ''),
                    'length' => [],
                ];
            }
            // Add the columns incrementally
            $keys[$name]['columns'][] = $row['COLUMN_NAME'];
            $keys[$name]['references'][1][] = $row['REFERENCED_COLUMN_NAME'];
        }
        foreach ($keys as $id => $key) {
            if (count($key['references'][1]) === 1) {
                $keys[$id]['references'][1] = $key['references'][1][0];
            }
        }

        return array_values($keys);
    }

    /**
     * @inheritDoc
     */
    public function truncateTableSql(TableSchema $schema): array
    {
        return [sprintf('TRUNCATE TABLE `%s`', $schema->name())];
    }

    /**
     * @inheritDoc
     */
    public function createTableSql(TableSchema $schema, array $columns, array $constraints, array $indexes): array
    {
        $content = implode(",\n", array_merge($columns, $constraints, $indexes));
        $temporary = $schema->isTemporary() ? ' TEMPORARY ' : ' ';
        $content = sprintf("CREATE%sTABLE `%s` (\n%s\n)", $temporary, $schema->name(), $content);
        $options = $schema->getOptions();
        if (isset($options['engine'])) {
            $content .= sprintf(' ENGINE=%s', $options['engine']);
        }
        if (isset($options['charset'])) {
            $content .= sprintf(' DEFAULT CHARSET=%s', $options['charset']);
        }
        if (isset($options['collate'])) {
            $content .= sprintf(' COLLATE=%s', $options['collate']);
        }

        return [$content];
    }

    /**
     * Create a SQL snippet for a column based on the array shape
     * that `describeColumns()` creates.
     *
     * @param array $column The column metadata
     * @return string Generated SQL fragment for a column
     */
    public function columnDefinitionSql(array $column): string
    {
        $name = $column['name'];
        $column += [
            'length' => null,
        ];

        $out = $this->_driver->quoteIdentifier($name);
        $nativeJson = $this->_driver->supports(DriverFeatureEnum::JSON);

        $typeMap = [
            TableSchemaInterface::TYPE_TINYINTEGER => ' TINYINT',
            TableSchemaInterface::TYPE_SMALLINTEGER => ' SMALLINT',
            TableSchemaInterface::TYPE_INTEGER => ' INTEGER',
            TableSchemaInterface::TYPE_BIGINTEGER => ' BIGINT',
            TableSchemaInterface::TYPE_BINARY_UUID => ' BINARY(16)',
            TableSchemaInterface::TYPE_BOOLEAN => ' BOOLEAN',
            TableSchemaInterface::TYPE_FLOAT => ' FLOAT',
            TableSchemaInterface::TYPE_DECIMAL => ' DECIMAL',
            TableSchemaInterface::TYPE_DATE => ' DATE',
            TableSchemaInterface::TYPE_TIME => ' TIME',
            TableSchemaInterface::TYPE_DATETIME => ' DATETIME',
            TableSchemaInterface::TYPE_DATETIME_FRACTIONAL => ' DATETIME',
            TableSchemaInterface::TYPE_TIMESTAMP => ' TIMESTAMP',
            TableSchemaInterface::TYPE_TIMESTAMP_FRACTIONAL => ' TIMESTAMP',
            TableSchemaInterface::TYPE_TIMESTAMP_TIMEZONE => ' TIMESTAMP',
            TableSchemaInterface::TYPE_CHAR => ' CHAR',
            TableSchemaInterface::TYPE_UUID => ' CHAR(36)',
            TableSchemaInterface::TYPE_NATIVE_UUID => ' UUID',
            TableSchemaInterface::TYPE_JSON => $nativeJson ? ' JSON' : ' LONGTEXT',
            TableSchemaInterface::TYPE_GEOMETRY => ' GEOMETRY',
            TableSchemaInterface::TYPE_POINT => ' POINT',
            TableSchemaInterface::TYPE_LINESTRING => ' LINESTRING',
            TableSchemaInterface::TYPE_POLYGON => ' POLYGON',
        ];
        $specialMap = [
            'string' => true,
            'text' => true,
            'char' => true,
            'binary' => true,
        ];
        if (isset($typeMap[$column['type']])) {
            $out .= $typeMap[$column['type']];
        }
        if (isset($specialMap[$column['type']])) {
            switch ($column['type']) {
                case TableSchemaInterface::TYPE_STRING:
                    $out .= ' VARCHAR';
                    if (!isset($column['length'])) {
                        $column['length'] = 255;
                    }
                    break;
                case TableSchemaInterface::TYPE_TEXT:
                    $isKnownLength = in_array($column['length'], TableSchema::$columnLengths);
                    if (empty($column['length']) || !$isKnownLength) {
                        $out .= ' TEXT';
                        break;
                    }

                    $length = array_search($column['length'], TableSchema::$columnLengths);
                    assert(is_string($length));
                    $out .= ' ' . strtoupper($length) . 'TEXT';

                    break;
                case TableSchemaInterface::TYPE_BINARY:
                    $isKnownLength = in_array($column['length'], TableSchema::$columnLengths);
                    if ($isKnownLength) {
                        $length = array_search($column['length'], TableSchema::$columnLengths);
                        assert(is_string($length));
                        $out .= ' ' . strtoupper($length) . 'BLOB';
                        break;
                    }

                    if (empty($column['length'])) {
                        $out .= ' BLOB';
                        break;
                    }

                    if ($column['length'] > 2) {
                        $out .= ' VARBINARY(' . $column['length'] . ')';
                    } else {
                        $out .= ' BINARY(' . $column['length'] . ')';
                    }
                    break;
            }
        }
        $hasLength = [
            TableSchemaInterface::TYPE_INTEGER,
            TableSchemaInterface::TYPE_CHAR,
            TableSchemaInterface::TYPE_SMALLINTEGER,
            TableSchemaInterface::TYPE_TINYINTEGER,
            TableSchemaInterface::TYPE_STRING,
        ];
        if (in_array($column['type'], $hasLength, true) && isset($column['length'])) {
            $out .= '(' . $column['length'] . ')';
        }

        $lengthAndPrecisionTypes = [
            TableSchemaInterface::TYPE_FLOAT,
            TableSchemaInterface::TYPE_DECIMAL,
        ];
        if (in_array($column['type'], $lengthAndPrecisionTypes, true) && isset($column['length'])) {
            if (isset($column['precision'])) {
                $out .= '(' . (int)$column['length'] . ',' . (int)$column['precision'] . ')';
            } else {
                $out .= '(' . (int)$column['length'] . ')';
            }
        }

        $precisionTypes = [
            TableSchemaInterface::TYPE_DATETIME_FRACTIONAL,
            TableSchemaInterface::TYPE_TIMESTAMP_FRACTIONAL,
        ];
        if (in_array($column['type'], $precisionTypes, true) && isset($column['precision'])) {
            $out .= '(' . (int)$column['precision'] . ')';
        }

        $hasUnsigned = [
            TableSchemaInterface::TYPE_TINYINTEGER,
            TableSchemaInterface::TYPE_SMALLINTEGER,
            TableSchemaInterface::TYPE_INTEGER,
            TableSchemaInterface::TYPE_BIGINTEGER,
            TableSchemaInterface::TYPE_FLOAT,
            TableSchemaInterface::TYPE_DECIMAL,
        ];
        if (
            in_array($column['type'], $hasUnsigned, true) &&
            isset($column['unsigned']) &&
            $column['unsigned'] === true
        ) {
            $out .= ' UNSIGNED';
        }

        $hasCollate = [
            TableSchemaInterface::TYPE_TEXT,
            TableSchemaInterface::TYPE_CHAR,
            TableSchemaInterface::TYPE_STRING,
        ];
        if (in_array($column['type'], $hasCollate, true) && isset($column['collate']) && $column['collate'] !== '') {
            $out .= ' COLLATE ' . $column['collate'];
        }

        if (isset($column['null']) && $column['null'] === false) {
            $out .= ' NOT NULL';
        }

        if (isset($column['autoIncrement']) && $column['autoIncrement']) {
            $out .= ' AUTO_INCREMENT';
            unset($column['default']);
        }

        $timestampTypes = [
            TableSchemaInterface::TYPE_TIMESTAMP,
            TableSchemaInterface::TYPE_TIMESTAMP_FRACTIONAL,
            TableSchemaInterface::TYPE_TIMESTAMP_TIMEZONE,
        ];
        if (isset($column['null']) && $column['null'] === true && in_array($column['type'], $timestampTypes, true)) {
            $out .= ' NULL';
            unset($column['default']);
        }
        if (isset($column['srid']) && in_array($column['type'], TableSchemaInterface::GEOSPATIAL_TYPES)) {
            $out .= " SRID {$column['srid']}";
        }

        $defaultExpressionTypes = array_merge(
            TableSchemaInterface::GEOSPATIAL_TYPES,
            [TableSchemaInterface::TYPE_BINARY, TableSchemaInterface::TYPE_TEXT, TableSchemaInterface::TYPE_JSON],
        );
        if (in_array($column['type'], $defaultExpressionTypes) && isset($column['default'])) {
            // Geospatial, blob and text types need to be wrapped in () to create an expression.
            $out .= ' DEFAULT (' . $this->_driver->schemaValue($column['default']) . ')';
            unset($column['default']);
        }

        $dateTimeTypes = [
            TableSchemaInterface::TYPE_DATETIME,
            TableSchemaInterface::TYPE_DATETIME_FRACTIONAL,
            TableSchemaInterface::TYPE_TIMESTAMP,
            TableSchemaInterface::TYPE_TIMESTAMP_FRACTIONAL,
            TableSchemaInterface::TYPE_TIMESTAMP_TIMEZONE,
        ];
        if (
            isset($column['default']) &&
            in_array($column['type'], $dateTimeTypes) &&
            str_contains(strtolower($column['default']), 'current_timestamp')
        ) {
            $out .= ' DEFAULT CURRENT_TIMESTAMP';
            if (isset($column['precision'])) {
                $out .= '(' . $column['precision'] . ')';
            }
            unset($column['default']);
        }
        if (isset($column['default'])) {
            $out .= ' DEFAULT ' . $this->_driver->schemaValue($column['default']);
            unset($column['default']);
        }
        if (isset($column['comment']) && $column['comment'] !== '') {
            $out .= ' COMMENT ' . $this->_driver->schemaValue($column['comment']);
        }
        if (isset($column['onUpdate']) && $column['onUpdate'] !== '') {
            $out .= ' ON UPDATE ' . $column['onUpdate'];
        }

        return $out;
    }

    /**
     * @inheritDoc
     */
    public function columnSql(TableSchema $schema, string $name): string
    {
        $data = $schema->getColumn($name);
        assert($data !== null);

        // TODO deprecrate Type defined schema mappings?
        $sql = $this->_getTypeSpecificColumnSql($data['type'], $schema, $name);
        if ($sql !== null) {
            return $sql;
        }
        $data['name'] = $name;

        $autoIncrementTypes = [
            TableSchemaInterface::TYPE_TINYINTEGER,
            TableSchemaInterface::TYPE_SMALLINTEGER,
            TableSchemaInterface::TYPE_INTEGER,
            TableSchemaInterface::TYPE_BIGINTEGER,
        ];
        if (
            in_array($data['type'], $autoIncrementTypes, true) &&
            $schema->getPrimaryKey() === [$name] &&
            $name === 'id'
        ) {
            $data['autoIncrement'] = true;
        }

        return $this->columnDefinitionSql($data);
    }

    /**
     * @inheritDoc
     */
    public function constraintSql(TableSchema $schema, string $name): string
    {
        $data = $schema->getConstraint($name);
        assert($data !== null);
        if ($data['type'] === TableSchema::CONSTRAINT_PRIMARY) {
            $columns = array_map(
                $this->_driver->quoteIdentifier(...),
                $data['columns'],
            );

            return sprintf('PRIMARY KEY (%s)', implode(', ', $columns));
        }

        $out = '';
        if ($data['type'] === TableSchema::CONSTRAINT_UNIQUE) {
            $out = 'UNIQUE KEY ';
        }
        if ($data['type'] === TableSchema::CONSTRAINT_FOREIGN) {
            $out = 'CONSTRAINT ';
        }
        $out .= $this->_driver->quoteIdentifier($name);

        return $this->_keySql($out, $data);
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
        $sqlPattern = 'ALTER TABLE %s DROP FOREIGN KEY %s;';
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
        $out = '';
        if ($data['type'] === TableSchema::INDEX_INDEX) {
            $out = 'KEY ';
        }
        if ($data['type'] === TableSchema::INDEX_FULLTEXT) {
            $out = 'FULLTEXT KEY ';
        }
        $out .= $this->_driver->quoteIdentifier($name);

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
        foreach ($data['columns'] as $i => $column) {
            if (isset($data['length'][$column])) {
                $columns[$i] .= sprintf('(%d)', $data['length'][$column]);
            }
        }
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
}
