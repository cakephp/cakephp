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
use Iterator;
use PDO;

/**
 * Test case for MySQL Schema Dialect.
 */
class MysqlSchemaTest extends TestCase
{
    protected PDO $pdo;

    /**
     * Helper method for skipping tests that need a real connection.
     */
    protected function _needsConnection(): void
    {
        $config = ConnectionManager::getConfig('test');
        $this->skipIf(!str_contains((string)$config['driver'], 'Mysql'), 'Not using Mysql for test config');
    }

    /**
     * Data provider for convert column testing
     *
     * @return array
     */
    public static function convertColumnProvider(): Iterator
    {
        yield [
            'DATETIME',
            ['type' => 'datetime', 'length' => null],
        ];
        yield [
            'DATETIME(0)',
            ['type' => 'datetime', 'length' => null],
        ];
        yield [
            'DATETIME(6)',
            ['type' => 'datetimefractional', 'length' => null, 'precision' => 6],
        ];
        yield [
            'DATE',
            ['type' => 'date', 'length' => null],
        ];
        yield [
            'TIME',
            ['type' => 'time', 'length' => null],
        ];
        yield [
            'TIMESTAMP',
            ['type' => 'timestamp', 'length' => null],
        ];
        yield [
            'TIMESTAMP(0)',
            ['type' => 'timestamp', 'length' => null],
        ];
        yield [
            'TIMESTAMP(6)',
            ['type' => 'timestampfractional', 'length' => null, 'precision' => 6],
        ];
        yield [
            'TINYINT(1)',
            ['type' => 'boolean', 'length' => null],
        ];
        yield [
            'TINYINT(1) UNSIGNED',
            ['type' => 'boolean', 'length' => null],
        ];
        yield [
            'TINYINT(3)',
            ['type' => 'tinyinteger', 'length' => null, 'unsigned' => false],
        ];
        yield [
            'TINYINT(3) UNSIGNED',
            ['type' => 'tinyinteger', 'length' => null, 'unsigned' => true],
        ];
        yield [
            'SMALLINT(4)',
            ['type' => 'smallinteger', 'length' => null, 'unsigned' => false],
        ];
        yield [
            'SMALLINT(4) UNSIGNED',
            ['type' => 'smallinteger', 'length' => null, 'unsigned' => true],
        ];
        yield [
            'INTEGER(11)',
            ['type' => 'integer', 'length' => null, 'unsigned' => false],
        ];
        yield [
            'MEDIUMINT(11)',
            ['type' => 'integer', 'length' => null, 'unsigned' => false],
        ];
        yield [
            'INTEGER(11) UNSIGNED',
            ['type' => 'integer', 'length' => null, 'unsigned' => true],
        ];
        yield [
            'BIGINT',
            ['type' => 'biginteger', 'length' => null, 'unsigned' => false],
        ];
        yield [
            'BIGINT UNSIGNED',
            ['type' => 'biginteger', 'length' => null, 'unsigned' => true],
        ];
        yield [
            'VARCHAR(255)',
            ['type' => 'string', 'length' => 255, 'collate' => 'utf8_general_ci'],
        ];
        yield [
            'CHAR(25)',
            ['type' => 'char', 'length' => 25],
        ];
        yield [
            'CHAR(36)',
            ['type' => 'uuid', 'length' => null],
        ];
        yield [
            'BINARY(16)',
            ['type' => 'binaryuuid', 'length' => null],
        ];
        yield [
            'BINARY(1)',
            ['type' => 'binary', 'length' => 1],
        ];
        yield [
            'TEXT',
            ['type' => 'text', 'length' => null, 'collate' => 'utf8_general_ci'],
        ];
        yield [
            'TINYTEXT',
            ['type' => 'text', 'length' => TableSchema::LENGTH_TINY, 'collate' => 'utf8_general_ci'],
        ];
        yield [
            'MEDIUMTEXT',
            ['type' => 'text', 'length' => TableSchema::LENGTH_MEDIUM, 'collate' => 'utf8_general_ci'],
        ];
        yield [
            'LONGTEXT',
            ['type' => 'text', 'length' => TableSchema::LENGTH_LONG, 'collate' => 'utf8_general_ci'],
        ];
        yield [
            'TINYBLOB',
            ['type' => 'binary', 'length' => TableSchema::LENGTH_TINY],
        ];
        yield [
            'BLOB',
            ['type' => 'binary', 'length' => null],
        ];
        yield [
            'MEDIUMBLOB',
            ['type' => 'binary', 'length' => TableSchema::LENGTH_MEDIUM],
        ];
        yield [
            'LONGBLOB',
            ['type' => 'binary', 'length' => TableSchema::LENGTH_LONG],
        ];
        yield [
            'FLOAT',
            ['type' => 'float', 'length' => null, 'precision' => null, 'unsigned' => false],
        ];
        yield [
            'DOUBLE',
            ['type' => 'float', 'length' => null, 'precision' => null, 'unsigned' => false],
        ];
        yield [
            'DOUBLE UNSIGNED',
            ['type' => 'float', 'length' => null, 'precision' => null, 'unsigned' => true],
        ];
        yield [
            'DECIMAL(11,2) UNSIGNED',
            ['type' => 'decimal', 'length' => 11, 'precision' => 2, 'unsigned' => true],
        ];
        yield [
            'DECIMAL(11,2)',
            ['type' => 'decimal', 'length' => 11, 'precision' => 2, 'unsigned' => false],
        ];
        yield [
            'FLOAT(11,2)',
            ['type' => 'float', 'length' => 11, 'precision' => 2, 'unsigned' => false],
        ];
        yield [
            'FLOAT(11,2) UNSIGNED',
            ['type' => 'float', 'length' => 11, 'precision' => 2, 'unsigned' => true],
        ];
        yield [
            'DOUBLE(10,4)',
            ['type' => 'float', 'length' => 10, 'precision' => 4, 'unsigned' => false],
        ];
        yield [
            'DOUBLE(10,4) UNSIGNED',
            ['type' => 'float', 'length' => 10, 'precision' => 4, 'unsigned' => true],
        ];
        yield [
            'JSON',
            ['type' => 'json', 'length' => null],
        ];
    }

    /**
     * Test parsing MySQL column types from field description.
     *
     * @dataProvider convertColumnProvider
     */
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
                created DATETIME,
                created_with_precision DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
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

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_articles');
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
            'created' => [
                'type' => 'datetime',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
            ],
            'created_with_precision' => [
                'type' => 'datetimefractional',
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP(3)',
                'length' => null,
                'precision' => 3,
                'comment' => null,
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

        $this->assertSame(['id'], $result->getPrimaryKey());
        foreach ($expected as $field => $definition) {
            $this->assertEquals(
                $definition,
                $result->getColumn($field),
                'Field definition does not match for ' . $field
            );
        }
    }

    /**
     * Test describing a table with indexes in MySQL
     */
    public function testDescribeTableIndexes(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_articles');
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
        ];

        $this->assertSame($expected['primary'], $result->getConstraint('primary'));
        $this->assertSame($expected['length_idx'], $result->getConstraint('length_idx'));
        if (ConnectionManager::get('test')->getDriver()->isMariadb()) {
            $this->assertSame($expected['schema_articles_ibfk_1'], $result->getConstraint('author_idx'));
        } else {
            $this->assertSame($expected['schema_articles_ibfk_1'], $result->getConstraint('schema_articles_ibfk_1'));
        }

        $this->assertSame($expected['unique_id_idx'], $result->getConstraint('unique_id_idx'));

        $this->assertCount(1, $result->indexes());
        $expected = [
            'type' => 'index',
            'columns' => ['author_id'],
            'length' => [],
        ];
        $this->assertSame($expected, $result->getIndex('author_idx'));
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
        $this->assertSame('unique', $constraint['type']);
        $this->assertSame(['config_id'], $constraint['columns']);
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
        $this->assertSame('string', $column['type']);

        $index = $result->getIndex('child_ids_idx');
        $this->assertNotEmpty($index);
        $this->assertSame('index', $index['type']);
        $this->assertSame([], $index['columns']);
    }

    /**
     * Test describing a table creates options
     */
    public function testDescribeTableOptions(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_articles');
        $this->assertArrayHasKey('engine', $result->getOptions());
        $this->assertArrayHasKey('collation', $result->getOptions());
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
    public static function columnSqlProvider(): Iterator
    {
        // strings
        yield [
            'title',
            ['type' => 'string', 'length' => 25, 'null' => true, 'default' => null],
            '`title` VARCHAR(25)',
        ];
        yield [
            'title',
            ['type' => 'string', 'length' => 25, 'null' => false],
            '`title` VARCHAR(25) NOT NULL',
        ];
        yield [
            'title',
            ['type' => 'string', 'length' => 25, 'null' => true, 'default' => 'ignored'],
            "`title` VARCHAR(25) DEFAULT 'ignored'",
        ];
        yield [
            'title',
            ['type' => 'string', 'length' => 25, 'null' => true, 'default' => ''],
            "`title` VARCHAR(25) DEFAULT ''",
        ];
        yield [
            'role',
            ['type' => 'string', 'length' => 10, 'null' => false, 'default' => 'admin'],
            "`role` VARCHAR(10) NOT NULL DEFAULT 'admin'",
        ];
        yield [
            'id',
            ['type' => 'char', 'length' => 32, 'fixed' => true, 'null' => false],
            '`id` CHAR(32) NOT NULL',
        ];
        yield [
            'title',
            ['type' => 'string'],
            '`title` VARCHAR(255)',
        ];
        yield [
            'id',
            ['type' => 'uuid'],
            '`id` CHAR(36)',
        ];
        yield [
            'id',
            ['type' => 'char', 'length' => 36],
            '`id` CHAR(36)',
        ];
        yield [
            'id',
            ['type' => 'binaryuuid'],
            '`id` BINARY(16)',
        ];
        yield [
            'title',
            ['type' => 'string', 'length' => 255, 'null' => false, 'collate' => 'utf8_unicode_ci'],
            '`title` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL',
        ];
        // Text
        yield [
            'body',
            ['type' => 'text', 'null' => false],
            '`body` TEXT NOT NULL',
        ];
        yield [
            'body',
            ['type' => 'text', 'length' => TableSchema::LENGTH_TINY, 'null' => false],
            '`body` TINYTEXT NOT NULL',
        ];
        yield [
            'body',
            ['type' => 'text', 'length' => TableSchema::LENGTH_MEDIUM, 'null' => false],
            '`body` MEDIUMTEXT NOT NULL',
        ];
        yield [
            'body',
            ['type' => 'text', 'length' => TableSchema::LENGTH_LONG, 'null' => false],
            '`body` LONGTEXT NOT NULL',
        ];
        yield [
            'body',
            ['type' => 'text', 'null' => false, 'collate' => 'utf8_unicode_ci'],
            '`body` TEXT COLLATE utf8_unicode_ci NOT NULL',
        ];
        // Blob / binary
        yield [
            'body',
            ['type' => 'binary', 'null' => false],
            '`body` BLOB NOT NULL',
        ];
        yield [
            'body',
            ['type' => 'binary', 'length' => TableSchema::LENGTH_TINY, 'null' => false],
            '`body` TINYBLOB NOT NULL',
        ];
        yield [
            'body',
            ['type' => 'binary', 'length' => TableSchema::LENGTH_MEDIUM, 'null' => false],
            '`body` MEDIUMBLOB NOT NULL',
        ];
        yield [
            'body',
            ['type' => 'binary', 'length' => TableSchema::LENGTH_LONG, 'null' => false],
            '`body` LONGBLOB NOT NULL',
        ];
        yield [
            'bytes',
            ['type' => 'binary', 'length' => 5],
            '`bytes` VARBINARY(5)',
        ];
        yield [
            'bit',
            ['type' => 'binary', 'length' => 1],
            '`bit` BINARY(1)',
        ];
        // Integers
        yield [
            'post_id',
            ['type' => 'tinyinteger'],
            '`post_id` TINYINT',
        ];
        yield [
            'post_id',
            ['type' => 'tinyinteger', 'unsigned' => true],
            '`post_id` TINYINT UNSIGNED',
        ];
        yield [
            'post_id',
            ['type' => 'smallinteger'],
            '`post_id` SMALLINT',
        ];
        yield [
            'post_id',
            ['type' => 'smallinteger', 'unsigned' => true],
            '`post_id` SMALLINT UNSIGNED',
        ];
        yield [
            'post_id',
            ['type' => 'integer'],
            '`post_id` INTEGER',
        ];
        yield [
            'post_id',
            ['type' => 'integer', 'unsigned' => true],
            '`post_id` INTEGER UNSIGNED',
        ];
        yield [
            'post_id',
            ['type' => 'biginteger'],
            '`post_id` BIGINT',
        ];
        yield [
            'post_id',
            ['type' => 'biginteger', 'unsigned' => true],
            '`post_id` BIGINT UNSIGNED',
        ];
        yield [
            'post_id',
            ['type' => 'integer', 'autoIncrement' => true],
            '`post_id` INTEGER AUTO_INCREMENT',
        ];
        yield [
            'post_id',
            ['type' => 'integer', 'null' => false, 'autoIncrement' => false],
            '`post_id` INTEGER NOT NULL',
        ];
        yield [
            'post_id',
            ['type' => 'biginteger', 'autoIncrement' => true],
            '`post_id` BIGINT AUTO_INCREMENT',
        ];
        // Decimal
        yield [
            'value',
            ['type' => 'decimal'],
            '`value` DECIMAL',
        ];
        yield [
            'value',
            ['type' => 'decimal', 'length' => 11, 'unsigned' => true],
            '`value` DECIMAL(11) UNSIGNED',
        ];
        yield [
            'value',
            ['type' => 'decimal', 'length' => 12, 'precision' => 5],
            '`value` DECIMAL(12,5)',
        ];
        // Float
        yield [
            'value',
            ['type' => 'float', 'unsigned'],
            '`value` FLOAT',
        ];
        yield [
            'value',
            ['type' => 'float', 'unsigned' => true],
            '`value` FLOAT UNSIGNED',
        ];
        yield [
            'latitude',
            ['type' => 'float', 'length' => 53, 'null' => true, 'default' => null, 'unsigned' => true],
            '`latitude` FLOAT(53) UNSIGNED',
        ];
        yield [
            'value',
            ['type' => 'float', 'length' => 11, 'precision' => 3],
            '`value` FLOAT(11,3)',
        ];
        // Boolean
        yield [
            'checked',
            ['type' => 'boolean', 'default' => false],
            '`checked` BOOLEAN DEFAULT FALSE',
        ];
        yield [
            'checked',
            ['type' => 'boolean', 'default' => false, 'null' => false],
            '`checked` BOOLEAN NOT NULL DEFAULT FALSE',
        ];
        yield [
            'checked',
            ['type' => 'boolean', 'default' => true, 'null' => false],
            '`checked` BOOLEAN NOT NULL DEFAULT TRUE',
        ];
        yield [
            'checked',
            ['type' => 'boolean', 'default' => false, 'null' => true],
            '`checked` BOOLEAN DEFAULT FALSE',
        ];
        // datetimes
        yield [
            'created',
            ['type' => 'datetime', 'comment' => 'Created timestamp'],
            "`created` DATETIME COMMENT 'Created timestamp'",
        ];
        yield [
            'created',
            ['type' => 'datetime', 'null' => false, 'default' => 'current_timestamp'],
            '`created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ];
        yield [
            'open_date',
            ['type' => 'datetime', 'null' => false, 'default' => '2016-12-07 23:04:00'],
            "`open_date` DATETIME NOT NULL DEFAULT '2016-12-07 23:04:00'",
        ];
        yield [
            'created_with_precision',
            ['type' => 'datetimefractional', 'precision' => 3, 'null' => false, 'default' => 'current_timestamp'],
            '`created_with_precision` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)',
        ];
        // Date & Time
        yield [
            'start_date',
            ['type' => 'date'],
            '`start_date` DATE',
        ];
        yield [
            'start_time',
            ['type' => 'time'],
            '`start_time` TIME',
        ];
        // timestamps
        yield [
            'created',
            ['type' => 'timestamp', 'null' => true],
            '`created` TIMESTAMP NULL',
        ];
        yield [
            'created',
            ['type' => 'timestamp', 'null' => false, 'default' => 'current_timestamp'],
            '`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ];
        yield [
            'created',
            ['type' => 'timestamp', 'null' => false, 'default' => 'current_timestamp()'],
            '`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ];
        yield [
            'open_date',
            ['type' => 'timestamp', 'null' => false, 'default' => '2016-12-07 23:04:00'],
            "`open_date` TIMESTAMP NOT NULL DEFAULT '2016-12-07 23:04:00'",
        ];
        yield [
            'created_with_precision',
            ['type' => 'timestampfractional', 'precision' => 3, 'null' => false, 'default' => 'current_timestamp'],
            '`created_with_precision` TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)',
        ];
    }

    /**
     * Test generating column definitions
     *
     * @dataProvider columnSqlProvider
     */
    public function testColumnSql(string $name, array $data, string $expected): void
    {
        $driver = $this->_getMockedDriver();
        $schema = new MysqlSchemaDialect($driver);

        $table = (new TableSchema('articles'))->addColumn($name, $data);
        $this->assertSame($expected, $schema->columnSql($table, $name));
    }

    /**
     * Provide data for testing constraintSql
     *
     * @return array
     */
    public static function constraintSqlProvider(): Iterator
    {
        yield [
            'primary',
            ['type' => 'primary', 'columns' => ['title']],
            'PRIMARY KEY (`title`)',
        ];
        yield [
            'unique_idx',
            ['type' => 'unique', 'columns' => ['title', 'author_id']],
            'UNIQUE KEY `unique_idx` (`title`, `author_id`)',
        ];
        yield [
            'length_idx',
            [
                'type' => 'unique',
                'columns' => ['author_id', 'title'],
                'length' => ['author_id' => 5, 'title' => 4],
            ],
            'UNIQUE KEY `length_idx` (`author_id`(5), `title`(4))',
        ];
        yield [
            'author_id_idx',
            ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id']],
            'CONSTRAINT `author_id_idx` FOREIGN KEY (`author_id`) ' .
            'REFERENCES `authors` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT',
        ];
        yield [
            'author_id_idx',
            ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'cascade'],
            'CONSTRAINT `author_id_idx` FOREIGN KEY (`author_id`) ' .
            'REFERENCES `authors` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT',
        ];
        yield [
            'author_id_idx',
            ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'restrict'],
            'CONSTRAINT `author_id_idx` FOREIGN KEY (`author_id`) ' .
            'REFERENCES `authors` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT',
        ];
        yield [
            'author_id_idx',
            ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'setNull'],
            'CONSTRAINT `author_id_idx` FOREIGN KEY (`author_id`) ' .
            'REFERENCES `authors` (`id`) ON UPDATE SET NULL ON DELETE RESTRICT',
        ];
        yield [
            'author_id_idx',
            ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'noAction'],
            'CONSTRAINT `author_id_idx` FOREIGN KEY (`author_id`) ' .
            'REFERENCES `authors` (`id`) ON UPDATE NO ACTION ON DELETE RESTRICT',
        ];
    }

    /**
     * Test the constraintSql method.
     *
     * @dataProvider constraintSqlProvider
     */
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

        $this->assertSame($expected, $schema->constraintSql($table, $name));
    }

    /**
     * Test provider for indexSql()
     *
     * @return array
     */
    public static function indexSqlProvider(): Iterator
    {
        yield [
            'key_key',
            ['type' => 'index', 'columns' => ['author_id']],
            'KEY `key_key` (`author_id`)',
        ];
        yield [
            'full_text',
            ['type' => 'fulltext', 'columns' => ['title']],
            'FULLTEXT KEY `full_text` (`title`)',
        ];
    }

    /**
     * Test the indexSql method.
     *
     * @dataProvider indexSqlProvider
     */
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

        $this->assertSame($expected, $schema->indexSql($table, $name));
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
        $connection->method('getDriver')
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
        $this->assertSame($expected, $result);
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
        $connection->method('getDriver')
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
        $this->assertSame($expected, $result);
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
        $connection->method('getDriver')
            ->willReturn($driver);

        $this->pdo
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
        $connection
            ->method('getDriver')
            ->willReturn($driver);

        $this->pdo
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
        $connection->method('getDriver')
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
        $connection->method('getDriver')
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
        $connection->method('getDriver')
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
        $connection->method('getDriver')
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
            'Field definition does not match for data'
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
            $this->pdo
            ->method('quote')
            ->willReturnCallback(fn($value): string => sprintf("'%s'", $value));

        $driver = $this->getMockBuilder(Mysql::class)
            ->onlyMethods(['createPdo'])
            ->getMock();

        $driver
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
