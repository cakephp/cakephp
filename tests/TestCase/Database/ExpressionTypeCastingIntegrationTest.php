<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Type;
use Cake\Database\Type\BinaryType;
use Cake\TestSuite\TestCase;
use Cake\Datasource\ConnectionManager;
use Cake\Database\Driver;
use Cake\Database\Type\ExpressionTypeInterface;

class UuidValue
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}

class OrderedUuidType extends Type implements ExpressionTypeInterface
{

    public function toPHP($value, Driver $d)
    {
        return new UuidValue($value);
    }

    public function toExpression($value)
    {
        $substr = function ($start, $lenght = null) use ($value) {
            return  new FunctionExpression(
                'SUBSTR',
                $lenght === null ? [$value, $start] : [$value, $start, $lenght],
                ['string', 'integer', 'integer']
            );
        };
        return new FunctionExpression(
            'CONCAT',
            [$substr(15, 4), $substr(10, 4), $substr(1, 8), $substr(20, 4), $substr(25)]
        );
    }
}

/**
 * Tests for Expression objects casting values to other expressions
 * using the type classes
 *
 */
class ExpressionTypeCastingIntegrationTest extends TestCase
{

    public $fixtures = ['core.ordered_uuid_items'];

    public function setUp()
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        Type::map('ordered_uuid', OrderedUuidType::class);
    }

    protected function _insert()
    {
        $query = $this->connection->newQuery();
        $values = $query
            ->insert(['id', 'published', 'name'], ['id' => 'ordered_uuid'])
            ->into('ordered_uuid_items')
            ->clause('values');
        $values
            ->values([
                ['id' => '481fc6d0-b920-43e0-a40d-6d1740cf8569', 'published' => 0, 'name' => 'Item 1'],
                ['id' => '48298a29-81c0-4c26-a7fb-413140cf8569', 'published' => 0, 'name' => 'Item 2'],
                ['id' => '482b7756-8da0-419a-b21f-27da40cf8569', 'published' => 0, 'name' => 'Item 3'],
            ]);

        $query->execute();
    }

    /**
     * Tests inserting a value that is to be converted to an expression
     * automatically
     *
     * @return void
     */
    public function testInsert()
    {
        $this->_insert();
        $query = $this->connection->newQuery()
            ->select('id')
            ->from('ordered_uuid_items')
            ->order('id')
            ->defaultTypes(['id' => 'ordered_uuid']);

        $query->selectTypeMap($query->typeMap());
        $results = $query->execute()->fetchAll('assoc');

        $this->assertEquals(new UuidValue('419a8da0482b7756b21f27da40cf8569'), $results[0]['id']);
        $this->assertEquals(new UuidValue('43e0b920481fc6d0a40d6d1740cf8569'), $results[1]['id']);
        $this->assertEquals(new UuidValue('4c2681c048298a29a7fb413140cf8569'), $results[2]['id']);
    }

    /**
     * Test selecting with a custom expression type using conditions
     *
     * @return void
     */
    public function testSelectWithConditions()
    {
        $this->_insert();
        $result = $this->connection->newQuery()
            ->select('id')
            ->from('ordered_uuid_items')
            ->where(['id' => '48298a29-81c0-4c26-a7fb-413140cf8569'], ['id' => 'ordered_uuid'])
            ->execute()
            ->fetchAll('assoc');

        $this->assertCount(1, $result);
        $this->assertEquals('4c2681c048298a29a7fb413140cf8569', $result[0]['id']);
    }

    /**
     * Tests using the expression type in with an IN condition
     *
     * @var string
     */
    public function testSelectWithInCondition()
    {
        $this->_insert();
        $result = $this->connection->newQuery()
            ->select('id')
            ->from('ordered_uuid_items')
            ->where(
                ['id' => ['48298a29-81c0-4c26-a7fb-413140cf8569', '482b7756-8da0-419a-b21f-27da40cf8569']],
                ['id' => 'ordered_uuid[]']
            )
            ->execute()
            ->fetchAll('assoc');

        $this->assertCount(2, $result);
        $this->assertEquals('419a8da0482b7756b21f27da40cf8569', $result[0]['id']);
        $this->assertEquals('419a8da0482b7756b21f27da40cf8569', $result[0]['id']);
    }

    /**
     * Tests using an expression type in a between condition
     *
     * @return void
     */
    public function testSelectWithBetween()
    {
        $this->_insert();
        $result = $this->connection->newQuery()
            ->select('id')
            ->from('ordered_uuid_items')
            ->where(function ($exp) {
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
     *
     * @return void
     */
    public function testSelectWithFunction()
    {
        $this->_insert();
        $result = $this->connection->newQuery()
            ->select('id')
            ->from('ordered_uuid_items')
            ->where(function ($exp, $q) {
               return $exp->eq(
                    'id',
                    $q->func()->concat(['48298a29-81c0-4c26-a7fb', '-413140cf8569'], []),
                    'ordered_uuid'
                );
            })
            ->execute()
            ->fetchAll('assoc');

        $this->assertCount(1, $result);
        $this->assertEquals('4c2681c048298a29a7fb413140cf8569', $result[0]['id']);
    }
}
