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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Schema;

use Cake\Database\Connection;
use Cake\Database\Driver;
use Cake\Database\Driver\Mysql;
use Cake\Database\DriverFeatureEnum;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\MysqlSchemaDialect;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Exception;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test case for MySQL Schema Dialect.
 */
class MysqlSchemaDialectTest extends TestCase
{
    protected PDO $pdo;

    /**
     * Helper method for skipping tests that need a real connection.
     */
    protected function _needsConnection(): void
    {
        $config = ConnectionManager::getConfig('test');
        $this->skipIf(!str_contains($config['driver'], 'Mysql'), 'Not using Mysql for test config');
    }

    /**
     * Data provider for convert column testing
     *
     * @return array
     */
    public static function convertColumnProvider(): array
    {
        return [
            [
                'DATETIME',
                ['type' => 'datetime', 'length' => null],
            ],
            [
                'DATETIME(0)',
                ['type' => 'datetime', 'length' => null],
            ],
            [
                'DATETIME(6)',
                ['type' => 'datetimefractional', 'length' => null, 'precision' => 6],
            ],
            [
                'DATE',
                ['type' => 'date', 'length' => null],
            ],
            [
                'TIME',
                ['type' => 'time', 'length' => null],
            ],
            [
                'TIMESTAMP',
                ['type' => 'timestamp', 'length' => null],
            ],
            [
                'TIMESTAMP(0)',
                ['type' => 'timestamp', 'length' => null],
            ],
            [
                'TIMESTAMP(6)',
                ['type' => 'timestampfractional', 'length' => null, 'precision' => 6],
            ],
            [
                'TINYINT(1)',
                ['type' => 'boolean', 'length' => null],
            ],
            [
                'TINYINT(1) UNSIGNED',
                ['type' => 'boolean', 'length' => null],
            ],
            [
                'TINYINT(3)',
                ['type' => 'tinyinteger', 'length' => null, 'unsigned' => false],
            ],
            [
                'TINYINT(3) UNSIGNED',
                ['type' => 'tinyinteger', 'length' => null, 'unsigned' => true],
            ],
            [
                'SMALLINT(4)',
                ['type' => 'smallinteger', 'length' => null, 'unsigned' => false],
            ],
            [
                'SMALLINT(4) UNSIGNED',
                ['type' => 'smallinteger', 'length' => null, 'unsigned' => true],
            ],
            [
                'INTEGER(11)',
                ['type' => 'integer', 'length' => null, 'unsigned' => false],
            ],
            [
                'MEDIUMINT(11)',
                ['type' => 'integer', 'length' => null, 'unsigned' => false],
            ],
            [
                'INTEGER(11) UNSIGNED',
                ['type' => 'integer', 'length' => null, 'unsigned' => true],
            ],
            [
                'BIGINT',
                ['type' => 'biginteger', 'length' => null, 'unsigned' => false],
            ],
            [
                'BIGINT UNSIGNED',
                ['type' => 'biginteger', 'length' => null, 'unsigned' => true],
            ],
            [
                'VARCHAR(255)',
                ['type' => 'string', 'length' => 255, 'collate' => 'utf8_general_ci'],
            ],
            [
                'CHAR(25)',
                ['type' => 'char', 'length' => 25],
            ],
            [
                'CHAR(36)',
                ['type' => 'uuid', 'length' => null],
            ],
            [
                'UUID',
                ['type' => 'nativeuuid', 'length' => null],
            ],
            [
                'BINARY(16)',
                ['type' => 'binaryuuid', 'length' => null],
            ],
            [
                'BINARY(1)',
                ['type' => 'binary', 'length' => 1],
            ],
            [
                'TEXT',
                ['type' => 'text', 'length' => null, 'collate' => 'utf8_general_ci'],
            ],
            [
                'TINYTEXT',
                ['type' => 'text', 'length' => TableSchema::LENGTH_TINY, 'collate' => 'utf8_general_ci'],
            ],
            [
                'MEDIUMTEXT',
                ['type' => 'text', 'length' => TableSchema::LENGTH_MEDIUM, 'collate' => 'utf8_general_ci'],
            ],
            [
                'LONGTEXT',
                ['type' => 'text', 'length' => TableSchema::LENGTH_LONG, 'collate' => 'utf8_general_ci'],
            ],
            [
                'TINYBLOB',
                ['type' => 'binary', 'length' => TableSchema::LENGTH_TINY],
            ],
            [
                'BLOB',
                ['type' => 'binary', 'length' => null],
            ],
            [
                'MEDIUMBLOB',
                ['type' => 'binary', 'length' => TableSchema::LENGTH_MEDIUM],
            ],
            [
                'LONGBLOB',
                ['type' => 'binary', 'length' => TableSchema::LENGTH_LONG],
            ],
            [
                'FLOAT',
                ['type' => 'float', 'length' => null, 'precision' => null, 'unsigned' => false],
            ],
            [
                'DOUBLE',
                ['type' => 'float', 'length' => null, 'precision' => null, 'unsigned' => false],
            ],
            [
                'DOUBLE UNSIGNED',
                ['type' => 'float', 'length' => null, 'precision' => null, 'unsigned' => true],
            ],
            [
                'DECIMAL(11,2) UNSIGNED',
                ['type' => 'decimal', 'length' => 11, 'precision' => 2, 'unsigned' => true],
            ],
            [
                'DECIMAL(11,2)',
                ['type' => 'decimal', 'length' => 11, 'precision' => 2, 'unsigned' => false],
            ],
            [
                'FLOAT(11,2)',
                ['type' => 'float', 'length' => 11, 'precision' => 2, 'unsigned' => false],
            ],
            [
                'FLOAT(11,2) UNSIGNED',
                ['type' => 'float', 'length' => 11, 'precision' => 2, 'unsigned' => true],
            ],
            [
                'DOUBLE(10,4)',
                ['type' => 'float', 'length' => 10, 'precision' => 4, 'unsigned' => false],
            ],
            [
                'DOUBLE(10,4) UNSIGNED',
                ['type' => 'float', 'length' => 10, 'precision' => 4, 'unsigned' => true],
            ],
            [
                'JSON',
                ['type' => 'json', 'length' => null],
            ],
            [
                'GEOMETRY',
                ['type' => 'geometry', 'length' => null],
            ],
            [
                'POINT',
                ['type' => 'point', 'length' => null],
            ],
            [
                'LINESTRING',
                ['type' => 'linestring', 'length' => null],
            ],
            [
                'POLYGON',
                ['type' => 'polygon', 'length' => null],
            ],
        ];
    }

    /**
     * Test parsing MySQL column types from field description.
     */
    #[DataProvider('convertColumnProvider')]
    public function testConvertColumn(string $type, array $expected): void
    {
        $field = [
            'Field' => 'field',
            'Type' => $type,
            'Null' => 'YES',
            'Default' => 'Default value',
            'Collation' => 'utf8_general_ci',
            'Comment' => 'Comment section',
        ];
        $expected += [
            'null' => true,
            'default' => 'Default value',
            'comment' => 'Comment section',
        ];
        $driver = $this->getMockBuilder(Mysql::class)->getMock();
        $dialect = new MysqlSchemaDialect($driver);

        $table = new TableSchema('table');
        $dialect->convertColumnDescription($table, $field);

        $actual = array_intersect_key($table->getColumn('field'), $expected);
        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * Helper method for testing methods.
     *
     * @param \Cake\Datasource\ConnectionInterface $connection
     */
    protected function _createTables($connection): void
    {
        $this->_needsConnection();
        $connection->execute('DROP TABLE IF EXISTS schema_articles');
        $connection->execute('DROP TABLE IF EXISTS schema_authors');
        $connection->execute('DROP TABLE IF EXISTS schema_json');
        $connection->execute('DROP VIEW IF EXISTS schema_articles_v');

        $table = <<<SQL
            CREATE TABLE schema_authors (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(50),
                bio TEXT,
                created DATETIME
            )ENGINE=InnoDB
SQL;
        $connection->execute($table);

        $table = <<<SQL
            CREATE TABLE schema_articles (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                title VARCHAR(20) COMMENT 'A title',
                body TEXT,
                author_id INT NOT NULL,
                unique_id INT NOT NULL,
                published BOOLEAN DEFAULT 0,
                allow_comments TINYINT(1) DEFAULT 0,
                location POINT,
                created DATETIME,
                created_with_precision DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
                updated DATETIME ON UPDATE CURRENT_TIMESTAMP,
                KEY `author_idx` (`author_id`),
                CONSTRAINT `length_idx` UNIQUE KEY(`title`(4)),
                FOREIGN KEY `author_idx` (`author_id`) REFERENCES `schema_authors`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
                UNIQUE INDEX `unique_id_idx` (`unique_id`)
            ) ENGINE=InnoDB COLLATE=utf8_general_ci
SQL;
        $connection->execute($table);

        $table = <<<SQL
            CREATE OR REPLACE VIEW schema_articles_v
                AS SELECT 1
SQL;
        $connection->execute($table);

        if ($connection->getDriver()->supports(DriverFeatureEnum::JSON)) {
            $table = <<<SQL
                CREATE TABLE schema_json (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    data JSON NOT NULL
                )
SQL;
            $connection->execute($table);
        }
    }

    /**
     * Integration test for SchemaCollection & MysqlSchemaDialect.
     */
    public function testListTables(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);
        $schema = new SchemaCollection($connection);

        $result = $schema->listTables();
        $this->assertIsArray($result);
        $this->assertContains('schema_articles', $result);
        $this->assertContains('schema_articles_v', $result);
        $this->assertContains('schema_authors', $result);

        $resultNoViews = $schema->listTablesWithoutViews();
        $this->assertIsArray($resultNoViews);
        $this->assertNotContains('schema_articles_v', $resultNoViews);
        $this->assertContains('schema_articles', $resultNoViews);
    }

    /**
     * Test describing a table with MySQL
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
            ],
            'title' => [
                'type' => 'string',
                'null' => true,
                'default' => null,
                'length' => 20,
                'precision' => null,
                'comment' => 'A title',
                'collate' => 'utf8_general_ci',
            ],
            'body' => [
                'type' => 'text',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
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
                'default' => 'CURRENT_TIMESTAMP(3)',
                'length' => null,
                'precision' => 3,
                'comment' => null,
                'onUpdate' => null,
            ],
            'updated' => [
                'type' => 'datetime',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
                'onUpdate' => 'CURRENT_TIMESTAMP',
            ],
        ];

        $driver = ConnectionManager::get('test')->getDriver();
        if ($driver->isMariaDb()) {
            $expected['created_with_precision']['default'] = 'current_timestamp(3)';
            $expected['created_with_precision']['comment'] = '';
        }
        if ($driver->isMariaDb() || version_compare($driver->version(), '8.0.30', '>=')) {
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
     * Test describing a table with MySQL
     */
    public function testDescribeTableDatabasePrefix(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $config = $connection->getDriver()->config();
        $dialect = $connection->getDriver()->schemaDialect();

        $result = $dialect->describe($config['database'] . '.schema_articles');
        $this->assertInstanceOf(TableSchema::class, $result);
    }

    /**
     * Test that schema reflection works for geosptial columns.
     *
     * We currently cannot reflect the postgis types from postgres.
     *
     * @return void
     */
    public function testDescribeTableGeometry(): void
    {
        $this->_needsConnection();
        $connection = ConnectionManager::get('test');
        $driver = $connection->getDriver();

        $hasGeometry = $driver->isMariaDb() || version_compare($driver->version(), '8.0.30', '>=');
        $this->skipIf(!$hasGeometry, 'This test requires geometry type support');

        $table = <<<SQL
CREATE TABLE schema_geometry (
    id INTEGER,
    geo_line LINESTRING,
    geo_geometry GEOMETRY,
    geo_point POINT,
    geo_polygon POLYGON
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
                'default' => null,
                'precision' => null,
                'length' => null,
                'comment' => '',
                'srid' => null,
            ],
            'geo_polygon' => [
                'type' => 'polygon',
                'null' => true,
                'default' => null,
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
     * Test describing a table with indexes in MySQL
     */
    public function testDescribeTableIndexes(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $database = $connection->getDriver()->config()['database'];
        $dialect = $connection->getDriver()->schemaDialect();
        $result = $dialect->describe('schema_articles');
        $this->assertInstanceOf(TableSchema::class, $result);

        $this->assertCount(4, $result->constraints());
        $expected = [
            'primary' => [
                'type' => 'primary',
                'columns' => ['id'],
                'length' => [],
            ],
            'length_idx' => [
                'type' => 'unique',
                'columns' => ['title'],
                'length' => [
                    'title' => 4,
                ],
            ],
            'schema_articles_ibfk_1' => [
                'type' => 'foreign',
                'columns' => ['author_id'],
                'references' => ['schema_authors', 'id'],
                'length' => [],
                'update' => 'cascade',
                'delete' => 'restrict',
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
        $this->assertEquals($expected['length_idx'], $result->getConstraint('length_idx'));
        if (ConnectionManager::get('test')->getDriver()->isMariadb()) {
            $this->assertEquals($expected['schema_articles_ibfk_1'], $result->getConstraint('author_idx'));
        } else {
            $this->assertEquals($expected['schema_articles_ibfk_1'], $result->getConstraint('schema_articles_ibfk_1'));
        }
        $this->assertEquals($expected['unique_id_idx'], $result->getConstraint('unique_id_idx'));

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
        }

        // Compare describeForeignKeys()
        $keys = $dialect->describeForeignKeys('schema_articles');
        $prefixed = $dialect->describeForeignKeys("{$database}.schema_articles");
        $this->assertEquals($keys, $prefixed, 'prefixed tables should work');

        $isMariaDb = ConnectionManager::get('test')->getDriver()->isMariaDb();
        foreach ($keys as $foreignKey) {
            $name = $foreignKey['name'];
            if ($name === 'author_idx' && $isMariaDb) {
                $name = 'schema_articles_ibfk_1';
            }
            $this->assertArrayHasKey($name, $expected);
            $expectedItem = $expected[$name];
            $expectedFields = array_intersect_key($expectedItem, $foreignKey);
            $resultFields = array_intersect_key($foreignKey, $expectedFields);

            $this->assertNotEmpty($resultFields);
            $this->assertEquals($expectedFields, $resultFields);
        }
    }

    /**
     * Test describing a table with conditional constraints
     */
    public function testDescribeTableConditionalConstraint(): void
    {
        $this->_needsConnection();
        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS conditional_constraint');
        $table = <<<SQL
CREATE TABLE conditional_constraint (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_id INT UNSIGNED NOT NULL,
    status ENUM ('new', 'processing', 'completed', 'failed') DEFAULT 'new' NOT NULL,
    CONSTRAINT unique_index UNIQUE (config_id, (
        (CASE WHEN ((`status` = "new") OR (`status` = "processing")) THEN `status` END)
    ))
);
SQL;
        try {
            $connection->execute($table);
        } catch (Exception) {
            $this->markTestSkipped('Could not create table with conditional constraint');
        }
        $schema = new SchemaCollection($connection);
        $result = $schema->describe('conditional_constraint');
        $connection->execute('DROP TABLE IF EXISTS conditional_constraint');

        $constraint = $result->getConstraint('unique_index');
        $this->assertNotEmpty($constraint);
        $this->assertEquals('unique', $constraint['type']);
        $this->assertEquals(['config_id'], $constraint['columns']);
    }

    public function testDescribeTableFunctionalIndex(): void
    {
        $this->_needsConnection();
        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS functional_index');
        $table = <<<SQL
CREATE TABLE functional_index (
    id INT AUTO_INCREMENT PRIMARY KEY,
    properties JSON,
    child_ids VARCHAR(400) GENERATED ALWAYS AS (
        properties->>'$.children[*].id'
    ) VIRTUAL
);
SQL;
        $index = <<<SQL
CREATE INDEX child_ids_idx ON functional_index ((CAST(child_ids AS UNSIGNED ARRAY)));
SQL;
        try {
            $connection->execute($table);
            $connection->execute($index);
        } catch (Exception) {
            $this->markTestSkipped('Could not create table with functional index');
        }
        $schema = new SchemaCollection($connection);
        $result = $schema->describe('functional_index');
        $connection->execute('DROP TABLE IF EXISTS functional_index');

        $column = $result->getColumn('child_ids');
        $this->assertNotEmpty($column, 'Virtual property column should be reflected');
        $this->assertEquals('string', $column['type']);

        $index = $result->getIndex('child_ids_idx');
        $this->assertNotEmpty($index);
        $this->assertEquals('index', $index['type']);
        $this->assertEquals([], $index['columns']);
    }

    /**
     * Test describing a table creates options
     */
    public function testDescribeTableOptions(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $dialect = $connection->getDriver()->schemaDialect();
        $result = $dialect->describeOptions('schema_articles');
        $this->assertArrayHasKey('engine', $result);
        $this->assertArrayHasKey('collation', $result);
    }

    public function testDescribeNonPrimaryAutoIncrement(): void
    {
        $this->_needsConnection();
        $connection = ConnectionManager::get('test');

        $sql = <<<SQL
CREATE TABLE `odd_primary_key` (
`id` BIGINT UNSIGNED NOT NULL,
`other_field` INTEGER NOT NULL AUTO_INCREMENT,
PRIMARY KEY (`id`),
UNIQUE KEY `other_field` (`other_field`)
)
SQL;
        $connection->execute($sql);
        $schema = new SchemaCollection($connection);
        $table = $schema->describe('odd_primary_key');
        $connection->execute('DROP TABLE odd_primary_key');

        $column = $table->getColumn('other_field');
        $this->assertTrue($column['autoIncrement']);
    }

    /**
     * Column provider for creating column sql
     *
     * @return array
     */
    public static function columnSqlProvider(): array
    {
        return [
            // strings
            [
                'title',
                ['type' => 'string', 'length' => 25, 'null' => true, 'default' => null],
                '`title` VARCHAR(25)',
            ],
            [
                'title',
                ['type' => 'string', 'length' => 25, 'null' => false],
                '`title` VARCHAR(25) NOT NULL',
            ],
            [
                'title',
                ['type' => 'string', 'length' => 25, 'null' => true, 'default' => 'ignored'],
                "`title` VARCHAR(25) DEFAULT 'ignored'",
            ],
            [
                'title',
                ['type' => 'string', 'length' => 25, 'null' => true, 'default' => ''],
                "`title` VARCHAR(25) DEFAULT ''",
            ],
            [
                'role',
                ['type' => 'string', 'length' => 10, 'null' => false, 'default' => 'admin'],
                "`role` VARCHAR(10) NOT NULL DEFAULT 'admin'",
            ],
            [
                'id',
                ['type' => 'char', 'length' => 32, 'fixed' => true, 'null' => false],
                '`id` CHAR(32) NOT NULL',
            ],
            [
                'title',
                ['type' => 'string'],
                '`title` VARCHAR(255)',
            ],
            [
                'id',
                ['type' => 'uuid'],
                '`id` CHAR(36)',
            ],
            [
                'id',
                ['type' => 'nativeuuid'],
                '`id` UUID',
            ],
            [
                'id',
                ['type' => 'char', 'length' => 36],
                '`id` CHAR(36)',
            ],
            [
                'id',
                ['type' => 'binaryuuid'],
                '`id` BINARY(16)',
            ],
            [
                'title',
                ['type' => 'string', 'length' => 255, 'null' => false, 'collate' => 'utf8_unicode_ci'],
                '`title` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL',
            ],
            // Text
            [
                'body',
                ['type' => 'text', 'null' => false],
                '`body` TEXT NOT NULL',
            ],
            [
                'body',
                ['type' => 'text', 'length' => TableSchema::LENGTH_TINY, 'null' => false],
                '`body` TINYTEXT NOT NULL',
            ],
            [
                'body',
                ['type' => 'text', 'length' => TableSchema::LENGTH_MEDIUM, 'null' => false],
                '`body` MEDIUMTEXT NOT NULL',
            ],
            [
                'body',
                ['type' => 'text', 'length' => TableSchema::LENGTH_LONG, 'null' => false],
                '`body` LONGTEXT NOT NULL',
            ],
            [
                'body',
                ['type' => 'text', 'null' => false, 'collate' => 'utf8_unicode_ci'],
                '`body` TEXT COLLATE utf8_unicode_ci NOT NULL',
            ],
            // Blob / binary
            [
                'body',
                ['type' => 'binary', 'null' => false],
                '`body` BLOB NOT NULL',
            ],
            [
                'body',
                ['type' => 'binary', 'length' => TableSchema::LENGTH_TINY, 'null' => false],
                '`body` TINYBLOB NOT NULL',
            ],
            [
                'body',
                ['type' => 'binary', 'length' => TableSchema::LENGTH_MEDIUM, 'null' => false],
                '`body` MEDIUMBLOB NOT NULL',
            ],
            [
                'body',
                ['type' => 'binary', 'length' => TableSchema::LENGTH_LONG, 'null' => false],
                '`body` LONGBLOB NOT NULL',
            ],
            [
                'bytes',
                ['type' => 'binary', 'length' => 5],
                '`bytes` VARBINARY(5)',
            ],
            [
                'bit',
                ['type' => 'binary', 'length' => 1],
                '`bit` BINARY(1)',
            ],
            // Integers
            [
                'post_id',
                ['type' => 'tinyinteger'],
                '`post_id` TINYINT',
            ],
            [
                'post_id',
                ['type' => 'tinyinteger', 'unsigned' => true],
                '`post_id` TINYINT UNSIGNED',
            ],
            [
                'post_id',
                ['type' => 'smallinteger'],
                '`post_id` SMALLINT',
            ],
            [
                'post_id',
                ['type' => 'smallinteger', 'unsigned' => true],
                '`post_id` SMALLINT UNSIGNED',
            ],
            [
                'post_id',
                ['type' => 'integer'],
                '`post_id` INTEGER',
            ],
            [
                'post_id',
                ['type' => 'integer', 'unsigned' => true],
                '`post_id` INTEGER UNSIGNED',
            ],
            [
                'post_id',
                ['type' => 'biginteger'],
                '`post_id` BIGINT',
            ],
            [
                'post_id',
                ['type' => 'biginteger', 'unsigned' => true],
                '`post_id` BIGINT UNSIGNED',
            ],
            [
                'post_id',
                ['type' => 'integer', 'autoIncrement' => true],
                '`post_id` INTEGER AUTO_INCREMENT',
            ],
            [
                'post_id',
                ['type' => 'integer', 'null' => false, 'autoIncrement' => false],
                '`post_id` INTEGER NOT NULL',
            ],
            [
                'post_id',
                ['type' => 'biginteger', 'autoIncrement' => true],
                '`post_id` BIGINT AUTO_INCREMENT',
            ],
            // Decimal
            [
                'value',
                ['type' => 'decimal'],
                '`value` DECIMAL',
            ],
            [
                'value',
                ['type' => 'decimal', 'length' => 11, 'unsigned' => true],
                '`value` DECIMAL(11) UNSIGNED',
            ],
            [
                'value',
                ['type' => 'decimal', 'length' => 12, 'precision' => 5],
                '`value` DECIMAL(12,5)',
            ],
            // Float
            [
                'value',
                ['type' => 'float', 'unsigned'],
                '`value` FLOAT',
            ],
            [
                'value',
                ['type' => 'float', 'unsigned' => true],
                '`value` FLOAT UNSIGNED',
            ],
            [
                'latitude',
                ['type' => 'float', 'length' => 53, 'null' => true, 'default' => null, 'unsigned' => true],
                '`latitude` FLOAT(53) UNSIGNED',
            ],
            [
                'value',
                ['type' => 'float', 'length' => 11, 'precision' => 3],
                '`value` FLOAT(11,3)',
            ],
            // Boolean
            [
                'checked',
                ['type' => 'boolean', 'default' => false],
                '`checked` BOOLEAN DEFAULT FALSE',
            ],
            [
                'checked',
                ['type' => 'boolean', 'default' => false, 'null' => false],
                '`checked` BOOLEAN NOT NULL DEFAULT FALSE',
            ],
            [
                'checked',
                ['type' => 'boolean', 'default' => true, 'null' => false],
                '`checked` BOOLEAN NOT NULL DEFAULT TRUE',
            ],
            [
                'checked',
                ['type' => 'boolean', 'default' => false, 'null' => true],
                '`checked` BOOLEAN DEFAULT FALSE',
            ],
            // datetimes
            [
                'created',
                ['type' => 'datetime', 'comment' => 'Created timestamp'],
                "`created` DATETIME COMMENT 'Created timestamp'",
            ],
            [
                'created',
                ['type' => 'datetime', 'null' => false, 'default' => 'current_timestamp'],
                '`created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            ],
            [
                'open_date',
                ['type' => 'datetime', 'null' => false, 'default' => '2016-12-07 23:04:00'],
                "`open_date` DATETIME NOT NULL DEFAULT '2016-12-07 23:04:00'",
            ],
            [
                'created_with_precision',
                ['type' => 'datetimefractional', 'precision' => 3, 'null' => false, 'default' => 'current_timestamp'],
                '`created_with_precision` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)',
            ],
            // Date & Time
            [
                'start_date',
                ['type' => 'date'],
                '`start_date` DATE',
            ],
            [
                'start_time',
                ['type' => 'time'],
                '`start_time` TIME',
            ],
            // timestamps
            [
                'created',
                ['type' => 'timestamp', 'null' => true],
                '`created` TIMESTAMP NULL',
            ],
            [
                'created',
                ['type' => 'timestamp', 'null' => false, 'default' => 'current_timestamp'],
                '`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
            ],
            [
                'created',
                ['type' => 'timestamp', 'null' => false, 'default' => 'current_timestamp()'],
                '`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
            ],
            [
                'open_date',
                ['type' => 'timestamp', 'null' => false, 'default' => '2016-12-07 23:04:00'],
                "`open_date` TIMESTAMP NOT NULL DEFAULT '2016-12-07 23:04:00'",
            ],
            [
                'created_with_precision',
                ['type' => 'timestampfractional', 'precision' => 3, 'null' => false, 'default' => 'current_timestamp'],
                '`created_with_precision` TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)',
            ],
            [
                'updated',
                [
                    'type' => 'timestamp',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                    'onUpdate' => 'CURRENT_TIMESTAMP',
                ],
                '`updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            ],
            // Geospatial types
            [
                'g',
                ['type' => 'geometry'],
                '`g` GEOMETRY',
            ],
            [
                'g',
                ['type' => 'geometry', 'null' => false, 'srid' => 4326],
                '`g` GEOMETRY NOT NULL SRID 4326',
            ],
            [
                'p',
                ['type' => 'point'],
                '`p` POINT',
            ],
            [
                'p',
                ['type' => 'point', 'null' => false, 'srid' => 4326],
                '`p` POINT NOT NULL SRID 4326',
            ],
            [
                'l',
                ['type' => 'linestring'],
                '`l` LINESTRING',
            ],
            [
                'l',
                ['type' => 'linestring', 'null' => false, 'srid' => 4326],
                '`l` LINESTRING NOT NULL SRID 4326',
            ],
            [
                'p',
                ['type' => 'polygon'],
                '`p` POLYGON',
            ],
            [
                'p',
                ['type' => 'polygon', 'null' => false, 'srid' => 4326],
                '`p` POLYGON NOT NULL SRID 4326',
            ],
        ];
    }

    /**
     * Test generating column definitions
     */
    #[DataProvider('columnSqlProvider')]
    public function testColumnSql(string $name, array $data, string $expected): void
    {
        $driver = $this->_getMockedDriver();
        $dialect = new MysqlSchemaDialect($driver);

        $table = (new TableSchema('articles'))->addColumn($name, $data);
        $this->assertEquals($expected, $dialect->columnSql($table, $name));

        $data['name'] = $name;
        $this->assertEquals($expected, $dialect->columnDefinitionSql($data));
    }

    /**
     * Provide data for testing constraintSql
     *
     * @return array
     */
    public static function constraintSqlProvider(): array
    {
        return [
            [
                'primary',
                ['type' => 'primary', 'columns' => ['title']],
                'PRIMARY KEY (`title`)',
            ],
            [
                'unique_idx',
                ['type' => 'unique', 'columns' => ['title', 'author_id']],
                'UNIQUE KEY `unique_idx` (`title`, `author_id`)',
            ],
            [
                'length_idx',
                [
                    'type' => 'unique',
                    'columns' => ['author_id', 'title'],
                    'length' => ['author_id' => 5, 'title' => 4],
                ],
                'UNIQUE KEY `length_idx` (`author_id`(5), `title`(4))',
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id']],
                'CONSTRAINT `author_id_idx` FOREIGN KEY (`author_id`) ' .
                'REFERENCES `authors` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT',
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'cascade'],
                'CONSTRAINT `author_id_idx` FOREIGN KEY (`author_id`) ' .
                'REFERENCES `authors` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT',
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'restrict'],
                'CONSTRAINT `author_id_idx` FOREIGN KEY (`author_id`) ' .
                'REFERENCES `authors` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT',
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'setNull'],
                'CONSTRAINT `author_id_idx` FOREIGN KEY (`author_id`) ' .
                'REFERENCES `authors` (`id`) ON UPDATE SET NULL ON DELETE RESTRICT',
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'noAction'],
                'CONSTRAINT `author_id_idx` FOREIGN KEY (`author_id`) ' .
                'REFERENCES `authors` (`id`) ON UPDATE NO ACTION ON DELETE RESTRICT',
            ],
        ];
    }

    /**
     * Test the constraintSql method.
     */
    #[DataProvider('constraintSqlProvider')]
    public function testConstraintSql(string $name, array $data, string $expected): void
    {
        $driver = $this->_getMockedDriver();
        $schema = new MysqlSchemaDialect($driver);

        $table = (new TableSchema('articles'))->addColumn('title', [
            'type' => 'string',
            'length' => 255,
        ])->addColumn('author_id', [
            'type' => 'integer',
        ])->addConstraint($name, $data);

        $this->assertEquals($expected, $schema->constraintSql($table, $name));
    }

    /**
     * Test provider for indexSql()
     *
     * @return array
     */
    public static function indexSqlProvider(): array
    {
        return [
            [
                'key_key',
                ['type' => 'index', 'columns' => ['author_id']],
                'KEY `key_key` (`author_id`)',
            ],
            [
                'full_text',
                ['type' => 'fulltext', 'columns' => ['title']],
                'FULLTEXT KEY `full_text` (`title`)',
            ],
        ];
    }

    /**
     * Test the indexSql method.
     */
    #[DataProvider('indexSqlProvider')]
    public function testIndexSql(string $name, array $data, string $expected): void
    {
        $driver = $this->_getMockedDriver();
        $schema = new MysqlSchemaDialect($driver);

        $table = (new TableSchema('articles'))->addColumn('title', [
            'type' => 'string',
            'length' => 255,
        ])->addColumn('author_id', [
            'type' => 'integer',
        ])->addIndex($name, $data);

        $this->assertEquals($expected, $schema->indexSql($table, $name));
    }

    /**
     * Test the addConstraintSql method.
     */
    public function testAddConstraintSql(): void
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('getDriver')
            ->willReturn($driver);

        $table = (new TableSchema('posts'))
            ->addColumn('author_id', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addColumn('category_id', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addColumn('category_name', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addConstraint('author_fk', [
                'type' => 'foreign',
                'columns' => ['author_id'],
                'references' => ['authors', 'id'],
                'update' => 'cascade',
                'delete' => 'cascade',
            ])
            ->addConstraint('category_fk', [
                'type' => 'foreign',
                'columns' => ['category_id', 'category_name'],
                'references' => ['categories', ['id', 'name']],
                'update' => 'cascade',
                'delete' => 'cascade',
            ]);

        $expected = [
            'ALTER TABLE `posts` ADD CONSTRAINT `author_fk` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;',
            'ALTER TABLE `posts` ADD CONSTRAINT `category_fk` FOREIGN KEY (`category_id`, `category_name`) REFERENCES `categories` (`id`, `name`) ON UPDATE CASCADE ON DELETE CASCADE;',
        ];
        $result = $table->addConstraintSql($connection);
        $this->assertCount(2, $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the dropConstraintSql method.
     */
    public function testDropConstraintSql(): void
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('getDriver')
            ->willReturn($driver);

        $table = (new TableSchema('posts'))
            ->addColumn('author_id', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addColumn('category_id', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addColumn('category_name', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addConstraint('author_fk', [
                'type' => 'foreign',
                'columns' => ['author_id'],
                'references' => ['authors', 'id'],
                'update' => 'cascade',
                'delete' => 'cascade',
            ])
            ->addConstraint('category_fk', [
                'type' => 'foreign',
                'columns' => ['category_id', 'category_name'],
                'references' => ['categories', ['id', 'name']],
                'update' => 'cascade',
                'delete' => 'cascade',
            ]);

        $expected = [
            'ALTER TABLE `posts` DROP FOREIGN KEY `author_fk`;',
            'ALTER TABLE `posts` DROP FOREIGN KEY `category_fk`;',
        ];
        $result = $table->dropConstraintSql($connection);
        $this->assertCount(2, $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test generating a column that is a primary key.
     */
    public function testColumnSqlPrimaryKey(): void
    {
        $driver = $this->_getMockedDriver();
        $schema = new MysqlSchemaDialect($driver);

        $table = new TableSchema('articles');
        $table->addColumn('id', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id'],
            ]);
        $result = $schema->columnSql($table, 'id');
        $this->assertSame('`id` INTEGER NOT NULL AUTO_INCREMENT', $result);

        $table = new TableSchema('articles');
        $table->addColumn('id', [
                'type' => 'biginteger',
                'null' => false,
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id'],
            ]);
        $result = $schema->columnSql($table, 'id');
        $this->assertSame('`id` BIGINT NOT NULL AUTO_INCREMENT', $result);
    }

    /**
     * Integration test for converting a Schema\Table into MySQL table creates.
     */
    public function testCreateSql(): void
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('getDriver')
            ->willReturn($driver);

        $this->pdo
            ->expects($this->any())
            ->method('getAttribute')
            ->willReturn('5.6.0');

        $table = (new TableSchema('posts'))->addColumn('id', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addColumn('title', [
                'type' => 'string',
                'null' => false,
                'comment' => 'The title',
            ])
            ->addColumn('body', [
                'type' => 'text',
                'comment' => '',
            ])
            ->addColumn('data', [
                'type' => 'json',
            ])
            ->addColumn('hash', [
                'type' => 'char',
                'fixed' => true,
                'length' => 40,
                'collate' => 'latin1_bin',
                'null' => false,
            ])
            ->addColumn('created', 'datetime')
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id'],
            ])
            ->setOptions([
                'engine' => 'InnoDB',
                'charset' => 'utf8',
                'collate' => 'utf8_general_ci',
            ]);

        $expected = <<<SQL
CREATE TABLE `posts` (
`id` INTEGER NOT NULL AUTO_INCREMENT,
`title` VARCHAR(255) NOT NULL COMMENT 'The title',
`body` TEXT,
`data` LONGTEXT,
`hash` CHAR(40) COLLATE latin1_bin NOT NULL,
`created` DATETIME,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
SQL;
        $result = $table->createSql($connection);
        $this->assertCount(1, $result);
        $this->assertTextEquals($expected, $result[0]);
    }

    /**
     * Integration test for converting a Schema\Table with native JSON
     */
    public function testCreateSqlJson(): void
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())
            ->method('getDriver')
            ->willReturn($driver);

        $this->pdo
            ->expects($this->any())
            ->method('getAttribute')
            ->willReturn('5.7.0');

        $table = (new TableSchema('posts'))->addColumn('id', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addColumn('data', [
                'type' => 'json',
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id'],
            ])
            ->setOptions([
                'engine' => 'InnoDB',
                'charset' => 'utf8',
                'collate' => 'utf8_general_ci',
            ]);

        $expected = <<<SQL
CREATE TABLE `posts` (
`id` INTEGER NOT NULL AUTO_INCREMENT,
`data` JSON,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
SQL;
        $result = $table->createSql($connection);
        $this->assertCount(1, $result);
        $this->assertTextEquals($expected, $result[0]);
    }

    /**
     * Tests creating temporary tables
     */
    public function testCreateTemporary(): void
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('getDriver')
            ->willReturn($driver);
        $table = (new TableSchema('schema_articles'))->addColumn('id', [
            'type' => 'integer',
            'null' => false,
        ]);
        $table->setTemporary(true);
        $sql = $table->createSql($connection);
        $this->assertStringContainsString('CREATE TEMPORARY TABLE', $sql[0]);
    }

    /**
     * Test primary key generation & auto-increment.
     */
    public function testCreateSqlCompositeIntegerKey(): void
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('getDriver')
            ->willReturn($driver);

        $table = (new TableSchema('articles_tags'))
            ->addColumn('article_id', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addColumn('tag_id', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['article_id', 'tag_id'],
            ]);

        $expected = <<<SQL
CREATE TABLE `articles_tags` (
`article_id` INTEGER NOT NULL,
`tag_id` INTEGER NOT NULL,
PRIMARY KEY (`article_id`, `tag_id`)
)
SQL;
        $result = $table->createSql($connection);
        $this->assertCount(1, $result);
        $this->assertTextEquals($expected, $result[0]);

        $table = (new TableSchema('composite_key'))
            ->addColumn('id', [
                'type' => 'integer',
                'null' => false,
                'autoIncrement' => true,
            ])
            ->addColumn('account_id', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id', 'account_id'],
            ]);

        $expected = <<<SQL
CREATE TABLE `composite_key` (
`id` INTEGER NOT NULL AUTO_INCREMENT,
`account_id` INTEGER NOT NULL,
PRIMARY KEY (`id`, `account_id`)
)
SQL;
        $result = $table->createSql($connection);
        $this->assertCount(1, $result);
        $this->assertTextEquals($expected, $result[0]);
    }

    /**
     * test dropSql
     */
    public function testDropSql(): void
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('getDriver')
            ->willReturn($driver);

        $table = new TableSchema('articles');
        $result = $table->dropSql($connection);
        $this->assertCount(1, $result);
        $this->assertSame('DROP TABLE `articles`', $result[0]);
    }

    /**
     * Test truncateSql()
     */
    public function testTruncateSql(): void
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('getDriver')
            ->willReturn($driver);

        $table = new TableSchema('articles');
        $result = $table->truncateSql($connection);
        $this->assertCount(1, $result);
        $this->assertSame('TRUNCATE TABLE `articles`', $result[0]);
    }

    /**
     * Test that constructing a schema dialect connects the driver.
     */
    public function testConstructConnectsDriver(): void
    {
        $driver = $this->getMockBuilder(Driver::class)->getMock();
        $driver->expects($this->once())
            ->method('connect');
        new MysqlSchemaDialect($driver);
    }

    /**
     * Tests JSON column parsing on MySQL 5.7+
     */
    public function testDescribeJson(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);
        $this->skipIf(!$connection->getDriver()->supports(DriverFeatureEnum::JSON), 'Does not support native json');
        $this->skipIf($connection->getDriver()->isMariadb(), 'MariaDb internally uses TEXT for JSON columns');

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_json');
        $this->assertInstanceOf(TableSchema::class, $result);
        $expected = [
            'type' => 'json',
            'null' => false,
            'default' => null,
            'length' => null,
            'precision' => null,
            'comment' => null,
        ];
        $this->assertEquals(
            $expected,
            $result->getColumn('data'),
            'Field definition does not match for data',
        );
    }

    /**
     * Get a schema instance with a mocked driver/pdo instances
     */
    protected function _getMockedDriver(): Driver
    {
        $this->_needsConnection();

        $this->pdo = $this->getMockBuilder(PDOMocked::class)
            ->onlyMethods(['quote', 'getAttribute', 'quoteIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
            $this->pdo->expects($this->any())
            ->method('quote')
            ->willReturnCallback(function ($value) {
                return "'{$value}'";
            });

        $driver = $this->getMockBuilder(Mysql::class)
            ->onlyMethods(['createPdo'])
            ->getMock();

        $driver->expects($this->any())
            ->method('createPdo')
            ->willReturn($this->pdo);

        $driver->connect();

        return $driver;
    }
}

// phpcs:disable
class PDOMocked extends PDO
{
    public function quoteIdentifier(): void {}
}
// phpcs:enable
