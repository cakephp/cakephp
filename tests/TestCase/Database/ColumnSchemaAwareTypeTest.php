<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Database;

use Cake\Database\Driver\Mysql;
use Cake\Database\Schema\TableSchema;
use Cake\Database\TypeFactory;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use TestApp\Database\Type\ColumnSchemaAwareType;

class ColumnSchemaAwareTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = ConnectionManager::get('test');

        TypeFactory::map('columnSchemaAwareType', ColumnSchemaAwareType::class);
    }

    public function testColumnSql(): void
    {
        $dialect = $this->connection->getDriver()->schemaDialect();

        $schema = new TableSchema('table', [
            'field' => [
                'type' => 'columnSchemaAwareType',
                'null' => false,
                'comment' => 'Lorem ipsum',
            ],
        ]);
        $sql = $dialect->columnSql($schema, 'field');

        if ($this->connection->getDriver() instanceof Mysql) {
            $this->assertEqualsSql("field TEXT NOT NULL COMMENT 'Lorem ipsum (schema aware)'", $sql);
        } else {
            $this->assertEqualsSql('field TEXT NOT NULL', $sql);
        }
    }

    public function testColumnSqlForUnmappedType(): void
    {
        $map = TypeFactory::getMap();
        TypeFactory::clear();

        $dialect = $this->connection->getDriver()->schemaDialect();

        $schema = new TableSchema('table', [
            'field' => [
                'type' => 'time',
                'null' => false,
                'comment' => null,
            ],
        ]);
        $sql = $dialect->columnSql($schema, 'field');

        TypeFactory::setMap($map);

        $this->assertEqualsSql('field TIME NOT NULL', $sql);
    }
}
