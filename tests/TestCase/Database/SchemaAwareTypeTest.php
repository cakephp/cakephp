<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Database;

use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Schema\TableSchema;
use Cake\Database\TypeFactory;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use TestApp\Database\Type\SchemaAwareType;

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

        TypeFactory::map('schemaawaretype', SchemaAwareType::class);
    }

    public function testColumnSql()
    {
        $dialect = $this->connection->getDriver()->schemaDialect();

        $schema = new TableSchema('table', [
            'field' => [
                'type' => 'schemaawaretype',
                'null' => false,
                'comment' => 'Lorem ipsum',
            ],
        ]);
        $sql = $dialect->columnSql($schema, 'field');

        $this->assertEqualsSql("field TEXT NOT NULL COMMENT 'Lorem ipsum (schema aware)'", $sql);
    }

    public function testColumnSqlForUnmappedType()
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

    public function testConvertColumnDescription()
    {
        switch (get_class($this->connection->getDriver())) {
            case Mysql::class:
                $this->convertColumnDescriptionMysqlTest();
                break;

            case Postgres::class:
                $this->convertColumnDescriptionPostgresTest();
                break;

            case Sqlite::class:
                $this->convertColumnDescriptionSqliteTest();
                break;

            case Sqlserver::class:
                $this->convertColumnDescriptionSqlserverTest();
                break;
        }
    }

    protected function convertColumnDescriptionMysqlTest()
    {
        $dialect = $this->connection->getDriver()->schemaDialect();

        $schema = new TableSchema('table');
        $dialect->convertColumnDescription($schema, [
            'Field' => 'field',
            'Type' => 'SCHEMAAWARETYPE',
            'Null' => 'YES',
            'Default' => null,
            'Collation' => null,
            'Comment' => 'Lorem ipsum',
        ]);

        $column = $schema->getColumn('field');
        $this->assertEquals(
            [
                'type' => 'schemaawaretype',
                'length' => null,
                'null' => true,
                'default' => null,
                'comment' => 'Custom schema aware type comment',
                'precision' => null,
            ],
            $column
        );
    }

    protected function convertColumnDescriptionPostgresTest()
    {
        $dialect = $this->connection->getDriver()->schemaDialect();

        $schema = new TableSchema('table');
        $dialect->convertColumnDescription($schema, [
            'name' => 'field',
            'type' => 'SCHEMAAWARETYPE',
            'null' => 'YES',
            'default' => null,
            'comment' => 'Lorem ipsum',
            'char_length' => null,
            'column_precision' => null,
            'column_scale' => null,
            'collation_name' => 'en_US.utf8',
        ]);

        $column = $schema->getColumn('field');
        $this->assertEquals(
            [
                'type' => 'schemaawaretype',
                'length' => null,
                'null' => true,
                'default' => null,
                'comment' => 'Custom schema aware type comment',
                'precision' => null,
            ],
            $column
        );
    }

    protected function convertColumnDescriptionSqliteTest()
    {
        $dialect = $this->connection->getDriver()->schemaDialect();

        $schema = new TableSchema('table');
        $dialect->convertColumnDescription($schema, [
            'pk' => false,
            'name' => 'field',
            'type' => 'SCHEMAAWARETYPE',
            'notnull' => false,
            'dflt_value' => 'NULL',
        ]);

        $column = $schema->getColumn('field');
        $this->assertEquals(
            [
                'type' => 'schemaawaretype',
                'length' => null,
                'null' => true,
                'default' => null,
                'comment' => 'Custom schema aware type comment',
                'precision' => null,
            ],
            $column
        );
    }

    protected function convertColumnDescriptionSqlserverTest()
    {
        $dialect = $this->connection->getDriver()->schemaDialect();

        $schema = new TableSchema('table');
        $dialect->convertColumnDescription($schema, [
            'name' => 'field',
            'type' => 'SCHEMAAWARETYPE',
            'null' => '1',
            'default' => 'NULL',
            'char_length' => null,
            'precision' => null,
            'scale' => null,
            'collation_name' => 'Latin1_General_CI_AI',
        ]);

        $column = $schema->getColumn('field');
        $this->assertEquals(
            [
                'type' => 'schemaawaretype',
                'length' => null,
                'null' => true,
                'default' => null,
                'comment' => 'Custom schema aware type comment',
                'precision' => null,
            ],
            $column
        );
    }
}
