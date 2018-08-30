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
 * Schema management/reflection features for Sqlite
 */
class SqliteSchema extends BaseSchema
{

    /**
     * Array containing the foreign keys constraints names
     * Necessary for composite foreign keys to be handled
     *
     * @var array
     */
    protected $_constraintsIdMap = [];

    /**
     * Whether there is any table in this connection to SQLite containing sequences.
     *
     * @var bool
     */
    protected $_hasSequences;

    /**
     * Convert a column definition to the abstract types.
     *
     * The returned type will be a type that
     * Cake\Database\Type can handle.
     *
     * @param string $column The column type + length
     * @throws \Cake\Database\Exception when unable to parse column type
     * @return array Array of column information.
     */
    protected function _convertColumn($column)
    {
        preg_match('/(unsigned)?\s*([a-z]+)(?:\(([0-9,]+)\))?/i', $column, $matches);
        if (empty($matches)) {
            throw new Exception(sprintf('Unable to parse column type from "%s"', $column));
        }

        $unsigned = false;
        if (strtolower($matches[1]) === 'unsigned') {
            $unsigned = true;
        }

        $col = strtolower($matches[2]);
        $length = null;
        if (isset($matches[3])) {
            $length = (int)$matches[3];
        }

        if ($col === 'bigint') {
            return ['type' => TableSchema::TYPE_BIGINTEGER, 'length' => $length, 'unsigned' => $unsigned];
        }
        if ($col == 'smallint') {
            return ['type' => TableSchema::TYPE_SMALLINTEGER, 'length' => $length, 'unsigned' => $unsigned];
        }
        if ($col == 'tinyint') {
            return ['type' => TableSchema::TYPE_TINYINTEGER, 'length' => $length, 'unsigned' => $unsigned];
        }
        if (strpos($col, 'int') !== false) {
            return ['type' => TableSchema::TYPE_INTEGER, 'length' => $length, 'unsigned' => $unsigned];
        }
        if (strpos($col, 'decimal') !== false) {
            return ['type' => TableSchema::TYPE_DECIMAL, 'length' => null, 'unsigned' => $unsigned];
        }
        if (in_array($col, ['float', 'real', 'double'])) {
            return ['type' => TableSchema::TYPE_FLOAT, 'length' => null, 'unsigned' => $unsigned];
        }

        if (strpos($col, 'boolean') !== false) {
            return ['type' => TableSchema::TYPE_BOOLEAN, 'length' => null];
        }

        if ($col === 'char' && $length === 36) {
            return ['type' => TableSchema::TYPE_UUID, 'length' => null];
        }
        if ($col === 'char') {
            return ['type' => TableSchema::TYPE_STRING, 'fixed' => true, 'length' => $length];
        }
        if (strpos($col, 'char') !== false) {
            return ['type' => TableSchema::TYPE_STRING, 'length' => $length];
        }

        if ($col === 'binary' && $length === 16) {
            return ['type' => TableSchema::TYPE_BINARY_UUID, 'length' => null];
        }
        if (in_array($col, ['blob', 'clob', 'binary'])) {
            return ['type' => TableSchema::TYPE_BINARY, 'length' => $length];
        }
        if (in_array($col, ['date', 'time', 'timestamp', 'datetime'])) {
            return ['type' => $col, 'length' => null];
        }

        return ['type' => TableSchema::TYPE_TEXT, 'length' => null];
    }

    /**
     * {@inheritDoc}
     */
    public function listTablesSql($config)
    {
        return [
            'SELECT name FROM sqlite_master WHERE type="table" ' .
            'AND name != "sqlite_sequence" ORDER BY name',
            []
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function describeColumnSql($tableName, $config)
    {
        $sql = sprintf(
            'PRAGMA table_info(%s)',
            $this->_driver->quoteIdentifier($tableName)
        );

        return [$sql, []];
    }

    /**
     * {@inheritDoc}
     */
    public function convertColumnDescription(TableSchema $schema, $row)
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
                'columns' => []
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
     * @param string|null $default The default value.
     * @return string|null
     */
    protected function _defaultValue($default)
    {
        if ($default === 'NULL') {
            return null;
        }

        // Remove quotes
        if (preg_match("/^'(.*)'$/", $default, $matches)) {
            return str_replace("''", "'", $matches[1]);
        }

        return $default;
    }

    /**
     * {@inheritDoc}
     */
    public function describeIndexSql($tableName, $config)
    {
        $sql = sprintf(
            'PRAGMA index_list(%s)',
            $this->_driver->quoteIdentifier($tableName)
        );

        return [$sql, []];
    }

    /**
     * {@inheritDoc}
     *
     * Since SQLite does not have a way to get metadata about all indexes at once,
     * additional queries are done here. Sqlite constraint names are not
     * stable, and the names for constraints will not match those used to create
     * the table. This is a limitation in Sqlite's metadata features.
     *
     */
    public function convertIndexDescription(TableSchema $schema, $row)
    {
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
        $statement->closeCursor();
        if ($row['unique']) {
            $schema->addConstraint($row['name'], [
                'type' => TableSchema::CONSTRAINT_UNIQUE,
                'columns' => $columns
            ]);
        } else {
            $schema->addIndex($row['name'], [
                'type' => TableSchema::INDEX_INDEX,
                'columns' => $columns
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function describeForeignKeySql($tableName, $config)
    {
        $sql = sprintf('PRAGMA foreign_key_list(%s)', $this->_driver->quoteIdentifier($tableName));

        return [$sql, []];
    }

    /**
     * {@inheritDoc}
     */
    public function convertForeignKeyDescription(TableSchema $schema, $row)
    {
        $name = $row['from'] . '_fk';

        $update = isset($row['on_update']) ? $row['on_update'] : '';
        $delete = isset($row['on_delete']) ? $row['on_delete'] : '';
        $data = [
            'type' => TableSchema::CONSTRAINT_FOREIGN,
            'columns' => [$row['from']],
            'references' => [$row['table'], $row['to']],
            'update' => $this->_convertOnClause($update),
            'delete' => $this->_convertOnClause($delete),
        ];

        if (isset($this->_constraintsIdMap[$schema->name()][$row['id']])) {
            $name = $this->_constraintsIdMap[$schema->name()][$row['id']];
        } else {
            $this->_constraintsIdMap[$schema->name()][$row['id']] = $name;
        }

        $schema->addConstraint($name, $data);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Cake\Database\Exception when the column type is unknown
     */
    public function columnSql(TableSchema $schema, $name)
    {
        $data = $schema->getColumn($name);
        $typeMap = [
            TableSchema::TYPE_BINARY_UUID => ' BINARY(16)',
            TableSchema::TYPE_UUID => ' CHAR(36)',
            TableSchema::TYPE_TINYINTEGER => ' TINYINT',
            TableSchema::TYPE_SMALLINTEGER => ' SMALLINT',
            TableSchema::TYPE_INTEGER => ' INTEGER',
            TableSchema::TYPE_BIGINTEGER => ' BIGINT',
            TableSchema::TYPE_BOOLEAN => ' BOOLEAN',
            TableSchema::TYPE_BINARY => ' BLOB',
            TableSchema::TYPE_FLOAT => ' FLOAT',
            TableSchema::TYPE_DECIMAL => ' DECIMAL',
            TableSchema::TYPE_DATE => ' DATE',
            TableSchema::TYPE_TIME => ' TIME',
            TableSchema::TYPE_DATETIME => ' DATETIME',
            TableSchema::TYPE_TIMESTAMP => ' TIMESTAMP',
            TableSchema::TYPE_JSON => ' TEXT'
        ];

        $out = $this->_driver->quoteIdentifier($name);
        $hasUnsigned = [
            TableSchema::TYPE_TINYINTEGER,
            TableSchema::TYPE_SMALLINTEGER,
            TableSchema::TYPE_INTEGER,
            TableSchema::TYPE_BIGINTEGER,
            TableSchema::TYPE_FLOAT,
            TableSchema::TYPE_DECIMAL
        ];

        if (in_array($data['type'], $hasUnsigned, true) &&
            isset($data['unsigned']) && $data['unsigned'] === true
        ) {
            if ($data['type'] !== TableSchema::TYPE_INTEGER || [$name] !== (array)$schema->primaryKey()) {
                $out .= ' UNSIGNED';
            }
        }

        if (isset($typeMap[$data['type']])) {
            $out .= $typeMap[$data['type']];
        }

        if ($data['type'] === TableSchema::TYPE_TEXT && $data['length'] !== TableSchema::LENGTH_TINY) {
            $out .= ' TEXT';
        }

        if ($data['type'] === TableSchema::TYPE_STRING ||
            ($data['type'] === TableSchema::TYPE_TEXT && $data['length'] === TableSchema::LENGTH_TINY)
        ) {
            $out .= ' VARCHAR';

            if (isset($data['length'])) {
                $out .= '(' . (int)$data['length'] . ')';
            }
        }

        $integerTypes = [
            TableSchema::TYPE_TINYINTEGER,
            TableSchema::TYPE_SMALLINTEGER,
            TableSchema::TYPE_INTEGER,
        ];
        if (in_array($data['type'], $integerTypes, true) &&
            isset($data['length']) && [$name] !== (array)$schema->primaryKey()
        ) {
                $out .= '(' . (int)$data['length'] . ')';
        }

        $hasPrecision = [TableSchema::TYPE_FLOAT, TableSchema::TYPE_DECIMAL];
        if (in_array($data['type'], $hasPrecision, true) &&
            (isset($data['length']) || isset($data['precision']))
        ) {
            $out .= '(' . (int)$data['length'] . ',' . (int)$data['precision'] . ')';
        }

        if (isset($data['null']) && $data['null'] === false) {
            $out .= ' NOT NULL';
        }

        if ($data['type'] === TableSchema::TYPE_INTEGER && [$name] === (array)$schema->primaryKey()) {
            $out .= ' PRIMARY KEY AUTOINCREMENT';
        }

        if (isset($data['null']) && $data['null'] === true && $data['type'] === TableSchema::TYPE_TIMESTAMP) {
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
     */
    public function constraintSql(TableSchema $schema, $name)
    {
        $data = $schema->getConstraint($name);
        if ($data['type'] === TableSchema::CONSTRAINT_PRIMARY &&
            count($data['columns']) === 1 &&
            $schema->getColumn($data['columns'][0])['type'] === TableSchema::TYPE_INTEGER
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
            [$this->_driver, 'quoteIdentifier'],
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
     */
    public function addConstraintSql(TableSchema $schema)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     *
     * SQLite can not properly handle dropping a constraint to an existing table.
     * This method is no-op
     */
    public function dropConstraintSql(TableSchema $schema)
    {
        return [];
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
    public function createTableSql(TableSchema $schema, $columns, $constraints, $indexes)
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
     * {@inheritDoc}
     */
    public function truncateTableSql(TableSchema $schema)
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
    public function hasSequences()
    {
        $result = $this->_driver
            ->prepare('SELECT 1 FROM sqlite_master WHERE name = "sqlite_sequence"');
        $result->execute();
        $this->_hasSequences = (bool)$result->rowCount();
        $result->closeCursor();

        return $this->_hasSequences;
    }
}
