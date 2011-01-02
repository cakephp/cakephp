<?php
/**
 * AllDatabaseTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.tests.cases
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * AllDatabaseTest class
 *
 * This test group will run database tests not in model or behavior group.
 *
 * @package       cake.tests.groups
 */
class AllDatabaseTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('Datasources, Schema and DbAcl tests');

		$path = CORE_TEST_CASES . DS . 'libs' . DS . 'model' . DS;
		$tasks = array(
			'db_acl',
			'cake_schema',
			'connection_manager',
			'datasources' . DS . 'dbo_source',
			'datasources' . DS . 'dbo' . DS . 'dbo_mysql',
			'datasources' . DS . 'dbo' . DS . 'dbo_postgres',
			'datasources' . DS . 'dbo' . DS . 'dbo_sqlite'
		);
		foreach ($tasks as $task) {
			$suite->addTestFile($path . $task . '.test.php');
		}
		return $suite;
	}
}
