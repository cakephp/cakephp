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
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\SqlserverSchemaDialect;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * SQL Server schema test case.
 */
class SqlserverSchemaTest extends TestCase
{
    /**
     * Helper method for skipping tests that need a real connection.
     */
    protected function _needsConnection(): void
    {
        $config = ConnectionManager::getConfig('test');
        $this->skipIf(strpos($config['driver'], 'Sqlserver') === false, 'Not using Sqlserver for test config');
    }

    /**
     * Helper method for testing methods.
     */
    protected function _createTables(ConnectionInterface $connection): void
    {
        $this->_needsConnection();

        $connection->execute("IF OBJECT_ID('schema_articles_v', 'V') IS NOT NULL DROP VIEW schema_articles_v");
        $connection->execute("IF OBJECT_ID('schema_articles', 'U') IS NOT NULL DROP TABLE schema_articles");
        $connection->execute("IF OBJECT_ID('schema_authors', 'U') IS NOT NULL DROP TABLE schema_authors");

        $table = <<<SQL
CREATE TABLE schema_authors (
id int IDENTITY(1,1) PRIMARY KEY,
name VARCHAR(50),
bio DATE,
created DATETIME
)
SQL;
        $connection->execute($table);

        $table = <<<SQL
CREATE TABLE schema_articles (
id BIGINT PRIMARY KEY,
title NVARCHAR(20) COLLATE Japanese_Unicode_CI_AI DEFAULT N'無題' COLLATE Japanese_Unicode_CI_AI,
body NVARCHAR(1000) DEFAULT N'本文なし',
author_id INTEGER NOT NULL,
published BIT DEFAULT 0,
views SMALLINT DEFAULT 0,
created DATETIME,
created2 DATETIME2,
created2_with_default DATETIME2 DEFAULT SYSDATETIME(),
created2_with_precision DATETIME2(3),
created2_without_precision DATETIME2(0),
field1 VARCHAR(10) DEFAULT NULL,
field2 VARCHAR(10) DEFAULT 'NULL',
field3 VARCHAR(10) DEFAULT 'O''hare',
CONSTRAINT [content_idx] UNIQUE ([title], [body]),
CONSTRAINT [author_idx] FOREIGN KEY ([author_id]) REFERENCES [schema_authors] ([id]) ON DELETE CASCADE ON UPDATE CASCADE
)
SQL;
        $connection->execute($table);
        $connection->execute('CREATE INDEX [author_idx] ON [schema_articles] ([author_id])');

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
            [
                'DATETIME',
                null,
                null,
                3,
                ['type' => 'datetime', 'length' => null, 'precision' => null],
            ],
            [
                'DATETIME2',
                null,
                null,
                7,
                ['type' => 'datetimefractional', 'length' => null, 'precision' => 7],
            ],
            [
                'DATETIME2',
                null,
                null,
                0,
                ['type' => 'datetime', 'length' => null, 'precision' => 0],
            ],
            [
                'DATE',
                null,
                null,
                null,
                ['type' => 'date', 'length' => null],
            ],
            [
                'TIME',
                null,
                null,
                null,
                ['type' => 'time', 'length' => null],
            ],
            [
                'TINYINT',
                null,
                2,
                null,
                ['type' => 'tinyinteger', 'length' => 2],
            ],
            [
                'TINYINT',
                null,
                null,
                null,
                ['type' => 'tinyinteger', 'length' => 3],
            ],
            [
                'SMALLINT',
                null,
                3,
                null,
                ['type' => 'smallinteger', 'length' => 3],
            ],
            [
                'SMALLINT',
                null,
                null,
                null,
                ['type' => 'smallinteger', 'length' => 5],
            ],
            [
                'INTEGER',
                null,
                null,
                null,
                ['type' => 'integer', 'length' => 10],
            ],
            [
                'INTEGER',
                null,
                8,
                null,
                ['type' => 'integer', 'length' => 8],
            ],
            [
                'BIGINT',
                null,
                null,
                null,
                ['type' => 'biginteger', 'length' => 20],
            ],
            [
                'NUMERIC',
                null,
                15,
                5,
                ['type' => 'decimal', 'length' => 15, 'precision' => 5],
            ],
            [
                'DECIMAL',
                null,
                11,
                3,
                ['type' => 'decimal', 'length' => 11, 'precision' => 3],
            ],
            [
                'MONEY',
                null,
                null,
                null,
                ['type' => 'decimal', 'length' => null, 'precision' => null],
            ],
            [
                'VARCHAR',
                null,
                null,
                null,
                ['type' => 'string', 'length' => 255, 'collate' => 'Japanese_Unicode_CI_AI'],
            ],
            [
                'VARCHAR',
                10,
                null,
                null,
                ['type' => 'string', 'length' => 10, 'collate' => 'Japanese_Unicode_CI_AI'],
            ],
            [
                'NVARCHAR',
                50,
                null,
                null,
                // Sqlserver returns double lengths for unicode columns
                ['type' => 'string', 'length' => 25, 'collate' => 'Japanese_Unicode_CI_AI'],
            ],
            [
                'CHAR',
                10,
                null,
                null,
                ['type' => 'char', 'length' => 10, 'collate' => 'Japanese_Unicode_CI_AI'],
            ],
            [
                'NCHAR',
                10,
                null,
                null,
                // SQLServer returns double length for unicode columns.
                ['type' => 'char', 'length' => 5, 'collate' => 'Japanese_Unicode_CI_AI'],
            ],
            [
                'UNIQUEIDENTIFIER',
                null,
                null,
                null,
                ['type' => 'uuid'],
            ],
            [
                'TEXT',
                null,
                null,
                null,
                ['type' => 'text', 'length' => null, 'collate' => 'Japanese_Unicode_CI_AI'],
            ],
            [
                'REAL',
                null,
                null,
                null,
                ['type' => 'float', 'length' => null],
            ],
            [
                'VARCHAR',
                -1,
                null,
                null,
                ['type' => 'text', 'length' => null, 'collate' => 'Japanese_Unicode_CI_AI'],
            ],
            [
                'IMAGE',
                10,
                null,
                null,
                ['type' => 'binary', 'length' => 10],
            ],
            [
                'BINARY',
                20,
                null,
                null,
                ['type' => 'binary', 'length' => 20],
            ],
            [
                'VARBINARY',
                30,
                null,
                null,
                ['type' => 'binary', 'length' => 30],
            ],
            [
                'VARBINARY',
                -1,
                null,
                null,
                ['type' => 'binary', 'length' => TableSchema::LENGTH_LONG],
            ],
        ];
    }

    /**
     * Test parsing Sqlserver column types from field description.
     *
     * @dataProvider convertColumnProvider
     */
    public function testConvertColumn(string $type, ?int $length, ?int $precision, ?int $scale, array $expected): void
    {
        $field = [
            'name' => 'field',
            'type' => $type,
            'null' => '1',
            'default' => 'Default value',
            'char_length' => $length,
            'precision' => $precision,
            'scale' => $scale,
            'collation_name' => 'Japanese_Unicode_CI_AI',
        ];
        $expected += [
            'null' => true,
            'default' => 'Default value',
        ];

        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlserver')->getMock();
        $dialect = new SqlserverSchemaDialect($driver);

        $table = new TableSchema('table');
        $dialect->convertColumnDescription($table, $field);

        $actual = array_intersect_key($table->getColumn('field'), $expected);
        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test listing tables with Sqlserver
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
     * Test describing a table with Sqlserver
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
                'length' => 19,
                'precision' => null,
                'unsigned' => null,
                'autoIncrement' => null,
                'comment' => null,
            ],
            'title' => [
                'type' => 'string',
                'null' => true,
                'default' => '無題',
                'length' => 20,
                'precision' => null,
                'comment' => null,
                'collate' => 'Japanese_Unicode_CI_AI',
            ],
            'body' => [
                'type' => 'string',
                'null' => true,
                'default' => '本文なし',
                'length' => 1000,
                'precision' => null,
                'comment' => null,
                'collate' => 'SQL_Latin1_General_CP1_CI_AS',
            ],
            'author_id' => [
                'type' => 'integer',
                'null' => false,
                'default' => null,
                'length' => 10,
                'precision' => null,
                'unsigned' => null,
                'autoIncrement' => null,
                'comment' => null,
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
            'created' => [
                'type' => 'datetime',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
            ],
            'created2' => [
                'type' => 'datetimefractional',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => 7,
                'comment' => null,
            ],
            'created2_with_default' => [
                'type' => 'datetimefractional',
                'null' => true,
                'default' => 'sysdatetime()',
                'length' => null,
                'precision' => 7,
                'comment' => null,
            ],
            'created2_with_precision' => [
                'type' => 'datetimefractional',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => 3,
                'comment' => null,
            ],
            'created2_without_precision' => [
                'type' => 'datetime',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => 0,
                'comment' => null,
            ],
            'field1' => [
                'type' => 'string',
                'null' => true,
                'default' => null,
                'length' => 10,
                'precision' => null,
                'comment' => null,
                'collate' => 'SQL_Latin1_General_CP1_CI_AS',
            ],
            'field2' => [
                'type' => 'string',
                'null' => true,
                'default' => 'NULL',
                'length' => 10,
                'precision' => null,
                'comment' => null,
                'collate' => 'SQL_Latin1_General_CP1_CI_AS',
            ],
            'field3' => [
                'type' => 'string',
                'null' => true,
                'default' => 'O\'hare',
                'length' => 10,
                'precision' => null,
                'comment' => null,
                'collate' => 'SQL_Latin1_General_CP1_CI_AS',
            ],
        ];
        $this->assertEquals(['id'], $result->getPrimaryKey());
        foreach ($expected as $field => $definition) {
            $column = $result->getColumn($field);
            $this->assertEquals($definition, $column, 'Failed to match field ' . $field);
            $this->assertSame($definition['length'], $column['length']);
            $this->assertSame($definition['precision'], $column['precision']);
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
    [id] INTEGER IDENTITY(1, 1),
    [site_id] INTEGER NOT NULL,
    [name] VARCHAR(255),
    PRIMARY KEY([id], [site_id])
);
SQL;
        $connection->execute($sql);
        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_composite');
        $connection->execute('DROP TABLE schema_composite');

        $this->assertEquals(['id', 'site_id'], $result->getPrimaryKey());
        $this->assertNull($result->getColumn('site_id')['autoIncrement'], 'site_id should not be autoincrement');
        $this->assertTrue($result->getColumn('id')['autoIncrement'], 'id should be autoincrement');
    }

    /**
     * Test that describe accepts tablenames containing `schema.table`.
     */
    public function testDescribeWithSchemaName(): void
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('dbo.schema_articles');
        $this->assertEquals(['id'], $result->getPrimaryKey());
        $this->assertSame('schema_articles', $result->name());
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
                'delete' => 'cascade',
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
                '[title] NVARCHAR(25) NOT NULL',
            ],
            [
                'title',
                ['type' => 'string', 'length' => 25, 'null' => true, 'default' => 'ignored'],
                "[title] NVARCHAR(25) DEFAULT 'ignored'",
            ],
            [
                'id',
                ['type' => 'char', 'length' => 16, 'null' => false],
                '[id] NCHAR(16) NOT NULL',
            ],
            [
                'id',
                ['type' => 'uuid', 'null' => false],
                '[id] UNIQUEIDENTIFIER NOT NULL',
            ],
            [
                'id',
                ['type' => 'binaryuuid', 'null' => false],
                '[id] UNIQUEIDENTIFIER NOT NULL',
            ],
            [
                'role',
                ['type' => 'string', 'length' => 10, 'null' => false, 'default' => 'admin'],
                "[role] NVARCHAR(10) NOT NULL DEFAULT 'admin'",
            ],
            [
                'title',
                ['type' => 'string'],
                '[title] NVARCHAR(255)',
            ],
            [
                'title',
                ['type' => 'string', 'length' => 25, 'null' => false, 'collate' => 'Japanese_Unicode_CI_AI'],
                '[title] NVARCHAR(25) COLLATE Japanese_Unicode_CI_AI NOT NULL',
            ],
            // Text
            [
                'body',
                ['type' => 'text', 'null' => false],
                '[body] NVARCHAR(MAX) NOT NULL',
            ],
            [
                'body',
                ['type' => 'text', 'length' => TableSchema::LENGTH_TINY, 'null' => false],
                sprintf('[body] NVARCHAR(%s) NOT NULL', TableSchema::LENGTH_TINY),
            ],
            [
                'body',
                ['type' => 'text', 'length' => TableSchema::LENGTH_MEDIUM, 'null' => false],
                '[body] NVARCHAR(MAX) NOT NULL',
            ],
            [
                'body',
                ['type' => 'text', 'length' => TableSchema::LENGTH_LONG, 'null' => false],
                '[body] NVARCHAR(MAX) NOT NULL',
            ],
            [
                'body',
                ['type' => 'text', 'null' => false, 'collate' => 'Japanese_Unicode_CI_AI'],
                '[body] NVARCHAR(MAX) COLLATE Japanese_Unicode_CI_AI NOT NULL',
            ],
            // Integers
            [
                'post_id',
                ['type' => 'smallinteger', 'length' => 11],
                '[post_id] SMALLINT',
            ],
            [
                'post_id',
                ['type' => 'tinyinteger', 'length' => 11],
                '[post_id] TINYINT',
            ],
            [
                'post_id',
                ['type' => 'integer', 'length' => 11],
                '[post_id] INTEGER',
            ],
            [
                'post_id',
                ['type' => 'biginteger', 'length' => 20],
                '[post_id] BIGINT',
            ],
            // Decimal
            [
                'value',
                ['type' => 'decimal'],
                '[value] DECIMAL',
            ],
            [
                'value',
                ['type' => 'decimal', 'length' => 11],
                '[value] DECIMAL(11,0)',
            ],
            [
                'value',
                ['type' => 'decimal', 'length' => 12, 'precision' => 5],
                '[value] DECIMAL(12,5)',
            ],
            // Float
            [
                'value',
                ['type' => 'float'],
                '[value] FLOAT',
            ],
            [
                'value',
                ['type' => 'float', 'length' => 11, 'precision' => 3],
                '[value] FLOAT(3)',
            ],
            // Binary
            [
                'img',
                ['type' => 'binary', 'length' => null],
                '[img] VARBINARY(MAX)',
            ],
            [
                'img',
                ['type' => 'binary', 'length' => TableSchema::LENGTH_TINY],
                sprintf('[img] VARBINARY(%s)', TableSchema::LENGTH_TINY),
            ],
            [
                'img',
                ['type' => 'binary', 'length' => TableSchema::LENGTH_MEDIUM],
                '[img] VARBINARY(MAX)',
            ],
            [
                'img',
                ['type' => 'binary', 'length' => TableSchema::LENGTH_LONG],
                '[img] VARBINARY(MAX)',
            ],
            [
                'bytes',
                ['type' => 'binary', 'length' => 5],
                '[bytes] VARBINARY(5)',
            ],
            [
                'bytes',
                ['type' => 'binary', 'length' => 1],
                '[bytes] BINARY(1)',
            ],
            // Boolean
            [
                'checked',
                ['type' => 'boolean', 'default' => false],
                '[checked] BIT DEFAULT 0',
            ],
            [
                'checked',
                ['type' => 'boolean', 'default' => true, 'null' => false],
                '[checked] BIT NOT NULL DEFAULT 1',
            ],
            // Datetime
            [
                'created',
                ['type' => 'datetime'],
                '[created] DATETIME2',
            ],
            [
                'open_date',
                ['type' => 'datetime', 'null' => false, 'default' => '2016-12-07 23:04:00'],
                '[open_date] DATETIME2 NOT NULL DEFAULT \'2016-12-07 23:04:00\'',
            ],
            [
                'open_date',
                ['type' => 'datetime', 'null' => false, 'default' => 'current_timestamp'],
                '[open_date] DATETIME2 NOT NULL DEFAULT CURRENT_TIMESTAMP',
            ],
            [
                'open_date',
                ['type' => 'datetime', 'null' => false, 'default' => 'getdate()'],
                '[open_date] DATETIME2 NOT NULL DEFAULT GETDATE()',
            ],
            [
                'open_date',
                ['type' => 'datetime', 'null' => false, 'default' => 'getutcdate()'],
                '[open_date] DATETIME2 NOT NULL DEFAULT GETUTCDATE()',
            ],
            [
                'open_date',
                ['type' => 'datetime', 'null' => false, 'default' => 'sysdatetime()'],
                '[open_date] DATETIME2 NOT NULL DEFAULT SYSDATETIME()',
            ],
            [
                'open_date',
                ['type' => 'datetime', 'null' => false, 'default' => 'sysutcdatetime()'],
                '[open_date] DATETIME2 NOT NULL DEFAULT SYSUTCDATETIME()',
            ],
            [
                'open_date',
                ['type' => 'datetime', 'null' => false, 'default' => 'sysdatetimeoffset()'],
                '[open_date] DATETIME2 NOT NULL DEFAULT SYSDATETIMEOFFSET()',
            ],
            [
                'null_date',
                ['type' => 'datetime', 'null' => true, 'default' => 'current_timestamp'],
                '[null_date] DATETIME2 DEFAULT CURRENT_TIMESTAMP',
            ],
            [
                'null_date',
                ['type' => 'datetime', 'null' => true],
                '[null_date] DATETIME2 DEFAULT NULL',
            ],
            // Date & Time
            [
                'start_date',
                ['type' => 'date'],
                '[start_date] DATE',
            ],
            [
                'start_time',
                ['type' => 'time'],
                '[start_time] TIME',
            ],
            // Timestamp
            [
                'created',
                ['type' => 'timestamp', 'null' => true],
                '[created] DATETIME2 DEFAULT NULL',
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
        $schema = new SqlserverSchemaDialect($driver);

        $table = (new TableSchema('schema_articles'))->addColumn($name, $data);
        $this->assertEquals($expected, $schema->columnSql($table, $name));
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
                'PRIMARY KEY ([title])',
            ],
            [
                'unique_idx',
                ['type' => 'unique', 'columns' => ['title', 'author_id']],
                'CONSTRAINT [unique_idx] UNIQUE ([title], [author_id])',
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id']],
                'CONSTRAINT [author_id_idx] FOREIGN KEY ([author_id]) ' .
                'REFERENCES [authors] ([id]) ON UPDATE NO ACTION ON DELETE NO ACTION',
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'cascade'],
                'CONSTRAINT [author_id_idx] FOREIGN KEY ([author_id]) ' .
                'REFERENCES [authors] ([id]) ON UPDATE CASCADE ON DELETE NO ACTION',
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'setDefault'],
                'CONSTRAINT [author_id_idx] FOREIGN KEY ([author_id]) ' .
                'REFERENCES [authors] ([id]) ON UPDATE SET DEFAULT ON DELETE NO ACTION',
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'setNull'],
                'CONSTRAINT [author_id_idx] FOREIGN KEY ([author_id]) ' .
                'REFERENCES [authors] ([id]) ON UPDATE SET NULL ON DELETE NO ACTION',
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'noAction'],
                'CONSTRAINT [author_id_idx] FOREIGN KEY ([author_id]) ' .
                'REFERENCES [authors] ([id]) ON UPDATE NO ACTION ON DELETE NO ACTION',
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
        $schema = new SqlserverSchemaDialect($driver);

        $table = (new TableSchema('schema_articles'))->addColumn('title', [
            'type' => 'string',
            'length' => 255,
        ])->addColumn('author_id', [
            'type' => 'integer',
        ])->addConstraint($name, $data);

        $this->assertEquals($expected, $schema->constraintSql($table, $name));
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
            'ALTER TABLE [posts] ADD CONSTRAINT [author_fk] FOREIGN KEY ([author_id]) REFERENCES [authors] ([id]) ON UPDATE CASCADE ON DELETE CASCADE;',
            'ALTER TABLE [posts] ADD CONSTRAINT [category_fk] FOREIGN KEY ([category_id], [category_name]) REFERENCES [categories] ([id], [name]) ON UPDATE CASCADE ON DELETE CASCADE;',
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
            'ALTER TABLE [posts] DROP CONSTRAINT [author_fk];',
            'ALTER TABLE [posts] DROP CONSTRAINT [category_fk];',
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
            ])
            ->addColumn('body', ['type' => 'text'])
            ->addColumn('data', ['type' => 'json'])
            ->addColumn('hash', [
                'type' => 'char',
                'length' => 40,
                'collate' => 'Latin1_General_BIN',
                'null' => false,
            ])
            ->addColumn('created', 'datetime')
            ->addColumn('created_with_default', [
                'type' => 'datetime',
                'default' => 'sysdatetime()',
            ])
            ->addColumn('created_with_precision', [
                'type' => 'datetime',
                'precision' => 3,
            ])
            ->addColumn('created_without_precision', [
                'type' => 'datetime',
                'precision' => 0,
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id'],
            ])
            ->addIndex('title_idx', [
                'type' => 'index',
                'columns' => ['title'],
            ]);

        $expected = <<<SQL
CREATE TABLE [schema_articles] (
[id] INTEGER IDENTITY(1, 1),
[title] NVARCHAR(255) NOT NULL,
[body] NVARCHAR(MAX),
[data] NVARCHAR(MAX),
[hash] NCHAR(40) COLLATE Latin1_General_BIN NOT NULL,
[created] DATETIME2,
[created_with_default] DATETIME2 DEFAULT SYSDATETIME(),
[created_with_precision] DATETIME2(3),
[created_without_precision] DATETIME2(0),
PRIMARY KEY ([id])
)
SQL;
        $result = $table->createSql($connection);

        $this->assertCount(2, $result);
        $this->assertSame(str_replace("\r\n", "\n", $expected), str_replace("\r\n", "\n", $result[0]));
        $this->assertSame(
            'CREATE INDEX [title_idx] ON [schema_articles] ([title])',
            $result[1]
        );
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
        $this->assertSame('DROP TABLE [schema_articles]', $result[0]);
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
        $this->assertCount(2, $result);
        $this->assertSame('DELETE FROM [schema_articles]', $result[0]);
        $this->assertSame(
            "IF EXISTS (SELECT * FROM sys.identity_columns WHERE OBJECT_NAME(OBJECT_ID) = 'schema_articles' AND last_value IS NOT NULL) " .
            "DBCC CHECKIDENT('schema_articles', RESEED, 0)",
            $result[1]
        );
    }

    /**
     * Get a schema instance with a mocked driver/pdo instances
     */
    protected function _getMockedDriver(): Driver
    {
        $driver = new Sqlserver();
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
