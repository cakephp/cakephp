<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * Integration tests for Table class with uuid primary keys.
 */
class TableUuidTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'core.BinaryUuidItems',
        'core.UuidItems',
    ];

    /**
     * setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();
    }

    /**
     * Provider for testing that string and binary uuids work the same
     *
     * @return array
     */
    public function uuidTableProvider()
    {
        return [['uuid_items'], ['binary_uuid_items']];
    }

    /**
     * Test saving new records sets uuids
     *
     * @dataProvider uuidTableProvider
     * @return void
     */
    public function testSaveNew($tableName)
    {
        $entity = new Entity([
            'name' => 'shiny new',
            'published' => true,
        ]);
        $table = $this->getTableLocator()->get($tableName);
        $this->assertSame($entity, $table->save($entity));
        $this->assertMatchesRegularExpression('/^[a-f0-9-]{36}$/', $entity->id, 'Should be 36 characters');

        $row = $table->find('all')->where(['id' => $entity->id])->first();
        $row->id = strtolower($row->id);
        $this->assertEquals($entity->toArray(), $row->toArray());
    }

    /**
     * Test saving new records allows manual uuids
     *
     * @dataProvider uuidTableProvider
     * @return void
     */
    public function testSaveNewSpecificId($tableName)
    {
        $id = Text::uuid();
        $entity = new Entity([
            'id' => $id,
            'name' => 'shiny and new',
            'published' => true,
        ]);
        $table = $this->getTableLocator()->get($tableName);
        $this->assertSame($entity, $table->save($entity));
        $this->assertSame($id, $entity->id);

        $row = $table->find('all')->where(['id' => $id])->first();
        $this->assertNotEmpty($row);
        $this->assertSame($id, strtolower($row->id));
        $this->assertSame($entity->name, $row->name);
    }

    /**
     * Test saving existing records works
     *
     * @dataProvider uuidTableProvider
     * @return void
     */
    public function testSaveUpdate($tableName)
    {
        $id = '481fc6d0-b920-43e0-a40d-6d1740cf8569';
        $entity = new Entity([
            'id' => $id,
            'name' => 'shiny update',
            'published' => true,
        ]);

        $table = $this->getTableLocator()->get($tableName);
        $this->assertSame($entity, $table->save($entity));
        $this->assertSame($id, $entity->id, 'Should be 36 characters');

        $row = $table->find('all')->where(['id' => $entity->id])->first();
        $row->id = strtolower($row->id);
        $this->assertEquals($entity->toArray(), $row->toArray());
    }

    /**
     * Test delete with string pk.
     *
     * @dataProvider uuidTableProvider
     * @return void
     */
    public function testGetById($tableName)
    {
        $table = $this->getTableLocator()->get($tableName);
        $entity = $table->find('all')->firstOrFail();

        $result = $table->get($entity->id);
        $this->assertSame($result->id, $entity->id);
    }

    /**
     * Test delete with string pk.
     *
     * @dataProvider uuidTableProvider
     * @return void
     */
    public function testDelete($tableName)
    {
        $table = $this->getTableLocator()->get($tableName);
        $entity = $table->find('all')->firstOrFail();

        $this->assertTrue($table->delete($entity));
        $query = $table->find('all')->where(['id' => $entity->id]);
        $this->assertEmpty($query->first(), 'No row left');
    }

    /**
     * Tests that sql server does not error when an empty uuid is bound
     *
     * @dataProvider uuidTableProvider
     * @return void
     */
    public function testEmptyUuid($tableName)
    {
        $id = '';
        $table = $this->getTableLocator()->get($tableName);
        $entity = $table->find('all')
            ->where(['id' => $id])
            ->first();

        $this->assertNull($entity);
    }
}
