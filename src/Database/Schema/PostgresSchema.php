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
namespace Cake\Database\Schema;

use Cake\Database\Exception;
use Cake\Database\Schema\TableSchema;

/**
 * Schema management/reflection features for Postgres.
 */
class PostgresSchema extends BaseSchema
{

    /**
     * {@inheritDoc}
     */
    public function listTablesSql($config)
    {
        $sql = 'SELECT table_name as name FROM information_schema.tables WHERE table_schema = ? ORDER BY name';
        $schema = empty($config['schema']) ? 'public' : $config['schema'];

        return [$sql, [$schema]];
    }

    /**
     * {@inheritDoc}
     */
    public function describeColumnSql($tableName, $config)
    {
        $sql = 'SELECT DISTINCT table_schema AS schema,
            column_name AS name,
            data_type AS type,
            is_nullable AS null, column_default AS default,
            character_maximum_length AS char_length,
            c.collation_name,
            d.description as comment,
            ordinal_position,
            c.numeric_precision as column_precision,
            c.numeric_scale as column_scale,
            pg_get_serial_sequence(attr.attrelid::regclass::text, attr.attname) IS NOT NULL AS has_serial
        FROM information_schema.columns c
        INNER JOIN pg_catalog.pg_namespace ns ON (ns.nspname = table_schema)
        INNER JOIN pg_catalog.pg_class cl ON (cl.relnamespace = ns.oid AND cl.relname = table_name)
        LEFT JOIN pg_catalog.pg_index i ON (i.indrelid = cl.oid AND i.indkey[0] = c.ordinal_position)
        LEFT JOIN pg_catalog.pg_description d on (cl.oid = d.objoid AND d.objsubid = c.ordinal_position)
        LEFT JOIN pg_catalog.pg_attribute attr ON (cl.oid = attr.attrelid AND column_name = attr.attname)
        WHERE table_name = ? AND table_schema = ? AND table_catalog = ?
        ORDER BY ordinal_position';

        $schema = empty($config['schema']) ? 'public' : $config['schema'];

        return [$sql, [$tableName, $schema, $config['database']]];
    }

    /**
     * Convert a column definition to the abstract types.
     *
     * The returned type will be a type that
     * Cake\Database\Type can handle.
     *
     * @param string $column The column type + length
     * @throws \Cake\Database\Exception when column cannot be parsed.
     * @return array Array of column information.
     */
    protected function _convertColumn($column)
    {
        preg_match('/([a-z\s]+)(?:\(([0-9,]+)\))?/i', $column, $matches);
        if (empty($matches)) {
            throw new Exception(sprintf('Unable to parse column type from "%s"', $column));
        }

        $col = strtolower($matches[1]);
        $length = null;
        if (isset($matches[2])) {
            $length = (int)$matches[2];
        }

        if (in_array($col, ['date', 'time', 'boolean'])) {
            return ['type' => $col, 'length' => null];
        }
        if (strpos($col, 'timestamp') !== false) {
            return ['type' => TableSchema::TYPE_TIMESTAMP, 'length' => null];
        }
        if (strpos($col, 'time') !== false) {
            return ['type' => TableSchema::TYPE_TIME, 'length' => null];
        }
        if ($col === 'serial' || $col === 'integer') {
            return ['type' => TableSchema::TYPE_INTEGER, 'length' => 10];
        }
        if ($col === 'bigserial' || $col === 'bigint') {
            return ['type' => TableSchema::TYPE_BIGINTEGER, 'length' => 20];
        }
        if ($col === 'smallint') {
            return ['type' => TableSchema::TYPE_SMALLINTEGER, 'length' => 5];
        }
        if ($col === 'inet') {
            return ['type' => TableSchema::TYPE_STRING, 'length' => 39];
        }
        if ($col === 'uuid') {
            return ['type' => TableSchema::TYPE_UUID, 'length' => null];
        }
        if ($col === 'char' || $col === 'character') {
            return ['type' => TableSchema::TYPE_STRING, 'fixed' => true, 'length' => $length];
        }
        // money is 'string' as it includes arbitrary text content
        // before the number value.
        if (strpos($col, 'char') !== false ||
            strpos($col, 'money') !== false
        ) {
            return ['type' => TableSchema::TYPE_STRING, 'length' => $length];
        }
        if (strpos($col, 'text') !== false) {
            return ['type' => TableSchema::TYPE_TEXT, 'length' => null];
        }
        if ($col === 'bytea') {
            return ['type' => TableSchema::TYPE_BINARY, 'length' => null];
        }
        if ($col === 'real' || strpos($col, 'double') !== false) {
            return ['type' => TableSchema::TYPE_FLOAT, 'length' => null];
        }
        if (strpos($col, 'numeric') !== false ||
            strpos($col, 'decimal') !== false
        ) {
            return ['type' => TableSchema::TYPE_DECIMAL, 'length' => null];
        }

        if (strpos($col, 'json') !== false) {
            return ['type' => TableSchema::TYPE_JSON, 'length' => null];
        }

        return ['type' => TableSchema::TYPE_STRING, 'length' => null];
    }

    /**
     * {@inheritDoc}
     */
    public function convertColumnDescription(TableSchema $schema, $row)
    {
        $field = $this->_convertColumn($row['type']);

        if ($field['type'] === TableSchema::TYPE_BOOLEAN) {
            if ($row['default'] === 'true') {
                $row['default'] = 1;
            }
            if ($row['default'] === 'false') {
                $row['default'] = 0;
            }
        }
        if (!empty($row['has_serial'])) {
            $field['autoIncrement'] = true;
        }

        $field += [
            'default' => $this->_defaultValue($row['default']),
            'null' => $row['null'] === 'YES',
            'collate' => $row['collation_name'],
            'comment' => $row['comment']
        ];
        $field['length'] = $row['char_length'] ?: $field['length'];

        if ($field['type'] === 'numeric' || $field['type'] === 'decimal') {
            $field['length'] = $row['column_precision'];
            $field['precision'] = $row['column_scale'] ?: null;
        }
        $schema->addColumn($row['name'], $field);
    }

    /**
     * Manipulate the default value.
     *
     * Postgres includes sequence data and casting information in default values.
     * We need to remove those.
     *
     * @param string|null $default The default value.
     * @return string|null
     */
    protected function _defaultValue($default)
    {
        if (is_numeric($default) || $default === null) {
            return $default;
        }
        // Sequences
        if (strpos($default, 'nextval') === 0) {
            return null;
        }

        if (strpos($default, 'NULL::') === 0) {
            return null;
        }

        // Remove quotes and postgres casts
        return preg_replace(
            "/^'(.*)'(?:::.*)$/",
            '$1',
            $default
        );
    }

    /**
     * {@inheritDoc}
     */
    public function describeIndexSql($tableName, $config)
    {
        $sql = 'SELECT
        c2.relname,
        a.attname,
        i.indisprimary,
        i.indisunique
        FROM pg_catalog.pg_namespace n
        INNER JOIN pg_catalog.pg_class c ON (n.oid = c.relnamespace)
        INNER JOIN pg_catalog.pg_index i ON (c.oid = i.indrelid)
        INNER JOIN pg_catalog.pg_class c2 ON (c2.oid = i.indexrelid)
        INNER JOIN pg_catalog.pg_attribute a ON (a.attrelid = c.oid AND i.indrelid::regclass = a.attrelid::regclass)
        WHERE n.nspname = ?
        AND a.attnum = ANY(i.indkey)
        AND c.relname = ?
        ORDER BY i.indisprimary DESC, i.indisunique DESC, c.relname, a.attnum';

        $schema = 'public';
        if (!empty($config['schema'])) {
            $schema = $config['schema'];
        }

        return [$sql, [$schema, $tableName]];
    }

    /**
     * {@inheritDoc}
     */
    public function convertIndexDescription(TableSchema $schema, $row)
    {
        $type = TableSchema::INDEX_INDEX;
        $name = $row['relname'];
        if ($row['indisprimary']) {
            $name = $type = TableSchema::CONSTRAINT_PRIMARY;
        }
        if ($row['indisunique'] && $type === TableSchema::INDEX_INDEX) {
            $type = TableSchema::CONSTRAINT_UNIQUE;
        }
        if ($type === TableSchema::CONSTRAINT_PRIMARY || $type === TableSchema::CONSTRAINT_UNIQUE) {
            $this->_convertConstraint($schema, $name, $type, $row);

            return;
        }
        $index = $schema->getIndex($name);
        if (!$index) {
            $index = [
                'type' => $type,
                'columns' => []
            ];
        }
        $index['columns'][] = $row['attname'];
        $schema->addIndex($name, $index);
    }

    /**
     * Add/update a constraint into the schema object.
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table to update.
     * @param string $name The index name.
     * @param string $type The index type.
     * @param array $row The metadata record to update with.
     * @return void
     */
    protected function _convertConstraint($schema, $name, $type, $row)
    {
        $constraint = $schema->getConstraint($name);
        if (!$constraint) {
            $constraint = [
                'type' => $type,
                'columns' => []
            ];
        }
        $constraint['columns'][] = $row['attname'];
        $schema->addConstraint($name, $constraint);
    }

    /**
     * {@inheritDoc}
     */
    public function describeForeignKeySql($tableName, $config)
    {
        $sql = 'SELECT
        c.conname AS name,
        c.contype AS type,
        a.attname AS column_name,
        c.confmatchtype AS match_type,
        c.confupdtype AS on_update,
        c.confdeltype AS on_delete,
        c.confrelid::regclass AS references_table,
        ab.attname AS references_field
        FROM pg_catalog.pg_namespace n
        INNER JOIN pg_catalog.pg_class cl ON (n.oid = cl.relnamespace)
        INNER JOIN pg_catalog.pg_constraint c ON (n.oid = c.connamespace)
        INNER JOIN pg_catalog.pg_attribute a ON (a.attrelid = cl.oid AND c.conrelid = a.attrelid AND a.attnum = ANY(c.conkey))
        INNER JOIN pg_catalog.pg_attribute ab ON (a.attrelid = cl.oid AND c.confrelid = ab.attrelid AND ab.attnum = ANY(c.confkey))
        WHERE n.nspname = ?
        AND cl.relname = ?
        ORDER BY name, a.attnum, ab.attnum DESC';

        $schema = empty($config['schema']) ? 'public' : $config['schema'];

        return [$sql, [$schema, $tableName]];
    }

    /**
     * {@inheritDoc}
     */
    public function convertForeignKeyDescription(TableSchema $schema, $row)
    {
        $data = [
            'type' => TableSchema::CONSTRAINT_FOREIGN,
            'columns' => $row['column_name'],
            'references' => [$row['references_table'], $row['references_field']],
            'update' => $this->_convertOnClause($row['on_update']),
            'delete' => $this->_convertOnClause($row['on_delete']),
        ];
        $schema->addConstraint($row['name'], $data);
    }

    /**
     * {@inheritDoc}
     */
    protected function _convertOnClause($clause)
    {
        if ($clause === 'r') {
            return TableSchema::ACTION_RESTRICT;
        }
        if ($clause === 'a') {
            return TableSchema::ACTION_NO_ACTION;
        }
        if ($clause === 'c') {
            return TableSchema::ACTION_CASCADE;
        }

        return TableSchema::ACTION_SET_NULL;
    }

    /**
     * {@inheritDoc}
     */
    public function columnSql(TableSchema $schema, $name)
    {
        $data = $schema->getColumn($name);
        $out = $this->_driver->quoteIdentifier($name);
        $typeMap = [
            TableSchema::TYPE_TINYINTEGER => ' SMALLINT',
            TableSchema::TYPE_SMALLINTEGER => ' SMALLINT',
            TableSchema::TYPE_BINARY_UUID => ' UUID',
            TableSchema::TYPE_BOOLEAN => ' BOOLEAN',
            TableSchema::TYPE_FLOAT => ' FLOAT',
            TableSchema::TYPE_DECIMAL => ' DECIMAL',
            TableSchema::TYPE_DATE => ' DATE',
            TableSchema::TYPE_TIME => ' TIME',
            TableSchema::TYPE_DATETIME => ' TIMESTAMP',
            TableSchema::TYPE_TIMESTAMP => ' TIMESTAMP',
            TableSchema::TYPE_UUID => ' UUID',
            TableSchema::TYPE_JSON => ' JSONB'
        ];

        if (isset($typeMap[$data['type']])) {
            $out .= $typeMap[$data['type']];
        }

        if ($data['type'] === TableSchema::TYPE_INTEGER || $data['type'] === TableSchema::TYPE_BIGINTEGER) {
            $type = $data['type'] === TableSchema::TYPE_INTEGER ? ' INTEGER' : ' BIGINT';
            if ([$name] === $schema->primaryKey() || $data['autoIncrement'] === true) {
                $type = $data['type'] === TableSchema::TYPE_INTEGER ? ' SERIAL' : ' BIGSERIAL';
                unset($data['null'], $data['default']);
            }
            $out .= $type;
        }

        if ($data['type'] === TableSchema::TYPE_TEXT && $data['length'] !== TableSchema::LENGTH_TINY) {
            $out .= ' TEXT';
        }
        if ($data['type'] === TableSchema::TYPE_BINARY) {
            $out .= ' BYTEA';
        }

        if ($data['type'] === TableSchema::TYPE_STRING ||
            ($data['type'] === TableSchema::TYPE_TEXT && $data['length'] === TableSchema::LENGTH_TINY)
        ) {
            $isFixed = !empty($data['fixed']);
            $type = ' VARCHAR';
            if ($isFixed) {
                $type = ' CHAR';
            }
            $out .= $type;
            if (isset($data['length'])) {
                $out .= '(' . (int)$data['length'] . ')';
            }
        }

        $hasCollate = [TableSchema::TYPE_TEXT, TableSchema::TYPE_STRING];
        if (in_array($data['type'], $hasCollate, true) && isset($data['collate']) && $data['collate'] !== '') {
            $out .= ' COLLATE "' . $data['collate'] . '"';
        }

        if ($data['type'] === TableSchema::TYPE_FLOAT && isset($data['precision'])) {
            $out .= '(' . (int)$data['precision'] . ')';
        }

        if ($data['type'] === TableSchema::TYPE_DECIMAL &&
            (isset($data['length']) || isset($data['precision']))
        ) {
            $out .= '(' . (int)$data['length'] . ',' . (int)$data['precision'] . ')';
        }

        if (isset($data['null']) && $data['null'] === false) {
            $out .= ' NOT NULL';
        }

        if (isset($data['default']) &&
            in_array($data['type'], [TableSchema::TYPE_TIMESTAMP, TableSchema::TYPE_DATETIME]) &&
            strtolower($data['default']) === 'current_timestamp'
        ) {
            $out .= ' DEFAULT CURRENT_TIMESTAMP';
        } elseif (isset($data['default'])) {
            $defaultValue = $data['default'];
            if ($data['type'] === 'boolean') {
                $defaultValue = (bool)$defaultValue;
            }
            $out .= ' DEFAULT ' . $this->_driver->schemaValue($defaultValue);
        } elseif (isset($data['null']) && $data['null'] !== false) {
            $out .= ' DEFAULT NULL';
        }

        return $out;
    }

    /**
     * {@inheritDoc}
     */
    public function addConstraintSql(TableSchema $schema)
    {
        $sqlPattern = 'ALTER TABLE %s ADD %s;';
        $sql = [];

        foreach ($schema->constraints() as $name) {
            $constraint = $schema->getConstraint($name);
            if ($constraint['type'] === TableSchema::CONSTRAINT_FOREIGN) {
                $tableName = $this->_driver->quoteIdentifier($schema->name());
                $sql[] = sprintf($sqlPattern, $tableName, $this->constraintSql($schema, $name));
            }
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function dropConstraintSql(TableSchema $schema)
    {
        $sqlPattern = 'ALTER TABLE %s DROP CONSTRAINT %s;';
        $sql = [];

        foreach ($schema->constraints() as $name) {
            $constraint = $schema->getConstraint($name);
            if ($constraint['type'] === TableSchema::CONSTRAINT_FOREIGN) {
                $tableName = $this->_driver->quoteIdentifier($schema->name());
                $constraintName = $this->_driver->quoteIdentifier($name);
                $sql[] = sprintf($sqlPattern, $tableName, $constraintName);
            }
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function indexSql(TableSchema $schema, $name)
    {
        $data = $schema->getIndex($name);
        $columns = array_map(
            [$this->_driver, 'quoteIdentifier'],
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
     * {@inheritDoc}
     */
    public function constraintSql(TableSchema $schema, $name)
    {
        $data = $schema->getConstraint($name);
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
    protected function _keySql($prefix, $data)
    {
        $columns = array_map(
            [$this->_driver, 'quoteIdentifier'],
            $data['columns']
        );
        if ($data['type'] === TableSchema::CONSTRAINT_FOREIGN) {
            return $prefix . sprintf(
                ' FOREIGN KEY (%s) REFERENCES %s (%s) ON UPDATE %s ON DELETE %s DEFERRABLE INITIALLY IMMEDIATE',
                implode(', ', $columns),
                $this->_driver->quoteIdentifier($data['references'][0]),
                $this->_convertConstraintColumns($data['references'][1]),
                $this->_foreignOnClause($data['update']),
                $this->_foreignOnClause($data['delete'])
            );
        }

        return $prefix . ' (' . implode(', ', $columns) . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function createTableSql(TableSchema $schema, $columns, $constraints, $indexes)
    {
        $content = array_merge($columns, $constraints);
        $content = implode(",\n", array_filter($content));
        $tableName = $this->_driver->quoteIdentifier($schema->name());
        $temporary = $schema->isTemporary() ? ' TEMPORARY ' : ' ';
        $out = [];
        $out[] = sprintf("CREATE%sTABLE %s (\n%s\n)", $temporary, $tableName, $content);
        foreach ($indexes as $index) {
            $out[] = $index;
        }
        foreach ($schema->columns() as $column) {
            $columnData = $schema->getColumn($column);
            if (isset($columnData['comment'])) {
                $out[] = sprintf(
                    'COMMENT ON COLUMN %s.%s IS %s',
                    $tableName,
                    $this->_driver->quoteIdentifier($column),
                    $this->_driver->schemaValue($columnData['comment'])
                );
            }
        }

        return $out;
    }

    /**
     * {@inheritDoc}
     */
    public function truncateTableSql(TableSchema $schema)
    {
        $name = $this->_driver->quoteIdentifier($schema->name());

        return [
            sprintf('TRUNCATE %s RESTART IDENTITY CASCADE', $name)
        ];
    }

    /**
     * Generate the SQL to drop a table.
     *
     * @param \Cake\Database\Schema\TableSchema $schema Table instance
     * @return array SQL statements to drop a table.
     */
    public function dropTableSql(TableSchema $schema)
    {
        $sql = sprintf(
            'DROP TABLE %s CASCADE',
            $this->_driver->quoteIdentifier($schema->name())
        );

        return [$sql];
    }
}
