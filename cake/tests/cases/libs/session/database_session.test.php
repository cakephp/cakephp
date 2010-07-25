<?php
/**
 * DatabaseSessionTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.tests.cases.libs.session
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Core', 'Model');
App::import('Core', 'CakeSession');
App::import('Core', 'session/DatabaseSession');

class SessionTestModel extends Model {
	var $name = 'SessionTestModel';
	var $useTable = 'sessions';
}

/**
 * Database session test.
 *
 * @package cake.tests.cases.libs.session
 */
class DatabaseSessionTest extends CakeTestCase {

	protected static $_sessionBackup;

/**
 * fixtures
 *
 * @var string
 */
	public $fixtures = array('core.session');

/**
 * test case startup
 *
 * @return void
 */
	public static function setupBeforeClass() {
		self::$_sessionBackup = Configure::read('Session');
		ClassRegistry::init(array(
			'class' => 'SessionTestModel',
			'alias' => 'Session',
			'ds' => 'test_suite'
		));
		Configure::write('Session.handler.model', 'SessionTestModel');
		Configure::write('Session.timeout', 100);
	}

/**
 * cleanup after test case.
 *
 * @return void
 */
	public static function teardownAfterClass() {
		Configure::write('Session', self::$_sessionBackup);
	}

/**
 * test opening the session
 *
 * @return void
 */
	function testOpen() {
		$this->assertTrue(DatabaseSession::open());
	}

/**
 * test write()
 *
 * @return void
 */
	function testWrite() {
		$result = DatabaseSession::write('foo', 'Some value');
		$expected = array(
			'Session' => array(
				'id' => 'foo',
				'data' => 'Some value',
				'expires' => time() + (Configure::read('Session.timeout') * 60)
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test read()
 *
 * @return void
 */
	function testRead() {
		DatabaseSession::write('foo', 'Some value');

		$result = DatabaseSession::read('foo');
		$expected = 'Some value';
		$this->assertEquals($expected, $result);
		
		$result = DatabaseSession::read('made up value');
		$this->assertFalse($result);
	}

/**
 * test blowing up the session.
 *
 * @return void
 */
	function testDestroy() {
		DatabaseSession::write('foo', 'Some value');
		
		$this->assertTrue(DatabaseSession::destroy('foo'), 'Destroy failed');
		$this->assertFalse(DatabaseSession::read('foo'), 'Value still present.');
	}
}