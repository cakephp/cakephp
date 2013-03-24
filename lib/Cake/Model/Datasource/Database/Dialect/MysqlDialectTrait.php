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
 * @package       Cake.Model
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Model\Datasource\Database\Dialect;

/**
 * Contains functions that encapsulates the SQL dialect used by MySQL,
 * including query translators and schema introspection.
 */
trait MysqlDialectTrait {

/**
 * Get the SQL to list the tables in MySQL
 *
 * @param array $config The connection configuration to use for
 *    getting tables from.
 * @return array An array of (sql, params) to execute.
 */
	public function listTablesSql($config) {
		return ["SHOW TABLES FROM " . $this->quoteIdentifier($config['database']), []];
	}

/**
 * Get the SQL to describe a table in MySQL.
 *
 * @param string $table The table name to describe.
 * @return array An array of (sql, params) to execute.
 */
	public function describeTableSql($table) {
		return ["SHOW TABLES FROM " . $this->quoteIdentifier($table), []];
	}

}
