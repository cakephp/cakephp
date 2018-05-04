<?php
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

use Cake\Database\Driver;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Type;
use Cake\Database\Type\ExpressionTypeInterface;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Value object for testing mappings.
 */
class UuidValue
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}

/**
 * Custom type class that maps between value objects, and SQL expressions.
 */
class OrderedUuidType extends Type implements ExpressionTypeInterface
{

    public function toPHP($value, Driver $d)
    {
        return new UuidValue($value);
    }

    public function toExpression($value)
    {
        if ($value instanceof UuidValue) {
            $value = $value->value;
        }
        $substr = function ($start, $length = null) use ($value) {
            return new FunctionExpression(
                'SUBSTR',
                $length === null ? [$value, $start] : [$value, $start, $length],
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
        $this->skipIf($this->connection->getDriver() instanceof Sqlserver, 'This tests uses functions specific to other drivers');
        Type::map('ordered_uuid', OrderedUuidType::class);
    }

    protected function _insert()
    {
        $query = $this->connection->newQuery();
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
            ->setDefaultTypes(['id' => 'ordered_uuid']);

        $query->setSelectTypeMap($query->getTypeMap());
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
     * Tests Select using value object in conditions
     *
     * @return void
     */
    public function testSelectWithConditionsValueObject()
    {
        $this->_insert();
        $result = $this->connection->newQuery()
            ->select('id')
            ->from('ordered_uuid_items')
            ->where(['id' => new UuidValue('48298a29-81c0-4c26-a7fb-413140cf8569')], ['id' => 'ordered_uuid'])
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
            ->order('id')
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
