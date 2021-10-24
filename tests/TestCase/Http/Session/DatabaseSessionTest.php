<?php
declare(strict_types=1);

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
    protected $fixtures = ['core.Sessions'];

    /**
     * @var \Cake\Http\Session\DatabaseSession
     */
    protected $storage;

    /**
     * setUp
     */
    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();
        $this->storage = new DatabaseSession();
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        unset($this->storage);
        parent::tearDown();
    }

    /**
     * test that constructor sets the right things up.
     */
    public function testConstructionSettings(): void
    {
        $this->getTableLocator()->clear();
        new DatabaseSession();

        $session = $this->getTableLocator()->get('Sessions');
        $this->assertInstanceOf('Cake\ORM\Table', $session);
        $this->assertSame('Sessions', $session->getAlias());
        $this->assertEquals(ConnectionManager::get('test'), $session->getConnection());
        $this->assertSame('sessions', $session->getTable());
    }

    /**
     * test opening the session
     */
    public function testOpen(): void
    {
        $this->assertTrue($this->storage->open(null, null));
    }

    /**
     * test write()
     */
    public function testWrite(): void
    {
        $result = $this->storage->write('foo', 'Some value');
        $this->assertTrue($result);

        $expires = $this->getTableLocator()->get('Sessions')->get('foo')->expires;
        $expected = time() + ini_get('session.gc_maxlifetime');
        $this->assertWithinRange($expected, $expires, 1);
    }

    /**
     * testReadAndWriteWithDatabaseStorage method
     */
    public function testWriteEmptySessionId(): void
    {
        $result = $this->storage->write('', 'This is a Test');
        $this->assertFalse($result);
    }

    /**
     * test read()
     */
    public function testRead(): void
    {
        $this->storage->write('foo', 'Some value');

        $result = $this->storage->read('foo');
        $expected = 'Some value';
        $this->assertSame($expected, $result);

        $result = $this->storage->read('made up value');
        $this->assertSame('', $result);
    }

    /**
     * test blowing up the session.
     */
    public function testDestroy(): void
    {
        $this->assertTrue($this->storage->write('foo', 'Some value'));

        $this->assertTrue($this->storage->destroy('foo'), 'Destroy failed');
        $this->assertSame('', $this->storage->read('foo'), 'Value still present.');
        $this->assertTrue($this->storage->destroy('foo'), 'Destroy should always return true');
    }

    /**
     * test the garbage collector
     */
    public function testGc(): void
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
     */
    public function testSerializeEntity(): void
    {
        $entity = new Entity();
        $entity->value = 'something';
        $result = $this->storage->write('key', serialize($entity));
        $data = $this->getTableLocator()->get('Sessions')->get('key')->data;
        $this->assertSame(serialize($entity), stream_get_contents($data));
    }
}
