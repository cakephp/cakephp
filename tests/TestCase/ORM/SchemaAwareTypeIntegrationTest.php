<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\ORM;

use Cake\Database\TypeFactory;
use Cake\TestSuite\TestCase;
use TestApp\Database\Type\SchemaAwareType;

class SchemaAwareTypeIntegrationTest extends TestCase
{
    protected $fixtures = [
        'core.SchemaAwareTypeValues',
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
        TypeFactory::map('text', SchemaAwareType::class);

        $this->loadFixtures('SchemaAwareTypeValues');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        TypeFactory::set('text', $this->textType);
    }

    public function testCustomTypesCanBeUsedInFixtures()
    {
        $table = $this->getTableLocator()->get('SchemaAwareTypeValues');

        $expected = [
            'this text has been processed via a custom type',
            'this text also has been processed via a custom type',
        ];
        $result = $table->find()->orderAsc('id')->extract('val')->toArray();
        $this->assertSame($expected, $result);
    }

    public function testCustomTypeCanProcessColumnInfo()
    {
        $column = $this->getTableLocator()->get('SchemaAwareTypeValues')->getSchema()->getColumn('val');

        $this->assertSame('text', $column['type']);
        $this->assertSame(255, $column['length']);
        $this->assertSame('Custom schema aware type comment', $column['comment']);
    }
}
