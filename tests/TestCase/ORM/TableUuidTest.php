<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * Integration tests for Table class with uuid primary keys.
 *
 */
class TableUuidTest extends TestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'core.uuiditems', 'core.uuidportfolios'
    ];

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        Configure::write('App.namespace', 'TestApp');
    }

    /**
     * teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
    }

    /**
     * Test saving new records sets uuids
     *
     * @return void
     */
    public function testSaveNew()
    {
        $entity = new Entity([
            'name' => 'shiny new',
            'published' => true,
        ]);
        $table = TableRegistry::get('uuiditems');
        $this->assertSame($entity, $table->save($entity));
        $this->assertRegExp('/^[a-f0-9-]{36}$/', $entity->id, 'Should be 36 characters');

        $row = $table->find('all')->where(['id' => $entity->id])->first();
        $row->id = strtolower($row->id);
        $this->assertEquals($entity->toArray(), $row->toArray());
    }

    /**
     * Test saving new records allows manual uuids
     *
     * @return void
     */
    public function testSaveNewSpecificId()
    {
        $id = Text::uuid();
        $entity = new Entity([
            'id' => $id,
            'name' => 'shiny and new',
            'published' => true,
        ]);
        $table = TableRegistry::get('uuiditems');
        $this->assertSame($entity, $table->save($entity));

        $row = $table->find('all')->where(['id' => $id])->first();
        $this->assertNotEmpty($row);
        $this->assertSame($id, strtolower($row->id));
        $this->assertSame($entity->name, $row->name);
    }

    /**
     * Test saving existing records works
     *
     * @return void
     */
    public function testSaveUpdate()
    {
        $id = '481fc6d0-b920-43e0-a40d-6d1740cf8569';
        $entity = new Entity([
            'id' => $id,
            'name' => 'shiny update',
            'published' => true,
        ]);

        $table = TableRegistry::get('uuiditems');
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals($id, $entity->id, 'Should be 36 characters');

        $row = $table->find('all')->where(['id' => $entity->id])->first();
        $row->id = strtolower($row->id);
        $this->assertEquals($entity->toArray(), $row->toArray());
    }

    /**
     * Test delete with string pk.
     *
     * @return void
     */
    public function testDelete()
    {
        $id = '481fc6d0-b920-43e0-a40d-6d1740cf8569';
        $table = TableRegistry::get('uuiditems');
        $entity = $table->find('all')->where(['id' => $id])->first();

        $this->assertTrue($table->delete($entity));
        $query = $table->find('all')->where(['id' => $id]);
        $this->assertCount(0, $query->execute(), 'No rows left');
    }

    /**
     * Tests that sql server does not error when an empty uuid is bound
     *
     * @return void
     */
    public function testEmptyUuid()
    {
        $id = '';
        $table = TableRegistry::get('uuiditems');
        $entity = $table->find('all')
            ->where(['id' => $id])
            ->first();

        $this->assertNull($entity);
    }
}
