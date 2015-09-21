<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Schema;

use Cake\Database\Exception;

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
        $sql =
        'SELECT DISTINCT table_schema AS schema, column_name AS name, data_type AS type,
            is_nullable AS null, column_default AS default,
            character_maximum_length AS char_length,
            d.description as comment,
            ordinal_position
        FROM information_schema.columns c
        INNER JOIN pg_catalog.pg_namespace ns ON (ns.nspname = table_schema)
        INNER JOIN pg_catalog.pg_class cl ON (cl.relnamespace = ns.oid AND cl.relname = table_name)
        LEFT JOIN pg_catalog.pg_index i ON (i.indrelid = cl.oid AND i.indkey[0] = c.ordinal_position)
        LEFT JOIN pg_catalog.pg_description d on (cl.oid = d.objoid AND d.objsubid = c.ordinal_position)
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
            return ['type' => 'timestamp', 'length' => null];
        }
        if (strpos($col, 'time') !== false) {
            return ['type' => 'time', 'length' => null];
        }
        if ($col === 'serial' || $col === 'integer') {
            return ['type' => 'integer', 'length' => 10];
        }
        if ($col === 'bigserial' || $col === 'bigint') {
            return ['type' => 'biginteger', 'length' => 20];
        }
        if ($col === 'smallint') {
            return ['type' => 'integer', 'length' => 5];
        }
        if ($col === 'inet') {
            return ['type' => 'string', 'length' => 39];
        }
        if ($col === 'uuid') {
            return ['type' => 'uuid', 'length' => null];
        }
        if ($col === 'char' || $col === 'character') {
            return ['type' => 'string', 'fixed' => true, 'length' => $length];
        }
        // money is 'string' as it includes arbitrary text content
        // before the number value.
        if (strpos($col, 'char') !== false ||
            strpos($col, 'money') !== false
        ) {
            return ['type' => 'string', 'length' => $length];
        }
        if (strpos($col, 'text') !== false) {
            return ['type' => 'text', 'length' => null];
        }
        if ($col === 'bytea') {
            return ['type' => 'binary', 'length' => null];
        }
        if ($col === 'real' || strpos($col, 'double') !== false) {
            return ['type' => 'float', 'length' => null];
        }
        if (strpos($col, 'numeric') !== false ||
            strpos($col, 'decimal') !== false
        ) {
            return ['type' => 'decimal', 'length' => null];
        }
        return ['type' => 'text', 'length' => null];
    }

    /**
     * {@inheritDoc}
     */
    public function convertColumnDescription(Table $table, $row)
    {
        $field = $this->_convertColumn($row['type']);

        if ($field['type'] === 'boolean') {
            if ($row['default'] === 'true') {
                $row['default'] = 1;
            }
            if ($row['default'] === 'false') {
                $row['default'] = 0;
            }
        }
        // Sniff out serial types.
        if (in_array($field['type'], ['integer', 'biginteger']) && strpos($row['default'], 'nextval(') === 0) {
            $field['autoIncrement'] = true;
        }
        $field += [
            'default' => $this->_defaultValue($row['default']),
            'null' => $row['null'] === 'YES' ? true : false,
            'comment' => $row['comment']
        ];
        $field['length'] = $row['char_length'] ?: $field['length'];
        $table->addColumn($row['name'], $field);
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

        // Remove quotes and postgres casts
        return preg_replace(
            "/^'(.*)'(?:::.*)$/",
            "$1",
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
            i.indisprimary,
            i.indisunique,
            i.indisvalid,
            pg_catalog.pg_get_indexdef(i.indexrelid, 0, true) AS statement
        FROM pg_catalog.pg_class AS c,
            pg_catalog.pg_class AS c2,
            pg_catalog.pg_index AS i
        WHERE c.oid  = (
            SELECT c.oid
            FROM pg_catalog.pg_class c
            LEFT JOIN pg_catalog.pg_namespace AS n ON n.oid = c.relnamespace
            WHERE c.relname = ?
                AND n.nspname = ?
        )
        AND c.oid = i.indrelid
        AND i.indexrelid = c2.oid
        ORDER BY i.indisprimary DESC, i.indisunique DESC, c2.relname';

        $schema = 'public';
        if (!empty($config['schema'])) {
            $schema = $config['schema'];
        }
        return [$sql, [$tableName, $schema]];
    }

    /**
     * {@inheritDoc}
     */
    public function convertIndexDescription(Table $table, $row)
    {
        $type = Table::INDEX_INDEX;
        $name = $row['relname'];
        if ($row['indisprimary']) {
            $name = $type = Table::CONSTRAINT_PRIMARY;
        }
        if ($row['indisunique'] && $type === Table::INDEX_INDEX) {
            $type = Table::CONSTRAINT_UNIQUE;
        }
        preg_match('/\(([^\)]+)\)/', $row['statement'], $matches);
        $columns = $this->_convertColumnList($matches[1]);
        if ($type === Table::CONSTRAINT_PRIMARY || $type === Table::CONSTRAINT_UNIQUE) {
            $table->addConstraint($name, [
                'type' => $type,
                'columns' => $columns
            ]);

            // If there is only one column in the primary key and it is integery,
            // make it autoincrement.
            $columnDef = $table->column($columns[0]);

            if ($type === Table::CONSTRAINT_PRIMARY &&
                count($columns) === 1 &&
                in_array($columnDef['type'], ['integer', 'biginteger'])
            ) {
                $columnDef['autoIncrement'] = true;
                $table->addColumn($columns[0], $columnDef);
            }
            return;
        }
        $table->addIndex($name, [
            'type' => $type,
            'columns' => $columns
        ]);
    }

    /**
     * Convert a column list into a clean array.
     *
     * @param string $columns comma separated column list.
     * @return array
     */
    protected function _convertColumnList($columns)
    {
        $columns = explode(', ', $columns);
        foreach ($columns as &$column) {
            $column = trim($column, '"');
        }
        return $columns;
    }

    /**
     * {@inheritDoc}
     */
    public function describeForeignKeySql($tableName, $config)
    {
        $sql = "SELECT
            rc.constraint_name AS name,
            tc.constraint_type AS type,
            kcu.column_name,
            rc.match_option AS match_type,
            rc.update_rule AS on_update,
            rc.delete_rule AS on_delete,

            kc.table_name AS references_table,
            kc.column_name AS references_field

            FROM information_schema.referential_constraints rc

            JOIN information_schema.table_constraints tc
                ON tc.constraint_name = rc.constraint_name
                AND tc.constraint_schema = rc.constraint_schema
                AND tc.constraint_name = rc.constraint_name

            JOIN information_schema.key_column_usage kcu
                ON kcu.constraint_name = rc.constraint_name
                AND kcu.constraint_schema = rc.constraint_schema
                AND kcu.constraint_name = rc.constraint_name

            JOIN information_schema.key_column_usage kc
                ON kc.ordinal_position = kcu.position_in_unique_constraint
                AND kc.constraint_name = rc.unique_constraint_name

            WHERE kcu.table_name = ?
              AND kc.table_schema = ?
              AND tc.constraint_type = 'FOREIGN KEY'

            ORDER BY rc.constraint_name, kcu.ordinal_position";

        $schema = empty($config['schema']) ? 'public' : $config['schema'];
        return [$sql, [$tableName, $schema]];
    }

    /**
     * {@inheritDoc}
     */
    public function convertForeignKeyDescription(Table $table, $row)
    {
        $data = [
            'type' => Table::CONSTRAINT_FOREIGN,
            'columns' => $row['column_name'],
            'references' => [$row['references_table'], $row['references_field']],
            'update' => $this->_convertOnClause($row['on_update']),
            'delete' => $this->_convertOnClause($row['on_delete']),
        ];
        $table->addConstraint($row['name'], $data);
    }

    /**
     * {@inheritDoc}
     */
    protected function _convertOnClause($clause)
    {
        if ($clause === 'RESTRICT') {
            return Table::ACTION_RESTRICT;
        }
        if ($clause === 'NO ACTION') {
            return Table::ACTION_NO_ACTION;
        }
        if ($clause === 'CASCADE') {
            return Table::ACTION_CASCADE;
        }
        return Table::ACTION_SET_NULL;
    }

    /**
     * {@inheritDoc}
     */
    public function columnSql(Table $table, $name)
    {
        $data = $table->column($name);
        $out = $this->_driver->quoteIdentifier($name);
        $typeMap = [
            'boolean' => ' BOOLEAN',
            'binary' => ' BYTEA',
            'float' => ' FLOAT',
            'decimal' => ' DECIMAL',
            'text' => ' TEXT',
            'date' => ' DATE',
            'time' => ' TIME',
            'datetime' => ' TIMESTAMP',
            'timestamp' => ' TIMESTAMP',
            'uuid' => ' UUID',
        ];

        if (isset($typeMap[$data['type']])) {
            $out .= $typeMap[$data['type']];
        }

        if ($data['type'] === 'integer' || $data['type'] === 'biginteger') {
            $type = $data['type'] === 'integer' ? ' INTEGER' : ' BIGINT';
            if ([$name] === $table->primaryKey() || $data['autoIncrement'] === true) {
                $type = $data['type'] === 'integer' ? ' SERIAL' : ' BIGSERIAL';
                unset($data['null'], $data['default']);
            }
            $out .= $type;
        }

        if ($data['type'] === 'string') {
            $isFixed = !empty($data['fixed']);
            $type = ' VARCHAR';
            if ($isFixed) {
                $type = ' CHAR';
            }
            $out .= $type;
            if (isset($data['length']) && $data['length'] != 36) {
                $out .= '(' . (int)$data['length'] . ')';
            }
        }

        if ($data['type'] === 'float' && isset($data['precision'])) {
            $out .= '(' . (int)$data['precision'] . ')';
        }

        if ($data['type'] === 'decimal' &&
            (isset($data['length']) || isset($data['precision']))
        ) {
            $out .= '(' . (int)$data['length'] . ',' . (int)$data['precision'] . ')';
        }

        if (isset($data['null']) && $data['null'] === false) {
            $out .= ' NOT NULL';
        }
        if (isset($data['null']) && $data['null'] === true) {
            $out .= ' DEFAULT NULL';
            unset($data['default']);
        }
        if (isset($data['default']) && $data['type'] !== 'timestamp') {
            $defaultValue = $data['default'];
            if ($data['type'] === 'boolean') {
                $defaultValue = (bool)$defaultValue;
            }
            $out .= ' DEFAULT ' . $this->_driver->schemaValue($defaultValue);
        }
        return $out;
    }

    /**
     * {@inheritDoc}
     */
    public function indexSql(Table $table, $name)
    {
        $data = $table->index($name);
        $columns = array_map(
            [$this->_driver, 'quoteIdentifier'],
            $data['columns']
        );
        return sprintf(
            'CREATE INDEX %s ON %s (%s)',
            $this->_driver->quoteIdentifier($name),
            $this->_driver->quoteIdentifier($table->name()),
            implode(', ', $columns)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function constraintSql(Table $table, $name)
    {
        $data = $table->constraint($name);
        $out = 'CONSTRAINT ' . $this->_driver->quoteIdentifier($name);
        if ($data['type'] === Table::CONSTRAINT_PRIMARY) {
            $out = 'PRIMARY KEY';
        }
        if ($data['type'] === Table::CONSTRAINT_UNIQUE) {
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
        if ($data['type'] === Table::CONSTRAINT_FOREIGN) {
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
    public function createTableSql(Table $table, $columns, $constraints, $indexes)
    {
        $content = array_merge($columns, $constraints);
        $content = implode(",\n", array_filter($content));
        $tableName = $this->_driver->quoteIdentifier($table->name());
        $temporary = $table->temporary() ? ' TEMPORARY ' : ' ';
        $out = [];
        $out[] = sprintf("CREATE%sTABLE %s (\n%s\n)", $temporary, $tableName, $content);
        foreach ($indexes as $index) {
            $out[] = $index;
        }
        foreach ($table->columns() as $column) {
            $columnData = $table->column($column);
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
    public function truncateTableSql(Table $table)
    {
        $name = $this->_driver->quoteIdentifier($table->name());
        return [
            sprintf('TRUNCATE %s RESTART IDENTITY CASCADE', $name)
        ];
    }

    /**
     * Generate the SQL to drop a table.
     *
     * @param \Cake\Database\Schema\Table $table Table instance
     * @return array SQL statements to drop a table.
     */
    public function dropTableSql(Table $table)
    {
        $sql = sprintf(
            'DROP TABLE %s CASCADE',
            $this->_driver->quoteIdentifier($table->name())
        );
        return [$sql];
    }
}
