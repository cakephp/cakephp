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
 * Schema generation/reflection features for MySQL
 */
class MysqlSchema extends BaseSchema
{

    /**
     * {@inheritDoc}
     */
    public function listTablesSql($config)
    {
        return ['SHOW TABLES FROM ' . $this->_driver->quoteIdentifier($config['database']), []];
    }

    /**
     * {@inheritDoc}
     */
    public function describeColumnSql($tableName, $config)
    {
        return ['SHOW FULL COLUMNS FROM ' . $this->_driver->quoteIdentifier($tableName), []];
    }

    /**
     * {@inheritDoc}
     */
    public function describeIndexSql($tableName, $config)
    {
        return ['SHOW INDEXES FROM ' . $this->_driver->quoteIdentifier($tableName), []];
    }

    /**
     * {@inheritDoc}
     */
    public function describeOptionsSql($tableName, $config)
    {
        return ['SHOW TABLE STATUS WHERE Name = ?', [$tableName]];
    }

    /**
     * {@inheritDoc}
     */
    public function convertOptionsDescription(TableSchema $schema, $row)
    {
        $schema->setOptions([
            'engine' => $row['Engine'],
            'collation' => $row['Collation'],
        ]);
    }

    /**
     * Convert a MySQL column type into an abstract type.
     *
     * The returned type will be a type that Cake\Database\Type can handle.
     *
     * @param string $column The column type + length
     * @return array Array of column information.
     * @throws \Cake\Database\Exception When column type cannot be parsed.
     */
    protected function _convertColumn($column)
    {
        preg_match('/([a-z]+)(?:\(([0-9,]+)\))?\s*([a-z]+)?/i', $column, $matches);
        if (empty($matches)) {
            throw new Exception(sprintf('Unable to parse column type from "%s"', $column));
        }

        $col = strtolower($matches[1]);
        $length = $precision = null;
        if (isset($matches[2])) {
            $length = $matches[2];
            if (strpos($matches[2], ',') !== false) {
                list($length, $precision) = explode(',', $length);
            }
            $length = (int)$length;
            $precision = (int)$precision;
        }

        if (in_array($col, ['date', 'time', 'datetime', 'timestamp'])) {
            return ['type' => $col, 'length' => null];
        }
        if (($col === 'tinyint' && $length === 1) || $col === 'boolean') {
            return ['type' => 'boolean', 'length' => null];
        }

        $unsigned = (isset($matches[3]) && strtolower($matches[3]) === 'unsigned');
        if (strpos($col, 'bigint') !== false || $col === 'bigint') {
            return ['type' => 'biginteger', 'length' => $length, 'unsigned' => $unsigned];
        }
        if (in_array($col, ['int', 'integer', 'tinyint', 'smallint', 'mediumint'])) {
            return ['type' => 'integer', 'length' => $length, 'unsigned' => $unsigned];
        }
        if ($col === 'char' && $length === 36) {
            return ['type' => 'uuid', 'length' => null];
        }
        if ($col === 'char') {
            return ['type' => 'string', 'fixed' => true, 'length' => $length];
        }
        if (strpos($col, 'char') !== false) {
            return ['type' => 'string', 'length' => $length];
        }
        if (strpos($col, 'text') !== false) {
            $lengthName = substr($col, 0, -4);
            $length = isset(Table::$columnLengths[$lengthName]) ? Table::$columnLengths[$lengthName] : null;

            return ['type' => 'text', 'length' => $length];
        }
        if (strpos($col, 'blob') !== false || $col === 'binary') {
            $lengthName = substr($col, 0, -4);
            $length = isset(Table::$columnLengths[$lengthName]) ? Table::$columnLengths[$lengthName] : null;

            return ['type' => 'binary', 'length' => $length];
        }
        if (strpos($col, 'float') !== false || strpos($col, 'double') !== false) {
            return [
                'type' => 'float',
                'length' => $length,
                'precision' => $precision,
                'unsigned' => $unsigned
            ];
        }
        if (strpos($col, 'decimal') !== false) {
            return [
                'type' => 'decimal',
                'length' => $length,
                'precision' => $precision,
                'unsigned' => $unsigned
            ];
        }

        if (strpos($col, 'json') !== false) {
            return ['type' => 'json', 'length' => null];
        }

        return ['type' => 'string', 'length' => null];
    }

    /**
     * {@inheritDoc}
     */
    public function convertColumnDescription(TableSchema $schema, $row)
    {
        $field = $this->_convertColumn($row['Type']);
        $field += [
            'null' => $row['Null'] === 'YES',
            'default' => $row['Default'],
            'collate' => $row['Collation'],
            'comment' => $row['Comment'],
        ];
        if (isset($row['Extra']) && $row['Extra'] === 'auto_increment') {
            $field['autoIncrement'] = true;
        }
        $schema->addColumn($row['Field'], $field);
    }

    /**
     * {@inheritDoc}
     */
    public function convertIndexDescription(TableSchema $schema, $row)
    {
        $type = null;
        $columns = $length = [];

        $name = $row['Key_name'];
        if ($name === 'PRIMARY') {
            $name = $type = Table::CONSTRAINT_PRIMARY;
        }

        $columns[] = $row['Column_name'];

        if ($row['Index_type'] === 'FULLTEXT') {
            $type = Table::INDEX_FULLTEXT;
        } elseif ($row['Non_unique'] == 0 && $type !== 'primary') {
            $type = Table::CONSTRAINT_UNIQUE;
        } elseif ($type !== 'primary') {
            $type = Table::INDEX_INDEX;
        }

        if (!empty($row['Sub_part'])) {
            $length[$row['Column_name']] = $row['Sub_part'];
        }
        $isIndex = (
            $type === Table::INDEX_INDEX ||
            $type === Table::INDEX_FULLTEXT
        );
        if ($isIndex) {
            $existing = $schema->index($name);
        } else {
            $existing = $schema->constraint($name);
        }

        // MySQL multi column indexes come back as multiple rows.
        if (!empty($existing)) {
            $columns = array_merge($existing['columns'], $columns);
            $length = array_merge($existing['length'], $length);
        }
        if ($isIndex) {
            $schema->addIndex($name, [
                'type' => $type,
                'columns' => $columns,
                'length' => $length
            ]);
        } else {
            $schema->addConstraint($name, [
                'type' => $type,
                'columns' => $columns,
                'length' => $length
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function describeForeignKeySql($tableName, $config)
    {
        $sql = 'SELECT * FROM information_schema.key_column_usage AS kcu
            INNER JOIN information_schema.referential_constraints AS rc
            ON (
                kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                AND kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
            )
            WHERE kcu.TABLE_SCHEMA = ? AND kcu.TABLE_NAME = ? AND rc.TABLE_NAME = ?';

        return [$sql, [$config['database'], $tableName, $tableName]];
    }

    /**
     * {@inheritDoc}
     */
    public function convertForeignKeyDescription(TableSchema $schema, $row)
    {
        $data = [
            'type' => Table::CONSTRAINT_FOREIGN,
            'columns' => [$row['COLUMN_NAME']],
            'references' => [$row['REFERENCED_TABLE_NAME'], $row['REFERENCED_COLUMN_NAME']],
            'update' => $this->_convertOnClause($row['UPDATE_RULE']),
            'delete' => $this->_convertOnClause($row['DELETE_RULE']),
        ];
        $name = $row['CONSTRAINT_NAME'];
        $schema->addConstraint($name, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function truncateTableSql(TableSchema $schema)
    {
        return [sprintf('TRUNCATE TABLE `%s`', $schema->name())];
    }

    /**
     * {@inheritDoc}
     */
    public function createTableSql(TableSchema $schema, $columns, $constraints, $indexes)
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
     * {@inheritDoc}
     */
    public function columnSql(TableSchema $schema, $name)
    {
        $data = $schema->column($name);
        $out = $this->_driver->quoteIdentifier($name);
        $nativeJson = $this->_driver->supportsNativeJson();

        $typeMap = [
            'integer' => ' INTEGER',
            'biginteger' => ' BIGINT',
            'boolean' => ' BOOLEAN',
            'float' => ' FLOAT',
            'decimal' => ' DECIMAL',
            'date' => ' DATE',
            'time' => ' TIME',
            'datetime' => ' DATETIME',
            'timestamp' => ' TIMESTAMP',
            'uuid' => ' CHAR(36)',
            'json' => $nativeJson ? ' JSON' : ' LONGTEXT'
        ];
        $specialMap = [
            'string' => true,
            'text' => true,
            'binary' => true,
        ];
        if (isset($typeMap[$data['type']])) {
            $out .= $typeMap[$data['type']];
        }
        if (isset($specialMap[$data['type']])) {
            switch ($data['type']) {
                case 'string':
                    $out .= !empty($data['fixed']) ? ' CHAR' : ' VARCHAR';
                    if (!isset($data['length'])) {
                        $data['length'] = 255;
                    }
                    break;
                case 'text':
                    $isKnownLength = in_array($data['length'], Table::$columnLengths);
                    if (empty($data['length']) || !$isKnownLength) {
                        $out .= ' TEXT';
                        break;
                    }

                    if ($isKnownLength) {
                        $length = array_search($data['length'], Table::$columnLengths);
                        $out .= ' ' . strtoupper($length) . 'TEXT';
                    }

                    break;
                case 'binary':
                    $isKnownLength = in_array($data['length'], Table::$columnLengths);
                    if (empty($data['length']) || !$isKnownLength) {
                        $out .= ' BLOB';
                        break;
                    }

                    if ($isKnownLength) {
                        $length = array_search($data['length'], Table::$columnLengths);
                        $out .= ' ' . strtoupper($length) . 'BLOB';
                    }

                    break;
            }
        }
        $hasLength = ['integer', 'string'];
        if (in_array($data['type'], $hasLength, true) && isset($data['length'])) {
            $out .= '(' . (int)$data['length'] . ')';
        }

        $hasPrecision = ['float', 'decimal'];
        if (in_array($data['type'], $hasPrecision, true) &&
            (isset($data['length']) || isset($data['precision']))
        ) {
            $out .= '(' . (int)$data['length'] . ',' . (int)$data['precision'] . ')';
        }

        $hasUnsigned = ['float', 'decimal', 'integer', 'biginteger'];
        if (in_array($data['type'], $hasUnsigned, true) &&
            isset($data['unsigned']) && $data['unsigned'] === true
        ) {
            $out .= ' UNSIGNED';
        }

        $hasCollate = ['text', 'string'];
        if (in_array($data['type'], $hasCollate, true) && isset($data['collate']) && $data['collate'] !== '') {
            $out .= ' COLLATE ' . $data['collate'];
        }

        if (isset($data['null']) && $data['null'] === false) {
            $out .= ' NOT NULL';
        }
        $addAutoIncrement = (
            [$name] == (array)$schema->primaryKey() &&
            !$schema->hasAutoincrement()
        );
        if (in_array($data['type'], ['integer', 'biginteger']) &&
            ($data['autoIncrement'] === true || $addAutoIncrement)
        ) {
            $out .= ' AUTO_INCREMENT';
        }
        if (isset($data['null']) && $data['null'] === true && $data['type'] === 'timestamp') {
            $out .= ' NULL';
            unset($data['default']);
        }
        if (isset($data['default']) &&
            in_array($data['type'], ['timestamp', 'datetime']) &&
            strtolower($data['default']) === 'current_timestamp'
        ) {
            $out .= ' DEFAULT CURRENT_TIMESTAMP';
            unset($data['default']);
        }
        if (isset($data['default'])) {
            $out .= ' DEFAULT ' . $this->_driver->schemaValue($data['default']);
            unset($data['default']);
        }
        if (isset($data['comment']) && $data['comment'] !== '') {
            $out .= ' COMMENT ' . $this->_driver->schemaValue($data['comment']);
        }

        return $out;
    }

    /**
     * {@inheritDoc}
     */
    public function constraintSql(TableSchema $schema, $name)
    {
        $data = $schema->constraint($name);
        if ($data['type'] === Table::CONSTRAINT_PRIMARY) {
            $columns = array_map(
                [$this->_driver, 'quoteIdentifier'],
                $data['columns']
            );

            return sprintf('PRIMARY KEY (%s)', implode(', ', $columns));
        }

        $out = '';
        if ($data['type'] === Table::CONSTRAINT_UNIQUE) {
            $out = 'UNIQUE KEY ';
        }
        if ($data['type'] === Table::CONSTRAINT_FOREIGN) {
            $out = 'CONSTRAINT ';
        }
        $out .= $this->_driver->quoteIdentifier($name);

        return $this->_keySql($out, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function addConstraintSql(TableSchema $schema)
    {
        $sqlPattern = 'ALTER TABLE %s ADD %s;';
        $sql = [];

        foreach ($schema->constraints() as $name) {
            $constraint = $schema->constraint($name);
            if ($constraint['type'] === Table::CONSTRAINT_FOREIGN) {
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
        $sqlPattern = 'ALTER TABLE %s DROP FOREIGN KEY %s;';
        $sql = [];

        foreach ($schema->constraints() as $name) {
            $constraint = $schema->constraint($name);
            if ($constraint['type'] === Table::CONSTRAINT_FOREIGN) {
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
        $data = $schema->index($name);
        $out = '';
        if ($data['type'] === Table::INDEX_INDEX) {
            $out = 'KEY ';
        }
        if ($data['type'] === Table::INDEX_FULLTEXT) {
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
    protected function _keySql($prefix, $data)
    {
        $columns = array_map(
            [$this->_driver, 'quoteIdentifier'],
            $data['columns']
        );
        foreach ($data['columns'] as $i => $column) {
            if (isset($data['length'][$column])) {
                $columns[$i] .= sprintf('(%d)', $data['length'][$column]);
            }
        }
        if ($data['type'] === Table::CONSTRAINT_FOREIGN) {
            return $prefix . sprintf(
                ' FOREIGN KEY (%s) REFERENCES %s (%s) ON UPDATE %s ON DELETE %s',
                implode(', ', $columns),
                $this->_driver->quoteIdentifier($data['references'][0]),
                $this->_convertConstraintColumns($data['references'][1]),
                $this->_foreignOnClause($data['update']),
                $this->_foreignOnClause($data['delete'])
            );
        }

        return $prefix . ' (' . implode(', ', $columns) . ')';
    }
}
