<?php
/**
 * DatabaseSessionTest file
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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Network\Session;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Network\Session;
use Cake\Network\Session\DatabaseSession;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Database session test.
 */
class DatabaseSessionTest extends TestCase
{

    /**
     * fixtures
     *
     * @var string
     */
    public $fixtures = ['core.sessions'];

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
        $this->storage = new DatabaseSession();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->storage);
        TableRegistry::clear();
        parent::tearDown();
    }

    /**
     * test that constructor sets the right things up.
     *
     * @return void
     */
    public function testConstructionSettings()
    {
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
    public function testOpen()
    {
        $this->assertTrue($this->storage->open(null, null));
    }

    /**
     * test write()
     *
     * @return void
     */
    public function testWrite()
    {
        $result = $this->storage->write('foo', 'Some value');
        $this->assertTrue($result);

        $expires = TableRegistry::get('Sessions')->get('foo')->expires;
        $expected = time() + ini_get('session.gc_maxlifetime');
        $this->assertWithinRange($expected, $expires, 1);
    }

    /**
     * testReadAndWriteWithDatabaseStorage method
     *
     * @return void
     */
    public function testWriteEmptySessionId()
    {
        $result = $this->storage->write('', 'This is a Test');
        $this->assertFalse($result);
    }

    /**
     * test read()
     *
     * @return void
     */
    public function testRead()
    {
        $this->storage->write('foo', 'Some value');

        $result = $this->storage->read('foo');
        $expected = 'Some value';
        $this->assertEquals($expected, $result);

        $result = $this->storage->read('made up value');
        $this->assertSame('', $result);
    }

    /**
     * test blowing up the session.
     *
     * @return void
     */
    public function testDestroy()
    {
        $this->storage->write('foo', 'Some value');

        $this->assertTrue($this->storage->destroy('foo'), 'Destroy failed');
        $this->assertSame('', $this->storage->read('foo'), 'Value still present.');
        $this->assertTrue($this->storage->destroy('foo'), 'Destroy should always return true');
    }

    /**
     * test the garbage collector
     *
     * @return void
     */
    public function testGc()
    {
        TableRegistry::clear();

        ini_set('session.gc_maxlifetime', 0);
        $storage = new DatabaseSession();
        $storage->write('foo', 'Some value');

        sleep(1);
        $storage->gc(0);
        $this->assertSame('', $storage->read('foo'));
    }

    /**
     * Tests serializing an entity
     *
     * @return void
     */
    public function testSerializeEntity()
    {
        $entity = new Entity();
        $entity->value = 'something';
        $result = $this->storage->write('key', serialize($entity));
        $data = TableRegistry::get('Sessions')->get('key')->data;
        $this->assertEquals(serialize($entity), stream_get_contents($data));
    }
}
