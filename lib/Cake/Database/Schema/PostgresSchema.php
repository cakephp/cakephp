<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database\Schema;

use Cake\Database\Exception;
use Cake\Database\Schema\Table;

/**
 * Schema management/reflection features for Postgres.
 */
class PostgresSchema extends BaseSchema {

/**
 * The driver instance being used.
 *
 * @var Cake\Database\Driver\Postgres
 */
	protected $_driver;

/**
 * Constructor
 *
 * @param Cake\Database\Driver\Postgres $driver Driver to use.
 * @return void
 */
	public function __construct($driver) {
		$this->_driver = $driver;
	}

/**
 * Get the SQL to list the tables
 *
 * @param array $config The connection configuration to use for
 *    getting tables from.
 * @return array An array of (sql, params) to execute.
 */
	public function listTablesSql($config) {
		$sql = "SELECT table_name as name FROM information_schema.tables WHERE table_schema = ? ORDER BY name";
		$schema = empty($config['schema']) ? 'public' : $config['schema'];
		return [$sql, [$schema]];
	}

/**
 * Get the SQL to describe a table in Postgres.
 *
 * @param string $table The table name to describe
 * @param array $config The connection configuration to use
 * @return array An array of (sql, params) to execute.
 */
	public function describeTableSql($table, $config) {
		$sql =
		"SELECT DISTINCT table_schema AS schema, column_name AS name, data_type AS type,
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
		ORDER BY ordinal_position";

		$schema = empty($config['schema']) ? 'public' : $config['schema'];
		return [$sql, [$table, $schema, $config['database']]];
	}

/**
 * Convert a column definition to the abstract types.
 *
 * The returned type will be a type that
 * Cake\Database\Type can handle.
 *
 * @param string $column The column type + length
 * @throws Cake\Database\Exception when column cannot be parsed.
 * @return array Array of column information.
 */
	public function convertColumn($column) {
		preg_match('/([a-z\s]+)(?:\(([0-9,]+)\))?/i', $column, $matches);
		if (empty($matches)) {
			throw new Exception(__d('cake_dev', 'Unable to parse column type from "%s"', $column));
		}

		$col = strtolower($matches[1]);
		$length = null;
		if (isset($matches[2])) {
			$length = (int)$matches[2];
		}

		if (in_array($col, array('date', 'time', 'boolean'))) {
			return ['type' => $col, 'length' => null];
		}
		if (strpos($col, 'timestamp') !== false) {
			return ['type' => 'datetime', 'length' => null];
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
			return ['type' => 'string', 'fixed' => true, 'length' => 36];
		}
		if ($col === 'char' || $col === 'character') {
			return ['type' => 'string', 'fixed' => true, 'length' => $length];
		}
		if (strpos($col, 'char') !== false) {
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
		if (
			strpos($col, 'numeric') !== false ||
			strpos($col, 'money') !== false ||
			strpos($col, 'decimal') !== false
		) {
			return ['type' => 'decimal', 'length' => null];
		}
		return ['type' => 'text', 'length' => null];
	}

/**
 * Convert field description results into abstract schema fields.
 *
 * @param Cake\Database\Schema\Table $table The table object to append fields to.
 * @param array $row The row data from describeTableSql
 * @return void
 */
	public function convertFieldDescription(Table $table, $row) {
		$field = $this->convertColumn($row['type']);

		if ($field['type'] === 'boolean') {
			if ($row['default'] === 'true') {
				$row['default'] = 1;
			}
			if ($row['default'] === 'false') {
				$row['default'] = 0;
			}
		}

		$field += [
			'null' => $row['null'] === 'YES' ? true : false,
			'default' => $row['default'],
			'comment' => $row['comment']
		];
		$field['length'] = $row['char_length'] ?: $field['length'];
		$table->addColumn($row['name'], $field);
	}

/**
 * Get the SQL to describe the indexes in a table.
 *
 * @param string $table The table name to get information on.
 * @param array $config The configuration containing the schema name.
 * @return array An array of (sql, params) to execute.
 */
	public function describeIndexSql($table, $config) {
		$sql = "SELECT
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
				AND pg_catalog.pg_table_is_visible(c.oid)
				AND n.nspname = ?
		)
		AND c.oid = i.indrelid
		AND i.indexrelid = c2.oid
		ORDER BY i.indisprimary DESC, i.indisunique DESC, c2.relname";

		$schema = 'public';
		if (!empty($config['schema'])) {
			$schema = $config['schema'];
		}
		return [$sql, [$table, $schema]];
	}

/**
 * Convert an index into the abstract description.
 *
 * @param Cake\Database\Schema\Table $table The table object to append
 *    an index or constraint to.
 * @param array $row The row data from describeIndexSql
 * @return void
 */
	public function convertIndexDescription(Table $table, $row) {
		$type = Table::INDEX_INDEX;
		$name = $row['relname'];
		if ($row['indisprimary']) {
			$name = $type = Table::CONSTRAINT_PRIMARY;
		}
		if ($row['indisunique'] && $type === Table::INDEX_INDEX) {
			$type = Table::CONSTRAINT_UNIQUE;
		}
		preg_match('/\(([^\)]+)\)/', $row['statement'], $matches);
		$columns = explode(', ', $matches[1]);
		if ($type === Table::CONSTRAINT_PRIMARY || $type === Table::CONSTRAINT_UNIQUE) {
			$table->addConstraint($name, [
				'type' => $type,
				'columns' => $columns
			]);
			return;
		}
		$table->addIndex($name, [
			'type' => $type,
			'columns' => $columns
		]);
	}

/**
 * Generate the SQL to describe the foreign keys on a table.
 *
 * @return array List of sql, params
 */
	public function describeForeignKeySql($table, $config = []) {
		$sql = "SELECT
			r.conname AS name,
			r.confupdtype AS update_type,
			r.confdeltype AS delete_type,
			pg_catalog.pg_get_constraintdef(r.oid, true) AS definition
			FROM pg_catalog.pg_constraint AS r
			WHERE r.conrelid = (
				SELECT c.oid
				FROM pg_catalog.pg_class AS c,
				pg_catalog.pg_namespace AS n
				WHERE c.relname = ?
				AND n.nspname = ?
				AND n.oid = c.relnamespace
			)
			AND r.contype = 'f'";
		$schema = empty($config['schema']) ? 'public' : $config['schema'];
		return [$sql, [$table, $schema]];
	}

/**
 * Convert a foreign key description into constraints on the Table object.
 *
 * @param Cake\Database\Table $table The table instance to populate.
 * @param array $row The row of data.
 * @return void
 */
	public function convertForeignKey(Table $table, $row) {
		preg_match('/REFERENCES ([^\)]+)\(([^\)]+)\)/', $row['definition'], $matches);
		$tableName = $matches[1];
		$column = $matches[2];

		preg_match('/FOREIGN KEY \(([^\)]+)\) REFERENCES/', $row['definition'], $matches);
		$columns = explode(',', $matches[1]);

		$data = [
			'type' => Table::CONSTRAINT_FOREIGN,
			'columns' => $columns,
			'references' => [$tableName, $column],
			'update' => $this->_convertOnClause($row['update_type']),
			'delete' => $this->_convertOnClause($row['delete_type']),
		];
		$name = $row['name'];
		$table->addConstraint($name, $data);
	}

/**
 * Convert Postgres on clauses to the abstract ones.
 *
 * @param string $clause
 * @return string|null
 */
	protected function _convertOnClause($clause) {
		if ($clause === 'r') {
			return Table::ACTION_RESTRICT;
		}
		if ($clause === 'a') {
			return Table::ACTION_NO_ACTION;
		}
		if ($clause === 'c') {
			return Table::ACTION_CASCADE;
		}
		return Table::ACTION_SET_NULL;
	}

/**
 * Generate the SQL fragment for a single column.
 *
 * @param Cake\Database\Schema\Table $table The table object the column is in.
 * @param string $name The name of the column.
 * @return string SQL fragment.
 */
	public function columnSql(Table $table, $name) {
		$data = $table->column($name);
		$out = $this->_driver->quoteIdentifier($name);
		$typeMap = [
			'biginteger' => ' BIGINT',
			'boolean' => ' BOOLEAN',
			'binary' => ' BYTEA',
			'float' => ' FLOAT',
			'decimal' => ' DECIMAL',
			'text' => ' TEXT',
			'date' => ' DATE',
			'time' => ' TIME',
			'datetime' => ' TIMESTAMP',
			'timestamp' => ' TIMESTAMP',
		];

		if (isset($typeMap[$data['type']])) {
			$out .= $typeMap[$data['type']];
		}

		if ($data['type'] === 'integer') {
			$type = ' INTEGER';
			if (in_array($name, (array)$table->primaryKey())) {
				$type = ' SERIAL';
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
			if ($isFixed && isset($data['length']) && $data['length'] == 36) {
				$type = ' UUID';
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
			$out .= ' DEFAULT ' . $this->_driver->schemaValue($data['default']);
		}
		return $out;
	}

/**
 * Generate the SQL fragment for a single index
 *
 * @param Cake\Database\Schema\Table $table The table object the column is in.
 * @param string $name The name of the column.
 * @return string SQL fragment.
 */
	public function indexSql(Table $table, $name) {
		$data = $table->index($name);
		$columns = array_map(
			[$this->_driver, 'quoteIdentifier'],
			$data['columns']
		);
		return sprintf('CREATE INDEX %s ON %s (%s)',
			$this->_driver->quoteIdentifier($name),
			$this->_driver->quoteIdentifier($table->name()),
			implode(', ', $columns)
		);
	}

/**
 * Generate the SQL fragment for a single constraint
 *
 * @param Cake\Database\Schema\Table $table The table object the column is in.
 * @param string $name The name of the column.
 * @return string SQL fragment.
 */
	public function constraintSql(Table $table, $name) {
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
	protected function _keySql($prefix, $data) {
		$columns = array_map(
			[$this->_driver, 'quoteIdentifier'],
			$data['columns']
		);
		if ($data['type'] === Table::CONSTRAINT_FOREIGN) {
			return $prefix . sprintf(
				' FOREIGN KEY (%s) REFERENCES %s (%s) ON UPDATE %s ON DELETE %s',
				implode(', ', $columns),
				$this->_driver->quoteIdentifier($data['references'][0]),
				$this->_driver->quoteIdentifier($data['references'][1]),
				$this->_foreignOnClause($data['update']),
				$this->_foreignOnClause($data['delete'])
			);
		}
		return $prefix . ' (' . implode(', ', $columns) . ')';
	}

/**
 * Generate the SQL to create a table.
 *
 * @param Cake\Database\Schema\Table $table Table instance.
 * @param array $columns The columns to go inside the table.
 * @param array $constraints The constraints for the table.
 * @param array $indexes The indexes for the table.
 * @return string Complete CREATE TABLE statement
 */
	public function createTableSql(Table $table, $columns, $constraints, $indexes) {
		$content = array_merge($columns, $constraints);
		$content = implode(",\n", array_filter($content));
		$tableName = $this->_driver->quoteIdentifier($table->name());
		$out = [];
		$out[] = sprintf("CREATE TABLE %s (\n%s\n)", $tableName, $content);
		foreach ($indexes as $index) {
			$out[] = $index;
		}
		foreach ($table->columns() as $column) {
			$columnData = $table->column($column);
			if (isset($columnData['comment'])) {
				$out[] = sprintf('COMMENT ON COLUMN %s.%s IS %s',
					$tableName,
					$this->_driver->quoteIdentifier($column),
					$this->_driver->schemaValue($columnData['comment'])
				);
			}
		}
		return $out;
	}

/**
 * Generate the SQL to truncate a table.
 *
 * @param Cake\Database\Schema\Table $table Table instance
 * @return array SQL statements to drop truncate a table.
 */
	public function truncateTableSql(Table $table) {
		$name = $this->_driver->quoteIdentifier($table->name());
		return [
			sprintf("TRUNCATE %s RESTART IDENTITY", $name)
		];
	}

}
