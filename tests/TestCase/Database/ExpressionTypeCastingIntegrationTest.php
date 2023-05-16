<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\Driver\Sqlserver;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Query;
use Cake\Database\TypeFactory;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use TestApp\Database\Type\OrderedUuidType;
use TestApp\Database\Type\UuidValue;

/**
 * Tests for Expression objects casting values to other expressions
 * using the type classes
 */
class ExpressionTypeCastingIntegrationTest extends TestCase
{
    protected $fixtures = ['core.OrderedUuidItems'];

    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $this->skipIf($this->connection->getDriver() instanceof Sqlserver, 'This tests uses functions specific to other drivers');
        TypeFactory::map('ordered_uuid', OrderedUuidType::class);
    }

    protected function _insert(): void
    {
        $query = $this->connection->insertQuery();
        $query
            ->insert(['id', 'published', 'name'], ['id' => 'ordered_uuid'])
            ->into('ordered_uuid_items')
            ->values(['id' => '481fc6d0-b920-43e0-a40d-6d1740cf8569', 'published' => 0, 'name' => 'Item 1'])
            ->values(['id' => '48298a29-81c0-4c26-a7fb-413140cf8569', 'published' => 0, 'name' => 'Item 2'])
            ->values(['id' => '482b7756-8da0-419a-b21f-27da40cf8569', 'published' => 0, 'name' => 'Item 3']);

        $query->execute();
    }

    /**
     * Tests inserting a value that is to be converted to an expression
     * automatically
     */
    public function testInsert(): void
    {
        $this->_insert();
        $query = $this->connection
            ->selectQuery('id')
            ->from('ordered_uuid_items')
            ->order('id')
            ->setDefaultTypes(['id' => 'ordered_uuid']);

        $query->setSelectTypeMap($query->getTypeMap());
        $results = $query->execute()->fetchAll('assoc');

        $this->assertEquals(new UuidValue('419a8da0482b7756b21f27da40cf8569'), $results[0]['id']);
        $this->assertEquals(new UuidValue('43e0b920481fc6d0a40d6d1740cf8569'), $results[1]['id']);
        $this->assertEquals(new UuidValue('4c2681c048298a29a7fb413140cf8569'), $results[2]['id']);
    }

    /**
     * Test selecting with a custom expression type using conditions
     */
    public function testSelectWithConditions(): void
    {
        $this->_insert();
        $result = $this->connection
            ->selectQuery('id')
            ->from('ordered_uuid_items')
            ->where(['id' => '48298a29-81c0-4c26-a7fb-413140cf8569'], ['id' => 'ordered_uuid'])
            ->execute()
            ->fetchAll('assoc');

        $this->assertCount(1, $result);
        $this->assertSame('4c2681c048298a29a7fb413140cf8569', $result[0]['id']);
    }

    /**
     * Tests Select using value object in conditions
     */
    public function testSelectWithConditionsValueObject(): void
    {
        $this->_insert();
        $result = $this->connection
            ->selectQuery('id')
            ->from('ordered_uuid_items')
            ->where(['id' => new UuidValue('48298a29-81c0-4c26-a7fb-413140cf8569')], ['id' => 'ordered_uuid'])
            ->execute()
            ->fetchAll('assoc');

        $this->assertCount(1, $result);
        $this->assertSame('4c2681c048298a29a7fb413140cf8569', $result[0]['id']);
    }

    /**
     * Tests using the expression type in with an IN condition
     *
     * @var string
     */
    public function testSelectWithInCondition(): void
    {
        $this->_insert();
        $result = $this->connection
            ->selectQuery('id')
            ->from('ordered_uuid_items')
            ->where(
                ['id' => ['48298a29-81c0-4c26-a7fb-413140cf8569', '482b7756-8da0-419a-b21f-27da40cf8569']],
                ['id' => 'ordered_uuid[]']
            )
            ->order('id')
            ->execute()
            ->fetchAll('assoc');

        $this->assertCount(2, $result);
        $this->assertSame('419a8da0482b7756b21f27da40cf8569', $result[0]['id']);
        $this->assertSame('419a8da0482b7756b21f27da40cf8569', $result[0]['id']);
    }

    /**
     * Tests using an expression type in a between condition
     */
    public function testSelectWithBetween(): void
    {
        $this->_insert();
        $result = $this->connection
            ->selectQuery('id')
            ->from('ordered_uuid_items')
            ->where(function (QueryExpression $exp) {
                return $exp->between(
                    'id',
                    '482b7756-8da0-419a-b21f-27da40cf8569',
                    '48298a29-81c0-4c26-a7fb-413140cf8569',
                    'ordered_uuid'
                );
            })
            ->execute()
            ->fetchAll('assoc');

        $this->assertCount(3, $result);
    }

    /**
     * Tests using an expression type inside a function expression
     */
    public function testSelectWithFunction(): void
    {
        $this->_insert();
        $result = $this->connection
            ->selectQuery('id')
            ->from('ordered_uuid_items')
            ->where(function (QueryExpression $exp, Query $q) {
                return $exp->eq(
                    'id',
                    $q->func()->concat(['48298a29-81c0-4c26-a7fb', '-413140cf8569'], []),
                    'ordered_uuid'
                );
            })
            ->execute()
            ->fetchAll('assoc');

        $this->assertCount(1, $result);
        $this->assertSame('4c2681c048298a29a7fb413140cf8569', $result[0]['id']);
    }
}
