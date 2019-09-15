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
 * @since         3.7.4
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use DateTime;

/**
 * Integration tests for Table class with uuid primary keys.
 */
class TableComplexIdTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'core.DateKeys',
    ];

    /**
     * setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        static::setAppNamespace();
    }

    /**
     * teardown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->getTableLocator()->clear();
    }

    /**
     * Test saving new records sets uuids
     *
     * @return void
     */
    public function testSaveNew()
    {
        $now = new DateTime('now');
        $entity = new Entity([
            'id' => $now,
            'title' => 'shiny new',
        ]);
        $table = $this->getTableLocator()->get('DateKeys');
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals($now, $entity->id);

        $row = $table->find('all')->where(['id' => $entity->id])->first();
        $this->assertEquals($row->id->format('Y-m-d'), $entity->id->format('Y-m-d'));
    }

    /**
     * Test saving existing records works
     *
     * @return void
     */
    public function testSaveUpdate()
    {
        $id = new DateTime('now');
        $entity = new Entity([
            'id' => $id,
            'title' => 'shiny update',
        ]);

        $table = $this->getTableLocator()->get('DateKeys');
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals($id, $entity->id, 'Should match');

        $row = $table->find('all')->where(['id' => $entity->id])->first();
        $row->title = 'things';
        $this->assertSame($row, $table->save($row));
    }

    /**
     * Test delete with string pk.
     *
     * @return void
     */
    public function testDelete()
    {
        $table = $this->getTableLocator()->get('DateKeys');
        $entity = new Entity([
            'id' => new DateTime('now'),
            'title' => 'shiny update',
        ]);
        $table->save($entity);
        $this->assertTrue($table->delete($entity));

        $query = $table->find('all')->where(['id' => $entity->id]);
        $this->assertEmpty($query->first(), 'No row left');
    }
}
