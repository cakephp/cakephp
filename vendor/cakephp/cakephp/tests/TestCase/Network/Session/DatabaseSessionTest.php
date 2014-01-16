<?php
/**
 * DatabaseSessionTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Network\Session;

use Cake\Core\Configure;
use Cake\Database\ConnectionManager;
use Cake\Network\Session;
use Cake\Network\Session\DatabaseSession;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Database session test.
 *
 */
class DatabaseSessionTest extends TestCase {

/**
 * sessionBackup
 *
 * @var array
 */
	protected static $_sessionBackup;

/**
 * fixtures
 *
 * @var string
 */
	public $fixtures = ['core.session'];

/**
 * test case startup
 *
 * @return void
 */
	public static function setupBeforeClass() {
		static::$_sessionBackup = Configure::read('Session');

		Configure::write('Session.handler', array(
			'model' => 'Sessions'
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
		Configure::write('App.namespace', 'TestApp');
		$this->storage = new DatabaseSession();
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		unset($this->storage);
		TableRegistry::clear();
		parent::tearDown();
	}

/**
 * test that constructor sets the right things up.
 *
 * @return void
 */
	public function testConstructionSettings() {
		TableRegistry::clear();
		new DatabaseSession();

		$session = TableRegistry::get('Sessions');
		$this->assertInstanceOf('Cake\ORM\Table', $session);
		$this->assertEquals('Sessions', $session->alias());
		$this->assertEquals(ConnectionManager::get('test'), $session->connection());
		$this->assertEquals('sessions', $session->table());
	}

/**
 * test opening the session
 *
 * @return void
 */
	public function testOpen() {
		$this->assertTrue($this->storage->open(null, null));
	}

/**
 * test write()
 *
 * @return void
 */
	public function testWrite() {
		$result = $this->storage->write('foo', 'Some value');
		$expected = [
			'id' => 'foo',
			'data' => 'Some value',
		];
		$expires = $result['expires'];
		unset($result['expires']);
		$this->assertEquals($expected, $result);

		$expected = time() + (Configure::read('Session.timeout') * 60);
		$this->assertWithinMargin($expires, $expected, 1);
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
		TableRegistry::clear();
		Configure::write('Session.timeout', 0);

		$storage = new DatabaseSession();
		$storage->write('foo', 'Some value');

		sleep(1);
		$storage->gc(0);
		$this->assertFalse($storage->read('foo'));
	}
}
