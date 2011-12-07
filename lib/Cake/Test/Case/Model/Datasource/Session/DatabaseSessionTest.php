<?php
/**
 * DatabaseSessionTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Model.Datasource.Session
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Model', 'Model');
App::uses('CakeSession', 'Model/Datasource');
App::uses('DatabaseSession', 'Model/Datasource/Session');
class_exists('CakeSession');

class SessionTestModel extends Model {
	public $name = 'SessionTestModel';
	public $useTable = 'sessions';
}

/**
 * Database session test.
 *
 * @package       Cake.Test.Case.Model.Datasource.Session
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
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->storage = new DatabaseSession();
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		unset($this->storage);
		ClassRegistry::flush();
		parent::tearDown();
	}

/**
 * test that constructor sets the right things up.
 *
 * @return void
 */
	public function testConstructionSettings() {
		ClassRegistry::flush();
		$storage = new DatabaseSession();

		$session = ClassRegistry::getObject('session');
		$this->assertInstanceOf('SessionTestModel', $session);
		$this->assertEquals('Session', $session->alias);
		$this->assertEquals('test', $session->useDbConfig);
		$this->assertEquals('sessions', $session->useTable);
	}

/**
 * test opening the session
 *
 * @return void
 */
	public function testOpen() {
		$this->assertTrue($this->storage->open());
	}

/**
 * test write()
 *
 * @return void
 */
	public function testWrite() {
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
 * testReadAndWriteWithDatabaseStorage method
 *
 * @return void
 */
	public function testWriteEmptySessionId() {
		$result = $this->storage->write('', 'This is a Test');
		$this->assertFalse($result);
	}

/**
 * test read()
 *
 * @return void
 */
	public function testRead() {
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
	public function testDestroy() {
		$this->storage->write('foo', 'Some value');

		$this->assertTrue($this->storage->destroy('foo'), 'Destroy failed');
		$this->assertFalse($this->storage->read('foo'), 'Value still present.');
	}

/**
 * test the garbage collector
 *
 * @return void
 */
	public function testGc() {
		ClassRegistry::flush();
		Configure::write('Session.timeout', 0);

		$storage = new DatabaseSession();
		$storage->write('foo', 'Some value');

		sleep(1);
		$storage->gc();
		$this->assertFalse($storage->read('foo'));
	}
}