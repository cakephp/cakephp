<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\ORM;

use Cake\Database\DriverInterface;
use Cake\Database\TypeFactory;
use Cake\TestSuite\TestCase;
use TestApp\Database\Type\ColumnSchemaAwareType;

class ColumnSchemaAwareTypeIntegrationTest extends TestCase
{
    protected $fixtures = [
        'core.ColumnSchemaAwareTypeValues',
    ];

    public $autoFixtures = false;

    /**
     * @var \Cake\Database\TypeInterface|null
     */
    public $textType;

    public function setUp(): void
    {
        parent::setUp();

        $this->textType = TypeFactory::build('text');
        TypeFactory::map('text', ColumnSchemaAwareType::class);
        // For SQLServer.
        TypeFactory::map('nvarchar', ColumnSchemaAwareType::class);

        $this->loadFixtures('ColumnSchemaAwareTypeValues');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        TypeFactory::set('text', $this->textType);

        $map = TypeFactory::getMap();
        unset($map['nvarchar']);
        TypeFactory::setMap($map);
    }

    public function testCustomTypesCanBeUsedInFixtures()
    {
        $table = $this->getTableLocator()->get('ColumnSchemaAwareTypeValues');

        $expected = [
            'this text has been processed via a custom type',
            'this text also has been processed via a custom type',
        ];
        $result = $table->find()->orderAsc('id')->extract('val')->toArray();
        $this->assertSame($expected, $result);
    }

    public function testCustomTypeCanProcessColumnInfo()
    {
        $column = $this->getTableLocator()->get('ColumnSchemaAwareTypeValues')->getSchema()->getColumn('val');

        $this->assertSame('text', $column['type']);
        $this->assertSame(255, $column['length']);
        $this->assertSame('Custom schema aware type comment', $column['comment']);
    }

    public function testCustomTypeReceivesAllColumnDefinitionKeys()
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
            ->willReturnCallback(function (array $definition, DriverInterface $driver) {
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

        $table->getSchema()->getColumn('val');
    }
}
