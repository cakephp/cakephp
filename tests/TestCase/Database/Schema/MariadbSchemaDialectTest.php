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
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Schema;

use Cake\Database\Driver;
use Cake\Database\Driver\Mariadb;
use Cake\Database\DriverFeatureEnum;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\ForeignKey;
use Cake\Database\Schema\MariadbSchemaDialect;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\UniqueKey;
use Cake\Datasource\ConnectionManager;
use Mockery;

/**
 * Test case for Mariadb Schema Dialect.
 */
class MariadbSchemaDialectTest extends MysqlSchemaDialectTest
{
    protected $schemaDialectClass = MariadbSchemaDialect::class;

    protected $driverClass = Mariadb::class;

    /**
     * Helper method for skipping tests that need a real connection.
     */
    protected function _needsConnection(): void
    {
        $config = ConnectionManager::getConfig('test');
        $this->skipIf(!str_contains($config['driver'], 'Mariadb'), 'Not using Mariadb for test config');
    }

    /**
     * Test describing a table with Mariadb
     *
     * Overrides a test in MysqlSchemaDialectTest
     */
    public function testDescribeTable(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $dialect = $connection->getDriver()->schemaDialect();
        $result = $dialect->describe('schema_articles');
        $this->assertInstanceOf(TableSchema::class, $result);
        $expected = [
            'id' => [
                'type' => 'biginteger',
                'null' => false,
                'unsigned' => false,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
                'autoIncrement' => true,
                'generated' => null,
            ],
            'title' => [
                'type' => 'string',
                'null' => true,
                'default' => null,
                'length' => 20,
                'precision' => null,
                'comment' => 'A title',
                'charset' => null,
                'collate' => 'utf8_general_ci',
            ],
            'body' => [
                'type' => 'text',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
                'charset' => null,
                'collate' => 'utf8_general_ci',
            ],
            'author_id' => [
                'type' => 'integer',
                'null' => false,
                'unsigned' => false,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
                'autoIncrement' => null,
                'generated' => null,
            ],
            'unique_id' => [
                'type' => 'integer',
                'null' => false,
                'unsigned' => false,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
                'autoIncrement' => null,
                'generated' => null,
            ],
            'published' => [
                'type' => 'boolean',
                'null' => true,
                'default' => 0,
                'length' => null,
                'precision' => null,
                'comment' => null,
            ],
            'allow_comments' => [
                'type' => 'boolean',
                'null' => true,
                'default' => 0,
                'length' => null,
                'precision' => null,
                'comment' => null,
            ],
            'location' => [
                'type' => 'point',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
                'srid' => null,
            ],
            'year_type' => [
                'type' => 'year',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
            ],
            // MariaDb aliases JSON to LONGTEXT
            // https://mariadb.com/kb/en/json/
            'config' => [
                'type' => 'text',
                'null' => true,
                'default' => null,
                'length' => 4294967295,
                'precision' => null,
                'comment' => '',
                'charset' => null,
                'collate' => 'utf8mb4_bin',
            ],
            'created' => [
                'type' => 'datetime',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
                'onUpdate' => null,
            ],
            'created_with_precision' => [
                'type' => 'datetimefractional',
                'null' => true,
                'default' => 'current_timestamp(3)',
                'length' => null,
                'precision' => 3,
                'comment' => '',
                'onUpdate' => null,
            ],
            'updated' => [
                'type' => 'datetime',
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'length' => null,
                'precision' => null,
                'comment' => '',
                'onUpdate' => 'CURRENT_TIMESTAMP',
            ],
        ];

        $driver = ConnectionManager::get('test')->getDriver();
        // MariaDB 10.5+ uses utf8mb3 alias instead of utf8
        if (version_compare($driver->version(), '10.5.0', '>=')) {
            $expected['title']['collate'] = 'utf8mb3_general_ci';
            $expected['body']['collate'] = 'utf8mb3_general_ci';
        }

        $this->assertEquals(['id'], $result->getPrimaryKey());
        foreach ($expected as $field => $definition) {
            $this->assertEquals(
                $definition,
                $result->getColumn($field),
                'Field definition does not match for ' . $field,
            );

            // Integration test for column() method.
            $col = $result->column($field);
            $this->assertEquals($definition['type'], $col->getType());
            $this->assertEquals($definition['null'], $col->getNull());
            $this->assertEquals($definition['length'], $col->getLength());
            $this->assertEquals($definition['default'], $col->getDefault());
            $this->assertEquals($definition['precision'], $col->getPrecision());
            $this->assertEquals($definition['comment'], $col->getComment());
            if (isset($definition['onUpdate'])) {
                $this->assertEquals($definition['onUpdate'], $col->getOnUpdate());
            } else {
                $this->assertNull($col->getOnUpdate());
            }
            if (isset($definition['collate'])) {
                $this->assertEquals($definition['collate'], $col->getCollate());
            } else {
                $this->assertNull($col->getCollate());
            }
            if (isset($definition['autoIncrement'])) {
                $this->assertEquals($definition['autoIncrement'], $col->getIdentity());
            } else {
                $this->assertFalse($col->getIdentity());
            }
        }

        $columns = $dialect->describeColumns('schema_articles');
        foreach ($columns as $column) {
            $this->assertArrayHasKey($column['name'], $expected);
            $expectedItem = $expected[$column['name']];
            $expectedFields = array_intersect_key($expectedItem, $column);
            $resultFields = array_intersect_key($column, $expectedFields);
            $this->assertEquals($expectedFields, $resultFields);
        }
    }

    /**
     * Tests JSON column parsing on Mariadb which reflects as longtext
     */
    public function testDescribeJson(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);
        $this->skipIf(!$connection->getDriver()->supports(DriverFeatureEnum::JSON), 'Does not support native json');

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_json');
        $this->assertInstanceOf(TableSchema::class, $result);
        $expected = [
            'type' => 'text',
            'null' => false,
            'default' => null,
            'length' => 4294967295,
            'precision' => null,
            'comment' => '',
            'charset' => null,
            'collate' => 'utf8mb4_bin',
        ];
        $this->assertEquals(
            $expected,
            $result->getColumn('data'),
            'Field definition does not match for data',
        );
    }

    /**
     * Test that schema reflection works for geosptial columns.
     */
    public function testDescribeTableGeometry(): void
    {
        $this->_needsConnection();
        $connection = ConnectionManager::get('test');
        $driver = $connection->getDriver();

        $table = <<<SQL
CREATE TABLE schema_geometry (
    id INTEGER,
    geo_line LINESTRING,
    geo_geometry GEOMETRY,
    geo_point POINT
)
SQL;
        $connection->execute($table);
        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_geometry');
        $connection->execute('DROP TABLE schema_geometry');

        $expected = [
            'id' => [
                'type' => 'integer',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'unsigned' => false,
                'comment' => '',
                'autoIncrement' => null,
                'generated' => null,
            ],
            'geo_line' => [
                'type' => 'linestring',
                'null' => true,
                'default' => null,
                'precision' => null,
                'length' => null,
                'comment' => '',
                'srid' => null,
            ],
            'geo_geometry' => [
                'type' => 'geometry',
                'null' => true,
                'default' => null,
                'precision' => null,
                'length' => null,
                'comment' => '',
                'srid' => null,
            ],
            'geo_point' => [
                'type' => 'point',
                'null' => true,
                'default' => '',
                'precision' => null,
                'length' => null,
                'comment' => '',
                'srid' => null,
            ],
        ];
        foreach ($expected as $field => $definition) {
            $this->assertEquals($definition, $result->getColumn($field), "Mismatch in {$field} column");
        }
    }

    /**
     * Test describing a table with indexes in Mariadb
     *
     * Overrides a test in MysqlSchemaDialectTest
     */
    public function testDescribeTableIndexes(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $database = $connection->getDriver()->config()['database'];
        $dialect = $connection->getDriver()->schemaDialect();
        $result = $dialect->describe('schema_articles');
        $this->assertInstanceOf(TableSchema::class, $result);

        $expected = [
            'primary' => [
                'type' => 'primary',
                'columns' => ['id'],
            ],
            'length_idx' => [
                'type' => 'unique',
                'columns' => ['title'],
                'length' => [
                    'title' => 4,
                ],
            ],
            'author_idx_fk' => [
                'type' => 'foreign',
                'columns' => ['author_id'],
                'references' => ['schema_authors', 'id'],
                'update' => 'cascade',
                'delete' => 'restrict',
                'deferrable' => null,
            ],
            'unique_id_idx' => [
                'type' => 'unique',
                'columns' => [
                    'unique_id',
                ],
                'length' => [],
            ],
            'author_idx' => [
                'type' => 'index',
                'columns' => ['author_id'],
                'length' => [],
            ],
        ];

        $this->assertEquals($expected['primary'], $result->getConstraint('primary'));
        $primary = $result->constraint('primary');
        $this->assertEquals($expected['primary']['columns'], $primary->getColumns());
        $this->assertEquals('primary', $primary->getName());

        $this->assertEquals($expected['length_idx'], $result->getConstraint('length_idx'));
        $key = $result->constraint('length_idx');
        $this->assertEquals('length_idx', $key->getName());
        $this->assertEquals($expected['length_idx']['columns'], $key->getColumns());
        $this->assertEquals(['title' => 4], $key->getLength());

        $this->assertEquals($expected['author_idx_fk'], $result->getConstraint('author_idx'));
        $this->assertEquals($expected['unique_id_idx'], $result->getConstraint('unique_id_idx'));
        $key = $result->constraint('unique_id_idx');
        $this->assertEquals('unique_id_idx', $key->getName());
        $this->assertEquals($expected['unique_id_idx']['columns'], $key->getColumns());
        $this->assertSame([], $key->getLength(), 'length should be an empty array as it has been set.');

        $this->assertCount(1, $result->indexes());
        $this->assertEquals($expected['author_idx'], $result->getIndex('author_idx'));

        // Compare with describeIndexes() which includes indexes + uniques
        $indexes = $dialect->describeIndexes('schema_articles');
        $prefixed = $dialect->describeIndexes("{$database}.schema_articles");
        $this->assertEquals($indexes, $prefixed, 'prefixed tables should work');

        foreach ($indexes as $index) {
            $this->assertArrayHasKey($index['name'], $expected);
            $expectedItem = $expected[$index['name']];
            $expectedFields = array_intersect_key($expectedItem, $index);
            $resultFields = array_intersect_key($index, $expectedFields);

            $this->assertNotEmpty($resultFields);
            $this->assertEquals($expectedFields, $resultFields);

            // describeIndexes will return primary keys, and unique indexes which are
            if (in_array($index['type'], [TableSchema::INDEX_INDEX, TableSchema::INDEX_FULLTEXT], true)) {
                // Compare with the index() method as well.
                $indexObject = $result->index($index['name']);
            } else {
                // Compare with the constraint() method as well.
                $indexObject = $result->constraint($index['name']);
            }
            foreach ($expectedFields as $key => $value) {
                if ($key == 'length' && !method_exists($indexObject, 'getLength')) {
                    $this->assertEmpty($value, 'length should not be present in in this type');
                    continue;
                }
                $this->assertEquals($value, $indexObject->{'get' . ucfirst($key)}());
            }
        }

        // Compare describeForeignKeys()
        $keys = $dialect->describeForeignKeys('schema_articles');
        $prefixed = $dialect->describeForeignKeys("{$database}.schema_articles");
        $this->assertEquals($keys, $prefixed, 'prefixed tables should work');

        foreach ($keys as $foreignKey) {
            $name = $foreignKey['name'];
            if ($name === 'author_idx') {
                $name = 'author_idx_fk';
            }
            $this->assertArrayHasKey($name, $expected);
            $expectedItem = $expected[$name];
            $expectedFields = array_intersect_key($expectedItem, $foreignKey);
            $resultFields = array_intersect_key($foreignKey, $expectedFields);

            $this->assertNotEmpty($resultFields);
            $this->assertEquals($expectedFields, $resultFields);

            // Compare with the constraint() method as well.
            $indexObject = $result->constraint($foreignKey['name']);
            foreach ($expectedItem as $key => $value) {
                $this->assertInstanceOf(ForeignKey::class, $indexObject);
                if ($key == 'references') {
                    $this->assertEquals($value[0], $indexObject->getReferencedTable());
                    $this->assertEquals((array)$value[1], $indexObject->getReferencedColumns());
                    continue;
                }
                if ($key === 'length' && !($indexObject instanceof UniqueKey)) {
                    $this->assertEquals([], $value);
                    continue;
                }
                $this->assertEquals($value, $indexObject->{'get' . ucfirst($key)}());
            }
        }
    }

    /**
     * Get a schema instance with a mocked driver/pdo instances
     */
    protected function _getMockedDriver($version = '10.2.7'): Driver
    {
        $this->_needsConnection();

        $this->pdo = Mockery::mock(PDOMocked::class);
        $this->pdo->shouldReceive('quote')
            ->andReturnUsing(function ($value) {
                return "'{$value}'";
            });

        $driver = Mockery::mock(Mariadb::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $driver->__construct();

        $driver->shouldReceive('createPdo')
            ->andReturn($this->pdo);

        $driver->shouldReceive('version')
            ->andReturn($version);

        $driver->connect();

        return $driver;
    }
}
