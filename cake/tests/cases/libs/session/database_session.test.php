<?php
/**
 * DatabaseSessionTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.tests.cases.libs.session
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
		Configure::write('Session.handler', array(
			'model' => 'SessionTestModel',
			'database' => 'test',
			'table' => 'sessions'
		));
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
 * setup
 *
 * @return void
 */
	function setup() {
		$this->storage = new DatabaseSession();
	}

/**
 * teardown
 *
 * @return void
 */
	function teardown() {
		unset($this->storage);
		ClassRegistry::flush();
	}

/**
 * test that constructor sets the right things up.
 *
 * @return void
 */
	function testConstructionSettings() {
		ClassRegistry::flush();
		$storage = new DatabaseSession();

		$session = ClassRegistry::getObject('session');
		$this->assertInstanceOf('SessionTestModel', $session);
		$this->assertEquals('Session', $session->alias);
		$this->assertEquals('test', $session->useDbConfig);
	}

/**
 * test opening the session
 *
 * @return void
 */
	function testOpen() {
		$this->assertTrue($this->storage->open());
	}

/**
 * test write()
 *
 * @return void
 */
	function testWrite() {
		$result = $this->storage->write('foo', 'Some value');
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
		$this->storage->write('foo', 'Some value');

		$result = $this->storage->read('foo');
		$expected = 'Some value';
		$this->assertEquals($expected, $result);
		
		$result = $this->storage->read('made up value');
		$this->assertFalse($result);
	}

/**
 * test blowing up the session.
 *
 * @return void
 */
	function testDestroy() {
		$this->storage->write('foo', 'Some value');
		
		$this->assertTrue($this->storage->destroy('foo'), 'Destroy failed');
		$this->assertFalse($this->storage->read('foo'), 'Value still present.');
	}

/**
 * test the garbage collector
 *
 * @return void
 */
	function testGc() {
		Configure::write('Session.timeout', 0);
		$this->storage->write('foo', 'Some value');

		sleep(1);
		$this->storage->gc();
		$this->assertFalse($this->storage->read('foo'));
	}
}