<?php
/**
 * AllDatabaseTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase;

use Cake\TestSuite\TestSuite;

/**
 * AllDatabaseTest class
 *
 * This test group will run database tests not in model or behavior group.
 *
 * @package       Cake.Test.Case
 */
class AllDatabaseTest extends \PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new \PHPUnit_Framework_TestSuite('Datasources, Schema and DbAcl tests');

		$path = CORE_TEST_CASES . DS . 'Model/';
		$tasks = array(
			'AclNode',
			'Schema',
			'ConnectionManager',
			'Datasource/DboSource',
			'Datasource/Database/Mysql',
			'Datasource/Database/Postgres',
			'Datasource/Database/Sqlite',
			'Datasource/Database/Sqlserver',
			'Datasource/Session',
			'Datasource/Session/CacheSession',
			'Datasource/Session/DatabaseSession',
		);
		foreach ($tasks as $task) {
			$suite->addTestFile($path . $task . 'Test.php');
		}
		return $suite;
	}
}
