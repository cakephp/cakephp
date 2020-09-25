<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Database;

use Cake\Database\Driver\Mysql;
use Cake\Database\Schema\TableSchema;
use Cake\Database\TypeFactory;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use TestApp\Database\Type\PointType;

class SchemaAwareTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = ConnectionManager::get('test');
        $this->skipIf(!($this->connection->getDriver() instanceof Mysql), 'MySQL only for now.');

        TypeFactory::map('point', PointType::class);
    }

    public function testColumnSql()
    {
        $dialect = $this->connection->getDriver()->schemaDialect();

        $schema = new TableSchema('table', [
            'pt' => [
                'type' => 'point',
                'null' => false,
                'comment' => 'Lorem ipsum',
            ],
        ]);
        $sql = $dialect->columnSql($schema, 'pt');
        $this->assertEqualsSql("pt POINT NOT NULL COMMENT 'Lorem ipsum'", $sql);
    }

    public function testConvertColumnDescription()
    {
        $dialect = $this->connection->getDriver()->schemaDialect();

        $schema = new TableSchema('table');
        $dialect->convertColumnDescription($schema, [
            'Field' => 'pt',
            'Type' => 'POINT',
            'Null' => 'YES',
            'Default' => null,
            'Collation' => null,
            'Comment' => 'Lorem ipsum',
        ]);

        $column = $schema->getColumn('pt');
        $this->assertEquals(
            [
                'type' => 'point',
                'length' => null,
                'null' => true,
                'default' => null,
                'comment' => 'Lorem ipsum',
                'precision' => null,
            ],
            $column
        );
    }
}
