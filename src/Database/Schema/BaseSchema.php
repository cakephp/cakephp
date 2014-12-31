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

use Cake\Database\Driver;
use Cake\Database\Expression\TableNameExpression;
use Cake\Database\Schema\Table;
use Cake\Database\ValueBinder;

/**
 * Base class for schema implementations.
 *
 * This class contains methods that are common across
 * the various SQL dialects.
 */
abstract class BaseSchema {

/**
 * The driver instance being used.
 *
 * @var \Cake\Database\Driver
 */
	protected $_driver;

/**
 * Constructor
 *
 * This constructor will connect the driver so that methods like columnSql() and others
 * will fail when the driver has not been connected.
 *
 * @param \Cake\Database\Driver $driver The driver to use.
 */
	public function __construct(Driver $driver) {
		$driver->connect();
		$this->_driver = $driver;
	}

/**
 * Retrieves the prefix from the current connection $config
 *
 * @param array $config Configuration array for the current Connection
 * @return string The prefix for the current connection
 */
	public function getConnectionPrefix($config) {
		$prefix = '';
		if (isset($config['prefix']) && is_string($config['prefix'])) {
			$prefix = $config['prefix'];
		}

		return $prefix;
	}

/**
 * Resolves the full table name for the table name $tableName
 *
 * @param string|TableNameExpression $tableName Table name
 * @param array $config Configuration array for the current Connection
 * @param bool $quoted Whether the table name should be quoted
 * @return string The full table name
 */
	public function getFullTableName($tableName, $config, $quoted = true) {
		$prefix = $this->getConnectionPrefix($config);

		if (is_string($tableName)) {
			if ($prefix !== '' && (strpos($tableName, $prefix) !== 0 || $tableName === $prefix)) {
				$tableName = $prefix . $tableName;
			}

			if ($quoted === true) {
				$tableName = $this->_driver->quoteIdentifier($tableName);
			}

			return $tableName;
		}

		if ($tableName instanceof TableNameExpression) {
			$name = $tableName->sql(new ValueBinder);
			if ($quoted === true) {
				$name = $this->_driver->quoteIdentifier($name);
			}

			return $name;
		}

		return $tableName;
	}

/**
 * Generate an ON clause for a foreign key.
 *
 * @param string|null $on The on clause
 * @return string
 */
	protected function _foreignOnClause($on) {
		if ($on === Table::ACTION_SET_NULL) {
			return 'SET NULL';
		}
		if ($on === Table::ACTION_SET_DEFAULT) {
			return 'SET DEFAULT';
		}
		if ($on === Table::ACTION_CASCADE) {
			return 'CASCADE';
		}
		if ($on === Table::ACTION_RESTRICT) {
			return 'RESTRICT';
		}
		if ($on === Table::ACTION_NO_ACTION) {
			return 'NO ACTION';
		}
	}

/**
 * Convert string on clauses to the abstract ones.
 *
 * @param string $clause The on clause to convert.
 * @return string|null
 */
	protected function _convertOnClause($clause) {
		if ($clause === 'CASCADE' || $clause === 'RESTRICT') {
			return strtolower($clause);
		}
		if ($clause === 'NO ACTION') {
			return Table::ACTION_NO_ACTION;
		}
		return Table::ACTION_SET_NULL;
	}

/**
 * Generate the SQL to drop a table.
 *
 * @param \Cake\Database\Schema\Table $table Table instance
 * @return array SQL statements to drop a table.
 */
	public function dropTableSql(Table $table) {
		$sql = sprintf(
			'DROP TABLE %s',
			$this->_driver->quoteIdentifier($table->name())
		);
		return [$sql];
	}

/**
 * Generate the SQL to list the tables.
 *
 * @param array $config The connection configuration to use for
 *    getting tables from.
 * @return array An array of (sql, params) to execute.
 */
	abstract public function listTablesSql($config);

/**
 * Generate the SQL to describe a table.
 *
 * @param string $tableName The table name to get information on.
 * @param array $config The connection configuration.
 * @return array An array of (sql, params) to execute.
 */
	abstract public function describeColumnSql($tableName, $config);

/**
 * Generate the SQL to describe the indexes in a table.
 *
 * @param string $tableName The table name to get information on.
 * @param array $config The connection configuration.
 * @return array An array of (sql, params) to execute.
 */
	abstract public function describeIndexSql($tableName, $config);

/**
 * Generate the SQL to describe the foreign keys in a table.
 *
 * @param string $tableName The table name to get information on.
 * @param array $config The connection configuration.
 * @return array An array of (sql, params) to execute.
 */
	abstract public function describeForeignKeySql($tableName, $config);

/**
 * Generate the SQL to describe table options
 *
 * @param string $tableName Table name.
 * @param array $config The connection configuration.
 * @return array SQL statements to get options for a table.
 */
	public function describeOptionsSql($tableName, $config) {
		return ['', ''];
	}

/**
 * Convert field description results into abstract schema fields.
 *
 * @param \Cake\Database\Schema\Table $table The table object to append fields to.
 * @param array $row The row data from `describeColumnSql`.
 * @return void
 */
	abstract public function convertColumnDescription(Table $table, $row);

/**
 * Convert an index description results into abstract schema indexes or constraints.
 *
 * @param \Cake\Database\Schema\Table $table The table object to append
 *    an index or constraint to.
 * @param array $row The row data from `describeIndexSql`.
 * @return void
 */
	abstract public function convertIndexDescription(Table $table, $row);

/**
 * Convert a foreign key description into constraints on the Table object.
 *
 * @param \Cake\Database\Schema\Table $table The table object to append
 *    a constraint to.
 * @param array $row The row data from `describeForeignKeySql`.
 * @return void
 */
	abstract public function convertForeignKeyDescription(Table $table, $row);

/**
 * Convert options data into table options.
 *
 * @param \Cake\Database\Schema\Table $table Table instance.
 * @param array $row The row of data.
 * @return void
 */
	public function convertOptionsDescription(Table $table, $row) {
	}

/**
 * Generate the SQL to create a table.
 *
 * @param \Cake\Database\Schema\Table $table Table instance.
 * @param array $columns The columns to go inside the table.
 * @param array $constraints The constraints for the table.
 * @param array $indexes The indexes for the table.
 * @return array SQL statements to create a table.
 */
	abstract public function createTableSql(Table $table, $columns, $constraints, $indexes);

/**
 * Generate the SQL fragment for a single column in a table.
 *
 * @param \Cake\Database\Schema\Table $table The table instance the column is in.
 * @param string $name The name of the column.
 * @return string SQL fragment.
 */
	abstract public function columnSql(Table $table, $name);

/**
 * Generate the SQL fragments for defining table constraints.
 *
 * @param \Cake\Database\Schema\Table $table The table instance the column is in.
 * @param string $name The name of the column.
 * @return string SQL fragment.
 */
	abstract public function constraintSql(Table $table, $name);

/**
 * Generate the SQL fragment for a single index in a table.
 *
 * @param \Cake\Database\Schema\Table $table The table object the column is in.
 * @param string $name The name of the column.
 * @return string SQL fragment.
 */
	abstract public function indexSql(Table $table, $name);

/**
 * Generate the SQL to truncate a table.
 *
 * @param \Cake\Database\Schema\Table $table Table instance.
 * @return array SQL statements to truncate a table.
 */
	abstract public function truncateTableSql(Table $table);

}
