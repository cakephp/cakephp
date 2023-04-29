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

use Cake\Database\Driver;
use Cake\Database\Driver\Postgres;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\PostgresSchemaDialect;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Postgres schema test case.
 */
class PostgresSchemaTest extends TestCase
{
    /**
     * Helper method for skipping tests that need a real connection.
     */
    protected function _needsConnection(): void
    {
        $config = ConnectionManager::getConfig('test');
        $this->skipIf(strpos($config['driver'], 'Postgres') === false, 'Not using Postgres for test config');
    }

    /**
     * Helper method for testing methods.
     *
     * @param \Cake\Datasource\ConnectionInterface $connection
     */
    protected function _createTables($connection): void
    {
        $this->_needsConnection();

        $connection->execute('DROP VIEW IF EXISTS schema_articles_v');
        $connection->execute('DROP TABLE IF EXISTS schema_articles');
        $connection->execute('DROP TABLE IF EXISTS schema_authors');

        $table = <<<SQL
CREATE TABLE schema_authors (
id SERIAL,
name VARCHAR(50) DEFAULT 'bob',
bio DATE,
position INT DEFAULT 1,
created TIMESTAMP,
PRIMARY KEY (id),
CONSTRAINT "unique_position" UNIQUE ("position")
)
SQL;
        $connection->execute($table);

        $table = <<<SQL
CREATE TABLE schema_articles (
id BIGINT PRIMARY KEY,
title VARCHAR(20),
body TEXT,
author_id INTEGER NOT NULL,
published BOOLEAN DEFAULT false,
views SMALLINT DEFAULT 0,
readingtime TIME,
data JSONB,
average_note DECIMAL(4,2),
average_income NUMERIC(10,2),
created TIMESTAMP,
created_without_precision TIMESTAMP(0),
created_with_precision TIMESTAMP(3),
created_with_timezone TIMESTAMPTZ(3),
CONSTRAINT "content_idx" UNIQUE ("title", "body"),
CONSTRAINT "author_idx" FOREIGN KEY ("author_id") REFERENCES "schema_authors" ("id") ON DELETE RESTRICT ON UPDATE CASCADE
)
SQL;
        $connection->execute($table);
        $connection->execute('COMMENT ON COLUMN "schema_articles"."title" IS \'a title\'');
        $connection->execute('CREATE INDEX "author_idx" ON "schema_articles" ("author_id")');

        $table = <<<SQL
CREATE VIEW schema_articles_v AS
SELECT * FROM schema_articles
SQL;
        $connection->execute($table);
    }

    /**
     * Data provider for convert column testing
     *
     * @return array
     */
    public static function convertColumnProvider(): array
    {
        return [
            // Timestamp
            [
                ['type' => 'TIMESTAMP', 'datetime_precision' => 6],
                ['type' => 'timestampfractional', 'length' => null, 'precision' => 6],
            ],
            [
                ['type' => 'TIMESTAMP', 'datetime_precision' => 0],
                ['type' => 'timestamp', 'length' => null, 'precision' => 0],
            ],
            [
                ['type' => 'TIMESTAMP WITHOUT TIME ZONE', 'datetime_precision' => 6],
                ['type' => 'timestampfractional', 'length' => null, 'precision' => 6],
            ],
            [
                ['type' => 'TIMESTAMP WITH TIME ZONE', 'datetime_precision' => 6],
                ['type' => 'timestamptimezone', 'length' => null, 'precision' => 6],
            ],
            [
                ['type' => 'TIMESTAMPTZ', 'datetime_precision' => 6],
                ['type' => 'timestamptimezone', 'length' => null, 'precision' => 6],
            ],
            // Date & time
            [
                ['type' => 'DATE'],
                ['type' => 'date', 'length' => null],
            ],
            [
                ['type' => 'TIME'],
                ['type' => 'time', 'length' => null],
            ],
            [
                ['type' => 'TIME WITHOUT TIME ZONE'],
                ['type' => 'time', 'length' => null],
            ],
            // Integer
            [
                ['type' => 'SMALLINT'],
                ['type' => 'smallinteger', 'length' => 5],
            ],
            [
                ['type' => 'INTEGER'],
                ['type' => 'integer', 'length' => 10],
            ],
            [
                ['type' => 'SERIAL'],
                ['type' => 'integer', 'length' => 10],
            ],
            [
                ['type' => 'BIGINT'],
                ['type' => 'biginteger', 'length' => 20],
            ],
            [
                ['type' => 'BIGSERIAL'],
                ['type' => 'biginteger', 'length' => 20],
            ],
            // Decimal
            [
                ['type' => 'NUMERIC'],
                ['type' => 'decimal', 'length' => null, 'precision' => null],
            ],
            [
                ['type' => 'NUMERIC', 'default' => 'NULL::numeric'],
                ['type' => 'decimal', 'length' => null, 'precision' => null, 'default' => null],
            ],
            [
                ['type' => 'DECIMAL(10,2)', 'column_precision' => 10, 'column_scale' => 2],
                ['type' => 'decimal', 'length' => 10, 'precision' => 2],
            ],
            // String
            [
                ['type' => 'VARCHAR'],
                ['type' => 'string', 'length' => null, 'collate' => 'ja_JP.utf8'],
            ],
            [
                ['type' => 'VARCHAR(10)'],
                ['type' => 'string', 'length' => 10, 'collate' => 'ja_JP.utf8'],
            ],
            [
                ['type' => 'CHARACTER VARYING'],
                ['type' => 'string', 'length' => null, 'collate' => 'ja_JP.utf8'],
            ],
            [
                ['type' => 'CHARACTER VARYING(10)'],
                ['type' => 'string', 'length' => 10, 'collate' => 'ja_JP.utf8'],
            ],
            [
                ['type' => 'CHARACTER VARYING(255)', 'default' => 'NULL::character varying'],
                ['type' => 'string', 'length' => 255, 'default' => null, 'collate' => 'ja_JP.utf8'],
            ],
            [
                ['type' => 'CHAR(10)'],
                ['type' => 'char', 'length' => 10, 'collate' => 'ja_JP.utf8'],
            ],
            [
                ['type' => 'CHAR(36)'],
                ['type' => 'char', 'length' => 36, 'collate' => 'ja_JP.utf8'],
            ],
            [
                ['type' => 'CHARACTER(10)'],
                ['type' => 'string', 'length' => 10, 'collate' => 'ja_JP.utf8'],
            ],
            [
                ['type' => 'MONEY'],
                ['type' => 'string', 'length' => null],
            ],
            // UUID
            [
                ['type' => 'UUID'],
                ['type' => 'uuid', 'length' => null],
            ],
            [
                ['type' => 'INET'],
                ['type' => 'string', 'length' => 39],
            ],
            // Text
            [
                ['type' => 'TEXT'],
                ['type' => 'text', 'length' => null, 'collate' => 'ja_JP.utf8'],
            ],
            // Blob
            [
                ['type' => 'BYTEA'],
                ['type' => 'binary', 'length' => null],
            ],
            // Float
            [
                ['type' => 'REAL'],
                ['type' => 'float', 'length' => null],
            ],
            [
                ['type' => 'DOUBLE PRECISION'],
                ['type' => 'float', 'length' => null],
            ],
            // JSON
            [
                ['type' => 'JSON'],
                ['type' => 'json', 'length' => null],
            ],
            [
                ['type' => 'JSONB'],
                ['type' => 'json', 'length' => null],
            ],
        ];
    }

    /**
     * Test parsing Postgres column types from field description.
     *
     * @dataProvider convertColumnProvider
     */
    public function testConvertColumn(array $field, array $expected): void
    {
        $field += [
            'name' => 'field',
            'null' => 'YES',
            'default' => 'Default value',
            'comment' => 'Comment section',
            'char_length' => null,
            'column_precision' => null,
            'column_scale' => null,
            'collation_name' => 'ja_JP.utf8',
        ];
        $expected += [
            'null' => true,
            'default' => 'Default value',
            'comment' => 'Comment section',
        ];

        $driver = $this->getMockBuilder('Cake\Database\Driver\Postgres')->getMock();
        $dialect = new PostgresSchemaDialect($driver);

        $table = new TableSchema('table');
        $dialect->convertColumnDescription($table, $field);

        $actual = array_intersect_key($table->getColumn('field'), $expected);
        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test listing tables with Postgres
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
     * Test that describe accepts tablenames containing `schema.table`.
     */
    public function testDescribeWithSchemaName(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('public.schema_articles');
        $this->assertEquals(['id'], $result->getPrimaryKey());
        $this->assertSame('schema_articles', $result->name());
    }

    /**
     * Test describing a table with Postgres
     */
    public function testDescribeTable(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_articles');
        $expected = [
            'id' => [
                'type' => 'biginteger',
                'null' => false,
                'default' => null,
                'length' => 20,
                'precision' => null,
                'unsigned' => null,
                'comment' => null,
                'autoIncrement' => false,
            ],
            'title' => [
                'type' => 'string',
                'null' => true,
                'default' => null,
                'length' => 20,
                'precision' => null,
                'comment' => 'a title',
                'collate' => null,
            ],
            'body' => [
                'type' => 'text',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
                'collate' => null,
            ],
            'author_id' => [
                'type' => 'integer',
                'null' => false,
                'default' => null,
                'length' => 10,
                'precision' => null,
                'unsigned' => null,
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
            'views' => [
                'type' => 'smallinteger',
                'null' => true,
                'default' => 0,
                'length' => 5,
                'precision' => null,
                'unsigned' => null,
                'comment' => null,
            ],
            'readingtime' => [
                'type' => 'time',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
            ],
            'data' => [
                'type' => 'json',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
            ],
            'average_note' => [
                'type' => 'decimal',
                'null' => true,
                'default' => null,
                'length' => 4,
                'precision' => 2,
                'unsigned' => null,
                'comment' => null,
            ],
            'average_income' => [
                'type' => 'decimal',
                'null' => true,
                'default' => null,
                'length' => 10,
                'precision' => 2,
                'unsigned' => null,
                'comment' => null,
            ],
            'created' => [
                'type' => 'timestampfractional',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => 6,
                'comment' => null,
            ],
            'created_without_precision' => [
                'type' => 'timestamp',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => 0,
                'comment' => null,
            ],
            'created_with_precision' => [
                'type' => 'timestampfractional',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => 3,
                'comment' => null,
            ],
            'created_with_timezone' => [
                'type' => 'timestamptimezone',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => 3,
                'comment' => null,
            ],
        ];
        $this->assertEquals(['id'], $result->getPrimaryKey());
        foreach ($expected as $field => $definition) {
            $this->assertEquals($definition, $result->getColumn($field));
        }
    }

    /**
     * Test describing a table with postgres and composite keys
     */
    public function testDescribeTableCompositeKey(): void
    {
        $this->_needsConnection();
        $connection = ConnectionManager::get('test');
        $sql = <<<SQL
CREATE TABLE schema_composite (
    "id" SERIAL,
    "site_id" INTEGER NOT NULL,
    "name" VARCHAR(255),
    PRIMARY KEY("id", "site_id")
);
SQL;
        $connection->execute($sql);
        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_composite');
        $connection->execute('DROP TABLE schema_composite');

        $this->assertEquals(['id', 'site_id'], $result->getPrimaryKey());
        $this->assertTrue($result->getColumn('id')['autoIncrement'], 'id should be autoincrement');
        $this->assertNull($result->getColumn('site_id')['autoIncrement'], 'site_id should not be autoincrement');
    }

    /**
     * Test describing a table containing defaults with Postgres
     */
    public function testDescribeTableWithDefaults(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_authors');
        $expected = [
            'id' => [
                'type' => 'integer',
                'null' => false,
                'default' => null,
                'length' => 10,
                'precision' => null,
                'unsigned' => null,
                'comment' => null,
                'autoIncrement' => true,
            ],
            'name' => [
                'type' => 'string',
                'null' => true,
                'default' => 'bob',
                'length' => 50,
                'precision' => null,
                'comment' => null,
                'collate' => null,
            ],
            'bio' => [
                'type' => 'date',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
            ],
            'position' => [
                'type' => 'integer',
                'null' => true,
                'default' => '1',
                'length' => 10,
                'precision' => null,
                'comment' => null,
                'unsigned' => null,
                'autoIncrement' => null,
            ],
            'created' => [
                'type' => 'timestampfractional',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => 6,
                'comment' => null,
            ],
        ];
        $this->assertEquals(['id'], $result->getPrimaryKey());
        foreach ($expected as $field => $definition) {
            $this->assertEquals($definition, $result->getColumn($field), "Mismatch in $field column");
        }
    }

    /**
     * Test describing a table with containing keywords
     */
    public function testDescribeTableConstraintsWithKeywords(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_authors');
        $this->assertInstanceOf('Cake\Database\Schema\TableSchema', $result);
        $expected = [
            'primary' => [
                'type' => 'primary',
                'columns' => ['id'],
                'length' => [],
            ],
            'unique_position' => [
                'type' => 'unique',
                'columns' => ['position'],
                'length' => [],
            ],
        ];
        $this->assertCount(2, $result->constraints());
        $this->assertEquals($expected['primary'], $result->getConstraint('primary'));
        $this->assertEquals($expected['unique_position'], $result->getConstraint('unique_position'));
    }

    /**
     * Test describing a table with indexes
     */
    public function testDescribeTableIndexes(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_articles');
        $this->assertInstanceOf('Cake\Database\Schema\TableSchema', $result);
        $expected = [
            'primary' => [
                'type' => 'primary',
                'columns' => ['id'],
                'length' => [],
            ],
            'content_idx' => [
                'type' => 'unique',
                'columns' => ['title', 'body'],
                'length' => [],
            ],
        ];
        $this->assertCount(3, $result->constraints());
        $expected = [
            'primary' => [
                'type' => 'primary',
                'columns' => ['id'],
                'length' => [],
            ],
            'content_idx' => [
                'type' => 'unique',
                'columns' => ['title', 'body'],
                'length' => [],
            ],
            'author_idx' => [
                'type' => 'foreign',
                'columns' => ['author_id'],
                'references' => ['schema_authors', 'id'],
                'length' => [],
                'update' => 'cascade',
                'delete' => 'restrict',
            ],
        ];
        $this->assertEquals($expected['primary'], $result->getConstraint('primary'));
        $this->assertEquals($expected['content_idx'], $result->getConstraint('content_idx'));
        $this->assertEquals($expected['author_idx'], $result->getConstraint('author_idx'));

        $this->assertCount(1, $result->indexes());
        $expected = [
            'type' => 'index',
            'columns' => ['author_id'],
            'length' => [],
        ];
        $this->assertEquals($expected, $result->getIndex('author_idx'));
    }

    /**
     * Test describing a table with indexes with nulls first
     */
    public function testDescribeTableIndexesNullsFirst(): void
    {
        $this->_needsConnection();
        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS schema_index');

        $table = <<<SQL
CREATE TABLE schema_index (
  id serial NOT NULL,
  user_id integer NOT NULL,
  group_id integer NOT NULL,
  grade double precision
)
WITH (
  OIDS=FALSE
)
SQL;
        $connection->execute($table);

        $index = <<<SQL
CREATE INDEX schema_index_nulls
  ON schema_index
  USING btree
  (group_id, grade DESC NULLS FIRST);
SQL;
        $connection->execute($index);
        $schema = new SchemaCollection($connection);

        $result = $schema->describe('schema_index');
        $this->assertCount(1, $result->indexes());
        $expected = [
            'type' => 'index',
            'columns' => ['group_id', 'grade'],
            'length' => [],
        ];
        $this->assertEquals($expected, $result->getIndex('schema_index_nulls'));
        $connection->execute('DROP TABLE schema_index');
    }

    /**
     * Test describing a table with postgres function defaults
     */
    public function testDescribeTableFunctionDefaultValue(): void
    {
        $this->_needsConnection();
        $connection = ConnectionManager::get('test');
        $sql = <<<SQL
CREATE TABLE schema_function_defaults (
    "id" SERIAL,
    year INT DEFAULT DATE_PART('year'::text, NOW()),
    PRIMARY KEY("id")
);
SQL;
        $connection->execute($sql);
        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_function_defaults');
        $connection->execute('DROP TABLE schema_function_defaults');

        $expected = [
            'type' => 'integer',
            'default' => "date_part('year'::text, now())",
            'null' => true,
            'precision' => null,
            'length' => 10,
            'comment' => null,
            'unsigned' => null,
            'autoIncrement' => null,
        ];
        $this->assertEquals($expected, $result->getColumn('year'));
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
                ['type' => 'string', 'length' => 25, 'null' => false],
                '"title" VARCHAR(25) NOT NULL',
            ],
            [
                'title',
                ['type' => 'string', 'length' => 25, 'null' => true, 'default' => 'ignored'],
                '"title" VARCHAR(25) DEFAULT \'ignored\'',
            ],
            [
                'id',
                ['type' => 'char', 'length' => 32, 'null' => false],
                '"id" CHAR(32) NOT NULL',
            ],
            [
                'title',
                ['type' => 'string', 'length' => 36, 'null' => false],
                '"title" VARCHAR(36) NOT NULL',
            ],
            [
                'id',
                ['type' => 'uuid', 'length' => 36, 'null' => false],
                '"id" UUID NOT NULL',
            ],
            [
                'id',
                ['type' => 'binaryuuid', 'length' => null, 'null' => false],
                '"id" UUID NOT NULL',
            ],
            [
                'role',
                ['type' => 'string', 'length' => 10, 'null' => false, 'default' => 'admin'],
                '"role" VARCHAR(10) NOT NULL DEFAULT \'admin\'',
            ],
            [
                'title',
                ['type' => 'string'],
                '"title" VARCHAR',
            ],
            [
                'title',
                ['type' => 'string', 'length' => 36],
                '"title" VARCHAR(36)',
            ],
            [
                'title',
                ['type' => 'string', 'length' => 255, 'null' => false, 'collate' => 'C'],
                '"title" VARCHAR(255) COLLATE "C" NOT NULL',
            ],
            // Text
            [
                'body',
                ['type' => 'text', 'null' => false],
                '"body" TEXT NOT NULL',
            ],
            [
                'body',
                ['type' => 'text', 'length' => TableSchema::LENGTH_TINY, 'null' => false],
                sprintf('"body" VARCHAR(%s) NOT NULL', TableSchema::LENGTH_TINY),
            ],
            [
                'body',
                ['type' => 'text', 'length' => TableSchema::LENGTH_MEDIUM, 'null' => false],
                '"body" TEXT NOT NULL',
            ],
            [
                'body',
                ['type' => 'text', 'length' => TableSchema::LENGTH_LONG, 'null' => false],
                '"body" TEXT NOT NULL',
            ],
            [
                'body',
                ['type' => 'text', 'null' => false, 'collate' => 'C'],
                '"body" TEXT COLLATE "C" NOT NULL',
            ],
            // Integers
            [
                'post_id',
                ['type' => 'tinyinteger', 'length' => 11],
                '"post_id" SMALLINT',
            ],
            [
                'post_id',
                ['type' => 'smallinteger', 'length' => 11],
                '"post_id" SMALLINT',
            ],
            [
                'post_id',
                ['type' => 'integer', 'length' => 11],
                '"post_id" INTEGER',
            ],
            [
                'post_id',
                ['type' => 'biginteger', 'length' => 20],
                '"post_id" BIGINT',
            ],
            [
                'post_id',
                ['type' => 'integer', 'autoIncrement' => true, 'length' => 11],
                '"post_id" SERIAL',
            ],
            [
                'post_id',
                ['type' => 'biginteger', 'autoIncrement' => true, 'length' => 20],
                '"post_id" BIGSERIAL',
            ],
            // Decimal
            [
                'value',
                ['type' => 'decimal'],
                '"value" DECIMAL',
            ],
            [
                'value',
                ['type' => 'decimal', 'length' => 11],
                '"value" DECIMAL(11,0)',
            ],
            [
                'value',
                ['type' => 'decimal', 'length' => 12, 'precision' => 5],
                '"value" DECIMAL(12,5)',
            ],
            // Float
            [
                'value',
                ['type' => 'float'],
                '"value" FLOAT',
            ],
            [
                'value',
                ['type' => 'float', 'length' => 11, 'precision' => 3],
                '"value" FLOAT(3)',
            ],
            // Binary
            [
                'img',
                ['type' => 'binary'],
                '"img" BYTEA',
            ],
            // Boolean
            [
                'checked',
                ['type' => 'boolean', 'default' => false],
                '"checked" BOOLEAN DEFAULT FALSE',
            ],
            [
                'checked',
                ['type' => 'boolean', 'default' => true, 'null' => false],
                '"checked" BOOLEAN NOT NULL DEFAULT TRUE',
            ],
            // Boolean
            [
                'checked',
                ['type' => 'boolean', 'default' => 0],
                '"checked" BOOLEAN DEFAULT FALSE',
            ],
            [
                'checked',
                ['type' => 'boolean', 'default' => 1, 'null' => false],
                '"checked" BOOLEAN NOT NULL DEFAULT TRUE',
            ],
            // Date & Time
            [
                'start_date',
                ['type' => 'date'],
                '"start_date" DATE',
            ],
            [
                'start_time',
                ['type' => 'time'],
                '"start_time" TIME',
            ],
            // Datetime
            [
                'created',
                ['type' => 'datetime', 'null' => true],
                '"created" TIMESTAMP DEFAULT NULL',
            ],
            [
                'created_without_precision',
                ['type' => 'datetime', 'precision' => 0],
                '"created_without_precision" TIMESTAMP(0)',
            ],
            [
                'created_without_precision',
                ['type' => 'datetimefractional', 'precision' => 0],
                '"created_without_precision" TIMESTAMP(0)',
            ],
            [
                'created_with_precision',
                ['type' => 'datetimefractional', 'precision' => 3],
                '"created_with_precision" TIMESTAMP(3)',
            ],
            // Timestamp
            [
                'created',
                ['type' => 'timestamp', 'null' => true],
                '"created" TIMESTAMP DEFAULT NULL',
            ],
            [
                'created_without_precision',
                ['type' => 'timestamp', 'precision' => 0],
                '"created_without_precision" TIMESTAMP(0)',
            ],
            [
                'created_without_precision',
                ['type' => 'timestampfractional', 'precision' => 0],
                '"created_without_precision" TIMESTAMP(0)',
            ],
            [
                'created_with_precision',
                ['type' => 'timestampfractional', 'precision' => 3],
                '"created_with_precision" TIMESTAMP(3)',
            ],
            [
                'open_date',
                ['type' => 'timestampfractional', 'null' => false, 'default' => '2016-12-07 23:04:00'],
                '"open_date" TIMESTAMP NOT NULL DEFAULT \'2016-12-07 23:04:00\'',
            ],
            [
                'null_date',
                ['type' => 'timestampfractional', 'null' => true],
                '"null_date" TIMESTAMP DEFAULT NULL',
            ],
            [
                'current_timestamp',
                ['type' => 'timestamp', 'null' => false, 'default' => 'CURRENT_TIMESTAMP'],
                '"current_timestamp" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
            ],
            [
                'current_timestamp_fractional',
                ['type' => 'timestampfractional', 'null' => false, 'default' => 'CURRENT_TIMESTAMP'],
                '"current_timestamp_fractional" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
            ],
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
        $schema = new PostgresSchemaDialect($driver);

        $table = (new TableSchema('schema_articles'))->addColumn($name, $data);
        $this->assertEquals($expected, $schema->columnSql($table, $name));
    }

    /**
     * Test generating a column that is a primary key.
     */
    public function testColumnSqlPrimaryKey(): void
    {
        $driver = $this->_getMockedDriver();
        $schema = new PostgresSchemaDialect($driver);

        $table = new TableSchema('schema_articles');
        $table->addColumn('id', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id'],
            ]);

        $result = $schema->columnSql($table, 'id');
        $this->assertSame($result, '"id" SERIAL');
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
                'PRIMARY KEY ("title")',
            ],
            [
                'unique_idx',
                ['type' => 'unique', 'columns' => ['title', 'author_id']],
                'CONSTRAINT "unique_idx" UNIQUE ("title", "author_id")',
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id']],
                'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
                'REFERENCES "authors" ("id") ON UPDATE RESTRICT ON DELETE RESTRICT DEFERRABLE INITIALLY IMMEDIATE',
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'cascade'],
                'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
                'REFERENCES "authors" ("id") ON UPDATE CASCADE ON DELETE RESTRICT DEFERRABLE INITIALLY IMMEDIATE',
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'restrict'],
                'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
                'REFERENCES "authors" ("id") ON UPDATE RESTRICT ON DELETE RESTRICT DEFERRABLE INITIALLY IMMEDIATE',
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'setNull'],
                'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
                'REFERENCES "authors" ("id") ON UPDATE SET NULL ON DELETE RESTRICT DEFERRABLE INITIALLY IMMEDIATE',
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'noAction'],
                'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
                'REFERENCES "authors" ("id") ON UPDATE NO ACTION ON DELETE RESTRICT DEFERRABLE INITIALLY IMMEDIATE',
            ],
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
        $schema = new PostgresSchemaDialect($driver);

        $table = (new TableSchema('schema_articles'))->addColumn('title', [
            'type' => 'string',
            'length' => 255,
        ])->addColumn('author_id', [
            'type' => 'integer',
        ])->addConstraint($name, $data);

        $this->assertTextEquals($expected, $schema->constraintSql($table, $name));
    }

    /**
     * Test the addConstraintSql method.
     */
    public function testAddConstraintSql(): void
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('getDriver')
            ->will($this->returnValue($driver));

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
            'ALTER TABLE "posts" ADD CONSTRAINT "author_fk" FOREIGN KEY ("author_id") REFERENCES "authors" ("id") ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE;',
            'ALTER TABLE "posts" ADD CONSTRAINT "category_fk" FOREIGN KEY ("category_id", "category_name") REFERENCES "categories" ("id", "name") ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE;',
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
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('getDriver')
            ->will($this->returnValue($driver));

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
            'ALTER TABLE "posts" DROP CONSTRAINT "author_fk";',
            'ALTER TABLE "posts" DROP CONSTRAINT "category_fk";',
        ];
        $result = $table->dropConstraintSql($connection);
        $this->assertCount(2, $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * Integration test for converting a Schema\Table into MySQL table creates.
     */
    public function testCreateSql(): void
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('getDriver')
            ->will($this->returnValue($driver));

        $table = (new TableSchema('schema_articles'))->addColumn('id', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addColumn('title', [
                'type' => 'string',
                'null' => false,
                'comment' => 'This is the title',
            ])
            ->addColumn('body', ['type' => 'text'])
            ->addColumn('data', ['type' => 'json'])
            ->addColumn('hash', [
                'type' => 'char',
                'length' => 40,
                'collate' => 'C',
                'null' => false,
            ])
            ->addColumn('created', 'timestamp')
            ->addColumn('created_without_precision', ['type' => 'timestamp', 'precision' => 0])
            ->addColumn('created_with_precision', ['type' => 'timestampfractional', 'precision' => 6])
            ->addColumn('created_with_timezone', ['type' => 'timestamptimezone', 'precision' => 6])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id'],
            ])
            ->addIndex('title_idx', [
                'type' => 'index',
                'columns' => ['title'],
            ]);

        $expected = <<<SQL
CREATE TABLE "schema_articles" (
"id" SERIAL,
"title" VARCHAR NOT NULL,
"body" TEXT,
"data" JSONB,
"hash" CHAR(40) COLLATE "C" NOT NULL,
"created" TIMESTAMP,
"created_without_precision" TIMESTAMP(0),
"created_with_precision" TIMESTAMP(6),
"created_with_timezone" TIMESTAMPTZ(6),
PRIMARY KEY ("id")
)
SQL;
        $result = $table->createSql($connection);

        $this->assertCount(3, $result);
        $this->assertTextEquals($expected, $result[0]);
        $this->assertSame(
            'CREATE INDEX "title_idx" ON "schema_articles" ("title")',
            $result[1]
        );
        $this->assertSame(
            'COMMENT ON COLUMN "schema_articles"."title" IS \'This is the title\'',
            $result[2]
        );
    }

    /**
     * Tests creating tables in postgres schema
     */
    public function testCreateInSchema(): void
    {
        $driver = $this->_getMockedDriver(['schema' => 'notpublic']);
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())
            ->method('getDriver')
            ->will($this->returnValue($driver));

        $table = (new TableSchema('schema_articles'))->addColumn('title', [
            'type' => 'string',
            'length' => 255,
        ]);
        $sql = $table->createSql($connection);
        $this->assertStringContainsString('CREATE TABLE "notpublic"."schema_articles"', $sql[0]);
    }

    /**
     * Tests creating temporary tables
     */
    public function testCreateTemporary(): void
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('getDriver')
            ->will($this->returnValue($driver));
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
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('getDriver')
            ->will($this->returnValue($driver));

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
CREATE TABLE "articles_tags" (
"article_id" INTEGER NOT NULL,
"tag_id" INTEGER NOT NULL,
PRIMARY KEY ("article_id", "tag_id")
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
CREATE TABLE "composite_key" (
"id" SERIAL,
"account_id" INTEGER NOT NULL,
PRIMARY KEY ("id", "account_id")
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
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('getDriver')
            ->will($this->returnValue($driver));

        $table = new TableSchema('schema_articles');
        $result = $table->dropSql($connection);
        $this->assertCount(1, $result);
        $this->assertSame('DROP TABLE "schema_articles" CASCADE', $result[0]);
    }

    /**
     * Test truncateSql()
     */
    public function testTruncateSql(): void
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('getDriver')
            ->will($this->returnValue($driver));

        $table = new TableSchema('schema_articles');
        $table->addColumn('id', 'integer')
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id'],
            ]);
        $result = $table->truncateSql($connection);
        $this->assertCount(1, $result);
        $this->assertSame('TRUNCATE "schema_articles" RESTART IDENTITY CASCADE', $result[0]);
    }

    /**
     * Get a schema instance with a mocked driver/pdo instances
     */
    protected function _getMockedDriver(array $config = []): Driver
    {
        $driver = new Postgres($config);
        $mock = $this->getMockBuilder(PDO::class)
            ->onlyMethods(['quote'])
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->any())
            ->method('quote')
            ->will($this->returnCallback(function ($value) {
                return "'$value'";
            }));
        $driver->setConnection($mock);

        return $driver;
    }
}
