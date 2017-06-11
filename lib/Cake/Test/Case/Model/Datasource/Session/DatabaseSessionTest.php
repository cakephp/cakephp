<?php
/**
 * DatabaseSessionTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Model.Datasource.Session
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');
App::uses('CakeSession', 'Model/Datasource');
App::uses('DatabaseSession', 'Model/Datasource/Session');
class_exists('CakeSession');

/**
 * SessionTestModel
 *
 * @package       Cake.Test.Case.Model.Datasource.Session
 */
class SessionTestModel extends Model {

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
		static::$_sessionBackup = Configure::read('Session');
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
		Configure::write('Session', static::$_sessionBackup);
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
		new DatabaseSession();

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
		$this->storage->write('foo', 'Some value');
		$this->assertEquals($this->storage->read('foo'), 'Some value');
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
		$this->assertEquals($this->storage->read('foo'), 'Some value');
		$this->storage->write('bar', 0);
		$this->assertEquals(0, $this->storage->read('bar'));
		$this->assertSame('', $this->storage->read('made up value'));
	}

/**
 * test blowing up the session.
 *
 * @return void
 */
	public function testDestroy() {
		$this->storage->write('foo', 'Some value');

		$this->assertTrue($this->storage->destroy('foo'), 'Destroy failed');
		$this->assertSame($this->storage->read('foo'), '');
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
		$this->assertSame($storage->read('foo'), '');
	}

/**
 * testConcurrentInsert
 *
 * @return void
 */
	public function testConcurrentInsert() {
		$this->skipIf(
			$this->db instanceof Sqlite,
			'Sqlite does not throw exceptions when attempting to insert a duplicate primary key'
		);

		ClassRegistry::removeObject('Session');

		$mockedModel = $this->getMockForModel(
			'SessionTestModel',
			array('exists'),
			array('alias' => 'MockedSessionTestModel', 'table' => 'sessions')
		);
		Configure::write('Session.handler.model', 'MockedSessionTestModel');

		$counter = 0;
		// First save
		$mockedModel->expects($this->at($counter++))
			->method('exists')
			->will($this->returnValue(false));

		// Second save
		$mockedModel->expects($this->at($counter++))
			->method('exists')
			->will($this->returnValue(false));

		// Second save retry
		$mockedModel->expects($this->at($counter++))
			->method('exists')
			->will($this->returnValue(true));

		// Datasource exists check
		$mockedModel->expects($this->at($counter++))
			->method('exists')
			->will($this->returnValue(true));

		$this->storage = new DatabaseSession();

		$this->storage->write('foo', 'Some value');
		$return = $this->storage->read('foo');
		$this->assertSame('Some value', $return);

		$this->storage->write('foo', 'Some other value');
		$return = $this->storage->read('foo');
		$this->assertSame('Some other value', $return);
	}
}
