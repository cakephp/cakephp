<?php
/**
 * DatabaseSessionTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Session;

use Cake\Datasource\ConnectionManager;
use Cake\Http\Session\DatabaseSession;
use Cake\ORM\Entity;
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
        static::setAppNamespace();
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
        $this->getTableLocator()->clear();
        parent::tearDown();
    }

    /**
     * test that constructor sets the right things up.
     *
     * @return void
     */
    public function testConstructionSettings()
    {
        $this->getTableLocator()->clear();
        new DatabaseSession();

        $session = $this->getTableLocator()->get('Sessions');
        $this->assertInstanceOf('Cake\ORM\Table', $session);
        $this->assertEquals('Sessions', $session->getAlias());
        $this->assertEquals(ConnectionManager::get('test'), $session->getConnection());
        $this->assertEquals('sessions', $session->getTable());
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

        $expires = $this->getTableLocator()->get('Sessions')->get('foo')->expires;
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
        $this->getTableLocator()->clear();

        $storage = new DatabaseSession();
        $storage->setTimeout(0);
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
        $data = $this->getTableLocator()->get('Sessions')->get('key')->data;
        $this->assertEquals(serialize($entity), stream_get_contents($data));
    }
}
