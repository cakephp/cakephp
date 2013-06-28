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

/**
 * Base class for schema implementations.
 *
 * This class contains methods that are common across
 * the various SQL dialects.
 */
class BaseSchema {

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
 * @param string $clause
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
 * @param Cake\Database\Schema\Table $table Table instance
 * @return array SQL statements to drop DROP a table.
 */
	public function dropTableSql(Table $table) {
		$sql = sprintf(
			'DROP TABLE %s',
			$this->_driver->quoteIdentifier($table->name())
		);
		return [$sql];
	}

}
