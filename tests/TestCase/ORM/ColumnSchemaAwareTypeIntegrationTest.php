<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\ORM;

use Cake\Database\Driver;
use Cake\Database\TypeFactory;
use Cake\TestSuite\TestCase;
use TestApp\Database\Type\ColumnSchemaAwareType;

class ColumnSchemaAwareTypeIntegrationTest extends TestCase
{
    protected array $fixtures = [
        'core.ColumnSchemaAwareTypeValues',
    ];

    protected array $typeMap;

    public function setUp(): void
    {
        $this->typeMap = TypeFactory::getMap();

        TypeFactory::map('text', ColumnSchemaAwareType::class);
        // For SQLServer.
        TypeFactory::map('nvarchar', ColumnSchemaAwareType::class);

        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        TypeFactory::setMap($this->typeMap);
    }

    public function testCustomTypesCanBeUsedInFixtures(): void
    {
        $table = $this->getTableLocator()->get('ColumnSchemaAwareTypeValues');

        $expected = [
            'this text has been processed via a custom type',
            'this text also has been processed via a custom type',
        ];
        $result = $table->find()->orderByAsc('id')->all()->extract('val')->toArray();
        $this->assertSame($expected, $result);
    }

    public function testCustomTypeCanProcessColumnInfo(): void
    {
        $column = $this->getTableLocator()->get('ColumnSchemaAwareTypeValues')->getSchema()->getColumn('val');

        $this->assertSame('text', $column['type']);
        $this->assertSame(255, $column['length']);
        $this->assertSame('Custom schema aware type comment', $column['comment']);
    }

    public function testCustomTypeReceivesAllColumnDefinitionKeys(): void
    {
        $table = $this->getTableLocator()->get('ColumnSchemaAwareTypeValues');

        $type = $this
            ->getMockBuilder(ColumnSchemaAwareType::class)
            ->setConstructorArgs(['char'])
            ->onlyMethods(['convertColumnDefinition'])
            ->getMock();

        $type
            ->expects($this->once())
            ->method('convertColumnDefinition')
            ->willReturnCallback(function (array $definition, Driver $driver) {
                $this->assertEquals(
                    [
                        'length',
                        'precision',
                        'scale',
                    ],
                    array_keys($definition)
                );

                return null;
            });

        TypeFactory::set('text', $type);
        TypeFactory::set('nvarchar', $type);

        $table->getSchema()->getColumn('val');
    }
}
